<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class VerifyService
{
    public function jsonParser(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimetypes:application/json,text/plain,text/html,text/oa|max:2048', // 2048 KB = 2 MB
        ]);

        if ($validator->fails()) {
            return [$validator->errors()];
        }


        $file = $request->file('file');
        $jsonContent = json_decode(file_get_contents($file->getRealPath(), true), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['Invalid JSON format'];
        }

        return $jsonContent;
    }

    public function checkJsonData(array $validationData): string
    {
        $validationResult = $this->validationVerificationJson($validationData);

        if ($validationResult !== 'verified') {
            return $validationResult;
        }

        $checkResult = $this->checkDnsAndSignature($validationData);

        return $checkResult;
    }

    public function validationVerificationJson(array $data): string
    {
        $validator = Validator::make($data, [
            "data" => "array|required",
            "data.id" => "string",
            "data.name" => "string",
            "data.recipient" => "array|required",
            "data.recipient.name" => "string|required",
            "data.recipient.email" => "string|required",
            "data.issuer.name" => "string",
            "data.issuer.identityProof" => "array|required",
            "data.issuer.identityProof.type" => "string|required",
            "data.issuer.identityProof.key" => "string|required",
            "data.issuer.identityProof.location" =>  "string|required",
            "data.issued" => "required",
            "signature" => "array|required",
            "signature.type" => "string|required",
            "signature.targetHash" => "string|required",
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();

            if ($errors->has('data.recipient.*')) {
                return 'invalid_recipient';
            }
            if ($errors->has('data.issuer.*')) {
                return 'invalid_issuer';
            }
            if ($errors->has('signature.*')) {
                return 'invalid_signature';
            }

            return 'invalid_json';
        }

        return 'verified';
    }

    public function checkDnsAndSignature(array $data): string
    {
        $result = '';
        $dnsResponse = Http::get('https://dns.google/resolve', [
            'name' => $data['data']['issuer']['identityProof']['location'],
            'type' => 'TXT'
        ]);

        $dnsRecords = collect($dnsResponse->json()['Answer'] ?? [])->pluck('data')->toArray();
        $key = $data['data']['issuer']['identityProof']['key'];

        foreach ($dnsRecords as $dnsRecord) {
            if (strpos($dnsRecord, $key) === false) {
                $result = 'invalid_issuer';
            } else {
                $result = 'verified';
                break;
            }
        }

        if ($result !== 'verified') {
            return 'invalid_issuer';
        }

        $pathsAndValues = $this->arrayKeyModification($data['data']);

        // Hashing, sorting and merging hashes
        $hashes = array_map(function ($key, $value) {
            return hash('sha256', json_encode([$key => $value]));
        }, array_keys($pathsAndValues), $pathsAndValues);

        sort($hashes);
        $hashes = array_map('trim', $hashes);

        $computedHash = hash('sha256', implode('', $hashes));

        if ($computedHash !== $data['signature']['targetHash']) {
            $result = 'invalid_signature';
        } else {
            $result = 'verified';
        }

        return $result;
    }

    public function arrayKeyModification($array, $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $path = $prefix === '' ? $key : "$prefix.$key";
            if (is_array($value)) {
                $result += $this->arrayKeyModification($value, $path);
            } else {
                $result[$path] = $value;
            }
        }
        return $result;
    }
}
