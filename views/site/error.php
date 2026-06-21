<?php

/**
 * Public-facing error page (404, 403, 500, …). Rendered with
 * views/layouts/public.php so it matches the portfolio design instead of
 * the old admin layout. See SiteController::actions()['error'].
 *
 * @var yii\web\View $this
 * @var string $name
 * @var string $message
 * @var Exception $exception
 */

use yii\helpers\Html;
use yii\helpers\Url;

$statusCode = ($exception instanceof \yii\web\HttpException) ? $exception->statusCode : null;
$isNotFound = $statusCode === 404;

$this->title = $name;
?>
<header class="shead error-head">
    <?php if ($statusCode): ?>
        <div class="error-code"><?= Html::encode($statusCode) ?></div>
    <?php endif; ?>
    <h1>
        <?= $isNotFound
            ? 'Page not found'
            : Html::encode($name) ?>
    </h1>
    <p>
        <?= $isNotFound
            ? 'Sorry, the page you are looking for doesn’t exist or has been moved.'
            : nl2br(Html::encode($message)) ?>
    </p>
</header>

<a class="inquire" href="<?= Url::to(['/']) ?>">Back to home</a>
