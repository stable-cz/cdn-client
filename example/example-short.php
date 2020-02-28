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

$progress = function($data) {
    echo (round(100 * $data->progress / $data->total_filesize) . '% - ' 
        . round($data->progress / 1024 / 1024) . 'M of ' . round($data->total_filesize / 1024 / 1024) . 'M'
        . ' ETA: ' . ( isset($data->eta_formatted) ? $data->eta_formatted : '?' )
        ) . "\n";
};

$client = new \Stable\Cdn\Client($GLOBALS['argv'][1]);

file_put_contents('localfile', str_repeat('x', 2 * $client->chunkFileSize));
$r = [];

$r[] = $client->configure('sizes', 'get');
$r[] = $client->configure('sizes', 'get', 800);
$r[] = $client->configure('sizes', 'add', ['size' => 300]);
$r[] = $client->configure('sizes', 'add', ['size' => 800]);
$r[] = $client->configure('sizes', 'get', 800);
$r[] = $client->configure('sizes', 'delete', 300);
$r[] = $client->configure('sizes', 'get', 800);

$r[] = $client->upload('localfile', 'path/to/remotefile');
$r[] = $client->upload('localfile', 'path/to/remotefile', $progress);
$r[] = $client->ls('path/to/remotefile');
$r[] = $client->ls('path/to');
$r[] = $client->delete('path/to/remotefile');
unlink('localfile');

var_dump($r);
