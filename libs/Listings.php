<?php
require_once dirname(__FILE__).'/logger.php';
require_once dirname(__FILE__).'/SearchUser.php';
require_once dirname(__FILE__).'/Updates.php';

/*
 * Class to insert a listing.
 */
class Listings {

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
	 * Function to create the listing.
	 */
	function createListing($user_args) {
		$operation = false;
		if (count($user_args) < 2) {
			$this->logger->log("Too few arguements passed to create a lsiting!",Zend_Log::INFO);
			return null;
		}
		if($this->dbHandle == null) {
			$this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
			return null;
		}
		$collection = $this->dbHandle->listings;
		//$id = $this->isListingAlreadyRegistered($user_args, $collection);
		//if (!$id) {
			$counter_func = "function counter(name) {
                                        var ret = db.counters.findAndModify({query:{_id:name}, update:{\$inc : {seq:1}}, \"new\":true, upsert:true});
                                        return ret.seq;
                        }";
                        $response = $this->dbHandle->execute($counter_func, array("listings"));
			$user_args["listing_id"] = $response['retval'];
                        $success = $collection->insert($user_args);
                        if (isset($success)) {
                                if ($success != false) {
                                        return $user_args['listing_id'];
                                }
                        }
			else{
				return $operation;
			}
		//}
		//else{
		//	return $id;
		//}
	}



        function createListingTiming($user_args) {
                $operation = false;
                if (count($user_args) < 2) {
                        $this->logger->log("Too few arguements passed to create a lsiting!",Zend_Log::INFO);
                        return null;
                }
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->timings;
                        //$success = $collection->insert($user_args);
			$success = $collection->insert( $user_args );
                        if (isset($success)) {
                                if ($success != false) {
                                        $operation = true;
                                }
                        }
                return $operation;
        }


        function batchCreateListings($user_args) {
                $operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->timings;
                        //$success = $collection->insert($user_args);
                        $success = $collection->batchInsert( $user_args );
                        if (isset($success)) {
                                if ($success != false) {
                                        $operation = count($user_args);
                                }
                        }
                return $operation;
        }


	/*
	 * Check if listing already exists.
	 */
	function isListingAlreadyRegistered($user_args, $collection) {
		$ans = false;
		$listing_name  = $user_args['listing_name'];
		$ch_id = $user_args['ch_id'];
		$listing = $collection->findOne(
				array("listing_name"=>$listing_name, "ch_id" => $ch_id));
		if ($listing) {
			$ans = $listing['listing_id'];
			return $ans;
			$this->logger->log("Listing already exists for $listing_name, $ch_id\n",Zend_Log::INFO);
		}
		return $ans;
	}

	function getListingStats($listing_id, $uid = null, $country = "IN_airtel"){
		$operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $timings_collection = $this->dbHandle->timings;
                $total_people = array();
		$show_times = array();
		$checkin_count = 0;
		$listings = $timings_collection->find(array ("country" => $country, "listing_id" => intval ($listing_id)));
		foreach ($listings as $show){ 
                        $show_times[] = array('start' => $show['start'], 'stop' => $show['stop']);
                        if (isset ($show['fbids_watching'])){
                                $people = $show['fbids_watching'];
                                $checkin_count += count($people);
                                foreach ($people as $person){
                                        @$total_people[$person]++;
                                }
                        }
                }
		if (count ($total_people) != 0){
			asort ($total_people);
			$i = 0;
			$search = new SearchUser();
			$checkins = new Updates();
			foreach ($total_people as $key => $value){
				if ($i == 0){
					$details = $search->getUserDetailsFromFBID($key);
					$name = "";
					if (isset ($details['fullname'])){
						$name = $details['fullname'];
					}
					else{
						$name = $details['name'];
					}
					$listing['mayor'] = array('fbid' => $key, 'count' => $value, 'name' => $name);
				}
				else{
					break;
				}
			}
			$last_checkins = $checkins->getShowCheckins($listing_id);
			$listing['last_checkins'] = $last_checkins;
		}
		else{
			$listing['mayor'] = array('fbid' => null, 'count' => 0);
			$listing['last_checkins'] = array();
		}
                $listing['total_people'] = count (array_keys($total_people));
                if (is_null($uid)){
                        $listing['user_checkins'] = 0;
                }
		else{
			$search_uid = new SearchUser();
			$details_uid = $search_uid->getUserDetailsFromUID($uid);
			$user_checkin_count = @$total_people[$details_uid['fbid']];
			if (is_null ($user_checkin_count)){
				$user_checkin_count = 0;
			}
			$listing['user_checkins'] = $user_checkin_count;
		}
                $listing['show_times'] = $show_times;
                $listing['checkin_count'] = $checkin_count;
		return $listing;
        }

	function getChIDFromListing($listing_id){
		$operation = false;
		$this->dbHandle = new DbHandler();
		$this->dbHandle = $this->dbHandle->getConnection();
		if($this->dbHandle == null) {
			$this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
			return null;
		}
		$collection = $this->dbHandle->listings;
		$listing = $collection->findOne(array("listing_id" => intval ($listing_id)));
		if ($listing){
			return $listing['ch_id'];
		}
		else{
			return $operation;
		}
	}
	function getListingNameFromID($listing_id){
		$operation = false;
                $this->dbHandle = new DbHandler();
                $this->dbHandle = $this->dbHandle->getConnection();
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->listings;
		$listing = $collection->findOne(array("listing_id" => intval ($listing_id)), array("listing_name"));
		if ($listing){
			return $listing['listing_name'];
		}
		else{
			return $operation;
		}
	}

	function listingSearch($name){
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->listings;
		$name_regex = "/".$name."/i";
		$regex = new MongoRegex($name_regex);
		$shows = $collection->find(array('listing_name'=>$regex), array('listing_name', 'listing_id', 'icon'));
		$resp_array = array();
		foreach ($shows as $show){
			$resp_array[] = array ('value' => $show['listing_name'], 'label' => $show['listing_name'], 'icon' => $show['icon'], 'listing_id' => $show['listing_id']);;
		}
		return $resp_array;
	}

	function getShowUpcomingDetails($lid){
		if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $shows_collection = $this->dbHandle->shows;
		$show_data = $shows_collection->findOne(array('list_ids' => intval ($lid)), array('list_ids'));
		$same_show_ids = array();
		if (isset ($show_data['list_ids'])){
			$same_show_ids = $show_data['list_ids'];
		}
		$timings_collection = $this->dbHandle->timings;
		$time = new MongoDate(time());
		$timing_data = $timings_collection->find(array('listing_id' => array('$in' => $same_show_ids), 'start' => array('$gt' => $time)))->sort(array('start' => 1));
		$return_arr = array();
		foreach ($timing_data as $time){
			if (isset ($time['meta'])){
				unset ($time['meta']);
			}
			$return_arr[] = $time;
		}
		return $return_arr;
	}
}



