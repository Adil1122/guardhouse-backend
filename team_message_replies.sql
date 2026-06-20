CREATE TABLE IF NOT EXISTS `team_message_replies` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `team_message_id` BIGINT UNSIGNED NOT NULL,
  `thread_user_id` BIGINT UNSIGNED NOT NULL,
  `sender_id` BIGINT UNSIGNED NOT NULL,
  `body` TEXT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `team_message_replies_team_message_id_foreign`
    FOREIGN KEY (`team_message_id`) REFERENCES `team_messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `team_message_replies_thread_user_id_foreign`
    FOREIGN KEY (`thread_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `team_message_replies_sender_id_foreign`
    FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_06_20_000001_create_team_message_replies_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations m2)
WHERE '2026_06_20_000001_create_team_message_replies_table' NOT IN (SELECT migration FROM migrations);
