<?php

namespace Controlabs\Handler\Slim;

use Controlabs\Http\Exception\Unauthorized;
use Slim\Http\Request;
use Slim\Http\Response;
use Controlabs\Helper\JWT as JWTHelper;

class Authentication
{
    private $jwtHelper;
    private $public;

    public function __construct(JWTHelper $jwtHelper, array $public = [])
    {
        $this->jwtHelper = $jwtHelper;
        $this->public = $public;
    }

    public function __invoke(Request $request, Response $response, $next)
    {
        try {
            $token = $this->token($request);
            $payload = $this->decodeToken($token);

            unset($payload['iss']);
            unset($payload['aud']);
            unset($payload['sub']);
            unset($payload['exp']);

            $request = $request->withAttributes($payload);
        } catch (Unauthorized $exception) {
            $this->handleException($request, $exception);
        }

        return $next($request, $response);
    }

    protected function handleException(Request $request, Unauthorized $unauthorized)
    {
        if (!$this->isPublic($request)) {
            throw $unauthorized;
        }
    }

    protected function isPublic(Request $request)
    {
        $route = $request->getAttribute('route') ?: '';

        $patterns = $this->getPublicRoutesByMethod($request->getMethod());

        foreach ($patterns as $pattern) {
            if ($this->match($pattern, $route->getPattern())) {
                return true;
            }
        }

        return false;
    }

    protected function decodeToken($token)
    {
        $payload = $this->jwtHelper->decode($token, true);

        if (!$payload) {
            throw new Unauthorized();
        }

        return $payload;
    }

    protected function token(Request $request)
    {
        $token = $request->getHeader('Authorization')[0] ?? null;

        if (!$token) {
            throw new Unauthorized();
        }

        return $token;
    }

    protected function getPublicRoutesByMethod($method)
    {
        /*
         * 0 - Method
         * 1 - Pattern
         */

        $map = function (array $public) use ($method) {
            return $this->match($public[0], $method) ? $public[1] : '';
        };

        $patterns = array_map($map, $this->public);
        $patterns = array_filter($patterns);
        $patterns = array_values($patterns);

        return $patterns;
    }

    protected function match($pattern, $str)
    {
        $pattern = str_replace("/", "\/", $pattern);
        $pattern = "/{$pattern}/";

        try {
            return !!preg_match($pattern, $str);
        } catch (\Exception $exception) {
            throw new \Exception('Invalid RegExp: ' . $pattern, 0, $exception);
        }
    }
}
