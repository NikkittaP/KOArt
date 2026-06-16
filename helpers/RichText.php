<?php

namespace app\helpers;

/**
 * Renders sanitised HTML for rich-text fields (paintings.description,
 * series.description) on the public series "blog" pages.
 *
 * Storage is already sanitised HTML (see docs/03-data-model-and-decisions.md),
 * but we purify again at render time as defence in depth — cheap, and
 * protects against any future admin-side change to how it's saved.
 *
 * Uses ezyang/htmlpurifier (already in vendor), which exposes global,
 * non-namespaced HTMLPurifier / HTMLPurifier_Config classes.
 */
class RichText
{
    /** @var \HTMLPurifier|null */
    private static $purifier;

    public static function purify(?string $html): string
    {
        if (empty($html)) {
            return '';
        }
        return self::getPurifier()->purify($html);
    }

    private static function getPurifier(): \HTMLPurifier
    {
        if (self::$purifier === null) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'p,br,strong,em,b,i,a[href|target],ul,ol,li');
            $config->set('AutoFormat.AutoParagraph', false);
            $config->set('Cache.SerializerPath', sys_get_temp_dir());
            self::$purifier = new \HTMLPurifier($config);
        }
        return self::$purifier;
    }
}
