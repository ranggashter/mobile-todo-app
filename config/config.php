<?php
$host = $_SERVER['HTTP_HOST'];
$base_url = "http://{$host}/mobile_todo_app/";

return [
    'app' => [
        'base_url' => $base_url
    ],
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => '3306',
        'name'    => 'mobile_todo',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4'
    ]
];