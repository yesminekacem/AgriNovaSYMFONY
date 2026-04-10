<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260410120835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('DROP INDEX unique_user_product ON cart');
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY `cart_ibfk_1`');
        $this->addSql('ALTER TABLE cart CHANGE id id INT NOT NULL, CHANGE product_id product_id INT DEFAULT NULL, CHANGE added_at added_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX product_id ON cart');
        $this->addSql('CREATE INDEX IDX_BA388B74584665A ON cart (product_id)');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (product_id) REFERENCES product_listing (listing_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY `fk_comment_post`');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY `fk_comment_post`');
        $this->addSql('ALTER TABLE comment CHANGE id_comment id_comment INT NOT NULL, CHANGE id_post id_post INT DEFAULT NULL, CHANGE content content LONGTEXT NOT NULL, CHANGE likes likes INT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CD1AA708F FOREIGN KEY (id_post) REFERENCES post (id_post) ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk_comment_post ON comment');
        $this->addSql('CREATE INDEX IDX_9474526CD1AA708F ON comment (id_post)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT `fk_comment_post` FOREIGN KEY (id_post) REFERENCES post (id_post) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE crop CHANGE crop_id crop_id INT NOT NULL, CHANGE variety variety VARCHAR(100) NOT NULL, CHANGE planting_date planting_date DATE NOT NULL, CHANGE expected_harvest_date expected_harvest_date DATE NOT NULL, CHANGE growth_stage growth_stage VARCHAR(50) NOT NULL, CHANGE area_size area_size DOUBLE PRECISION NOT NULL, CHANGE status status VARCHAR(50) NOT NULL, CHANGE image_path image_path VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE inventory CHANGE item_type item_type VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE quantity quantity INT NOT NULL, CHANGE unit_price unit_price DOUBLE PRECISION NOT NULL, CHANGE purchase_date purchase_date DATE NOT NULL, CHANGE condition_status condition_status VARCHAR(255) NOT NULL, CHANGE is_rentable is_rentable TINYINT NOT NULL, CHANGE rental_price_per_day rental_price_per_day DOUBLE PRECISION NOT NULL, CHANGE rental_status rental_status VARCHAR(255) NOT NULL, CHANGE last_maintenance_date last_maintenance_date DATE NOT NULL, CHANGE next_maintenance_date next_maintenance_date DATE NOT NULL, CHANGE total_usage_hours total_usage_hours INT NOT NULL, CHANGE owner_name owner_name VARCHAR(255) NOT NULL, CHANGE owner_contact owner_contact VARCHAR(100) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL, CHANGE image_path image_path VARCHAR(500) NOT NULL');
        $this->addSql('ALTER TABLE notifications CHANGE id id INT NOT NULL, CHANGE is_read is_read TINYINT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY `order_items_ibfk_1`');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY `order_items_ibfk_2`');
        $this->addSql('ALTER TABLE order_items CHANGE id id INT NOT NULL, CHANGE order_id order_id INT DEFAULT NULL, CHANGE product_id product_id INT DEFAULT NULL, CHANGE price_per_unit price_per_unit DOUBLE PRECISION NOT NULL, CHANGE subtotal subtotal DOUBLE PRECISION NOT NULL');
        $this->addSql('DROP INDEX order_id ON order_items');
        $this->addSql('CREATE INDEX IDX_62809DB08D9F6D38 ON order_items (order_id)');
        $this->addSql('DROP INDEX product_id ON order_items');
        $this->addSql('CREATE INDEX IDX_62809DB04584665A ON order_items (product_id)');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (product_id) REFERENCES product_listing (listing_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE orders CHANGE id id INT NOT NULL, CHANGE order_date order_date DATETIME NOT NULL, CHANGE total_price total_price DOUBLE PRECISION NOT NULL, CHANGE status status VARCHAR(50) NOT NULL, CHANGE delivery_address delivery_address LONGTEXT NOT NULL, CHANGE payment_method payment_method VARCHAR(50) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE delivery_lat delivery_lat DOUBLE PRECISION NOT NULL, CHANGE delivery_lng delivery_lng DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE post CHANGE id_post id_post INT NOT NULL, CHANGE content content LONGTEXT NOT NULL, CHANGE category category VARCHAR(80) NOT NULL, CHANGE status status VARCHAR(255) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE author_id author_id INT NOT NULL');
        $this->addSql('ALTER TABLE product_listing CHANGE listing_id listing_id INT NOT NULL, CHANGE user_id user_id VARCHAR(50) NOT NULL, CHANGE price_per_unit price_per_unit DOUBLE PRECISION NOT NULL, CHANGE quantity quantity INT NOT NULL, CHANGE status status VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE picture picture VARCHAR(255) NOT NULL, CHANGE category category VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE rental CHANGE renter_address renter_address VARCHAR(500) NOT NULL, CHANGE actual_return_date actual_return_date DATE NOT NULL, CHANGE daily_rate daily_rate DOUBLE PRECISION NOT NULL, CHANGE total_days total_days INT NOT NULL, CHANGE total_cost total_cost DOUBLE PRECISION NOT NULL, CHANGE security_deposit security_deposit DOUBLE PRECISION NOT NULL, CHANGE late_fee late_fee DOUBLE PRECISION NOT NULL, CHANGE requires_delivery requires_delivery TINYINT NOT NULL, CHANGE delivery_fee delivery_fee DOUBLE PRECISION NOT NULL, CHANGE delivery_address delivery_address VARCHAR(500) NOT NULL, CHANGE rental_status rental_status VARCHAR(255) NOT NULL, CHANGE pickup_condition pickup_condition VARCHAR(255) NOT NULL, CHANGE return_condition return_condition VARCHAR(255) NOT NULL, CHANGE pickup_photos pickup_photos LONGTEXT NOT NULL, CHANGE return_photos return_photos LONGTEXT NOT NULL, CHANGE damage_notes damage_notes LONGTEXT NOT NULL, CHANGE owner_rating owner_rating INT NOT NULL, CHANGE renter_rating renter_rating INT NOT NULL, CHANGE owner_review owner_review LONGTEXT NOT NULL, CHANGE renter_review renter_review LONGTEXT NOT NULL, CHANGE payment_status payment_status VARCHAR(255) NOT NULL, CHANGE payment_method payment_method VARCHAR(100) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE rental_history CHANGE action_type action_type VARCHAR(255) NOT NULL, CHANGE action_description action_description LONGTEXT NOT NULL, CHANGE performed_by performed_by VARCHAR(255) NOT NULL, CHANGE action_timestamp action_timestamp DATETIME NOT NULL');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY `fk_task_crop`');
        $this->addSql('ALTER TABLE task CHANGE task_id task_id INT NOT NULL, CHANGE crop_id crop_id INT DEFAULT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE task_type task_type VARCHAR(50) NOT NULL, CHANGE scheduled_date scheduled_date DATE NOT NULL, CHANGE completed_date completed_date DATE NOT NULL, CHANGE status status VARCHAR(50) NOT NULL, CHANGE assigned_to assigned_to VARCHAR(100) NOT NULL, CHANGE cost cost DOUBLE PRECISION NOT NULL');
        $this->addSql('DROP INDEX fk_task_crop ON task');
        $this->addSql('CREATE INDEX IDX_527EDB25888579EE ON task (crop_id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT `fk_task_crop` FOREIGN KEY (crop_id) REFERENCES crop (crop_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user CHANGE id id INT NOT NULL, CHANGE profile_image profile_image VARCHAR(255) NOT NULL, CHANGE email_verified email_verified TINYINT NOT NULL, CHANGE face_data face_data LONGTEXT NOT NULL, CHANGE banned banned TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY FK_BA388B74584665A');
        $this->addSql('ALTER TABLE cart CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE added_at added_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE product_id product_id INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX unique_user_product ON cart (user_id, product_id)');
        $this->addSql('DROP INDEX idx_ba388b74584665a ON cart');
        $this->addSql('CREATE INDEX product_id ON cart (product_id)');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT FK_BA388B74584665A FOREIGN KEY (product_id) REFERENCES product_listing (listing_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CD1AA708F');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CD1AA708F');
        $this->addSql('ALTER TABLE comment CHANGE id_comment id_comment INT AUTO_INCREMENT NOT NULL, CHANGE content content TEXT NOT NULL, CHANGE likes likes INT DEFAULT 0, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE id_post id_post INT NOT NULL');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT `fk_comment_post` FOREIGN KEY (id_post) REFERENCES post (id_post) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_9474526cd1aa708f ON comment');
        $this->addSql('CREATE INDEX fk_comment_post ON comment (id_post)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CD1AA708F FOREIGN KEY (id_post) REFERENCES post (id_post) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE crop CHANGE crop_id crop_id INT AUTO_INCREMENT NOT NULL, CHANGE variety variety VARCHAR(100) DEFAULT NULL, CHANGE planting_date planting_date DATE DEFAULT NULL, CHANGE expected_harvest_date expected_harvest_date DATE DEFAULT NULL, CHANGE growth_stage growth_stage VARCHAR(50) DEFAULT NULL, CHANGE area_size area_size NUMERIC(8, 2) DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT NULL, CHANGE image_path image_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE inventory CHANGE item_type item_type ENUM(\'EQUIPMENT\', \'TOOL\', \'CONSUMABLE\', \'STORAGE\') NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE quantity quantity INT DEFAULT 1 NOT NULL, CHANGE unit_price unit_price DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE purchase_date purchase_date DATE DEFAULT NULL, CHANGE condition_status condition_status ENUM(\'EXCELLENT\', \'GOOD\', \'FAIR\', \'POOR\') DEFAULT \'GOOD\' NOT NULL, CHANGE is_rentable is_rentable TINYINT DEFAULT 0 NOT NULL, CHANGE rental_price_per_day rental_price_per_day DOUBLE PRECISION DEFAULT \'0\', CHANGE rental_status rental_status ENUM(\'AVAILABLE\', \'RENTED_OUT\', \'IN_USE\', \'MAINTENANCE\', \'RETIRED\') DEFAULT \'AVAILABLE\' NOT NULL, CHANGE last_maintenance_date last_maintenance_date DATE DEFAULT NULL, CHANGE next_maintenance_date next_maintenance_date DATE DEFAULT NULL, CHANGE total_usage_hours total_usage_hours INT DEFAULT 0, CHANGE owner_name owner_name VARCHAR(255) DEFAULT NULL, CHANGE owner_contact owner_contact VARCHAR(100) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE image_path image_path VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE notifications CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE is_read is_read TINYINT DEFAULT 0 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE orders CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE order_date order_date DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE total_price total_price NUMERIC(10, 2) NOT NULL, CHANGE status status VARCHAR(50) DEFAULT \'pending\', CHANGE delivery_address delivery_address TEXT NOT NULL, CHANGE payment_method payment_method VARCHAR(50) DEFAULT \'cash\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE delivery_lat delivery_lat DOUBLE PRECISION DEFAULT NULL, CHANGE delivery_lng delivery_lng DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB08D9F6D38');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY FK_62809DB04584665A');
        $this->addSql('ALTER TABLE order_items CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE price_per_unit price_per_unit NUMERIC(10, 2) NOT NULL, CHANGE subtotal subtotal NUMERIC(10, 2) NOT NULL, CHANGE order_id order_id INT NOT NULL, CHANGE product_id product_id INT NOT NULL');
        $this->addSql('DROP INDEX idx_62809db08d9f6d38 ON order_items');
        $this->addSql('CREATE INDEX order_id ON order_items (order_id)');
        $this->addSql('DROP INDEX idx_62809db04584665a ON order_items');
        $this->addSql('CREATE INDEX product_id ON order_items (product_id)');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB08D9F6D38 FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT FK_62809DB04584665A FOREIGN KEY (product_id) REFERENCES product_listing (listing_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post CHANGE id_post id_post INT AUTO_INCREMENT NOT NULL, CHANGE content content TEXT NOT NULL, CHANGE category category VARCHAR(80) DEFAULT NULL, CHANGE status status ENUM(\'ACTIVE\', \'ARCHIVED\') DEFAULT \'ACTIVE\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE author_id author_id INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE product_listing CHANGE listing_id listing_id INT AUTO_INCREMENT NOT NULL, CHANGE user_id user_id VARCHAR(50) DEFAULT \'user1\' NOT NULL, CHANGE price_per_unit price_per_unit NUMERIC(10, 2) NOT NULL, CHANGE quantity quantity INT DEFAULT 0 NOT NULL, CHANGE status status ENUM(\'available\', \'sold-out\') DEFAULT \'available\', CHANGE description description TEXT DEFAULT NULL, CHANGE picture picture VARCHAR(255) DEFAULT NULL, CHANGE category category VARCHAR(50) DEFAULT \'Vegetables\'');
        $this->addSql('ALTER TABLE rental CHANGE renter_address renter_address VARCHAR(500) DEFAULT NULL, CHANGE actual_return_date actual_return_date DATE DEFAULT NULL, CHANGE daily_rate daily_rate DOUBLE PRECISION DEFAULT \'0\' NOT NULL, CHANGE total_days total_days INT DEFAULT 0, CHANGE total_cost total_cost DOUBLE PRECISION DEFAULT \'0\', CHANGE security_deposit security_deposit DOUBLE PRECISION DEFAULT \'0\', CHANGE late_fee late_fee DOUBLE PRECISION DEFAULT \'0\', CHANGE requires_delivery requires_delivery TINYINT DEFAULT 0, CHANGE delivery_fee delivery_fee DOUBLE PRECISION DEFAULT \'0\', CHANGE delivery_address delivery_address VARCHAR(500) DEFAULT NULL, CHANGE rental_status rental_status ENUM(\'PENDING\', \'APPROVED\', \'ACTIVE\', \'RETURNED\', \'COMPLETED\', \'CANCELLED\', \'DISPUTED\') DEFAULT \'PENDING\' NOT NULL, CHANGE pickup_condition pickup_condition VARCHAR(255) DEFAULT NULL, CHANGE return_condition return_condition VARCHAR(255) DEFAULT NULL, CHANGE pickup_photos pickup_photos TEXT DEFAULT NULL, CHANGE return_photos return_photos TEXT DEFAULT NULL, CHANGE damage_notes damage_notes TEXT DEFAULT NULL, CHANGE owner_rating owner_rating INT DEFAULT NULL, CHANGE renter_rating renter_rating INT DEFAULT NULL, CHANGE owner_review owner_review TEXT DEFAULT NULL, CHANGE renter_review renter_review TEXT DEFAULT NULL, CHANGE payment_status payment_status ENUM(\'PENDING\', \'DEPOSIT_PAID\', \'FULLY_PAID\', \'REFUNDED\', \'DISPUTED\') DEFAULT \'PENDING\' NOT NULL, CHANGE payment_method payment_method VARCHAR(100) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE rental_history CHANGE action_type action_type ENUM(\'CREATED\', \'APPROVED\', \'ACTIVATED\', \'RETURNED\', \'COMPLETED\', \'CANCELLED\', \'UPDATED\', \'DISPUTED\') NOT NULL, CHANGE action_description action_description TEXT DEFAULT NULL, CHANGE performed_by performed_by VARCHAR(255) DEFAULT NULL, CHANGE action_timestamp action_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25888579EE');
        $this->addSql('ALTER TABLE task CHANGE task_id task_id INT AUTO_INCREMENT NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE task_type task_type VARCHAR(50) DEFAULT NULL, CHANGE scheduled_date scheduled_date DATE DEFAULT NULL, CHANGE completed_date completed_date DATE DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT NULL, CHANGE assigned_to assigned_to VARCHAR(100) DEFAULT NULL, CHANGE cost cost NUMERIC(10, 2) DEFAULT NULL, CHANGE crop_id crop_id INT NOT NULL');
        $this->addSql('DROP INDEX idx_527edb25888579ee ON task');
        $this->addSql('CREATE INDEX fk_task_crop ON task (crop_id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25888579EE FOREIGN KEY (crop_id) REFERENCES crop (crop_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE profile_image profile_image VARCHAR(255) DEFAULT NULL, CHANGE email_verified email_verified TINYINT DEFAULT 0, CHANGE face_data face_data MEDIUMTEXT DEFAULT NULL, CHANGE banned banned TINYINT DEFAULT 0');
    }
}
