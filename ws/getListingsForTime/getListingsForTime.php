<?php
require_once '/usr/include/php/callistoV4/Response.php';
require_once '/usr/include/php/callistoV4/Trends.php';
require_once '/usr/include/php/callistoV4/UserProfile.php';

/**
 * Display and process a HTML form via a HTTP POST request
 *
 * This example outputs a simple HTML form and gathers the POSTed data
 *
 * @namespace Tonic\Examples\HTMLForm
 * @uri /getListingsForTime
 */
class getListingsForTime extends Resource {
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
		$ch_id;
		$listing_id;
		$genre;
		$uid;
		$ch_filter = false;
		$listing_filter = false;
		$genre_filter = false;
		$channel_guide_page = false;
		$genre_guide_page = false;
		if (isset($_REQUEST['time'])) {
			$resp ;
			$listings;
			date_default_timezone_set('UTC');
			if($this->dbHandle == null) {
       				return null;
			}
			$collection = $this->dbHandle->timings;
			$listings_collection = $this->dbHandle->listings;
			$time = new MongoDate ($_REQUEST['time']);
			$listingids = array();
			$country = "UK_sky"; //default to UK_sky.
			
			if (isset ($_REQUEST['country'])){
				$country = $_REQUEST['country'];
			}
				
			if (isset ($_REQUEST['ch_id'])){
				$ch_id = $_REQUEST['ch_id'];
				$ch_filter = true;
			}	

                        if (isset ($_REQUEST['listing_id'])){
                                $listing_id = $_REQUEST['listing_id'];
                                $listing_filter = true;
                        }
			
			if (isset ($_REQUEST['genre'])){
                                $genre= $_REQUEST['genre'];
                                $genre_filter = true;
                        }
	
			if (isset ($_REQUEST['duration'])){
				$start_time = $time;
				$end_time = new MongoDate($_REQUEST['time'] + $_REQUEST['duration']*60*60);
				if ($ch_filter){
					$listings = $collection->find(array ('$or' => array( array("start" => array('$lt' => $time), "stop" => array('$gte' => $time), "country" => $country, "ch_id" => "".$ch_id), array("start" => array('$gt' => $start_time), "stop" => array('$lte' => $end_time), "country" => $country, "ch_id" => "".$ch_id))));
				}
				else if ($listing_filter){
					$listings = $collection->find(array ("country" => $country, "listing_id" => intval ($listing_id), "start" => array('$gt' => $start_time), "stop" => array('$lte' => $end_time)));
				}
				else if ($genre_filter){
					$listings = $collection->find(array ("country" => $country, "genre" => "".$genre, "start" => array('$gt' => $start_time), "stop" => array('$lte' => $end_time)));
				}
				else{
					$listings = $collection->find(array ("country" => $country, "start" => array('$gt' => $start_time), "stop" => array('$lte' => $end_time)));
				}
			}
			else{
				if ($ch_filter){
					$listings = $collection->find(array ("country" => $country, "ch_id" => "".$ch_id, "start" => array('$lt' => $time), "stop" => array('$gte' => $time)));
				}
				else if ($listing_filter){
					$listings = $collection->find(array ("country" => $country, "listing_id" => intval ($listing_id), "start" => $time));
				}
				else if ($genre_filter){
					$genre_guide_page = true;
					$listings = $collection->find(array ("country" => $country, "genre" => "".$genre, "start" => array('$lt' => $time), "stop" => array('$gte' => $time)));
				}
				else{
					$channel_guide_page = true;
					$listings = $collection->find(array ("country" => $country, "start" => array('$lt' => $time), "stop" => array('$gte' => $time)));
				}
			}
			$list_array = array();
			$channel_ids = array();
			if ($listings){
				$listings->sort(array('start' => 1));
				$listings_hash = array();
				$user_show_likes = array();
				$ldata = $listings_collection->find(array('like' => array('$exists' => true)), array('listing_id', 'like'));
				foreach ($ldata as $like){
					$listings_hash[$like['listing_id']] = $like['like'];
				}
				if (isset ($_REQUEST['uid'])){
					$uid = $_REQUEST['uid'];
					$profile = new UserProfile($uid);
					$likes = $profile->getLikedShows();
					foreach ($likes as $l){
                                        	$user_show_likes[$l] = true;
                                	}
				}	
				if ($channel_guide_page || $genre_guide_page){
					$return_arr_key = 0;
                        		$special_listings = array();
					$regular_listings = array();
					foreach ($listings as $listing){
						if (isset ($listings_hash[$listing['listing_id']])){
							$listing['like'] = $listings_hash[$listing['listing_id']];
						}
						if (isset ($_REQUEST['uid']) && isset ($user_show_likes[intval($listing['listing_id'])])){
							$listing['is_like'] = true;
						}
						if (isset ($listing['meta']['desc'])){
							unset ($listing['meta']['desc']);
						}
						$id = $listing['ch_id'];
						if (in_array($id, $channel_ids)){
							continue;
						}
						else{
							$regular_listings[] = $listing;
						}
						$channel_ids[] = $id;
					}
					//$list_array = array_merge($special_listings, $regular_listings);
					$list_array = $regular_listings;
				}
				else{
						$listing_ids = array();
						$pos = 0;
						foreach ($listings as $listing){
							if (isset ($listings_hash[$listing['listing_id']])){
                                                 	       $listing['like'] = $listings_hash[$listing['listing_id']];
                                                	}
							if (isset ($user_show_likes[intval($listing['listing_id'])])){
                                                 	       $listing['is_like'] = true;
                                                	}
							$key =  $listing['listing_id'];
							if (isset ($listing_ids[$key])){
								$time = $listing_ids[$key]['time'];
								$diff = $listing['start']->sec - $time;
								if ($diff < 1200){
									$list_array[$listing_ids[$key]['pos']] = $listing;
									continue;
								}
							}
							$list_array[] = $listing;
							$listing_ids[$key]['time'] = $listing['start']->sec;
							$listing_ids[$key]['pos'] = $pos;
							$pos++;
						}
				}
				
				$resp = $list_array;

			}
			else {

				$resp = '{"errmsg":"no listing"}';
			}	
		} else {
			$resp = '{"errmsg":"no matching time"}';
		}
		$etag = md5($request->uri);

		$response->code = Response::OK;
		$response->addHeader('Content-type', 'application/json');
		$response->addEtag($etag);
		$response->body = sendRes($resp, 'true', time());
		error_log ("data sent now");
		return $response;

	}
}

?>
