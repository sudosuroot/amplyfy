<?php 
require_once dirname(__FILE__)."/DbHandler.php";
require_once dirname(__FILE__).'/logger.php';
/*
   Basic Reco engine
*/

class RecoEngine {

	private $recos = array();
	private $stop_shows = array("FILLER", "TELESHOPPING", "GUTHY RENKER", "MOVIE IN FOCUS");
	private $message = array(
		'2' => "You like the dark side and we know it. Here's a recommendation just for YOU based on your viewing patterns",
		'3' => "Want to escape to a fantasy world of ghouls, demons and fairies? Here's a recommendation just for YOU based on your viewing patterns",
		'4' => "Bring out the Mozart or the Von Trapp in you? Here's a recommendation just for YOU based on your viewing patterns",
		'6' => "All work and no action? Here's a recommendation just for YOU based on your viewing patterns",
		'7' => "Laughter's your best therapy. Here's a recommendation just for YOU based on your viewing patterns",
		'8' => "Bring out the Sherlock Holmes in you. Here's a recommendation just for YOU based on your viewing patterns",
		'9' => "You drama queen! Here's a recommendation just for YOU based on your viewing patterns",
		'10' => "Real events on reel. Here's a recommendation just for YOU based on your viewing patterns",
		'11' => "Sucker for chick flicks? Here's a recommendation just for YOU based on your viewing patterns",
		'16' => "Bring out the gourmet chef in you. Here's a recommendation just for YOU based on your viewing patterns",
		'17' => "Like em edge of the seat thrillers. Here's one tailor-made for you!",
		'18' => "You legal beagle! Here's a recommendation just for YOU based on your viewing patterns",
		'22' => "Not all those who wander are lost. Here's a recommendation just for YOU based on your viewing patterns",
		'29' => "Enjoy a good whodunit? Here's a recommendation just for YOU based on your viewing patterns",
		'24' => "Bring out the fashionista in you. Here's a recommendation just for YOU based on your viewing patterns",
		'30' => "You cricket fanatic!",
		'31' => "You cricket fanatic!",
		'44' => "You hooligan! Welcome to match-day madness.",
		'46' => "Watch the giants of Europe battle it out for bragging rights.",
		'47' => "Messi or Ronaldo? Decide who's the best player in the world!",
		'32' => "Stop calling it soccer, will you?",
		'45' => "Alonso or Vettel? This one's going to the wire!",
		'33' => "New balls please!");

	function __construct(){
		$this->dbHandle = DbHandler::getConnection();
                $this->logger = Logger::getLogger();
		date_default_timezone_set('UTC');
	}

	private function GetCurrentTrendingShows(){
		$current_time = new MongoDate (time());
		$country = "IN_airtel";
		$timings_collection = $this->dbHandle->timings;	
		$current_trend = array();
		$shows = $timings_collection->find(array ("country" => $country, "start" => array('$lt' => $current_time), "stop" => array('$gte' => $current_time), 'view_count' => array('$exists' => true)));
		//$shows = $timings_collection->find(array ("country" => $country, "start" => array('$lt' => $current_time), "stop" => array('$gte' => $current_time)));
		foreach ($shows as $show){
			if ($show['view_count'] < 3){
				continue;
			}
			if (isset ($current_trend[$show['view_count']])){
				$new_count = intval($show['view_count']) + 1;
				$current_trend[$new_count] = $show;
			}
			else{
				$current_trend[$show['view_count']] = $show;
			}
		}
		$top_keys = array_keys ($current_trend);
		rsort ($top_keys);
		$top_counts = array_splice ($top_keys, 0, 3);
		foreach ($top_counts as $top){
			$present = false;
			foreach ($this->recos as $reco){
				if ($reco['listing_id'] == $top['listing_id']){
					$present = true;
					break;
				}
			}
			if (!$present){
				$current_trend[$top]['reason'] = 'Looks like this show is being watched by a lot of people NOW';
				$this->recos[] = $current_trend[$top];
			}
		}	
	}

	private function GetAmplyfyRecos(){
		$current_time = new MongoDate (time());
		$country = "IN_airtel";
                $timings_collection = $this->dbHandle->timings;
		$shows = $timings_collection->find(array ("country" => $country, "start" => array('$gte' => $current_time), 'reco' => array('$exists' => true)));
		foreach ($shows as $show){
			$this->recos[] = $show;
		}
	}
/*	
	private function GetUserTrendRecos($uid){
                $user_profile_collection = $this->dbHandle->user_profile;
                $user_data = $user_profile_collection->findOne(array('uid' => intval ($uid)));
                $country = "IN_airtel";
                if (isset ($user_data['genres']) && isset ($user_data['timings'])){
                        //has trends.
                        $timings_collection = $this->dbHandle->timings;
                        $genres = $user_data['genres'];
                        $timings = $user_data['timings'];
                        $best_hour = $timings[0];
                        $utc_best_hour = strtotime("".$best_hour.":01", time());
                        if ($utc_best_hour < time()){
                                //time passed, use next day.
                                        $utc_best_hour = strtotime("".$best_hour.":01", time() + 86400);
                        }
                        $utc_best_hour_mongo = new MongoDate($utc_best_hour);
                        $best_genre = $genres[0];
                        $shows = $timings_collection->find(array ("country" => $country, "start" => array('$lt' => $utc_best_hour_mongo), "stop" => array('$gte' => $utc_best_hour_mongo), 'genre' => "".$best_genre));
                        foreach ($shows as $show){
                                $show['reason'] = "We recommend you this show based on your TV viewing pattern we have noticed.";
                                $this->recos[] = $show;
                        }
                }
                else{
                        return false;
                }
        }
*/

