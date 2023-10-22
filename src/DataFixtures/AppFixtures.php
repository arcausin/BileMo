<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\Consumer;
use App\Entity\Phone;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // make 5 customers! Bam!
        for ($i=1; $i <= 5; $i++) { 
            $customer = new Customer();
            $customer->setName('Customer '.$i);
            $customer->setEmail('customer'.$i.'@test.com');
            $customer->setPassword('password');
            $customer->setCreatedAt(new \DateTimeImmutable('now'));
            $manager->persist($customer);

            // save customer in an array
            $customers[] = $customer;
        }

        // make 10 consumers! Bam!
        for ($i=1; $i <= 10; $i++) { 
            $consumer = new Consumer();
            $consumer->setCustomer($customers[array_rand($customers)]);
            $consumer->setFirstName('Name '.$i);
            $consumer->setLastName('Surname '.$i);
            $consumer->setEmail('consumer'.$i.'@test.com');
            $consumer->setCreatedAt(new \DateTimeImmutable('now'));
            $manager->persist($consumer);
        }

        // make 20 phones! Bam!
        for ($i=10; $i <= 20; $i++) { 
            $phone = new Phone();
            $phone->setBrand('Samsung');
            $phone->setModel('Galaxy S'.$i);
            $phone->setImage('/images/samsung-galaxy-s'.$i.'.jpg');
            $phone->setPrice(\rand(10000, 100000) / 100);
            $phone->setStock(\rand(0, 100));
            $phone->setReleaseAt(new \DateTimeImmutable('now'));
            $manager->persist($phone);
        }

        $manager->flush();
    }
}
