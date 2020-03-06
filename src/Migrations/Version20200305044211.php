<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200305044211 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE sylius_job DROP FOREIGN KEY FK_49F4BFD233EF8782');
        $this->addSql('ALTER TABLE sylius_trans_error DROP FOREIGN KEY FK_455CA05733EF8782');
        $this->addSql('ALTER TABLE sylius_trans_step DROP FOREIGN KEY FK_75E2AFEB33EF8782');
        $this->addSql('ALTER TABLE sylius_trans_dictionnary DROP FOREIGN KEY FK_BF0B688777E5854A');
        $this->addSql('CREATE TABLE currency_cache (id INT AUTO_INCREMENT NOT NULL, source VARCHAR(32) NOT NULL, timestamp VARCHAR(255) NOT NULL, hit_count INT NOT NULL, quote LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job (id INT AUTO_INCREMENT NOT NULL, trans_id INT DEFAULT NULL, user_id INT NOT NULL, last_consulted_at DATETIME DEFAULT NULL, step INT DEFAULT NULL, ussd_handler_number VARCHAR(32) DEFAULT NULL, step_description LONGTEXT DEFAULT NULL, status VARCHAR(32) DEFAULT \'WAITING\', last_requested_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, response LONGTEXT DEFAULT NULL, request LONGTEXT DEFAULT NULL, INDEX IDX_FBD8E0F833EF8782 (trans_id), UNIQUE INDEX UNIQ_FBD8E0F8A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trans_error (id INT AUTO_INCREMENT NOT NULL, trans_id INT DEFAULT NULL, reg_exp VARCHAR(255) NOT NULL, INDEX IDX_E21ED9AE33EF8782 (trans_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trans_mode (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(64) NOT NULL, UNIQUE INDEX UNIQ_84CE8E985E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trans_step (id INT AUTO_INCREMENT NOT NULL, trans_id INT DEFAULT NULL, position INT NOT NULL, INDEX IDX_50BD370F33EF8782 (trans_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trans_dictionnary (id INT AUTO_INCREMENT NOT NULL, mode_id INT DEFAULT NULL, recipient_name_reg_exp VARCHAR(64) DEFAULT NULL, carreer_reg_exp VARCHAR(64) NOT NULL, carreer_name VARCHAR(64) NOT NULL, trans_code VARCHAR(64) NOT NULL, INDEX IDX_947E79BB77E5854A (mode_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE job ADD CONSTRAINT FK_FBD8E0F833EF8782 FOREIGN KEY (trans_id) REFERENCES trans_dictionnary (id)');
        $this->addSql('ALTER TABLE job ADD CONSTRAINT FK_FBD8E0F8A76ED395 FOREIGN KEY (user_id) REFERENCES dati_user (id)');
        $this->addSql('ALTER TABLE trans_error ADD CONSTRAINT FK_E21ED9AE33EF8782 FOREIGN KEY (trans_id) REFERENCES trans_dictionnary (id)');
        $this->addSql('ALTER TABLE trans_step ADD CONSTRAINT FK_50BD370F33EF8782 FOREIGN KEY (trans_id) REFERENCES trans_dictionnary (id)');
        $this->addSql('ALTER TABLE trans_dictionnary ADD CONSTRAINT FK_947E79BB77E5854A FOREIGN KEY (mode_id) REFERENCES trans_mode (id)');
        $this->addSql('DROP TABLE sylius_currency_cache');
        $this->addSql('DROP TABLE sylius_job');
        $this->addSql('DROP TABLE sylius_trans_dictionnary');
        $this->addSql('DROP TABLE sylius_trans_error');
        $this->addSql('DROP TABLE sylius_trans_mode');
        $this->addSql('DROP TABLE sylius_trans_step');
        $this->addSql('ALTER TABLE dati_oauth2_access_token CHANGE user_id user_id INT DEFAULT NULL, CHANGE expires_at expires_at INT DEFAULT NULL, CHANGE scope scope VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dati_user CHANGE salt salt VARCHAR(255) DEFAULT NULL, CHANGE last_login last_login DATETIME DEFAULT NULL, CHANGE confirmation_token confirmation_token VARCHAR(180) DEFAULT NULL, CHANGE password_requested_at password_requested_at DATETIME DEFAULT NULL, CHANGE credit credit VARCHAR(255) DEFAULT NULL, CHANGE third_partie_id third_partie_id INT DEFAULT NULL, CHANGE last_call_id last_call_id INT DEFAULT NULL, CHANGE last_order_id last_order_id INT DEFAULT NULL, CHANGE call_balance_reference call_balance_reference VARCHAR(255) DEFAULT NULL, CHANGE security_token security_token VARCHAR(255) DEFAULT NULL, CHANGE security_token_requested_at security_token_requested_at DATETIME DEFAULT NULL, CHANGE sip_password sip_password VARCHAR(255) DEFAULT NULL, CHANGE password_reset_token password_reset_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dati_oauth2_refresh_token CHANGE user_id user_id INT DEFAULT NULL, CHANGE expires_at expires_at INT DEFAULT NULL, CHANGE scope scope VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dati_oauth2_auth_code CHANGE user_id user_id INT DEFAULT NULL, CHANGE expires_at expires_at INT DEFAULT NULL, CHANGE scope scope VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE message CHANGE thread_id thread_id INT DEFAULT NULL, CHANGE sender_id sender_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE message_metadata CHANGE message_id message_id INT DEFAULT NULL, CHANGE participant_id participant_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE thread CHANGE created_by_id created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE thread_metadata CHANGE thread_id thread_id INT DEFAULT NULL, CHANGE participant_id participant_id INT DEFAULT NULL, CHANGE last_participant_message_date last_participant_message_date DATETIME DEFAULT NULL, CHANGE last_message_date last_message_date DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE trans_dictionnary DROP FOREIGN KEY FK_947E79BB77E5854A');
        $this->addSql('ALTER TABLE job DROP FOREIGN KEY FK_FBD8E0F833EF8782');
        $this->addSql('ALTER TABLE trans_error DROP FOREIGN KEY FK_E21ED9AE33EF8782');
        $this->addSql('ALTER TABLE trans_step DROP FOREIGN KEY FK_50BD370F33EF8782');
        $this->addSql('CREATE TABLE sylius_currency_cache (id INT AUTO_INCREMENT NOT NULL, source VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, timestamp VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, hit_count INT NOT NULL, quote LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE sylius_job (id INT AUTO_INCREMENT NOT NULL, trans_id INT DEFAULT NULL, user_id INT NOT NULL, last_consulted_at DATETIME DEFAULT \'NULL\', step INT DEFAULT NULL, ussd_handler_number VARCHAR(32) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, step_description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, status VARCHAR(32) CHARACTER SET utf8mb4 DEFAULT \'\'\'WAITING\'\'\' COLLATE `utf8mb4_unicode_ci`, last_requested_at DATETIME DEFAULT \'NULL\', created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT \'NULL\', response LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, request LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_49F4BFD2A76ED395 (user_id), INDEX IDX_49F4BFD233EF8782 (trans_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE sylius_trans_dictionnary (id INT AUTO_INCREMENT NOT NULL, mode_id INT DEFAULT NULL, recipient_name_reg_exp VARCHAR(64) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, carreer_reg_exp VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, carreer_name VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, trans_code VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_BF0B688777E5854A (mode_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE sylius_trans_error (id INT AUTO_INCREMENT NOT NULL, trans_id INT DEFAULT NULL, reg_exp VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_455CA05733EF8782 (trans_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE sylius_trans_mode (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_A191167C5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE sylius_trans_step (id INT AUTO_INCREMENT NOT NULL, trans_id INT DEFAULT NULL, position INT NOT NULL, INDEX IDX_75E2AFEB33EF8782 (trans_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE sylius_job ADD CONSTRAINT FK_49F4BFD233EF8782 FOREIGN KEY (trans_id) REFERENCES sylius_trans_dictionnary (id)');
        $this->addSql('ALTER TABLE sylius_job ADD CONSTRAINT FK_49F4BFD2A76ED395 FOREIGN KEY (user_id) REFERENCES dati_user (id)');
        $this->addSql('ALTER TABLE sylius_trans_dictionnary ADD CONSTRAINT FK_BF0B688777E5854A FOREIGN KEY (mode_id) REFERENCES sylius_trans_mode (id)');
        $this->addSql('ALTER TABLE sylius_trans_error ADD CONSTRAINT FK_455CA05733EF8782 FOREIGN KEY (trans_id) REFERENCES sylius_trans_dictionnary (id)');
        $this->addSql('ALTER TABLE sylius_trans_step ADD CONSTRAINT FK_75E2AFEB33EF8782 FOREIGN KEY (trans_id) REFERENCES sylius_trans_dictionnary (id)');
        $this->addSql('DROP TABLE currency_cache');
        $this->addSql('DROP TABLE job');
        $this->addSql('DROP TABLE trans_error');
        $this->addSql('DROP TABLE trans_mode');
        $this->addSql('DROP TABLE trans_step');
        $this->addSql('DROP TABLE trans_dictionnary');
        $this->addSql('ALTER TABLE dati_oauth2_access_token CHANGE user_id user_id INT DEFAULT NULL, CHANGE expires_at expires_at INT DEFAULT NULL, CHANGE scope scope VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE dati_oauth2_auth_code CHANGE user_id user_id INT DEFAULT NULL, CHANGE expires_at expires_at INT DEFAULT NULL, CHANGE scope scope VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE dati_oauth2_refresh_token CHANGE user_id user_id INT DEFAULT NULL, CHANGE expires_at expires_at INT DEFAULT NULL, CHANGE scope scope VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE dati_user CHANGE salt salt VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE last_login last_login DATETIME DEFAULT \'NULL\', CHANGE confirmation_token confirmation_token VARCHAR(180) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE password_requested_at password_requested_at DATETIME DEFAULT \'NULL\', CHANGE credit credit VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE third_partie_id third_partie_id INT DEFAULT NULL, CHANGE last_call_id last_call_id INT DEFAULT NULL, CHANGE last_order_id last_order_id INT DEFAULT NULL, CHANGE call_balance_reference call_balance_reference VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE security_token security_token VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE security_token_requested_at security_token_requested_at DATETIME DEFAULT \'NULL\', CHANGE sip_password sip_password VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE password_reset_token password_reset_token VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE message CHANGE thread_id thread_id INT DEFAULT NULL, CHANGE sender_id sender_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE message_metadata CHANGE message_id message_id INT DEFAULT NULL, CHANGE participant_id participant_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE thread CHANGE created_by_id created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE thread_metadata CHANGE thread_id thread_id INT DEFAULT NULL, CHANGE participant_id participant_id INT DEFAULT NULL, CHANGE last_participant_message_date last_participant_message_date DATETIME DEFAULT \'NULL\', CHANGE last_message_date last_message_date DATETIME DEFAULT \'NULL\'');
    }
}
