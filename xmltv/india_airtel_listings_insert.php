<?php
require_once "/usr/include/php/callistoV4/Listings.php";
require_once "/usr/include/php/callistoV4/DbHandler.php";
require_once '/usr/include/php/callistoV4/ShowMapping.php';

$shortopts  = "";
$longopts = array ("country:", "xml:", "help::"); 

$options = getopt($shortopts, $longopts);

if (isset ($options['help']) || count ($options) == 0 || (!isset ($options['country']) || !isset($options['xml']))){
	print "usage : php sky_listings_insert.php --country <country code> --xml <xmltv file name>\n";
	exit;
}

$country = $options['country'];
$file = $options['xml'];


date_default_timezone_set('UTC');
$string = file_get_contents ($file, FILE_USE_INCLUDE_PATH);
$xml = simplexml_load_string($string);
$json = json_encode($xml);
$array = json_decode($json,TRUE);

$programmes = $array["programme"];

$listing = new Listings();
$dbHandle = DbHandler::getConnection();
                if($dbHandle == null) {
                        error_log("Error in fetching DB handler.");
                        return null;
                }
$collection = $dbHandle->channels;
$list_collection = $dbHandle->listings;
$show_collection = $dbHandle->shows;
$timings_collection = $dbHandle->timings;


error_log ("deleting all upcoming channels before load");
$rstat = $timings_collection->remove(array('start' => array('$gt' => new MongoDate(time()))));
if ($rstat){
	print "All upcoming shows deleted\n";
}
else{
	print "Error in deleting upcoming shows\n";
}

$listings_hash = array();
$channels_hash = array();
$timings_hash = array();
$show_hash = array();
$listings_meta = array();
$listings = $list_collection->find( array('country' => $country));
$channels = $collection->find ( array ('country' => $country));
$timings_data = $timings_collection->find(array('start' => array('$gte' => new MongoDate(strtotime("now") - 24*60*60))), array('listing_id', 'start'));
$show_data = $show_collection->find();

foreach ($show_data as $show){
	if (isset ($show['meta_id'])){
		$show_hash[$show['meta_id']] = $show['list_ids'];
	}
}

print "starting to fetch timing\n";
foreach ($timings_data as $timings){
	$timings_hash[$timings['listing_id']][$timings['start']->sec] = true;	
}
print "done timing starting listing\n";

foreach ($listings as $list){
	if (isset ($list['meta'])){
		$listings_meta[$list['listing_id']] = $list['meta'];
	}
	else{
		$listings_meta[$list['listing_id']] = array();
	}
	$listings_hash[$list["ch_id"]][$list["listing_name"]] = $list["listing_id"];
}
print "done listing. starting meta insertions\n";
foreach ($listings_meta as $key => $value){
	if (count ($value) != 0){
		//meta
		if (isset ($show_hash[$key])){
			$same_shows = $show_hash[$key];
			foreach ($same_shows as $duplicate){
				if ($key == $duplicate){
					continue;
				}
				$listings_meta[$duplicate] = $value;
			}
		}
	}
}


print "done meta. starting channels\n";
foreach ($channels as $channel){
	$channels_hash[$channel['ch_id']]['icon'] = $channel['icon'];
	$channels_hash[$channel['ch_id']]['ch_name'] = $channel['ch_name'];
	$channels_hash[$channel['ch_id']]['cat_id'] = $channel['cat_id'];
	if (isset ($channel['genre'])){
		$channels_hash[$channel['ch_id']]['genre'] = $channel['genre'];
	}
	else{
		$channels_hash[$channel['ch_id']]['genre'] = "0";
	}
}

