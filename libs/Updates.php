<?php
require_once dirname(__FILE__).'/logger.php';
require_once dirname(__FILE__)."/DbHandler.php";
require_once dirname(__FILE__)."/SearchUser.php";
require_once dirname(__FILE__).'/Listings.php';
require_once dirname(__FILE__).'/Friends.php';
require_once dirname(__FILE__).'/CheckinPointsHandler.php';
/*
 * Class to insert/ delete an update. 
 */
class Updates {
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
	function insertUpdate($user_args) {
		$success = false;
		if (count($user_args) < 3) {
			$this->logger->log("Too few arguements passed to insert update!",Zend_Log::INFO);
			return null;
		}
		if($this->dbHandle == null) {
			$this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
			return null;
		}
		$collection = $this->dbHandle->updates;
		$search = new SearchUser();
		$client= new GearmanClient();
		$client->addServer();
		$fbid = $search->getFBIDFromUID($user_args['uid']);
		if ($fbid == null){
			return false;
		}
		$user_args['fbid'] = $fbid;
		$update_table_args = $user_args;
		$update_table_args['created_on'] = new MongoDate($user_args['created_on']);
		$handler = new CheckinPointsHandler($user_args);
		$return = array();
		$pointsForCheckin = true;
		$shareOnFacebook = false;
		if (isset($user_args['shareOnFacebook'])){
			$flag = $user_args['shareOnFacebook'];
			$this->logger->log("share on facebook : $flag",Zend_Log::INFO);
			if ($flag == "true"){
				$shareOnFacebook = true;
			}
		}
		if ($handler->isValidCheckin()){
//		if (true){
			$this->logger->log("valid checkin for points",Zend_Log::INFO);
			$return = $handler->getCheckintype();
		}
		else{
			$this->logger->log("no points for this checkin",Zend_Log::INFO);
			$return['details'] = array();
                	$return['points'] = 0;
			$pointsForCheckin = false;
			return $return;
		}
		$update_table_args['meta'] = array('score' => $return);//add the points details for the checkin.
		$this->logger->log(var_export($return, true),Zend_Log::INFO);
		$success = $collection->insert($update_table_args);
		if (isset($success)) {
			if ($success != false) {
				if ($pointsForCheckin){
					$points = array('uid' => $user_args['uid'], 'points' => $return['points'], 'ts' => $user_args['created_on']);
					$points_job = $client->doBackground("UpdateUserPointsV4", json_encode($points));
					$this->logger->log("update user points job id = $points_job", Zend_Log::INFO);
				}
				if ($shareOnFacebook){
					$post_fb_job = $client->doBackground("PostTofacebookWallV4", json_encode ($user_args));
					$this->logger->log("checking notifiy job id = $post_fb_job", Zend_Log::INFO);
				}
				$update_job = $client->doBackground("UpdateUserTableForCheckinV4", json_encode ($user_args));
				$timing_job = $client->doBackground("UpdateTimingsTableForCheckinV4", json_encode ($user_args));
				$notify_job = $client->doBackground("SendCheckinNotificationsV4", json_encode ($user_args));
				$this->logger->log("user table update job id = $update_job", Zend_Log::INFO);
				$this->logger->log("timings table update job id = $timing_job", Zend_Log::INFO);
				$this->logger->log("checking notifiy job id = $notify_job", Zend_Log::INFO);
				$operation = $return;
			}
		}
		return $operation;
	}

	/*
	 * Fucntion to delete the update.
	 */
	function deleteUpdate($update_id) {
		$operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->updates;
		echo "deleting id: $update_id";
		$operation = $collection->remove(array("_id" => new MongoID($update_id)), true);
		return $operation;
	}

	function getListingGlobalTrend($listing_id){
		$operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->updates;

		$count = $collection->find(array("listing_id" => $listing_id))->count();
		return $count;
	}




	function getGlobalTrends(){
                $operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->updates;
		$collection1 = $this->dbHandle->listings;
		$collection2 = $this->dbHandle->channels;

		$keys = array("listing_id" => 1);

		$initial = array("uids" => array());

		$reduce = "function (obj, prev) { prev.uids.push(obj.uid); }";

		$g = $collection->group($keys, $initial, $reduce);
		if ($g){
			//return $g['retval'];
			$group = $g['retval'];
			$return = array(); 
			foreach ($group as $listing){
				$listing_id = $listing['listing_id'];
				$uids = $listing['uids'];
				$count = count ($uids);
				if ($listing_id != null && isset ($listing_id)){
					$listing_details = $this->getListingDetails($listing_id);
					$listing_details['count'] = $count;
					array_push ($return, $listing_details);
				}
			}
			$this->array_sort_by_column($return, 'count');
			return array_slice ($return, 0, 20);
		}
		else{
			return $operation;
		}

	}

