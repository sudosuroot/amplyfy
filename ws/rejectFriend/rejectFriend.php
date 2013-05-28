<?php

require_once '/usr/include/php/callistoV4/Notifications.php';
require_once '/usr/include/php/callistoV4/DbHandler.php';

/**
 * Add Follower. 
 *
 * @uri /RejectFriend
 */
class RejectFriendResource extends Resource {

	/**
	 * Handle a POST request for this resource
	 * @param Request request
	 * @return Response
	 */
	function post($request) {

		$response = new Response($request);
		$message = '';
		if (isset ($_POST["notification_id"])) {
			$notif = new Notifications();
			$result = $notif->deleteNotification($_POST['notification_id']); 
			if ($result) {
				$message = array('msg' => 'Friend rejection operation successful');
				$response->code = Response::OK;
			} else {
				$message = array('msg' => 'Error while rejecting a friend.');
				$response->code = Response::INTERNALSERVERERROR;
			}
		} else {
			$message = array('msg' => 'Essential attributes missing.');
			$response->code = Response::INTERNALSERVERERROR;

		}
		$response->addHeader('Content-type', 'application/json');
		$response->body = json_encode($message);
		return $response;
	}
}

?>
