<?php
require_once dirname(__FILE__).'/logger.php';

/*
 * Class to create a user.
 */
class CreateCategories {
	private $dbHandle;
	private $logger;
	/*
	 * Default constructor.
	 */
	function __construct() {
		 $this->dbHandle = DbHandler::getConnection();
		$this->logger = Logger::getLogger();
	}

	/*
	 * Function to create the user.
	 */

	function createCategories($user_args) {
		$operation = false;
		if (count($user_args) < 2) {
			$this->logger->log("Too few arguements passed to create a category",Zend_Log::INFO);
			return null;
		}
		if($this->dbHandle == null) {
			$this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
		}
		$collection = $this->dbHandle->categories;
		if (!$this->isCategoryAlreadyPresent($user_args, $collection)) {
			$success = $collection->insert($user_args);
			if (isset($success)) {
				if ($success != false) {
					$operation = true;
				}
			}
		}
                return $operation;
	}

	function getCategories($country){
                $operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->categories;
		$categories = $collection->find (array('c_id' => $country));
		$cat = array();

		foreach ($categories as $category){
			array_push ($cat, array ('name' => $category['ch_id'], 'icon' => $category['icon']));
		}
		if ($cat){
			return $cat;
		}
		else{
			return $operation;
		}
	}

        function getChannels($country, $cat, $time){
                $operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->channels;
		$collection_listing = $this->dbHandle->listings;
		$collection_timing = $this->dbHandle->timings;
                $channels = $collection->find (array('cat_id' => $cat, 'country' => $country));
                $chan = array();
		if ($country == "UK"){
                        foreach ($channels as $channel){ 
				if (isset ($time) || $time != ""){
					$listing_details = $this->getListingForTime($channel['ch_id'], $time);
                               		array_push ($chan, array ('ch_id' => $channel['ch_id'], 'ch_name' => $channel['ch_name'], 'icon' => "http://tvnodi.com/tvdev/actual_channel_logos/".$channel['icon'].".gif", "listing" => $listing_details));
				}
				else{
					array_push ($chan, array ('ch_id' => $channel['ch_id'], 'ch_name' => $channel['ch_name'], 'icon' => "http://tvnodi.com/tvdev/actual_channel_logos/".$channel['icon'].".gif"));
				}
			
			}
		}
		else if ($country == "UK_sky"){
                        foreach ($channels as $channel){
                                if (isset ($time) || $time != ""){
                                        $listing_details = $this->getListingForTime($channel['ch_id'], $time);
                                        array_push ($chan, array ('ch_id' => $channel['ch_id'], 'ch_name' => $channel['ch_name'], 'icon' => $channel['icon'], "listing" => $listing_details));
                                }
                                else{
                                        array_push ($chan, array ('ch_id' => $channel['ch_id'], 'ch_name' => $channel['ch_name'], 'icon' => $channel['icon']));
                                }

                        }

		}
		else{
                	foreach ($channels as $channel){
				if (isset ($time) || $time != ""){
					$listing_details = $this->getListingForTime($channel['ch_id'], $time);
					array_push ($chan, array ('ch_id' => $channel['ch_id'], 'ch_name' => $channel['ch_name'], 'icon' => "http://tvnodi.com/tvdev/actual_channel_logos/".str_replace(".","_",$channel['ch_id']).".jpg", "listing" => $listing_details));
				}
				else{	
                        		array_push ($chan, array ('ch_id' => $channel['ch_id'], 'ch_name' => $channel['ch_name'], 'icon' => "http://tvnodi.com/tvdev/actual_channel_logos/".str_replace(".","_",$channel['ch_id']).".jpg"));
				}
                	}
		}
                if ($chan){
                        return $chan;
		}
                else{
                        return $operation;
                }
        }
/*

	function getListingForTime($ch_id, $time){
                $dbHandle = new DbHandler();
                $dbHandle = $dbHandle->getConnection();
                if($dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.");
                        return null;
                }
                $collection_listing = $dbHandle->listings;
                $collection_timing = $dbHandle->timings;

			$resp = "";
                        $format = "YmdHis";
                        date_default_timezone_set('UTC');
                        $date = DateTime::createFromFormat($format, $time);
                        $cursor = $collection_timing->find (array ("ch_id" => "".$ch_id));
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
                        $listing = $collection_listing->findOne(array("listing_id" => $listing_id), array("listing_name", "listing_id"));
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

			return $resp;
	}
*/


        function getListingForTime($ch_id, $time){
		$time = new MongoDate ($time);
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
		$search = new Updates();
                $collection_listing = $this->dbHandle->listings;
                $collection_timing = $this->dbHandle->timings;
		$collection_timing->ensureIndex(array('ch_id' => 1, 'start' => 1, 'stop' => 1));
		//$listing = $collection_timing->find(array("ch_id" => $ch_id))->findOne(array ("start" => array('$lte' => $time), "stop" => array('$gt' => $time)));
		$listing = $collection_timing->findOne(array ("ch_id" => $ch_id, "start" => array('$lte' => $time), "stop" => array('$gt' => $time)));

                $resp = "";
		$list_array = array();
                        if ($listing){
                                $listing_details = $search->getListingDetails($listing['listing_id']);
                                $resp = $listing_details;

                        }
                        else {

                                $resp = '{"errmsg":"no listing"}';
                        }
		return $resp;

	}


        function getListingsForTime($ch_id, $time){
                $time = new MongoDate ($time);
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $search = new Updates();
                $collection_listing = $this->dbHandle->listings;
                $collection_timing = $this->dbHandle->timings;
                $collection_timing->ensureIndex(array('ch_id' => 1, 'start' => 1, 'stop' => 1));
                //$listing = $collection_timing->find(array("ch_id" => $ch_id))->findOne(array ("start" => array('$lte' => $time), "stop" => array('$gt' => $time)));
                $listing = $collection_timing->findOne(array ("ch_id" => $ch_id, "start" => array('$lte' => $time), "stop" => array('$gt' => $time)));

                $resp = "";
                $list_array = array();
                        if ($listing){
                                $listing_details = $search->getListingDetails($listing['listing_id']);
                                $resp = $listing_details;

                        }
                        else {

                                $resp = '{"errmsg":"no listing"}';
                        }
                return $resp;

        }


	/*
	 * Check if user already exists.
	 */
	function isCategoryAlreadyPresent($user_args, $collection) {
		$ch_id = "ch_id";
		$c_id = "c_id";
		$ans = false;
		$cat = $collection->findOne(
				array($c_id=>$user_args[$c_id], $ch_id => $user_args[$ch_id]));
		if ($cat) {
			$ans = true;
			$this->logger->log("Category already exists for $usr_mail",Zend_Log::INFO);
		}
		return $ans;
	}

}



