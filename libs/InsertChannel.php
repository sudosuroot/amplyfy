<?php
require_once dirname(__FILE__).'/logger.php';

/*
 * Class to insert a channel.
 */
class InsertChannel {
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
	 * Function to create the channel.
	 */
	function createChannel($user_args) {
		$operation = false;
		if (count($user_args) < 1) {
			$this->logger->log("Too few arguements passed to create a user!",Zend_Log::INFO);
			return null;
		}
		if($this->dbHandle == null) {
			$this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
			return null;
		}
		$collection = $this->dbHandle->channels;
		if (!$this->isChannelAlreadyRegistered($user_args, $collection)) {
		try{
			$success = $collection->insert($user_args);
		}
		catch(MongoCursorException $e) {
 		   echo "Can't save the same record twice!\n";
		}
			if (isset($success)) {
				if ($success != false) {
					$operation = true;
				}
			}
		}
		return $operation;
	}

	/*
	 * Check if channel already exists.
	 */
	function isChannelAlreadyRegistered($user_args, $collection) {
		$id = "ch_id";
		$ans = false;
		$ch_id  = $user_args[$id];
		$channel = $collection->findOne(
				array($id=>$ch_id));
		if ($channel) {
			$ans = true;
			$this->logger->log("Channel already exists for $ch_id",Zend_Log::INFO);
		}
		return $ans;
	}
}



