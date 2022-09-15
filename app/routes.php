<?php
declare(strict_types=1);

use App\Application\Actions\Product\ListProductsAction;
use App\Application\Actions\Product\ViewProductAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/out/api/public/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/out/api/public/', function (Request $request, Response $response) {
        $response->getBody()->write('<h4>SCT API</h4> <hr> <p> For More Info Enter <code>/readme.md</code></p>');
        return $response;
    });

    $app->get('/out/api/public/readme.md', function (Request $request, Response $response) {
        $response->getBody()->write("
            <h2>SCT API</h2>
            <hr>
            <h4>Use your sctAccessKey</h4>
            <p>Do not forget to add header on GET request <code>sctAccessKey: YOUR_API_KEY </code></p>
            <h4>How to use?</h4>
            <p>/products or /products/:id</p>
            <h4>What it will return?</h4>
            <p><code>{'statusCode': 200, data: []}</code></p>
        ");
        return $response;
    });

    $app->group('/out/api/public/products', function (Group $group) {
        $group->get('', ListProductsAction::class);
        $group->get('/{id}', ViewProductAction::class);
//    });
};
