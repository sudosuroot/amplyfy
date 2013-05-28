<?php

require_once '/usr/include/php/callistoV4/Response.php';
require_once '/usr/include/php/callistoV4/Friends.php';
require_once '/usr/include/php/callistoV4/DbHandler.php';
require_once '/usr/include/php/callistoV4/SearchUser.php';
/**
 * Get Friends. 
 *
 * @uri /GetFriends
 */
class GetFriendsResource extends Resource {

	/**
	 * Handle a POST request for this resource
	 * @param Request request
	 * @return Response
	 */
	function get($request) {

		$response = new Response($request);
		$message = '';
		$status = true;
		if ($_REQUEST["uid"]) {
			$friends = new Friends();
			if (isset ($_REQUEST['leaderboard'])){
				$result = $friends->getLeaderBoardSnapshot($_REQUEST["uid"]);
			}
			else{
				$result = $friends->getFriends($_REQUEST["uid"]);
			}
			if ($result) {
				$message = $result;
				$status = 'true';
				$response->code = Response::OK;
			} 
			else if ($result == null) {
				$message = array('msg' => 'Invalid access token');
				$status = 'false';
				$response->code = Response::INTERNALSERVERERROR;
			}
			else if (count ($result) == 0){
				$message = array('msg' => 'No friends');
				$status = 'false';
				$response->code = Response::OK;
			}
			else {
				$message = array('msg' => 'Error while trying to fetch friends');
				$status = 'false';
				$response->code = Response::INTERNALSERVERERROR;
			}
		} else {
			$message = array('msg' => 'Essential attributes missing.');
			$status = 'false';
			$response->code = Response::INTERNALSERVERERROR;

		}
		$response->addHeader('Content-type', 'application/json');
		$response->body = sendRes($message, $status, time());
		return $response;
	}
}

?>
