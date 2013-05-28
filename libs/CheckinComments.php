<?php
require_once dirname(__FILE__)."/DbHandler.php";
require_once dirname(__FILE__).'/Listings.php';
require_once dirname(__FILE__).'/Friends.php';
require_once dirname(__FILE__).'/logger.php';
/*
 * Class to insert/ delete an comment. 
 */
class CheckinComments {
	
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
	function insertComment($user_args) {
		$operation = false;
		if (count($user_args) < 3) {
			$this->logger->log("Too few arguements passed to insert comment!",Zend_Log::INFO);
			return null;
		}
		if($this->dbHandle == null) {
			$this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
			return null;
		}
		$collection = $this->dbHandle->updates;
		$user_collection = $this->dbHandle->users;
		$data = $user_collection->findOne(array('uid' => intval($user_args['uid'])), array('fbid', 'name'));
		//getting name and inserting as fullname. 
		$comment =  array('fbid' => $data['fbid'], 'uid' => $user_args['uid'], 'fullname' => $data['name'], 'comment' => $user_args['comment'], 'created_on' => $user_args['created_on']);
		$status = $collection->update(array('uid' => "".$user_args['update_id'], 'listing_id' => $user_args['listing_id'], 'time' => "".$user_args['time']), array('$push' => array('comments' => $comment)));
		//error_log (var_export ($user_args), true);
		if ($status) {
                   	$operation = true;
			$client= new GearmanClient();
                	$client->addServer();
			$comments_checkin_job = $client->doBackground("SendCheckinCommentsNotificationsV4", json_encode ($user_args));
                        $this->logger->log("checkin comments notification job id = $comments_checkin_job", Zend_Log::INFO);	
		}
		return $operation;
	}

	function insertLike($user_args){
		$operation = false;
                if (count($user_args) < 3) {
                        $this->logger->log("Too few arguements passed to insert like!",Zend_Log::INFO);
                        return null;
                }
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->updates;
                $user_collection = $this->dbHandle->users;
                $user_data = $user_collection->findOne(array('uid' => intval($user_args['uid'])), array('fbid', 'name'));
		
                $likes_data = $this->getCheckinLikes($user_args['update_id'], $user_args['listing_id'], $user_args['time']);
		$likes = array();
		foreach ($likes_data as $data){
			$likes[] = intval ($data['uid']);
		}
		if (in_array(intval ($user_args['uid']), $likes)){
			return false;
		}
		$like_arr =  array('fbid' => $user_data['fbid'], 'uid' => $user_args['uid'], 'name' => $user_data['name'], 'created_on' => $user_args['created_on']);
                $status = $collection->update(array('uid' => "".$user_args['update_id'], 'listing_id' => $user_args['listing_id'], 'time' => "".$user_args['time']), array('$push' => array('likes' => $like_arr)));
		if ($status) {
                        $operation = true;
                        $client= new GearmanClient();
                        $client->addServer();
                        $likes_checkin_job = $client->doBackground("SendCheckinLikesNotificationsV4", json_encode ($user_args));
                        $this->logger->log("checkin comments notification job id = $likes_checkin_job", Zend_Log::INFO);
                }
		return $status;
	}

	function unLikeCheckin($user_args){
                $operation = false;
                if (count($user_args) < 3) {
                        $this->logger->log("Too few arguements passed to unlike!",Zend_Log::INFO);
                        return null;
                }
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->updates;
                $status = $collection->update(array('uid' => "".$user_args['update_id'], 'listing_id' => $user_args['listing_id'], 'time' => "".$user_args['time']), array('$pull' => array('likes' => array('uid' => $user_args['uid']))));
                return $status;
	}

	function getCheckinComments($update_id, $listing_id, $timestamp){
                $operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->updates;
                $comments = $collection->findOne(array ("uid" => "".$update_id, "time" => "".$timestamp, "listing_id" => "".$listing_id), array('comments'));
		$resp_arr = array();
		if (isset ($comments['comments'])){
			$resp_arr = $comments['comments'];
		}
		return $resp_arr;
	}


        function getCheckinLikes($update_id, $listing_id, $timestamp){
                $operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->updates;
                $comments = $collection->findOne(array ("uid" => "".$update_id, "time" => "".$timestamp, "listing_id" => "".$listing_id), array('likes'));
                $resp_arr = array();
                if (isset ($comments['likes'])){
                        $resp_arr = $comments['likes'];
                }
                return $resp_arr;
        }

	function getCheckinDetails($update_id, $listing_id, $timestamp){
		$operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->updates;
                $details = $collection->findOne(array ("uid" => "".$update_id, "time" => "".$timestamp, "listing_id" => "".$listing_id), array('likes', 'comments'));
		return $details;
	}


       function dates($name) {
             $_dates = array(
                'now' => 0,
                '-1min' => -60,
                '-3min' => -180,
                '-5min' => -300,
                '15min' => 900,
                '1day' => -84600,
                '10days' => -846000,
		'1000days' => -84600000
             );

             return new MongoDate(time() + $_dates[$name]);
       }
		
}

?>
