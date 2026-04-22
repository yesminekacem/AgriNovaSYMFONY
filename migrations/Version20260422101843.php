<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260422101843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE post_reaction (id INT AUTO_INCREMENT NOT NULL, reaction VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL, id_post INT NOT NULL, user_id INT NOT NULL, INDEX IDX_1B3A8E56D1AA708F (id_post), INDEX IDX_1B3A8E56A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE post_reaction ADD CONSTRAINT FK_1B3A8E56D1AA708F FOREIGN KEY (id_post) REFERENCES post (id_post)');
        $this->addSql('ALTER TABLE post_reaction ADD CONSTRAINT FK_1B3A8E56A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY `cart_ibfk_1`');
        $this->addSql('DROP INDEX unique_user_product ON cart');
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY `cart_ibfk_1`');
        $this->addSql('ALTER TABLE cart CHANGE added_at added_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT FK_BA388B74584665A FOREIGN KEY (product_id) REFERENCES product_listing (listing_id)');
        $this->addSql('DROP INDEX product_id ON cart');
        $this->addSql('CREATE INDEX IDX_BA388B74584665A ON cart (product_id)');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (product_id) REFERENCES product_listing (listing_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY `fk_comment_post`');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY `fk_comment_post`');
        $this->addSql('ALTER TABLE comment CHANGE likes likes INT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CD1AA708F FOREIGN KEY (id_post) REFERENCES post (id_post)');
        $this->addSql('DROP INDEX fk_comment_post ON comment');
        $this->addSql('CREATE INDEX IDX_9474526CD1AA708F ON comment (id_post)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT `fk_comment_post` FOREIGN KEY (id_post) REFERENCES post (id_post) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE crop CHANGE name name VARCHAR(255) NOT NULL, CHANGE type type VARCHAR(255) NOT NULL, CHANGE variety variety VARCHAR(255) NOT NULL, CHANGE planting_date planting_date DATE NOT NULL, CHANGE expected_harvest_date expected_harvest_date DATE NOT NULL, CHANGE growth_stage growth_stage VARCHAR(255) NOT NULL, CHANGE area_size area_size NUMERIC(8, 2) NOT NULL, CHANGE status status VARCHAR(255) NOT NULL, CHANGE image_path image_path VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE inventory ADD owner_id INT DEFAULT NULL, CHANGE inventory_id inventory_id INT AUTO_INCREMENT NOT NULL, CHANGE item_type item_type VARCHAR(20) NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE quantity quantity INT NOT NULL, CHANGE unit_price unit_price DOUBLE PRECISION NOT NULL, CHANGE condition_status condition_status VARCHAR(20) NOT NULL, CHANGE is_rentable is_rentable TINYINT NOT NULL, CHANGE rental_price_per_day rental_price_per_day DOUBLE PRECISION DEFAULT NULL, CHANGE rental_status rental_status VARCHAR(20) NOT NULL, CHANGE total_usage_hours total_usage_hours INT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE inventory ADD CONSTRAINT FK_B12D4A367E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_B12D4A367E3C61F9 ON inventory (owner_id)');
        $this->addSql('ALTER TABLE notifications CHANGE is_read is_read TINYINT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY `order_items_ibfk_1`');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY `order_items_ibfk_2`');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY `order_items_ibfk_1`');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY `order_items_ibfk_2`');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB08D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id)');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB04584665A FOREIGN KEY (product_id) REFERENCES product_listing (listing_id)');
        $this->addSql('DROP INDEX order_id ON order_items');
        $this->addSql('CREATE INDEX IDX_62809DB08D9F6D38 ON order_items (order_id)');
        $this->addSql('DROP INDEX product_id ON order_items');
        $this->addSql('CREATE INDEX IDX_62809DB04584665A ON order_items (product_id)');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (product_id) REFERENCES product_listing (listing_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE orders CHANGE order_date order_date DATETIME DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT NULL, CHANGE payment_method payment_method VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE post ADD image_path VARCHAR(255) DEFAULT NULL, CHANGE status status VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE author_id author_id INT NOT NULL');
        $this->addSql('ALTER TABLE product_listing CHANGE user_id user_id VARCHAR(50) NOT NULL, CHANGE quantity quantity INT NOT NULL, CHANGE status status VARCHAR(255) DEFAULT NULL, CHANGE category category VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE rental CHANGE rental_id rental_id INT AUTO_INCREMENT NOT NULL, CHANGE daily_rate daily_rate DOUBLE PRECISION NOT NULL, CHANGE total_days total_days INT DEFAULT NULL, CHANGE total_cost total_cost DOUBLE PRECISION DEFAULT NULL, CHANGE security_deposit security_deposit DOUBLE PRECISION DEFAULT NULL, CHANGE late_fee late_fee DOUBLE PRECISION DEFAULT NULL, CHANGE requires_delivery requires_delivery TINYINT DEFAULT NULL, CHANGE delivery_fee delivery_fee DOUBLE PRECISION DEFAULT NULL, CHANGE rental_status rental_status VARCHAR(20) NOT NULL, CHANGE pickup_photos pickup_photos LONGTEXT DEFAULT NULL, CHANGE return_photos return_photos LONGTEXT DEFAULT NULL, CHANGE damage_notes damage_notes LONGTEXT DEFAULT NULL, CHANGE owner_review owner_review LONGTEXT DEFAULT NULL, CHANGE renter_review renter_review LONGTEXT DEFAULT NULL, CHANGE payment_status payment_status VARCHAR(20) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE rental ADD CONSTRAINT FK_1619C27D9EEA759 FOREIGN KEY (inventory_id) REFERENCES inventory (inventory_id)');
        $this->addSql('CREATE INDEX IDX_1619C27D9EEA759 ON rental (inventory_id)');
        $this->addSql('ALTER TABLE rental_history CHANGE history_id history_id INT AUTO_INCREMENT NOT NULL, CHANGE action_type action_type VARCHAR(20) NOT NULL, CHANGE action_description action_description LONGTEXT DEFAULT NULL, CHANGE action_timestamp action_timestamp DATETIME NOT NULL');
        $this->addSql('ALTER TABLE rental_history ADD CONSTRAINT FK_F45CAAA4A7CF2329 FOREIGN KEY (rental_id) REFERENCES rental (rental_id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_F45CAAA4A7CF2329 ON rental_history (rental_id)');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY `fk_task_crop`');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY `fk_task_crop`');
        $this->addSql('ALTER TABLE task CHANGE description description LONGTEXT NOT NULL, CHANGE task_type task_type VARCHAR(50) NOT NULL, CHANGE scheduled_date scheduled_date DATE NOT NULL, CHANGE status status VARCHAR(50) NOT NULL, CHANGE assigned_to assigned_to VARCHAR(100) NOT NULL, CHANGE cost cost NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25888579EE FOREIGN KEY (crop_id) REFERENCES crop (crop_id)');
        $this->addSql('DROP INDEX fk_task_crop ON task');
        $this->addSql('CREATE INDEX IDX_527EDB25888579EE ON task (crop_id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT `fk_task_crop` FOREIGN KEY (crop_id) REFERENCES crop (crop_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user DROP face_data, CHANGE full_name full_name VARCHAR(100) DEFAULT NULL, CHANGE role role VARCHAR(100) DEFAULT \'USER\' NOT NULL, CHANGE profile_image profile_image VARCHAR(100) DEFAULT NULL, CHANGE email_verified email_verified TINYINT DEFAULT 0 NOT NULL, CHANGE banned banned TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE post_reaction DROP FOREIGN KEY FK_1B3A8E56D1AA708F');
        $this->addSql('ALTER TABLE post_reaction DROP FOREIGN KEY FK_1B3A8E56A76ED395');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP TABLE post_reaction');
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY FK_BA388B74584665A');
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY FK_BA388B74584665A');
        $this->addSql('ALTER TABLE cart CHANGE added_at added_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (product_id) REFERENCES product_listing (listing_id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX unique_user_product ON cart (user_id, product_id)');
        $this->addSql('DROP INDEX idx_ba388b74584665a ON cart');
        $this->addSql('CREATE INDEX product_id ON cart (product_id)');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT FK_BA388B74584665A FOREIGN KEY (product_id) REFERENCES product_listing (listing_id)');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CD1AA708F');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CD1AA708F');
        $this->addSql('ALTER TABLE comment CHANGE likes likes INT DEFAULT 0, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT `fk_comment_post` FOREIGN KEY (id_post) REFERENCES post (id_post) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_9474526cd1aa708f ON comment');
        $this->addSql('CREATE INDEX fk_comment_post ON comment (id_post)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CD1AA708F FOREIGN KEY (id_post) REFERENCES post (id_post)');
        $this->addSql('ALTER TABLE crop CHANGE name name VARCHAR(100) NOT NULL, CHANGE type type VARCHAR(50) NOT NULL, CHANGE variety variety VARCHAR(100) DEFAULT NULL, CHANGE planting_date planting_date DATE DEFAULT NULL, CHANGE expected_harvest_date expected_harvest_date DATE DEFAULT NULL, CHANGE growth_stage growth_stage VARCHAR(50) DEFAULT NULL, CHANGE area_size area_size NUMERIC(8, 2) DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT NULL, CHANGE image_path image_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE inventory DROP FOREIGN KEY FK_B12D4A367E3C61F9');
        $this->addSql('DROP INDEX IDX_B12D4A367E3C61F9 ON inventory');
        $this->addSql('ALTER TABLE inventory DROP owner_id, CHANGE inventory_id inventory_id INT NOT NULL, CHANGE item_type item_type ENUM(\'EQUIPMENT\', \'TOOL\', \'CONSUMABLE\', \'STORAGE\') NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE quantity quantity INT DEFAULT 1 NOT NULL, CHANGE unit_price unit_price DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE condition_status condition_status ENUM(\'EXCELLENT\', \'GOOD\', \'FAIR\', \'POOR\') DEFAULT \'GOOD\' NOT NULL, CHANGE is_rentable is_rentable TINYINT DEFAULT 0 NOT NULL, CHANGE rental_price_per_day rental_price_per_day DOUBLE PRECISION DEFAULT \'0\', CHANGE rental_status rental_status ENUM(\'AVAILABLE\', \'RENTED_OUT\', \'IN_USE\', \'MAINTENANCE\', \'RETIRED\') DEFAULT \'AVAILABLE\' NOT NULL, CHANGE total_usage_hours total_usage_hours INT DEFAULT 0, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE notifications CHANGE is_read is_read TINYINT DEFAULT 0 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE orders CHANGE order_date order_date DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE status status VARCHAR(50) DEFAULT \'pending\', CHANGE payment_method payment_method VARCHAR(50) DEFAULT \'cash\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB08D9F6D38');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB04584665A');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB08D9F6D38');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB04584665A');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (product_id) REFERENCES product_listing (listing_id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_62809db08d9f6d38 ON order_items');
        $this->addSql('CREATE INDEX order_id ON order_items (order_id)');
        $this->addSql('DROP INDEX idx_62809db04584665a ON order_items');
        $this->addSql('CREATE INDEX product_id ON order_items (product_id)');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB08D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id)');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB04584665A FOREIGN KEY (product_id) REFERENCES product_listing (listing_id)');
        $this->addSql('ALTER TABLE post DROP image_path, CHANGE status status ENUM(\'ACTIVE\', \'ARCHIVED\') DEFAULT \'ACTIVE\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE author_id author_id INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE product_listing CHANGE user_id user_id VARCHAR(50) DEFAULT \'user1\' NOT NULL, CHANGE quantity quantity INT DEFAULT 0 NOT NULL, CHANGE status status ENUM(\'available\', \'sold-out\') DEFAULT \'available\', CHANGE category category VARCHAR(50) DEFAULT \'Vegetables\'');
        $this->addSql('ALTER TABLE rental DROP FOREIGN KEY FK_1619C27D9EEA759');
        $this->addSql('DROP INDEX IDX_1619C27D9EEA759 ON rental');
        $this->addSql('ALTER TABLE rental CHANGE rental_id rental_id INT NOT NULL, CHANGE daily_rate daily_rate DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE total_days total_days INT DEFAULT 0, CHANGE total_cost total_cost DOUBLE PRECISION DEFAULT \'0\', CHANGE security_deposit security_deposit DOUBLE PRECISION DEFAULT \'0\', CHANGE late_fee late_fee DOUBLE PRECISION DEFAULT \'0\', CHANGE requires_delivery requires_delivery TINYINT DEFAULT 0, CHANGE delivery_fee delivery_fee DOUBLE PRECISION DEFAULT \'0\', CHANGE rental_status rental_status ENUM(\'PENDING\', \'APPROVED\', \'ACTIVE\', \'RETURNED\', \'COMPLETED\', \'CANCELLED\', \'DISPUTED\') DEFAULT \'PENDING\' NOT NULL, CHANGE pickup_photos pickup_photos TEXT DEFAULT NULL, CHANGE return_photos return_photos TEXT DEFAULT NULL, CHANGE damage_notes damage_notes TEXT DEFAULT NULL, CHANGE owner_review owner_review TEXT DEFAULT NULL, CHANGE renter_review renter_review TEXT DEFAULT NULL, CHANGE payment_status payment_status ENUM(\'PENDING\', \'DEPOSIT_PAID\', \'FULLY_PAID\', \'REFUNDED\', \'DISPUTED\') DEFAULT \'PENDING\' NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE rental_history DROP FOREIGN KEY FK_F45CAAA4A7CF2329');
        $this->addSql('DROP INDEX IDX_F45CAAA4A7CF2329 ON rental_history');
        $this->addSql('ALTER TABLE rental_history CHANGE history_id history_id INT NOT NULL, CHANGE action_type action_type ENUM(\'CREATED\', \'APPROVED\', \'ACTIVATED\', \'RETURNED\', \'COMPLETED\', \'CANCELLED\', \'UPDATED\', \'DISPUTED\') NOT NULL, CHANGE action_description action_description TEXT DEFAULT NULL, CHANGE action_timestamp action_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25888579EE');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25888579EE');
        $this->addSql('ALTER TABLE task CHANGE description description TEXT DEFAULT NULL, CHANGE task_type task_type VARCHAR(50) DEFAULT NULL, CHANGE scheduled_date scheduled_date DATE DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT NULL, CHANGE assigned_to assigned_to VARCHAR(100) DEFAULT NULL, CHANGE cost cost NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT `fk_task_crop` FOREIGN KEY (crop_id) REFERENCES crop (crop_id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_527edb25888579ee ON task');
        $this->addSql('CREATE INDEX fk_task_crop ON task (crop_id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25888579EE FOREIGN KEY (crop_id) REFERENCES crop (crop_id)');
        $this->addSql('ALTER TABLE user ADD face_data MEDIUMTEXT DEFAULT NULL, CHANGE role role VARCHAR(100) NOT NULL, CHANGE full_name full_name VARCHAR(100) NOT NULL, CHANGE email_verified email_verified TINYINT DEFAULT 0, CHANGE profile_image profile_image VARCHAR(255) DEFAULT NULL, CHANGE banned banned TINYINT DEFAULT 0');
    }
}
