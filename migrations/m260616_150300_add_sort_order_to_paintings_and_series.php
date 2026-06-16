<?php

use yii\db\Migration;

/**
 * Adds sort_order to `paintings` and `series` for manual ordering within a
 * section / series. Lower values come first.
 */
class m260616_150300_add_sort_order_to_paintings_and_series extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%paintings}}', 'sort_order', $this->integer()->notNull()->defaultValue(0));
        $this->addColumn('{{%series}}', 'sort_order', $this->integer()->notNull()->defaultValue(0));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%paintings}}', 'sort_order');
        $this->dropColumn('{{%series}}', 'sort_order');
    }
}
