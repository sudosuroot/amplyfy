<?php
require_once dirname(__FILE__)."/DbHandler.php";
require_once dirname(__FILE__).'/logger.php';
require_once dirname(__FILE__).'/Constants.php';

/*
 * Class to handle all of users profile. 
 */
class UserProfile{
	private $favChannels = array();
	private $favShows = array();
	private $location;
	private $favGenres = array();
	private $likedShows = array();
	private $likedChannels = array();
	private $likedGenres = array();
	private $checkinShows = array();
	private $checkinChannels = array();
	private $checkinGenres = array();
	private $activityShows = array();
	private $activityChannels  = array();
	private $activityGenres = array();

	private $dbHandle;
        private $userprofile_collection;
        private $logger;
	private $uid;

	function __construct($uid){
                $this->dbHandle = DbHandler::getConnection();
                $this->logger = Logger::getLogger();
                $this->userprofile_collection = $this->dbHandle->user_profile;
		$this->uid = intval ($uid);
        }

	public function setFavChannels($channels = array()){
		$new_fav_channels = array();
		$channels_collection = $this->dbHandle->channels;
		$channels_data = $channels_collection->find(array('country' => 'IN_airtel'), array('ch_id'));
		$channel_ids = array();
		foreach ($channels_data as $channel){
			$channel_ids[] = $channel['ch_id'];
		}
		$current_fav_channels = $this->getFavChannels();
		foreach ($channels as $ch){
			if (in_array($ch, $channel_ids) && !in_array($ch, $current_fav_channels)){
				$new_fav_channels[] = $ch; 
			}
		}	
		if (count ($new_fav_channels) != 0){
			$success = $this->userprofile_collection->update(array('uid' => $this->uid), array('$pushAll' => array('favc' => $new_fav_channels)), array("upsert" => true));
			if ($success){
				return true;
			}
		}
		return false;
	}

	public function removeFavChannel($channel){
		$cur_fav = $this->getFavChannels();
		$key = array_search($channel, $cur_fav);
		if($key!==false){
    			unset($cur_fav[$key]);
			$success = $this->userprofile_collection->update(array('uid' => $this->uid), array('$set' => array('favc' => array_values ($cur_fav))));
			if ($success){
				return true;
			}
		}
		return false;
	}

	public function likeShow($show){
		$shows_collection = $this->dbHandle->shows;
		$listings_collection = $this->dbHandle->listings;
		$user_liked_shows = $this->getLikedShows();
                if (in_array($show, $user_liked_shows)){
                        return false;
                }
		$show_data = $shows_collection->findOne(array('list_ids' => intval($show)), array('list_ids'));
		if (isset ($show_data['list_ids'])){
			$shows = $show_data['list_ids'];
			$listing_update_status = $listings_collection->update(array('listing_id' => array('$in' => $shows)), array('$inc' => array('like' => 1)), array('multiple' => true));
			//$listing_update_status = $listings_collection->update(array('listing_id' => intval($show)), array('$inc' => array('like' => 1)));
			if ($listing_update_status){
				$success = $this->userprofile_collection->update(array('uid' => $this->uid), array('$push' => array('favs' => intval($show))), array("upsert" => true));
				if ($success){
					$success = $this->IncrementGenreSubGenreTrends($show);
					$this->logger->log("Profile updated for user $this->uid after the like",Zend_Log::INFO);
					$client= new GearmanClient();
                			$client->addServer();
					if (isset ($show_data['meta_id'])){
						$show = $show_data['meta_id'];
					}
					$user_args = array('uid' => $this->uid, 'listing_id' => $show, 'created_on' => time());
					$notify_job = $client->doBackground("SendShowLikeNotificationsV4", json_encode ($user_args));
					$this->logger->log("show like notifiy job id = $notify_job", Zend_Log::INFO);
				}
				return $success;
			}
		}
		return false;
	}

	public function unlikeShow($show){
		$user_liked_shows = $this->getLikedShows(false);
		$key = array_search($show, $user_liked_shows);
		if($key!==false){
                        unset($user_liked_shows[$key]);
                        $success = $this->userprofile_collection->update(array('uid' => $this->uid), array('$set' => array('favs' => array_values ($user_liked_shows))));
                        if ($success){
                                return true;
                        }
                }
                return false;
	}		

