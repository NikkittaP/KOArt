<?php

use yii\db\Migration;

/**
 * Adds a sale / availability `status` to paintings. This is INDEPENDENT of
 * `isVisible` (which controls whether a work is shown publicly): a work can be
 * "Нет в наличии" yet still be visible in the portfolio.
 *
 * Values (see app\models\Paintings::statuses()):
 *   1 = Available     ("В наличии")     — default for newly created works
 *   2 = Sold          ("Продано")
 *   3 = Not available ("Нет в наличии") — e.g. the piece stayed in RF and
 *                                          currently can't be obtained
 *
 * Existing works are back-filled to "Not available" (3); new works default to
 * "Available" (1) via the column default.
 */
class m260617_140000_add_status_to_paintings extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%paintings}}',
            'status',
            $this->tinyInteger(3)->unsigned()->notNull()->defaultValue(1)
                ->comment('Статус: 1=В наличии, 2=Продано, 3=Нет в наличии')
                ->after('isVisible')
        );

        // Back-fill every pre-existing work to "Not available" (3); new works
        // created from now on take the column default (1 = Available).
        $this->update('{{%paintings}}', ['status' => 3]);
    }

    public function safeDown()
    {
        $this->dropColumn('{{%paintings}}', 'status');
    }
}
