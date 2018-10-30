<?php

namespace Controlabs\Test\Handler\Slim;

use Controlabs\Handler\Slim\Cors;
use Controlabs\Test\Handler\Slim\Fixture\NextHandler;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Route;
use Slim\Router;

class CorsTest extends \Controlabs\Test\AbstractTestCase
{
    protected $container;
    protected $request;
    protected $response;

    protected function setUp()
    {
        $this->container = $this->container();
        $this->request = $this->mock(Request::class);
        $this->response = new Response();
    }

    public function testEmptyRoute()
    {
        $this->request
            ->expects($this->once())
            ->method('getAttribute')
            ->with('route')
            ->willReturn([]);
        $this->request
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        $handler = new Cors($this->container);
        $resp = $handler($this->request, $this->response, null);
        $headers = $resp->getHeaders();
        $this->assertEquals($headers['Access-Control-Allow-Origin'], ['*']);
        $this->assertEquals($headers['Access-Control-Allow-Headers'], [implode(',', Cors::HEADERS)]);
        $this->assertEquals($headers['Access-Control-Allow-Methods'], ['GET']);
    }

    public function testRoutes()
    {
        $route = $this->mock(Route::class);
        $route
            ->expects($this->once())
            ->method('getPattern')
            ->willReturn('/ping');
        $this->request
            ->expects($this->once())
            ->method('getAttribute')
            ->with('route')
            ->willReturn($route);

        $routeRouter = $this->mock(Route::class);
        $routeRouter
            ->expects($this->once())
            ->method('getPattern')
            ->willReturn('/ping');
        $routeRouter
            ->expects($this->once())
            ->method('getMethods')
            ->willReturn(['GET', 'POST']);
        $router = $this->mock(Router::class);
        $router
            ->expects($this->once())
            ->method('getRoutes')
            ->willReturn([$routeRouter]);

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('router')
            ->willReturn($router);

        $handler = new Cors($this->container);
        $resp = $handler($this->request, $this->response, null);
        $headers = $resp->getHeaders();
        $this->assertEquals($headers['Access-Control-Allow-Origin'], ['*']);
        $this->assertEquals($headers['Access-Control-Allow-Headers'], [implode(',', Cors::HEADERS)]);
        $this->assertEquals($headers['Access-Control-Allow-Methods'], ['GET,POST']);
    }

    public function testNext()
    {
        $nextHandler = new NextHandler();
        $handler = new Cors($this->container);
        $resp = $handler($this->request, $this->response, $nextHandler);
        $headers = $resp->getHeaders();
        $this->assertEquals($headers['Next-Handler-Executed'], [true]);
    }
}
