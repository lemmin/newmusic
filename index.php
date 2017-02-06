<?php 
/*
index.php

getStates
getRadioStations
getPlaylists
getSongs
getPopularity
getReleaseDate
*/

define('MYSQL_USER', 'root');
define('MYSQL_PASS', 'pass');
define('MYSQL_HOST', 'localhost');
define('MYSQL_DB', 'newmusic');
define('WIKIBASE_URL', 'https://en.wikipedia.org');
define('STATES_URL', 'https://en.wikipedia.org/wiki/List_of_states_and_territories_of_the_United_States');
define('STATIONS_URL', 'https://en.wikipedia.org/wiki/List_of_radio_stations_in_');
define('STATION_TYPES', 'Alternative');



$mf = new MusicFinder();
//$mf->updateStates();
//$mf->updateStations();

//$mf->getStations();
//print_r($mf);

class MusicFinder {
	public $states = [];
	public $stations = [];
	function __construct() {
		//$this->states = $this->getStates();
	}

	public function updateStates() {
		$states = $this->findStates();

		$values = '("' . implode('"),("', $states) . '")';

		$this->query('TRUNCATE states');
		$this->query('INSERT INTO states (state) VALUES ' . $values);
	}

	public function updateStations() {
		$stations = $this->findStations();
		$values = '';
		foreach ($stations as $station) {
			$values .= $station->toInsertString() . ',';
		}
		$values = rtrim($values, ',');

		$this->query('TRUNCATE stations');
		$this->query('INSERT INTO stations (callsign, wikisite, genre_text, state_id) VALUES ' . $values);
	}

	private function findStates() {
		$states = [];
		$html = file_get_contents(STATES_URL);
		preg_match_all('/scope="row".+?title="([^"]+)?"/', $html, $m);

		for ($i=0; $i<50; $i++) {
			$states[] = strtr(trim(preg_replace('/\([^\)]+\)/', '', $m[1][$i])), ' ', '_');
		}
		return $states;
	}

	private function getStates() {
		$r = $this->query('SELECT * FROM states');
		$states = [];
		while ($row = $r->fetch_assoc()) {
			$states[] = $row;
		}
		return $states;
	}

	private function findStations() {
		$states = $this->getStates();
		$stations = [];
		foreach ($states as $state_info) {
			$state = $state_info['state'];
			$stateid = $state_info['state_id'];
			$html = file_get_contents(STATIONS_URL.$state);
			preg_match_all('/<td><a href="([^"]+)"[^>]+>([^<]+)<.+?(?:<td>.+?<\/td>\s*){3}\s*<td>(.*?)<\/td>/ism', $html, $m);

			foreach ($m[1] as $i => $url) {
				$station = new RadioStation($m[2][$i], WIKIBASE_URL.$url, strtr(strip_tags($m[3][$i]), '"', ''), $stateid);
				$stations[] = $station;
			}
			echo $state . '<br/>';
		}
		return $stations;
	}
	private function query($s) {
		$db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
		$result = $db->query($s) or die($db->error . ': ' . $s);

		$db->close();
		return $result;
	}
}

class RadioStation {
	public $stationid;
	public $callsign;
	public $wikisite;
	public $website;
	public $genre;
	public $stateid;

	function __construct($callsign, $wikisite, $genre, $stateid) {
		$this->callsign = $callsign;
		$this->wikisite = $wikisite;
		$this->genre = $genre;
		$this->stateid = $stateid;
	}

	function getWebsite() {
		$html = file_get_contents($this->wikisite);
		echo $this->wikisite;
		preg_match('/website.+?href="([^"]+)"/ism', $html, $m);
		print_r($m);
		die();
	}

	function toInsertString() {
		return '("' . 
			addslashes(substr($this->callsign, 0, 8)) . '","' .
			addslashes($this->wikisite) . '","' .
			addslashes($this->genre) . '", ' .
			addslashes($this->stateid).')';
	}
}

?>