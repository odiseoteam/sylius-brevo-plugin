<?php

declare(strict_types=1);

namespace Odiseo\SyliusBrevoPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Sylius\Bundle\CoreBundle\Doctrine\Migrations\AbstractMigration;

/** MySQL/MariaDB. Version20260923120001 is the PostgreSQL twin. */
final class Version20260923120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Brevo configuration per channel';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE odiseo_brevo_channel_configuration (id INT AUTO_INCREMENT NOT NULL, channel_id INT NOT NULL, enabled TINYINT(1) NOT NULL, api_key LONGTEXT DEFAULT NULL, sender_name VARCHAR(255) DEFAULT NULL, sender_email VARCHAR(255) DEFAULT NULL, modules JSON NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_1FDD127172F5A1AA (channel_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE odiseo_brevo_channel_configuration ADD CONSTRAINT FK_1FDD127172F5A1AA FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE odiseo_brevo_channel_configuration');
    }
}
