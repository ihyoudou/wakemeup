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
    // $twig = Twig::create('../templates/', ['cache' => false]); //disabling cache for debuging
    // Add Twig-View Middleware
    $app->add(TwigMiddleware::create($app, $twig));

    // Define named route
    $app->get('/', function ($request, $response, $args) {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'index.html', [
            'hcaptchaSiteKey'=>$_ENV['hcaptchaSiteKey'],
            'appname' => $_ENV['APP_NAME']
        ]);
    })->setName('index');

    // /api/v1/getURLcount GET endpoint
    // this endpoint is used to get current documents count in db
    $app->get('/api/v1/getURLcount', function ($request, $response, $args) {
        global $collection;
        $count = $collection->count();
        $jsonData = array(
            'count'=>$count
        );
        return $response->withJson($jsonData, 200);
    })->setName('getURLcount');

    // /api/v1/cron GET endpoint
    // this endpoint is used to executing pinging to websites
    // it is required to provide secret that is set in .env file to execute
    $app->get('/api/v1/cron', function ($request, $response, $args) {
        $secret = $request->getQueryParams()['secret'];
        if($secret == $_ENV['APP_CRONSECRET']){
            global $collection;
            $documents = $collection->find()->toArray();
            foreach ($documents as $entry) {
                $url = $entry['url'];

                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3); 
                curl_setopt($curl, CURLOPT_TIMEOUT, 3); //timeout in seconds
                curl_setopt($curl, CURLOPT_USERAGENT, $_ENV['USERAGENT']);
                $resp = curl_exec($curl);
                curl_close($curl);
                
                echo $entry['url'],"<br>";
            }
            return $response->write('executing...');
        } else {
            return $response->withStatus(403);
        }
        
    })->setName('cron');

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
        if(!empty($data['url']) && filter_var($url, FILTER_VALIDATE_URL)){

            $verifyingCaptcha = validateCaptcha($hcaptchaResponse);

            if($verifyingCaptcha){

                $checkIfExist = $collection->find( [ 'url' => $url] )->toArray();
                // checking if url is not already in db
                if(!empty($checkIfExist[0]['url'])){
                    // if it is, returning 200 http code with success = false
                    $data = array(
                        'success'=> false,
                        'reason'=>'urlExist'
                    );
                    return $response->withJson($data, 200);

                } else { // if it not exist - inserting into db and returning result
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
            } else { // if captcha validation failed
                $data = array(
                    'success'=> false,
                    'reason'=>'captchaInvalid'
                );
                return $response->withJson($data, 200);
            }
            
        } else { // if body is empty
            $data = array(
                    'success'=> false,
                    'reason'=>'missingBodyOrBadURL'
            );
            return $response->withJson($data, 200);
                
        }

    })->setName('addNew');

    function validateCaptcha($response){
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
        if($responseData->success) {
            // your success code goes here
            return true;
        } 
        else {
            // return error to user; they did not pass
            return false;
        }
    }


    // Run app
    $app->run();