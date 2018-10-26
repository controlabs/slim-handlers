<?php

namespace Controlabs\SlimHandler;

use Controlabs\Http\Exception\Unauthorized;
use Slim\Http\Request;
use Slim\Http\Response;
use Controlabs\Helper\JWT as JWTHelper;

class Authentication
{
    private $jwtHelper;

    public function __construct(JWTHelper $jwtHelper)
    {
        $this->jwtHelper = $jwtHelper;
    }

    public function __invoke(Request $request, Response $response, $next)
    {
        $token = $this->token($request);
        $payload = $this->decodeToken($token);

        unset($payload['iss']);
        unset($payload['aud']);
        unset($payload['sub']);
        unset($payload['exp']);

        $request = $request->withAttributes($payload);

        return $next($request, $response);
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
}
