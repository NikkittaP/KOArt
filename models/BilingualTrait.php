<?php

namespace app\models;

/**
 * Phase 4b bilingual content. For a base attribute (e.g. "name", Russian source)
 * returns the English value from "<attr>_en" when the current UI/site language
 * is English and that value is set; otherwise the base (Russian) value.
 *
 * Guarded by hasAttribute() so models keep working before the *_en migration
 * has been applied.
 */
trait BilingualTrait
{
    public function tr($attr)
    {
        $en = $attr . '_en';
        if (strncmp(\Yii::$app->language, 'en', 2) === 0
            && $this->hasAttribute($en) && !empty($this->$en)) {
            return $this->$en;
        }
        return $this->$attr;
    }
}
