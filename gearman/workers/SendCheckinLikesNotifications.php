<?php

require_once '/usr/include/php/callistoV3/DbHandler.php';
require_once '/usr/include/php/callistoV3/Friends.php';
require_once '/usr/include/php/callistoV3/CreateUser.php';
require_once '/usr/include/php/callistoV3/Notifications.php';
require_once '/usr/include/php/callistoV3/SearchUser.php';
require_once '/usr/include/php/callistoV3/CheckinComments.php';
# Create our worker object.

//$log[] = "Success";
function SendCheckinLikesNotifications($job, &$log){
	$dbHandle = DbHandler::getConnection();
	$user_collection = $dbHandle->users;
	$channel_collection = $dbHandle->channels;
        $user_args= json_decode ($job->workload(), true);
        $log[] = "Received job: " . $job->handle() . "\n";

	//args from comments
	$update_id = $user_args['update_id'];
	$listing_id = $user_args['listing_id'];
	$updates = new Updates();
	$timestamp = $user_args['time'];
	$uid = $user_args['uid'];
	$created_on = new MongoDate(time());
	$users = new CreateUser();
	$notification = new Notifications();
	$comments = new CheckinComments();
	$comment_likes = $comments->getCheckinLikes($update_id, $listing_id, $timestamp);
	$friends_uids = array();
	$poke_friends = array();
	foreach ($comment_likes as $likes){
		$liked_uid = $likes['uid'];
		if (!in_array($likes['uid'], $friends_uids) && $likes['uid'] != $uid){
			$friends_uids[] = intval ($likes['uid']);
			$poke_friends[] = "".$likes['uid'];
			$log[] = "adding for notification ". $liked_uid;
		}
	}

	if ($uid != $update_id){
		$friends_uids[] = intval ($update_id); //add checked in users id too.
		$poke_friends[] = "".$update_id;
	}

	$checkedin_user = $user_collection->findOne(array('uid' => intval ($update_id)), array('name'));
	$checkedin_user_name = $checkedin_user['name'];
	$liked_user = $user_collection->findOne(array('uid' => intval ($uid)), array('name'));
	$liked_user_name = $liked_user['name'];
	$list_details = $updates->getListingDetails($listing_id, "IN_airtel", new MongoDate ($timestamp));
	if ($liked_user_name == $checkedin_user_name){
		$message = "$liked_user_name liked his checkin to ".$list_details['listing_name'];
	}
	else{
		$message = "$liked_user_name liked $checkedin_user_name's checkin to ".$list_details['listing_name'];
	}

	$device_tokens = $users->getDeviceTokens($friends_uids);
	$type = 7; //only push for comments on checkins
	//$args = array ('from_id' => $uid, 'message' => $message, 'type' => $type, 'is_read' => 0, 'ch_id' => $ch_id, 'listing_id' => $listing_id, 'start' => new MongoDate ($timestamp), 'country' => 'IN_airtel', 'created_on' => $created_on);
	$args = array ('from_id' => $uid, 'message' => $message, 'type' => $type, 'is_read' => 0);
	$result = $notification->insertNotification($args, $poke_friends, $device_tokens, $message, true);
    	if ($result) {
               	$log[] = "Notifications successfully posted with message = $message";
        } else {
             	$log[] = "Error in posting notifications";
        }
	return;
}

?>
