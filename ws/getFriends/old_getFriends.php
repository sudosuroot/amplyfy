<?php

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
		if ($_REQUEST["uid"]) {
			$friends = new Friends();
			$result = $friends->getFriends($_REQUEST["uid"]);
			if ($result) {
				$search = new SearchUser();
				$resp_arr = array();
				$i = 0;
				foreach ($result as $uid){
					$fbid = $search->getFBIDFromUID($uid);
					$resp_arr[$i++] = array('fbid' => $fbid, 'uid' => $uid);
				}
				$message = $resp_arr;
				$response->code = Response::OK;
			} else {
				$message = array('msg' => 'Error while trying to get friends.');
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
