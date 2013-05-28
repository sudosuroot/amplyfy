<?php

require_once '/usr/include/php/callistoV4/DbHandler.php';
require_once '/usr/include/php/callistoV4/Friends.php';
require_once '/usr/include/php/callistoV4/CreateUser.php';
require_once '/usr/include/php/callistoV4/Notifications.php';
require_once '/usr/include/php/callistoV4/SearchUser.php';
require_once '/usr/include/php/callistoV4/Updates.php';
# Create our worker object.

//$log[] = "Success";
function SendFriendAcceptNotificationsV4($job, &$log){
	$success = true;
	$dbHandle = DbHandler::getConnection();
	$user_collection = $dbHandle->users;
	$notification_collection = $dbHandle->notifications;
        $user_args= json_decode ($job->workload(), true);
        $log[] = "Received job: " . $job->handle() . "\n";
	$notification = new Notifications();
	$users = new CreateUser();
	$search = new SearchUser();
	$poke_friends = array();
	$friends_uids = array();
	$user_uid = intval ($user_args['user_uid']);
	$friend_uid = intval ($user_args['friend_uid']);
	$name = "Unknown";
	$user_details = $user_collection->findOne(array('uid' => $user_uid), array('name'));
        if (isset ($user_details['name'])){
                $name = $user_details['name'];
        }
	if (isset ($friend_uid)){
        	array_push ($poke_friends, "".$friend_uid."");
               	array_push ($friends_uids, $friend_uid);
	}
	if (count ($poke_friends) == 0){
		$log[] = "No friends to send notifications";
		return;
	}
	
	$device_tokens = $users->getDeviceTokens($friends_uids);
	$type = 10; //friend accept.
	$message = "Nice! ".$name. " has accepted your friend request";
	$country = "IN_airtel"; //get this from user table. 
	$args = array ('from_id' => $user_uid, 'message' => $message, 'type' => $type, 'is_read' => 0, 'created_on' => new MongoDate(time()), 'country' => $country);
	$result = $notification->insertNotification($args, $poke_friends, $device_tokens, $message);
    	if ($result) {
               	$log[] = "Notifications successfully posted";
        } else {
             	$log[] = "Error in posting notifications";
        }
	return;
}

?>
