<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211004065333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('alter table tblProductData add intStock int(6) not null;
                alter table tblProductData add dcmlPrice decimal(11,2) not null;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table tblProductData drop column intStock;
                alter table tblProductData drop column dcmlPrice;');
    }
}
