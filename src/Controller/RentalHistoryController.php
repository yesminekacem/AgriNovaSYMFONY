<?php

namespace App\Controller;

use App\Entity\RentalHistory;
use App\Repository\RentalHistoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rental-history')]
final class RentalHistoryController extends AbstractController
{
    #[Route('/', name: 'rental_history_index', methods: ['GET'])]
    public function index(Request $request, RentalHistoryRepository $rentalHistoryRepository): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $actionType = $this->normalizeFilter($request->query->get('actionType'));

        return $this->render('rental_history/index.html.twig', [
            'entries' => $rentalHistoryRepository->findByFilters($search, $actionType),
            'search' => $search,
            'actionType' => $actionType,
            'actionTypes' => RentalHistory::ACTION_TYPES,
            'stats' => [
                'total' => $rentalHistoryRepository->countAllHistory(),
                'today' => $rentalHistoryRepository->countToday(),
                'created' => $rentalHistoryRepository->countByActionType('CREATED'),
                'completed' => $rentalHistoryRepository->countByActionType('COMPLETED'),
                'cancelled' => $rentalHistoryRepository->countByActionType('CANCELLED'),
            ],
        ]);
    }

    private function normalizeFilter(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : null;
    }
}
