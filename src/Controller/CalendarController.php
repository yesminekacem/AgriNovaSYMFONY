<?php

namespace App\Controller;

use App\Repository\RentalRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/calendar')]
final class CalendarController extends AbstractController
{
    #[Route('/', name: 'calendar_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('rental/calendar.html.twig');
    }

    #[Route('/events', name: 'calendar_events', methods: ['GET'])]
    public function events(RentalRepository $rentalRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $rentals = $rentalRepository->findAllWithInventory();
        $events = [];

        foreach ($rentals as $rental) {
            $startDate = $rental->getStartDate();
            $endDate = $rental->getEndDate();

            // Skip rentals with missing dates
            if (!$startDate || !$endDate) {
                continue;
            }

            $color = match ($rental->getRentalStatus()) {
                'PENDING' => '#f59e0b',
                'APPROVED' => '#3b82f6',
                'ACTIVE' => '#10b981',
                'RETURNED' => '#6366f1',
                'COMPLETED' => '#22c55e',
                'CANCELLED' => '#ef4444',
                'DISPUTED' => '#dc2626',
                default => '#6b7280',
            };

            $events[] = [
                'id' => $rental->getId(),
                'title' => sprintf('%s - %s', $rental->getRenterName(), $rental->getDisplayItemName()),
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->modify('+1 day')->format('Y-m-d'),
                'color' => $color,
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'status' => $rental->getRentalStatus(),
                    'item' => $rental->getDisplayItemName(),
                    'renter' => $rental->getRenterName(),
                    'url' => $this->generateUrl('rental_show', ['id' => $rental->getId()]),
                ],
            ];
        }

        return new JsonResponse($events);
    }
}
