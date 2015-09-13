<?php

require_once 'lib/EchoNest/Autoloader.php';
EchoNest_Autoloader::register();

$musixapikey = '4cc9e7ad04391df298f95ede0ba7b286';

$email = ($_REQUEST['email'])?$_REQUEST['email']:'mike@elsmore.me';

$echonest = new EchoNest_Client();
$echonest->authenticate('R9RBK9MF4OKHSIVRW');

$html = file_get_contents('http://widget.soundwave.com/?feedType=feed&email='.$email);

$dom = new DOMDocument();
$dom->loadHTML($html);

$xpath = new DOMXpath($dom);
$result = $xpath->query('//div/ul/li/div[@class="media-body"]');

if ($result->length > 0) {

		$lyrics = '';

		for($i=0; $i<$result->length; $i++) {
			$trackHtml = $result->item($i)->childNodes;

			$time = $trackHtml->item(0)->textContent;
			var_dump(substr ( $time, -1));
			$artist = $trackHtml->item(1)->textContent;
			$track = $trackHtml->item(2)->textContent;

			$songApi = $echonest->getSongApi();
			$songData = $songApi->search(array('title' => $track, 'artist' => $artist,'bucket'=>'id:musixmatch-WW'));

			$musixMatchData = explode(':', $songData[0]['foreign_ids'][0]['foreign_id']);

			$musixMatchId = $musixMatchData[count($musixMatchData)-1];

			$musixMatchResponse = json_decode(file_get_contents('http://api.musixmatch.com/ws/1.1/track.lyrics.get?format=json&apikey='.$musixapikey.'&track_id='.$musixMatchId));

			$lyrics .= $musixMatchResponse->message->body->lyrics->lyrics_body;

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
		$result = curl_exec($ch);

		var_dump($result);

}
