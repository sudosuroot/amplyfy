<?php
set_include_path('/usr/share/php/libzend-framework-php/');
require_once('Zend/Log.php');
require_once('Zend/Log/Writer/Stream.php');
require_once('Zend/Log/Formatter/Simple.php');
date_default_timezone_set('UTC');

class Logger {
	private static $logger = null;
	private static $initialized = false;
	private function __construct() {
	}

	private static function initialize(){
		if (self::$initialized) {
			return;
		}
		$logger = new Zend_Log();

		$writer = new Zend_Log_Writer_Stream('/var/log/amplyfy/amplyfyWSV4.log');
		$format = '%timestamp% %priorityName% (%priority%): %message%' . PHP_EOL;
		$formatter = new Zend_Log_Formatter_Simple($format);
		$writer->setFormatter($formatter);
		$logger->addWriter($writer);

		self::$logger = $logger;
		self::$initialized = true;
	}

	public static function getLogger(){
		self::initialize();
		return self::$logger;
	}
}
 
?>
