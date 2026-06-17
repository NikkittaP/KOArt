<?php

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Asset bundle for the admin / archive panel (Phase 4b redesign).
 *
 * Mirrors PublicAsset: custom CSS only, no Bootstrap and no kartik CSS. Shares
 * the public design tokens (Jost, --ink/--soft/--muted/...) but the admin uses a
 * dark sidebar so it always reads as "admin", not "site".
 */
class AdminAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/admin.css',
    ];
    public $js = [
        'js/admin.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}
