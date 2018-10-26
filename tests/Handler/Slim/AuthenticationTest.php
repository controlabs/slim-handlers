<?php

namespace Controlabs\Test\SlimHandler;

use Controlabs\Helper\JWT;
use Controlabs\Http\Exception\Unauthorized;
use Controlabs\Handler\Slim\Authentication;
use Controlabs\Test\AbstractTestCase;
use Slim\Http\Request;
use Slim\Http\Response;

class AuthenticationTest extends AbstractTestCase
{
    public function testAuthenticationHandlerWithoutToken()
    {
        $this->expectException(Unauthorized::class);
        $this->expectExceptionMessage('Unauthorized');

        $jwtHelper = $this->mock(JWT::class);
        $request = $this->mock(Request::class);
        $response = $this->mock(Response::class);

        $handler = new Authentication($jwtHelper);

        $handler($request, $response, null);
    }

    public function testAuthenticationWithExpiredToken()
    {
        $this->expectException(Unauthorized::class);
        $this->expectExceptionMessage('Unauthorized');

        $jwtHelper = $this->mock(JWT::class);
        $request = $this->mock(Request::class);
        $response = $this->mock(Response::class);

        $request
            ->expects($this->once())
            ->method('getHeader')
            ->with('Authorization')
            ->willReturn(['TEST_TOKEN']);

        $jwtHelper
            ->expects($this->once())
            ->method('decode')
            ->with('TEST_TOKEN', true)
            ->willReturn(null);

        $handler = new Authentication($jwtHelper);

        $handler($request, $response, null);
    }

    public function testAuthenticationWithValidToken()
    {
        $jwtHelper = $this->mock(JWT::class);
        $request = $this->mock(Request::class);
        $response = $this->mock(Response::class);

        $payloadExample = [
            'iss' => 'iss_test',
            'aud' => 'aud_test',
            'sub' => 'sub_test',
            'exp' => 'exp_test',
            'user_id' => 'user_id',
            'another_info' => 'another_info'
        ];

        $request
            ->expects($this->once())
            ->method('getHeader')
            ->with('Authorization')
            ->willReturn(['TEST_TOKEN']);

        $jwtHelper
            ->expects($this->once())
            ->method('decode')
            ->with('TEST_TOKEN', true)
            ->willReturn($payloadExample);

        $request
            ->expects($this->once())
            ->method('withAttributes')
            ->with([
                'user_id' => 'user_id',
                'another_info' => 'another_info'
            ])
            ->willReturnSelf();

        $nextIsCalled = false;

        $next = function (Request $requestTest, Response $responseTest) use ($request, $response, &$nextIsCalled) {
            $this->assertSame($requestTest, $request);
            $this->assertSame($responseTest, $response);

            $nextIsCalled = true;

            return $responseTest;
        };

        $handler = new Authentication($jwtHelper);

        $this->assertSame($response, $handler($request, $response, $next));

        $this->assertTrue($nextIsCalled);
    }
}