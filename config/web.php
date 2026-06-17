<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'name' => 'Oskina.Art',
    'language' => 'en',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            'baseUrl' => '',
            // Secret key from .env (see .env.example). No secret in this file.
            'cookieValidationKey' => $_ENV['COOKIE_VALIDATION_KEY'] ?? '',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'logFile' => '@runtime/logs/error.log',
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'class' => 'codemix\localeurls\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            // English is the default language and has NO code in the URL.
            // Russian (added later) will live under /ru/. To add it, just keep
            // 'ru' in this list and provide ru content/translations.
            'languages' => ['en', 'ru'],
            // Default language ('en') never shows a /en/ prefix; visiting /en/...
            // redirects to the clean URL.
            'enableDefaultLanguageUrlCode' => false,
            // Do not auto-redirect by browser Accept-Language: everyone gets
            // English unless they explicitly request /ru/.
            'enableLanguageDetection' => false,
            // Language is determined ONLY by the URL. Don't remember /ru/ in a
            // cookie/session, so removing the prefix immediately returns to English.
            'enableLanguagePersistence' => false,
            'rules' => [
                '' => 'site/index',
                'about' => 'site/about',
                'contact' => 'site/contact',
                'login' => 'site/login',

                // Public series page. MUST stay before the admin rules below:
                // otherwise URL creation for the 'series/show' route matches
                // 'admin/series/<action>' first and links get an /admin/ prefix.
                'series/<id:\d+>' => 'series/show',

                // --- Admin / archive panel (Phase 4b). Must stay BEFORE the
                // generic <slug> rule so /admin/* never resolves to a section. ---
                'admin' => 'admin/index',
                'admin/lang' => 'admin/lang',
                'admin/archive' => 'admin/archive',
                'admin/works' => 'paintings/index',
                'admin/paintings' => 'paintings/index',
                'admin/series' => 'series/index',
                'admin/sections' => 'sections/index',
                'admin/genres' => 'art-genres/index',
                'admin/grounds' => 'grounds/index',
                'admin/materials' => 'materials/index',
                'admin/paintings/<action:[\w-]+>' => 'paintings/<action>',
                'admin/works/<action:[\w-]+>' => 'paintings/<action>',
                'admin/series/<action:[\w-]+>' => 'series/<action>',
                'admin/sections/<action:[\w-]+>' => 'sections/<action>',
                'admin/genres/<action:[\w-]+>' => 'art-genres/<action>',
                'admin/grounds/<action:[\w-]+>' => 'grounds/<action>',
                'admin/materials/<action:[\w-]+>' => 'materials/<action>',
                'admin/photos/<action:[\w-]+>' => 'photos/<action>',

                // Any single-segment slug maps to a section page; SiteController
                // 404s unknown slugs. Must stay AFTER the named routes above so
                // about/contact/login/series win first.
                '<slug:[a-z0-9-]+>' => 'site/section',
                '<controller:\w+>/<action:\w+>/<id:\d+>/<page:\d+>/<per-page:\d+>' => '<controller>/<action>',
                '<controller:\w+>/<id:\d+>' => '<controller>/view',
                '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>'
            ],
        ],
        'i18n' => [
            'translations' => [
                'app*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => 'ru',
                    'fileMap' => [
                        'app'       => 'app.php',
                        'app/error' => 'error.php',
                    ],
                ],
                // Admin UI strings (Phase 4b). Authored in ENGLISH in code;
                // Russian lives in messages/ru/admin.php. Separate category so it
                // never collides with the public 'app' (ru-source) strings.
                'admin' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => 'en',
                    'fileMap' => [
                        'admin' => 'admin.php',
                    ],
                ],
            ],
        ],
    ],
    'modules' => [
        'gridview' => ['class' => 'kartik\grid\Module']
    ],
    'on beforeRequest' => function ($event) {
        // Force HTTPS only in non-dev environments. On a local OSPanel http
        // domain this redirect would otherwise loop and the site never opens.
        if(!YII_ENV_DEV && !Yii::$app->request->isSecureConnection){
            $url = Yii::$app->request->getAbsoluteUrl();
            $url = str_replace('http:', 'https:', $url);
            Yii::$app->getResponse()->redirect($url);
            Yii::$app->end();
        }
    },
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
