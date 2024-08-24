<?php

namespace App\Http\Controllers;

use App\Models\Verification;
use App\Services\VerifyService;
use Exception;
use http\Env\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function __construct(VerifyService $service)
    {
        $this->verifyService = $service;
    }

    public function verify(Request $request): JsonResponse
    {
        $parserResult = $this->verifyService->jsonParser($request);

        if (!isset($parserResult['data'])) {
            return response()->json(
                $parserResult[0] ?? 'Invalid data',
                200
            );
        }

        $validData = $this->verifyService->checkJsonData($parserResult);
        $name = $parserResult['data']['issuer']['name'];

        if ($validData !== 'verified') {
            return response()->json([
                'data' => [
                    'issuer' => $name,
                    'result' => $validData,
                ]
            ], 200);
        }

        try {
            Verification::create([
                'user_id' => $request->user()->id,
                'file_type' => 'json',
                'result' => $validData,
                'created_at' => now(),
            ]);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
//
        return response()->json([
            'data' => [
                'issuer' => $name,
                'result' => $validData,
            ]
        ], 200);
    }
}
