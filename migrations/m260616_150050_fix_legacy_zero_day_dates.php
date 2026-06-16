<?php

use yii\db\Migration;

/**
 * Legacy data fix: the old database (lax sql_mode) stored painting dates with an
 * unknown day as "YYYY-MM-00". MySQL 8 strict mode rejects these when the table
 * is rebuilt (e.g. adding a foreign key). Normalise day 00 -> 01, keeping the
 * year and month. Runs before the section_id FK migration.
 */
class m260616_150050_fix_legacy_zero_day_dates extends Migration
{
    public function safeUp()
    {
        if ($this->db->driverName === 'mysql') {
            $this->execute(
                "UPDATE {{%paintings}} SET `date` = DATE_FORMAT(`date`, '%Y-%m-01') " .
                "WHERE `date` IS NOT NULL AND DAY(`date`) = 0"
            );
        }
    }

    public function safeDown()
    {
        // Data fix — cannot be meaningfully reverted (original day was unknown).
        echo "m260616_150050_fix_legacy_zero_day_dates does not revert data.\n";
        return true;
    }
}
