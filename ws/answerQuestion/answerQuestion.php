<?php

require_once '/usr/include/php/callistoV4/UserAnswers.php';

/**
 * Insert Comments. 
 *
 * @uri /AnswerQuestion
 */
class AnswerQuestionResource extends Resource {

	/**
	 * Handle a POST request for this resource
	 * @param Request request
	 * @return Response
	 */
	function post($request) {

		$response = new Response($request);
		$message = '';
		if ($this->ValidateArgs($_POST)){
			$answer = new UserAnswers();
			$user_args = $this->addTimestamp($_POST);
			$result = $answer->setAnswer($user_args);
			if ($result) {
				$message = array('msg' => 'Question successfully answered');
				$response->code = Response::OK;
			} else {
				$message = array('msg' => 'Error in answering"');
				$response->code = Response::INTERNALSERVERERROR;
			}
		}
		else{
			$message = array('msg' => 'invalid arguments');
			$response->code = Response::INTERNALSERVERERROR;
		}
		$response->addHeader('Content-type', 'application/json');
		$response->body = json_encode($message);
		return $response;
	}

	function ValidateArgs($user_args){
                if (!isset ($user_args['question_id'])){
                        return false;
                }
                if (!isset ($user_args['answer'])){
                        return false;
                }
                if (!isset ($user_args['show_id'])){
                        return false;
                }
                if (!isset ($user_args['start_time'])){
                        return false;
                }
                if (!isset ($user_args['uid'])){
                        return false;
                }
                return true;
        }

	function addTimestamp($user_args){
                $user_args["created_on"] = time();
                return $user_args;
        }

}

?>
