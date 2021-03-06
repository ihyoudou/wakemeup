<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Http\Response as Response;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

// loading env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();
$dbname = $_ENV['DBNAME'];

if($_ENV['MEMCAHCED_ENABLED'] == 'true'){
    $memcached = new Memcached();
    $memcached->addServer($_ENV['MEMCACHED_SERVER'], $_ENV['MEMCACHED_PORT']);
}

$db = new MongoDB\Client($_ENV['MONGODB']);
$collection = $db->$dbname->towakeup;
 
// Create App
$app = AppFactory::create();

// caching routes
$routeCollector = $app->getRouteCollector();
$routeCollector->setCacheFile(__DIR__ . '/../cache/routes.cachefile');

// Create Twig
if ($_ENV['APP_ENV'] == "prod") {
    $twig = Twig::create('../templates/', ['cache' => '../cache/']);
} else if ($_ENV['APP_ENV'] == "dev") {
    $twig = Twig::create('../templates/', ['cache' => false]); //disabling cache for debuging
}


// Add Twig-View Middleware
$app->add(TwigMiddleware::create($app, $twig));

// Define named route
$app->get('/', function ($request, $response, $args) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'index.html', [
        'hcaptchaSiteKey' => $_ENV['hcaptchaSiteKey'],
        'appname' => $_ENV['APP_NAME']
    ]);
})->setName('index');

// /api/v1/getURLcount GET endpoint
// this endpoint is used to get current documents count in db
$app->get('/api/v1/getURLcount', function ($request, $response, $args) {
    global $collection;
    global $memcached;
    if($_ENV['MEMCAHCED_ENABLED'] == 'true'){
        $jsonData = $memcached->get("wkm_urlCount");

        if (!$jsonData){ // if memcached cache is empty
            $count = $collection->count();
            $jsonData = array(
                'count' => $count
            );
            $memcached->set("wkm_urlCount", $jsonData, $_ENV['MEMCACHED_STORETIME']) or die("Cannot create memcached object");
        }
    } else { // if memcached is not enabled
        $count = $collection->count();
        $jsonData = array(
            'count' => $count
        );
    }
   

    
    return $response->withJson($jsonData, 200);
})->setName('getURLcount');


// /api/v1/addURL POST endpoint
// this endpoint is used to add a new link to system
$app->post('/api/v1/addURL', function ($request, $response, $args) {
    global $collection;

    // retriving body and parsing json into array
    $json = $request->getBody();
    $data = json_decode($json, true);

    $url = $data['url'];
    $hcaptchaResponse = $data['captchaResponse'];


    // checking if array is not empty and value is a valid url
    if (!empty($data['url']) && filter_var($url, FILTER_VALIDATE_URL)) {

        $verifyingCaptcha = validateCaptcha($hcaptchaResponse);

        if ($verifyingCaptcha) {

            $checkIfExist = $collection->find(['url' => $url])->toArray();
            // checking if url is not already in db
            if (!empty($checkIfExist[0]['url'])) {
                // if it is, returning 200 http code with success = false
                $data = array(
                    'success' => false,
                    'reason' => 'urlExist'
                );
                return $response->withJson($data, 200);
            } else { // if it not exist - inserting into db and returning result
                $addNewURL = $collection->insertOne(
                    [
                        'url' => $url,
                        'date' => microtime(true)
                    ]
                );
                // creating array with json response
                $data = array(
                    'success' => true,
                    'id' => $addNewURL->getInsertedId()
                );

                return $response->withJson($data, 200);
            }
        } else { // if captcha validation failed
            $data = array(
                'success' => false,
                'reason' => 'captchaInvalid'
            );
            return $response->withJson($data, 200);
        }
    } else { // if body is empty
        $data = array(
            'success' => false,
            'reason' => 'missingBodyOrBadURL'
        );
        return $response->withJson($data, 200);
    }
})->setName('addNew');

function validateCaptcha($response)
{
    $data = array(
        'secret' => $_ENV['hcaptchaSecret'],
        'response' => $response
    );
    $verify = curl_init();
    curl_setopt($verify, CURLOPT_URL, "https://hcaptcha.com/siteverify");
    curl_setopt($verify, CURLOPT_POST, true);
    curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($verify);
    // var_dump($response);
    $responseData = json_decode($response);
    if ($responseData->success) {
        // your success code goes here
        return true;
    } else {
        // return error to user; they did not pass
        return false;
    }
}


// Run app
$app->run();
