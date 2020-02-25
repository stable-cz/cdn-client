<?php

error_reporting(E_ALL);


// Load
if (file_exists(__DIR__ . '/../src/Client/Client.php')) {
    require_once __DIR__ . '/../src/Client/Client.php'; // Class itself
} else {
    require_once __DIR__ . '/../../../autoload.php'; // composer autoloader
}

// Param?
if (!isset($GLOBALS['argv'][1])) {
    die("Specify your API key as first CLI param please\n");
}

$client = new \Stable\Cdn\Client($GLOBALS['argv'][1]);
file_put_contents('localfile', date('Y-m-d'));
$r = [];
$r[] = $client->upload('localfile', 'path/to/remotefile');
$r[] = $client->ls('path/to/remotefile');
$r[] = $client->ls('path/to');
$r[] = $client->delete('path/to/remotefile');
unlink('localfile');

var_dump($r);
