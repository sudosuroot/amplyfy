<?php
require_once dirname(__FILE__).'/logger.php';
/*
 * Class to Search for a user.
 */
class SearchUser {
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
	function searchUser($user_args) {
		$operation = false;
		if (count($user_args) < 1) {
			$this->logger->log("Too few arguements passed to create a user!",Zend_Log::INFO);
			return null;
		}
		if($this->dbHandle == null) {
			$this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
			return null;
		}
		$collection = $this->dbHandle->users;
		
		if (is_null ($user_args) || $user_args == ''){
			$this->logger->log ("name cant be null\n",Zend_Log::INFO);
			return $operation;
		}

		else{
			$name_regex = "/^".$user_args."/i";
			$regex = new MongoRegex($name_regex);
			$friends = $collection->find(array('name' => $regex));
			return $friends;
		}
		
	}

	function getUserDetailsFromFBID($fbid){
		$operation = false;
		if ($fbid == '' || is_null ($fbid)){
			$this->logger->log ("fbid should be present",Zend_Log::INFO);
			return $operation;
		}

                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->users;
		$details = $collection->findOne(array('fbid' => "".$fbid.""), array('uid', 'name', 'fullname', 'last_update', 'points'));
		if (is_null ($details)){
			return NULL;
		}
		return $details;
	}


        function getUserDetailsFromUID($uid){
                $operation = false;
                if ($uid == '' || is_null ($uid)){
                        $this->logger->log ("uid should be present",Zend_Log::INFO);
                        return $operation;
                }

                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->users;
                $details = $collection->findOne(array('uid' => intval ($uid)), array('fbid', 'name', 'fullname', 'last_update', 'points'));
                if (is_null ($details)){
                        return NULL;
                }
                return $details;
        }

        function getAmplyfyFriends($uid){
                $operation = false;
                if ($uid == '' || is_null ($uid)){
                        $this->logger->log ("uid should be present",Zend_Log::INFO);
                        return $operation;
                }

                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->users;
                $friends = $collection->findOne(array('uid' => intval($uid)), array('amplyfy_friend'));
                if (isset($friends['amplyfy_friend'])){
			return $friends;
                }
                else{
                        return null;
                }
        }


        function getFBIDFromUID($uid){
                $operation = false;
                if ($uid == '' || is_null ($uid)){
                        $this->logger->log ("uid should be present",Zend_Log::INFO);
                        return null;
                }

                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->users;
                $fbid = $collection->findOne(array('uid' => intval($uid)), array('fbid'));
		if (isset ($fbid['fbid'])){
                	return $fbid['fbid'];
		}
		else{
			return null;
		}
        }
	
	function getAccessTokenFromUID($uid) {
                $operation = false;
	        if ($uid == '' || is_null ($uid)){
                        $this->logger->log ("uid should be present",Zend_Log::INFO);
                        return $operation;
                }

                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->users;
                $fbid = $collection->findOne(array('uid' => intval($uid)), array('token'));
                return $fbid['token'];
 	
	}

        function isUserCheckedIn($fbid, $listing_id, $start){
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->timings;
                $listing = $collection->findOne(array('listing_id' => intval ($listing_id), 'start' => new MongoDate($start)), array('fbids_watching'));
		if (is_null ($listing) || !isset($listing['fbids_watching'])){
			return false;
		}
		else {
                	if (in_array ($fbid, $listing['fbids_watching'])){
                        	return true;
                	}
                	else{
                        	return false;
                	}
		}
        }


}



