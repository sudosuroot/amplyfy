<?php

require_once '/usr/include/php/callistoV4/CheckinComments.php';

/**
 * Insert Comments. 
 *
 * @uri /InsertCheckinComments
 */
class InsertCheckinCommentResource extends Resource {

	/**
	 * Handle a POST request for this resource
	 * @param Request request
	 * @return Response
	 */
	function post($request) {

		$response = new Response($request);
		$message = '';
		if ($this->checkMinArgs($_POST)) {
			$comment = new CheckinComments();
			$insert_array = $this->addTimestamp($_POST);
			//$mongo_start = new MongoDate($insert_array['start']);
			//$insert_array['start'] = $mongo_start;
			$result = $comment->insertComment($insert_array);
			if ($result) {
				$message = array('msg' => 'comment successfully posted');
				$response->code = Response::OK;
			} else {
				$message = array('msg' => 'Error in posting review');
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


	/**
	 * Check for some minimum attributes.
	 */
	function checkMinArgs($user_args) {
		//Put token
		$values = array('update_id', 'uid', 'comment', 'listing_id', 'time');
		foreach ($values as $value) {
			if(!isset($user_args[$value])) {
				return false;
			}
		}
		return true;
	}

	function addTimestamp($user_args){
		$user_args["created_on"] = new MongoDate(time());	
		return $user_args;
	}
}

?>
