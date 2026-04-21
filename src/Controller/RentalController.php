<?php

namespace App\Controller;

use App\Entity\Inventory;
use App\Entity\Rental;
use App\Entity\RentalHistory;
use App\Form\RentalType;
use App\Repository\InventoryRepository;
use App\Repository\RentalHistoryRepository;
use App\Repository\RentalRepository;
use App\Service\AgriLocationService;
use App\Service\AgriWeatherService;
use App\Service\RentalNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rental')]
final class RentalController extends AbstractController
{
    #[Route('/', name: 'rental_index', methods: ['GET'])]
    public function index(Request $request, RentalRepository $rentalRepository, InventoryRepository $inventoryRepository, AgriLocationService $agriLocationService): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $status = $this->normalizeFilter($request->query->get('status'));
        $paymentStatus = $this->normalizeFilter($request->query->get('paymentStatus'));
        $overdueOnly = $request->query->getBoolean('overdue');
        $inventoryId = $request->query->getInt('inventory');
        $inventory = $inventoryId > 0 ? $inventoryRepository->find($inventoryId) : null;
        $mapQuery = trim((string) $request->query->get('mapQuery', ''));
        $loadMap = $request->query->getBoolean('loadMap');

        $rentals = $rentalRepository->findByFilters($search, $status, $paymentStatus, $overdueOnly, $inventory instanceof Inventory ? $inventory->getId() : null);

