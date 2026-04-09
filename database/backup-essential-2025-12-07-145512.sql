-- Uptime Kita Database Backup
-- Generated: 2025-12-07T14:55:12+00:00
-- Essential data only (excludes monitoring history and cache)
--
-- To restore: sqlite3 database.sqlite < backup.sql
--

PRAGMA foreign_keys = OFF;

-- Table: migrations (30 rows)
DELETE FROM "migrations";
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (1, '0001_01_01_000000_create_users_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (3, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (4, '2025_05_28_014043_create_monitors_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (5, '2025_06_04_204329_create_telescope_entries_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (6, '2025_06_10_035439_create_user_monitor_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (7, '2025_06_19_041703_create_monitor_histories_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (8, '2025_06_26_162525_create_social_accounts_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (9, '2025_06_26_171319_alter_users_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (10, '2025_06_27_125605_create_monitor_uptime_dailies_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (11, '2025_07_01_154946_create_status_pages_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (12, '2025_07_01_225846_create_status_page_monitor_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (13, '2025_07_05_044506_create_notification_channels_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (14, '2025_07_20_011146_create_tag_tables', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (15, '2025_07_23_122315_add_order_column_to_status_page_monitor_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (16, '2025_07_24_065055_add_unique_to_monitor_uptime_dailies_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (17, '2025_07_25_000000_add_is_admin_to_users_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (18, '2025_08_01_035827_create_health_tables', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (19, '2025_08_07_183132_create_queue_tables', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (20, '2025_08_07_183540_create_telescope_entries_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (21, '2025_08_07_193540_drop_queue_and_telescope_tables', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (22, '2025_08_14_092916_add_performance_indexes', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (23, '2025_08_15_084216_add_is_pinned_to_user_monitor_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (24, '2025_08_16_090721_add_custom_domains_to_status_pages', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (25, '2025_08_16_103721_update_monitors_to_https', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (26, '2025_08_16_104837_add_missing_monitor_fields', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (27, '2025_08_19_071638_create_monitor_statistics_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (28, '2025_08_19_072542_add_checked_at_index_to_monitor_histories_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (29, '2025_08_19_081445_add_unique_constraint_to_monitor_histories_table', 1);
INSERT INTO "migrations" ("id", "migration", "batch") VALUES (30, '2025_09_12_104331_create_email_notification_logs_table', 1);

-- Table 'users' is empty

-- Table 'monitors' is empty

-- Table 'notification_channels' is empty

-- Table 'status_pages' is empty

-- Table 'status_page_monitor' is empty

-- Table 'user_monitor' is empty

-- Table 'tags' is empty

-- Table 'taggables' is empty

-- Table 'social_accounts' is empty

-- Table 'monitor_incidents' is empty

PRAGMA foreign_keys = ON;
