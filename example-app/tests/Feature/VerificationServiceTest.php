<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use App\Services\VerifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VerificationServiceTest extends TestCase
{
    public function test_json_parser_with_invalid_file()
    {
        $request = Request::create('/verify', 'POST', [], [], [
            'file' => new UploadedFile(__DIR__ . '/_files/invalid.pdf', 'invalid')
        ]);

        $service = new VerifyService();
        $result = $service->jsonParser($request);

        $this->assertArrayHasKey('file', $result[0]->messages());
    }

    public function test_check_json_data_with_invalid_data()
    {
        $service = new VerifyService();
        $validationData = ['data' => ['recipient' => ['name' => '']]];

        $result = $service->checkJsonData($validationData);

        $this->assertEquals('invalid_recipient', $result);
    }

    public function test_validation_verification_json_with_invalid_data()
    {
        $service = new VerifyService();
        $data = ['data' => ['recipient' => ['name' => '']]];

        $result = $service->validationVerificationJson($data);

        $this->assertEquals('invalid_recipient', $result);
    }

    public function test_validation_verification_json_with_valid_data()
    {
        $service = new VerifyService();
        $data = [
            'data' => [
                'recipient' => ['name' => 'Test', 'email' => 'test@example.com'],
                'issuer' => ['identityProof' => ['type' => 'DNS', 'key' => 'test_key', 'location' => 'test_location']],
                'issued' => '2023-01-01',
            ],
            'signature' => ['type' => 'SHA256', 'targetHash' => 'test_hash']
        ];

        $result = $service->validationVerificationJson($data);

        $this->assertEquals('verified', $result);
    }

    public function test_check_dns_and_signature_with_invalid_issuer()
    {
        $service = new VerifyService();
        $data = [
            'data' => [
                'recipient' => ['name' => 'Test', 'email' => 'test@example.com'],
                'issuer' => ['identityProof' => ['type' => 'DNS', 'key' => 'invalid_key', 'location' => 'test_location']],
                'issued' => '2023-01-01',
            ],
            'signature' => ['type' => 'SHA256', 'targetHash' => 'test_hash']
        ];

        Http::fake([
            'https://dns.google/resolve' => Http::response(['Answer' => [['data' => 'test_key']]], 200)
        ]);

        $result = $service->checkDnsAndSignature($data);

        $this->assertEquals('invalid_issuer', $result);
    }

    public function test_array_key_modification()
    {
        $service = new VerifyService();
        $array = [
            'data' => [
                'recipient' => ['name' => 'Test', 'email' => 'test@example.com'],
                'issuer' => ['identityProof' => ['type' => 'DNS', 'key' => 'test_key', 'location' => 'test_location']],
                'issued' => '2023-01-01',
            ]
        ];

        $result = $service->arrayKeyModification($array);

        $expected = [
            'data.recipient.name' => 'Test',
            'data.recipient.email' => 'test@example.com',
            'data.issuer.identityProof.type' => 'DNS',
            'data.issuer.identityProof.key' => 'test_key',
            'data.issuer.identityProof.location' => 'test_location',
            'data.issued' => '2023-01-01',
        ];

        $this->assertEquals($expected, $result);
    }

}
