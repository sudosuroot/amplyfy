<?php

require_once '/usr/include/php/callistoV4/DbHandler.php';
require_once '/usr/include/php/callistoV4/Friends.php';
require_once '/usr/include/php/callistoV4/CreateUser.php';
require_once '/usr/include/php/callistoV4/Notifications.php';
require_once '/usr/include/php/callistoV4/SearchUser.php';
# Create our worker object.

//$log[] = "Success";
function SendMessageNotificationsV4($job, &$log){
	$dbHandle = DbHandler::getConnection();
	$user_collection = $dbHandle->users;
        $user_args= json_decode ($job->workload(), true);
        $log[] = "Received job: " . $job->handle() . "\n";
	$friendResource = new Friends();
	$notification = new Notifications();
	$users = new CreateUser();
	$search = new SearchUser();
	$poke_friends = array();
	$friends_uids = array();
	$name = $user_args['name'];
	$pFbids = $user_args['fbids'];
	foreach ($pFbids as $friend){
		$details = $search->getUserDetailsFromFBID($friend);
        	array_push ($poke_friends, "".$details['uid']."");
                array_push ($friends_uids, intval ($details['uid']));
        }
	$device_tokens = $users->getDeviceTokens($friends_uids);
	$type = 5; //only push for insert message
	$message = $user_args['message'];
	$thread_id = $user_args['thread_id'];
	$log[] = "passing thread id = ".$thread_id."\n";
	$args = array ('from_id' => $uid, 'message' => $message, 'type' => $type, 'is_read' => 0, 'created_on' => $created_on, 'thread_id' => $thread_id);
	$message = $name. " left a new comment";
	$result = $notification->insertNotification($args, $poke_friends, $device_tokens, $message, true);
    	if ($result) {
               	$log[] = "Notifications successfully posted";
        } else {
             	$log[] = "Error in posting notifications";
        }
	return;
}

?>
