<?php

require_once '/usr/include/php/callistoV4/Notifications.php';
require_once '/usr/include/php/callistoV4/Friends.php';
require_once '/usr/include/php/callistoV4/Response.php';
require_once '/usr/include/php/callistoV4/SearchUser.php';
/**
 * Insert Updates. 
 *
 * @uri /PokeFriends
 */
class PokeFriendsResource extends Resource {

	/**
	 * Handle a POST request for this resource
	 * API for recommending shows as well as adding new friends.
	 * @param Request request
	 * @return Response
	 */
	function post($request) {

		$response = new Response($request);
		$message = '';
		if ($this->checkMinArgs($_POST)) {
			$notification = new Notifications();
			$users = new CreateUser();
			$search = new SearchUser();
			$poke_time = $this->addTimestamp($_POST);
			$uid = $_POST['uid'];
			if (isset ($_POST['friends'])){
				$friends = $_POST['friends'];
			}
			if (isset ($_POST['friend_uids'])){
				$friends = $_POST['friend_uids'];
			}
			$type = 1; // poke
			if (isset ($_POST['type'])){
                                $type = $_POST['type'];
                        }
			$poke_friends = array();
			$arr_poke_friends = array();
			$friends_uids = array();
			//If poke all friedns, get the list from Freinds db.
			if ($friends == "all"){
				$friendResource = new Friends();
				$arr_poke_friends = $friendResource->getFriends($uid, false);				
				foreach ($arr_poke_friends as $friend){
                                	array_push ($poke_friends, "".$friend['uid']."");
                                	array_push ($friends_uids, intval ($friend['uid']));
                        	}
			}
			//parse from the comma separated list of fbids. Get the uid from the user table.
			else {
				//for type 3 fbids are send instead of uid's. Handle this special type case
				if ($type == "3" && isset ($_POST['friends'])){
					$fbids = split (",", $friends);
					foreach ($fbids as $to_fbid){         
						$details = $search->getUserDetailsFromFBID($to_fbid);
						if (isset ($details['uid'])){ 
                                        		$to_uid = $details['uid'];
							array_push ($poke_friends, "".$to_uid);
                                        		array_push ($friends_uids, intval ($to_uid));
						}
                                	}
				}
				else{
					$uids = split (",", $friends);
					foreach ($uids as $to_uid){			
						array_push ($poke_friends, $to_uid);
						array_push ($friends_uids, intval ($to_uid));
					}
				}
			}
			$device_tokens = $users->getDeviceTokens($friends_uids);
			$message =  $_POST['message'];
			$args = array();
			$country = "UK_sky";
			if (isset ($_POST['country'])){
				$country = $_POST['country'];
			}
			if (isset ($_POST['ch_id']) && isset ($_POST['listing_id'])){
				$details = $search->getUserDetailsFromUID(intval ($uid));
				$name = "Unknown";
				if (isset ($details['name'])){
					$name = $details['name'];	
				}
				$message = "$name recommends watching";
				if (! isset ($_POST['start'])){
					$message = array('msg' => 'start as a parameter should be give with the show start time');
                                	$response->code = Response::INTERNALSERVERERROR;
					$response->addHeader('Content-type', 'application/json');
                			$response->body = sendRes($message, 'false', time());
					return $response;
				}
				$args = array ('from_id' => $uid, 'message' => $message, 'type' => $type, 'is_read' => 0, 'ch_id' => $_POST['ch_id'], 'listing_id' => $_POST['listing_id'], 'created_on' => $poke_time, 'country' => $country, 'start' => new MongoDate (intval ($_POST['start'])) );
			}
			else{
				$args = array ('from_id' => $uid, 'message' => $message, 'type' => $type, 'is_read' => 0, 'created_on' => $poke_time);	
			}
			//send a message for notifying friend requests.
			if ($type == "3"){	
				$args['message'].=" wants to be friends with you!";
				$result = $notification->insertNotification($args, $poke_friends, $device_tokens, $message." wants to be friends with you!");
			}
			else{
				$result = $notification->insertNotification($args, $poke_friends, $device_tokens, $message);
			}
			if ($result) {
				$message = array('msg' => 'Notifications successfully posted');
				$response->code = Response::OK;
			} else {
				$message = array('msg' => 'Error in posting notifications');
				$response->code = Response::INTERNALSERVERERROR;
			}
		} else {
			$message = array('msg' => 'Essential attributes missing.');
			$response->code = Response::INTERNALSERVERERROR;

		}
		$response->addHeader('Content-type', 'application/json');
		$response->body = sendRes($message, 'true', time());
		return $response;
	}


	/**
	 * Check for some minimum attributes.
	 */
	function checkMinArgs($user_args) {
		//Put token
		$values = array('uid', 'message');
		if (!isset ($user_args['friends']) && !isset ($user_args['friend_uids'])){
			return false;
		}
		foreach ($values as $value) {
			if(!isset($user_args[$value])) {
				return false;
			}
		}
		return true;
	}

	function addTimestamp($user_args){
		$time = new MongoDate(time());	
		return $time;
	}
}

?>
