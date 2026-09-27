<?php

use yii\db\Migration;

/**
 * Projects and PDF portfolios
 * (docs/superpowers/specs/2026-09-18-projects-and-portfolio-design.md).
 *
 * paintings.display_type — 'artwork' opens in the lightbox, 'project' (a board
 * game, a picture book) opens straight to its own page. All existing works
 * stay artworks; the author switches the projects herself.
 *
 * photos.in_portfolio — the photos of a work that go into the section PDF.
 * Deliberately NOT back-filled: with nothing ticked the PDF uses the work's
 * cover (Paintings::portfolioPhotos()), and that keeps following the cover if
 * the author changes it later. A back-fill would freeze today's cover.
 *
 * Production has no console: docs/sql/2026-09-18-display-type-and-portfolio.sql
 * is the same change for phpMyAdmin. Keep the two in sync.
 */
class m260918_120000_add_display_type_and_in_portfolio extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%paintings}}',
            'display_type',
            $this->string(16)->notNull()->defaultValue('artwork')->comment('artwork | project')
        );
        $this->addColumn(
            '{{%photos}}',
            'in_portfolio',
            $this->boolean()->notNull()->defaultValue(0)->comment('Goes into the section PDF portfolio')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%photos}}', 'in_portfolio');
        $this->dropColumn('{{%paintings}}', 'display_type');
    }
}
