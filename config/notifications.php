<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Canal SMS (Ombala)
    |--------------------------------------------------------------------------
    | Credenciais para o serviço de SMS. Vêm do `.env`.
    | Em testes pode-se sobrepor via config()->set('notifications.sms.*', ...).
    */
    'sms' => [
        'enabled' => env('OMBALA_ENABLED', false),
        'api_url' => env('OMBALA_API_URL', 'https://api.useombala.ao'),
        'api_key' => env('OMBALA_API_KEY'),
        'sender_id' => env('OMBALA_SENDER_ID', 'gestSchool'),
    ],

];
