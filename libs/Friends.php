<?php
require_once dirname(__FILE__)."/CURL.php";
require_once dirname(__FILE__)."/SearchUser.php";
require_once dirname(__FILE__)."/Updates.php";
require_once dirname(__FILE__).'/logger.php';

/*
 * Class to Fetch followers for a user. 
 */
class Friends {
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
	 * @param - uids of the user and the friend
	 * Adds friend for the user and also marks the notification read.
	 */
	function addFriend($user_id, $friend_id, $notif_id) {
		$operation = false;
		$friends = array();
		if (!isset($user_id) || !isset($friend_id)) {
			$this->logger->log("User id or Follow id missing!",Zend_Log::INFO);
			return null;
		}
		if($this->dbHandle == null) {
			$this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
			return null;
		}
		$collection = $this->dbHandle->users;

		$amplyfy_friends = $collection->findOne(array('uid' => intval ($user_id)), array('amplyfy_friend'));

		if (isset ($amplyfy_friends['amplyfy_friend'])){
			if (in_array($friend_id, $amplyfy_friends['amplyfy_friend'])){
				if ($this->markNotificationAsRead($notif_id)){
                                        return true;
                                }
                                else{
                                        $this->logger->log ("notif could not be marked as read",Zend_Log::INFO);
					return false;
                                }
			}
		}

		$user_update = $collection->update (array ("uid" => intval ($user_id)), array('$push' => array("amplyfy_friend" => intval ($friend_id))));
		$friend_update = $collection->update (array ("uid" => intval ($friend_id)), array('$push' => array("amplyfy_friend" => intval ($user_id))));

		if ($user_update && $friend_update){
			if (!is_null($notif_id)){
				if ($this->markNotificationAsRead($notif_id)){
					$client= new GearmanClient();
                			$client->addServer();
					$user_args = array('user_uid' => $user_id, 'friend_uid' => $friend_id);
					$faccept_job = $client->doBackground("SendFriendAcceptNotificationsV4", json_encode ($user_args));
                                	$this->logger->log("friend accept job id = $faccept_job", Zend_Log::INFO);
					return true;
				}
				else{
					$this->logger->log ("notif could not be marked as read",Zend_Log::INFO);
				}
			}
			return true;
		}
		return $operation;
	}

	function markNotificationAsRead($notification_id){
		$operation = false;
		if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
		$collection = $this->dbHandle->notifications;
		$read = $collection->update(array("_id" => new MongoId($notification_id)), array('$set' => array('is_read' => 1)));
		if ($read){
			$this->logger->log ("$notification_id marked as read\n",Zend_Log::INFO);
			return true;
		}
		return $operation;
	}


	/**
	 * Function to get facebook and amplyfy friends. 
	 * Gives the uids of all the friends.
	 * Currently takes in FB and amplyfy friends.
	 * @param user_id
	 */

	function getFBAMFriends($uid){
		$friend_list_fb = array();
		$friend_list = array();
                //call FB get app friends and populate the fbids
                if ($uid == '' || is_null ($uid)){
                        $this->logger->log ("uid should be present",Zend_Log::INFO);
                        return null;
                }

                $search = new SearchUser();
                $access_token = $search->getAccessTokenFromUID($uid);
                if($access_token == "")
                {
                        $this->logger->log("access_token not stored",Zend_Log::INFO);
                }
		else{
                	$url = "https://api.facebook.com/method/friends.getAppUsers?access_token=".$access_token."&format=json";
                	$curl = new CURL();
                	$curl->retry = 1;
                	$opts = array( CURLOPT_RETURNTRANSFER => true);
                	$curl->addSession($url, $opts);
                	$result = $curl->exec();
                	$curl->clear();
                	$friend_list_fb = json_decode($result,1);
                	if(array_key_exists('error_code', $friend_list)){
                        	$this->logger->log("access token expired",Zend_Log::INFO);
				$token_error = true;
                	}
		}
		if (count ($friend_list_fb) != 0){
			$friend_list_fb_str = array();
			foreach ($friend_list_fb as $fb){
				$friend_list_fb_str[] = "".$fb;
			}
			$collection = $this->dbHandle->users;
			$users = $collection->find(array ("fbid" => array ('$in' => $friend_list_fb_str)), array ("uid"));
			foreach ($users as $user){
				$friend_list[] = $user['uid'];
			}
		}
		
                $ret = $search->getAmplyfyFriends($uid);
                if (isset ($ret['amplyfy_friend'])){
                        $amplyfy_friends = $ret['amplyfy_friend'];
                }
                $amplyfy_friends[] = $uid;//add my uid too
                if (!is_null($amplyfy_friends)){
                        foreach ($amplyfy_friends as $amplyfy_friend){
                                if (!in_array ($amplyfy_friend, $friend_list)){
                                        array_push($friend_list, $amplyfy_friend);
                                }
                        }
                }
		return $friend_list;	
	}

