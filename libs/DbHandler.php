<?php
require_once dirname(__FILE__).'/logger.php';
/*
 * Wrapper on top of MongoDB. Abstracted out as we may go for an Object pool later.
 */
class DbHandler {

	public static $DbHandle;
	private $logger;
	/*
	 * Default constructor.
	 */
	private function __construct() {
		$this->logger = Logger::getLogger();
		try 
		{
			$mongoHandle = new Mongo(); // connect
			self::$DbHandle = $mongoHandle->callisto;
		}
		catch ( MongoConnectionException $e ) 
		{
			$this->logger->log("Couldn\'t connect to mongodb, is the \"mongo\" process running?",Zend_Log::INFO);
			self::$DbHandle = null;
		}
	}

	/*
	 * Returns the DB connection.
	 */
	public static function getConnection() {
		if (self::$DbHandle == null){
			$DbHandle = new DbHandler(); 
		}
		return self::$DbHandle;
	} 
}
