<?php
require_once '/usr/include/php/callistoV4/Response.php';
/**
 * Get Top Shows
 *
 * @uri /GetTopShows
 */
class GetTopShowsResource extends Resource {

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
			if (isset ($_REQUEST['country'])){
				$result = $updates->GetTopListings($_REQUEST["uid"], $_REQUEST['country']);
			}
			else{
				$result = $updates->GetTopListings($_REQUEST["uid"]);
			}
			if ($result) {
				$message = $result;
				$response->code = Response::OK;
			} 
			else if (count ($result) == 0){
                                $message = array('msg' => 'No shows trending');
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
