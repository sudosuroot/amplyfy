<?php
require_once dirname(__FILE__).'/logger.php';

/*
* Class to insert a show for mapping with listing ids.
*/
class ShowMapping {

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
function createShows($user_args) {
	$operation = false;
	if (!$this->ValidateArgs($user_args)) {
		$this->logger->log("Invalid arguments passed",Zend_Log::INFO);
		return null;
	}
	if($this->dbHandle == null) {
		$this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
		return null;
	}
	$collection = $this->dbHandle->shows;
	$flag = $this->CheckShowStatus($user_args, $collection);
	$this->logger->log("status = $flag",Zend_Log::INFO);
	if ($flag == 3){
		unset ($user_args['list_id']);
		$operation = $collection->insert($user_args);
	}
	else if ($flag == 2){
		$operation = $collection->update(array('list_name' => $user_args['list_name']), array('$push' => array('list_ids' => $user_args['list_id'])));
	}
	else{
		$operation = false;
	}
	return $operation;
}

private function ValidateArgs($user_args){
	$keys = array ('list_name', 'list_ids', 'list_id', 'meta_id');
	foreach ($user_args as $key => $value){
		if (!in_array ($key, $keys)){
			return false;
		}
	}
	if (!is_array ($user_args['list_ids'])){
		return false;
	}
	if (count ($user_args['list_ids']) == 0){
		return false;
	}
	return true;
}

private function CheckShowStatus($user_args, $collection){
	$status = 1;
	$listing_name = $user_args['list_name'];
	$listing_id = $user_args['list_id'];
	$listing_data = $collection->findOne(array('list_name' => $listing_name));
	if (isset($listing_data['list_ids'])){
		if (in_array($listing_id,$listing_data['list_ids'])){
			$status = 1; //repeat reject
		}
		else{
			$status = 2; //new id, update
		}
	}
	else{
		$status = 3; //new record, insert
	}
	return $status;
}


}

?>
