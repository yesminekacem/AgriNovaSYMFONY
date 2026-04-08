<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('Front/home.html.twig');
    }

    #[Route('/test', name: 'app_test')]
    public function test(): Response
    {
        return $this->render('Front/test.html.twig');
    }
}