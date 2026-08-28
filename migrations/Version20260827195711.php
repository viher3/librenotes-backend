<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827195711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates notifications and notification_templates for the Notification module';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE notification_templates (id VARCHAR(36) NOT NULL, template_key VARCHAR(255) NOT NULL, channel VARCHAR(20) NOT NULL, locale VARCHAR(10) NOT NULL, subject VARCHAR(255) NOT NULL, body LONGTEXT NOT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX uniq_notification_templates_key_channel_locale (template_key, channel, locale), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE notifications (id VARCHAR(36) NOT NULL, channel VARCHAR(20) NOT NULL, template_key VARCHAR(255) NOT NULL, locale VARCHAR(10) NOT NULL, recipient VARCHAR(255) NOT NULL, recipient_id VARCHAR(36) DEFAULT NULL, payload JSON NOT NULL, subject VARCHAR(255) DEFAULT NULL, body LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, attempts INT NOT NULL, last_error LONGTEXT DEFAULT NULL, provider_message_id VARCHAR(255) DEFAULT NULL, sent_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX idx_notifications_status_channel (status, channel), INDEX idx_notifications_recipient_id (recipient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP TABLE notification_templates
            SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE notifications
            SQL);
    }
}
