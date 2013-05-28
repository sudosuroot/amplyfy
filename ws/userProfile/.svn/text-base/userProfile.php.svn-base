<?php
require_once '/usr/include/php/callistoV4/Response.php';
require_once "/usr/include/php/callistoV4/UserProfile.php";
/**
 * Get UserProfile. 
 *
 * @uri /UserProfile
 */
class UserProfileResource extends Resource {
	private $dbHandle;
	/**
	 * Handle a POST request for this resource
	 * @param Request request
	 * @return Response
	 */
	function get($request) {
		date_default_timezone_set('UTC');
		$response = new Response($request);
		$message = '';
		$fav_channels = array();
		if ($_REQUEST["uid"]) {
			$uid = $_REQUEST["uid"];
			if ($_REQUEST["channel"]){
				$profile = new UserProfile($uid);
				$shows = $profile->getFavCurrentPlayingChannels();
				$message = $shows;
				$response->code = Response::OK;
			}
			else {
				$message = array('msg' => 'Channel attr is missing');
				$response->code = Response::INTERNALSERVERERROR;
			}
		} else {
			$message = array('msg' => 'Essential (uid) attributes missing.');
			$response->code = Response::INTERNALSERVERERROR;

		}
		$response->addHeader('Content-type', 'application/json');
		$response->body = sendRes($message, 'true', time());
		return $response;
	}

	function post($request) {
		$response = new Response($request);
		if ($_REQUEST["uid"]) {
			$uid = $_REQUEST["uid"];	
			print $uid."\n";
			if (isset ($_REQUEST["channels"]) && isset ($_REQUEST["type"])){
				$type = $_REQUEST["type"];
				$profile = new UserProfile($uid);
				$status = false;
				if ($type == "like"){
					$channels = explode(",", $_REQUEST["channels"]);
					$status = $profile->setFavChannels($channels);
				}
				else if ($type == "unlike"){
					$channel = $_REQUEST["channels"];
					$status = $profile->removeFavChannel($channel);
				}
				else{
					$message = array('msg' => 'Type has to be like or unlike');
					$response->code = Response::INTERNALSERVERERROR;
				}
				if ($status){
					$message = $status;
                                	$response->code = Response::OK;
				}
				else{
					$message = array('msg' => 'Error in setting favorites/channel already set');
                                	$response->code = Response::OK;
				}
			}
                        else if (isset ($_REQUEST["show"]) && isset ($_REQUEST["type"])){
                                $type = $_REQUEST["type"];
                                $profile = new UserProfile($uid);
				$show = $_REQUEST["show"];
                                $status = false;
                                if ($type == "like"){
                                        $status = $profile->likeShow($show);
                                }
                                else if ($type == "unlike"){
                                        $status = $profile->unlikeShow($show);
                                }
                                else{
                                        $message = array('msg' => 'Type has to be like or unlike');
                                        $response->code = Response::INTERNALSERVERERROR;
                                }
                                if ($status){
                                        $message = $status;
                                        $response->code = Response::OK;
                                }
                                else{
                                        $message = array('msg' => 'Error in setting favorites/show already set');
                                        $response->code = Response::OK;
                                }
                        }

			else{
				$message = array('msg' => 'Essential (channels , seperated and type = like/unlike) attributes missing.');
                        	$response->code = Response::INTERNALSERVERERROR;
			}
		}
		else {
                        $message = array('msg' => 'Essential (uid) attributes missing.');
                        $response->code = Response::INTERNALSERVERERROR;
                }
		$response->addHeader('Content-type', 'application/json');
                $response->body = sendRes($message, 'true', time());
                return $response;
	}
}

?>
