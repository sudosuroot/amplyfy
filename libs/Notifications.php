<?php
require_once dirname(__FILE__).'/logger.php';
require_once dirname(__FILE__)."/Friends.php";
require_once dirname(__FILE__)."/SearchUser.php";
require_once dirname(__FILE__)."/CheckinComments.php";
require_once dirname(__FILE__)."/Response.php";
/*
 * Class to insert/ delete an notification. 
 */
class Notifications {
	private $dbHandle;
	private $logger;
	/*
	 * Default constructor.
	 */
	function __construct() {
		$this->dbHandle = DbHandler::getConnection();
		$this->logger = Logger::getLogger();
		date_default_timezone_set('UTC');
	}

	/*
	 * Function to create the notification
	 * Types of notifications:
	 * Type Comment
	 * --------------
	 * 1	Recommend show
	 * 2 	Checkin 
	 * 3 	Friend Request
	 * 4	Create Conversation
	 * 5	Message on a conversation
	 * 6	New friend addition
	 * 7	Comment on a checkin
	 * 8 	Like a checkin
	 */
	function insertNotification($args, $uids, $device_tokens = NULL, $push_message = NULL, $push_only = false) {
		$operation = false;
		if (count($args) < 4 || count($uids) == 0) {
			$this->logger->log("Too few arguements passed to notification insert!",Zend_Log::INFO);
			return null;
		}
		if($this->dbHandle == null) {
			$this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
			return null;
		}
		$collection = $this->dbHandle->notifications;
		$notifications = array();
		$listing_data_there = false;
		$start = null;
		$listing_id = null;
		$country = "IN_airtel";
		$thread_id = null;
		$insert_args = array();
		$success = false;
		if (isset ($args['listing_id'])){
			$listing_id = $args['listing_id'];
		}
		if (isset ($args['start'])){
			$start = $args['start'];
		}

		/*
		 * preparing all the data for inserting a notification.
		 * mandatory : from_id, to_id, type, message, created_on, is_read.
		 * type 1,2,7,8 : listing_id, start - will be in meta.
		 */

		if (isset ($args['fsplacename']) && isset ($args['fsplaceid'])){
                        $this->logger->log("foursquare details recieved",Zend_Log::INFO);
                        $insert_args['fsplacename'] = $args['fsplacename'];
                        $insert_args['fsplaceid'] = $args['fsplaceid'];
                }
		
		$insert_args['from_id'] = $args['from_id'];
		$insert_args['type'] = intval ($args['type']);
		$insert_args['is_read'] = $args['is_read'];
		$insert_args['message'] = $args['message'];
		$insert_args['to_id'] = $uids;
		
		if (!$push_only){
			$insert_args['created_on'] = $args['created_on'];	
		}
		if ( (isset ($listing_id) || $listing_id != "") ){
                        $insert_args['meta']['listing_id'] = $listing_id;
                        $listing_data_there = true;
                }
                if ( (isset ($start) || $start != "") ){
                        $insert_args['meta']['start'] = $start;
                }
                if (isset ($args['checkin_id'])){
                        $insert_args['meta']['checkin_id'] = $args['checkin_id'];
                }
		$notification = $insert_args;
		if (!$push_only){	
			$success = $collection->insert($notification);
		}
		else{
			$success = true; //make fake insert notif and just send the push notif which is not guarenteed and non blocking
		}
		if (!is_null ($device_tokens)){
			/*
			 * Insert appropriate message in to the notification arrray
			 * If type = 1,2,7,8 then get listing details and add the name, channel in message
			 * else add the message that was sent in.
			 */
			 
			$message = "";
			if (is_null ($push_message)){
				$message = $notification['message'];
			}
			else{
				if ($listing_data_there){
					$updates = new Updates();
					$this->logger->log("Listing details : $listing_id, $country, $start",Zend_Log::INFO);
					$list_details = $updates->getListingDetails($listing_id, $country, $start);
					$listing_name = $list_details['listing_name'];
					$ch_name = $list_details['ch_name'];
					$message = $push_message. " ".$listing_name." on ".$ch_name;
				}
				else{
					$message = $push_message;
				}
			}
			// push the notifications.
			$this->logger->log ("Message = $message", Zend_Log::INFO);
			if (isset ($device_tokens['android']) && count ($device_tokens['android']) > 0){
				$extra = array();
				if (in_array($notification['type'], array(3,6,10))){
					$extra['type'] = 2;
				}
				else{
					$extra['type'] = 1;
					$extra['data'] = $notification['_id']->{'$id'};
				}
				$this->logger->log ("Notif details : ".var_export ($extra, true)." total device tokens = ". count($device_tokens['android']), Zend_Log::INFO);
				$android_data = array ("apids" => $device_tokens['android'], "android" => array ("alert" => $message, "extra" => json_encode($extra)));
				$code = $this->SendPushNotifications($android_data, 1);
                        	//error_log (var_export ($data, true));
                        	$this->logger->log ("Sent android push with code : ".var_export ($code, true),Zend_Log::INFO);
			}
			/*
			if (isset ($device_tokens['ios']) && count ($device_tokens['ios']) > 0){
				$ios_data = array ("device_tokens" => $device_tokens['ios'], "aps" => array("alert" => $message, "badge" => "+1"));
				$code = $this->SendPushNotifications($ios_data, 2);
                        	//error_log (var_export ($data, true));
                        	$this->logger->log ("Sent ios push with code : $code",Zend_Log::INFO);
			}
			*/
		}
		if (isset($success)) {
			if ($success != false) {
				$operation = true;
			}
		}
		return $operation;
	}


