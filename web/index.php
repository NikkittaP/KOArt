<?php

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env (if present).
(\Dotenv\Dotenv::createImmutable(dirname(__DIR__)))->safeLoad();

defined('YII_DEBUG') or define('YII_DEBUG', filter_var($_ENV['YII_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN));
defined('YII_ENV') or define('YII_ENV', $_ENV['YII_ENV'] ?? 'dev');

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
