<?php

require_once '/usr/include/php/callistoV4/DbHandler.php';
require_once '/usr/include/php/callistoV4/Friends.php';
require_once '/usr/include/php/callistoV4/CreateUser.php';
require_once '/usr/include/php/callistoV4/Notifications.php';
require_once '/usr/include/php/callistoV4/SearchUser.php';
require_once '/usr/include/php/callistoV4/CheckinComments.php';
# Create our worker object.

//$log[] = "Success";
function SendCheckinCommentsNotificationsV4($job, &$log){
	$dbHandle = DbHandler::getConnection();
	$user_collection = $dbHandle->users;
        $user_args= json_decode ($job->workload(), true);
        $log[] = "Received job: " . $job->handle() . "\n";

	//args from comments
	$update_id = $user_args['update_id'];
	$listing_id = $user_args['listing_id'];
	$timestamp = $user_args['time'];
	$uid = $user_args['uid'];
	if (is_null ($uid) || $uid == "" || is_null ($update_id) || $update_id == "" || is_null ($listing_id) || $listing_id == ""){
                $log[] = "Error uid or update or listing_id is null. returning.";
                return;
        }
	$users = new CreateUser();
	$notification = new Notifications();
	$comments = new CheckinComments();
	$updates = new Updates();
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
	//$list_details = $updates->getListingDetails($listing_id, "IN_airtel", new MongoDate ($timestamp));
	if ($commented_user_name == $checkedin_user_name){
		//$message = "$commented_user_name commented on his checkin to ".$list_details['listing_name'];
		$message = "$commented_user_name commented on his checkin to";
	}
	else{
		//$message = "$commented_user_name commented on $checkedin_user_name's checkin to ".$list_details['listing_name'];
		$message = "$commented_user_name commented on $checkedin_user_name's checkin to";
	}

	$device_tokens = $users->getDeviceTokens($friends_uids);
	$type = 7; //only push for comments on checkins
	$args = array ('checkin_id' => $update_id, 'from_id' => $uid, 'message' => $message, 'type' => $type, 'is_read' => 0, 'listing_id' => $listing_id, 'start' => new MongoDate($timestamp), 'created_on' => new MongoDate(time()));
	$result = $notification->insertNotification($args, $poke_friends, $device_tokens, $message, false);
    	if ($result) {
               	$log[] = "Notifications successfully posted";
        } else {
             	$log[] = "Error in posting notifications";
        }
	return;
}

?>
