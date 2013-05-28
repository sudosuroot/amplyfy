<?php

require_once '/usr/include/php/callistoV4/Conversations.php';

/**
 * Insert Updates. 
 *
 * @uri /InsertMessage
 */
class InsertMessageResource extends Resource {

	/**
	 * Handle a POST request for this resource
	 * @param Request request
	 * @return Response
	 */
	function post($request) {

		$response = new Response($request);
		$message = '';
		if ($this->checkMinArgs($_REQUEST)) {
			$conversation = new Conversations();
			$insert_array = $this->addTimestamp($_REQUEST);
			$status = $conversation->insertMessage($insert_array);
			if ($status) {
				$message = array('msg' => 'Message posted successfully');
				$response->code = Response::OK;
			} else {
				$message = array('msg' => 'Error in posting the message');
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
		$values = array('fbid', 'message', 'thread_id');
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
