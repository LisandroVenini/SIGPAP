<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Administrador inicial
    |--------------------------------------------------------------------------
    |
    | Credenciales del usuario administrador que se crea únicamente en el
    | entorno local mediante el seeder. Si no se define una contraseña, el
    | seeder genera una aleatoria y la muestra una sola vez.
    |
    */

    'admin' => [
        'email' => env('SIGPAP_ADMIN_EMAIL', 'admin@sigpap.local'),
        'password' => env('SIGPAP_ADMIN_PASSWORD'),
    ],

];
