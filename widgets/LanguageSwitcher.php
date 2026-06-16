<?php
namespace app\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;

class LanguageSwitcher extends Widget
{
    private static $_labels;
    private $_isError;
    private static $_links;

    public function init()
    {

        $route = Yii::$app->controller->route;
        $appLanguage = Yii::$app->language;
        $params = $_GET;
        $this->_isError = $route === Yii::$app->errorHandler->errorAction;

        array_unshift($params, '/' . $route);

        if (self::$_links === null) {
            self::$_links = [];
        }

        foreach (Yii::$app->urlManager->languages as $language) {
            $isLink = true;
            $isWildcard = substr($language, -2) === '-*';
            if (
                $language === $appLanguage ||
                // Also check for wildcard language
                $isWildcard && substr($appLanguage, 0, 2) === substr($language, 0, 2)
            ) {
                $isLink = false;
                continue; // Exclude the current language
            }
            if ($isWildcard) {
                $language = substr($language, 0, 2);
            }
            $params['language'] = $language;

            if ($isLink) {
                self::$_links[] = '<li>'.Html::a(self::label($language), $params, ['class' => 'nav-link']).'</li>';
            } else {
                self::$_links[] = '<li class="aslink">'.self::label($language).'</li>';
            }

            //$this->items[] = [
            //    'label' => self::label($language),
            //    'url' => $params,
            //];
        }
        parent::init();
    }

    public function run()
    {
        // Only show this widget if we're not on the error page
        if ($this->_isError) {
            return '';
        } else {
            return implode(" ", self::$_links);
        }
    }

    public static function label($code)
    {
        if (self::$_labels === null) {
            self::$_labels = [
                'ru' => 'Ru', //Yii::t('language', 'Русский'),
                'en' => 'En', //Yii::t('language', 'English'),
            ];
        }

        return isset(self::$_labels[$code]) ? self::$_labels[$code] : null;
    }
}
