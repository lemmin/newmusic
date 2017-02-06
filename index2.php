<?php

define('MYSQL_USER', 'root');
define('MYSQL_PASS', 'pass');
define('MYSQL_HOST', 'localhost');
define('MYSQL_DB', 'newmusic');
define('YOUTUBE_API_KEY', 'AIzaSyDQi10SFHLCca8y2S2ydwD0uD3ZVXpBHds');
define('YOUTUBE_SEARCH_URL', 'https://content.googleapis.com/youtube/v3/search?maxResults=20&part=snippet&type=video&videoCategoryId=10&videoEmbeddable=true&key=' . YOUTUBE_API_KEY . '&q=');
$urls = [
	//'http://pitchfork.com/reviews/best/tracks/' => '/ul class="artist-list"[^>]*><li[^>]*>([^<]+)<.+?h2 class="title"[^>]*>([^<]+)</',
	//'https://www.indieshuffle.com/new-songs/' => '/data-track-artist="([^"]+)"\s*?data-track-title="([^"]+)"/',
	'http://www.themusicninja.com/category/playlists/indie-dojo/' => '/class="player-artist">([^<]+?)&#8211;([^<]+)</'
];


$nm = new NewMusic();
//$nm->updateSongs();
$nm->updateSongsYTID();

class NewMusic {

	public function updateSongs() {
		$songs = $this->findSongs();

		$q = 'INSERT IGNORE INTO songs (artist,title,site_id) VALUES ';
		foreach ($songs as $i => $song) {
			$q .= '("' . $song[0] . '","' . $song[1] . '","' . $song[2] . '"),';
		}
		$q = rtrim($q, ',');

		$this->query($q);
	}

	public function updateSongsYTID() {
		$songs = $this->getSongsWithoutYTID();

		foreach ($songs as $song) {

			$ytid = $this->findSongYTID($song['artist'], $song['title']);

			$q = 'UPDATE songs SET youtube_id = "' . $ytid . '" WHERE song_id = ' . $song['song_id'];
			$this->query($q);
		}
	}

	public function findSongs() {
		$sites = $this->getSites();
		$songs = [];
		foreach ($sites as $site) {
			$html = file_get_contents($site['url']);
			preg_match_all($site['regex'], $html, $m);

			if (count($m[0])) {
				foreach ($m[1] as $i => $artist) {
					$songs[] = [$this->clean($artist), $this->clean($m[2][$i]), $site['site_id']];
				}
			}
		}
		return $songs;
	}

	private function findSongYTID($artist, $title) {
		$q = urlencode($this->cleanURL($artist . ' ' . $title));
		$json = file_get_contents(YOUTUBE_SEARCH_URL . $q);
		$results = json_decode($json);

		return $results->items[0]->id->videoId;
	}

	private function getSites() {
		$r = $this->query('SELECT * FROM sites');
		$sites = [];
		while ($row = $r->fetch_assoc()) {
			$sites[] = $row;
		}
		return $sites;
	}

	private function getSongs() {
		$r = $this->query('SELECT * FROM songs');
		$sites = [];
		while ($row = $r->fetch_assoc()) {
			$sites[] = $row;
		}
		return $sites;
	}

	private function getSongsWithoutYTID() {
		$r = $this->query('SELECT * FROM songs WHERE youtube_id IS NULL || youtube_id = ""');
		$sites = [];
		while ($row = $r->fetch_assoc()) {
			$sites[] = $row;
		}
		return $sites;
	}

	private function clean($s) {
		$s = html_entity_decode($s);
		$s = trim($s, '’“” ');
		//$s = str_replace("“", '', $s);
		//$s = str_replace('”', '', $s);
		
		return $s;
	}

	private function cleanURL($s) {
		$pats = [
			'/\[[^\]]+\]/',
			'/\([^\)]+\)/',
			'/\{[^\}]+\}/',
		];
		$reps = ['','',''];

		$s = preg_replace($pats, $reps, $s);
		return preg_replace('/[^a-zA-Z0-9\s]/', '', $s);
	}

	private function query($s) {
		$db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
		$result = $db->query($s) or die($db->error . ': ' . $s);

		$db->close();
		return $result;
	}
}
?>