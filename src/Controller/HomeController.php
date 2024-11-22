<?php

// src/Controller/HomeController.php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'home')]
    public function index(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
       /* $user = new User();
        $user->setEmail('khe.walid59@gmail.com')
         ->setNom('kherroubi')->setPrenom('Walid')
         ->setPassword($hasher->hashPassword($user, 'walid2002'))
        ->setRoles([]);

        $em->persist($user);
        $em->flush();  */
        return $this->render('home/index.html.twig'); 
    }
}
  