<?php

// Copy this file to db.php and fill in real values. db.php is gitignored.
//
// OSPanel 6: the DB host is the *module name* (e.g. MySQL-8.0), NOT "localhost".
// Local default user = root with empty password.
// (Secrets will move to a .env file in the next Phase 1 step.)

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=MySQL-8.0;dbname=oskina_art',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',

    'enableSchemaCache' => false, // set true in production
    'schemaCacheDuration' => 60,
    'schemaCache' => 'cache',
];
