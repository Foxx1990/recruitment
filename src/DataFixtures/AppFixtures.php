<?php

namespace App\DataFixtures;

use App\Component\Product\Entity\Product;
use App\Component\Promotion\Entity\Promotion;
use App\Component\User\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $firstUser = new User();
        $firstUser->setName('First user');
        $manager->persist($firstUser);

        $firstProduct = new Product();
        $firstProduct->setName('First product');
        $firstProduct->setCode('first');
        $firstProduct->setType(Product::TYPE_AUDIO);
        $firstProduct->setPrice(1033);
        $firstProduct->setTaxRate(23);
        $manager->persist($firstProduct);

        $secondProduct = new Product();
        $secondProduct->setName('Second product');
        $secondProduct->setCode('second');
        $secondProduct->setType(Product::TYPE_BOOK);
        $secondProduct->setPrice(87);
        $secondProduct->setTaxRate(8);
        $manager->persist($secondProduct);

        $thirdProduct = new Product();
        $thirdProduct->setName('Third product');
        $thirdProduct->setCode('third');
        $thirdProduct->setType(Product::TYPE_COURSE);
        $thirdProduct->setPrice(1987);
        $thirdProduct->setTaxRate(null);
        $manager->persist($thirdProduct);

        $orderPromotion = new Promotion();
        $orderPromotion->setType(Promotion::TYPE_ORDER);
        $orderPromotion->setPercentageDiscount(13);
        $manager->persist($orderPromotion);

        $firstItemPromotion = new Promotion();
        $firstItemPromotion->setType(Promotion::TYPE_ITEM);
        $firstItemPromotion->setPercentageDiscount(15);
        $firstItemPromotion->setProductTypesFilter([Product::TYPE_BOOK, Product::TYPE_AUDIO]);
        $manager->persist($firstItemPromotion);

        $secondItemPromotion = new Promotion();
        $secondItemPromotion->setType(Promotion::TYPE_ITEM);
        $secondItemPromotion->setPercentageDiscount(24);
        $secondItemPromotion->setProductTypesFilter([Product::TYPE_AUDIO]);
        $manager->persist($secondItemPromotion);

        $manager->flush();
    }
}
