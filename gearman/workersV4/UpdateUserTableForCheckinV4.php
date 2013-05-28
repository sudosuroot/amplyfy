<?php

require_once '/usr/include/php/callistoV4/DbHandler.php';
require_once '/usr/include/php/callistoV4/Friends.php';
require_once '/usr/include/php/callistoV4/CreateUser.php';
require_once '/usr/include/php/callistoV4/Notifications.php';
require_once "/usr/include/php/callistoV4/UserProfile.php";
# Create our worker object.



function UpdateUserTableForCheckinV4($job, &$log){
	$dbHandle = DbHandler::getConnection();
	$user_args= json_decode ($job->workload(), true);
	$log[] = "Received job: " . $job->handle() . "\n";
	$timings_collection = $dbHandle->timings;
        $user_collection = $dbHandle->users;
        $start = new MongoDate ($user_args['time']);
        $listing_id = $user_args['listing_id'];
	$uid = $user_args['uid'];
	$created_on = new MongoDate ($user_args['created_on']);
        $listing_detail = $timings_collection->findOne (array ("listing_id" => intval ($listing_id), "start" => $start));
        $update_set = array ('$set' => array('last_update' => array('listing' => $listing_detail, 'created_on' => $created_on)));
        $user_update = $user_collection->update(array('uid' => intval($uid)), $update_set);
	if (!$user_update){
		$log[] = "Not able to update user table";
	}
	else{
		$log[] = "Update successfull\n";
	}
	$profile = new UserProfile($uid);
        $up_status = $profile->IncrementGenreSubGenreTrends($listing_id);
	if ($up_status){
		$log[] = "User profile updated successfully\n";
	}
	else{
		$log[] = "Error in updating user profile\n";
	}
	return;
}



?>
