<?php

require_once '/usr/include/php/callistoV4/Conversations.php';

/**
 * Insert Updates. 
 *
 * @uri /CreateConversation
 */
class CreateConversationResource extends Resource {

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

			$pFbids = array();
			$pFbidsTmp = split (",", $_REQUEST['fbids']);
			foreach ($pFbidsTmp as $fbid){
				if ($fbid == "" || is_null($fbid)){
					next;
				}
				$pFbids[] = trim ($fbid);			
			}
			$insert_array['fbids'] = $pFbids;
			$thread_id = $conversation->createConversation($insert_array);
			if ($thread_id) {
				$message = array('thread_id' => $thread_id, 'msg' => 'Thread created successfully');
				$response->code = Response::OK;
			} else {
				$message = array('msg' => 'Error in creating conversation');
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
		$values = array('uid', 'message', 'listing_id', 'fbids', 'country');
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
