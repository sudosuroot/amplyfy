<?php

require_once '/usr/include/php/callistoV3/DbHandler.php';
require_once '/usr/include/php/callistoV3/Points.php';
# Create our worker object.



function UpdateUserPoints($job, &$log){
	$points = new Points();
	$user_args= json_decode ($job->workload(), true);
	$uid = $user_args['uid'];
	$point = $user_args['points'];
	$log[] = "Received job: " . $job->handle() . "\n";
	$user_points = $points->setPoints($uid, $point);
	if ($user_points){
		$log[] = "User $uid updates with $user_points points";
	}
	else{
		$log[] = "Failed to update $uid with points";
	}
	return;
}



?>
