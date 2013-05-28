<?php

require_once dirname(__FILE__).'/DbHandler.php';
require_once dirname(__FILE__).'/Friends.php';
require_once dirname(__FILE__).'/CreateUser.php';
require_once dirname(__FILE__).'/Notifications.php';
# Create our worker object.
$worker= new GearmanWorker();

 
# Add default server (localhost).
$worker->addServer();
 
$worker->addFunction("update_timings_table_for_checkin", "UpdateTimingsTableForCheckin");
$worker->addFunction("update_user_table_for_checkin", "UpdateUserTableForCheckin"); 
$worker->addFunction("send_checkin_notifications", "SendCheckinNotifications");

while (1)
{
  print "Waiting for job...\n";
 
  $ret= $worker->work();
  if ($worker->returnCode() != GEARMAN_SUCCESS)
    break;
}
 
function UpdateTimingsTableForCheckin($job){
	$user_args= json_decode ($job->workload(), true);
  	echo "Received job: " . $job->handle() . "\n";
	$dbHandle = DbHandler::getConnection();
  	$start = new MongoDate($user_args['time']);
  	$listing_id = $user_args['listing_id'];
  	$fbid = $user_args['fbid'];
	echo $start." ".$listing_id." ".$fbid."\n";
  	$timings_collection = $dbHandle->timings;
  	$timing_update = $timings_collection->update (array ("listing_id" => intval ($listing_id), "start" => $start), array('$push' => array("fbids_watching" => $fbid), '$inc' => array("view_count" => 1)));
	var_dump ($timing_update);
  	if (!$timing_update){
		error_log ("Not able to update timings table");
  	}
	else{
		echo "Update Successfull";
	}
	return;
}

function UpdateUserTableForCheckin($job){
	$dbHandle = DbHandler::getConnection();
	$user_args= json_decode ($job->workload(), true);
	echo "Received job: " . $job->handle() . "\n";
	$timings_collection = $dbHandle->timings;
        $user_collection = $dbHandle->users;
        $start = new MongoDate ($user_args['time']);
        $listing_id = $user_args['listing_id'];
	$uid = $user_args['uid'];
	$created_on = new MongoDate ($user_args['created_on']);
        $listing_detail = $timings_collection->findOne (array ("listing_id" => intval ($listing_id), "start" => $start));
        $update_set = array ('$set' => array('last_update' => array('listing' => $listing_detail, 'created_on' => $created_on)));
        $user_update = $user_collection->update(array('uid' => intval($uid)), $update_set);
	if (!$user_update){
		error_log ('Not able to update user table');
	}
	else{
		echo "Update successfull\n";
	}
	return;
}


function SendCheckinNotifications($job){
	$dbHandle = DbHandler::getConnection();
        $user_args= json_decode ($job->workload(), true);
        echo "Received job: " . $job->handle() . "\n";
	$friendResource = new Friends();
	$notification = new Notifications();
	$users = new CreateUser();
	$poke_friends = array();
	$friends_uids = array();
	$uid = $user_args['uid'];

        $arr_poke_friends = $friendResource->getFriends($uid, false);
	foreach ($arr_poke_friends as $friend){
        	array_push ($poke_friends, "".$friend['uid']."");
                array_push ($friends_uids, intval ($friend['uid']));
        }
	$device_tokens = $users->getDeviceTokens($friends_uids);
	$type = 2; //checkin updates
	$ch_id = $user_args['ch_id'];
	$listing_id = $user_args['listing_id'];
	$start = $user_args['time'];
	$message = $user_args['update'];
	$country = $user_args['country'];
	$created_on = new MongoDate ($user_args['created_on']);
	$args = array ('from_id' => $uid, 'message' => $message, 'type' => $type, 'is_read' => 0, 'ch_id' => $ch_id, 'listing_id' => $listing_id, 'created_on' => $created_on, 'country' => $country, 'start' => new MongoDate ($start) );

	$result = $notification->insertNotification($args, $poke_friends, $device_tokens);
    	if ($result) {
               	error_log ('Notifications successfully posted');
        } else {
             	error_log ('Error in posting notifications');
        }
	return;
}

?>
