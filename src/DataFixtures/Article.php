<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class Article extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create();

        $faker->title();

        for ($i = 0; $i < 50; $i++){
            $article = new \App\Entity\Article();
            $article->setTitle($faker->text(50));
            $article->setBody($faker->text(255));
            $manager->persist($article);
        }

        $manager->flush();
    }
}
