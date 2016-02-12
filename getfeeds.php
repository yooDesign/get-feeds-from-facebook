<?php 
	header('Content-Type: application/json; charset=utf-8');
	$total_records       = 0;
	$cc                  = 0;
	$feed_url            = "https://graph.facebook.com/v2.1/656996807691827/feed?access_token=582923955167959|ztcJ8f1auJH_SjCEDmJgF4YL9-A&fields=from,id,message,caption,type,picture,created_time,link,likes.limit(1).summary(true),comments.limit(1).summary(true)&limit=50";
	$GLOBALS['stopit']   = false;
	$GLOBALS['arrFeeds'] = array();
	$tags_all="";
	function fetchUrl($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 40);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}

	
	function my_sort_c($a, $b){
		
		if ($a->Pubdate > $b->Pubdate) {
			return -1;
		} else
		if ($a->Pubdate < $b->Pubdate) {
			return 1;
		} else {
			return 0;
		}

	}

	
	function GetJsonFeed($url){
		$obj = file_get_contents($url,0,null,null);
		return json_decode($obj);
	}

	
	function get_last_date(){
		$feedsr =  GetJsonFeed('json.js');
		usort($feedsr, 'my_sort_c');
		$l_date=date("Y-m-d H:i:s", strtotime($feedsr[0]->Pubdate));
		return $l_date;
	}

	$mode="localhost";
	function url_get_contents ($Url) {
		
		if($mode=="localhost"){
			$output =file_get_contents($Url);
		} else {
			
			if (!function_exists('curl_init')){
				die('CURL is not installed!');
			}

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $Url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$output = curl_exec($ch);
			curl_close($ch);
		}

		return $output;
	}

	
	function trim_all($str, $what = NULL, $with = ' '){
		
		if ($what === NULL) {
			//	Character      Decimal      Use
			//	"\0"            0           Null Character
			//	"\t"            9           Tab
			//	"\n"           10           New line
			//	"\x0B"         11           Vertical Tab
			//	"\r"           13           New Line in Mac
			//	" "            32           Space
			$what = "\\x00-\\x20";
			//all white-spaces and control chars
		}

		return trim(preg_replace("/[" . $what . "]+/", $with, $str), $what);
	}

	$p = "";
	$GLOBALS['last_date']   = get_last_date();
	function get_feeds($current_feed){
		$cur_date=date("Y-m-d H:i:s");
		
		if ($current_feed != "" ) {
			$feeds_array = fetchUrl($current_feed);
			$feeds_array = str_replace("\u00002","-", $feeds_array);
			$feeds_array = iconv('UTF-8', 'UTF-8//IGNORE', $feeds_array);
			$feeds_array = json_decode($feeds_array);
			
			if(!$feeds_array){
				break;
			}

			foreach ($feeds_array->data as $feed) {
				$date_n=date("Y-m-d H:i:s", strtotime($feed->created_time));
				
				if($date_n<=$GLOBALS['last_date']){
					continue;
				}

				
				if($date_n>$cur_date){
					exit;
				}

				$post = array(                'id' => $feed->id,                'type' => $feed->type,                'from' => $feed->from->name,                'title' => $feed->message,                'image' => $feed->picture,                'link' => $feed->link,                'pubdate' => $date_n,                'likes' => $feed->likes->summary->total_count,                'comments' => $feed->comments->summary->total_count            );
				array_push($GLOBALS['arrFeeds'], $post);
				
				if ($GLOBALS['stopit']) {
					break;
				}

			}

			$next_feed = $feeds_array->paging->next;
			
			if (!$GLOBALS['stopit']) {
				get_feeds($next_feed);
			}

			
			if ($GLOBALS['stopit']) {
				return $GLOBALS['arrFeeds'];
			}

		}

		return $GLOBALS['arrFeeds'];
	}

	$GLOBALS['arrFeeds'] = get_feeds($feed_url);
	$p= "[";
	for ($i = 0; $i < count($GLOBALS['arrFeeds']); $i++) {
		
		if($GLOBALS['arrFeeds'][$i]['type']=="photo" && $GLOBALS['lastid']!=$GLOBALS['arrFeeds'][$i]['id']){
			$feedTitle = str_replace("'", "", $GLOBALS['arrFeeds'][$i]['title']);
			$feedTitle2=$feedTitle;
			$feedTitle = str_replace('"', '', $feedTitle);
			$feedTitle = str_replace('\\', '', $feedTitle);
			$feedTitle = str_replace("\u00002"," - ", $feedTitle);
			$feedTitle = trim_all($feedTitle);
			$feedTitle = preg_replace("/\r|\n/", "", $feedTitle);
			$feedImg=substr($GLOBALS['arrFeeds'][$i]['image'],strpos($GLOBALS['arrFeeds'][$i]['image'], "_"));
			$feedImg=substr($feedImg,0,strpos($feedImg, "_n"));
			$feedImg=substr($feedImg,0,strripos($feedImg, "_"));
			$feedImg=str_replace("_","",$feedImg);
			$feedImg="https://graph.facebook.com/".$feedImg."/picture?type=normal";
			$popularity= (int)$GLOBALS['arrFeeds'][$i]['likes'] + (int)$GLOBALS['arrFeeds'][$i]['comments'];
			$feedTitle2 = str_replace("'", " ", $feedTitle2);
			$feedTitle2 = str_replace('"', ' ', $feedTitle2);
			$feedTitle2 = str_replace("."," ",$feedTitle2);
			$feedTitle2 = str_replace("["," ",$feedTitle2);
			$feedTitle2 = str_replace("]"," ",$feedTitle2);
			$feedTitle2 = str_replace('\\', '', $feedTitle2);
			$feedTitle2 = preg_replace("/\r|\n/", " ", $feedTitle2);
			$feedTitle2 = str_replace(","," ",$feedTitle2);
			$feedTitle2 = str_replace("!"," ",$feedTitle2);
			$feedTitle2 = str_replace("....."," ",$feedTitle2);
			$feedTitle2 = str_replace("...."," ",$feedTitle2);
			$feedTitle2 = str_replace("..."," ",$feedTitle2);
			$feedTitle2 = str_replace(".."," ",$feedTitle2);
			$feedTitle2 = str_replace("."," ",$feedTitle2);
			$feedTitle2 = trim_all($feedTitle2);
			$feedTitle2 = preg_replace("/\r|\n/", "", $feedTitle2);
			$tags = explode(' ', $feedTitle2);
			foreach ($tags as $key=>&$value) {
				
				if (mb_strlen($value,'UTF-8') < 5 && !is_numeric($value)) {
					unset($tags[$key]);
				}

			}

			$tags_str=implode(",",$tags);
			$tags_str=str_replace("--","-",$tags_str);
			$tags_all.=$tags_str;
			$p .= 
				'{ "Id":"' . $GLOBALS['arrFeeds'][$i]['id'] . '",
				"Type":"' . $GLOBALS['arrFeeds'][$i]['type'] . '",
				"Title":"' . $feedTitle . '",
				"From":"' . $GLOBALS['arrFeeds'][$i]['from'] . '",
				"Image":"' . $feedImg . '",
				"Link":"' . $GLOBALS['arrFeeds'][$i]['link'] . '",
				"Pubdate":"' . $GLOBALS['arrFeeds'][$i]['pubdate'] . '",
				"Likes":"' . $GLOBALS['arrFeeds'][$i]['likes'] . '",
				"Comments":"' . $GLOBALS['arrFeeds'][$i]['comments'] . '",
				"Popularity":"' . $popularity . '"
},';
		}

	}

	$jsonfile = substr(file_get_contents('json.js'), 1);
	$tagsfile=file_get_contents('tags.js');
	$p .= $jsonfile;
	$tagsw=$tags_all.",".$tagsfile;
	file_put_contents('json.js', $p);
	file_put_contents('tags.js', $tagsw);
	?>