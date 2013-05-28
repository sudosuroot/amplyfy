<?php
require_once '/usr/include/php/callistoV4/Updates.php';
require_once '/usr/include/php/callistoV4/Response.php';
/**
 * Get Friends. 
 *
 * @uri /GetGlobalTrends
 */
class GetGlobalTrendsResource extends Resource {

	/**
	 * Handle a POST request for this resource
	 * @param Request request
	 * @return Response
	 */
	function get($request) {

		$response = new Response($request);
		$message = '';
		$updates = new Updates();
			$result = $updates->getGlobalTrends();
			if ($result) {
				$message = $result;
				$response->code = Response::OK;
			} 
			else if (count ($result) == 0){
                                $message = array('msg' => 'No updates');
                                $response->code = Response::OK;
			}
			else {
				$message = array('msg' => 'Error while trying to fetch trends');
				$response->code = Response::INTERNALSERVERERROR;
			}
		$response->addHeader('Content-type', 'application/json');
		$response->body = sendRes($message, 'true', time());
		return $response;
	}
}

?>
