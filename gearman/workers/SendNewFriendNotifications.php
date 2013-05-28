<?php

require_once '/usr/include/php/callistoV3/DbHandler.php';
require_once '/usr/include/php/callistoV3/Friends.php';
require_once '/usr/include/php/callistoV3/CreateUser.php';
require_once '/usr/include/php/callistoV3/Notifications.php';
require_once '/usr/include/php/callistoV3/SearchUser.php';
require_once '/usr/include/php/callistoV3/Updates.php';
# Create our worker object.

//$log[] = "Success";
function SendNewFriendNotifications($job, &$log){
	$success = true;
	$dbHandle = DbHandler::getConnection();
	$user_collection = $dbHandle->users;
	$notification_collection = $dbHandle->notifications;
        $user_args= json_decode ($job->workload(), true);
        $log[] = "Received job: " . $job->handle() . "\n";
	$friendResource = new Friends();
	$notification = new Notifications();
	$users = new CreateUser();
	$search = new SearchUser();
	$poke_friends = array();
	$friends_uids = array();
	$uid = $user_args['uid'];
	$name = "Unknown";
	//$details = $user_collection->findOne(array('uid' => intval($uid)), array('name', 'fbid', 'country'));
	$myfbid = $user_args['fbid'];
        if (isset ($user_args['name'])){
                $name = $user_args['name'];
        }

	$friends = $friendResource->getFriends($uid, false);
	foreach ($friends as $friend){
		if ($friend['fbid'] == $myfbid){
			continue;
		}
		else{
        		array_push ($poke_friends, "".$friend['uid']."");
                	array_push ($friends_uids, intval ($friend['uid']));
		}
        }
	if (count ($poke_friends) == 0){
		$log[] = "No friends to send notifications";
		return;
	}
	
	$device_tokens = $users->getDeviceTokens($friends_uids);
	$type = 6; //friend addition.
	$message = "Nice! Your friend ".$name. " has joined amplyfy.me";
	$country = "IN_airtel"; //get this from user table. 
	$args = array ('from_id' => $uid, 'message' => $message, 'type' => $type, 'is_read' => 0, 'created_on' => new MongoDate(time()), 'country' => $country);
	$result = $notification->insertNotification($args, $poke_friends, $device_tokens, $message);
    	if ($result) {
               	$log[] = "Notifications successfully posted";
        } else {
             	$log[] = "Error in posting notifications";
        }
	$update = new Updates();
	$friend_notif = $update->getUpdatesOfFriends("".$uid, "".$myfbid, $poke_friends);
	if (count ($friend_notif) != 0){
		$success = $notification_collection->batchInsert($friend_notif);
	}
	if ($success){
		$log[] = "Notifications of friend added to user";
	}
	else{
		$log[] = "Error in adding Notifications";
	}
	return;
}

?>
