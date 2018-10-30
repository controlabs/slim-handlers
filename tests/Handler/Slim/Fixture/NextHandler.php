<?php

namespace Controlabs\Test\Handler\Slim\Fixture;

use Slim\Http\Request;
use Slim\Http\Response;

class NextHandler
{
    public function __invoke(Request $request, Response $response)
    {
        return $response
            ->withHeader('Next-Handler-Executed', true);
    }
}
