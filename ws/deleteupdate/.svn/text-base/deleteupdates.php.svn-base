<?php

/**
 * Delete an update.
 *
 * @uri /DeleteUpdates
 */
class DeleteUpdates extends Resource {

	/**
	 * Handle a GET request for this resource
	 * @param Request request
	 * @return Response
	 */
	function get($request) {

		$response = new Response($request);

		if (isset($_REQUEST['status_id'])) {
			$updates = new Updates();
			$result  = $updates->deleteUpdate($_REQUEST['status_id']);	
			if ($result) {
				$message = array('msg' => 'Update succesfully deleted.');
				$response->code = Response::OK;
			} else {
				$message = array('msg' => 'Error while trying to deleting update.');
				$response->code = Response::INTERNALSERVERERROR;
			}

		} else {
			$message = array('msg' => 'Error while trying to deleting update.');
		}
		$etag = md5($request->uri);

		$response->code = Response::OK;
		$response->addHeader('Content-type', 'application/json');
		$response->addEtag($etag);
		$response->body = json_encode($message);

		return $response;

	}
}
