<?php
require_once '/usr/include/php/callistoV4/Response.php';
/**
 * Display and process a HTML form via a HTTP POST request
 *
 * This example outputs a simple HTML form and gathers the POSTed data
 *
 * @namespace Tonic\Examples\HTMLForm
 * @uri /getListingForTime
 */
class getListingForTime extends Resource {

	/**
	 * Handle a GET request for this resource
	 * @param Request request
	 * @return Response
	 */
	function get($request, $uid) {
		date_default_timezone_set('UTC');
		$response = new Response($request);

		if (isset($_REQUEST['ch_id']) && isset($_REQUEST['time'])) {
			$m = new Mongo();
			$db = $m->callisto;
			$collection = $db->timings;
			$listing_collection = $db->listings;
			//need to add exceptions :|
			$resp ;
			//need to add timestamp sorting
			$ch_id = $_REQUEST['ch_id'];
			$time = $_REQUEST['time'];
			$format = "YmdHis";
			date_default_timezone_set('UTC');
			$date = DateTime::createFromFormat($format, $time);
			$cursor = $collection->find (array ("ch_id" => "".$ch_id));
			$min_diff = 86400;
			$listing_id = "";
			$start = "";
			$end = "";
			foreach ($cursor as $obj){
        			$listing_date = DateTime::createFromFormat($format, $obj["start_time"]);
				$listing_end = DateTime::createFromFormat($format, $obj["stop_time"]);
        			$interval = date_diff ($date, $listing_date, false);
        			$hour = $interval->format("%h");
        			$minutes = $interval->format("%i");
        			$seconds = $interval->format("%s");
				$sign = $interval->format("%R");
				if ($sign == "-"){
        				$total_seconds = $hour*60*60 + $minutes*60 + $seconds;
        				if ($total_seconds < $min_diff){
                				$min_diff = $total_seconds;
                				$listing_id = $obj["listing_id"];
						$start = $listing_date->getTimestamp();
						$end = $listing_end->getTimestamp();						
        				}
				}

        			//echo $interval->format('%h:%i:%s:%R')."\n";
			}
			$listing = $listing_collection->findOne(array("listing_id" => $listing_id), array("listing_name", "listing_id"));
			
			if ($listing){
				$listing_name = $listing['listing_name'];
				$lisitng_id = $listing['listing_id'];
				$resp_arr = array();
				$resp_arr['listing_name'] = $listing_name; 
				$resp_arr['listing_id'] = $listing_id;
				$resp_arr['start_time'] = $start;
				$resp_arr['stop_time'] = $end;
				$resp = $resp_arr;
			}
			else {

				$resp = '{"errmsg":"no listing"}';
			}	
		} else {
			$resp = '{"errmsg":"no matching ch_id and time"}';
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
