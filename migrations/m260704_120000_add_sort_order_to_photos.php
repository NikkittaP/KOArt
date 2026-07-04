<?php

use yii\db\Migration;

/**
 * Adds sort_order to `photos` so an author can arrange the extra photos of a
 * work into a deliberate sequence (drag-and-drop on the "Manage photos" page).
 * Lower values come first. The public work page renders the photos in this
 * order; the cover (isMain) is independent and used for thumbnails elsewhere.
 *
 * Back-fill: seed sort_order from the current id order (cover first, then the
 * rest as they were uploaded) so existing multi-photo works keep a sane order.
 */
class m260704_120000_add_sort_order_to_photos extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%photos}}', 'sort_order', $this->integer()->notNull()->defaultValue(0));

        // Per-painting back-fill: cover (isMain DESC) first, then by id.
        $rows = (new \yii\db\Query())
            ->select(['id', 'painting_id'])
            ->from('{{%photos}}')
            ->orderBy(['painting_id' => SORT_ASC, 'isMain' => SORT_DESC, 'id' => SORT_ASC])
            ->all($this->db);

        $order = [];
        foreach ($rows as $r) {
            $pid = (int) $r['painting_id'];
            $order[$pid] = ($order[$pid] ?? -1) + 1;
            $this->update('{{%photos}}', ['sort_order' => $order[$pid]], ['id' => (int) $r['id']]);
        }
    }

    public function safeDown()
    {
        $this->dropColumn('{{%photos}}', 'sort_order');
    }
}