	/*
	 * Function to send push notifications thru urban air ship.
	 */

	function SendPushNotifications($data = array(), $device){
		//$push_password = "R3vUyMWQQnSdT96XTKCaQA:iw1BOGNpRHWKWiuh3DAaRA"; //dev
		$push_password = "tENe0LmCQCOEJ4wt5p8kdA:mTRlMrxERcKawitNOzrNFQ"; //prod
		if ($device == 2){
			$push_password = "XluyqnUpQTueesCsAJ6VIA:jb_mIzl-S-6XCOuvga_0Jg";

		}
                $url = "https://go.urbanairship.com/api/push/";
                $curl = new CURL();
                $curl->retry = 1;
                $header = array ("Content-Type: application/json");
                $opts = array( CURLOPT_RETURNTRANSFER => true, CURLOPT_USERPWD => $push_password, CURLOPT_HTTPHEADER => $header, CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode ($data));
                $curl->addSession($url, $opts);
                $result = $curl->exec();
                $code = $curl->info(false, CURLINFO_HTTP_CODE);
                $curl->clear();
                return $code[0];
	}


	/*
	 * Fucntion to delete all notifications of a user.
	 */
	function deleteNotifications($uid) {
		$operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->notifications;
		$operation = $collection->remove(array("to_id" => $uid), false);
		return $operation;
	}

	function deleteNotification($notif_id){
		$operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->notifications;
		$operation = $collection->remove(array ("_id" => new MongoId($notif_id)));
		$this->logger->log("removed notification id = $notif_id",Zend_Log::INFO);
		return $operation;	
	}

	/*
	 * Function to get Notification related to friend requests.
	 * Types - 3, 6, 9 (request, new friend added, request accepted)
	 * @param - uid, number
	 * @return - array with the Notification structure. Empty if none available.
	 */
	function getLatestFriendNotifications($uid, $number, $ts = null){
		$operation = false;
		if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
		$types = array (3, 6, 10); //types to pull
		$mark_as_read_types = array(6, 10);
		$notif_arr = array(); // holder to return
		$search = new SearchUser();
                $collection = $this->dbHandle->notifications;
		if (is_null ($ts)){
                	$notifications = $collection->find(array('to_id' => $uid, 'type' => array('$in' => $types)));
		}
		else{
			$notifications = $collection->find(array('to_id' => $uid, 'type' => array('$in' => $types), 'created_on' => array('$lt' => new MongoDate($ts))));
		}
                $notifications->sort(array('created_on' => -1))->limit($number);
		foreach ($notifications as $notification){
			$from_details = $search->getUserDetailsFromUID($notification['from_id']);
			$to_details = $search->getUserDetailsFromUID($uid);
			$notification['to_id'] = $uid; 
			$notification['from_fbid'] = $from_details['fbid'];
			$notification['to_fbid'] = $to_details['fbid'];
			$notification['from_id'] = $notification['from_id'];
			$notification['from_name'] = $from_details['name'];
			if ($notification['type'] == "3" && $notification['is_read'] == 1){
				continue;
			}
			if ($notification['type'] == "3" && $notification['is_read'] == 0){	
                                array_unshift ($notif_arr, $notification);
                        }
			else{
                                array_push ($notif_arr, $notification);
                        }
		}	
		$status = $collection->update(array('to_id' => $uid, 'type' => array('$in' => $mark_as_read_types), 'is_read' => 0), array('$set' => array( 'is_read' => 1 )), array('multiple' => true) );
		if ($status){
                	return $notif_arr;
                }
                else{
                        return $operation;
                }
	}

