<?php
require_once dirname(__FILE__)."/DbHandler.php";
require_once dirname(__FILE__).'/Updates.php';
require_once dirname(__FILE__).'/logger.php';
/*
 * Class to insert/ delete Conversations. 
 */
class Conversations {
	
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
	 * Function to create a thread for a conversation.
	 * @params : user_args - (uid, fbid, listing_id, array (fbids), comment) 
	 */
	function createConversation($user_args) {
		$operation = false;
		if (count($user_args) < 4) {
			$this->logger->log("Too few arguements passed to create a conversation",Zend_Log::INFO);
			return null;
		}
		if($this->dbHandle == null) {
			$this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
			return null;
		}
		$collection = $this->dbHandle->conversations;
		$user_collection = $this->dbHandle->users;
		$user_info = $user_collection->findOne(array('uid' => intval ($user_args['uid'])), array('fbid', 'name'));
		if (is_null ($user_info)){
			$this->logger->log ("user not found",Zend_Log::INFO);
			return null;
		}
		$updates = new Updates();
		$search = new SearchUser();
                $client= new GearmanClient();
                $client->addServer();
		$listing_details = $updates->getListingDetails($user_args['listing_id'], $user_args['country']);
		$conversation = array();
		$pFbids = $user_args['fbids'];
		$created_on = new MongoDate($user_args['created_on']);
		$pFbids[] =  $user_info['fbid'];//adding myself to the thread
		array_push ($conversation, array('fullname' => $user_info['name'], 'message' => $user_args['message'], 'created_on' => $created_on));
		$conversation_insert_arr = array ('listing_id' => $user_args['listing_id'], 'listing_details' => $listing_details, 'participating_fbids' => $pFbids, 'conversation' => $conversation, 'created_on' => $created_on);
		$success = $collection->insert($conversation_insert_arr);
		$thread_id;
		if (isset($success)) {
			if ($success != false) {
                                //Update the timings table with a count and the fbid of the user.
				$thread_id = $conversation_insert_arr['_id']->{'$id'};
				if ($this->addConversationsAgainstUsers($thread_id, $pFbids)){
                                       	$operation = $thread_id;
					$user_args['thread_id'] = $thread_id;
					$notify_job = $client->doBackground("SendConversationNotificationsV4", json_encode ($user_args));
					$this->logger->log("SendConversationNotifications job id = $notify_job",Zend_Log::INFO);
				}

			}
		}
		return $operation;
	}

