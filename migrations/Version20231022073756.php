<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231022073756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consumer DROP FOREIGN KEY FK_705B3727B171EB6C');
        $this->addSql('DROP INDEX IDX_705B3727B171EB6C ON consumer');
        $this->addSql('ALTER TABLE consumer CHANGE customer_id_id customer_id INT NOT NULL');
        $this->addSql('ALTER TABLE consumer ADD CONSTRAINT FK_705B37279395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('CREATE INDEX IDX_705B37279395C3F3 ON consumer (customer_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE consumer DROP FOREIGN KEY FK_705B37279395C3F3');
        $this->addSql('DROP INDEX IDX_705B37279395C3F3 ON consumer');
        $this->addSql('ALTER TABLE consumer CHANGE customer_id customer_id_id INT NOT NULL');
        $this->addSql('ALTER TABLE consumer ADD CONSTRAINT FK_705B3727B171EB6C FOREIGN KEY (customer_id_id) REFERENCES customer (id)');
        $this->addSql('CREATE INDEX IDX_705B3727B171EB6C ON consumer (customer_id_id)');
    }
}
