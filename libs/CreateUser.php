<?php
require_once dirname(__FILE__).'/logger.php';
/*
 * Class to create a user.
 */
class CreateUser {
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

	function createUser($user_args) {
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
		if (!$this->isUserAlreadyRegistered($user_args, $collection)) {
			$this->logger->log ("new user\n",Zend_Log::INFO);
			if ($uid = $this->createUID()){
				$user_args['uid'] = $uid;
			}
			else{
				$this->logger->log ("error in getting the uid",Zend_Log::INFO);
				return $operation;
			}
			$success = $collection->insert($user_args);
			if (isset($success)) {
				if ($success != false) {
					$operation = true;
					$client= new GearmanClient();
                                	$client->addServer();
                                	$newfriend_job = $client->doBackground("SendNewFriendNotificationsV4", json_encode ($user_args));
                                	$this->logger->log("new friend notification job id = $newfriend_job", Zend_Log::INFO);
                        		return $user_args['uid'];
				}
			}
		}
		else{
			$user_args['uid'] = $this->getUID($user_args, $collection);
			$this->logger->log ("old user ".$user_args['uid'], Zend_Log::INFO);
			$collection->update( array('fbid' => $user_args['fbid']), array('$set' => $user_args));

		}
                return $this->getUID($user_args, $collection);
	}

	/*
	 * Check if user already exists.
	 */
	function isUserAlreadyRegistered($user_args, $collection) {
		$fbid_key = "fbid";
		$ans = false;
		$fbid  = $user_args[$fbid_key];
		$user = $collection->findOne(
				array($fbid_key=>"".$fbid));
		if ($user) {
			$ans = true;
			$this->logger->log("Account already exists for $fbid",Zend_Log::INFO);
		}
		return $ans;
	}

	function createUID(){
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $counter_func = "function counter(name) {
                                 var ret = db.counters.findAndModify({query:{_id:name}, update:{\$inc : {seq:1}}, \"new\":true, upsert:true});
                                 return ret.seq;
                }";
                $response = $this->dbHandle->execute($counter_func, array("users"));
		return $response['retval'];
	}

	function getUID($user_args, $collection){
		$fbid_key = "fbid";
		$usr_fbid  = $user_args[$fbid_key];
		$uid = $collection->findOne(
                                array($fbid_key=>$usr_fbid), array('uid'));
		return $uid['uid'];
	}

	function getDeviceTokens ($uids){
		if($this->dbHandle == null) {
       			$this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
       			return null;
		}
		$collection = $this->dbHandle->users;

		$tokens = $collection->find(array ("uid" => array ('$in' => $uids)), array ("devicetoken", "ios_device_token"));
		$device_tokens = array();
		$device_tokens['android'] = array();
		$device_tokens['ios'] = array();
		foreach ($tokens as $token){
			if (isset ($token['devicetoken']) && $token['devicetoken'] != ''){
        			$device_tokens['android'][] = $token['devicetoken'];
			}
			if (isset ($token['ios_device_token']) && $token['ios_device_token'] != ''){
				$this->logger->log("Adding ios device tokens",Zend_Log::INFO);
                                $device_tokens['ios'][] = $token['ios_device_token'];
                        }
		}
		return $device_tokens;
	}
}



