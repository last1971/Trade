<?php

return [
    'checkToken' => [
        'token' => 'required|string',
    ],
    'forgot' => [
        'email' => 'required|string|email'
    ],
    'login' => [
        'email' => 'required|string|email|max:255',
        'password' => 'required|string|min:6',
    ],
    'register' => [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:6|confirmed',
    ],
    'resetPassword' => [
        'email' => 'required|string|email',
        'password' => 'required|string|min:6|confirmed',
        'token' => 'required|string'
    ],
];
