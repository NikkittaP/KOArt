<?php

use yii\db\Migration;

/**
 * Creates the `sections` lookup table (the navigation dimension) and seeds it.
 * A section groups series (top of a section page) and loose works (mosaic).
 */
class m260616_150000_create_sections_table extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%sections}}', [
            'id' => $this->primaryKey()->unsigned(),
            'slug' => $this->string(64)->notNull()->unique(),
            'title' => $this->string(128)->notNull(),
            'sort' => $this->integer()->notNull()->defaultValue(0),
        ], $tableOptions);

        $this->batchInsert('{{%sections}}', ['slug', 'title', 'sort'], [
            ['artworks', 'Artworks', 0],
            ['commercial-illustrations', 'Commercial illustrations', 1],
            ['picturebooks', 'Picturebooks', 2],
            ['sketchbooks', 'Sketchbooks', 3],
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%sections}}');
    }
}
