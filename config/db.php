<?php

return [
    'class' => 'yii\db\Connection',
    // Credentials come from .env (see .env.example). No secrets in this file.
    'dsn' => $_ENV['DB_DSN'] ?? 'mysql:host=MySQL-8.0;dbname=oskina_art',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    'enableSchemaCache' => false,
    'schemaCacheDuration' => 60,
    'schemaCache' => 'cache',
];
