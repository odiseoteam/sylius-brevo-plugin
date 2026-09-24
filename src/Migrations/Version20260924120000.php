<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Sylius\Bundle\CoreBundle\Doctrine\Migrations\AbstractMigration;

/** MySQL/MariaDB. Version20260924120001 is the PostgreSQL twin. */
final class Version20260924120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Brevo contact options per channel';
    }

    public function up(Schema $schema): void
    {
        // Existing configurations keep syncing guests, the default.
        $this->addSql('ALTER TABLE odiseo_brevo_channel_configuration ADD syncing_guest_contacts TINYINT(1) DEFAULT 1 NOT NULL, ADD deleting_contacts_of_removed_customers TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE odiseo_brevo_channel_configuration ALTER syncing_guest_contacts DROP DEFAULT, ALTER deleting_contacts_of_removed_customers DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE odiseo_brevo_channel_configuration DROP syncing_guest_contacts, DROP deleting_contacts_of_removed_customers');
    }
}
