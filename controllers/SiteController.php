<?php

namespace app\controllers;

use app\models\ContactForm;
use app\models\LoginForm;
use app\models\Paintings;
use app\models\PaintingsToSeries;
use app\models\Sections;
use app\models\Series;
use Yii;
use yii\data\Pagination;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Homepage = the "artworks" section (Phase 3 public design).
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->renderSection('artworks');
    }

    /**
     * One of the other public nav sections: commercial-illustrations,
     * picturebooks, sketchbooks. Slug is supplied by the matching
     * urlManager rule in config/web.php.
     *
     * @return string
     */
    public function actionSection($slug)
    {
        return $this->renderSection($slug);
    }

    /**
     * Shared rendering for the hybrid section page: an optional series-card
     * grid on top, an optional mosaic of loose (non-series) works below.
     */
    private function renderSection($slug)
    {
        $section = Sections::find()->where(['slug' => $slug])->one();
        if (!$section) {
            throw new NotFoundHttpException('The requested section does not exist.');
        }
        $intro = (string) $section->tr('description');

        $series = Series::find()
            ->where(['section_id' => $section->id, 'isVisible' => 1])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        $loosePaintingIds = PaintingsToSeries::find()->select('painting_id');
        $paintings = Paintings::find()
            ->where(['section_id' => $section->id, 'isVisible' => 1])
            ->andWhere(['not in', 'id', $loosePaintingIds])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        $this->layout = '@app/views/layouts/public';
        $this->view->params['activeNav'] = $section->slug;
        // Logged-in owner: "Edit" jumps to this section's works in admin.
        $this->view->params['adminEditUrl'] = ['/paintings/index', 'selected_section' => $section->id];

        return $this->render('section', [
            'section' => $section,
            'intro' => $intro,
            'series' => $series,
            'paintings' => $paintings,
        ]);
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['/admin/index']);
        }

        // Login is the gateway to the admin panel: style it like admin and use
        // the admin UI language.
        \app\helpers\AdminLang::apply();
        $this->layout = '@app/views/layouts/blank';

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            // Land in the admin dashboard, not on the public homepage.
            return $this->redirect(['/admin/index']);
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        $this->layout = '@app/views/layouts/public';
        $this->view->params['activeNav'] = 'about';

        $author = \app\models\Authors::find()->orderBy(['id' => SORT_ASC])->one();

        return $this->render('about', [
            'author' => $author,
        ]);
    }
}
