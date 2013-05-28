<?php
require_once '/usr/include/php/callistoV4/Response.php';
require_once '/usr/include/php/callistoV4/Conversations.php';
/**
 * Get Friends. 
 *
 * @uri /GetConversations
 */
class GetConversationsResource extends Resource {

	/**
	 * Handle a POST request for this resource
	 * @param Request request
	 * @return Response
	 */
	function get($request) {

		$response = new Response($request);
		$message = '';
		$result;
		$timestamp = null;
		$conversations = new Conversations();
		if (isset ($_REQUEST["listing_id"]) && !isset($_REQUEST["uid"])) {
			$result = $conversations->getThreadsForListing($_REQUEST["listing_id"]);
		}

		else if (isset ($_REQUEST["uid"])){
			if (isset($_REQUEST["listing_id"])){
				$result = $conversations->getUserThreads($_REQUEST["uid"], $_REQUEST["listing_id"]);
			}
			else{
				$result = $conversations->getUserThreads($_REQUEST["uid"]);
			}
		}

		else if (isset ($_REQUEST['thread_id'])){
			$result = $conversations->getThread($_REQUEST['thread_id']);
		}

		else{
			$status = 'Essential attributes missing.';
                        $response->code = Response::INTERNALSERVERERROR;
			$response->addHeader('Content-type', 'application/json');
                	$response->body = sendRes($message, $status, time());
                	return $response;
		}
		if ($result) {
			$message = $result;
			$status = 'true';
			$response->code = Response::OK;
		} 
		else if (count ($result) == 0){
                               $status = 'No conversations';
                               $response->code = Response::OK;
		}
		else {
			$status = 'Error while trying to fetch conversations';
			$response->code = Response::INTERNALSERVERERROR;
		}
		$response->addHeader('Content-type', 'application/json');
		$response->body = sendRes($message, $status, time());
		return $response;
	}
}

?>
