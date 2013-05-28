<?php
require_once '/usr/include/php/callistoV4/Response.php';
require_once "/usr/include/php/callistoV4/Recommendations.php";

/**
 * Display and process a HTML form via a HTTP POST request
 *
 * This example outputs a simple HTML form and gathers the POSTed data
 *
 * @namespace Tonic\Examples\HTMLForm
 * @uri /GetSpecialShows
 */
class GetSpecialShows extends Resource {
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
		$list_array = array();
		$resp ;
                if($this->dbHandle == null) {
                	return null;
                }
                $collection = $this->dbHandle->timings;
		if (isset($_REQUEST['time'])) {
			$time = new MongoDate ($_REQUEST['time']);
			$listings = $collection->find(array ("country" => "IN_airtel", "start" => array('$gte' => $time), "special_show" => array('$exists' => 'true')), array('listing_id', 'start', 'listing_name', 'ch_name'));
			foreach ($listings as $listing){
				$list_array[] = array ('listing_id' => $listing['listing_id'], 'start_time' => $listing['start'], 'listing_name' => $listing['listing_name'], 'ch_name' => $listing['ch_name']);
			}
			$resp = $list_array;

		}
		else if (isset($_REQUEST['uid'])){
			$user_show_likes = array();
			$listings_hash = array();
			$mod_shows = array();
			$uid = $_REQUEST['uid'];
			$listings_collection = $this->dbHandle->listings;
			$reco = new RecoEngine();
			$shows = $reco->GetRecosForUser($uid);
			$profile = new UserProfile($uid);
                        $likes = $profile->getLikedShows();
                        foreach ($likes as $l){
                        	$user_show_likes[$l] = true;
                        }
			$ldata = $listings_collection->find(array('like' => array('$exists' => true)), array('listing_id', 'like'));
                        foreach ($ldata as $like){
	                        $listings_hash[$like['listing_id']] = $like['like'];
                        }

			foreach ($shows as $show){
				if (isset ($listings_hash[$show['listing_id']])){
                                        $show['like'] = $listings_hash[$show['listing_id']];
                                }
				if (isset ($user_show_likes[$show['listing_id']])){
					$show['is_like'] = true;
				}
				$mod_shows[] = $show;
			}
			$resp = $mod_shows;
		}
		else {

				$resp = '{"errmsg":"no listing"}';
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
