<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260521203304 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP CONSTRAINT fk_64c19c175dd17f9');
        $this->addSql('ALTER TABLE category ALTER referential_id SET NOT NULL');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C175DD17F9 FOREIGN KEY (referential_id) REFERENCES referential (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE measure_node DROP CONSTRAINT fk_c2a6aef1727aca70');
        $this->addSql('ALTER TABLE measure_node DROP CONSTRAINT fk_c2a6aef112469de2');
        $this->addSql('ALTER TABLE measure_node ALTER category_id SET NOT NULL');
        $this->addSql('ALTER TABLE measure_node ADD CONSTRAINT FK_C2A6AEF1727ACA70 FOREIGN KEY (parent_id) REFERENCES measure_node (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE measure_node ADD CONSTRAINT FK_C2A6AEF112469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE measure_review DROP CONSTRAINT fk_665549065da37d00');
        $this->addSql('ALTER TABLE measure_review ADD CONSTRAINT FK_665549065DA37D005DA37D00 FOREIGN KEY (measure_id) REFERENCES measure_node (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE remediation_action DROP CONSTRAINT fk_c49f09095876e8d3');
        $this->addSql('ALTER TABLE remediation_action ADD CONSTRAINT FK_C49F09095876E8D3 FOREIGN KEY (measure_review_id) REFERENCES measure_review (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP CONSTRAINT FK_64C19C175DD17F9');
        $this->addSql('ALTER TABLE category ALTER referential_id DROP NOT NULL');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT fk_64c19c175dd17f9 FOREIGN KEY (referential_id) REFERENCES referential (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE measure_node DROP CONSTRAINT FK_C2A6AEF112469DE2');
        $this->addSql('ALTER TABLE measure_node DROP CONSTRAINT FK_C2A6AEF1727ACA70');
        $this->addSql('ALTER TABLE measure_node ALTER category_id DROP NOT NULL');
        $this->addSql('ALTER TABLE measure_node ADD CONSTRAINT fk_c2a6aef112469de2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE measure_node ADD CONSTRAINT fk_c2a6aef1727aca70 FOREIGN KEY (parent_id) REFERENCES measure_node (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE measure_review DROP CONSTRAINT FK_665549065DA37D005DA37D00');
        $this->addSql('ALTER TABLE measure_review ADD CONSTRAINT fk_665549065da37d00 FOREIGN KEY (measure_id) REFERENCES measure_node (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE remediation_action DROP CONSTRAINT FK_C49F09095876E8D3');
        $this->addSql('ALTER TABLE remediation_action ADD CONSTRAINT fk_c49f09095876e8d3 FOREIGN KEY (measure_review_id) REFERENCES measure_review (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