	private function GetDisplayMessage($froms = array(), $type, $list_details, $last_activity, $text = null){
		$friends = "";
		$verbs = "";
		$message = "";
		$message_holder = array();
		$count = count ($froms);
		$present_tense = true;
		$list_details_required = true;
                $cur_time = time();
                $show_stop_time = $list_details['stop']->sec;
                if ($cur_time > $show_stop_time){
                	$present_tense = false;
                }
		for ($i = 0; $i < $count; $i++){
			if ($i == $count - 1){
				$friends .= $froms[$i]['name'];
			}
			else{
				$friends .= $froms[$i]['name'].", ";
			}
		}
		switch ($type){
                        case "2" : 
				   if ($count > 1){
					if ($present_tense){
				   		$verbs = "are watching";
					}
					else{
						$verbs = "were watching";
					}
				   }
				   else{
					$list_details_required = false;
					if ($present_tense){
						$verbs = "is watching";
					}
					else{
						$verbs = "was watching";
					}
				   }
                                   break;
                        case "1" : 
				   if ($count > 1){
					$verbs = "recommend watching";
				   }
				   else{
					$verbs = "recommends watching";
				   }
                                   break;
                        case "9" :
                                   if ($count > 1){
                                        $verbs = "like";
                                   }
                                   else{
                                        $verbs = "likes";
                                   }
                                   break;
			case "7" :
				   $verbs = "commented on ".$last_activity['name']. " checkin to";
				   break;
			case "8" : $verbs = "liked ".$last_activity['name']. " checkin to";
				   break;
                }
		if ($text){
			$message_holder['text'] = $text;
		}
		else{
			if ($list_details_required){
				if ($type == 9){
					$message = $friends." ".$verbs." ".$list_details['listing_name'];
				}
				else{
					$message = $friends." ".$verbs." ".$list_details['listing_name']." on ".$list_details['ch_name'];
				}
			}
			else{
				$message = $friends." ".$verbs;
			}
			$message_holder['text'] = $message;
		}
		$message_holder['last_activity'] = $last_activity;
		return $message_holder;
	}

