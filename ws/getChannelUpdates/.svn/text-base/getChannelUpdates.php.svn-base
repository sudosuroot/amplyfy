<?php

/**
 * Display and process a HTML form via a HTTP POST request
 *
 * This example outputs a simple HTML form and gathers the POSTed data
 *
 * @namespace Tonic\Examples\HTMLForm
 * @uri /getChannelUpdates
 */
class ChannelUpdates extends Resource {
    
    /**
     * Handle a GET request for this resource
     * @param Request request
     * @return Response
     */


    function get($request, $ch_id) {
        
        $response = new Response($request);
        
	       $m = new Mongo();
	       $db = $m->callisto;
	       $collection = $db->updates;
	       //need to add exceptions :|
	       $resp = "";
		if(isset($_GET['ch_id']))
		{
			$ch_id = $_GET['ch_id'];
			$updates = $collection->find(array('ch_id' => $ch_id))->sort(array('created_on'=>-1))->limit(10);
			if($updates->count == 0){
				$resp = '{"errmsg":"no updates"}';
			}
			else
			{
				$resp_arr = array();
				foreach($updates as $update)
				{
#	$resp = $update["update"].$resp;	
					array_push($resp_arr, $update);
				}
				$resp = json_encode($resp_arr);
			}
		}
		else
		{
			$resp = '{"errmsg":"no ch specified"}';
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
