<?php

error_reporting(E_ALL);

require_once __DIR__ .'/lib/EchoNest/Autoloader.php';
EchoNest_Autoloader::register();

$musixapikey = '4cc9e7ad04391df298f95ede0ba7b286';

$email = ($_REQUEST['email'])?$_REQUEST['email']:'mike@elsmore.me';

$start_date = ($_REQUEST['start_date'])?$_REQUEST['start_date']:0;
$end_date = ($_REQUEST['end_date'])?$_REQUEST['end_date']:time();

$echonest = new EchoNest_Client();
$echonest->authenticate('R9RBK9MF4OKHSIVRW');

$html = file_get_contents('http://widget.soundwave.com/?feedType=feed&email='.$email);

$dom = new DOMDocument();
$dom->loadHTML($html);

$xpath = new DOMXpath($dom);
$result = $xpath->query('//div/ul/li/div[@class="media-body"]');

$frequency = 0;

if ($result->length > 0) {

		$lyrics = '';

		for($i=0; $i<$result->length; $i++) {
			$trackHtml = $result->item($i)->childNodes;

			$time = $trackHtml->item(0)->textContent;
			$timeVal = substr ( $time, -1);
			switch($timeVal) {
				case "m":
					$timeDiff = substr ( $time, 0, (strlen($time)-1)) * 60;
					break;
				case "h":
				default:
					$timeDiff = substr ( $time, 0, (strlen($time)-1)) * 60 * 60;
					break;
			}
			$timestamp = time() - $timeDiff;
			$artist = $trackHtml->item(1)->textContent;
			$track = $trackHtml->item(2)->textContent;

			if ($timestamp > $start_date && $timestamp < $end_date) {

					$songApi = $echonest->getSongApi();
					$songData = $songApi->search(array('title' => $track, 'artist' => $artist,'bucket'=>'id:musixmatch-WW'));

					$musixMatchData = explode(':', $songData[0]['foreign_ids'][0]['foreign_id']);

					$musixMatchId = $musixMatchData[count($musixMatchData)-1];

					$musixMatchResponse = json_decode(file_get_contents('http://api.musixmatch.com/ws/1.1/track.lyrics.get?format=json&apikey='.$musixapikey.'&track_id='.$musixMatchId));

					$lyrics .= $musixMatchResponse->message->body->lyrics->lyrics_body;

					$frequency++;

			}

			if($i==19) {
				break;
			}
		}


		$url = 'http://battlehack.jakelprice.com/api/nlp';
		$fields = array(
								'payload' => $lyrics
						);

		//url-ify the data for the POST
		$fields_string = '';
		foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
		rtrim($fields_string, '&');

		//open connection
		$ch = curl_init();

		//set the url, number of POST vars, POST data
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_POST, count($fields));
		curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

		//execute post
		$result = json_decode(curl_exec($ch));

		$result->frequency = $frequency;
    $result->score = $result->value;
		echo json_encode($result);

}
