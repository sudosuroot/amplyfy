<?php

require_once '/usr/include/php/callistoV4/DbHandler.php';
require_once '/usr/include/php/callistoV4/Friends.php';
require_once '/usr/include/php/callistoV4/CreateUser.php';
require_once '/usr/include/php/callistoV4/Notifications.php';
require_once '/usr/include/php/callistoV4/SearchUser.php';
require_once '/usr/include/php/callistoV4/CheckinComments.php';
# Create our worker object.

//$log[] = "Success";
function SendCheckinLikesNotificationsV4($job, &$log){
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
	if (is_null ($uid) || $uid == "" || is_null ($update_id) || $update_id == "" || is_null ($listing_id) || $listing_id == ""){
		$log[] = "Error uid or update or listing_id is null. returning.";
		return;
	}
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
	//$list_details = $updates->getListingDetails($listing_id, "IN_airtel", new MongoDate ($timestamp));
	if ($liked_user_name == $checkedin_user_name){
		//$message = "$liked_user_name liked his checkin to ".$list_details['listing_name'];
		$message = "$liked_user_name liked his checkin to";
	}
	else{
		//$message = "$liked_user_name liked $checkedin_user_name's checkin to ".$list_details['listing_name'];
		$message = "$liked_user_name liked $checkedin_user_name's checkin to";
	}

	$device_tokens = $users->getDeviceTokens($friends_uids);
	$type = 8; //only push for comments on checkins
	$args = array ('from_id' => $uid, 'message' => $message, 'type' => $type, 'is_read' => 0, 'checkin_id' => $update_id, 'listing_id' => $listing_id, 'start' => new MongoDate ($timestamp), 'country' => 'IN_airtel', 'created_on' => $created_on);
	//$args = array ('from_id' => $uid, 'message' => $message, 'type' => $type, 'is_read' => 0);
	$result = $notification->insertNotification($args, $poke_friends, $device_tokens, $message, false);
    	if ($result) {
               	$log[] = "Like Notifications successfully posted with message = $message";
        } else {
             	$log[] = "Error in posting notifications";
        }
	return;
}

?>
