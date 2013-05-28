<?php

require_once '/usr/include/php/callistoV4/DbHandler.php';
require_once '/usr/include/php/callistoV4/Friends.php';
require_once '/usr/include/php/callistoV4/CreateUser.php';
require_once '/usr/include/php/callistoV4/Notifications.php';
require_once '/usr/include/php/callistoV4/SearchUser.php';
# Create our worker object.

//$log[] = "Success";
function SendShowLikeNotificationsV4($job, &$log){
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
	//$device_tokens = $users->getDeviceTokens($friends_uids);
	$type = 9; //show likes
	$listing_id = $user_args['listing_id'];
	$country = "IN_airtel";
	$created_on = new MongoDate ($user_args['created_on']);
	$message = $name." likes ";
	$args = array ('from_id' => $uid, 'message' => $message, 'type' => $type, 'is_read' => 0, 'listing_id' => $listing_id, 'created_on' => $created_on, 'country' => $country);
	$result = $notification->insertNotification($args, $poke_friends);
    	if ($result) {
               	$log[] = "Show like Notifications successfully posted";
        } else {
             	$log[] = "Error in posting notifications";
        }
	return;
}

?>
