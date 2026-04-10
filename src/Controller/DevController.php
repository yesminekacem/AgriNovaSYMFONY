<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class DevController extends AbstractController
{
    // Dev preview route removed to disable profile-preview in production/dev
    public function profilePreview(): Response
    {
        // DevController removed/disabled. The profile-preview route has been intentionally disabled.
        // If you need it again for debugging, recreate a controller or re-enable the route.
    }
}
