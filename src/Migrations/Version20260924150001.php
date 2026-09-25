<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Sylius\Bundle\CoreBundle\Doctrine\Migrations\AbstractPostgreSQLMigration;

/** PostgreSQL. Version20260924150000 is the MySQL/MariaDB twin. */
final class Version20260924150001 extends AbstractPostgreSQLMigration
{
    public function getDescription(): string
    {
        return 'Brevo customers list per channel';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE odiseo_brevo_channel_configuration ADD customers_list_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE odiseo_brevo_channel_configuration DROP customers_list_id');
    }
}
