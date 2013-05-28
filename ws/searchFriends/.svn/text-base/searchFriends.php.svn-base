<?php


/**
 * Display and process a HTML form via a HTTP POST request
 *
 * This example outputs a simple HTML form and gathers the POSTed data
 *
 * @namespace Tonic\Examples\HTMLForm
 * @uri /searchFriends
 */
class People extends Resource {
    
    /**
     * Handle a GET request for this resource
     * @param Request request
     * @return Response
     */
        function get($request, $name) {

                $response = new Response($request);

                if (isset($_REQUEST['name'])) {
                        $resp = "";
                        $name = $_REQUEST['name'];
			//$friends = new SearchUser();
                        //$people = $friends->searchUser($name);
			$m = new Mongo();
                        $db = $m->callisto;
                        $collection = $db->users;
                        //need to add exceptions :|
                        $resp = "";
		  	$name_regex = "/^".$name."/i";
                        error_log ($name_regex);
                        $regex = new MongoRegex($name_regex);
	
                        $users = $collection->find(array('name'=>$regex));
                        if($users)
                        {
                                $resp_arr = array();
                                foreach($users as $user)
                                {
                                        array_push($resp_arr, $user);
                                }
                                $resp = json_encode($resp_arr);
                        }
                        else {

                                $resp = '{"errmsg":"no users"}';
                        }
                } else {
                        $resp = '{"errmsg":"no matching name"}';
                }
                $etag = md5($request->uri);

                $response->code = Response::OK;
                $response->addHeader('Content-type', 'application/json');
                $response->addEtag($etag);
                $response->body = $resp;

                return $response;

        }


}

?>
