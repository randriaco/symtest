<?php

namespace App\DataFixtures;

use App\Entity\Cart;
use App\Entity\User;
use App\Entity\Product;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $encoder;
    public function __construct(UserPasswordHasherInterface $encoder)
    {
        $this->encoder = $encoder;
    }
       
    public function load(ObjectManager $manager): void
    {

        // user
        $user = new User();
        $user->setEmail('alain@sfr.fr');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->encoder->hashPassword($user, 'alain123'));

        $manager->persist($user);
        $manager->flush();

        // cart
        $cart = new Cart();
        $cart->setUser($user);
        $cart->setStatus('active');

        $manager->persist($cart);
        $manager->flush();
    }
}
