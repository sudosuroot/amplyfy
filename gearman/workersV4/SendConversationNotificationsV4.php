<?php

require_once '/usr/include/php/callistoV4/DbHandler.php';
require_once '/usr/include/php/callistoV4/Friends.php';
require_once '/usr/include/php/callistoV4/CreateUser.php';
require_once '/usr/include/php/callistoV4/Notifications.php';
require_once '/usr/include/php/callistoV4/SearchUser.php';
# Create our worker object.

//$log[] = "Success";
function SendConversationNotificationsV4($job, &$log){
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
	$uid = $user_args['uid'];
	$pFbids = $user_args['fbids'];
	$name = "Unknown";
	$details = $user_collection->findOne(array('uid' => intval($uid)), array('name', 'fbid'));
	$myfbid = $details['fbid'];
	if (isset ($details['name'])){
        	$name = $details['name'];
        }
	foreach ($pFbids as $friend){
		if ($friend == $myfbid){
			next;
		}
		$details = $search->getUserDetailsFromFBID($friend);
        	array_push ($poke_friends, "".$details['uid']."");
                array_push ($friends_uids, intval ($details['uid']));
        }
	$device_tokens = $users->getDeviceTokens($friends_uids);
	$type = 4; //create conversation updates
	$listing_id = $user_args['listing_id'];
	$message = $user_args['message'];
	$country = $user_args['country'];
	$thread_id = $user_args['thread_id'];
	$log[] = "passing thread id = ".$thread_id."\n";
	$created_on = new MongoDate ($user_args['created_on']);
	$args = array ('from_id' => $uid, 'message' => $message, 'type' => $type, 'is_read' => 0, 'listing_id' => $listing_id, 'created_on' => $created_on, 'country' => $country, 'thread_id' => $thread_id);
	$message = $name. " started a discussion around";
	$result = $notification->insertNotification($args, $poke_friends, $device_tokens, $message);
    	if ($result) {
               	$log[] = "Notifications successfully posted";
        } else {
             	$log[] = "Error in posting notifications";
        }
	return;
}

?>
