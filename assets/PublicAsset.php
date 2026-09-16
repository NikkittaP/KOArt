<?php

namespace app\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Asset bundle for the public-facing portfolio frontend (Phase 3).
 *
 * Separate from AdminAsset so the admin screens are not touched. Files are
 * named public.css/public.js to defeat aggressive mobile HTML/asset caching
 * on first deploy.
 *
 * Deliberately has NO $depends. It used to depend on yii\web\YiiAsset, which
 * dragged jQuery (285 KB uncompressed) plus yii.js onto every public page —
 * for nothing, because public.js is plain vanilla JS. The only markup that
 * still needs yii.js is the logout link in the signed-in admin bar, so the
 * layout registers YiiAsset on its own when a user is logged in.
 */
class PublicAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/public.css',
    ];
    public $js = [
        'js/public.js',
    ];
    public $jsOptions = [
        // Nothing in public.js runs before DOMContentLoaded, so it must never
        // block the parser.
        'defer' => true,
    ];
    public $depends = [];
}
