<?php

require_once '/usr/include/php/callistoV4/Response.php';
/**
 * Get Notification. 
 *
 * @uri /GetNotifications
 */
class GetNotificationsResource extends Resource {

	/**
	 * Handle a POST request for this resource
	 * @param Request request
	 * @return Response
	 */
	function get($request) {

		$response = new Response($request);
		$message = '';
		$count = 25;
		$ts = null;
		if ($_REQUEST["uid"]) {
			$uid = $_REQUEST["uid"];
			$next = "http://amplyfy.me/V4/Callisto/GetNotifications?uid=$uid";
			if (array_key_exists ("count", $_REQUEST)){
				$count = $_REQUEST["count"];
			}
			if (array_key_exists ("ts", $_REQUEST)){
                                $ts = $_REQUEST["ts"];
                        }
			$notifications = new Notifications();
			if (array_key_exists ("friends", $_REQUEST)){
				$result = $notifications->getLatestFriendNotifications($uid, $count, $ts);
				$next = "http://amplyfy.me/V4/Callisto/GetNotifications?friends=true&uid=$uid";
			}
			else{
				$result = $notifications->GroupNotifications($uid, $count,$ts);
				$next = "http://amplyfy.me/V4/Callisto/GetNotifications?uid=$uid";
			}
			//$result = $notifications->getLatestNotifications($_REQUEST["uid"], $count);
			if ($result) {
				$last_ts = $result[count($result) - 1]['created_on']->sec;
				$next .= "&ts=$last_ts";
				$message = $result;
				$response->code = Response::OK;
			}
			else if (count($result) == 0) {
				$message = array('msg' => 'No new notifications');
                                $response->code = Response::OK;
			}
			 else {
				$message = array('msg' => 'Error while trying to get friends.');
				$response->code = Response::INTERNALSERVERERROR;
			}
		} else if (isset ($_REQUEST["notificationid"])){
			$notif_id = $_REQUEST["notificationid"];
			$notifications = new Notifications();
			$result = $notifications->GroupNotifications(null, null, null, $notif_id);
			if ($result) {
				$message = $result;
                                $response->code = Response::OK;
			}
			else if (count($result) == 0) {
                                $message = array('msg' => 'No new notifications');
                                $response->code = Response::OK;
                        }
                         else {
                                $message = array('msg' => 'Error while trying to get friends.');
                                $response->code = Response::INTERNALSERVERERROR;
                        }
		} else {
			$message = array('msg' => 'Essential attributes missing.');
			$response->code = Response::INTERNALSERVERERROR;

		}
		$response->addHeader('Content-type', 'application/json');
		$response->body = sendRes($message, 'true', time(), $next);
		return $response;
	}
}

?>