        function array_sort_by_column_new(&$arr, $key, $col, $dir = SORT_DESC) {
                $sort_col = array();

                foreach ($arr as $collec){
                        $sort_col[$collec[$key]] = $collec[$col];
                }
                array_multisort($sort_col, $dir, $arr);
        }


	function array_sort_by_column(&$arr, $col, $dir = SORT_DESC) {
    		$sort_col = array();
    		foreach ($arr as $key=> $row) {
       	 		$sort_col[$key] = $row[$col];
    		}

    		array_multisort($sort_col, $dir, $arr);
	}
	

	function getListingDetails($listing_id, $country="IN_airtel", $start=NULL){
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
		$collection = $this->dbHandle->timings;
		$listings = array();
		if (is_null ($start)){
			$listings_arr = $collection->find(array ("country" => $country, "listing_id" => intval ($listing_id)))->sort(array('_id' => -1))->limit(1);
			foreach ($listings_arr as $l){
				$listings = $l;
			}
		}
		else{
			$listings = $collection->findOne(array ("country" => $country, "listing_id" => intval ($listing_id), "start" => $start));
		}
		return $listings;
	}


	function getListingsDetails($listings){
		if($this->dbHandle == null) {
       			$this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
       			return null;
		}
		$listings_array = array();
                $collection3 = $this->dbHandle->timings;
                $listing_details = $collection3->find (array ('listing_id' => array('$in' => $listings)));
                foreach ($listing_details as $lis){
			$listings_array[] = $lis;
                }
		return $listings_array;

	}

	function getFriendsWatching($listing_id, $uid){
                $operation = false;
		$friendsDB = new Friends();
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->updates;
		$friends = $friendsDB->getFriends($uid);
		if($friends == null) {
			$this->logger->log("token  error",Zend_Log::INFO);
			return null;
		}

		$friends_arr = array();
		$return = array();

		foreach ($friends as $friend){
			if ($friend['uid'] == ""){
				continue;
			}
			array_push ($friends_arr, "".$friend['uid']."");
		}
		$uids = $collection->find(array('listing_id' => "".$listing_id, 'uid' => array('$in' => $friends_arr)));
		foreach ($uids as $uid){
			array_push ($return, $uid['uid']);
		}
		return $return;

	}

	function getFBIDsWatching ($listing_id, $start_time){
		$operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->timings;
		$fbids = $collection->findOne(array ('listing_id' => intval ($listing_id), 'start' => $start_time), array('fbids_watching'));
		return $fbids['fbids_watching'];
	}

	function getFriendsTrend($listing_id, $uid){
		                $operation = false;
                $this->dbHandle = $this->dbHandle->getConnection();
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->updates;
		$friends = $friendsDB->getFriends($uid, false);
                $friends_arr = array();
                $return = array();
                foreach ($friends as $friend){
			if ($friend['uid'] == ""){
                                continue;
                        }
                        array_push ($friends_arr, intval($friend['uid']));
                }
                $count = $collection->find(array('listing_id' => "".$listing_id, 'uid' => array('$in' => $friends_arr)))->count();
		return $count;
	}

	function getFriendsUpdates($uid){
                $operation = false;
		$search = new SearchUser();
		$listing = new Listings();
		$friendsDB = new Friends();
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->updates;
		$friends = $friendsDB->getFriends($uid, false);
                $friends_arr = array();
                $return = array();
                foreach ($friends as $friend){
                        array_push ($friends_arr, "".$friend['uid']."");
                }

                $start = $this->dates('10days');
                $end = $this->dates('now');

                $friends_updates = $collection->find(array("created_on" => array('$gt' => $start, '$lte' => $end), 'uid' => array('$in' => $friends_arr)));
		$friends_updates->sort(array('_id' => 0));
              	$resp_arr = array();
                foreach($friends_updates as $friend_update){
			$listing_details = $this->getListingDetails($friend_update['listing_id']);
			$listing_details['fbid'] = $friend_update['fbid'];
			$listing_details['uid'] = $friend_update['uid'];
			$listing_details['created_on'] = $friend_update['created_on'];
                        array_push($resp_arr, $listing_details);
               	}
		return $resp_arr;
	}

