<?php
require_once '/usr/include/php/callistoV4/Response.php';
require_once '/usr/include/php/callistoV4/Listings.php';

/**
 * Display and process a HTML form via a HTTP POST request
 *
 * This example outputs a simple HTML form and gathers the POSTed data
 *
 * @namespace Tonic\Examples\HTMLForm
 * @uri /getShowDetails
 */
class getShowDetails extends Resource {
	private $dbHandle;
	/**
	 * Handle a GET request for this resource
	 * @param Request request
	 * @return Response
	 */
	function get($request, $uid) {
		date_default_timezone_set('UTC');
		$this->dbHandle = DbHandler::getConnection();
		$response = new Response($request);
		$listing_id;
		$listings = array();
		if (isset($_REQUEST['listing_id'])) {
			$resp ;
			date_default_timezone_set('UTC');
			$listing_id = $_REQUEST['listing_id'];
			$obj = new Listings();
			if (isset($_REQUEST['upcoming'])){
				$resp = $obj->getShowUpcomingDetails($listing_id);
			}
			else{
				if($this->dbHandle == null) {
       					return null;
				}
				$listings_collection = $this->dbHandle->listings;
				$country = "IN_airtel"; //default to UK_sky.
				if (isset ($_REQUEST['country'])){
					$country = $_REQUEST['country'];
				}
				$listing = $listings_collection->findOne(array('listing_id' => intval ($listing_id)));
				$listing_stats = array();
				if (isset($_REQUEST['uid'])){
					$uid = $_REQUEST['uid'];
					$listing_stats = $obj->getListingStats($listing_id, $uid);	
				}		
				else{
					$listing_stats = $obj->getListingStats($listing_id);
				}
				$resp = array_merge ($listing, $listing_stats);
			}
				
	
		} else {
			$resp = '{"errmsg":"no matching show"}';
		}
		$etag = md5($request->uri);

		$response->code = Response::OK;
		$response->addHeader('Content-type', 'application/json');
		$response->addEtag($etag);
		$response->body = sendRes($resp, 'true', time());

		return $response;

	}
}

?>
