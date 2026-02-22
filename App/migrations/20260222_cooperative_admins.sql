-- Migration: cooperative_admins mapping (single admin per cooperative)
CREATE TABLE IF NOT EXISTS `cooperative_admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cooperative_type` ENUM('tenant','registration') NOT NULL,
    `cooperative_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_coop_admin` (`cooperative_type`,`cooperative_id`),
    UNIQUE KEY `uniq_user` (`user_id`),
    KEY `idx_user` (`user_id`),
    CONSTRAINT `fk_coop_admin_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_coop_admin_tenant` FOREIGN KEY (`cooperative_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
);

-- Note: cooperative_type='registration' rows refer to cooperative_registrations.id; FK omitted to allow staged approval.
