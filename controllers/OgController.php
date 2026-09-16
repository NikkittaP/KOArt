<?php

namespace app\controllers;

use app\helpers\OgImage;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Generates social preview cards on first request.
 *
 * The og:image URLs in the public layout point at static paths under
 * /og_cache/. Apache serves those directly once they exist; only the very
 * first request for a card (usually from a crawler, right after someone
 * pastes the link) falls through the rewrite rules to this controller, which
 * composes the card, writes it into the cache directory and streams it.
 * Every later request is a plain static file hit with a one-year cache header.
 *
 * Unknown or tampered tokens 404 rather than generating anything: the token
 * embeds a hash of the source file, which OgImage re-derives and compares.
 */
class OgController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * @param string $token e.g. "work-156-a1b2c3d4"
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionImage($token)
    {
        $path = OgImage::generate($token);

        if ($path === null || !is_file($path)) {
            // Covers a deleted work, a stale hash (the photo was replaced, so
            // the page now links a different token) and an unwritable cache
            // directory. The page's og:image simply 404s and the platform
            // falls back to no image, which is the same as before this
            // feature existed - never a 500.
            throw new NotFoundHttpException('Preview image not found.');
        }

        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'image/jpeg');
        $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        $response->headers->set('Content-Length', (string) filesize($path));
        $response->content = file_get_contents($path);

        return $response;
    }
}