	private function GetUserTrendRecos($uid){
		$user_profile_collection = $this->dbHandle->user_profile;
		$user_data = $user_profile_collection->findOne(array('uid' => intval ($uid)), array('gs'));
		$country = "IN_airtel";
		$now_time = time();
		$next_3_time = $now_time + 10800;
		$now_time_mongo = new MongoDate($now_time);
		$next_3_time_mongo = new MongoDate($next_3_time);
		if (isset ($user_data['gs'])){
			$genre_sgenres = $user_data['gs'];
			$gskeys = array_keys($genre_sgenres);
			$genres = array();
			foreach ($gskeys as $gs){
				$gsarr = explode("|", $gs);
				$genres[] = $gsarr[0];
			}
			$timings_collection = $this->dbHandle->timings;
			$now_next_shows = $timings_collection->find(array ('$or' => array( array("start" => array('$lt' => $now_time_mongo), "stop" => array('$gte' => $now_time_mongo), "country" => $country, "genre" => array('$in' => $genres)), array("start" => array('$gt' => $now_time_mongo), "stop" => array('$lte' => $next_3_time_mongo), "country" => $country, "genre" => array('$in' => $genres)))));
			$all_recos = array();
			$all_scores = array();
			$uniq_show_names = array();
			foreach ($now_next_shows as $nns){
				$genre;
				$score = 0;
				if (isset ($nns['genre'])){
					$genre = $nns['genre'];
				}
				$lname = $nns['listing_name'];
				$start_time = $nns['start']->sec;
				$stop_time = $nns['stop']->sec;
				$diff = $stop_time - $now_time;
				if (($genre == "3" || $genre == "15") && $now_time > $start_time && $diff < 3600){
					continue;
				}
				if ($now_time > $start_time && $diff < 900 || in_array($lname, $this->stop_shows) || isset ($uniq_show_names[$nns['listing_name']])){
					continue;
				}
				if (isset ($nns['meta']) && isset ($nns['meta']['sgenre'])){
					$subgenres = $nns['meta']['sgenre'];
					foreach ($subgenres as $sg){
						$key = "$genre|$sg";
						if (isset ($genre_sgenres[$key])){
							$score = round( $score + $genre_sgenres[$key], 2);
						}
					}
				}
				else{
					$key = "$genre|0";
					if (isset ($genre_sgenres[$key])){
						$score = $genre_sgenres[$key];
					}
				}
				if ($score == 0){
					continue;
				}
				$score_key = "".$nns['listing_id']."*".$key;
				$all_scores[$score_key] = $score;
				$all_recos[$nns['listing_id']] = $nns;
				$uniq_show_names[$nns['listing_name']] = true;
			}	
			arsort ($all_scores, SORT_NUMERIC);
			//$top_reco_ids = array_splice (array_keys ($all_scores), 0, 5);
			$top_reco_ids = array_keys ($all_scores);
			$this->logger->log("Total recos collected for user $uid is ".count($top_reco_ids),Zend_Log::INFO);
			$gs_hash = array();
			$ctr = 0;
			foreach ($top_reco_ids as $id){
				if ($ctr == 10){
					break;
				}
				$id_gs = explode ("*", $id);
				$gs = $id_gs[1];
				$lid = $id_gs[0];
				if (isset ($gs_hash[$gs])){
					$gs_hash[$gs]++;
					if ($gs_hash[$gs] < 3){
						$g_s = explode("|", $gs);
						$s = $g_s[1];
						if (isset ($this->message[$s])){
							$all_recos[$lid]['reason'] = $this->message[$s];
						}
						else{
							$all_recos[$lid]['reason'] = "Here's a recommendation just for YOU based on your viewing patterns";
						}
						$this->logger->log("Adding $ctr reco for g/s = $gs",Zend_Log::INFO);
						$this->recos[] = $all_recos[$lid];
						$ctr++;
					}
				}
				else{
					$g_s = explode("|", $gs);
					$s = $g_s[1];
					if (isset ($this->message[$s])){
						$all_recos[$lid]['reason'] = $this->message[$s];
					}
					else{
						$all_recos[$lid]['reason'] = "Here's a recommendation just for YOU based on your viewing patterns";
					}
					$this->logger->log("Adding first reco for g/s = $gs",Zend_Log::INFO);
					$this->recos[] = $all_recos[$lid];
					$gs_hash[$gs] = 1;
					$ctr++;
				}
			}
		}
		else{
			return false;
		}
	}

	public function GetRecosForUser($uid){
		$this->GetAmplyfyRecos();
		$this->GetCurrentTrendingShows();
		$this->GetUserTrendRecos($uid);
		$this->logger->log("Total recos served for user : $uid = ".count ($this->recos),Zend_Log::INFO);
		return $this->recos;
	}

}


?>
