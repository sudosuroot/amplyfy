<?php

require_once '/usr/include/php/callistoV4/Friends.php';
require_once '/usr/include/php/callistoV4/DbHandler.php';

/**
 * Add Follower. 
 *
 * @uri /AddFriend
 */
class AddFriendResource extends Resource {

	/**
	 * Handle a POST request for this resource
	 * @param Request request
	 * @return Response
	 */
	function post($request) {

		$response = new Response($request);
		$message = '';
		if (isset ($_POST["friend_uid"]) && isset($_POST["uid"]) ) {
			$friends = new Friends();
			if (isset ($_POST['notification_id'])){
				$result = $friends->addFriend($_POST["uid"], $_POST["friend_uid"], $_POST['notification_id']);
			}
			else{
				$result = $friends->addFriend($_POST["uid"], $_POST["friend_uid"]);
			}
			if ($result) {
				$message = array('msg' => 'Friend addition operation successful');
				$response->code = Response::OK;
			} else {
				$message = array('msg' => 'Error while trying to add a friend.');
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