$timings_data = array();
$insert_count = 0;
print "total programme count = ".count($programmes)."\n";
foreach ($programmes as $programme){
	$insert_count++;
	$ch_id = $programme["@attributes"]["channel"];
	$start_off = $programme["@attributes"]["start"];
	$stop_off = $programme["@attributes"]["stop"];
	$arr = explode (" ", $start_off);
        $arr1 = explode (" ", $stop_off);
	$timetominus = 330 * 60;
        $start = new MongoDate(strtotime ($arr[0]) - $timetominus);
        $stop = new MongoDate(strtotime ($arr1[0]) - $timetominus);
	$listing_name = $programme["title"];
        	$desc = "No description found.";
			$ch_name = $channels_hash[$ch_id]['ch_name'];
			$icon = $channels_hash[$ch_id]['icon'];
			$genre = $channels_hash[$ch_id]['genre'];
			$cat_id = $channels_hash[$ch_id]['cat_id'];
			if ($genre == "0"){
				if ($insert_count == count($programmes)){
					$i = count ($timings_data);
                                	if ($i == 0){
                                        	print "No records to insert \n";
                                        	continue;
                                	}
                                	$result = $listing->batchCreateListings($timings_data);
                                	if ($result) {
                                        	error_log ("$result records successfully created\n");
                                	} else {
                                        	error_log  ("Error in inserting to timings table");
                                	}
                                	print "inserting $i records\n";
					$timings_data = array();
					break;
				}
				else{
					continue;
				}
			}
			$insert_rec = array("ch_id" => $ch_id, "listing_name"  => $listing_name, "country" => $country, "ch_name" => $ch_name, "icon" => $icon);
			$listing_id;
			if (isset ($listings_hash[$ch_id][$listing_name])){
				$listing_id = $listings_hash[$ch_id][$listing_name];
			}
			else{
				$listing_id =  $listing->createListing($insert_rec);
				error_log ("new listing.. need to insert id = $listing_id");
				if (!$listing_id){
					error_log ("failure in create listing");
					continue;
				}
				$listings_hash[$ch_id][$listing_name] = $listing_id;
				$show = new ShowMapping();
				$user_args = array();
        			$user_args['list_name'] = $listing_name;
       		 		$user_args['list_id'] = $listing_id;
        			$listings1 = $list_collection->find(array('listing_name' => $listing_name));
       				$list_ids = array();
        			foreach ($listings1 as $listing1){
                			$list_ids[] = intval ($listing1['listing_id']);
                			if (!isset ($user_args['meta_id']) && isset($listing1['meta'])){
                        			$user_args['meta_id'] = $listing1['listing_id'];
               				}
        			}
        			$user_args['list_ids'] = $list_ids;
        			error_log ($show->createShows($user_args)."\n");
				$current_meta = array();
				if (isset ($user_args['meta_id']) && isset ($listings_meta[$user_args['meta_id']])){
					$current_meta = $listings_meta[$user_args['meta_id']];
					$listings_meta[$listing_id] = $current_meta;
				}
			}
			if (isset ($timings_hash[$listing_id][$start->sec])){
				error_log ("old timing for $listing_id so skip\n");
			}
			else {
				$insert_rec1 = array();
				if (isset ($listings_meta[$listing_id]) && count($listings_meta[$listing_id]) != 0){
					$meta = $listings_meta[$listing_id];
                        		$insert_rec1 = array("ch_id" => $ch_id, "listing_id" => $listing_id, "start" => $start, "stop" => $stop, "country" => $country, "listing_name" => $listing_name, "ch_name" => $ch_name, "icon" => $icon, "cat_id" => $cat_id, "genre" => $genre, "meta" => $meta);
				}
				else{
					$insert_rec1 = array("ch_id" => $ch_id, "listing_id" => $listing_id, "start" => $start, "stop" => $stop, "country" => $country, "listing_name" => $listing_name, "ch_name" => $ch_name, "icon" => $icon, "cat_id" => $cat_id, "genre" => $genre);
				}
                        	$timings_data[] = $insert_rec1;	
			}
			if ($insert_count % 5000 == 1 || $insert_count == count($programmes)){
				$i = count ($timings_data);
				if ($i == 0){
					print "No records to insert \n";
					continue;
				}
				$result = $listing->batchCreateListings($timings_data);
	                        if ($result) {
        	                        error_log ("$result records successfully created\n");
                	        } else {
                        	        error_log  ("Error in inserting to timings table");
                        	}
         		       	print "inserting $i records\n";
				$timings_data = array();
       	 		}
		
}
?>
