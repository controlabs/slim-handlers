<?php

namespace Controlabs\Handler\Slim;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

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

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(Request $request, Response $response, $next)
    {
        if ($next && is_callable($next)) {
            $response = $next($request, $response);
        }

        $methods = $this->methods($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', implode(',', self::HEADERS))
            ->withHeader('Access-Control-Allow-Methods', implode(',', $methods));
    }

    private function methods(Request $request): array
    {
        $methods = [];

        if ($route = $request->getAttribute('route')) {
            $pattern = $route->getPattern();

            $router = $this->container->get('router');
            foreach ($router->getRoutes() as $route) {
                if ($pattern === $route->getPattern()) {
                    $methods = array_merge_recursive($methods, $route->getMethods());
                }
            }
        } else {
            // Methods holds all of the HTTP Verbs that a particular route handles.
            $methods[] = $request->getMethod();
        }
        return $methods;
    }
}
