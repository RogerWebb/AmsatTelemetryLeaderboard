<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

require __DIR__.'/vendor/autoload.php';

$subdir = substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT']));

$app = AppFactory::create();
//$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$twig = Twig::create(__DIR__.'/view');
$twig->getEnvironment()->addGlobal('subdir', $subdir);
$app->add(TwigMiddleware::create($app, $twig));

$app->group($subdir, function(RouteCollectorProxy $r) {
    $r->get('/', function(Request $request, Response $response, array $args) {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'index.html');
    });

    $r->get('/map/{name}', function (Request $request, Response $response, array $args) {
        $name = $args['name'];

        if(!in_array($name, ['rx-ground-stations', 'ground-station-passes'])) {
            print("<b>ERROR: Invalid Map Name</b>");
            die();
        }

        $view = Twig::fromRequest($request);

        return $view->render($response, 'map.html', ['name' => $name]);
    });

    $r->post('/map/api/rx-ground-stations', function (Request $request, Response $response, array $args) {
        $conn_mgr = new \Amsat\FoxDb\ConnectionManager();
        $lb = new \Amsat\Telemetry\LeaderBoard($conn_mgr, true);

        $last_x     = $_REQUEST['last_x'];
        $spacecraft = $_REQUEST['spacecraft'];

        if(!in_array($last_x, ['-90 minutes', '-24 hours', '-30 days'])) {
            $response = ['status' => 'error', 'error' => 'Invalid last_x'];
        } elseif($spacecraft < 0 || $spacecraft > 6) {
            $response = ['status' => 'error', 'error' => 'Invalid spacecraft'];
        } else {
            $start = clone $lb->getDefaultEndDateTime();
            $start->modify($last_x);

            $result = $lb->groundStationMapSearch($spacecraft, $start);
        }

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    });
});

$app->run();