	function insertMessage($user_args){
                $operation = false;
                if (count($user_args) < 3) {
                        $this->logger->log("Too few arguements passed to insert a comment",Zend_Log::INFO);
                        return null;
                }
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
		$message = $user_args['message'];
		$fbid = $user_args['fbid'];
		$thread_id = $user_args['thread_id'];
		$user_collection = $this->dbHandle->users;
		$client= new GearmanClient();
                $client->addServer();
                $user_info = $user_collection->findOne(array('fbid' => $user_args['fbid']), array('name'));
		if (is_null ($user_info)){
                        $this->logger->log ("user not found",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->conversations;
		$data = $collection->findOne (array('_id' => new MongoID($thread_id)), array('participating_fbids'));
		if (is_null($data)){
			$this->logger->log ("thread $thread_id does not exist\n",Zend_Log::INFO);	
			return null;
		}

		if (in_array($fbid, $data['participating_fbids'])){
                	$conversation =  array('fullname' => $user_info['name'], 'message' => $message, 'created_on' => $user_args['created_on']);
			$status = $collection->update(array('_id' => new MongoID($thread_id)), array('$push' => array('conversation' => $conversation), '$set' => array('created_on' => $user_args['created_on'])));
			if ($status){
				//add the below two params for push notif async
				$pushFbids = array_diff ($data['participating_fbids'], array($fbid));//remove my fbid
				$user_args['name'] = $user_info['name'];
				$user_args['fbids'] = $pushFbids;
				$operation = true;
				$notify_job = $client->doBackground("SendMessageNotificationsV4", json_encode ($user_args));
                                $this->logger->log("SendConversationNotifications job id = $notify_job",Zend_Log::INFO);
			}
		}

		else{
			$this->logger->log ("fbid $fbid is not a part of this thread\n",Zend_Log::INFO);
			$operation = false;
		}
		return $operation;
	}

	/*
	 * Fucntion to delete the comment.
	 */
	function deleteConversation($thread_id) {
		$operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->conversations;
		$this->logger->log ("deleting id: $thread_id",Zend_Log::INFO);
		$operation = $collection->remove(array("_id" => new MongoID($thread_id)), true);
		return $operation;
	}

	function addFriendsToConversation($user_args){
		$operation = false;
		if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
		$thread_id = $user_args['thread_id'];
		$fbids = $user_args['fbids'];
                $collection = $this->dbHandle->conversations;
		$data = $collection->findOne(array('_id' => new MongoID($thread_id)), array('participating_fbids'));
		if (is_null ($data)){
			$this->logger->log ("thread id : $thread_id not found\n",Zend_Log::INFO);
			return $operation;
		}
		$pFbids = array();
		if (isset($data['participating_fbids'])){
			$pFbids = $data['participating_fbids'];
		}
		foreach ($fbids as $fbid){
			if (!in_array($fbid, $pFbids)){
				$pFbids[] = $fbid;
			}
		}
		$status = $collection->update(array('_id' => new MongoID($thread_id)), array('$set' => array('participating_fbids' => $pFbids)));
		if ($status){
			if ($this->addConversationsAgainstUsers($thread_id, $pFbids)){
				$operation = true;
			}
		}
		return $operation;
	}

	function addConversationsAgainstUsers($thread_id, $fbids = array()){
		$operation = false;
		if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->users;
		$nFbids = array();
		foreach ($fbids as $fbid){
			$data = $collection->findOne(array('fbid' => $fbid), array('users_threads'));
			if (isset ($data['users_threads'])){
				if (!in_array($thread_id, $data['users_threads'])){
					//$nFbids[] = $fbid;	
					$user_update = $collection->update (array ("fbid" => "".$fbid), array('$push' => array("users_threads" => $thread_id)));
					if ($user_update){
                        			$operation = true;
                			}               
				}
			}
			else{
				//$nFbids[] = $fbid;
				$user_update = $collection->update (array ("fbid" => $fbid), array('$push' => array("users_threads" => $thread_id)));
                                if ($user_update){
                                	$operation = true;
                               	}  
			}
		}
//		$user_update = $collection->update (array ("fbid" => array('$in', array($nFbids))), array('$push' => array("users_threads" => $thread_id)));
		return $operation;
	}

	function getUserThreads($uid, $listing_id = null){
		$operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
		$ret_arr = array();
                $collection = $this->dbHandle->users;
		$data = $collection->findOne(array('uid' => intval ($uid)), array('users_threads'));
		if (is_null($data)){
			$this->logger->log ("uid $uid does not exist\n",Zend_Log::INFO);
			return $operation;
		}
		if (!isset($data['users_threads'])){
			$this->logger->log ("No threads for user $uid",Zend_Log::INFO);
			return $ret_arr;
		}
		//he has threads. 
		$conversation_collection = $this->dbHandle->conversations;
		foreach ($data['users_threads'] as $thread){
			$thread_ids[] = new MongoID($thread);
		} 
		if (is_null ($listing_id)){
			$thread_data = $conversation_collection->find(array('_id' => array('$in' => $thread_ids)))->sort(array('created_on' => -1));;
		}
		else{
			$thread_data = $conversation_collection->find(array('_id' => array('$in' => $thread_ids), 'listing_id' => $listing_id))->sort(array('created_on' => -1));
		}
		foreach ($thread_data as $thread){
			$conversation = $thread['conversation'];
			$last_comment = end($conversation);
			$fbids = $thread['participating_fbids'];
			$listing_id = $thread['listing_id'];
			$listing_details = $thread['listing_details'];
			$created_on = $thread['created_on'];
			$ret_arr[] = array('thread_id' => $thread['_id']->{'$id'}, 'participating_fbids' => $fbids, 'last_comment' => $last_comment, 'listing_id' => $listing_id, 'listing_details' => $listing_details, 'created_on' => $created_on);
		}
		return $ret_arr;
		
	}

	function getThread($thread_id){
		$operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
		$collection = $this->dbHandle->conversations;
		$thread = $collection->findOne(array('_id' => new MongoID($thread_id)));
		if (is_null ($thread)){
			$this->logger->log ("thread $thread_id not present\n",Zend_Log::INFO);
			return $operation;
		}
		$ret_arr = array('thread_id' => $thread['_id']->{'$id'}, 'participating_fbids' => $thread['participating_fbids'], 'listing_id' => $thread['listing_id'], 'listing_details' => $thread['listing_details'], 'created_on' => $thread['created_on'], 'conversation' => $thread['conversation']);
		return $ret_arr;
	}	

	function getThreadsForListing($listing_id){
		$operation = false;
                if($this->dbHandle == null) {
                        $this->logger->log("Error in fetching DB handler.",Zend_Log::INFO);
                        return null;
                }
                $collection = $this->dbHandle->conversations;
		$resp_arr = array();
		$data = $collection->find(array('listing_id' => $listing_id))->sort(array('_id' => 0));;
		if (is_null ($data)){
			$this->logger->log ("no thread for listing : $listing_id",Zend_Log::INFO);
			return $operation;
		}
		foreach ($data as $thread){
			$conversation = $thread['conversation'];
                        $last_comment = end($conversation);
                        $fbids = $thread['participating_fbids'];
                        $listing_id = $thread['listing_id'];
			$created_on = $thread['created_on'];
			$listing_details = ((isset ($thread['listing_details'])) ? $thread['listing_details'] : null);
                        $resp_arr[] = array('thread_id' => $thread['_id']->{'$id'}, 'participating_fbids' => $fbids, 'last_comment' => $last_comment, 'listing_id' => $listing_id, 'listing_details' => $listing_details, 'created_on' => $created_on);
		}
		return $resp_arr;
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
		'1000days' => -84600000
             );

             $this->logger->log (time() + $_dates[$name]);
             return new MongoDate(time() + $_dates[$name]);
       }
}

?>
