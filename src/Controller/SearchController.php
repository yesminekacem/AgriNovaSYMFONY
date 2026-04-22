<?php

namespace App\Controller;

use App\Repository\InventoryRepository;
use App\Repository\RentalRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/search')]
final class SearchController extends AbstractController
{
    #[Route('/inventory', name: 'search_inventory', methods: ['GET'])]
    public function searchInventory(Request $request, InventoryRepository $inventoryRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $query = trim($request->query->get('q', ''));
        
        if (strlen($query) < 2) {
            return new JsonResponse([]);
        }

        $items = $inventoryRepository->findByFilters($query);
        
        $results = array_map(fn($item) => [
            'id' => $item->getId(),
            'name' => $item->getItemName(),
            'type' => $item->getItemType(),
            'status' => $item->getRentalStatus(),
            'url' => $this->generateUrl('inventory_show', ['id' => $item->getId()]),
        ], $items);

        return new JsonResponse($results);
    }

    #[Route('/rental', name: 'search_rental', methods: ['GET'])]
    public function searchRental(Request $request, RentalRepository $rentalRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $query = trim($request->query->get('q', ''));
        
        if (strlen($query) < 2) {
            return new JsonResponse([]);
        }

        $rentals = $rentalRepository->search($query);
        
        $results = array_map(fn($rental) => [
            'id' => $rental->getId(),
            'renter' => $rental->getRenterName(),
            'item' => $rental->getDisplayItemName(),
            'status' => $rental->getRentalStatus(),
            'start' => $rental->getStartDate()?->format('Y-m-d'),
            'url' => $this->generateUrl('rental_show', ['id' => $rental->getId()]),
        ], $rentals);

        return new JsonResponse($results);
    }
}
