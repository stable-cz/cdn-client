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




// Helper functions

function dump($var, $color = '#666') {
    // https://joshtronic.com/2013/09/02/how-to-use-colors-in-command-line-output/
    $colors = [
        "black" => "\e[1;37;48m"
        , "red" => "\e[0;31;48m"
        , "green" => "\e[0;32;48m"
        , "yellow" => "\e[1;33;41m"
        , "blue" => "\e[1;34;48m"
        , "magenta" => "\e[0;35;48m"
        , "cyan" => "\e[0;36;48m"
        , "white" => "\e[1;37;48m"
        , "reset" => "\e[0;31;0m"
    ];
    
    if ('cli' == PHP_SAPI) {
        if (isset($colors[$color])) {
            print($colors[$color]);
        }
        var_dump($var);
        if (isset($colors[$color])) {
            print($colors['reset']);
        }
        echo "\n";
    } else {
        echo '<pre style="border: 1px solid #ccc;overflow:auto; max-height: 100px; color:' . $color . '">' . var_export($var, true) . '</pre>';
    }
}



$apikey = $GLOBALS['argv'][1];

// Initialize
$c =  new \Stable\Cdn\Client($apikey);

dump('========= TEST LS DIR / ===========', 'black');
$res = $c->ls('/');
dump($res,  !empty($res->data) ? 'green' : 'red');

dump('========= TEST LS DIR ../ ===========', 'black');
$res = $c->ls('/../');
dump($res, !empty($res->errors) ? 'green' : 'red');

dump('========= TEST UPLOAD ===========', 'black');
$testfile = tempnam('/tmp', 'rwer');
file_put_contents($testfile, sha1(microtime(true)));
$testfiletarget = 'cesticka/k/souboru/daleka/soubor.jpg';

$res = $c->upload($testfile, $testfiletarget);
dump($res,  !empty($res->data) ? 'green' : 'red');
if (!empty($res->data->url)) {
    $file = file_get_contents('http:' . $res->data->url);
    dump('UPLOAD MATCH', 'black');
    dump('MATCH RESULT: ' . ( file_get_contents($testfile) == $file ? 'OK' : 'ERROR' ), file_get_contents($testfile) == $file ? 'green' : 'red');
}

dump('========= TEST LS FILE ===========', 'black');
$res = $c->ls($testfiletarget);
dump($res,  !empty($res->data) ? 'green' : 'red');


dump('========= TEST DELETE ===========', 'black');
$res = $c->delete('cesticka/k/souboru/daleka/soubor.jpg');
dump($res,  !empty($res->data) ? 'green' : 'red');

dump('========= TEST LS DELETED FILE ===========', 'black');
$res = $c->ls($testfiletarget);
dump($res,  !empty($res->errors) ? 'green' : 'red');
