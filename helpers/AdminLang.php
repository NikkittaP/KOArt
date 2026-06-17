<?php

namespace app\helpers;

use Yii;
use yii\web\Cookie;

/**
 * Admin UI language (Phase 4b).
 *
 * The public site is English-only and localeurls persistence/detection are OFF,
 * so we must NOT change Yii::$app->language globally via the URL. Instead the
 * admin panel keeps its own language in a cookie and applies it only on admin
 * requests. Supported: 'en' (default) and 'ru'.
 */
class AdminLang
{
    const COOKIE = 'adminLang';
    const SUPPORTED = ['en', 'ru'];
    const DEFAULT = 'ru'; // owner is Russian-speaking; toggle to EN any time

    /** Current admin language from the cookie (falls back to default). */
    public static function current()
    {
        $v = Yii::$app->request->cookies->getValue(self::COOKIE);
        return in_array($v, self::SUPPORTED, true) ? $v : self::DEFAULT;
    }

    /** Apply the admin language to the running request. */
    public static function apply()
    {
        Yii::$app->language = self::current();
    }

    /** Persist a new admin language (validated) for ~1 year. */
    public static function set($lang)
    {
        if (!in_array($lang, self::SUPPORTED, true)) {
            $lang = self::DEFAULT;
        }
        Yii::$app->response->cookies->add(new Cookie([
            'name' => self::COOKIE,
            'value' => $lang,
            'expire' => time() + 86400 * 365,
            'httpOnly' => true,
        ]));
        Yii::$app->language = $lang;
    }
}
