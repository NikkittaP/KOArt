<?php

namespace app\controllers;

use app\helpers\AdminLang;
use yii\web\Controller;

/**
 * Base controller for the admin / archive panel (Phase 4b).
 *
 * Provides the dark admin layout and the admin-scoped language for every action
 * EXCEPT those listed in $publicActions (e.g. SeriesController::actionShow, which
 * is a public page that sets its own public layout/English).
 *
 * Access control + verb filters stay in each subclass's behaviors() so the
 * existing, correctly-scoped rules (public vs. @-only actions) are preserved.
 */
abstract class AdminBaseController extends Controller
{
    /** @var string default layout for admin screens */
    public $layout = 'admin';

    /** @var string[] action ids that are public pages, not admin screens */
    protected $publicActions = [];

    /** @var string|null active sidebar nav key for this controller */
    public $adminNav = null;

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if (!in_array($action->id, $this->publicActions, true)) {
            AdminLang::apply();
            if ($this->adminNav !== null) {
                $this->view->params['adminNav'] = $this->adminNav;
            }
        }
        return true;
    }
}
