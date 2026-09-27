<?php

namespace app\controllers;

use app\helpers\PortfolioPdf;
use app\models\Paintings;
use app\models\Sections;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ServiceUnavailableHttpException;

/**
 * Public section PDF portfolio: /portfolio/<slug>.pdf. English only, whatever
 * page linked here. The PDF is built and cached by app\helpers\PortfolioPdf.
 */
class PortfolioController extends Controller
{
    public function actionSection($slug)
    {
        Yii::$app->language = 'en';

        $section = Sections::find()->where(['slug' => $slug])->one();
        if (!$section) {
            throw new NotFoundHttpException('The requested section does not exist.');
        }
        $works = Paintings::findForSectionMosaic($section->id)->all();
        if (!PortfolioPdf::hasContent($works)) {
            throw new NotFoundHttpException('This section has no portfolio yet.');
        }

        try {
            $path = PortfolioPdf::get($section, $works);
        } catch (\Throwable $e) {
            Yii::error($e, __METHOD__);
            throw new ServiceUnavailableHttpException('The portfolio could not be prepared right now. Please try again in a minute.');
        }

        $response = Yii::$app->response;
        $response->headers->set('Cache-Control', 'public, max-age=3600');
        return $response->sendFile($path, PortfolioPdf::downloadName($section), [
            'mimeType' => 'application/pdf',
            'inline' => true,
        ]);
    }
}
