<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260818181903 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Baseline schema, capturing the tables created by hand via doctrine:schema:update '
            . 'before migrations were introduced: user, user_roles, user_role_assignments, refresh_tokens.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE refresh_tokens (id VARCHAR(36) NOT NULL, user_id VARCHAR(36) NOT NULL, token_hash LONGTEXT NOT NULL, jti VARCHAR(255) NOT NULL, device_info JSON NOT NULL, created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, last_used DATETIME DEFAULT NULL, revoked TINYINT(1) DEFAULT 0 NOT NULL, replaced_by_jti VARCHAR(255) NOT NULL, ip VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_9BACE7E1C53CF2EA (jti), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (id VARCHAR(36) NOT NULL, username VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, is_active TINYINT(1) NOT NULL, is_email_validated TINYINT(1) NOT NULL, password_recovery_token VARCHAR(255) DEFAULT NULL, password_recovery_requested_at DATETIME DEFAULT NULL, token_version INT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, last_login_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_role_assignments (id VARCHAR(50) NOT NULL, user_id VARCHAR(50) NOT NULL, user_role_id VARCHAR(50) NOT NULL, assigned_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', revoked_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX idx_user_role (user_id, user_role_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_roles (id VARCHAR(50) NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE refresh_tokens
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_role_assignments
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_roles
        SQL);
    }
}
