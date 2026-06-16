<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Main application asset bundle.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/main.css',
        'css/main_mod.css',
        'css/masonry.css',
        'css/pagination.css',
        'css/intranet.css',
        'slick/slick.css',
        'slick/slick-theme.css'
    ];
    public $js = [
        'js/browser.min.js',
        'js/breakpoints.min.js',
        'js/util.js',
        'js/main.js',
        'slick/slick.min.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap4\BootstrapAsset',
    ];
}
