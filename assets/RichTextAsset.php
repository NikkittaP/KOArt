<?php

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Loads a lightweight WYSIWYG editor (CKEditor 4, "basic" build, from CDN)
 * and auto-attaches it to every `textarea.rich-text-editor` on the page.
 *
 * The toolbar is intentionally restricted to bold/italic/lists/links so the
 * HTML produced stays within what app\helpers\RichText::purify() allows
 * (p, br, strong, em, b, i, a[href|target], ul, ol, li). No headings,
 * images, tables, or source editing — those would just get stripped on
 * save, which would be confusing for the editor.
 */
class RichTextAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = [
        'https://cdn.ckeditor.com/4.22.1/basic/ckeditor.js',
        'js/rich-text-editor-init.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}
