<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260818191335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop the datetime_immutable type-hint comment on user_role_assignments dates now that '
            . 'they are mapped through the datetime_vo Doctrine type (App\Core\Domain\ValueObject\DateTimeValueObject); '
            . 'column type and data are unchanged.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user_role_assignments
            CHANGE
              assigned_at assigned_at DATETIME NOT NULL,
            CHANGE
              revoked_at revoked_at DATETIME DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE
              user_role_assignments
            CHANGE
              assigned_at assigned_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
            CHANGE
              revoked_at revoked_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
        SQL);
    }
}
