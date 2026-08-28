<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ramsey\Uuid\Uuid;

/**
 * Manual, non-diffed migration: seeds the ROLE_ADMIN row that
 * `user:promote-admin` depends on, and closes the missing uniqueness
 * constraint on user_roles.name (the table predates migrations, see
 * Version20260818181903).
 */
final class Version20260827204500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seeds the ROLE_ADMIN user role and enforces uniqueness on user_roles.name';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX uniq_user_roles_name ON user_roles (name)');

        $this->addSql(
            'INSERT INTO user_roles (id, name, created_at) VALUES (?, ?, NOW())',
            [Uuid::uuid4()->toString(), 'ROLE_ADMIN']
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM user_roles WHERE name = 'ROLE_ADMIN'");
        $this->addSql('DROP INDEX uniq_user_roles_name ON user_roles');
    }
}
