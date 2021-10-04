<?php
// setting 240 secounds to max execution time just to be safe
set_time_limit(240);
require __DIR__ . '/vendor/autoload.php';
// loading env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ );
$dotenv->safeLoad();

$dbname = $_ENV['DBNAME'];

$db = new MongoDB\Client($_ENV['MONGODB']);
$collection = $db->$dbname->towakeup;
$documents = $collection->find()->toArray();
// i know that foreach is not the most efficient way to do that
// but for simple test it would be fast enough
foreach ($documents as $entry) {
    $url = $entry['url'];

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $_ENV['CURL_TIMEOUT']);
    curl_setopt($curl, CURLOPT_TIMEOUT, $_ENV['CURL_TIMEOUT']); 
    curl_setopt($curl, CURLOPT_USERAGENT, $_ENV['USERAGENT']);
    curl_setopt($curl, CURLOPT_REFERER, $_ENV['REFERER']);
    $resp = curl_exec($curl);
    curl_close($curl);

    echo 'pinging ', $entry['url'], "\n";
}

?>