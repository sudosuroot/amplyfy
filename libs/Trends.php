<?php
require_once dirname(__FILE__).'/logger.php';
require_once dirname(__FILE__).'/Updates.php';
/*
 * Class to insert a listing.
 */
class Trends {

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
	 * Function to create the listing.
	 */
	function createTrends($user_args) {
		$operation = false;
		if (count($user_args) < 2) {
			$this->logger->log("Too few arguements passed to create a lsiting!",Zend_Log::INFO);
			return null;
		}
		if($this->dbHandle == null) {
			$this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
			return null;
		}
		$collection = $this->dbHandle->trends;
		//columns to add.
		$country = $user_args['country'];
		$listings = $user_args['listings'];
		$channels = $user_args['channels'];
                $success = $collection->update(array("country" => $country), array("country" => $country, "listings" => $listings, "channels" => $channels), array("upsert" => true));
                if (isset($success)) {
                	if ($success != false) {
                        	$operation = true;
                        }
                }
		return $operation;
	}

	function GetAndUpsertTrends($country){
		$updates = new Updates;
		$trends = $updates->GetTopListings("27", $country);
		if (count($trends['top_channels'] > 0)){
			$upsert = array('country' => $country, 'listings' => $trends['top_listings'], 'channels' => $trends['top_channels']);	
			return $this->createTrends($upsert);
		}
		return false;
	}

	function GetChannelTrends($country){
		$ret_array = array();
		$collection = $this->dbHandle->trends;
		$channels = $collection->findOne(array("country" => $country), array('channels'));
		if (isset ($channels['channels'])){
			$ret_array = $channels['channels'];
		}
		return $ret_array;
	}

	function GetTopChannelIds($country){
		$channels = $this->GetChannelTrends($country);
		$ch_ids = array();
		if (count ($channels) > 0){
			foreach ($channels as $channel){
				$ch_ids[] = $channel["channel_id"];
			}
		}
		return $ch_ids;
	}

}
?>
