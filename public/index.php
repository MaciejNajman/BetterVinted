<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();

$container->set('templating', function() {
  return new Mustache_Engine([
    'loader' => new Mustache_Loader_FilesystemLoader(
      __DIR__ . '/../templates',
      ['extension' => '']
      )
  ]);
});

AppFactory::setContainer($container);

$app = AppFactory::create();

$app->get('/', '\App\Controller\HomepageController:homepage');
$app->get('/vinted', '\App\Controller\VintedController:search');
$app->get('/vinted/', '\App\Controller\VintedController:search');
$app->get('/contactpage', '\App\Controller\HomepageController:contactpage');

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setErrorHandler(
  Slim\Exception\HttpNotFoundException::class,
  function (Psr\Http\Message\ServerRequestInterface $request) use ($container){
    $controller = new App\Controller\ExceptionController($container);
    return $controller->notFound($request);
});

// Set default timezone
date_default_timezone_set('Europe/Warsaw');

$app->run();
