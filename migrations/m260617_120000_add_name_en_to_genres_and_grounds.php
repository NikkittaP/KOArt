<?php

use yii\db\Migration;

/**
 * Phase 4b: adds an English name (`name_en`) to the `art_genres` and `grounds`
 * taxonomies. The existing `name` column stays as the Russian/source value;
 * `name_en` is optional and falls back to `name` when empty (see the models'
 * displayName()). Lets the bilingual admin show English labels.
 */
class m260617_120000_add_name_en_to_genres_and_grounds extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%art_genres}}', 'name_en', $this->string(255)->null()->after('name'));
        $this->addColumn('{{%grounds}}', 'name_en', $this->string(255)->null()->after('name'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%art_genres}}', 'name_en');
        $this->dropColumn('{{%grounds}}', 'name_en');
    }
}
