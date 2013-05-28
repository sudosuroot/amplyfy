<?php

require_once '/usr/include/php/callistoV4/CheckinComments.php';

/**
 * Checkin Likes
 *
 * @uri /CheckinLikes
 */
class CheckinLikesResource extends Resource {

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
			$type = $_POST['type'];
			$user_args = $_POST;
			if ($type == "like"){
				unset ($user_args['type']);
				$insert_array = $this->addTimestamp($user_args);
				$result = $comment->insertLike($insert_array);
				if ($result){
					$message = array('msg' => 'Checkin liked');
				}
				else{
					$message = array('msg' => 'already liked');
				}
			}
			else if ($type == "unlike"){
				unset ($user_args['type']);
                                $result = $comment->unLikeCheckin($user_args);
				if ($result){
					$message = array('msg' => 'Checkin unliked');
				}
				else{
					$message = array('msg' => 'already unliked');
				}
			}
			else{
				$message = array('msg' => 'Please enter a valid type');
                                $response->code = Response::INTERNALSERVERERROR;
			}
			if ($result) {
                                $response->code = Response::OK;
                        } else {
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
		$values = array('update_id', 'uid', 'type', 'listing_id', 'time');
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