        function getUpdatesOfFriends($uid, $fbid, $friends){
                $operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->updates;
                $return = array();

                $start = $this->dates('10days');
                $end = $this->dates('now');

                $friends_updates = $collection->find(array("created_on" => array('$gt' => $start, '$lte' => $end), 'uid' => array('$in' => $friends)));
                $friends_updates->sort(array('_id' => -1));
                $resp_arr = array();
                foreach($friends_updates as $friend_update){
                        //send it similar to the notification response.
                        $listing_id = $friend_update['listing_id'];
                        $country = $friend_update['country'];
                        $start = new MongoDate($friend_update['time']);
                        $from_id = $friend_update['uid'];
                        $from_fbid = $friend_update['fbid'];
                        $message = $friend_update['update'];
                        $created_on = $friend_update['created_on'];
                        $type = 2;
                        $to_id = $uid;
                        $to_fbid = $fbid;
                        $list_details = $this->getListingDetails($listing_id, $country, $start);
                        $notify_all = array('from_id' => $from_id, 'to_id' => $to_id, 'from_fbid' => $from_fbid, 'to_fbid' => $to_fbid, 'type' => $type, 'message' => $message, 'meta' => array('listing_details' => $list_details), 'country' => $country, 'created_on' => $created_on);
                        array_push($resp_arr, $notify_all);
                }
                return $resp_arr;
        }


	function GetTopListings($uid, $country = "UK_sky"){
                $operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->timings;
                $start = $this->dates('10days');
                $end = $this->dates('now');


		$channel_hash = array();
		$listings_hash = array();
		$ret_channel_array = array();
		$ret_listing_array = array();

		$keys = array('ch_id' => 1, 'ch_name' => 1, 'icon' => 1);
		$keys1 = array('listing_id' => 1, 'listing_name' => 1, 'ch_name' => 1, 'ch_id' => 1, 'icon' => 1);
		$initial = array('count' => 0);
		$reduce = "function (obj, prev) { prev.count += obj.view_count; }";
		$cond = array('country' => $country, 'view_count' => array('$gt' => 0), 'start' => array('$gt' => $start));

		$g = $collection->group($keys, $initial, $reduce, $cond);
		$g1 = $collection->group($keys1, $initial, $reduce, $cond);

		$channel_hash = $g['retval'];
		$listing_hash = $g1['retval'];

		$this->array_sort_by_column_new($listing_hash, 'listing_id', 'count');
		$this->array_sort_by_column_new($channel_hash, 'ch_id', 'count');
		$top_ch_count = 0;
		$top_list_count = 0;
		foreach ($channel_hash as $channel){
			if ($top_ch_count == 5){
				break;
			}
			$ret_channel_array[] = array('channel_id' => $channel['ch_id'], 'ch_name' => $channel['ch_name'], 'icon' => $channel['icon'], 'count' => $channel['count']);
			$top_ch_count++;
		}


                foreach ($listing_hash as $listing_details){
                        if ($top_list_count == 5){
                                break;
                        }
                        $ret_listing_array[] = array ('listing_id' => $listing_details['listing_id'], 'icon' => $listing_details['icon'], 'ch_name' => $listing_details['ch_name'], 'count' => $listing_details['count'], 'ch_id' => $listing_details['ch_id'], 'listing_name' => $listing_details['listing_name']);
			$top_list_count++;
                }


		$trend_holder = array();
		$trend_holder['top_listings'] = $ret_listing_array;
		$trend_holder['top_channels'] = $ret_channel_array;
                return $trend_holder;
	}

	function getShowCheckins($listing_id, $limit=5){
		$operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->updates;
                $search = new SearchUser();
		$updates = $collection->find(array('listing_id' => $listing_id));
		$updates->sort(array('_id' => 0))->limit($limit);
		$last_checkins = array();
		foreach ($updates as $update){
			$checkins = array();
			$checkins['fbid'] = $update['fbid'];
			$from_details = $search->getUserDetailsFromUID($update['uid']);
			$checkins['name'] = $from_details['name'];
			$checkins['created_on'] = $update['created_on'];
			$last_checkins[] = $checkins;
		}	
		return $last_checkins;
	}

