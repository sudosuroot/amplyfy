<?php
require_once dirname(__FILE__)."/DbHandler.php";
require_once dirname(__FILE__).'/Listings.php';
require_once dirname(__FILE__).'/Friends.php';
require_once dirname(__FILE__).'/logger.php';
require_once dirname(__FILE__).'/Constants.php';
/*
 * Class to insert/ delete an comment. 
 */
class Comments {
	
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
		$collection = $this->dbHandle->comments;
		$user_collection = $this->dbHandle->users;
		$data = $user_collection->findOne(array('fbid' => $user_args['fbid']), array('fullname', 'name'));
		if(is_null ($data)){
			$this->logger->log ("fbid commenting does not exist..",Zend_Log::INFO);
			return null;
		}
		if (isset($data['fullname'])){
                        $user_args['fullname'] = $data['fullname'];
                }
		if (isset($data['name'])){
                        $user_args['name'] = $data['name'];
                }
		//error_log (var_export ($user_args), true);
		$success = $collection->insert($user_args);
		if (isset($success)) {
			if ($success != false) {
                                //Update the timings table with a count and the fbid of the user.
                                $timings_collection = $this->dbHandle->timings;
				$listings_collection = $this->dbHandle->listings;
				$fbid = $user_args['fbid'];
                                $listing_id = $user_args['listing_id'];
                                $time_update = $timings_collection->update (array ("listing_id" => intval ($listing_id)), array('$inc' => array("review_count" => 1)), array("multiple" => true));
				$listing_update = $listings_collection->update (array ("listing_id" => intval ($listing_id)), array('$inc' => array("review_count" => 1)), array("multiple" => true));
                                if ($time_update && $listing_update){
                                        $operation = true;
                                }

			}
		}
		return $operation;
	}


	function getPoints(){
		$return = array();
                $return['details'] = array();
                $return['points'] = 0;
                $return['details'][] = array ('type' => nReview , 'msg' => 'Congrats! You wrote a review for a show', 'points' => nReviewPoints); //add normal checkin as type as default
                $return['points'] += nReviewPoints;
		return $return;
	}

	/*
	 * Fucntion to delete the comment.
	 */
	function deleteComment($comment_id) {
		$operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->comments;
		echo "deleting id: $comment_id";
		$operation = $collection->remove(array("_id" => new MongoID($comment_id)), true);
		return $operation;
	}

	function getFriendsComments($uid, $listing_id, $timestamp=null, $provider="fb"){
                $operation = false;
		$search = new SearchUser();
		$listing = new Listings();
		$friendsDB = new Friends();
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->comments;
		$friends = $friendsDB->getFriends($uid);
                $friends_arr = array();
                $return = array();
                foreach ($friends as $friend){
                        array_push ($friends_arr, "".$friend['uid']."");
                }

                array_push ($friends_arr, "".$uid."");
		$start;
		if ($timestamp!= null or $timestamp !=0){
			$start = new MongoDate($timestamp);
		}
		else{
                	$start = $this->dates('1000days');
		}
                $end = $this->dates('now');
                $friends_comments = $collection->find(array("created_on" => array('$gt' => $start, '$lte' => $end), 'uid' => array('$in' => $friends_arr), 'listing_id' => $listing_id));
              	$resp_arr = array();
                foreach($friends_comments as $friend_comment){
			#$listing_name = $listing->getListingNameFromID($friend_comment['listing_id']);
			#$friend_comment['listing_name'] = $listing_name;
			#$friend_comment['fbid'] = $fbid;
			$resp_arr[] = array ('uid' => $friend_comment['uid'], 'fbid' => $friend_comment['fbid'], 'comment' => $friend_comment['comment'], 'provider' => $provider);
               	}
		return $resp_arr;
	}


        function getUserComments($uid){
                $operation = false;
                $search = new SearchUser();
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->comments;
                $return = array();

                $start = $this->dates('10days');
                $end = $this->dates('now');

                $friends_comments = $collection->find(array("created_on" => array('$gt' => $start, '$lte' => $end), 'uid' => $uid));
                $resp_arr = array();
                foreach($friends_comments as $friend_comment){
                        $fbid = $search->getFBIDFromUID($friend_comment['uid']);
                        #$listing_name = $listing->getListingNameFromID($friend_comment['listing_id']);
                        #$friend_comment['listing_name'] = $listing_name;
                        #$friend_comment['fbid'] = $fbid;
                        $friend_comment['user']['uid'] = $friend_comment['uid'];
                        unset($friend_comment['uid']);
                        $friend_comment['user']['fbid'] = $fbid;
                        $friend_comment['user']['provider'] = "fb";
                        array_push($resp_arr, $friend_comment);
                }
                return $resp_arr;
        }


	function getAllComments($listing_id, $timestamp = NULL, $provider="fb"){
                $operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->comments;
                if ($timestamp!= NULL){
                        $start = new MongoDate($timestamp);
                }
                else{
                        $start = $this->dates('1000days');
                }

                $end = $this->dates('now');

                $comments = $collection->find(array("created_on" => array('$gt' => $start, '$lte' => $end), "listing_id" => $listing_id));
		$comments->sort(array('_id' => 1));
                $resp_arr = array();
                foreach($comments as $comment){
			//$resp_arr[] = array ('uid' => $comment['uid'], 'fbid' => $comment['fbid'], 'comment' => $comment['comment'], 'provider' => $provider);
			$resp_arr[] = $comment;
                }
                return $resp_arr;
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
