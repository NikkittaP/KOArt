<?php

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Asset bundle for the public-facing portfolio frontend (Phase 3).
 *
 * Deliberately separate from AppAsset (used by the old admin layout) so the
 * admin screens are not touched. Files are named public.css/public.js (not
 * site.css/site.js) to avoid colliding with the legacy web/css/site.css and
 * to defeat aggressive mobile HTML/asset caching on first deploy.
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
    public $depends = [
        'yii\web\YiiAsset',
    ];
}
