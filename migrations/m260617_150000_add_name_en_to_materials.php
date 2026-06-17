<?php

use yii\db\Migration;

/**
 * Adds an English name (`name_en`) to the `materials` taxonomy, mirroring the
 * earlier genres/grounds migration. The Russian `name` stays as the source
 * value; `name_en` is optional and falls back to `name` when empty (see
 * Materials::tr() via BilingualTrait).
 */
class m260617_150000_add_name_en_to_materials extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%materials}}', 'name_en', $this->string(255)->null()->after('name'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%materials}}', 'name_en');
    }
}
