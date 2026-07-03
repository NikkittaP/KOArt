<?php

return [
    'class' => 'yii\db\Connection',
    // Credentials come from .env (see .env.example). No secrets in this file.
    'dsn' => $_ENV['DB_DSN'] ?? 'mysql:host=MySQL-8.0;dbname=oskina_art',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8mb4',

    // Schema cache: enabled outside dev (avoids information_schema hits on every
    // request in production); disabled in dev so schema changes are picked up
    // immediately without clearing the cache.
    'enableSchemaCache' => !YII_ENV_DEV,
    'schemaCacheDuration' => 3600,
    'schemaCache' => 'cache',
];
