<?php
require_once dirname(__FILE__)."/DbHandler.php";
require_once dirname(__FILE__).'/logger.php';
/*
 * Class to set/get Users points. 
 */
class Points {

        private $dbHandle;
        private $logger;
	private $points;
	private $connection;
        /*
         * Default constructor.
         */
        function __construct() {
                $this->dbHandle = DbHandler::getConnection();
                $this->logger = Logger::getLogger();
		$this->connection = $this->dbHandle->users;
        }


	/*
	 * Get the users total points
	 * @params $uid
	 * @return int points or false on failure
	 */
	function getPoints($uid){
		$points = false;
		$data = $this->connection->findOne (array('uid' => intval ($uid)), array('points'));
		if (!isset($data['points'])){
			 $this->logger->log("points not found for user : $uid, please ask him to checkin first",Zend_Log::INFO);
			return $points;
		}
		$points = $data['points'];
		$this->logger->log("returning points : $points for user : $uid",Zend_Log::INFO);	
		return $data['points'];
	}


	function getTimestamp($uid){
		$ts = false;
                $data = $this->connection->findOne (array('uid' => intval ($uid)), array('ts'));
                if (!isset($data['ts'])){
                         $this->logger->log("ts not found for user : $uid, please ask him to checkin first",Zend_Log::INFO);
                        return $ts;
                }
                $ts = $data['ts'];
                $this->logger->log("returning ts : $ts for user : $uid",Zend_Log::INFO);
                return $ts;
	}

	function getHighScore($uid){
		$score = 0;
		$data = $this->connection->findOne (array('uid' => intval ($uid)), array('high'));
		if (isset ($data['high'])){
			$score = $data['high'];
			$this->logger->log("high score set, return $score for user $uid ",Zend_Log::INFO);
		}
		return $score;
	}

	/*
	 * Set points for a user.
	 * @params uid, points
	 * @return true/false
	 */
	function setPoints($uid, $points, $ts){
		$operation = false;
		$current_points = $this->getPoints($uid);
		$current_ts = $this->getTimestamp($uid);
		$ret_points = false;
		if (! $current_points || ! $current_ts){
			$operation = $this->connection->update(array('uid' => intval ($uid)), array('$set' => array('points' => $points, 'ts' => intval ($ts))));
			if ($operation){
				return $points;
			}
			else{
				return false;
			}
		}
		else{
			$diff_days = floor((intval($ts) - intval($current_ts)));
			$this->logger->log("secs diff = $diff_days, $current_ts  $ts, $points, $uid", Zend_Log::INFO);
			if ($diff_days < 604800){
				$added_points = $current_points + $points;
				$operation = $this->connection->update(array('uid' => intval ($uid)), array('$set' => array('points' => $added_points)));
				$ret_points = $added_points;
			}
			else{
				if ($this->getHighScore($uid) < $current_points){
					//$operation = $this->connection->update(array('uid' => intval ($uid)), array('$set' => array('points' => $points, 'ts' => intval ($ts)), 'high' => $current_points));
					$operation = $this->connection->update(array('uid' => intval ($uid)), array('$set' => array('points' => $points, 'ts' => intval ($ts), 'high' => $current_points)));
					$this->logger->log("setting high score $current_points for uid $uid", Zend_Log::INFO);
				}
				else{
					$operation = $this->connection->update(array('uid' => intval ($uid)), array('$set' => array('points' => $points, 'ts' => intval ($ts))));
				}
				$ret_points = $points;
			}
                        if ($operation){
                                return $ret_points;
                        }
                        else{
                                return false;
                        }
		}
	}

}
?>
