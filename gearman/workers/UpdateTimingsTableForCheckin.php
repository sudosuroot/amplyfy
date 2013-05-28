<?php

require_once '/usr/include/php/callistoV3/DbHandler.php';
require_once '/usr/include/php/callistoV3/Friends.php';
require_once '/usr/include/php/callistoV3/CreateUser.php';
require_once '/usr/include/php/callistoV3/Notifications.php';
# Create our worker object.


function UpdateTimingsTableForCheckin($job, &$log){
	$user_args= json_decode ($job->workload(), true);
  	$log[] = "Received job: " . $job->handle() . "\n";
	$dbHandle = DbHandler::getConnection();
  	$start = new MongoDate($user_args['time']);
  	$listing_id = $user_args['listing_id'];
  	$fbid = $user_args['fbid'];
  	$timings_collection = $dbHandle->timings;
	$log[] = "listing id = $listing_id start = $start fbid = $fbid";
  	$timing_update = $timings_collection->update (array ("listing_id" => intval ($listing_id), "start" => $start), array('$push' => array("fbids_watching" => $fbid), '$inc' => array("view_count" => 1)));
  	if (!$timing_update){
		$log[] = "Not able to update timings table";
  	}
	else{
		$log[] = "Update Successfull";
	}
	return;
}


?>
