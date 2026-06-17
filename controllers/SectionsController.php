<?php

namespace app\controllers;

use app\helpers\RichText;
use app\models\Sections;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;

/**
 * Phase 4: lets the owner manage the navigation sections themselves
 * (slug, title, sort order, intro text) without touching code or migrations.
 */
class SectionsController extends AdminBaseController
{
    public $adminNav = 'sections';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['index', 'create', 'update', 'delete'],
                'rules' => [
                    [
                        'actions' => ['index', 'create', 'update', 'delete'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $sections = Sections::find()->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])->all();

        return $this->render('index', [
            'sections' => $sections,
        ]);
    }

    public function actionCreate()
    {
        $model = new Sections();

        if ($model->load(Yii::$app->request->post())) {
            $model->description = RichText::purify($model->description);
            if ($model->save()) {
                Yii::$app->session->setFlash('success', Yii::t('admin', 'Section created.'));

                return $this->redirect(['sections/index']);
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            $model->description = RichText::purify($model->description);
            if ($model->save()) {
                Yii::$app->session->setFlash('success', Yii::t('admin', 'Section saved.'));

                return $this->redirect(['sections/index']);
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if ($model->getPaintings()->exists() || $model->getSeries()->exists()) {
            Yii::$app->session->setFlash('warning', Yii::t('admin', 'Cannot delete section: it still has works or series. Move them to another section first.'));

            return $this->redirect(['sections/index']);
        }

        $model->delete();
        Yii::$app->session->setFlash('success', Yii::t('admin', 'Section deleted.'));

        return $this->redirect(['sections/index']);
    }

    protected function findModel($id)
    {
        if (($model = Sections::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
