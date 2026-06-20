<?php

namespace app\controllers;

use app\models\Authors;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

/**
 * Admin editor for the public About page (Phase 4b). Lets the owner edit the
 * author bio (Russian source + English) without touching code. The page itself
 * is a single record (authors.id = 1), so there is one screen — Edit — rather
 * than the usual index/create/update/delete CRUD.
 *
 * The portrait photo stays a static file (web/about_photo/about.jpg); only the
 * text lives in the database.
 */
class AboutController extends AdminBaseController
{
    public $adminNav = 'about';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'edit' => ['get', 'post'],
                ],
            ],
        ];
    }

    public function actionEdit()
    {
        $model = $this->loadAuthor();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('admin', 'About page saved.'));

            return $this->refresh();
        }

        return $this->render('edit', [
            'model' => $model,
        ]);
    }

    /**
     * The single public author (id = 1). Created in memory if the row is missing
     * so the form still renders before the seed migration has run.
     */
    protected function loadAuthor()
    {
        $model = Authors::find()->orderBy(['id' => SORT_ASC])->one();

        if ($model === null) {
            $model = new Authors();
            $model->name = 'Katia Oskina';
        }

        return $model;
    }
}
