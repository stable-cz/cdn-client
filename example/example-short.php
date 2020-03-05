<?php

$config = [
    'upload_small_file' => true
    , 'delete_small_file' => true
    , 'upload_large_file' => true
    , 'configure_sizes' => true
    , 'upload_image_and_test_resizer' => true
];

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
$r = [];
if ($config['upload_small_file']) {
    file_put_contents('localfile', str_repeat('x', 1024));
    // $r[] = $client->upload('localfile', 'path/to/remotefile');
    $r[] = $client->upload('localfile', 'path/to/smallremotefile.txt', $progress);
    $r[] = $client->ls('path/to/smallremotefile.txt');
    unlink('localfile');
}
if ($config['delete_small_file']) {
    $r[] = $client->delete('path/to/smallremotefile.txt');
    $r[] = $client->ls('path/to/smallremotefile.txt');
}

if ($config['upload_large_file']) {
    file_put_contents('localfile', str_repeat('x', 2 * $client->chunkFileSize));
    // $r[] = $client->upload('localfile', 'path/to/remotefile');
    $r[] = $client->upload('localfile', 'path/to/remotefile', $progress);
    $r[] = $client->ls('path/to/remotefile');
    $r[] = $client->ls('path/to');
    $r[] = $client->delete('path/to/remotefile');
    unlink('localfile');
}

if ($config['configure_sizes']) {
    $r[] = $client->configure('sizes', 'get');
    $r[] = $client->configure('sizes', 'get', 800);
    $r[] = $client->configure('sizes', 'add', ['size' => 250]);
    $r[] = $client->configure('sizes', 'add', ['size' => 301]);
    $r[] = $client->configure('sizes', 'add', ['size' => 800]);
    $r[] = $client->configure('sizes', 'get', 800);
    $r[] = $client->configure('sizes', 'delete', 301);
}

if ($config['upload_image_and_test_resizer']) {
    if (file_put_contents('/tmp/eva.jpg', file_get_contents('https://m.media-amazon.com/images/M/MV5BMTY3Mjk2MzExN15BMl5BanBnXkFtZTgwMDc1NjE2MTE@._V1_.jpg'))) {
        $r[] = $img = $client->upload('/tmp/eva.jpg', '/images/eva.jpg');
        if (!empty($img->data->url)) {
            $pu = parse_url($img->data->url);
            $url = 'https://' . $pu['host'] . '/ir/resize/250x250' . $pu['path'];
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            
            $url = 'https://' . $pu['host'] . '/ir/info' . $pu['path'];
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $r[] = curl_exec($ch);
            
        }
        // $r[] = $client->delete('/images/eva.jpg');
    };
}

var_dump($r);
foreach($client->calls as $call) {
    foreach($call as $key=>$v) {
        echo $key . "\n-------------\n" . $v . "\n\n";
    }
}
echo "\n\n";
