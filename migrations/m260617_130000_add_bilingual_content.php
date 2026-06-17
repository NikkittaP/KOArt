<?php

use yii\db\Migration;

/**
 * Phase 4b: bilingual content. The existing name/description/title columns hold
 * the Russian (source) text; these *_en columns hold English. The public site
 * (English) shows the *_en value with a fallback to the base field; a future
 * /ru/ shows the base field. See each model's display* () helpers.
 */
class m260617_130000_add_bilingual_content extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%paintings}}', 'name_en', $this->string(255)->null()->after('name'));
        $this->addColumn('{{%paintings}}', 'description_en', $this->text()->null()->after('description'));

        $this->addColumn('{{%series}}', 'name_en', $this->string(255)->null()->after('name'));
        $this->addColumn('{{%series}}', 'description_en', $this->text()->null()->after('description'));

        $this->addColumn('{{%sections}}', 'title_en', $this->string(255)->null()->after('title'));
        $this->addColumn('{{%sections}}', 'description_en', $this->text()->null()->after('description'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%paintings}}', 'name_en');
        $this->dropColumn('{{%paintings}}', 'description_en');
        $this->dropColumn('{{%series}}', 'name_en');
        $this->dropColumn('{{%series}}', 'description_en');
        $this->dropColumn('{{%sections}}', 'title_en');
        $this->dropColumn('{{%sections}}', 'description_en');
    }
}
