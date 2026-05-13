<?php

namespace App\Controller;

use App\Repository\InventoryRepository;
use App\Repository\PostRepository;
use App\Repository\RentalRepository;
use App\Service\AgriAiCopilotService;
use App\Service\AlertService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\UserRepository;
use App\Repository\PostRepository;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\CropRepository;
use App\Repository\OrdersRepository;
use App\Repository\ProductListingRepository;
use App\Entity\OrderItems;

class DashBoardController extends AbstractController
{
    #[Route('/DashBoard', name: 'app_dashboard')]
<<<<<<< HEAD
    public function index(
        Request $request,
        InventoryRepository $inventoryRepository,
        PostRepository $postRepository,
        RentalRepository $rentalRepository,
        AlertService $alertService,
        AgriAiCopilotService $agriAiCopilotService
    ): Response
=======
public function index(
    Request $request,
    InventoryRepository $inventoryRepository,
    RentalRepository $rentalRepository,
    AlertService $alertService,
    AgriAiCopilotService $agriAiCopilotService,
    PostRepository $postRepository,
    UserRepository $userRepository,
    CropRepository $cropRepository,
    OrdersRepository $ordersRepository,
    ProductListingRepository $productListingRepository
): Response
>>>>>>> 39fd2583a4fc43063e212db22d3d24adcad82e56
    {

        $totalUsers = $userRepository->count([]);
$totalOrders = $ordersRepository->count([]);
$totalProducts = $productListingRepository->count([]);
$totalCrops = $cropRepository->count([]);
      $stats = [
    [
        'label' => 'Users',
        'value' => number_format($totalUsers),
        'change' => 'Live data',
        'icon' => 'users'
    ],
    [
        'label' => 'Orders',
        'value' => number_format($totalOrders),
        'change' => 'Live data',
        'icon' => 'cart'
    ],
    [
        'label' => 'Products',
        'value' => number_format($totalProducts),
        'change' => 'Live data',
        'icon' => 'box'
    ],
    [
        'label' => 'Crops',
        'value' => number_format($totalCrops),
        'change' => 'Live data',
        'icon' => 'leaf'
    ],
];

       $crops = $cropRepository->findBy([], ['cropId' => 'DESC']);

$cropYields = [];
foreach ($crops as $index => $crop) {
    $progress = $crop->getGrowthProgressPercent();

    $color = 'green';
    if ($progress < 40) {
        $color = 'orange';
    }

    $cropYields[] = [
        'name' => $crop->getName(),
        'value' => $progress,
        'color' => $color,
    ];
}
$latestOrders = $ordersRepository->findBy([], ['createdAt' => 'DESC'], 5);

$transactions = [];
foreach ($latestOrders as $order) {
    $firstItem = $order->getOrderItems()->first();

    // Default
    $productName = 'No items';

    if ($firstItem instanceof OrderItems) {
        $productName = $firstItem->getProductName() ?? 'Unknown product';
    }

    $transactions[] = [
        'customer' => $order->getUserId(),
        'product' => $productName,
        'amount' => 'TND' . number_format($order->getTotalPrice(), 2),
        'time' => $order->getCreatedAt()?->format('M d, H:i') ?? '-',
    ];
}

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

        $forumCategoryRows = $postRepository->createQueryBuilder('p')
            ->select('COALESCE(p.category, :defaultCategory) AS name, COUNT(p.idPost) AS count')
            ->andWhere('p.status = :status')
            ->setParameter('defaultCategory', 'Organic Farming')
            ->setParameter('status', 'ACTIVE')
            ->groupBy('name')
            ->orderBy('count', 'DESC')
            ->getQuery()
            ->getArrayResult();

        $totalPosts = array_sum(array_map(
            static fn (array $row): int => (int) $row['count'],
            $forumCategoryRows
        ));

        $categoryPalette = ['green', 'orange'];
        $categoryStats = array_map(
            static function (array $row, int $index) use ($categoryPalette): array {
                return [
                    'name' => (string) $row['name'],
                    'count' => (int) $row['count'],
                    'color' => $categoryPalette[$index % count($categoryPalette)],
                ];
            },
            $forumCategoryRows,
            array_keys($forumCategoryRows)
        );

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
        $criticalAlertCount = count(array_filter(
            $alerts,
            static fn(array $alert): bool => ($alert['severity'] ?? null) === 'danger'
        ));

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

       $rawCategoryStats = $postRepository->countPostsByCategory();

$colors = ['green', 'orange', 'green', 'orange', 'green', 'orange'];

$categoryStats = [];
foreach ($rawCategoryStats as $index => $row) {
    $categoryStats[] = [
        'name' => $row['name'],
        'count' => (int) $row['count'],
        'color' => $colors[$index % count($colors)],
    ];
}

$totalPosts = array_sum(array_column($categoryStats, 'count'));

        $totalPosts = array_sum(array_column($categoryStats, 'count'));

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
                        static fn(array $alert): array => [
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
                'forum_categories' => $categoryStats,
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
            'categoryStats' => $categoryStats,
            'totalPosts' => $totalPosts,
            'operationalPulse' => $operationalPulse,
            'categoryStats' => $categoryStats,
            'totalPosts' => $totalPosts,
            'aiResult' => $aiResult,
            'aiConfigured' => $agriAiCopilotService->isConfigured(),
            'aiProvider' => $agriAiCopilotService->getProviderLabel(),
            'aiModel' => $agriAiCopilotService->getModel(),
        ]);
    }
}