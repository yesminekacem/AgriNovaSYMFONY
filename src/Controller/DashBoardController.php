<?php

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashBoardController extends AbstractController
{
    #[Route('/DashBoard', name: 'app_dashboard')]
    public function index(PostRepository $postRepository): Response
    {
        $posts = $postRepository->findAll();

        $categoryCounts = [
            'Organic Farming' => 0,
            'Soil Management' => 0,
            'Water Management' => 0,
            'Harvesting' => 0,
            'Crop Management' => 0,
        ];

        foreach ($posts as $post) {
            $category = $post->getCategory();

            if (isset($categoryCounts[$category])) {
                $categoryCounts[$category]++;
            }
        }

        $categoryStats = [
            [
                'name' => 'Organic Farming',
                'count' => $categoryCounts['Organic Farming'],
                'color' => 'green'
            ],
            [
                'name' => 'Soil Management',
                'count' => $categoryCounts['Soil Management'],
                'color' => 'orange'
            ],
            [
                'name' => 'Water Management',
                'count' => $categoryCounts['Water Management'],
                'color' => 'green'
            ],
            [
                'name' => 'Harvesting',
                'count' => $categoryCounts['Harvesting'],
                'color' => 'orange'
            ],
            [
                'name' => 'Crop Management',
                'count' => $categoryCounts['Crop Management'],
                'color' => 'green'
            ],
        ];

        $totalPosts = array_sum($categoryCounts);

        $stats = [
            [
                'label' => 'Users',
                'value' => '1,245',
                'change' => '+12%',
                'icon' => 'users'
            ],
            [
                'label' => 'Orders',
                'value' => '320',
                'change' => '+8%',
                'icon' => 'cart'
            ],
            [
                'label' => 'Products',
                'value' => '87',
                'change' => '+5%',
                'icon' => 'box'
            ],
            [
                'label' => 'Forum Posts',
                'value' => $totalPosts,
                'change' => 'By category',
                'icon' => 'leaf'
            ],
        ];

        $cropYields = [
            ['name' => 'Wheat', 'value' => 75, 'color' => 'green'],
            ['name' => 'Corn', 'value' => 60, 'color' => 'orange'],
            ['name' => 'Tomatoes', 'value' => 85, 'color' => 'green'],
        ];

        return $this->render('Back/dashboard.html.twig', [
            'stats' => $stats,
            'cropYields' => $cropYields,
            'categoryStats' => $categoryStats,
            'totalPosts' => $totalPosts,
        ]);
    }
}