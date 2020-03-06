<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200305042106 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE dati_oauth2_access_token (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, token VARCHAR(128) NOT NULL, INDEX IDX_D6F3014619EB6921 (client_id), INDEX IDX_D6F30146A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dati_oauth2_client (id INT AUTO_INCREMENT NOT NULL, random_id VARCHAR(255) NOT NULL, redirect_uris LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', secret VARCHAR(255) NOT NULL, allowed_grant_types LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dati_user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, username_canonical VARCHAR(180) NOT NULL, email VARCHAR(180) NOT NULL, email_canonical VARCHAR(180) NOT NULL, enabled TINYINT(1) NOT NULL, salt VARCHAR(255) DEFAULT NULL, password VARCHAR(255) NOT NULL, last_login DATETIME DEFAULT NULL, confirmation_token VARCHAR(180) DEFAULT NULL, password_requested_at DATETIME DEFAULT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', credit VARCHAR(255) DEFAULT NULL, third_partie_id INT DEFAULT NULL, last_call_id INT DEFAULT NULL, last_order_id INT DEFAULT NULL, call_balance_reference VARCHAR(255) DEFAULT NULL, security_token VARCHAR(255) DEFAULT NULL, security_token_requested_at DATETIME DEFAULT NULL, sip_password VARCHAR(255) DEFAULT NULL, password_reset_token VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_DA8F9FC692FC23A8 (username_canonical), UNIQUE INDEX UNIQ_DA8F9FC6A0D96FBF (email_canonical), UNIQUE INDEX UNIQ_DA8F9FC6C05FB297 (confirmation_token), UNIQUE INDEX UNIQ_DA8F9FC6B38B4291 (security_token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dati_oauth2_refresh_token (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, token VARCHAR(128) NOT NULL, INDEX IDX_1BF97D8619EB6921 (client_id), INDEX IDX_1BF97D86A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dati_oauth2_auth_code (id INT AUTO_INCREMENT NOT NULL, client_id INT NOT NULL, user_id INT DEFAULT NULL, redirect_uri LONGTEXT NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, token VARCHAR(128) NOT NULL, INDEX IDX_89DE9F9719EB6921 (client_id), INDEX IDX_89DE9F97A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_currency_cache (id INT AUTO_INCREMENT NOT NULL, source VARCHAR(32) NOT NULL, timestamp VARCHAR(255) NOT NULL, hit_count INT NOT NULL, quote LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_job (id INT AUTO_INCREMENT NOT NULL, trans_id INT DEFAULT NULL, user_id INT NOT NULL, last_consulted_at DATETIME DEFAULT NULL, step INT DEFAULT NULL, ussd_handler_number VARCHAR(32) DEFAULT NULL, step_description LONGTEXT DEFAULT NULL, status VARCHAR(32) DEFAULT \'WAITING\', last_requested_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, response LONGTEXT DEFAULT NULL, request LONGTEXT DEFAULT NULL, INDEX IDX_49F4BFD233EF8782 (trans_id), UNIQUE INDEX UNIQ_49F4BFD2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, thread_id INT DEFAULT NULL, sender_id INT DEFAULT NULL, body LONGTEXT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_B6BD307FE2904019 (thread_id), INDEX IDX_B6BD307FF624B39D (sender_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE message_metadata (id INT AUTO_INCREMENT NOT NULL, message_id INT DEFAULT NULL, participant_id INT DEFAULT NULL, is_read TINYINT(1) NOT NULL, INDEX IDX_4632F005537A1329 (message_id), INDEX IDX_4632F0059D1C3019 (participant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE thread (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, subject VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, is_spam TINYINT(1) NOT NULL, INDEX IDX_31204C83B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE thread_metadata (id INT AUTO_INCREMENT NOT NULL, thread_id INT DEFAULT NULL, participant_id INT DEFAULT NULL, is_deleted TINYINT(1) NOT NULL, last_participant_message_date DATETIME DEFAULT NULL, last_message_date DATETIME DEFAULT NULL, INDEX IDX_40A577C8E2904019 (thread_id), INDEX IDX_40A577C89D1C3019 (participant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_trans_error (id INT AUTO_INCREMENT NOT NULL, trans_id INT DEFAULT NULL, reg_exp VARCHAR(255) NOT NULL, INDEX IDX_455CA05733EF8782 (trans_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transaction_error_translation (id INT UNSIGNED AUTO_INCREMENT NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_trans_mode (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(64) NOT NULL, UNIQUE INDEX UNIQ_A191167C5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_trans_step (id INT AUTO_INCREMENT NOT NULL, trans_id INT DEFAULT NULL, position INT NOT NULL, INDEX IDX_75E2AFEB33EF8782 (trans_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transaction_step_translation (id INT UNSIGNED AUTO_INCREMENT NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sylius_trans_dictionnary (id INT AUTO_INCREMENT NOT NULL, mode_id INT DEFAULT NULL, recipient_name_reg_exp VARCHAR(64) DEFAULT NULL, carreer_reg_exp VARCHAR(64) NOT NULL, carreer_name VARCHAR(64) NOT NULL, trans_code VARCHAR(64) NOT NULL, INDEX IDX_BF0B688777E5854A (mode_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE trans_dictionnary_translation (id INT UNSIGNED AUTO_INCREMENT NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE dati_oauth2_access_token ADD CONSTRAINT FK_D6F3014619EB6921 FOREIGN KEY (client_id) REFERENCES dati_oauth2_client (id)');
        $this->addSql('ALTER TABLE dati_oauth2_access_token ADD CONSTRAINT FK_D6F30146A76ED395 FOREIGN KEY (user_id) REFERENCES dati_user (id)');
        $this->addSql('ALTER TABLE dati_oauth2_refresh_token ADD CONSTRAINT FK_1BF97D8619EB6921 FOREIGN KEY (client_id) REFERENCES dati_oauth2_client (id)');
        $this->addSql('ALTER TABLE dati_oauth2_refresh_token ADD CONSTRAINT FK_1BF97D86A76ED395 FOREIGN KEY (user_id) REFERENCES dati_user (id)');
        $this->addSql('ALTER TABLE dati_oauth2_auth_code ADD CONSTRAINT FK_89DE9F9719EB6921 FOREIGN KEY (client_id) REFERENCES dati_oauth2_client (id)');
        $this->addSql('ALTER TABLE dati_oauth2_auth_code ADD CONSTRAINT FK_89DE9F97A76ED395 FOREIGN KEY (user_id) REFERENCES dati_user (id)');
        $this->addSql('ALTER TABLE sylius_job ADD CONSTRAINT FK_49F4BFD233EF8782 FOREIGN KEY (trans_id) REFERENCES sylius_trans_dictionnary (id)');
        $this->addSql('ALTER TABLE sylius_job ADD CONSTRAINT FK_49F4BFD2A76ED395 FOREIGN KEY (user_id) REFERENCES dati_user (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FE2904019 FOREIGN KEY (thread_id) REFERENCES thread (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FF624B39D FOREIGN KEY (sender_id) REFERENCES dati_user (id)');
        $this->addSql('ALTER TABLE message_metadata ADD CONSTRAINT FK_4632F005537A1329 FOREIGN KEY (message_id) REFERENCES message (id)');
        $this->addSql('ALTER TABLE message_metadata ADD CONSTRAINT FK_4632F0059D1C3019 FOREIGN KEY (participant_id) REFERENCES dati_user (id)');
        $this->addSql('ALTER TABLE thread ADD CONSTRAINT FK_31204C83B03A8386 FOREIGN KEY (created_by_id) REFERENCES dati_user (id)');
        $this->addSql('ALTER TABLE thread_metadata ADD CONSTRAINT FK_40A577C8E2904019 FOREIGN KEY (thread_id) REFERENCES thread (id)');
        $this->addSql('ALTER TABLE thread_metadata ADD CONSTRAINT FK_40A577C89D1C3019 FOREIGN KEY (participant_id) REFERENCES dati_user (id)');
        $this->addSql('ALTER TABLE sylius_trans_error ADD CONSTRAINT FK_455CA05733EF8782 FOREIGN KEY (trans_id) REFERENCES sylius_trans_dictionnary (id)');
        $this->addSql('ALTER TABLE sylius_trans_step ADD CONSTRAINT FK_75E2AFEB33EF8782 FOREIGN KEY (trans_id) REFERENCES sylius_trans_dictionnary (id)');
        $this->addSql('ALTER TABLE sylius_trans_dictionnary ADD CONSTRAINT FK_BF0B688777E5854A FOREIGN KEY (mode_id) REFERENCES sylius_trans_mode (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE dati_oauth2_access_token DROP FOREIGN KEY FK_D6F3014619EB6921');
        $this->addSql('ALTER TABLE dati_oauth2_refresh_token DROP FOREIGN KEY FK_1BF97D8619EB6921');
        $this->addSql('ALTER TABLE dati_oauth2_auth_code DROP FOREIGN KEY FK_89DE9F9719EB6921');
        $this->addSql('ALTER TABLE dati_oauth2_access_token DROP FOREIGN KEY FK_D6F30146A76ED395');
        $this->addSql('ALTER TABLE dati_oauth2_refresh_token DROP FOREIGN KEY FK_1BF97D86A76ED395');
        $this->addSql('ALTER TABLE dati_oauth2_auth_code DROP FOREIGN KEY FK_89DE9F97A76ED395');
        $this->addSql('ALTER TABLE sylius_job DROP FOREIGN KEY FK_49F4BFD2A76ED395');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FF624B39D');
        $this->addSql('ALTER TABLE message_metadata DROP FOREIGN KEY FK_4632F0059D1C3019');
        $this->addSql('ALTER TABLE thread DROP FOREIGN KEY FK_31204C83B03A8386');
        $this->addSql('ALTER TABLE thread_metadata DROP FOREIGN KEY FK_40A577C89D1C3019');
        $this->addSql('ALTER TABLE message_metadata DROP FOREIGN KEY FK_4632F005537A1329');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FE2904019');
        $this->addSql('ALTER TABLE thread_metadata DROP FOREIGN KEY FK_40A577C8E2904019');
        $this->addSql('ALTER TABLE sylius_trans_dictionnary DROP FOREIGN KEY FK_BF0B688777E5854A');
        $this->addSql('ALTER TABLE sylius_job DROP FOREIGN KEY FK_49F4BFD233EF8782');
        $this->addSql('ALTER TABLE sylius_trans_error DROP FOREIGN KEY FK_455CA05733EF8782');
        $this->addSql('ALTER TABLE sylius_trans_step DROP FOREIGN KEY FK_75E2AFEB33EF8782');
        $this->addSql('DROP TABLE dati_oauth2_access_token');
        $this->addSql('DROP TABLE dati_oauth2_client');
        $this->addSql('DROP TABLE dati_user');
        $this->addSql('DROP TABLE dati_oauth2_refresh_token');
        $this->addSql('DROP TABLE dati_oauth2_auth_code');
        $this->addSql('DROP TABLE sylius_currency_cache');
        $this->addSql('DROP TABLE sylius_job');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE message_metadata');
        $this->addSql('DROP TABLE thread');
        $this->addSql('DROP TABLE thread_metadata');
        $this->addSql('DROP TABLE sylius_trans_error');
        $this->addSql('DROP TABLE transaction_error_translation');
        $this->addSql('DROP TABLE sylius_trans_mode');
        $this->addSql('DROP TABLE sylius_trans_step');
        $this->addSql('DROP TABLE transaction_step_translation');
        $this->addSql('DROP TABLE sylius_trans_dictionnary');
        $this->addSql('DROP TABLE trans_dictionnary_translation');
    }
}
