<?php

use yii\db\Migration;

/**
 * Follow-up to m260620_120000: that migration originally seeded the *English*
 * text into both `biography` and `biography_en`. The Russian column is meant to
 * hold the Russian source, so this migration replaces the Russian field with a
 * proper Russian translation — but ONLY if it still holds the old English seed
 * (or is empty). If the owner has already edited the bio in the admin, their
 * text is left untouched.
 *
 * On a fresh database m260620_120000 already seeds Russian, so the guard below
 * does not match and this migration is a no-op.
 */
class m260620_130000_translate_about_bio_ru extends Migration
{
    private function oldEnglishSeed()
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
        $this->db->createCommand()
            ->update(
                '{{%authors}}',
                ['biography' => $this->bioRu()],
                ['and', ['id' => 1], ['or', ['biography' => $this->oldEnglishSeed()], ['biography' => null], ['biography' => '']]]
            )
            ->execute();
    }

    public function safeDown()
    {
        echo "m260620_130000_translate_about_bio_ru does not revert data.\n";
        return true;
    }
}
