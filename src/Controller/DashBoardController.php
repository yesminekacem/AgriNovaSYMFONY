<?php

namespace App\Controller;

use App\Repository\InventoryRepository;
use App\Repository\RentalRepository;
use App\Service\AgriAiCopilotService;
use App\Service\AlertService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashBoardController extends AbstractController
{
    #[Route('/DashBoard', name: 'app_dashboard')]
    public function index(
        Request $request,
        InventoryRepository $inventoryRepository,
        RentalRepository $rentalRepository,
        AlertService $alertService,
        AgriAiCopilotService $agriAiCopilotService
    ): Response
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

        $alerts = $alertService->getAlerts();
        $alertCount = count($alerts);
        $criticalAlertCount = count(array_filter($alerts, static fn (array $alert): bool => ($alert['severity'] ?? null) === 'danger'));

        $operationalPulse = [
            'inventoryUtilization' => $inventoryStats['rentable'] > 0
                ? (int) round(($inventoryStats['rentedOut'] / max(1, $inventoryStats['rentable'])) * 100)
                : 0,
            'overdueRate' => $rentalStats['total'] > 0
                ? (int) round(($rentalStats['overdue'] / max(1, $rentalStats['total'])) * 100)
                : 0,
            'completionRate' => $rentalStats['total'] > 0
                ? (int) round(($rentalStats['completed'] / max(1, $rentalStats['total'])) * 100)
                : 0,
            'revenue' => (float) $rentalStats['revenue'],
        ];

        $aiResult = null;
        if ($request->query->getBoolean('generateAi')) {
            $aiResult = $agriAiCopilotService->generateDashboardBrief([
                'generated_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
                'inventory' => $inventoryStats,
                'rentals' => $rentalStats,
                'alerts' => [
                    'total' => $alertCount,
                    'critical' => $criticalAlertCount,
                    'top' => array_map(
                        static fn (array $alert): array => [
                            'title' => (string) ($alert['title'] ?? ''),
                            'message' => (string) ($alert['message'] ?? ''),
                            'severity' => (string) ($alert['severity'] ?? 'info'),
                        ],
                        array_slice($alerts, 0, 5)
                    ),
                ],
                'operational_pulse' => $operationalPulse,
                'crop_yields' => $cropYields,
                'recent_transactions' => $transactions,
            ]);
        }

        return $this->render('Back/dashboard.html.twig', [
            'stats' => $stats,
            'cropYields' => $cropYields,
            'transactions' => $transactions,
            'inventoryStats' => $inventoryStats,
            'rentalStats' => $rentalStats,
            'inventoryChartData' => $inventoryChartData,
            'rentalChartData' => $rentalChartData,
            'alerts' => $alerts,
            'alertCount' => $alertCount,
            'criticalAlertCount' => $criticalAlertCount,
            'operationalPulse' => $operationalPulse,
            'aiResult' => $aiResult,
            'aiConfigured' => $agriAiCopilotService->isConfigured(),
            'aiProvider' => $agriAiCopilotService->getProviderLabel(),
            'aiModel' => $agriAiCopilotService->getModel(),
        ]);
    }
}
