<?php 
require_once dirname(__FILE__).'/DbHandler.php';

class UserAnswers {
	private $dbHandle;
	private $userans_collection;
	private $logger;

	function __construct(){
                $this->dbHandle = DbHandler::getConnection();
                $this->logger = Logger::getLogger();
		$this->userans_collection = $this->dbHandle->user_answers;
	}



	public function setAnswer($user_args){
		$operation = false;
		if ($this->isUserAnsweredQuestion($user_args)){
			return $operation;
		}
		$answer = $user_args['answer'];
		$corrects = array(1,2,3,4,5);
                $c_answers = explode (",", $answer);
                $c_answer = array();
                foreach ($c_answers as $c_a){
                        if (in_array(intval($c_a), $corrects)){
                                $c_answer[] = intval($c_a)-1;
                        }
                        else{
                                return false;
                        }
		}
		$answer_insert_arr = array('uid' => intval ($user_args['uid']), 's_time' => new MongoDate($user_args['start_time']), 'q_id' => $user_args['question_id'], 'show_id' => intval($user_args['show_id']), 'answer' => $c_answer, 'created_on' => new MongoDate($user_args['created_on']));
		$success = $this->userans_collection->insert($answer_insert_arr);
		return $success;
	}

	public function isUserAnsweredQuestion($user_args){
		$operation = false;
		$show_id = $user_args['show_id'];
		$start_time = new MongoDate($user_args['start_time']);
		$question_id = $user_args['question_id'];
		$uid = $user_args['uid'];
		$data = $this->userans_collection->findOne(array('uid' => intval ($uid), 'show_id' => intval($show_id), 's_time' => $start_time, 'q_id' => $question_id), array('answer'));
		if (isset ($data['answer'])){
			return true;
		}
		return false;
	}
}

?>
