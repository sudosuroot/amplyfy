<?php 
require_once dirname(__FILE__).'/DbHandler.php';
require_once dirname(__FILE__).'/UserAnswers.php';

class QuestionsModel {
	private $dbHandle;
	private $questions_collection;
	private $timings_collection;
	private $logger;
	private $question = array();
	private $answers = array();
	private $correct_answers = array();

	function __construct(){
                $this->dbHandle = DbHandler::getConnection();
                $this->logger = Logger::getLogger();
		$this->questions_collection = $this->dbHandle->questions;
		$this->timings_collection = $this->dbHandle->timings;
	}

	private function SetQuestionParams($user_args){
		$this->question = $user_args['question'];
		$this->answers = $user_args['answers'];
		$this->correct_answers = $user_args['correct_answers'];
	}

	private function ValidateArgs($user_args){
		if (!isset ($user_args['question']) || !isset($user_args['question']['text'])){
			return false;
		}
		if (!isset ($user_args['answers']) || count ($user_args['answers']) < 4){
			return false;
		}
		if (!isset ($user_args['correct_answers']) || count ($user_args['correct_answers']) < 1){
			return false;
		}
		return true;
	}

	private function ConstructInsertArray($user_args){
		$insert_array = array();
		$insert_array['question'] = $user_args['question'];
		$insert_array['answers'] = $user_args['answers'];
		$insert_array['c_answers'] = $user_args['correct_answers'];
		return $insert_array;
	}

	public function setQuestions($user_args, $link = false, $show_details = array()){
		$question_id;
		$operation = false;
		if (! $this->ValidateArgs($user_args)){
			return false;
		}
		$question_insert_arr = $this->ConstructInsertArray($user_args);
		$question = array();
		if (isset ($user_args['question_id'])){
			$question = $this->getQuestionById($user_args['question_id']);
		}
		if (count ($question) != 0){
			$success = $this->questions_collection->update(array('_id' => new MongoId($user_args['question_id'])), $question_insert_arr);
			if ($success){
				$question_id = $question['q_id'];
				$operation = $question_id;
			}
		}
		else{
			$success = $this->questions_collection->insert($question_insert_arr);
			if ($success){
                                $question_id = $question_insert_arr['_id']->{'$id'};
                                $operation = $question_id;
                        }
		}

		if ($link && $operation && count($show_details) != 0){
			$linkq = $this->linkQuestion($operation, $show_details); 
			if ($linkq){
				return $operation;
			}
			else{
				return false;
			}
		}
		return $operation;
	}

	private function ValidateShowArgs($show_details){
		$args = array('show_id', 'start_time', 'start_offset', 'end_offset');
		if (count ($show_details) < 4){
			return false;
		}
		foreach ($show_details as $key => $value){
			if (!in_array($key, $args)){
				return false;
			}
		}
		return true;

	}

	public function linkQuestion($question_id, $show_details){
		$operation = false;
		if (!$this->ValidateShowArgs($show_details)){
			return $operation;
		}
		$show_id = $show_details['show_id'];
		$start_time = $show_details['start_time'];
		$start_offset = $show_details['start_offset'];
		$end_offset = $show_details['end_offset'];
		$question = array('id' => $question_id, 'start' => $start_offset, 'end' => $end_offset);
		$show_question = $this->timings_collection->findOne(array('listing_id' => intval($show_id), 'start' => new MongoDate($start_time)), array('questions'));
		if (isset ($show_question['questions'])){
			$qs = $show_question['questions'];
			$duplicate = false;
			$nqs = array();
			foreach ($qs as $q){
				if ($q['id'] == $question_id){
					$duplicate = true;
				}
				else{
					$nqs[] = $q;
				}
			}
			if ($duplicate){
				$nqs[] = $question;
				$operation = $this->timings_collection->update(array('listing_id' => intval($show_id), 'start' => new MongoDate($start_time)), array('$set' => array('questions' => $nqs)));   
			}
			else{
				$operation = $this->timings_collection->update(array('listing_id' => intval($show_id), 'start' => new MongoDate($start_time)), array('$push' => array('questions' => $question)));
			}
		}
		else{
			$operation = $this->timings_collection->update(array('listing_id' => intval($show_id), 'start' => new MongoDate($start_time)), array('$push' => array('questions' => $question)));
		}
		return $operation;
	}
	
	public function getQuestionsForShow($show_id, $start_time){
		$questions = array();
		$timings_data = $this->timings_collection->findOne(array('listing_id' => intval ($show_id), 'start' => new MongoDate($start_time)), array('questions'));
		if (isset ($timings_data['questions'])){
			$questions_data = $timings_data['questions'];
			foreach ($questions_data as $question_data){
				$tmpq = $this->getQuestionById($question_data['id']);
				$tmpq['start_offset'] = $question_data['start'];
				$tmpq['end_offset'] = $question_data['end'];
				$questions[] = $tmpq;
			}
		}
		return $questions;
	}

	public function getQuestionById($question_id, $details = array()){
		$question = array();
               	$question = $this->questions_collection->findOne(array('_id' => new MongoId($question_id)));
		if (count ($details) != 0 && isset ($question['_id'])){
			//validate user if answered.
			$answers = new UserAnswers();
			$user_args = array('uid' => $details['uid'], 'show_id' => $details['show_id'], 'start_time' => $details['start_time'], 'question_id' => $question_id); 
			if ($answers->isUserAnsweredQuestion($user_args)){
				$question['hasAnswered'] = true;
			}
			else{
				$question['hasAnswered'] = false;
			}
		}
		if (isset ($question['_id'])){
                        $question['q_id'] = $question['_id']->{'$id'};
                        unset ($question['_id']);
                }
                return $question;
	}


}

?>
