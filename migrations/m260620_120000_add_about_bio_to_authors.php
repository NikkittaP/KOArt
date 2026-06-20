<?php

use yii\db\Migration;

/**
 * Phase 4b (About page): move the About bio out of the view template and into
 * the database. Adds `biography_en` to `authors` (the existing `biography`
 * column stays as the Russian/source value; `biography_en` is the English text
 * shown on the public English site, with a fallback to `biography` — see
 * Authors::tr() / BilingualTrait).
 *
 * Also seeds author #1 with the About copy (Russian source + English) so the
 * page is not empty right after migration. Re-running is safe (update first,
 * insert only if the row is missing).
 */
class m260620_120000_add_about_bio_to_authors extends Migration
{
    private function bioEn()
    {
        return implode("\n\n", [
            'I am an illustrator currently based in Sweden, working primarily with illustrations for board games. My practice focuses on creating expressive, character-driven and atmospheric imagery that supports gameplay and world-building.',
            'I have a formal background in fine art and illustration, and I continue to actively develop my visual language through both commissioned work and personal projects. Alongside client projects, I regularly draw for myself, experiment with new approaches, and explore long-term ideas that shape the direction of my work.',
            'My experience also includes teaching painting and leading workshops, which has influenced the way I approach structure, clarity, and visual storytelling in illustration.',
        ]);
    }

    private function bioRu()
    {
        return implode("\n\n", [
            'Я иллюстратор, живу в Швеции и работаю преимущественно над иллюстрациями для настольных игр. В своей работе я создаю выразительные, атмосферные образы и проработанных персонажей, которые поддерживают игровой процесс и помогают раскрыть мир игры.',
            'У меня академическое образование в области изобразительного искусства и иллюстрации, и я продолжаю активно развивать свой визуальный язык — как в заказных, так и в личных проектах. Помимо работы с клиентами я регулярно рисую для себя, пробую новые подходы и исследую долгосрочные идеи, которые задают направление моему творчеству.',
            'Мой опыт также включает преподавание живописи и проведение мастер-классов, что повлияло на то, как я выстраиваю структуру, ясность и визуальное повествование в иллюстрации.',
        ]);
    }

    public function safeUp()
    {
        $this->addColumn('{{%authors}}', 'biography_en', $this->text()->null()->after('biography'));

        $ru = $this->bioRu();
        $en = $this->bioEn();

        // Seed the public author (id = 1). Update if present, otherwise insert.
        // biography = Russian (source), biography_en = English (public site).
        $updated = $this->db->createCommand()
            ->update('{{%authors}}', ['biography' => $ru, 'biography_en' => $en], ['id' => 1])
            ->execute();

        if ($updated === 0) {
            $this->insert('{{%authors}}', [
                'id' => 1,
                'name' => 'Katia Oskina',
                'biography' => $ru,
                'biography_en' => $en,
            ]);
        }
    }

    public function safeDown()
    {
        $this->dropColumn('{{%authors}}', 'biography_en');
        // The `biography` text is kept (was empty/placeholder before this migration).
    }
}
