<?php

require_once '/usr/include/php/callistoV4/Friends.php';
require_once '/usr/include/php/callistoV4/DbHandler.php';
require_once '/usr/include/php/callistoV4/SearchUser.php';
require_once '/usr/include/php/callistoV4/Response.php';
/**
 * Get Notification. 
 *
 * @uri /GetNotificationCount
 */
class GetNotificationCountResource extends Resource {

	/**
	 * Handle a POST request for this resource
	 * @param Request request
	 * @return Response
	 */
	function get($request) {

		$response = new Response($request);
		$message = '';
		$count = 10;
		if ($_REQUEST["uid"]) {
			if (array_key_exists ("count", $_REQUEST)){
				$count = $_REQUEST["count"];
			}
			$notifications = new Notifications();
			$result = $notifications->getNotificationCount($_REQUEST["uid"], $count);
			if ($result) {
				$message = $result;
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
		$response->body = sendRes($message, 'true', time());
		return $response;
	}
}

?>