        return $this->render('rental/index.html.twig', [
            'rentals' => $rentals,
            'search' => $search,
            'status' => $status,
            'paymentStatus' => $paymentStatus,
            'overdueOnly' => $overdueOnly,
            'inventoryItem' => $inventory,
            'mapQuery' => $mapQuery,
            'loadMap' => $loadMap,
            'locationLookup' => $loadMap && $mapQuery !== '' ? $agriLocationService->searchLocation($mapQuery) : null,
            'statuses' => Rental::RENTAL_STATUSES,
            'paymentStatuses' => Rental::PAYMENT_STATUSES,
            'stats' => [
                'total' => $rentalRepository->countAllRentals(),
                'pending' => $rentalRepository->countByStatus('PENDING'),
                'active' => $rentalRepository->countByStatus('ACTIVE'),
                'completed' => $rentalRepository->countByStatus('COMPLETED'),
                'overdue' => $rentalRepository->countOverdue(),
                'revenue' => $rentalRepository->getTotalRevenue(),
            ],
            'upcomingReturns' => array_slice($rentalRepository->findUpcomingReturns(), 0, 5),
            'overdueRentals' => array_slice($rentalRepository->findOverdue(), 0, 5),
        ]);
    }

    #[Route('/new', name: 'rental_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, InventoryRepository $inventoryRepository, RentalRepository $rentalRepository, RentalNotificationService $notificationService): Response
    {
        $inventoryId = $request->query->getInt('inventory');
        if ($inventoryId <= 0) {
            $this->addFlash('error', 'Start a rental from an inventory item so the selected asset stays controlled.');

            return $this->redirectToRoute('inventory_index');
        }

        $inventory = $inventoryRepository->findAvailableForRentalForm($inventoryId);
        if (!$inventory instanceof Inventory) {
            $this->addFlash('error', 'This inventory item is not available for a new rental.');

            return $this->redirectToRoute('inventory_index');
        }

    $rental = new Rental();
    $rental->setInventory($inventory);
    $rental->setDailyRate($inventory->getRentalPricePerDay());
    $rental->setOwnerName($inventory->getOwnerName() ?: 'Unknown owner'); // ✅ add this

        $form = $this->createForm(RentalType::class, $rental, [
            'inventory_id' => $inventory->getId(),
            'lock_inventory' => true,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
            'is_edit' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->prepareRental($rental);

            if ($rental->getInventory()?->getId() !== $inventory->getId()) {
                $form->addError(new FormError('The rental must stay attached to the inventory item you opened it from.'));
            }

            if ($this->isDuplicateRentalSubmission($rental, $rentalRepository)) {
                $form->addError(new FormError('A rental already exists for this renter, contact, inventory item, and date range.'));
            }

            if (!$this->canUseInventory($rental->getInventory(), $rentalRepository)) {
                $form->get('inventory')->addError(new FormError('Choose an inventory item that is rentable and not already blocked by another approved or active rental.'));
            }

            if ($form->isValid()) {
                $this->syncInventoryStatus($rental, $rentalRepository);
                $entityManager->persist($rental);
                $this->logHistory($entityManager, $rental, 'CREATED', sprintf('Rental created for %s.', $rental->getRenterName()));
                $entityManager->flush();

                // Send email notification
                $notificationService->notifyNewRental($rental);

                $this->addFlash('success', sprintf('Rental for %s was created successfully.', $rental->getRenterName()));

                return $this->redirectToRoute('rental_show', ['id' => $rental->getId()]);
            }
        }

        return $this->render('rental/form.html.twig', [
            'form' => $form,
            'pageHeading' => 'Create Rental',
            'submitLabel' => 'Save rental',
            'rental' => $rental,
            'inventoryItem' => $inventory,
        ]);
    }

    #[Route('/export/csv', name: 'rental_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, RentalRepository $rentalRepository): StreamedResponse
    {
        $search = trim((string) $request->query->get('search', ''));
        $status = $this->normalizeFilter($request->query->get('status'));
        $paymentStatus = $this->normalizeFilter($request->query->get('paymentStatus'));
        $overdueOnly = $request->query->getBoolean('overdue');
        $inventoryId = $request->query->getInt('inventory');
        $rentals = $rentalRepository->findByFilters($search, $status, $paymentStatus, $overdueOnly, $inventoryId > 0 ? $inventoryId : null);

        $response = new StreamedResponse(function () use ($rentals): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['ID', 'Inventory Item', 'Owner', 'Renter', 'Contact', 'Start', 'End', 'Status', 'Payment', 'Total Cost']);

            foreach ($rentals as $rental) {
                fputcsv($handle, [
                    $rental->getId(),
                    $rental->getDisplayItemName(),
                    $rental->getOwnerName(),
                    $rental->getRenterName(),
                    $rental->getRenterContact(),
                    $rental->getStartDate()?->format('Y-m-d'),
                    $rental->getEndDate()?->format('Y-m-d'),
                    $rental->getRentalStatus(),
                    $rental->getPaymentStatus(),
                    $rental->getTotalCost(),
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="rentals-%s.csv"', date('Y-m-d')));

        return $response;
    }

    #[Route('/{id}', name: 'rental_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Request $request, Rental $rental, RentalHistoryRepository $rentalHistoryRepository, AgriWeatherService $agriWeatherService): Response
    {
        $locationHint = $this->resolveRentalLocationHint($rental);
        $loadWeather = $request->query->getBoolean('loadWeather');

        return $this->render('rental/show.html.twig', [
            'rental' => $rental,
            'historyEntries' => $rentalHistoryRepository->findForRental($rental),
            'rentalWeather' => $loadWeather && $locationHint !== null
                ? $agriWeatherService->getRentalWeatherBrief($locationHint, $rental->getStartDate(), $rental->getEndDate())
                : null,
            'loadWeather' => $loadWeather,
            'rentalWeatherLocationHint' => $locationHint,
        ]);
    }

    #[Route('/{id}/edit', name: 'rental_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Rental $rental, EntityManagerInterface $entityManager, RentalRepository $rentalRepository, RentalNotificationService $notificationService): Response
    {
        $previousInventory = $rental->getInventory();
        $previousStatus = $rental->getRentalStatus();

        $form = $this->createForm(RentalType::class, $rental, [
            'inventory_id' => $rental->getInventory()?->getId(),
            'current_rental_id' => $rental->getId(),
            'lock_inventory' => true,
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $this->prepareRental($rental);

            if ($previousInventory instanceof Inventory && $rental->getInventory()?->getId() !== $previousInventory->getId()) {
                $form->addError(new FormError('The linked inventory item cannot be changed from the rental form.'));
            }

            if ($this->isDuplicateRentalSubmission($rental, $rentalRepository, $rental->getId())) {
                $form->addError(new FormError('Another rental already exists for this renter, contact, inventory item, and date range.'));
            }

            if (!$this->canUseInventory($rental->getInventory(), $rentalRepository, $rental->getId())) {
                $form->get('inventory')->addError(new FormError('This inventory item is already blocked by another approved or active rental.'));
            }

            if ($form->isValid()) {
                $this->syncInventoryStatus($rental, $rentalRepository, $previousInventory, $previousStatus);
                $this->logHistory($entityManager, $rental, 'UPDATED', sprintf('Rental #%d was updated.', $rental->getId()));
                $entityManager->flush();

                // Send email if status changed
                if ($previousStatus !== $rental->getRentalStatus()) {
                    $notificationService->notifyStatusChange($rental, $previousStatus, $rental->getRentalStatus());
                }

                $this->addFlash('success', sprintf('Rental #%d was updated successfully.', $rental->getId()));

                return $this->redirectToRoute('rental_show', ['id' => $rental->getId()]);
            }
        }

        return $this->render('rental/form.html.twig', [
            'form' => $form,
            'pageHeading' => sprintf('Edit Rental #%d', $rental->getId()),
            'submitLabel' => 'Update rental',
            'rental' => $rental,
            'inventoryItem' => $rental->getInventory(),
        ]);
    }

    #[Route('/{id}/delete', name: 'rental_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Rental $rental, EntityManagerInterface $entityManager, RentalRepository $rentalRepository): Response
    {
        if (!$this->isCsrfTokenValid('delete_rental_'.$rental->getId(), $request->request->getString('_token'))) {
            $this->addFlash('error', 'The delete action was blocked. Please try again.');

            return $this->redirectToRoute('rental_index');
        }

        $inventory = $rental->getInventory();
        $rentalId = $rental->getId();
        $renterName = $rental->getRenterName();

        $entityManager->remove($rental);
        if ($inventory instanceof Inventory && !$rentalRepository->hasBlockingRentalForInventory($inventory, $rentalId)) {
            $this->setInventoryAvailable($inventory);
        }
        $entityManager->flush();

        $this->addFlash('success', sprintf('Rental for %s was deleted.', $renterName));

        if ($inventory instanceof Inventory && $inventory->getId() !== null) {
            return $this->redirectToRoute('inventory_show', ['id' => $inventory->getId()]);
        }

        return $this->redirectToRoute('inventory_index');
    }

    #[Route('/{id}/approve', name: 'rental_approve', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function approve(Request $request, Rental $rental, EntityManagerInterface $entityManager, RentalNotificationService $notificationService): Response
    {
        return $this->transitionRental($request, $rental, $entityManager, $notificationService, 'APPROVED', 'Rental approved.', 'Rental approved for pickup.', 'APPROVED');
    }

    #[Route('/{id}/activate', name: 'rental_activate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function activate(Request $request, Rental $rental, EntityManagerInterface $entityManager, RentalNotificationService $notificationService): Response
    {
        return $this->transitionRental($request, $rental, $entityManager, $notificationService, 'ACTIVATED', 'Rental activated.', 'Rental is now active.', 'ACTIVE');
    }

    #[Route('/{id}/return', name: 'rental_return', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function returnRental(Request $request, Rental $rental, EntityManagerInterface $entityManager, RentalNotificationService $notificationService): Response
    {
        return $this->transitionRental($request, $rental, $entityManager, $notificationService, 'RETURNED', 'Rental marked as returned.', 'Item was returned by the renter.', 'RETURNED', true);
    }

    #[Route('/{id}/complete', name: 'rental_complete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function complete(Request $request, Rental $rental, EntityManagerInterface $entityManager, RentalNotificationService $notificationService): Response
    {
        return $this->transitionRental($request, $rental, $entityManager, $notificationService, 'COMPLETED', 'Rental completed.', 'Rental was completed and closed.', 'COMPLETED', true);
    }

    #[Route('/{id}/cancel', name: 'rental_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(Request $request, Rental $rental, EntityManagerInterface $entityManager, RentalNotificationService $notificationService): Response
    {
        return $this->transitionRental($request, $rental, $entityManager, $notificationService, 'CANCELLED', 'Rental cancelled.', 'Rental was cancelled.', 'CANCELLED');
    }

    #[Route('/{id}/dispute', name: 'rental_dispute', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function dispute(Request $request, Rental $rental, EntityManagerInterface $entityManager, RentalNotificationService $notificationService): Response
    {
        return $this->transitionRental($request, $rental, $entityManager, $notificationService, 'DISPUTED', 'Rental marked as disputed.', 'Rental was flagged for dispute review.', 'DISPUTED');
    }
private function transitionRental(
    Request $request,
    Rental $rental,
    EntityManagerInterface $entityManager,
    RentalNotificationService $notificationService,
    string $actionType,
    string $flashMessage,
    string $historyMessage,
    ?string $newStatus = null,
    bool $markReturned = false
): Response {
    $previousStatus = $rental->getRentalStatus();
    if (!$this->isCsrfTokenValid('action_rental_'.$rental->getId().'_'.$actionType, $request->request->getString('_token'))) {
        $this->addFlash('error', 'The action could not be verified. Please try again.');

        return $this->redirectToRoute('rental_index');
    }

    // ✅ NEW: validate transition before applying it
    if ($newStatus !== null) {
        $allowed = $this->getAllowedTransitions()[$newStatus] ?? [];
        if (!in_array($rental->getRentalStatus(), $allowed, true)) {
            $this->addFlash('error', sprintf(
                'Cannot move rental from "%s" to "%s".',
                $rental->getRentalStatus(),
                $newStatus
            ));

            return $this->redirectToRoute('rental_show', ['id' => $rental->getId()]);
        }

        $rental->setRentalStatus($newStatus);
    }

    if ($markReturned && $rental->getActualReturnDate() === null) {
        $rental->setActualReturnDate(new \DateTime('today'));
    }

    $this->prepareRental($rental);
    $this->syncInventoryStatus($rental, $entityManager->getRepository(Rental::class));
    $this->logHistory($entityManager, $rental, $actionType, $historyMessage);
    $entityManager->flush();

    // Send email notification for status change
    if ($newStatus !== null
        && $previousStatus !== $newStatus
        && in_array($newStatus, ['APPROVED', 'ACTIVE', 'CANCELLED'], true)) {
        $notificationService->notifyStatusChange($rental, $previousStatus, $newStatus);
    }

    $this->addFlash('success', $flashMessage);

    return $this->redirectToRoute('rental_show', ['id' => $rental->getId()]);
}

// ✅ NEW: add this private method anywhere in the class
private function getAllowedTransitions(): array
{
    return [
        'APPROVED'   => ['PENDING'],
        'ACTIVE'     => ['APPROVED'],
        'RETURNED'   => ['ACTIVE'],
        'COMPLETED'  => ['RETURNED'],
        'CANCELLED'  => ['PENDING', 'APPROVED'],
        'DISPUTED'   => ['ACTIVE', 'RETURNED'],
    ];
}
    private function prepareRental(Rental $rental): void
    {
        $inventory = $rental->getInventory();
        if ($inventory instanceof Inventory) {
            $ownerName = $inventory->getOwnerName() ?: 'Unknown owner';
            $rental->setOwnerName($ownerName);

            if (($rental->getDailyRate() ?? 0) <= 0 && ($inventory->getRentalPricePerDay() ?? 0) > 0) {
                $rental->setDailyRate($inventory->getRentalPricePerDay());
            }
        }

        if (!($rental->isRequiresDelivery() ?? false)) {
            $rental->setDeliveryFee(0.0);
            $rental->setDeliveryAddress(null);
        }

        $this->calculateCosts($rental);
    }

    private function calculateCosts(Rental $rental): void
    {
        if (!$rental->getStartDate() || !$rental->getEndDate()) {
            return;
        }

        $days = max(1, $rental->getStartDate()->diff($rental->getEndDate())->days);
        $baseCost = $days * (float) ($rental->getDailyRate() ?? 0);
        $deliveryFee = ($rental->isRequiresDelivery() ?? false) ? (float) ($rental->getDeliveryFee() ?? 0) : 0.0;
        $lateFee = 0.0;

        if ($rental->getActualReturnDate() && $rental->getActualReturnDate() > $rental->getEndDate()) {
            $lateDays = $rental->getEndDate()->diff($rental->getActualReturnDate())->days;
            $lateFee = $lateDays * (float) ($rental->getDailyRate() ?? 0) * 0.5;
        }

        $rental->setTotalDays($days);
        $rental->setLateFee($lateFee);
        $rental->setSecurityDeposit(round(($baseCost + $deliveryFee) * 0.5, 2));
        $rental->setTotalCost(round($baseCost + $deliveryFee + $lateFee, 2));
    }

    private function logHistory(EntityManagerInterface $entityManager, Rental $rental, string $actionType, string $description): void
    {
        $entry = new RentalHistory();
        $entry
            ->setRental($rental)
            ->setActionType($actionType)
            ->setActionDescription($description)
            ->setPerformedBy($this->resolveActorName());

        $entityManager->persist($entry);
    }

    private function syncInventoryStatus(Rental $rental, RentalRepository $rentalRepository, ?Inventory $previousInventory = null, ?string $previousStatus = null): void
    {
        $currentInventory = $rental->getInventory();

        if ($currentInventory instanceof Inventory) {
            if (in_array($rental->getRentalStatus(), ['APPROVED', 'ACTIVE'], true)
                && !in_array($currentInventory->getRentalStatus(), ['MAINTENANCE', 'RETIRED'], true)) {
                $currentInventory->setRentalStatus('RENTED_OUT');
            } elseif (in_array($rental->getRentalStatus(), ['RETURNED', 'COMPLETED', 'CANCELLED', 'PENDING', 'DISPUTED'], true)
                && !in_array($currentInventory->getRentalStatus(), ['MAINTENANCE', 'RETIRED'], true)
                && !$rentalRepository->hasBlockingRentalForInventory($currentInventory, $rental->getId())) {
                $currentInventory->setRentalStatus('AVAILABLE');
            }
        }

        if ($previousInventory instanceof Inventory && $previousInventory !== $currentInventory
            && !in_array($previousInventory->getRentalStatus(), ['MAINTENANCE', 'RETIRED'], true)
            && !$rentalRepository->hasBlockingRentalForInventory($previousInventory, $rental->getId())) {
            $this->setInventoryAvailable($previousInventory);
        }

        if ($previousInventory instanceof Inventory && $previousInventory === $currentInventory && $previousStatus === 'ACTIVE'
            && $rental->getRentalStatus() !== 'ACTIVE'
            && !in_array($currentInventory?->getRentalStatus(), ['MAINTENANCE', 'RETIRED'], true)
            && !$rentalRepository->hasBlockingRentalForInventory($previousInventory, $rental->getId())) {
            $this->setInventoryAvailable($previousInventory);
        }
    }

    private function canUseInventory(?Inventory $inventory, ?RentalRepository $rentalRepository = null, ?int $excludeRentalId = null): bool
    {
        if (!$inventory instanceof Inventory || !$inventory->isRentable()) {
            return false;
        }

        if ($rentalRepository instanceof RentalRepository && $rentalRepository->hasBlockingRentalForInventory($inventory, $excludeRentalId)) {
            return false;
        }

        return true;
    }

    private function isDuplicateRentalSubmission(Rental $rental, RentalRepository $rentalRepository, ?int $excludeRentalId = null): bool
    {
        if (!$rental->getInventory() instanceof Inventory || !$rental->getStartDate() || !$rental->getEndDate()) {
            return false;
        }

        $renterName = trim((string) $rental->getRenterName());
        $renterContact = trim((string) $rental->getRenterContact());
        if ($renterName === '' || $renterContact === '') {
            return false;
        }

        return $rentalRepository->hasDuplicateDraftForForm(
            $rental->getInventory(),
            $renterName,
            $renterContact,
            $rental->getStartDate(),
            $rental->getEndDate(),
            $excludeRentalId
        );
    }

    private function setInventoryAvailable(Inventory $inventory): void
    {
        if (!in_array($inventory->getRentalStatus(), ['MAINTENANCE', 'RETIRED'], true)) {
            $inventory->setRentalStatus('AVAILABLE');
        }
    }

    private function resolveActorName(): string
    {
        $user = $this->getUser();
        if ($user === null) {
            return 'System';
        }

        return $user->getUserIdentifier();
    }

    private function normalizeFilter(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : null;
    }

    private function resolveRentalLocationHint(Rental $rental): ?string
    {
        $deliveryAddress = trim((string) $rental->getDeliveryAddress());
        if ($deliveryAddress !== '') {
            return $deliveryAddress;
        }

        $renterAddress = trim((string) $rental->getRenterAddress());
        if ($renterAddress !== '') {
            return $renterAddress;
        }

        return null;
    }
}
