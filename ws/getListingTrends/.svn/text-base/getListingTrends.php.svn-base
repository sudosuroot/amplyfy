<?php
require_once '/usr/include/php/callistoV4/Response.php';
require_once '/usr/include/php/callistoV4/Friends.php';
require_once '/usr/include/php/callistoV4/DbHandler.php';
require_once '/usr/include/php/callistoV4/Updates.php';
require_once '/usr/include/php/callistoV4/SearchUser.php';
/**
 * Get Friends. 
 *
 * @uri /GetTrends
 */
class GetListingTrendsResource extends Resource {

	/**
	 * Handle a POST request for this resource
	 * @param Request request
	 * @return Response
	 */
	function get($request) {

		$response = new Response($request);
		$message = '';
		$search = new Updates();
		$status = 'true';
		if ($_REQUEST["listing_id"] && $_REQUEST['start']) {
				$listing_id = $_REQUEST["listing_id"];
				$start = new MongoDate($_REQUEST['start']);
				$fbids = $search->getFBIDsWatching($listing_id, $start);
				if (is_null ($fbids)){
					$message['people'] = array();
					$message['count'] = 0;
				}
				else{
					$message['people'] = array_values (array_unique($fbids));
					$message['count'] = count (array_unique($fbids));
				}
				$response->code = Response::OK;
		}
		else{
			$message = array('msg' => 'UID and listingid of the user for which trends are required must be provided');
                	$response->code = Response::INTERNALSERVERERROR;			
		}	
		$response->addHeader('Content-type', 'application/json');
		$response->body = sendRes($message, $status, time());
		return $response;
	}
}

?>
