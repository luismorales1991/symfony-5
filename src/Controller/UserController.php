<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    
    #[Route("/api/me",name:"app_user_api_me")]
    #[IsGranted("IS_AUTHENTICATED_REMEMBERED")]
    public function apiMe()
    {
        return $this->json($this->getUser(), 200, [], [
            'groups' => ['user:read']
        ]);
    }
}
