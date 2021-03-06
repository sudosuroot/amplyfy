<?php

require_once '/usr/include/php/callistoV3/DbHandler.php';
require_once '/usr/include/php/callistoV3/Friends.php';
require_once '/usr/include/php/callistoV3/CreateUser.php';
require_once '/usr/include/php/callistoV3/Notifications.php';
require_once '/usr/include/php/callistoV3/SearchUser.php';
# Create our worker object.

//$log[] = "Success";
function SendCheckinNotifications($job, &$log){
	$dbHandle = DbHandler::getConnection();
	$user_collection = $dbHandle->users;
        $user_args= json_decode ($job->workload(), true);
        $log[] = "Received job: " . $job->handle() . "\n";
	$friendResource = new Friends();
	$notification = new Notifications();
	$users = new CreateUser();
	$poke_friends = array();
	$friends_uids = array();
	$uid = $user_args['uid'];
	$name = "Unknown";
	$details = $user_collection->findOne(array('uid' => intval($uid)), array('name'));
	if (isset($details['name'])){
		$name = $details['name'];
	}
        $arr_poke_friends = $friendResource->getFriends($uid, false);
	foreach ($arr_poke_friends as $friend){
		if ("".$uid == "".$friend['uid']){
			array_push ($poke_friends, "".$friend['uid']."");
		}
		else{
			array_push ($poke_friends, "".$friend['uid']."");
                	array_push ($friends_uids, intval ($friend['uid']));
		}
        }
	$device_tokens = $users->getDeviceTokens($friends_uids);
	$type = 2; //checkin updates
	$ch_id = $user_args['ch_id'];
	$listing_id = $user_args['listing_id'];
	$start = $user_args['time'];
	$message = $user_args['update'];
	$country = $user_args['country'];
	$created_on = new MongoDate ($user_args['created_on']);
	if (isset ($user_args['fsplacename']) && isset ($user_args['fsplaceid'])){
		$log[] = "place recieved";
		$fsplacename = $user_args['fsplacename'];
		$fsplaceid = $user_args['fsplaceid'];
		$args = array ('from_id' => $uid, 'message' => $message, 'type' => $type, 'is_read' => 0, 'ch_id' => $ch_id, 'listing_id' => $listing_id, 'created_on' => $created_on, 'country' => $country, 'start' => new MongoDate ($start), 'fsplacename' => $fsplacename, 'fsplaceid' => $fsplaceid);
	}
	else{
		$args = array ('from_id' => $uid, 'message' => $message, 'type' => $type, 'is_read' => 0, 'ch_id' => $ch_id, 'listing_id' => $listing_id, 'created_on' => $created_on, 'country' => $country, 'start' => new MongoDate ($start));
	}
	$message = $name." is watching";
	$result = $notification->insertNotification($args, $poke_friends, $device_tokens, $message);
    	if ($result) {
               	$log[] = "Notifications successfully posted";
        } else {
             	$log[] = "Error in posting notifications";
        }
	return;
}

?>
