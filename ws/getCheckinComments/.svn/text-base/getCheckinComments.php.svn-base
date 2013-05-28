<?php
require_once '/usr/include/php/callistoV4/Response.php';
require_once '/usr/include/php/callistoV4/CheckinComments.php';
/**
 * Get Friends. 
 *
 * @uri /GetCheckinComments
 */
class GetCheckinCommentsResource extends Resource {

	/**
	 * Handle a POST request for this resource
	 * @param Request request
	 * @return Response
	 */
	function get($request) {

		$response = new Response($request);
		$message = '';
		if (isset($_REQUEST['listing_id']) && isset($_REQUEST['time']) && isset($_REQUEST['update_id'])) {
			$comments = new CheckinComments();
			$result = $comments->getCheckinComments($_REQUEST['update_id'], $_REQUEST["listing_id"], $_REQUEST['time']);
			if ($result) {
				$message = $result;
				$status = 'true';
				$response->code = Response::OK;
			} 
			else if (count ($result) == 0){
                                $message = 'No comments';
				$status = 'false';
                                $response->code = Response::OK;
			}
			else {
				$message = 'Error while trying to fetch friends updates';
				$status = 'false';
				$response->code = Response::INTERNALSERVERERROR;
			}
		} else {
			$message = 'Essential attributes missing.';
			$status = 'false';
			$response->code = Response::INTERNALSERVERERROR;

		}
		$response->addHeader('Content-type', 'application/json');
		$response->body = sendRes($message, $status, time());
		return $response;
	}
}

?>
