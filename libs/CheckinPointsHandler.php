<?php

require_once dirname(__FILE__).'/DbHandler.php';
require_once dirname(__FILE__).'/Friends.php';
require_once dirname(__FILE__).'/Constants.php';

class CheckinPointsHandler {
	private $dbHandle;
	private $uid;
	private $fbid;
	private $listing_id;
	private $time;
	private $country;
	private $timestamp;
	private $ch_id;
	private $friend_fbids_watchin;
	private $channel_checkin_count;
	private $listing_checkin_count;

	function __construct($user_args){
		$this->dbHandle = DbHandler::getConnection();
                $this->logger = Logger::getLogger();
		$this->fbid = $user_args['fbid'];
		$this->uid = $user_args['uid'];
		$this->listing_id = $user_args['listing_id'];
		$this->ch_id = $user_args['ch_id'];
		$this->time = $user_args['time'];
		$this->country = $user_args['country'];
		$this->timestamp = $user_args['created_on'];
		$this->friend_fbids_watchin = null;
	}

	private function isFirstCheckin(){
		$updates_connection = $this->dbHandle->updates;
		//check if the user has any checkins.
		$count = $updates_connection->find(array('uid' => "".$this->uid))->count();
		$this->logger->log("total checkins for $this->uid = $count",Zend_Log::INFO);
		if ($count != 0){
			$this->logger->log("not first checkin",Zend_Log::INFO);
			return false;
		}
		$this->logger->log("yes first checkin",Zend_Log::INFO);
		return true;
	}

	private function isSpecialShow(){
		$timings_collection = $this->dbHandle->timings;
		$data = $timings_collection->findOne(array('listing_id' => intval($this->listing_id), 'start' => new MongoDate($this->time)), array('special_show', 'fbids_watching'));
		if (isset ($data['fbids_watching'])){
			$this->friend_fbids_watchin = $data['fbids_watching'];
		}
		if (isset ($data['special_show'])){
			if ($data['special_show']){
				return true;
			}
			else{
				return false;
			}
		}	
		else{
			return false;
		}
	}

	/*
	 * Check if you are checkin into a show your friends are watching.
	 * @return false or array of friend fbids watching.
	 */
	private function isCheckinWithFriend(){
		if (is_null ($this->friend_fbids_watchin)){
			return false;
		}
		else {
			$friend = new Friends();
			$user_friends = $friend->getFriends($this->uid, false);
			$friend_fbids = array();
			//$friends_watching = array();
			foreach ($user_friends as $user_fbid){
				$friend_fbids[] = $user_fbid['fbid'];
			}
			foreach ($this->friend_fbids_watchin as $watching_fbid){
				if (in_array ($watching_fbid, $friend_fbids)){
					return true;
				}
			}
		}
		return false;
	}

        private function setChannelCheckinCount(){
                $updates_connection = $this->dbHandle->updates;
                $count = $updates_connection->find(array('uid' => "".$this->uid, 'ch_id' => $this->ch_id))->count();
                $this->logger->log("checkin count for user for $this->ch_id is $count",Zend_Log::INFO);
                $this->channel_checkin_count = $count;

        }

	
	private function getChannelCheckinCount(){
		return $this->channel_checkin_count;

	}


        private function setListingCheckinCount(){
                $updates_connection = $this->dbHandle->updates;
                $count = $updates_connection->find(array('uid' => "".$this->uid, 'listing_id' => $this->listing_id))->count();
                $this->logger->log("checkin count for user for $this->listing_id is $count",Zend_Log::INFO);
                $this->listing_checkin_count = $count;
        }

	private function getListingCheckinCount(){
                return $this->listing_checkin_count;	
	}

	private function isChannelBonusCheckin(){
		if ($this->getChannelCheckinCount() == 9){
			return true;
		}
		return false;
	}

	private function isListingBonusCheckin(){
                if ($this->getListingCheckinCount() == 4){
                        return true;
                }
                return false;
        }

