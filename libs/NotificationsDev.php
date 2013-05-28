<?php
require_once dirname(__FILE__).'/logger.php';
require_once dirname(__FILE__)."/Friends.php";
require_once dirname(__FILE__)."/SearchUser.php";
require_once dirname(__FILE__)."/CheckinComments.php";
/*
 * Class to insert/ delete an notification. 
 */
class Notifications {
	private $dbHandle;
	private $logger;
	/*
	 * Default constructor.
	 */
	function __construct() {
		 $this->dbHandle = DbHandler::getConnection();
		$this->logger = Logger::getLogger();
	}

	/*
	 * Function to create the notification
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
		$ch_id = null;
		$start = null;
		$listing_id = null;
		$thread_id = null;
		$success = false;
		if (isset ($args['ch_id'])){
			$ch_id = $args['ch_id'];
		}
		if (isset ($args['listing_id'])){
			$listing_id = $args['listing_id'];
		}
		if (isset ($args['start'])){
			$start = $args['start'];
		}
		$updates = new Updates();
		
		$insert_args = array();
		$insert_args['from_id'] = $args['from_id'];
		$insert_args['type'] = intval ($args['type']);
		$insert_args['is_read'] = $args['is_read'];
		$insert_args['message'] = $args['message'];
		if (isset ($args['fsplacename']) && isset ($args['fsplaceid'])){
			$this->logger->log("foursquare details recieved",Zend_Log::INFO);
                        $insert_args['fsplacename'] = $args['fsplacename'];
                        $insert_args['fsplaceid'] = $args['fsplaceid'];
                }
		if (!$push_only){
			$insert_args['created_on'] = $args['created_on'];	
			$insert_args['country'] = $args['country'];
		}
		if (isset ($args['thread_id'])){
			$insert_args['meta']['thread_id'] = $args['thread_id'];
		}
		if (isset ($listing_id) || $listing_id != ""){
			$country = $args['country'];
			$list_details = $updates->getListingDetails($listing_id, $country, $start);
                        $insert_args['meta']['listing_details'] = $list_details;
                }
		$notif_details = $insert_args;
		/*
		foreach ($uids as $uid){
			$insert_args['to_id'] = $uid;
			$notifications[] = $insert_args;
		}
		*/
		$insert_args['to_id'] = $uids;
		$notification = $insert_args;
		if (!$push_only){	
			//$success = $collection->batchInsert($notifications);
			$success = $collection->insert($notification);
		}
		else{
			$success = true; //make fake insert notif and just send the push notif which is not guarenteed and non blocking
		}
		if (!is_null ($device_tokens)){
			$message = "";
			if (is_null ($push_message)){
				$message = $notif_details["message"];
			}
			else{
				if (isset ($listing_id) || $listing_id != ""){
					$listing_name = $list_details['listing_name'];
					$ch_name = $list_details['ch_name'];
					$message = $push_message. " ".$listing_name." on ".$ch_name;
				}
				else{
					$message = $push_message;
				}
			}

			$this->logger->log (var_export ($device_tokens, true), Zend_Log::INFO);
			if (isset ($device_tokens['android']) && count ($device_tokens['android']) > 0){
				$android_data = array ("apids" => $device_tokens['android'], "android" => array ("alert" => $message, "extra" => json_encode ($notif_details)));	
				$code = $this->SendPushNotifications($android_data, 1);
                        	//error_log (var_export ($data, true));
                        	$this->logger->log ("Sent android push with code : $code",Zend_Log::INFO);
			}
			if (isset ($device_tokens['ios']) && count ($device_tokens['ios']) > 0){
				$ios_data = array ("device_tokens" => $device_tokens['ios'], "aps" => array("alert" => $message, "badge" => "+1"));
				$code = $this->SendPushNotifications($ios_data, 2);
                        	//error_log (var_export ($data, true));
                        	$this->logger->log ("Sent ios push with code : $code",Zend_Log::INFO);
			}
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
		$push_password = "8pFQDEGTTkydM_930JyzMQ:d-6_KF77Q3yhXYWiCIjs5Q";
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

	/**
	 * Function to get latest N notifications for a user
	 */
	function getLatestNotifications($uid, $number, $is_wall = true){
                $operation = false;
		$search = new SearchUser();
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->notifications;
		$notifications = $collection->find(array('to_id' => $uid));
		$notifications->sort(array('created_on' => -1))->limit($number);
		$updates = new Updates();
		$comments = new CheckinComments();
                $type3notifications = array();
                if ($notifications){
                        $notif_arr = Array();
                        foreach ($notifications as $notification){
                                $from_details = $search->getUserDetailsFromUID($notification['from_id']);
                                $to_details = $search->getUserDetailsFromUID($uid);
                                $country = $notification['country'];
				$notification['to_id'] = $uid;	
                                $notification['from_fbid'] = $from_details['fbid'];
                                $notification['to_fbid'] = $to_details['fbid'];
				$notification['from_name'] = $from_details['name'];
                                //$list_details = $updates->getListingDetails($listing_id, $country, $start);
                                //if ($list_details){
                                //        $notification['meta']['listing_details'] = $list_details;
                                //}
				if ($is_wall){
					if ($notification['type'] == "2"){
						$listing_id = $notification['meta']['listing_details']['listing_id'];
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
						$notification['comment_count'] = count ($checkin_comments);
						$notification['checkin_comments'] = $checkin_comments;	
						$notification['checkin_likes'] = $checkin_likes;
						array_push ($notif_arr, $notification);
					}
					if ($notification['type'] == "1"){
						array_push ($notif_arr, $notification);
					}
				}
				else{
					if ($notification['type'] == "2" || $notification['type'] == "1"){
						continue;
					}
					else{
						if ($notification['type'] == "3" && $notification['is_read'] == 0){
                                                	$type3notifications[] = $notification["_id"];
                                         	       	array_unshift ($notif_arr, $notification);
                                        	}
						else{
                                        		array_push ($notif_arr, $notification);
						}
					}
				}
                        }
			if ($is_wall){
                                $status = $collection->update(array('to_id' => $uid, 'type' => array ('$in' => array(1, 2))), array('$set' => array( 'is_read' => 1 )), array('multiple' => true) );
			}
			else{
				foreach ($type3notifications as $type3){
                                        $collection->update(array('_id' => $type3), array('$set' => array( 'is_read' =>  0)));
                                }
				$status = $collection->update(array('to_id' => $uid, 'type' => array('$in' => array(4, 5, 6, 7))), array('$set' => array( 'is_read' => 1 )), array('multiple' => true) );
			}

                        if ($status){
                                return $notif_arr;
                        }
                        else{
                                return $operation;
                        }
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
                //$notifications = $collection->find(array('to_id' => $uid, 'is_read' => 0, array('$or' => array( array('type' => array('$ne' => '2')), array( 'type' => array('$ne' => '3'))))));
		$notifications = $collection->find(array('to_id' => $uid, 'is_read' => 0));
                $count = $notifications->sort(array('_id' => 1))->limit($number)->count();
		return array('count' => $count);
	}
}
