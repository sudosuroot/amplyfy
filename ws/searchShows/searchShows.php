<?php
require_once '/usr/include/php/callistoV4/Response.php';
require_once '/usr/include/php/callistoV4/Listings.php';

/**
 * Display and process a HTML form via a HTTP POST request
 *
 * This example outputs a simple HTML form and gathers the POSTed data
 *
 * @namespace Tonic\Examples\HTMLForm
 * @uri /searchShows
 */
class searchShows extends Resource {
	/**
	 * Handle a GET request for this resource
	 * @param Request request
	 * @return Response
	 */
	function get($request, $uid) {
		$response = new Response($request);
		$listings = array();
		$resp;
		if (isset($_REQUEST['listing_name'])){
			$listing_name = $_REQUEST['listing_name'];
			$obj = new Listings();
			$listings = $obj->listingSearch($listing_name);	
			$resp = $listings;	
		} else {
			$resp = '{"errmsg":"listing name required}';
		}
		$etag = md5($request->uri);

		$response->code = Response::OK;
		$response->addHeader('Content-type', 'application/json');
		$response->addEtag($etag);
		//$response->body = sendRes($resp, 'true', time());
		if (isset ($_REQUEST['jsoncallback'])){
			$jsonp = $_REQUEST['jsoncallback'] . '(' . json_encode($resp) . ')';
			$response->body = $jsonp;
		}
		else{	
			$response->body = json_encode($resp);
		}
		return $response;

	}
}

?>