	/*
	 * Group Notifications based : 
	 * 1) checkins single/multiple 2) recos single/multiple 3) comments/likes on checkins 4) likes on shows.
	 */
	function GroupNotifications($uid, $number, $ts = null, $notification_id = null){
		if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $types = array(1,2,7,8,9);
                $collection = $this->dbHandle->notifications;
		if (is_null($notification_id)){
			if (is_null ($ts)){
                		$notifications = $collection->find(array('to_id' => $uid, 'type' => array('$in' => $types)));
			}
			else{
				$notifications = $collection->find(array('to_id' => $uid, 'type' => array('$in' => $types), 'created_on' => array('$lt' => new MongoDate($ts))));
			}
        	        $notifications->sort(array('created_on' => -1))->limit($number);
		}
		else{
			$notifications = $collection->find(array('_id' => new MongoID($notification_id)));
		}
		$notify_hash = array();
		$search = new SearchUser();
		$updates = new Updates();
		$comments = new CheckinComments();
		if (!is_null($uid)){
			$profile = new UserProfile($uid);
                	$likes = $profile->getLikedShows();
                	foreach ($likes as $l){
                		$user_show_likes[$l] = true;
                	}
		}
		foreach ($notifications as $notification){
			$checkin_comments = array();
                	$checkin_likes = array();
			if (isset ($notification['meta'])){
				if (isset ($notification['meta']['listing_id'])){
					$listing_id = $notification['meta']['listing_id'];
				}
				else{
					$listing_id = $notification['meta']['listing_details']['listing_id'];
				}
				if (isset ($notification['meta']['start'])){
					$start_key = $notification['meta']['start']->sec;
                                	$start = $notification['meta']['start'];
				}
				else if (isset ($notification['meta']['listing_details']['start'])){
					$start_key = $notification['meta']['listing_details']['start']->sec;
                                	$start = $notification['meta']['listing_details']['start'];
				}
				else{
					$start_key = null;
					$start = null;
				}
				$type = intval ($notification['type']);
                                $list_details = $updates->getListingDetails($listing_id, "IN_airtel", $start);
				if (isset ($user_show_likes[intval($list_details['listing_id'])])){
					$list_details['is_like'] = true;
				}
				$from_id = $notification['from_id'];
				$from_details = $search->getUserDetailsFromUID($from_id);
				$from_fbid = $from_details['fbid'];
                                $from_name = $from_details['name'];
                                $created_on = $notification['created_on'];
                                $message = $notification['message'];
				$pic_url = "https://graph.facebook.com/$from_fbid/picture?type=normal";
				$from_holder = array('pic_url' => $pic_url, 'uid' => $from_id, 'name' => $from_name, 'created_on' => $created_on, 'message' => $message);
				if (isset ($notification['meta']['checkin_id'])){
					$update_id = $notification['meta']['checkin_id'];
					$key = $listing_id."|".$start_key."|".$type."|".$update_id; // Key format for grouping 123|23456781234|2.
					$checkin_details = $comments->getCheckinDetails($update_id, $listing_id, $start_key);
                                	if (isset($checkin_details['comments'])){
                                        	$checkin_comments = $checkin_details['comments'];
                                	}
                                	if (isset($checkin_details['likes'])){
                                        	$checkin_likes = $checkin_details['likes'];
                                	}
				}
				else{
					if ($type != 1 && $type != 9){
						$checkin_details = $comments->getCheckinDetails($from_id, $listing_id, $start_key);
						if (isset($checkin_details['comments'])){
                                                	$checkin_comments = $checkin_details['comments'];
                                        	}
                                        	if (isset($checkin_details['likes'])){
                                                	$checkin_likes = $checkin_details['likes'];
                                        	}
					}	
					$key = $listing_id."|".$start_key."|".$type;
				}
				if (isset ($notify_hash[$key])){
					//aggregate notifications.
					if ($type == 7 || $type == 8){
						if ($created_on->sec < $notify_hash[$key]['created_on']->sec){
							continue;
						}
						$notify_hash[$key]['message'] = $this->GetDisplayMessage($notify_hash[$key]['activity'], $type, $list_details, $from_holder, $message);
                                                $notify_hash[$key]['created_on'] = $created_on;
					}
					else{
						$activity = $from_holder;
						$activity['likes'] = $checkin_likes;
						$activity['comments'] = $checkin_comments;
						$notify_hash[$key]['activity'][] = $activity;  
						$notify_hash[$key]['message'] = $this->GetDisplayMessage($notify_hash[$key]['activity'], $type, $list_details, $from_holder);
						$notify_hash[$key]['created_on'] = $created_on;
					}
				}
				else{
					//New notification.
					//if the type is 7 or 8. Split the type 2 notification.
                                        if ($type == 7 || $type == 8){
                                                if (isset ($notification['meta']['checkin_id'])){
                                                        $checkin_id = $notification['meta']['checkin_id'];
							$checkin_id_details = $search->getUserDetailsFromUID($checkin_id);
							$checkin_fbid = $checkin_id_details['fbid'];
							$checkin_id_details_holder = array();
							$checkin_id_details_holder[] = array('pic_url' => "https://graph.facebook.com/$checkin_fbid/picture?type=normal", 'uid' => $checkin_id, 'name' => $checkin_id_details['name'], 'likes' => $checkin_likes, 'comments' => $checkin_comments);
							$notify_hash[$key]['activity'] = $checkin_id_details_holder;
							$notify_hash[$key]['message'] = $this->GetDisplayMessage($notify_hash[$key]['activity'], $type, $list_details, $from_holder, $message);
							$notify_hash[$key]['show'] = $list_details;
							$notify_hash[$key]['type'] = $type;
							$notify_hash[$key]['created_on'] = $created_on;
                                                }
                                                else{   
                                                        $this->logger->log("No checkin handle", Zend_Log::INFO);
                                                        continue;
                                                }
                                        }
					else{
						$activity = $from_holder;
                                                $activity['likes'] = $checkin_likes;
                                                $activity['comments'] = $checkin_comments;
						$notify_hash[$key]['activity'][] = $activity;
						$notify_hash[$key]['show'] = $list_details;
						$notify_hash[$key]['message'] = $this->GetDisplayMessage($notify_hash[$key]['activity'], $type, $list_details, $from_holder);
						if ($type == 9){
                                                	$type = 1;
                                                }
						$notify_hash[$key]['type'] = $type;
						$notify_hash[$key]['created_on'] = $created_on;
					}
				}
			}
		}
		if (!is_null ($uid)){
			$status = $collection->update(array('to_id' => $uid, 'type' => array('$in', $types)), array('$set' => array( 'is_read' => 1 )), array('multiple' => true) );
		}
		$notif_arr = array_values ($notify_hash);
		return $notif_arr;	
	}

