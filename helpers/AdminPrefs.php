<?php

namespace app\helpers;

use Yii;
use yii\web\Cookie;

/**
 * Per-admin UI preferences kept in cookies (Phase 4b).
 *
 * hideArchive: a global toggle. When ON, archived (isVisible = 0) works and
 * series are hidden from the admin lists and excluded from the dashboard
 * counts, so the owner can focus on what is actually published. Default OFF
 * (show everything; archived rows are just visually muted) — currently all
 * works start archived, so an empty-by-default list would be confusing.
 */
class AdminPrefs
{
    const COOKIE_HIDE_ARCHIVE = 'hideArchive';

    /** Whether archived (hidden) items should be excluded from admin views. */
    public static function hideArchive()
    {
        return Yii::$app->request->cookies->getValue(self::COOKIE_HIDE_ARCHIVE) === '1';
    }

    /** Persist the toggle for ~1 year. */
    public static function setHideArchive($on)
    {
        Yii::$app->response->cookies->add(new Cookie([
            'name' => self::COOKIE_HIDE_ARCHIVE,
            'value' => $on ? '1' : '0',
            'expire' => time() + 86400 * 365,
            'httpOnly' => true,
        ]));
    }
}
