<?php

require_once '/usr/include/php/callistoV4/Conversations.php';

/**
 * Insert Updates. 
 *
 * @uri /AddFriendsToConversation
 */
class AddFriendsToConversation extends Resource {

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
			$insert_array = $this->addFbids($_REQUEST);
			$status = $conversation->addFriendsToConversation($insert_array);
			if ($status) {
				$message = array('msg' => 'friends added successfully successfully');
				$response->code = Response::OK;
			} else {
				$message = array('msg' => 'Error in adding friends to the conversation');
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
		$values = array('fbids', 'thread_id');
		foreach ($values as $value) {
			if(!isset($user_args[$value])) {
				return false;
			}
		}
		return true;
	}

	function addFbids($user_args){
		$fbid_string = $user_args['fbids'];
		$fbids = array();
		$tmp = split (",", $fbid_string);
		foreach ($tmp as $fbid){
			$fbids[] = trim ($fbid);
		}
		$user_args['fbids'] = $fbids;
		return $user_args;
	}
}

?>
