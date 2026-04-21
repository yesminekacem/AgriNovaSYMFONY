<?php

namespace App\Controller;

use App\Entity\Inventory;
use App\Form\InventoryType;
use App\Entity\User;
use App\Repository\InventoryRepository;
use App\Repository\RentalRepository;
use App\Service\AgriWeatherService;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/inventory')]
final class InventoryController extends AbstractController
{
    #[Route('/', name: 'inventory_index', methods: ['GET'])]
    public function index(Request $request, InventoryRepository $inventoryRepository, AgriWeatherService $agriWeatherService): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $type = $this->normalizeFilter($request->query->get('type'));
        $condition = $this->normalizeFilter($request->query->get('condition'));
        $status = $this->normalizeFilter($request->query->get('status'));
        $rentableRaw = $request->query->get('rentable');
        $rentable = $rentableRaw === null || $rentableRaw === '' ? null : $request->query->getBoolean('rentable');
        $weatherCity = trim((string) $request->query->get('weatherCity', ''));
        $loadWeather = $request->query->getBoolean('loadWeather');

        $items = $inventoryRepository->findByFilters($search, $type, $condition, $rentable, $status);

        return $this->render('inventory/index.html.twig', [
            'items' => $items,
            'search' => $search,
            'type' => $type,
            'condition' => $condition,
            'status' => $status,
            'rentable' => $rentableRaw,
            'weatherCity' => $weatherCity,
            'loadWeather' => $loadWeather,
            'maintenanceWeather' => $loadWeather && $weatherCity !== '' ? $agriWeatherService->getMaintenanceWeather($weatherCity) : null,
            'types' => Inventory::ITEM_TYPES,
            'conditions' => Inventory::CONDITION_STATUSES,
            'statuses' => Inventory::RENTAL_STATUSES,
            'stats' => [
                'total' => $inventoryRepository->countAllItems(),
                'rentable' => $inventoryRepository->countRentableItems(),
                'rentedOut' => $inventoryRepository->countByRentalStatus('RENTED_OUT'),
                'maintenance' => $inventoryRepository->countMaintenanceDue(),
                'lowStock' => $inventoryRepository->countLowStock(),
                'totalValue' => $inventoryRepository->getTotalValue(),
                'dailyRentalPotential' => $inventoryRepository->getRentableValue(),
            ],
            'maintenanceItems' => array_slice($inventoryRepository->findNeedingMaintenance(), 0, 5),
            'lowStockItems' => array_slice($inventoryRepository->findLowStock(), 0, 5),
            'base_template' => $this->getBaseTemplate(),
        ]);
    }

    #[Route('/new', name: 'inventory_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $inventory = new Inventory();
        $form = $this->createForm(InventoryType::class, $inventory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException('You must be logged in to add inventory.');
            }

            $inventory->setOwner($user);
            if ($inventory->getOwnerName() === null) {
                $inventory->setOwnerName($user->getFullName() ?? $user->getEmail());
            }
            if ($inventory->getOwnerContact() === null) {
                $inventory->setOwnerContact($user->getEmail());
            }

            $entityManager->persist($inventory);
            $entityManager->flush();

            $this->addFlash('success', sprintf('"%s" was added successfully.', $inventory->getItemName()));

            return $this->redirectToRoute('inventory_index');
        }

        return $this->render('inventory/form.html.twig', [
            'form' => $form,
            'pageHeading' => 'Add Inventory Item',
            'submitLabel' => 'Save item',
            'item' => $inventory,
            'base_template' => $this->getBaseTemplate(),
        ]);
    }

    #[Route('/export/csv', name: 'inventory_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, InventoryRepository $inventoryRepository): StreamedResponse
    {
        $search = trim((string) $request->query->get('search', ''));
        $type = $this->normalizeFilter($request->query->get('type'));
        $condition = $this->normalizeFilter($request->query->get('condition'));
        $status = $this->normalizeFilter($request->query->get('status'));
        $rentableRaw = $request->query->get('rentable');
        $rentable = $rentableRaw === null || $rentableRaw === '' ? null : $request->query->getBoolean('rentable');

        $items = $inventoryRepository->findByFilters($search, $type, $condition, $rentable, $status);

        $response = new StreamedResponse(function () use ($items): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, [
                'ID',
                'Item Name',
                'Type',
                'Condition',
                'Quantity',
                'Unit Price',
                'Rentable',
                'Rental Price / Day',
                'Rental Status',
                'Owner Name',
                'Owner Contact',
                'Next Maintenance',
            ]);

            foreach ($items as $item) {
                fputcsv($handle, [
                    $item->getId(),
                    $item->getItemName(),
                    $item->getItemType(),
                    $item->getConditionStatus(),
                    $item->getQuantity(),
                    $item->getUnitPrice(),
                    $item->isRentable() ? 'Yes' : 'No',
                    $item->getRentalPricePerDay(),
                    $item->getRentalStatus(),
                    $item->getOwnerName(),
                    $item->getOwnerContact(),
                    $item->getNextMaintenanceDate()?->format('Y-m-d'),
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="inventory-%s.csv"', date('Y-m-d')));

        return $response;
    }

    #[Route('/{id}', name: 'inventory_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Request $request, Inventory $inventory, AgriWeatherService $agriWeatherService): Response
    {
        $weatherCity = trim((string) $request->query->get('weatherCity', ''));
        $loadWeather = $request->query->getBoolean('loadWeather');

        return $this->render('inventory/show.html.twig', [
            'item' => $inventory,
            'weatherCity' => $weatherCity,
            'loadWeather' => $loadWeather,
            'maintenanceWeather' => $loadWeather && $weatherCity !== '' ? $agriWeatherService->getMaintenanceWeather($weatherCity) : null,
            'base_template' => $this->getBaseTemplate(),
        ]);
    }

    #[Route('/{id}/edit', name: 'inventory_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Inventory $inventory, EntityManagerInterface $entityManager): Response
    {
        $this->assertInventoryOwnerOrAdmin($inventory);

        $form = $this->createForm(InventoryType::class, $inventory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', sprintf('"%s" was updated successfully.', $inventory->getItemName()));

            return $this->redirectToRoute('inventory_index');
        }

        return $this->render('inventory/form.html.twig', [
            'form' => $form,
            'pageHeading' => sprintf('Edit %s', $inventory->getItemName()),
            'submitLabel' => 'Update item',
            'item' => $inventory,
            'base_template' => $this->getBaseTemplate(),
        ]);
    }

    #[Route('/{id}/delete', name: 'inventory_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Inventory $inventory, EntityManagerInterface $entityManager, RentalRepository $rentalRepository): Response
    {
        $this->assertInventoryOwnerOrAdmin($inventory);

        if (!$this->isCsrfTokenValid('delete_inventory_'.$inventory->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'The delete action was blocked. Please try again.');

            return $this->redirectToRoute('inventory_index');
        }

        if ($rentalRepository->countForInventory($inventory) > 0) {
            $this->addFlash('error', sprintf('"%s" cannot be deleted because rental records still reference it.', $inventory->getItemName()));

            return $this->redirectToRoute('inventory_show', ['id' => $inventory->getId()]);
        }

        $itemName = $inventory->getItemName();

        try {
            $entityManager->remove($inventory);
            $entityManager->flush();

            $this->addFlash('success', sprintf('"%s" was deleted.', $itemName));
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('error', sprintf('"%s" cannot be deleted because it is still linked to rental data.', $itemName));
        }

        return $this->redirectToRoute('inventory_index');
    }

    private function normalizeFilter(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : null;
    }

    private function getBaseTemplate(): string
    {
        return $this->isGranted('ROLE_ADMIN') ? 'baseBack.html.twig' : 'base.html.twig';
    }

    private function assertInventoryOwnerOrAdmin(Inventory $inventory): void
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('You must be logged in to access this inventory item.');
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        $userEmail = strtolower(trim($user->getEmail() ?? ''));
        $ownerContact = strtolower(trim((string) $inventory->getOwnerContact()));
        $isOwnerUser = $inventory->getOwner() !== null && $inventory->getOwner()->getId() === $user->getId();

        if (!$isOwnerUser && $ownerContact !== $userEmail) {
            throw $this->createAccessDeniedException('You do not have permission to access this inventory item.');
        }
    }
}
