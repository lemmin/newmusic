<?php

define('MYSQL_USER', 'root');
define('MYSQL_PASS', 'pass');
define('MYSQL_HOST', 'localhost');
define('MYSQL_DB', 'newmusic');

$n = 10;
if (isset($_GET['max'])) {
	$n = (int)$_GET['max'];
}
$q = 'SELECT * FROM songs WHERE youtube_id IS NOT NULL AND youtube_id != '' ORDER BY date_added DESC, song_id LIMIT ' . $n;

$db = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB);
$result = $db->query($q) or die($db->error . ': ' . $q);
$songs = [];
while ($row = $result->fetch_assoc()) {
	$songs[] = $row;
}
$db->close();

header('Content-Type: application/json');
echo json_encode($songs)
?>