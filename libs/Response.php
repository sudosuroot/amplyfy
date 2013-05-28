<?php
function sendRes($message, $status, $time, $next_page = null) {
	if($status == "" || $time == "")
	{
		return null;
	}
	if($message == "")
	{
		$message = Array();
	}	

	$resp['data'] = $message;
	$resp['status'] = $status;
	$resp['ts'] = $time;
	if (!is_null($next_page)){
		$resp['next'] = $next_page;
	}
	return json_encode($resp);


}



?>
