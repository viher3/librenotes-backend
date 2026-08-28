<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260828125657 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE attachment ADD deleted_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_attachment_owner_deleted ON attachment (owner_id, deleted_at)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE folder ADD deleted_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_folder_owner_deleted ON folder (owner_id, deleted_at)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note ADD deleted_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_note_owner_deleted ON note (owner_id, deleted_at)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX idx_attachment_owner_deleted ON attachment
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE attachment DROP deleted_at
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_folder_owner_deleted ON folder
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE folder DROP deleted_at
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_note_owner_deleted ON note
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE note DROP deleted_at
        SQL);
    }
}
