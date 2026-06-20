SET @batch = (SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations);

INSERT INTO migrations (migration, batch)
SELECT '2026_06_10_000001_create_site_visits_table', @batch
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'site_visits'
  AND NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_06_10_000001_create_site_visits_table');

INSERT INTO migrations (migration, batch)
SELECT '2026_06_10_000002_create_supervisor_reports_table', @batch
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'supervisor_reports'
  AND NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_06_10_000002_create_supervisor_reports_table');

INSERT INTO migrations (migration, batch)
SELECT '2026_06_10_000003_create_check_calls_table', @batch
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'check_calls'
  AND NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_06_10_000003_create_check_calls_table');

INSERT INTO migrations (migration, batch)
SELECT '2026_06_10_133243_create_notifications_table', @batch
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'notifications'
  AND NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_06_10_133243_create_notifications_table');
