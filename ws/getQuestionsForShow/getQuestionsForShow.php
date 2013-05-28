<?php
require_once '/usr/include/php/callistoV4/QuestionsModel.php';
require_once '/usr/include/php/callistoV4/Response.php';

/**
 * Display and process a HTML form via a HTTP POST request
 *
 * This example outputs a simple HTML form and gathers the POSTed data
 *
 * @namespace Tonic\Examples\HTMLForm
 * @uri /GetQuestions
 */
class GetQuestions extends Resource {
    
    /**
     * Handle a GET request for this resource
     * @param Request request
     * @return Response
     */
        function get($request) {

                $response = new Response($request);
                $message = '';
                $questions = new QuestionsModel();
		if (isset ($_REQUEST['question_id'])){
			$qid = $_REQUEST['question_id'];
			if (isset ($_REQUEST['uid']) && isset ($_REQUEST['show_id']) && isset ($_REQUEST['start_time'])){
				$details = array('uid' => $_REQUEST['uid'], 'show_id' => $_REQUEST['show_id'], 'start_time' => $_REQUEST['start_time']);
				$result = $questions->getQuestionById($qid, $details);
			}
			else{
                        	$result = $questions->getQuestionById($qid);
			}
                        $message = $result;
                        $response->code = Response::OK;
		}
		else if (isset ($_REQUEST['show_id']) && isset ($_REQUEST['start'])){
			$result = $questions->getQuestionsForShow($_REQUEST['show_id'], $_REQUEST['start']);
			$message = $result;
                        $response->code = Response::OK;
		}
		else {
                        $message = array('msg' => 'Pass a show id, start time or a question id');
                        $response->code = Response::OK;
                }
                $response->addHeader('Content-type', 'application/json');
		$response->body = sendRes($message, 'true', time());
                return $response;
        }

}

?>
