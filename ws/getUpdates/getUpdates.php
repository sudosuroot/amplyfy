<?php
require_once '/usr/include/php/callistoV4/Response.php';
#require_once '/usr/include/php/callisto/Updates.php';

/**
 * Display and process a HTML form via a HTTP POST request
 *
 * This example outputs a simple HTML form and gathers the POSTed data
 *
 * @namespace Tonic\Examples\HTMLForm
 * @uri /getUpdates
 */
class myUpdates extends Resource {

	/**
	 * Handle a GET request for this resource
	 * @param Request request
	 * @return Response
	 */
	function get($request, $uid) {
		$status = 'true';
		$response = new Response($request);
		$resp = array();
		if (isset($_REQUEST['uid'])) {
			$update = new Updates();
			if (isset($_REQUEST['me'])){
				$updates = $update->getUserProfile($_REQUEST['uid'], "IN_airtel", false, $_REQUEST['me']);
			}
			else{
				$updates = $update->getUserProfile($_REQUEST['uid'], "IN_airtel");
			}
			if(count ($updates) > 0)
			{
				$resp = $updates;
			}
			else {

				$status = 'no updates';
			}	
		} 
		else if (isset($_REQUEST['fbid'])){
			$user = new SearchUser();
			$user_details = $user->getUserDetailsFromFBID($_REQUEST['fbid']);
			$uid = $user_details['uid'];
			$update = new Updates();
			if (isset($_REQUEST['me'])){
                                $updates = $update->getUserProfile($uid, "IN_airtel", false, $_REQUEST['me']);
                        }
                        else{
                                $updates = $update->getUserProfile($uid, "IN_airtel");
                        }

                        if(count ($updates) > 0)
                        {
                                $resp = $updates;
                        }
                        else {

                                $status = 'no updates';
                        }
		}
		else {
			$status = 'uid/fbid is a must';
		}
		$etag = md5($request->uri);

		$response->code = Response::OK;
		$response->addHeader('Content-type', 'application/json');
		$response->addEtag($etag);
		$response->body = sendRes($resp, $status, time());

		return $response;

	}
}

?>
