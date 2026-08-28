<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260819192813 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates configurations and configuration_history for the Configuration module';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE configuration_history (id VARCHAR(36) NOT NULL, configuration_id VARCHAR(36) DEFAULT NULL, configuration_key VARCHAR(255) NOT NULL, action VARCHAR(20) NOT NULL, previous_value LONGTEXT DEFAULT NULL, previous_value_key_version SMALLINT DEFAULT NULL, new_value LONGTEXT DEFAULT NULL, new_value_key_version SMALLINT DEFAULT NULL, changed_by VARCHAR(36) NOT NULL, changed_at DATETIME NOT NULL, INDEX idx_configuration_history_key (configuration_key), INDEX idx_configuration_history_configuration_id (configuration_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE configurations (id VARCHAR(36) NOT NULL, config_key VARCHAR(255) NOT NULL, value LONGTEXT NOT NULL, type VARCHAR(20) NOT NULL, description LONGTEXT DEFAULT NULL, is_sensitive TINYINT(1) NOT NULL, key_version SMALLINT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_31C6AD995D1CAA6 (config_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP TABLE configuration_history
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE configurations
        SQL);
    }
}