	public function getLikedShows($similar = true){
		$uid = $this->uid;
                $user_fav_shows = $this->userprofile_collection->findOne(array('uid' => $uid), array('favs'));
                if (isset ($user_fav_shows['favs']) && count ($user_fav_shows['favs']) != 0){
			$fav_shows = $user_fav_shows['favs'];
			if ($similar){
				$shows_collection = $this->dbHandle->shows;
				$shows_data = $shows_collection->find(array('list_ids' => array('$in' => $fav_shows)), array('list_ids'));
				$expanded_shows = array();
				foreach ($shows_data as $shows){
					$expanded_shows = array_merge ($expanded_shows, $shows['list_ids']);
				}
                        	$this->favShows = array_unique($expanded_shows);
			}
			else{
				$this->favShows = $fav_shows;
			}
                }
                return $this->favShows;   
	}

	private function getFavChannels(){
		$uid = $this->uid;
                $user_fav_channels = $this->userprofile_collection->findOne(array('uid' => $uid), array('favc'));
		if (isset ($user_fav_channels['favc'])){
			if (count ($user_fav_channels['favc']) != 0){
				$this->favChannels = $user_fav_channels['favc'];
			}
		}
		return $this->favChannels;
	}

	public function getFavCurrentPlayingChannels(){
		$user_fav_nowplaying = array();
		date_default_timezone_set('UTC');
		$time = new MongoDate(time());
		$user_fav_channel = $this->getFavChannels();
		$timings_collection = $this->dbHandle->timings;
		foreach ($user_fav_channel as $channel){
			$ch_id = $channel;
                        $listings = $timings_collection->findOne(array ("country" => "IN_airtel", "ch_id" => "".$ch_id, "start" => array('$lt' => $time), "stop" => array('$gte' => $time)));
                        if ($listings){
                        	$user_fav_nowplaying[] = $listings;
                        }
		}
		return $user_fav_nowplaying;
	}


	public function IncrementGenreSubGenreTrends($listing_id){
		$uid = $this->uid;
		$genres = array();
		$users_profile_collection = $this->dbHandle->user_profile;
		$shows_collection = $this->dbHandle->shows;
		$listings_collection = $this->dbHandle->listings;
		$channels_collection = $this->dbHandle->channels;
		$profile_data = $users_profile_collection->findOne(array('uid' => intval($uid)), array('gs'));
		if (isset ($profile_data['gs'])){
			$genres = $profile_data['gs'];
		}
		$show_data = $shows_collection->findOne(array('list_ids' => intval($listing_id)), array('meta_id'));
		$show_meta_id = $listing_id;
                if (isset ($show_data['meta_id'])){
	                $show_meta_id = $show_data['meta_id'];
                }
		$listings_data = $listings_collection->findOne(array('listing_id' => intval($show_meta_id)), array('meta', 'ch_id'));
		if (isset ($listings_data['meta'])){
			$meta = $listings_data['meta'];
		}
		$ch_id = null;
		if (isset ($listings_data['ch_id'])){
			$ch_id = $listings_data['ch_id'];
		}
		if (is_null ($ch_id)){
			return false;
		}
		$genre = '';
                $key;
                if (isset ($meta['genre'])){
                	$genre = $meta['genre'];
                }
                else{
                        $channel_data = $channels_collection->findOne(array('ch_id' => $ch_id), array('genre'));
                        if (isset ($channel_data['genre'])){
                	        $genre = $channel_data['genre'];
                        }
                }
                if (isset ($meta['sgenre'])){
                	$sgenre = $meta['sgenre'];
			error_log (var_export ($sgenre, true));
                        foreach ($sgenre as $sg){
                        	$key = $genre."|".$sg;
                                if (isset ($genres[$key])){
                                	$genres[$key] = round ($genres[$key] + genreAndSubgenre, 2);
                                }
                                else{
                                        $genres[$key] = genreAndSubgenre;
                                }
                        }
                }
                else{
                	$key = "$genre|0";
                        if (isset ($genres[$key])){
                        	$genres[$key] = round ($genres[$key] + genre, 2);
                        }
                        else{
                                $genres[$key] = genre;
                        }
               	}
		$trends = array('gs' => $genres);
                return ($this->setUserTrends($trends));
	}

	private function CreateChannelHash(&$channel_hash){
		$channels_collection = $this->dbHandle->channels;
		$channels_data = $channels_collection->find();
                foreach ($channels_data as $ch){
                        if (isset ($ch['genre'])){
                                $channel_hash[$ch['ch_id']]['genre'] = $ch['genre'];
                        }
                }
	}

