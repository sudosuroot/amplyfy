<?php

require_once '/usr/include/php/callistoV3/DbHandler.php';
require_once '/usr/include/php/callistoV3/Friends.php';
require_once '/usr/include/php/callistoV3/CreateUser.php';
require_once '/usr/include/php/callistoV3/Notifications.php';
require_once '/usr/include/php/callistoV3/SearchUser.php';
require_once '/usr/include/php/callistoV3/CheckinComments.php';
# Create our worker object.

//$log[] = "Success";
function SendCheckinCommentsNotifications($job, &$log){
	$dbHandle = DbHandler::getConnection();
	$user_collection = $dbHandle->users;
        $user_args= json_decode ($job->workload(), true);
        $log[] = "Received job: " . $job->handle() . "\n";

	//args from comments
	$update_id = $user_args['update_id'];
	$listing_id = $user_args['listing_id'];
	$timestamp = $user_args['time'];
	$uid = $user_args['uid'];
	$users = new CreateUser();
	$notification = new Notifications();
	$comments = new CheckinComments();
	$comment_list = $comments->getCheckinComments($update_id, $listing_id, $timestamp);
	$friends_uids = array();
	$poke_friends = array();
	foreach ($comment_list as $comment){
		$commented_uid = $comment['uid'];
		if (!in_array($comment['uid'], $friends_uids) && $comment['uid'] != $uid){
			$friends_uids[] = intval ($comment['uid']);
			$poke_friends[] = "".$comment['uid'];
			$log[] = "adding for notification ". $commented_uid;
		}
	}

	if ($uid != $update_id){
		$friends_uids[] = intval ($update_id); //add checked in users id too.
		$poke_friends[] = "".$update_id;
	}

	$checkedin_user = $user_collection->findOne(array('uid' => intval ($update_id)), array('name'));
	$checkedin_user_name = $checkedin_user['name'];
	$commented_user = $user_collection->findOne(array('uid' => intval ($uid)), array('name'));
	$commented_user_name = $commented_user['name'];

	if ($commented_user_name == $checkedin_user_name){
		$message = "$commented_user_name commented on his checkin";
	}
	else{
		$message = "$commented_user_name commented on $checkedin_user_name's checkin";
	}

	$device_tokens = $users->getDeviceTokens($friends_uids);
	$type = 7; //only push for comments on checkins
	$args = array ('from_id' => $uid, 'message' => $message, 'type' => $type, 'is_read' => 0);
	$result = $notification->insertNotification($args, $poke_friends, $device_tokens, $message, true);
    	if ($result) {
               	$log[] = "Notifications successfully posted";
        } else {
             	$log[] = "Error in posting notifications";
        }
	return;
}

?>
