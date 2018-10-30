<?php

namespace Controlabs\Handler\Slim;

use FastRoute\Dispatcher;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Router;

class Cors
{
    const HEADERS = [
        'Content-Type',
        'Accept',
        'Origin',
        'Authorization',
        'X-Requested-With'
    ];

    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function __invoke(Request $request, Response $response, $next)
    {
        if ($next && is_callable($next)) {
            $response = $next($request, $response);
        }

        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', implode(',', self::HEADERS))
            ->withHeader('Access-Control-Allow-Methods', implode(',', $this->methods($request)));
    }

    protected function methods(Request $request)
    {
        $methods = $this->optionsMethods($request);

        $currentRoute = $request->getAttribute('route');

        if (empty($currentRoute)) {
            return array_merge($methods, [$request->getMethod()]);
        }

        $pattern = $currentRoute->getPattern();

        foreach ($this->router()->getRoutes() as $route) {
            $pattern === $route->getPattern()
            and $methods = array_merge_recursive($methods, $route->getMethods());
        }

        return $methods;
    }

    protected function optionsMethods(Request $request)
    {
        if ('OPTIONS' !== $request->getMethod()) {
            return [];
        }

        list($status, $allowedMethods) = $this->router()->dispatch($request);

        return Dispatcher::METHOD_NOT_ALLOWED == $status ? $allowedMethods : [];
    }

    protected function router(): Router
    {
        return $this->container->get('router');
    }
}