	public function ComputeUserCheckinTrends(){
		$channels = array();
		$genres = array();
		$shows = array();
		$sgenres = array();
		//$timing_range = array();
		$listing_ids = array();
		$uid = $this->uid;
		$checkin_collection = $this->dbHandle->updates;
		$channels_collection = $this->dbHandle->channels;
		$shows_collection = $this->dbHandle->shows;
		$listings_collection = $this->dbHandle->listings;
		$users_profile_collection = $this->dbHandle->user_profile;
		$user_checkin_data = $checkin_collection->find(array('uid' => "".$uid), array('listing_id', 'ch_id', 'created_on'));
		$user_profile_data = $users_profile_collection->findOne(array('uid' => intval($uid)), array('favs'));

		$ch_hash = array();
		$this->CreateChannelHash($ch_hash);
		if (isset ($user_profile_data['favs'])){
			$listing_ids = $user_profile_data['favs'];
		}
		//$time_range = new TimeRange();
		foreach ($user_checkin_data as $record){
			$ch_id = $record['ch_id'];
			//$time = $record['created_on'];
			//$time_range->SetTime($time);
			$listing_id = intval ($record['listing_id']);
			$listing_ids[] = $listing_id;
			/*
			if (isset ($channels[$ch_id])){
                                $channels[$ch_id]++;
                        }
                        else{
                                $channels[$ch_id] = 1;
                        }
			*/
		}
		foreach ($listing_ids as $id){
			$show_data = $shows_collection->findOne(array('list_ids' => $id), array('meta_id'));
			$show_meta_id = $id;
			if (isset ($show_data['meta_id'])){
				$show_meta_id = $show_data['meta_id'];
			}
			if (isset ($shows[$show_meta_id])){
				$shows[$show_meta_id]++;
			}
			else{
				$shows[$show_meta_id] = 1;
			}
		}
		$show_ids = array_keys ($shows);
		$ch_ids = array_keys ($channels);
		$listings_data = $listings_collection->find(array('listing_id' => array('$in' => $show_ids)), array('meta', 'ch_id'));
		foreach ($listings_data as $record){
			if (isset ($record['meta'])){
				$meta = $record['meta'];
			}
			$ch_id = $record['ch_id'];
			$genre = '';
			$key;
			if (isset ($meta['genre'])){
				$genre = $meta['genre'];
			}
			else{
				$genre = $ch_hash[$ch_id]['genre'];
			}
			if (isset ($meta['sgenre'])){
				$sgenre = $meta['sgenre'];
				foreach ($sgenre as $sg){
					$key = $genre."|".$sg;
					if (isset ($genres[$key])){
						$genres[$key] = round ($genres[$key] + genreAndSubgenre, 2);
                        		}
                        		else{
                                		$genres[$key] = genreAndSubgenre;
                        		}
				}
			}
			else{
				$key = "$genre|0";
				if (isset ($genres[$key])){
                                	$genres[$key] = round ($genres[$key] + genre, 2);
                        	}
                        	else{
                                	$genres[$key] = genre;
                        	}
			}
		}

		//$timings = $time_range->GetTimeRange();
		//arsort ($channels, SORT_NUMERIC);
		//arsort ($genres, SORT_NUMERIC);	
		//arsort ($timings, SORT_NUMERIC);
		//$trends = array('channels' => array_splice (array_keys($channels), 0, 5), 'genres' => array_splice (array_keys($genres), 0, 5), 'timings' => array_splice (array_keys($timings), 0, 5));
		//$trends = array('channels' => array_splice (array_keys($channels), 0, 5), 'genres' => array_splice (array_keys($genres), 0, 5));
		$trends = array('gs' => $genres);
		//var_dump ($trends);
		return ($this->setUserTrends($trends));
	}

	private function setUserTrends($trends){
		$success = $this->userprofile_collection->update(array('uid' => $this->uid), array('$set' => $trends), array("upsert" => true));
		return $success;
	}

}

class TimeRange {       
	private $timings_hash = array();
        function __construct(){
        	date_default_timezone_set('UTC');
                for ($i = 0; $i < 24; $i++){
                	if ($i < 10){
                        	$this->timings_hash[intval("0".$i)] = 0;
                        }             
                        else{
                                $this->timings_hash[$i] = 0;
                        }
              	}                     
    	}                             
                                      
        public function SetTime($time){
  		$time_sec = $time->sec;
                $hour = date ('H', $time_sec);
                $this->timings_hash[intval($hour)]++;
        }

        public function GetTimeRange(){
                $timh = $this->timings_hash;
		foreach ($timh as $key => $val){
			if ($val == 0){
				unset ($timh[$key]);
			}
		}
		return $timh;
        }
}

?>
