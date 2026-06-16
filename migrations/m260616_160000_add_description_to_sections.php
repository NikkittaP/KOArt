<?php

use yii\db\Migration;

/**
 * Adds an editable `description` (intro copy) to `sections` so the section
 * intro text lives in the DB instead of being hardcoded in the controller.
 * Back-fills the four current intros from the approved design.
 */
class m260616_160000_add_description_to_sections extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%sections}}', 'description', $this->text()->null()->after('title'));

        $intros = [
            'artworks' => 'Studio works — drawing, painting and prints. Some pieces belong to a series, others stand on their own.',
            'commercial-illustrations' => 'Illustration for board games and publishers. Projects are grouped into series; single pieces are below.',
            'picturebooks' => 'Picture book and story illustration, by project.',
            'sketchbooks' => 'Studies, sketches and works on paper.',
        ];
        foreach ($intros as $slug => $text) {
            $this->update('{{%sections}}', ['description' => $text], ['slug' => $slug]);
        }
    }

    public function safeDown()
    {
        $this->dropColumn('{{%sections}}', 'description');
    }
}
