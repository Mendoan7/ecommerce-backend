<?php

namespace App;

class ResponseFormatter
{
    protected static $response = [
        'meta' => [
            'code' => 200,
            'status' => 'success',
            'messages' => [],
            'validations' => null,
            'response_date' => null,
        ],
        'data' => null,
    ];

    public static function success($data = null, $messages = [])
    {
        self::$response['data'] = $data;
        self::$response['meta']['messages'] = $messages;
        self::$response['meta']['response_date'] = now()->format('Y-m-d H:i:s'); 

        return response()->json(self::$response, self::$response['meta']['code']);
    }

    public static function error($code = null, $validations = null, $messages = [])
    {
        self::$response['meta']['status'] = 'error';
        self::$response['meta']['code'] = $code;
        self::$response['meta']['messages'] = $messages;
        self::$response['meta']['validations'] = $validations;
        self::$response['meta']['response_date'] = now()->format('Y-m-d H:i:s'); 
        
        return response()->json(self::$response, self::$response['meta']['code']);
    }
}