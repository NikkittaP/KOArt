<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Adds section_id to `paintings` (FK -> sections) and back-fills existing rows
 * to the "artworks" section.
 */
class m260616_150100_add_section_id_to_paintings extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%paintings}}', 'section_id', $this->integer()->unsigned()->null()->after('isVisible'));
        $this->createIndex('idx-paintings-section_id', '{{%paintings}}', 'section_id');
        $this->addForeignKey(
            'fk-paintings-section_id',
            '{{%paintings}}', 'section_id',
            '{{%sections}}', 'id',
            'SET NULL', 'CASCADE'
        );

        $artworksId = (new Query())
            ->select('id')->from('{{%sections}}')
            ->where(['slug' => 'artworks'])
            ->scalar($this->db);

        if ($artworksId) {
            $this->update('{{%paintings}}', ['section_id' => $artworksId]);
        }
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-paintings-section_id', '{{%paintings}}');
        $this->dropIndex('idx-paintings-section_id', '{{%paintings}}');
        $this->dropColumn('{{%paintings}}', 'section_id');
    }
}
