<?php

require_once '/usr/include/php/callistoV4/Updates.php';
require_once '/usr/include/php/callistoV4/Response.php';

/**
 * Insert Updates. 
 *
 * @uri /InsertUpdate
 */
class InsertUpdateResource extends Resource {

	/**
	 * Handle a POST request for this resource
	 * @param Request request
	 * @return Response
	 */
	function post($request) {

		$response = new Response($request);
		$message = '';
		if ($this->checkMinArgs($_REQUEST)) {
			$update = new Updates();
			$insert_array = $this->addTimestamp($_REQUEST);
			if (isset ($insert_array['time'])){
				$mongo_time = $insert_array['time'];
				$insert_array['time'] = $mongo_time;
			}
			$result = $update->insertUpdate($insert_array);
			if ($result) {
				$message = $result;
				$response->code = Response::OK;
			} else {
				$message = array('msg' => 'Error in posting update');
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


	/**
	 * Check for some minimum attributes.
	 */
	function checkMinArgs($user_args) {
		//Put token
		$values = array('uid', 'update', 'listing_id');
		foreach ($values as $value) {
			if(!isset($user_args[$value])) {
				return false;
			}
		}
		return true;
	}

	function addTimestamp($user_args){
		$user_args["created_on"] = time();	
		return $user_args;
	}
}

?>