	/**
	 * Function to get latest N notifications for a user
	 */
	function getLatestNotifications($uid, $number, $overlay = false){
                $operation = false;
		$search = new SearchUser();
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
		$types = array(1,2,7,8);
                $collection = $this->dbHandle->notifications;
		$notifications = $collection->find(array('to_id' => $uid, 'type' => array('$in' => $types)));
		$notifications->sort(array('created_on' => -1))->limit($number);
		$updates = new Updates();
		$comments = new CheckinComments();
                $notif_arr = Array();
                foreach ($notifications as $notification){
                	$from_details = $search->getUserDetailsFromUID($notification['from_id']);
                        $to_details = $search->getUserDetailsFromUID($uid);
			$notification['to_id'] = $uid;	
                        $notification['from_fbid'] = $from_details['fbid'];
                        $notification['to_fbid'] = $to_details['fbid'];
			$notification['from_id'] = $notification['from_id'];
			$notification['from_name'] = $from_details['name'];
			$listing_id = $notification['meta']['listing_id'];
                        $start = $notification['meta']['start'];
			if (isset ($notification['meta']['listing_id']) && isset ($notification['meta']['start'])){
				$list_details = $updates->getListingDetails($listing_id, "IN_airtel", $start);
				if ($list_details){
					if ($overlay){
						$notification['meta']['listing_name'] = $list_details['listing_name'];
						$notification['meta']['ch_name'] = $list_details['ch_name'];
					}
					else{
						$notification['meta']['listing_details'] = $list_details;
					}	
				}
			}
			if ($notification['type'] == "2" && !$overlay){
				$update_id = $notification['from_id'];
				$time = $notification['meta']['listing_details']['start']->sec;
				$checkin_details = $comments->getCheckinDetails($update_id, $listing_id, $time);
                                $checkin_comments = array();
                                $checkin_likes = array();
                                if (isset($checkin_details['comments'])){
                        	    	$checkin_comments = $checkin_details['comments'];
                                }
                                if (isset($checkin_details['likes'])){
                                        $checkin_likes = $checkin_details['likes'];
                                }
				if (isset ($notification['meta']['listing_details']['meta']['desc'])){
					unset ($notification['meta']['listing_details']['meta']['desc']);
				}
                                $notification['comment_count'] = count ($checkin_comments);
                                $notification['checkin_comments'] = $checkin_comments;
                                $notification['checkin_likes'] = $checkin_likes;
			}
                        array_push ($notif_arr, $notification);
		}
                $status = $collection->update(array('to_id' => $uid, 'type' => array('$in', $types)), array('$set' => array( 'is_read' => 1 )), array('multiple' => true) );
                if ($status){
                	return $notif_arr;
                }
                else{
                        return $operation;
                }
        }

        function getUserNotificationRequests($uid, $number){
                $operation = false;
                $search = new SearchUser();
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->notifications;
                $notifications = $collection->find(array('from_id' => $uid));
                $notifications->sort(array('_id' => 1))->limit($number);

                if ($notifications){
                        $notif_arr = Array();
                        foreach ($notifications as $notification){
                                $from_fbid = $search->getFBIDFromUID($notification['from_id']);
                                $to_fbid = $search->getFBIDFromUID($notification['to_id']);
                                $notification['from_fbid'] = $from_fbid;
                                $notification['to_fbid'] = $to_fbid;
                                array_push ($notif_arr, $notification);
				return $notif_arr;
                        }
                }
                else{
                        return $operation;
                }
        }


	function getNotificationCount($uid, $number){
		$operation = false;
                $search = new SearchUser();
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->notifications;
                $notifications = $collection->find(array('to_id' => $uid, 'is_read' => 0));
                $count = $notifications->sort(array('_id' => 1))->limit($number)->count();
		return array('count' => $count);
	}
}
