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
    /**
     * @param string $attr   base (Russian) attribute name
     * @param bool   $strict when true and the site language is English, an
     *                       empty "<attr>_en" returns '' instead of falling
     *                       back to the Russian value (used for painting
     *                       titles the author left untranslated).
     */
    public function tr($attr, $strict = false)
    {
        $en = $attr . '_en';
        $isEn = strncmp(\Yii::$app->language, 'en', 2) === 0;
        if ($isEn && $this->hasAttribute($en) && !empty($this->$en)) {
            return $this->$en;
        }
        if ($strict && $isEn && $this->hasAttribute($en)) {
            return '';
        }
        return $this->$attr;
    }
}
