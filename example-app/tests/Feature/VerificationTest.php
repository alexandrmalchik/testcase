<?php

namespace Tests\Feature;

use App\Http\Controllers\VerificationController;
use App\Models\User;
use App\Models\Verification;
use App\Services\VerifyService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_verify_method_with_invalid_data()
    {
        $serviceMock = Mockery::mock(VerifyService::class);
        $serviceMock->shouldReceive('jsonParser')->andReturn(['error' => 'Invalid data']);

        $controller = new VerificationController($serviceMock);
        $request = Request::create('/verify', 'POST', []);

        $response = $controller->verify($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Invalid data', $response->getData(true));
    }

    public function test_verify_method_with_valid_data_but_not_verified()
    {
        $serviceMock = Mockery::mock(VerifyService::class);
        $serviceMock->shouldReceive('jsonParser')->andReturn(['data' => ['issuer' => ['name' => 'Test Issuer']]]);
        $serviceMock->shouldReceive('checkJsonData')->andReturn('not verified');

        $controller = new VerificationController($serviceMock);
        $request = Request::create('/verify', 'POST', []);

        $response = $controller->verify($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([
            'data' => [
                'issuer' => 'Test Issuer',
                'result' => 'not verified',
            ]
        ], $response->getData(true));
    }

    public function test_verify_method_with_exception()
    {
        $this->expectException(Exception::class);

        $serviceMock = Mockery::mock(VerifyService::class);
        $serviceMock->shouldReceive('jsonParser')->andReturn(['data' => ['issuer' => ['name' => 'Test Issuer']]]);
        $serviceMock->shouldReceive('checkJsonData')->andReturn('verified');

        $controller = new VerificationController($serviceMock);
        $request = Request::create('/verify', 'POST', []);
        $userMock = Mockery::mock('alias:App\Models\User');
        $userMock->shouldReceive('id')->andReturn(1);
        $request->setUserResolver(function () use ($userMock) {
            return $userMock;
        });

        Verification::shouldReceive('create')->andThrow(new Exception('Database error'));

        $controller->verify($request);
    }
}
