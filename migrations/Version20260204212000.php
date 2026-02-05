<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260204212000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add promotion accounting fields to order/order_item and create order_promotion table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bookshop_order ADD tax_total INT DEFAULT NULL, ADD order_promotion_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE bookshop_order ADD CONSTRAINT FK_6E8239365F7B3C39 FOREIGN KEY (order_promotion_id) REFERENCES bookshop_promotion (id)');
        $this->addSql('CREATE INDEX IDX_6E8239365F7B3C39 ON bookshop_order (order_promotion_id)');

        $this->addSql('ALTER TABLE bookshop_order_item ADD discount INT DEFAULT NULL, ADD discount_value INT NOT NULL, ADD distributed_order_discount_value INT NOT NULL, ADD discounted_unit_price INT NOT NULL');
        $this->addSql('UPDATE bookshop_order_item SET discount_value = 0, distributed_order_discount_value = 0, discounted_unit_price = unit_price');

        $this->addSql('CREATE TABLE bookshop_order_promotion (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, promotion_id INT NOT NULL, position INT NOT NULL, INDEX IDX_9096E4DF8D9F6D38 (order_id), INDEX IDX_9096E4DF139DF194 (promotion_id), UNIQUE INDEX uniq_order_promotion (order_id, promotion_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bookshop_order_promotion ADD CONSTRAINT FK_9096E4DF8D9F6D38 FOREIGN KEY (order_id) REFERENCES bookshop_order (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bookshop_order_promotion ADD CONSTRAINT FK_9096E4DF139DF194 FOREIGN KEY (promotion_id) REFERENCES bookshop_promotion (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bookshop_order_promotion DROP FOREIGN KEY FK_9096E4DF8D9F6D38');
        $this->addSql('ALTER TABLE bookshop_order_promotion DROP FOREIGN KEY FK_9096E4DF139DF194');
        $this->addSql('DROP TABLE bookshop_order_promotion');

        $this->addSql('ALTER TABLE bookshop_order_item DROP discount, DROP discount_value, DROP distributed_order_discount_value, DROP discounted_unit_price');

        $this->addSql('ALTER TABLE bookshop_order DROP FOREIGN KEY FK_6E8239365F7B3C39');
        $this->addSql('DROP INDEX IDX_6E8239365F7B3C39 ON bookshop_order');
        $this->addSql('ALTER TABLE bookshop_order DROP tax_total, DROP order_promotion_id');
    }
}
