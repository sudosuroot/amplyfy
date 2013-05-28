<?php
require_once '/usr/include/php/callistoV4/Response.php';
require_once '/usr/include/php/callistoV4/Comments.php';
/**
 * Get Friends. 
 *
 * @uri /GetReviews
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
		$timestamp = null;
		if (isset ($_REQUEST["listing_id"])) {
			if (isset ($_REQUEST["timestamp"])){
				$timestamp = $_REQUEST["timestamp"];
			}
			$comments = new Comments();
			if (isset ($_REQUEST["uid"])){
				$result = $comments->getFriendsComments($_REQUEST["uid"], $_REQUEST["listing_id"], $timestamp);
			}
			else{
				$result = $comments->getAllComments($_REQUEST["listing_id"], $timestamp);
			}
			if ($result) {
				$message = $result;
				$status = 'true';
				$response->code = Response::OK;
			} 
			else if (count ($result) == 0){
                                $status = 'No comments from friends';
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
		$response->body = sendRes($message, $status, time());
		return $response;
	}
}

?>
