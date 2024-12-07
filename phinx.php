<?php
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
return [
    "paths" => [
        "migrations" => "database/migrations",
        "seeds" => "database/seeds"
    ],
    "environments" => [
        "default_migration_table" => "phinxlog",
        "default_environment" => "development",
        "development" => [
            "adapter" => "mysql",
            "host" => $_SERVER['DB_HOST'],
            "name" => $_SERVER['DB_USER'],
            "user" => $_SERVER['DB_NAME'],
            "pass" => $_SERVER['DB_PASSWORD'],
            "port" => $_SERVER['DB_PORT'],
            "charset" => "utf8mb4"
        ]
    ]
];
