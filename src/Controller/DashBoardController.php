<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashBoardController extends AbstractController
{
    #[Route('/DashBoard', name: 'app_dashboard')]
    public function index(): Response
    {
        // ---- Fake data (you can replace later with database) ----

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
                'label' => 'Crops',
                'value' => '54',
                'change' => '+10%',
                'icon' => 'leaf'
            ],
        ];

        $cropYields = [
            ['name' => 'Wheat', 'value' => 75, 'color' => 'green'],
            ['name' => 'Corn', 'value' => 60, 'color' => 'orange'],
            ['name' => 'Tomatoes', 'value' => 85, 'color' => 'green'],
        ];

        $transactions = [
            [
                'customer' => 'Ali Ben Salah',
                'product' => 'Tomatoes',
                'amount' => '$120',
                'time' => '2h ago'
            ],
            [
                'customer' => 'Sami Trabelsi',
                'product' => 'Wheat',
                'amount' => '$300',
                'time' => '5h ago'
            ],
            [
                'customer' => 'Mouna Kefi',
                'product' => 'Corn',
                'amount' => '$210',
                'time' => '1 day ago'
            ],
        ];

        return $this->render('Back/dashboard.html.twig', [
            'stats' => $stats,
            'cropYields' => $cropYields,
            'transactions' => $transactions,
        ]);
    }
}