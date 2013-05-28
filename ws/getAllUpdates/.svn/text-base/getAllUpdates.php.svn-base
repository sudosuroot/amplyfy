<?php
require_once '/usr/include/php/callistoV4/Updates.php';
require_once '/usr/include/php/callistoV4/Response.php';

/**
 * Display and process a HTML form via a HTTP POST request
 *
 * This example outputs a simple HTML form and gathers the POSTed data
 *
 * @namespace Tonic\Examples\HTMLForm
 * @uri /GetAllUpdates
 */
class GetAllUpdates extends Resource {
    
    /**
     * Handle a GET request for this resource
     * @param Request request
     * @return Response
     */
        function get($request) {

                $response = new Response($request);
                $message = '';
                        $updates = new Updates();
			$last_ts = null;
			if (isset ($_REQUEST['ts'])){
				$ts = $_REQUEST['ts'];
				$result = $updates->getAllUpdates(35, $ts);
			}
			else{
                        	$result = $updates->getAllUpdates(35);
			}
                        if ($result) {
				$last_ts = $result[count($result) - 1]['created_on']->sec;
                                $message = $result;
                                $response->code = Response::OK;
                        } 
			else if (count ($result) == 0){
				$message = array('msg' => 'No updates');
                                $response->code = Response::OK;
			}
			else {
                                $message = array('msg' => 'Error while trying to get friends.');
                                $response->code = Response::INTERNALSERVERERROR;
                        }
                $response->addHeader('Content-type', 'application/json');
		$next = "http://amplyfy.me/V4/Callisto/GetAllUpdates?ts=".$last_ts;
		$respArr = sendRes($message, 'true', time(), $next);
		$response->body = $respArr;
                return $response;
        }



}

?>
