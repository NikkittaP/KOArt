<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Adds section_id to `series` (FK -> sections) and back-fills existing rows
 * to the "artworks" section.
 */
class m260616_150200_add_section_id_to_series extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%series}}', 'section_id', $this->integer()->unsigned()->null()->after('isVisible'));
        $this->createIndex('idx-series-section_id', '{{%series}}', 'section_id');
        $this->addForeignKey(
            'fk-series-section_id',
            '{{%series}}', 'section_id',
            '{{%sections}}', 'id',
            'SET NULL', 'CASCADE'
        );

        $artworksId = (new Query())
            ->select('id')->from('{{%sections}}')
            ->where(['slug' => 'artworks'])
            ->scalar($this->db);

        if ($artworksId) {
            $this->update('{{%series}}', ['section_id' => $artworksId]);
        }
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-series-section_id', '{{%series}}');
        $this->dropIndex('idx-series-section_id', '{{%series}}');
        $this->dropColumn('{{%series}}', 'section_id');
    }
}
