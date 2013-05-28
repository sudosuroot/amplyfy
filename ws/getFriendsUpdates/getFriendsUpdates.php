<?php
require_once '/usr/include/php/callistoV4/Response.php';
require_once '/usr/include/php/callistoV4/Updates.php';
/**
 * Get Friends. 
 *
 * @uri /GetFriendsUpdates
 */
class GetFriendsUpdatesResource extends Resource {

	/**
	 * Handle a POST request for this resource
	 * @param Request request
	 * @return Response
	 */
	function get($request) {

		$response = new Response($request);
		$message = '';
		if ($_REQUEST["uid"]) {
			$updates = new Updates();
			$result = $updates->getFriendsUpdates($_REQUEST["uid"]);
			if ($result) {
				$message = $result;
				$response->code = Response::OK;
			} 
			else if (count ($result) == 0){
                                $message = array('msg' => 'No updates from friends');
                                $response->code = Response::OK;
			}
			else {
				$message = array('msg' => 'Error while trying to fetch friends updates');
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
