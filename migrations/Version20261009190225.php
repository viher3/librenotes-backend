<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261009190225 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Attachments can belong to a note: add attachment.note_id, make folder_id nullable';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE attachment ADD note_id VARCHAR(36) DEFAULT NULL, CHANGE folder_id folder_id VARCHAR(36) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_attachment_owner_note ON attachment (owner_id, note_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX idx_attachment_owner_note ON attachment
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE attachment DROP note_id, CHANGE folder_id folder_id VARCHAR(36) NOT NULL
        SQL);
    }
}
