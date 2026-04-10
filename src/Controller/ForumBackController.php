<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ForumBackController extends AbstractController
{
    #[Route('/forumBack', name: 'app_forum_back')]
    public function index(): Response
    {
        $posts = [];
        $commentsByPost = [];

        return $this->render('Back/forumBack.html.twig', [
            'posts' => $posts,
            'commentsByPost' => $commentsByPost,
        ]);
    }
}