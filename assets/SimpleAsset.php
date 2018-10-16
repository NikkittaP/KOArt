<?php

namespace app\assets;

use yii\web\AssetBundle;

class SimpleAsset extends AssetBundle {
  public $sourcePath = __DIR__ . '/../themes/simple';

  public $css = [
    'css/portfolio-item.css'
  ];

  public $depends = [
    'app\assets\AppAsset',
  ];
}

?>