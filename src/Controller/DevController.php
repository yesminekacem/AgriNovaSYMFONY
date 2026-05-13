<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DevController extends AbstractController
{
    // Dev preview route removed to disable profile-preview in production/dev
   public function profilePreview(): Response
{
    return new Response('Profile preview disabled.');
}
}
