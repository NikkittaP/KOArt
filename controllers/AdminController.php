<?php

namespace app\controllers;

use app\helpers\AdminLang;
use app\helpers\AdminPrefs;
use app\models\ArtGenres;
use app\models\Grounds;
use app\models\Materials;
use app\models\Paintings;
use app\models\Sections;
use app\models\Series;
use Yii;
use yii\filters\AccessControl;
use yii\web\Response;

/**
 * Admin dashboard + admin-wide actions (Phase 4b). Reached at /admin.
 */
class AdminController extends AdminBaseController
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /** Dashboard: at-a-glance counts + quick actions. */
    public function actionIndex()
    {
        $hideArchive = AdminPrefs::hideArchive();

        $worksTotal = (int) Paintings::find()->count();
        $worksVisible = (int) Paintings::find()->where(['isVisible' => 1])->count();
        $seriesTotal = (int) Series::find()->count();
        $seriesVisible = (int) Series::find()->where(['isVisible' => 1])->count();

        $this->view->params['adminNav'] = 'dashboard';

        return $this->render('index', [
            // When the archive is hidden, the headline numbers reflect only what
            // is published; otherwise they show the full archive with a breakdown.
            'hideArchive' => $hideArchive,
            'worksTotal' => $hideArchive ? $worksVisible : $worksTotal,
            'worksVisible' => $worksVisible,
            'worksHidden' => $worksTotal - $worksVisible,
            'seriesTotal' => $hideArchive ? $seriesVisible : $seriesTotal,
            'seriesVisible' => $seriesVisible,
            'sectionsTotal' => (int) Sections::find()->count(),
            // Taxonomy/catalogue counts (not affected by the archive toggle).
            'genresTotal' => (int) ArtGenres::find()->count(),
            'groundsTotal' => (int) Grounds::find()->count(),
            'materialsTotal' => (int) Materials::find()->count(),
        ]);
    }

    /** Toggle the global "hide archive" preference and return to where we were. */
    public function actionArchive($hide = '0', $back = null)
    {
        AdminPrefs::setHideArchive($hide === '1');

        if (is_string($back) && $back !== '' && $back[0] === '/' && strpos($back, '//') !== 0) {
            return $this->redirect($back);
        }
        return $this->redirect(['/admin/index']);
    }

    /** Switch the admin UI language (en|ru) and return to where we were. */
    public function actionLang($l = 'en', $back = null)
    {
        AdminLang::set($l);

        // Only allow same-site relative redirects (no open redirect).
        if (is_string($back) && $back !== '' && $back[0] === '/' && strpos($back, '//') !== 0) {
            return $this->redirect($back);
        }
        return $this->redirect(['/admin/index']);
    }
}
