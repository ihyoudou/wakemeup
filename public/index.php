<?php
require __DIR__ . '/../vendor/autoload.php';
use Slim\Http\Response as Response;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

// loading env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../');
$dotenv->safeLoad();

$db = new MongoDB\Client($_ENV['MONGODB']);
$collection = $db->wakemeup->towakeup;

// Create App
$app = AppFactory::create();

// Create Twig
$twig = Twig::create('../templates/', ['cache' => '../cache/']);

// Add Twig-View Middleware
$app->add(TwigMiddleware::create($app, $twig));

// Define named route
$app->get('/', function ($request, $response, $args) {
    $view = Twig::fromRequest($request);
    return $view->render($response, 'index.html', [
        'appname' => $_ENV['APP_NAME']
    ]);
})->setName('index');

$app->post('/add', function ($request, $response, $args) {
    global $collection;

    // retriving body and parsing json into array
    $json = $request->getBody();
    $data = json_decode($json, true);
    
    $url = $data['url'];

    // checking if array is not empty
    if(!empty($data['url'])){
        
        $checkIfExist = $collection->find( [ 'url' => $url] )->toArray();
        // checking if url is not already in db
        if(!empty($checkIfExist[0]['url'])){
            // if it is, returning 200 http code with success = false
            $data = array(
                'success'=> false,
                'reason'=>'urlExist'
            );
            return $response->withJson($data, 200);
        } else {

            // if it not exist - inserting into db and returning result
            $addNewURL = $collection->insertOne(
                [
                    'url'=>$url,
                    'date'=>microtime(true)
                ]
            );
            // creating array with json response
            $data = array(
                'success'=> true,
                'id'=>$addNewURL->getInsertedId()
            );

            return $response->withJson($data, 200);

        }

        
    } else {
        // if body is empty
        $data = array(
                'success'=> false,
                'reason'=>'missingBody'
        );
        return $response->withJson($data, 500);
            
    }

    



})->setName('addNew');
// Run app
$app->run();