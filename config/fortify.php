<?php

use Laravel\Fortify\Features;

return [
    'guard' => 'web',
    'passwords' => 'users',
    'username' => 'email',
    'email' => 'email',

    'home' => '/hu',

    'prefix' => '',
    'domain' => null,

    'middleware' => ['web'],

    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
    ],

    // NONAKTIFKAN VIEWS - biar Fortify handle sendiri
    'views' => false,

    'features' => [
        // 'registration', // Comment jika tidak butuh registrasi
        'resetPasswords',
        // 'emailVerification',
        'updateProfileInformation',
        'updatePasswords',
        'twoFactorAuthentication',
    ],

];
