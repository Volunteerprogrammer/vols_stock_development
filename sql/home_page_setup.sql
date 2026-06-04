-- =============================================================================
-- Home Page Setup Script
-- Run this ONCE against your database.
-- =============================================================================

ALTER TABLE `user`
  ADD COLUMN `home_page` SMALLINT NULL DEFAULT NULL;