	/**
	 *  Function to get all the friends of a user
	 *  @params user_id
	 *  @returns Array of friends. 
	 */
	function getFriends($uid, $update_flag=true, $leaderboard=true){
		$operation = false;
		$resp_arr = array();
		$search = new SearchUser();
		$friend_list = $this->getFBAMFriends($uid);
		$friend_count = count ($friend_list);
		$count = 0;
		$i = 0;
		$user_details = array();
		foreach ($friend_list as $uid){
			$details = $search->getUserDetailsFromUID($uid);
			$fullname = (isset($details['fullname'])?$details['fullname']:null);
			$points = (isset($details['points'])?$details['points']:0);
			if ($details == NULL){
				$count++;
				continue;
			}
			$fbid = $details['fbid'];
			if ($update_flag){
				$last_update = (isset($details['last_update'])?$details['last_update']:null);
				if ($last_update){
					$resp_arr[] = array('uid' => $uid, 'fbid' => $fbid, 'name' => $details['name'], 'fullname' => $fullname, 'pic_url' => "https://graph.facebook.com/$fbid/picture?type=normal", 'update' => $last_update, 'points' => $points, 'timestamp' => $last_update['created_on']->sec);
				}
				else{
					$resp_arr[] = array('uid' => $uid, 'fbid' => $fbid, 'name' => $details['name'], 'fullname' => $fullname, 'pic_url' => "https://graph.facebook.com/$fbid/picture?type=normal", 'update' => $last_update, 'points' => $points, 'timestamp' => 0);
				}
			}
			else{
				$resp_arr[$i++] = array('uid' => $uid, 'fbid' => $fbid, 'name' => $details['name'], 'fullname' => $fullname, 'pic_url' => "https://graph.facebook.com/$fbid/picture?type=normal", 'points' => $points);
			}
			$count++;
		}	
		if ($leaderboard){
			foreach ($resp_arr as $key => $value){
				$diff = (time() - $resp_arr[$key]['timestamp'])/86400;
				if ($diff > 7){
					$points_arr[$key] = 0;
					$resp_arr[$key]['points'] = 0;
				}
				else{
					$points_arr[$key] = $value['points'];
				}
			}
			array_multisort($points_arr, SORT_DESC, $resp_arr);
		}
	
		return $resp_arr;

	}	

	function getLeaderBoardSnapshot($uid){
		$ret_arr = array();
		$leaderboard = $this->getFriends($uid, true, true); 
		for ($pos = 0 ; $pos < count ($leaderboard); $pos++){
			unset ($leaderboard[$pos]['update']);
			$holder = $leaderboard[$pos];
			$holder['position'] = $pos + 1;
			$leaderboard[$pos] = $holder;
		}
		$lcount = count ($leaderboard);
		if ($lcount < 4){
			return $leaderboard;
		}
		$user_index = 0;
		foreach ($leaderboard as $key => $value){
			if ($value['uid'] == "".$uid){
				$user_index = $key;
				break;
			}
		}
		if ($user_index == 0){
			$ret_arr[0] = $leaderboard[0];
			$ret_arr[1] = $leaderboard[1];
			$ret_arr[2] = $leaderboard[2];
		}
		else if ($user_index == $lcount-1){	
			$ret_arr[0] = $leaderboard[$lcount-3];
                        $ret_arr[1] = $leaderboard[$lcount-2];
                        $ret_arr[2] = $leaderboard[$lcount-1];
		}
		else{
			$ret_arr[0] = $leaderboard[$user_index-1];
                        $ret_arr[1] = $leaderboard[$user_index];
                        $ret_arr[2] = $leaderboard[$user_index+1];
		}
		return $ret_arr;
	}




	/*
	 * Check if user already exists.
	 */
	function isFriend($user_id, $collection) {
		$follows = $collection->findOne(
				array('uid' => $user_id));
		return $follows;
	}


}



