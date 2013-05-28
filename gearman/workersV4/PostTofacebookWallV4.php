<?php
require_once '/usr/include/php/php-sdk/src/facebook.php';
require_once '/usr/include/php/callistoV4/DbHandler.php';
require_once '/usr/include/php/callistoV4/CreateUser.php';
require_once '/usr/include/php/callistoV4/SearchUser.php';
# Create our worker object.

//$log[] = "Success";
function PostTofacebookWallV4($job, &$log){
        $dbHandle = DbHandler::getConnection();
	$user_args= json_decode ($job->workload(), true);
        $log[] = "Received job: " . $job->handle() . "\n";
        $user_collection = $dbHandle->users;
        $listing_collection = $dbHandle->listings;
	$shows_collection = $dbHandle->shows;
        $users = new CreateUser();
        $uid = $user_args['uid'];
        $token;
        $fsplacename;
        $details = $user_collection->findOne(array('uid' => intval($uid)), array('token', 'name'));
        if (isset($details['token'])){
                $token = $details['token'];
        }
        $listing_id = $user_args['listing_id'];
	$show_data = $shows_collection->findOne(array('list_ids' => intval ($listing_id)), array('meta_id'));
        if (isset ($show_data['meta_id'])){
                $listing_id = $show_data['meta_id'];
        }
        $list_details = $listing_collection->findOne(array('listing_id' => intval ($listing_id)));
	$listing_name = $list_details['listing_name'];
        $ch_name = $list_details['ch_name'];
        $desc = 'Amplyfy.me helps you bring your loved ones closer while you watch your favourite TV shows. Invite, discuss and bet on your favorite matches with your friends using amplyfy me. With a lot more features coming soon Amplyfy might be what you are looking for.';
        $link = "http://amplyfy.me/pages/show/$listing_id";
        $update = $user_args['update'];
        if (isset ($user_args['fsplacename'])){
                $fsplacename = $user_args['fsplacename'];
		if ($update == ""){
          	      $message = "watching $listing_name on $ch_name at $fsplacename";
       	 	}
        	else{
                	$message = trim ($update) . " - on $ch_name at $fsplacename";
        	} 
        }

	else{
		if ($update == ""){
        		$message = "watching $listing_name on $ch_name";
		}
		else{
			$message = trim ($update) . " - on $ch_name";
		}
	}
        $link_name = ucwords($listing_name);
        $picture = 'http://amplyfy.me/img/logo-0'.rand(1,5).'.png';
	$log[] = "picture : $picture";
        if (isset($list_details['meta'])){
                if (isset($list_details['meta']['desc']) && $list_details['meta']['desc'] != ""){
                        $desc = $list_details['meta']['desc'];
                }
                if (isset($list_details['meta']['posters'])){
                        $picture = $list_details['meta']['posters']['profile'];
                }
        }

        $post =  array(
                'access_token' => $token,
                'message' => $message,
                'name' => $link_name,
                'link' => $link,
                'description' => $desc,
                'picture' => $picture
        );
	$log[] = "Posting checkin for user $uid";
 
        $facebook = new Facebook(array(
                'appId'  => '233989876695578',
                'secret' => 'c8e243aef8f48e1e4aeee4bf46a9271d',
                'cookie' => false
        ));

	try {
        	$res = $facebook->api('/me/feed', 'POST', $post);
	} catch (FacebookApiException $e) {
		$log[] = $e;
	}
        return;
}


?>
