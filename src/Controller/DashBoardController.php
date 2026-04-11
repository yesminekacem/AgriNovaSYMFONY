<?php

namespace App\Controller;

use App\Repository\InventoryRepository;
use App\Repository\RentalRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashBoardController extends AbstractController
{
    #[Route('/DashBoard', name: 'app_dashboard')]
    public function index(InventoryRepository $inventoryRepository, RentalRepository $rentalRepository): Response
    {
        // ---- Dashboard data for crops, inventory, and rentals ----

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

        $inventoryStats = [
            'total' => $inventoryRepository->countAllItems(),
            'rentable' => $inventoryRepository->countRentableItems(),
            'rentedOut' => $inventoryRepository->countByRentalStatus('RENTED_OUT'),
            'lowStock' => $inventoryRepository->countLowStock(),
        ];

        $rentalStats = [
            'total' => $rentalRepository->countAllRentals(),
            'pending' => $rentalRepository->countByStatus('PENDING'),
            'active' => $rentalRepository->countByStatus('ACTIVE'),
            'completed' => $rentalRepository->countByStatus('COMPLETED'),
            'overdue' => $rentalRepository->countOverdue(),
            'revenue' => $rentalRepository->getTotalRevenue(),
        ];

        $inventoryChartData = [
            'labels' => ['Available', 'Rentable', 'Rented Out', 'Low Stock'],
            'values' => [
                max(0, $inventoryStats['total'] - $inventoryStats['rentedOut']),
                $inventoryStats['rentable'],
                $inventoryStats['rentedOut'],
                $inventoryStats['lowStock'],
            ],
        ];

        $rentalChartData = [
            'labels' => ['Pending', 'Active', 'Completed', 'Overdue'],
            'values' => [
                $rentalStats['pending'],
                $rentalStats['active'],
                $rentalStats['completed'],
                $rentalStats['overdue'],
            ],
        ];

        return $this->render('Back/dashboard.html.twig', [
            'stats' => $stats,
            'cropYields' => $cropYields,
            'transactions' => $transactions,
            'inventoryStats' => $inventoryStats,
            'rentalStats' => $rentalStats,
            'inventoryChartData' => $inventoryChartData,
            'rentalChartData' => $rentalChartData,
        ]);
    }
}