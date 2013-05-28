<?php
require_once '/usr/include/php/callistoV4/Response.php';
require_once '/usr/include/php/callistoV4/Updates.php';
/**
 * Get Friends. 
 *
 * @uri /GetCheckinPoints
 */
class GetCheckinPointsResource extends Resource {

	/**
	 * Handle a POST request for this resource
	 * @param Request request
	 * @return Response
	 */
	function get($request) {

		$response = new Response($request);
		$message = '';
		if (isset($_REQUEST['listing_id']) && isset($_REQUEST['time']) && isset($_REQUEST['update_id'])) {
			$checkin = new Updates();
			$result = $checkin->getCheckinPoints($_REQUEST['update_id'], $_REQUEST["listing_id"], $_REQUEST['time']);
			if ($result) {
				$message = $result;
				$status = 'true';
				$response->code = Response::OK;
			} 
			else {
				$message = 'Error while trying to fetch points';
				$status = 'true';
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
