<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211006144550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE tblProductData charset=utf8mb4, 
            MODIFY COLUMN strProductName VARCHAR(50) CHARACTER SET utf8mb4,
            MODIFY COLUMN strProductDesc VARCHAR(255) CHARACTER SET utf8mb4,
            MODIFY COLUMN strProductCode VARCHAR(255) CHARACTER SET utf8mb4;'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE tblProductData charset=latin1, 
            MODIFY COLUMN strProductName VARCHAR(50) CHARACTER SET latin1,
            MODIFY COLUMN strProductDesc VARCHAR(255) CHARACTER SET latin1,
            MODIFY COLUMN strProductCode VARCHAR(255) CHARACTER SET latin1;'
        );
    }
}
