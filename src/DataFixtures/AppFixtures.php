<?php

namespace App\DataFixtures;

use App\Entity\Phone;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i=0; $i < 20; $i++) { 
            $phones = new Phone();
            $phones->setBrand('Samsung');
            $phones->setModel('Galaxy S'.$i);
            $phones->setImage('/images/samsung-galaxy-s'.$i.'.jpg');
            $phones->setPrice(\rand(10000, 100000) / 100);
            $phones->setStock(\rand(0, 100));
            $phones->setReleaseAt(new \DateTimeImmutable('now'));
            $manager->persist($phones);
        }

        $manager->flush();
    }
}
