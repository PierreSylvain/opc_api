<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241221144329 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE property_user (property_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_928B6973549213EC (property_id), INDEX IDX_928B6973A76ED395 (user_id), PRIMARY KEY(property_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE property_user ADD CONSTRAINT FK_928B6973549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE property_user ADD CONSTRAINT FK_928B6973A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE property_user DROP FOREIGN KEY FK_928B6973549213EC');
        $this->addSql('ALTER TABLE property_user DROP FOREIGN KEY FK_928B6973A76ED395');
        $this->addSql('DROP TABLE property_user');
    }
}