	private function isChannelFirstCheckin(){
		if ($this->getChannelCheckinCount() == 0){
			return true;
		}
		return false;
	}

	private function isListingFirstCheckin(){
		if ($this->getListingCheckinCount() == 0){
                        return true;
                }
                return false;
	}

	//check for basic checkin points abuse.
	//we do not award points for checkins which are close in time. say 30 mins.
	public function isValidCheckin(){
		$users_connection = $this->dbHandle->users;
		$data = $users_connection->findOne(array('uid' => intval($this->uid)));
		if (!isset ($data['last_update']) || $data['last_update'] == null){
			//first update so is valid.
			return true;
		}
		//$last_update_time = $data['last_update']['created_on']->sec;
		if (isset ($data['last_update']['listing'])){
			$last_checkedin_show = $data['last_update']['listing']['listing_id'];
			$last_checkedin_show_start = $data['last_update']['listing']['start']->sec;
		}
		else{
			return true;
		}
		if (($this->listing_id == $last_checkedin_show) && ($this->time == $last_checkedin_show_start)){
			$this->logger->log("Checked into the same show again. Return false",Zend_Log::INFO);
			return false;
		}
		/*
		$current_update_time = $this->timestamp;
		$diff_secs = $current_update_time - $last_update_time;
		$this->logger->log("The diff secs = $diff_secs",Zend_Log::INFO);
		$threshold = 30*60; //30 mins 
		if ($diff_secs > $threshold){
			return true;
		}
		//within 30 mins not valid for points.
		return false;
		*/
		return true;
	}

	//returns an array with the types listed as constants.
	public function getCheckintype(){
		$this->setChannelCheckinCount();
		$this->setListingCheckinCount();
		$return = array();
		$return['details'] = array();
		$return['points'] = 0;
		$return['details'][] = array ('type' => nCheckin, 'msg' => 'Nice checkin!', 'points' => nCheckinPoints); //add normal checkin as type as default
		$return['points'] += nCheckinPoints;
		if ($this->isFirstCheckin()){
			$return['details'][] = array('type' => firstCheckin, 'msg' => 'Its your first checkin! Congrats!', 'points' => firstCheckinPoints);
			$return['points'] += firstCheckinPoints;
		}
		if ($this->isSpecialShow()){
			$return['details'][] = array('type' => sCheckin, 'msg' => 'You football buff! This one\'s going to be a cracker!', 'points' => sCheckinPoints);
			$return['points'] += sCheckinPoints;
		}
		if ($this->isChannelBonusCheckin()){
			$return['details'][] = array('type' => bonusChannelCheckin, 'msg' => 'Congrats! This must be your favorite channel! You have checked in to this channel 10 times', 'points' => bonusChannelCheckinPoints);
			$return['points'] += bonusChannelCheckinPoints;
		}
		if ($this->isListingBonusCheckin()){
			$return['details'][] = array('type' => bonusListingCheckin, 'msg' => 'Congrats! This must be your favorite show! You have checked into this show 5 times', 'points' => bonusListingCheckinPoints);
			$return['points'] += bonusListingCheckinPoints;
		}
		if ($this->isCheckinWithFriend()){
			$return['details'][] = array('type' => checkinWithFriend, 'msg' => 'Share interests? You just checked in to a show your friend is watching!', 'points' => checkinWithFriendPoints);
                        $return['points'] += checkinWithFriendPoints;			
		}
		if ($this->isChannelFirstCheckin()){
			$return['details'][] = array('type' => firstChannelCheckin, 'msg' => 'Congrats! Your first checkin to this channel', 'points' => firstChannelCheckinPoints); 
                        $return['points'] += firstChannelCheckinPoints;
		}
		if ($this->isListingFirstCheckin()){                        
			$return['details'][] = array('type' => firstListingCheckin, 'msg' => 'Congrats! Your first checkin to this show', 'points' => firstListingCheckinPoints);   
                        $return['points'] += firstListingCheckinPoints;
                }
		return $return;
	}

} 
