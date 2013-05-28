<?php
require_once '/usr/include/php/callistoV4/Response.php';
require_once '/usr/include/php/callistoV4/Comments.php';
/**
 * Get Friends. 
 *
 * @uri /GetFriendsComments
 */
class GetFriendsCommentsResource extends Resource {

	/**
	 * Handle a POST request for this resource
	 * @param Request request
	 * @return Response
	 */
	function get($request) {

		$response = new Response($request);
		$message = '';
		if ($_REQUEST["uid"]) {
			$comments = new Comments();
			$result = $comments->getFriendsComments($_REQUEST["uid"]);
			if ($result) {
				$message = $result;
				$response->code = Response::OK;
			} 
			else if (count ($result) == 0){
                                $status = 'No updates from friends';
                                $response->code = Response::OK;
			}
			else {
				$status = 'Error while trying to fetch friends updates';
				$response->code = Response::INTERNALSERVERERROR;
			}
		} else {
			$status = 'Essential attributes missing.';
			$response->code = Response::INTERNALSERVERERROR;

		}
		$response->addHeader('Content-type', 'application/json');
		$status = 'true';
		$response->body = sendRes($message, $status, time());
		return $response;
	}
}

?>
