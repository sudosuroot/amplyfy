<?php
require_once '/usr/include/php/callistoV4/Response.php';
#require_once '/usr/include/php/callistoV4/Updates.php';

/**
 * Display and process a HTML form via a HTTP POST request
 *
 * This example outputs a simple HTML form and gathers the POSTed data
 *
 * @namespace Tonic\Examples\HTMLForm
 * @uri /isUserCheckedIn
 */
class isUserCheckedIn extends Resource {

	/**
	 * Handle a GET request for this resource
	 * @param Request request
	 * @return Response
	 */
	function get($request) {
		$status = 'true';
		$response = new Response($request);
		$resp = array();
		if (isset($_REQUEST['fbid']) && isset($_REQUEST['start']) && isset($_REQUEST['listing_id'])) {
			$start = $_REQUEST['start'];
			$listing_id = $_REQUEST['listing_id'];
			$fbid = $_REQUEST['fbid'];
			$user = new SearchUser();
			$check = $user->isUserCheckedIn($fbid, $listing_id, $start);
			if (is_null ($check)){
				$resp['errmsg'] = 'failure';
				$resp['status'] = 'false';
			}
			if($check)
			{
				$resp['check'] = true;
			}
			else {

				$resp['check'] = false;
			}	
		} else {
			$status = 'false';
			$resp['errmsg'] = 'essential attributes missing';
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
