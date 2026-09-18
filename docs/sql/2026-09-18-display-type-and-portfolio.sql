-- Same change as migrations/m260918_120000_add_display_type_and_in_portfolio.php,
-- for production (no console on Hetzner Webhosting S). Paste into
-- phpMyAdmin → SQL on the production database. Run ONCE.
-- The last statement records the migration so a future `yii migrate`
-- does not try to apply it again.

ALTER TABLE `paintings`
  ADD COLUMN `display_type` VARCHAR(16) NOT NULL DEFAULT 'artwork' COMMENT 'artwork | project';

ALTER TABLE `photos`
  ADD COLUMN `in_portfolio` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Goes into the section PDF portfolio';

INSERT INTO `migration` (`version`, `apply_time`)
  VALUES ('m260918_120000_add_display_type_and_in_portfolio', UNIX_TIMESTAMP());
