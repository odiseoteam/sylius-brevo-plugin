<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Sylius\Bundle\CoreBundle\Doctrine\Migrations\AbstractPostgreSQLMigration;

/** PostgreSQL. Version20260924120000 is the MySQL/MariaDB twin. */
final class Version20260924120001 extends AbstractPostgreSQLMigration
{
    public function getDescription(): string
    {
        return 'Brevo contact options per channel';
    }

    public function up(Schema $schema): void
    {
        // Existing configurations keep syncing guests, the default.
        $this->addSql('ALTER TABLE odiseo_brevo_channel_configuration ADD syncing_guest_contacts BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE odiseo_brevo_channel_configuration ADD deleting_contacts_of_removed_customers BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE odiseo_brevo_channel_configuration ALTER syncing_guest_contacts DROP DEFAULT');
        $this->addSql('ALTER TABLE odiseo_brevo_channel_configuration ALTER deleting_contacts_of_removed_customers DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE odiseo_brevo_channel_configuration DROP syncing_guest_contacts');
        $this->addSql('ALTER TABLE odiseo_brevo_channel_configuration DROP deleting_contacts_of_removed_customers');
    }
}