	function getAllUpdates($limit, $ts = null){
                $operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->notifications;
                $start = $this->dates('100days');
                $end = $this->dates('now');
		$search = new SearchUser();
		if (is_null ($ts)){
                	$friend_updates = $collection->find(array("type" => array('$in' => array(2,9))));
		}
		else{
			$friend_updates = $collection->find(array("type" => array('$in' => array(2,9)), 'created_on' => array('$lt' => new MongoDate($ts))));
		}
		$friend_updates->sort(array('created_on' => -1))->limit($limit);
                $resp_arr = array();
		$duplicate_check = array();
                foreach($friend_updates as $friend_update){
			//send it similar to the notification response.
			if (isset ($friend_update['meta']['listing_id'])){
                        	$listing_id = $friend_update['meta']['listing_id'];
			}
			else{
				$listing_id = $friend_update['meta']['listing_details']['listing_id'];
			}
                        $country = "IN_airtel";
			if (isset ($friend_update['meta']['start'])){
				$start = $friend_update['meta']['start'];
			}
			else if(isset ($friend_update['meta']['listing_details']['start'])){
				$start = $friend_update['meta']['listing_details']['start'];
			}
			else{
				$start = null;
			}
                        $from_id = $friend_update['from_id'];
			$from_details = $search->getUserDetailsFromUID($from_id);
			$from_name = $from_details['name'];
                        $from_fbid = $from_details['fbid'];
                        $message = $friend_update['message'];
                        $created_on = $friend_update['created_on'];
			if (isset ($friend_update['fsplaceid'])){
                                $fsplaceid = $friend_update['fsplaceid'];
				$fsplacename = $friend_update['fsplacename'];
                        }
                        $type = $friend_update['type'];
			//duplicate key check
			$key = $from_id."|".$type."|".$listing_id;
			if (isset ($duplicate_check[$key])){
				continue;
			}
			$duplicate_check[$key] = true;
                        $to_id = null;
                        $to_fbid = null;
                        $list_details = $this->getListingDetails($listing_id, $country, $start);
			if (isset ($friend_update['fsplaceid'])){
				$notify_all = array('from_name' => $from_name, 'from_id' => $from_id, 'to_id' => $to_id, 'from_fbid' => $from_fbid, 'to_fbid' => $to_fbid, 'is_read' => 1, 'type' => $type, 'message' => $message, 'meta' => array('listing_details' => $list_details), 'country' => $country, 'created_on' => $created_on, 'fsplaceid' => $fsplaceid, 'fsplacename' => $fsplacename);
			}
			else{
                        	$notify_all = array('from_name' => $from_name, 'from_id' => $from_id, 'to_id' => $to_id, 'from_fbid' => $from_fbid, 'to_fbid' => $to_fbid, 'is_read' => 1, 'type' => $type, 'message' => $message, 'meta' => array('listing_details' => $list_details), 'country' => $country, 'created_on' => $created_on);
			}
                        array_push($resp_arr, $notify_all);
                }
                return $resp_arr;
	}

        function getUserUpdates($uid, $limit = 10){
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->updates;
                $start = $this->dates('100days');
                $end = $this->dates('now');
                $user_updates = $collection->find(array("created_on" => array('$gt' => $start, '$lte' => $end), "uid" => "".$uid));
		$user_updates->sort(array('_id' => 0))->limit($limit);
                $resp_arr = array();
                foreach($user_updates as $user_update){
			$time = new MongoDate($user_update['time']);
                        $listing_details = $this->getListingDetails($user_update['listing_id'], "IN_airtel", $time);
			$update_holder = array('listing' => $listing_details, 'created_on' => $user_update['created_on'], 'comment' => $user_update['update']);
			$resp_arr[] = $update_holder;
                }
                return $resp_arr;
        }


