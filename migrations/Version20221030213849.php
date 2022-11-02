<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221030213849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE customers (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, firstname VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(30) NOT NULL, password VARCHAR(255) NOT NULL, balance INTEGER NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE "transaction" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, sender_id_id INTEGER DEFAULT NULL, amount INTEGER NOT NULL, transaction_code VARCHAR(255) NOT NULL, date DATETIME NOT NULL, type VARCHAR(50) NOT NULL, receiver_phone VARCHAR(255) NOT NULL, CONSTRAINT FK_723705D16061F7CF FOREIGN KEY (sender_id_id) REFERENCES customers (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_723705D16061F7CF ON "transaction" (sender_id_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE customers');
        $this->addSql('DROP TABLE "transaction"');
    }
}
