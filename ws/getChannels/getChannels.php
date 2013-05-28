<?php
require_once '/usr/include/php/callistoV4/Response.php';
require_once '/usr/include/php/callistoV4/DbHandler.php';
/**
 * Get Friends. 
 *
 * @uri /GetChannels
 */
class GetChannelsResource extends Resource {
	private $dbHandle;
	/**
	 * Handle a POST request for this resource
	 * @param Request request
	 * @return Response
	 */
	function get($request) {
		$response = new Response($request);
		$return_arr = array();
		$message;
		$this->dbHandle = DbHandler::getConnection();
		$collection = $this->dbHandle->channels;
		$channels_data = $collection->find()->sort(array('ch_name' => 1));
		if ($channels_data) {
			foreach ($channels_data as $channel){
				if (!isset ($channel['genre'])){
					continue;
				}
				$genre = $channel['genre'];
				if ($genre == "0"){
					continue;
				}
				$return_arr[] = $channel;
			}
			$message = $return_arr;
			$response->code = Response::OK;
		} 
		else {
			$message = array('msg' => 'Error while trying to fetch channels');
			$response->code = Response::INTERNALSERVERERROR;
		}
		$response->addHeader('Content-type', 'application/json');
		$response->body = sendRes($message, 'true', time());
		return $response;
	}
}

?>