	function getUserProfile($uid, $country = "UK_sky", $top_shows = false, $from_uid = null){
		$operation = false;
		$friend_status = 3; //0 - Already freinds | 1 - Request sent by me, pending approval | 2 - Request sent by him/her show accet reject | 3 - Not freinds.
		$notif_id = null;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
		$collection = $this->dbHandle->updates;
		$timings_collection = $this->dbHandle->timings;
		$user_collection = $this->dbHandle->users;
		$keys = array('ch_id' => 1);
		$initial = array('count' => 0);
		$reduce = "function (obj, prev) { prev.count += 1; }";
		$cond = array('uid' => $uid, 'country' => $country);

		$g = $collection->group($keys, $initial, $reduce, $cond);

		if ($top_shows){
			$keys1 = array('listing_id' => 1);
			$g1 = $collection->group($keys1, $initial, $reduce, $cond);
			$top_shows = $g1['retval'];
			$this->array_sort_by_column_new($top_shows, 'listing_id', 'count');
		}


		$top_channels = $g['retval'];

		$total_checkins = 0;
		$unique_channels = 0;
		foreach ($top_channels as $channel){
			$unique_channels++;
			$total_checkins += $channel['count'];
		}


		$this->array_sort_by_column_new($top_channels, 'ch_id', 'count');
		$ret_channel_array = array();
		$top_c = 0;
		$ret = array();

		foreach ($top_channels as $channel){
			if ($top_c >= 5){
				break;
			}
			$details = $timings_collection->findOne(array('ch_id' => $channel['ch_id']), array('ch_name', 'icon'));
			$ret_channel_array[] = array('channel_id' => $channel['ch_id'], 'ch_name' => $details['ch_name'], 'icon' => $details['icon'], 'count' => $channel['count']);
			$top_c++;
		}


		if ($top_shows){
			$top_s = 0;
			$ret_listings_array = array();
                	foreach ($top_shows as $show){
                        	if ($top_s >= 5){
                                	break;
                        	}
				$details = $timings_collection->findOne(array('listing_id' => intval($show['listing_id'])), array('ch_name', 'icon', 'listing_name', 'ch_id'));
                        	$ret_listings_array[] = array('listing_id' => $show['listing_id'], 'count' => $show['count'], 'ch_name' => $details['ch_name'], 'ch_id' => $details['ch_id'], 'listing_name' => $details['listing_name'], 'icon' => $details['icon']);
				$top_s++;
                	}
			$ret['shows'] = $ret_listings_array;
		}
		$last_update = $user_collection->findOne(array('uid' => intval($uid)), array('last_update', 'fullname', 'points', 'fbid'));
		$fbid = $last_update['fbid'];
		$friends = new Friends();
		$user_friends = $friends->getFBAMFriends($uid);
		//Check for friend status here.
		if (!is_null($from_uid)){
			if (in_array($from_uid, $user_friends)){
				$friend_status = 0;//already friends
			}	
			else{
				$notif_collection = $this->dbHandle->notifications;	
				$type2 = $notif_collection->findOne(array('from_id' => $uid, 'to_id' => $from_uid, 'type' => 3));
				if (isset ($type2['from_id'])){
					$friend_status = 2;
					$notif_id = $type2['_id']->{'$id'};
				}
				else{
					$type1 = $notif_collection->findOne(array('from_id' => $from_uid, 'to_id' => $uid, 'type' => 3));
					if (isset ($type1['from_id'])){
						$friend_status = 1;
					}
					else{
						$friend_status = 3;
					}
				}
			}
			$ret['friend_status'] = $friend_status;
		}
		$ret['notification_id'] = $notif_id;
		$ret['friend_count'] = count($user_friends);
		$ret['user_meta'] = array('uid' => $uid, 'fbid' => $fbid, 'pic_url' => "https://graph.facebook.com/$fbid/picture?type=normal", 'fullname' => $last_update['fullname'], 'location' => null);
		$ret['channels'] = $ret_channel_array;
		$ret['last_update'] = $last_update['last_update'];
		$ret['points'] = (isset($last_update['points'])?$last_update['points']:0);
		$ret['unique_channels'] = $unique_channels;
		$ret['total_checkins'] = $total_checkins;
		$ret['checkins'] = $this->getUserUpdates($uid);
		return $ret;
	}

        function getCheckinPoints($update_id, $listing_id, $timestamp){
                $operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->updates;
                $meta = $collection->findOne(array ("uid" => "".$update_id, "time" => "".$timestamp, "listing_id" => "".$listing_id), array('meta'));
                $resp_arr = array();
                if (isset ($meta['meta'])){
                        $resp_arr = $meta['meta']['score'];
                }
		else{
			$resp_arr['details'] = array();
                        $resp_arr['points'] = 0;
		}
                return $resp_arr;
        }

	function getWeeklyPointsForUser($uid){
		if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
		$total_points = 0;
		$now_time = time();
		$weekinms = 7*24*60*60;
		$weekagotime = time() - $weekinms;
		$weekagotime_mongo = new MongoDate($weekagotime);
                $collection = $this->dbHandle->updates;
		$points_data = $collection->find(array ("uid" => "".$uid, 'created_on' => array('$gte' => $weekagotime_mongo)));
		foreach ($points_data as $points){
			if (isset ($points['meta']['score']['points'])){
				$checkin_point = $points['meta']['score']['points'];
				$total_points += $checkin_point;
			}
		}
		return $total_points;
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
		'100days' => -8460000
             );

             return new MongoDate(time() + $_dates[$name]);
       }
		
}

?>
