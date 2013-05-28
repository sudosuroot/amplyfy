<?php

require_once '/usr/include/php/callistoV4/CreateUser.php';
require_once '/usr/include/php/callistoV4/DbHandler.php';
require_once '/usr/include/php/callistoV4/Response.php';

/**
 * Create User. 
 *
 * @uri /CreateUser
 */
class CreateUserResource extends Resource {

	/**
	 * Handle a POST request for this resource
	 * @param Request request
	 * @return Response
	 */
	function get($request) {
		$status = 0;
		$response = new Response($request);
		$message = '';
		if ($this->checkMinArgs($_REQUEST)) {
			$_REQUEST['login'] = new MongoDate(time());
			$user = new CreateUser();
			$result = $user->createUser($_REQUEST);
			if ($result == null){
				$message = array('msg' => 'Error in creating user');
				$response->code = Response::INTERNALSERVERERROR;
			}
			else{
				$message = array('msg' => 'User successfully created/exists', 'uid' => $result);
				$response->code = Response::OK;
				$status = 1;
			}
		} else {
			$message = array('msg' => 'Essential attributes missing.');
			$response->code = Response::INTERNALSERVERERROR;

		}
		$response->addHeader('Content-type', 'application/json');
                $response->body = sendRes($message, $status, time());
                return $response;
	}


	/**
	 * Check for some minimum attributes.
	 */
	function checkMinArgs($user_args) {
		//Put token
		$values = array('email', 'name', 'fbid', 'token', 'fullname');
		foreach ($values as $value) {
			if(!isset($user_args[$value])) {
				return false;
			}
		}
		return true;
	}
}

?>
