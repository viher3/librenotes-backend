<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261009191214 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Links: saved links (url, title, note, tags) owned by a user';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE link (id VARCHAR(36) NOT NULL, title VARCHAR(255) NOT NULL, url VARCHAR(2048) NOT NULL, note LONGTEXT DEFAULT NULL, tags JSON DEFAULT NULL, folder_id VARCHAR(36) DEFAULT NULL, owner_id VARCHAR(36) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, INDEX idx_link_owner_folder (owner_id, folder_id), INDEX idx_link_owner_deleted (owner_id, deleted_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE link
        SQL);
    }
}
