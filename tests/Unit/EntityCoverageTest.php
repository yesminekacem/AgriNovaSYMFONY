<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Cart;
use App\Entity\Comment;
use App\Entity\Crop;
use App\Entity\Inventory;
use App\Entity\MessengerMessages;
use App\Entity\Notifications;
use App\Entity\OrderItems;
use App\Entity\Orders;
use App\Entity\Post;
use App\Entity\PostReaction;
use App\Entity\ProductListing;
use App\Entity\Rental;
use App\Entity\RentalHistory;
use App\Entity\Task;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class EntityCoverageTest extends TestCase
{
    public function testTaskRoundTrip(): void
    {
        $task = new Task();
        $crop = new Crop();

        $task->setTaskName('Irrigation Plan')
            ->setDescription('Water all crop rows in section A')
            ->setTaskType('Maintenance')
            ->setScheduledDate(new \DateTimeImmutable('2026-05-04'))
            ->setCompletedDate(new \DateTimeImmutable('2026-05-05'))
            ->setStatus('Pending')
            ->setAssignedTo('John')
            ->setCost(120.50)
            ->setCrop($crop);

        self::assertSame('Irrigation Plan', $task->getTaskName());
        self::assertSame('Water all crop rows in section A', $task->getDescription());
        self::assertSame('Maintenance', $task->getTaskType());
        self::assertSame('2026-05-04', $task->getScheduledDate()->format('Y-m-d'));
        self::assertSame('2026-05-05', $task->getCompletedDate()->format('Y-m-d'));
        self::assertSame('Pending', $task->getStatus());
        self::assertSame('John', $task->getAssignedTo());
        self::assertSame(120.50, $task->getCost());
        self::assertSame($crop, $task->getCrop());
    }

    public function testCropRoundTripAndGrowthProgress(): void
    {
        $crop = new Crop();

        $crop->setName('Maize')
            ->setType('Grain')
            ->setVariety('Yellow')
            ->setPlantingDate(new \DateTimeImmutable('2026-05-01'))
            ->setExpectedHarvestDate(new \DateTimeImmutable('2026-05-01'))
            ->setGrowthStage('Seedling')
            ->setAreaSize(12.5)
            ->setStatus('Active')
            ->setImagePath('crops/maize.png');

        self::assertSame('Maize', $crop->getName());
        self::assertSame('Grain', $crop->getType());
        self::assertSame('Yellow', $crop->getVariety());
        self::assertSame('Seedling', $crop->getGrowthStage());
        self::assertSame(12.5, $crop->getAreaSize());
        self::assertSame('Active', $crop->getStatus());
        self::assertSame('crops/maize.png', $crop->getImagePath());
        self::assertSame(0, $crop->getGrowthProgressPercent());
    }

    public function testNotificationsConstructorAndSetters(): void
    {
        $notification = new Notifications();

        self::assertFalse($notification->getIsRead());
        self::assertInstanceOf(\DateTimeInterface::class, $notification->getCreatedAt());

        $notification->setRecipientId(10)
            ->setActorId(20)
            ->setPostId(30)
            ->setType('like')
            ->setMessage('Your post was liked')
            ->setIsRead(true)
            ->setCreatedAt(new \DateTimeImmutable('2026-05-04'));

        self::assertSame(10, $notification->getRecipientId());
        self::assertSame(20, $notification->getActorId());
        self::assertSame(30, $notification->getPostId());
        self::assertSame('like', $notification->getType());
        self::assertSame('Your post was liked', $notification->getMessage());
        self::assertTrue($notification->getIsRead());
    }

    public function testUserCollectionsRolesAndFlags(): void
    {
        $user = new User();

        $user->setEmail('farmer@example.com')
            ->setPassword('hashed-password')
            ->setRole('ADMIN')
            ->setFullName('Farmer Joe')
            ->setProfileImage('profiles/farmer.jpg')
            ->setFaceData('face-data')
            ->setIsVerified(true)
            ->setBanned(false);

        self::assertSame('farmer@example.com', $user->getEmail());
        self::assertSame('farmer@example.com', $user->getUserIdentifier());
        self::assertContains('ROLE_ADMIN', $user->getRoles());
        self::assertContains('ROLE_USER', $user->getRoles());
        self::assertSame('Farmer Joe', $user->getFullName());
        self::assertSame('profiles/farmer.jpg', $user->getProfileImage());
        self::assertTrue($user->isFaceEnrolled());
        self::assertTrue($user->isVerified());
        self::assertFalse($user->getBanned());
        self::assertCount(0, $user->getInventories());

        $inventory = (new Inventory())->setItemName('Tractor');
        $user->addInventory($inventory);

        self::assertCount(1, $user->getInventories());
        self::assertSame($user, $inventory->getOwner());

        $user->removeInventory($inventory);
        self::assertCount(0, $user->getInventories());
    }

    public function testRentalHistoryTrimsAndSetsTimestamp(): void
    {
        $history = new RentalHistory();
        $rental = new Rental();

        $history->setRental($rental)
            ->setActionType('CREATED')
            ->setActionDescription('  Rental created  ')
            ->setPerformedBy('  Admin  ')
            ->setActionTimestamp(null)
            ->onPrePersist();

        self::assertSame($rental, $history->getRental());
        self::assertSame('CREATED', $history->getActionType());
        self::assertSame('  Rental created  ', $history->getActionDescription());
        self::assertSame('Admin', $history->getPerformedBy());
        self::assertInstanceOf(\DateTimeInterface::class, $history->getActionTimestamp());
    }

    public function testInventoryRoundTripAndHelpers(): void
    {
        $inventory = new Inventory();

        $inventory->setItemName('  Sprayer  ')
            ->setItemType('EQUIPMENT')
            ->setDescription('  Used for crops  ')
            ->setQuantity(3)
            ->setUnitPrice(50.0)
            ->setPurchaseDate(new \DateTimeImmutable('2026-05-01'))
            ->setConditionStatus('GOOD')
            ->setIsRentable(true)
            ->setRentalPricePerDay(15.0)
            ->setRentalStatus('AVAILABLE')
            ->setLastMaintenanceDate(new \DateTimeImmutable('2026-05-01'))
            ->setNextMaintenanceDate(new \DateTimeImmutable('+3 days'))
            ->setTotalUsageHours(20)
            ->setOwnerName('  Owner Name  ')
            ->setOwnerContact('  +123456789  ')
            ->setImagePath('  inventory/sprayer.jpg  ');

        self::assertSame('Sprayer', $inventory->getItemName());
        self::assertSame('Used for crops', $inventory->getDescription());
        self::assertSame(3, $inventory->getQuantity());
        self::assertSame(50.0, $inventory->getUnitPrice());
        self::assertSame('GOOD', $inventory->getConditionStatus());
        self::assertTrue($inventory->isRentable());
        self::assertSame(15.0, $inventory->getRentalPricePerDay());
        self::assertSame('AVAILABLE', $inventory->getRentalStatus());
        self::assertSame('Owner Name', $inventory->getOwnerName());
        self::assertSame('+123456789', $inventory->getOwnerContact());
        self::assertSame('inventory/sprayer.jpg', $inventory->getImagePath());
        self::assertSame(150.0, $inventory->getEstimatedValue());
        self::assertTrue($inventory->needsMaintenanceSoon(7));
        self::assertFalse($inventory->isLowStock(2));
    }

    public function testRentalRoundTripAndHelpers(): void
    {
        $inventory = (new Inventory())->setItemName('Harvester')->setIsRentable(true);
        $rental = new Rental();

        $rental->setInventory($inventory)
            ->setOwnerName('  Owner  ')
            ->setRenterName('  Renter  ')
            ->setRenterContact('  555-123  ')
            ->setRenterAddress('  Farm Road  ')
            ->setStartDate(new \DateTimeImmutable('yesterday'))
            ->setEndDate(new \DateTimeImmutable('yesterday'))
            ->setActualReturnDate(null)
            ->setDailyRate(25.0)
            ->setTotalDays(2)
            ->setTotalCost(50.0)
            ->setSecurityDeposit(10.0)
            ->setLateFee(0.0)
            ->setRequiresDelivery(true)
            ->setDeliveryFee(5.0)
            ->setDeliveryAddress('  Delivery Street  ')
            ->setRentalStatus('ACTIVE')
            ->setPickupCondition('Good')
            ->setReturnCondition('Good')
            ->setPickupPhotos('pickup.jpg')
            ->setReturnPhotos('return.jpg')
            ->setDamageNotes('None')
            ->setOwnerRating(5)
            ->setRenterRating(4)
            ->setOwnerReview('Excellent renter')
            ->setRenterReview('Excellent owner')
            ->setPaymentStatus('FULLY_PAID')
            ->setPaymentMethod('Card')
            ->setCreatedAt(new \DateTimeImmutable('2026-05-04'))
            ->setUpdatedAt(new \DateTimeImmutable('2026-05-04'));

        self::assertSame($inventory, $rental->getInventory());
        self::assertSame('Owner', $rental->getOwnerName());
        self::assertSame('Renter', $rental->getRenterName());
        self::assertSame('555-123', $rental->getRenterContact());
        self::assertSame('Farm Road', $rental->getRenterAddress());
        self::assertSame(25.0, $rental->getDailyRate());
        self::assertSame('ACTIVE', $rental->getRentalStatus());
        self::assertSame('Card', $rental->getPaymentMethod());
        self::assertSame('Harvester', $rental->getDisplayItemName());
        self::assertTrue($rental->isOverdue());

        $upcomingRental = (new Rental())
            ->setEndDate(new \DateTimeImmutable('tomorrow'));
        self::assertSame(1, $upcomingRental->getDaysRemaining());
    }

    public function testCartRoundTrip(): void
    {
        $cart = new Cart();
        $product = (new ProductListing())
            ->setUserId('user-1')
            ->setProductName('Seeds')
            ->setPricePerUnit(5.5)
            ->setQuantity(10);

        $cart->setUserId('user-1')
            ->setProduct($product)
            ->setQuantity(2)
            ->setAddedAt(new \DateTimeImmutable('2026-05-04'));

        self::assertSame('user-1', $cart->getUserId());
        self::assertSame($product, $cart->getProduct());
        self::assertSame(2, $cart->getQuantity());
        self::assertSame('2026-05-04', $cart->getAddedAt()->format('Y-m-d'));
    }

    public function testCommentRoundTrip(): void
    {
        $post = (new Post())
            ->setTitle('Crop update')
            ->setContent('This is a detailed post about crop status.')
            ->setAuthor('Admin')
            ->setAuthorId(1);

        $comment = new Comment();
        $comment->setIdPost($post)
            ->setContent('Nice update')
            ->setAuthor('Reader')
            ->setLikes(3)
            ->setCreatedAt(new \DateTimeImmutable('2026-05-04'))
            ->setAuthorId(7);

        self::assertSame($post, $comment->getIdPost());
        self::assertSame('Nice update', $comment->getContent());
        self::assertSame('Reader', $comment->getAuthor());
        self::assertSame(3, $comment->getLikes());
        self::assertSame(7, $comment->getAuthorId());
    }

    public function testOrdersRoundTrip(): void
    {
        $orders = new Orders();

        $orders->setUserId('user-1')
            ->setOrderDate(new \DateTimeImmutable('2026-05-04'))
            ->setTotalPrice(120.0)
            ->setStatus('PENDING')
            ->setDeliveryAddress('Farm Lane')
            ->setPaymentMethod('Cash')
            ->setCreatedAt(new \DateTimeImmutable('2026-05-04'))
            ->setDeliveryLat(34.1)
            ->setDeliveryLng(-1.2);

        self::assertSame('user-1', $orders->getUserId());
        self::assertSame(120.0, $orders->getTotalPrice());
        self::assertSame('PENDING', $orders->getStatus());
        self::assertSame('Farm Lane', $orders->getDeliveryAddress());
        self::assertSame('Cash', $orders->getPaymentMethod());
        self::assertSame(34.1, $orders->getDeliveryLat());
        self::assertSame(-1.2, $orders->getDeliveryLng());
    }

    public function testProductListingRoundTrip(): void
    {
        $listing = new ProductListing();

        $listing->setUserId('user-1')
            ->setProductName('Fertilizer')
            ->setPricePerUnit(12.75)
            ->setQuantity(8)
            ->setStatus('ACTIVE')
            ->setDescription('Slow-release fertilizer')
            ->setPicture('listing/fertilizer.jpg')
            ->setCategory('Inputs');

        self::assertSame('user-1', $listing->getUserId());
        self::assertSame('Fertilizer', $listing->getProductName());
        self::assertSame(12.75, $listing->getPricePerUnit());
        self::assertSame(8, $listing->getQuantity());
        self::assertSame('ACTIVE', $listing->getStatus());
        self::assertSame('Slow-release fertilizer', $listing->getDescription());
        self::assertSame('listing/fertilizer.jpg', $listing->getPicture());
        self::assertSame('Inputs', $listing->getCategory());
    }

    public function testPostRoundTrip(): void
    {
        $post = new Post();

        $post->setTitle('Harvest report')
            ->setContent('Detailed harvest report content here.')
            ->setImagePath('posts/harvest.jpg')
            ->setAuthor('Admin')
            ->setCategory('News')
            ->setStatus('Published')
            ->setCreatedAt(new \DateTimeImmutable('2026-05-04'))
            ->setAuthorId(2);

        self::assertSame('Harvest report', $post->getTitle());
        self::assertSame('Detailed harvest report content here.', $post->getContent());
        self::assertSame('posts/harvest.jpg', $post->getImagePath());
        self::assertSame('Admin', $post->getAuthor());
        self::assertSame('News', $post->getCategory());
        self::assertSame('Published', $post->getStatus());
        self::assertSame(2, $post->getAuthorId());
    }

    public function testPostReactionRoundTrip(): void
    {
        $post = (new Post())
            ->setTitle('Update')
            ->setContent('Long enough post content here.')
            ->setAuthor('Admin')
            ->setAuthorId(1);

        $user = (new User())
            ->setEmail('reader@example.com')
            ->setPassword('hashed');

        $reaction = new PostReaction();
        $reaction->setIdPost($post)
            ->setUser($user)
            ->setReaction('LIKE')
            ->setCreatedAt(new \DateTimeImmutable('2026-05-04'));

        self::assertSame($post, $reaction->getIdPost());
        self::assertSame($user, $reaction->getUser());
        self::assertSame('LIKE', $reaction->getReaction());
    }

    public function testOrderItemsRoundTrip(): void
    {
        $order = (new Orders())
            ->setUserId('user-1')
            ->setTotalPrice(100.0)
            ->setDeliveryAddress('Farm Lane')
            ->setCreatedAt(new \DateTimeImmutable('2026-05-04'));

        $product = (new ProductListing())
            ->setUserId('user-1')
            ->setProductName('Seeds')
            ->setPricePerUnit(5.0)
            ->setQuantity(10);

        $orderItem = new OrderItems();
        $orderItem->setOrder($order)
            ->setProduct($product)
            ->setProductName('Seeds')
            ->setQuantity(4)
            ->setPricePerUnit(5.0)
            ->setSubtotal(20.0);

        self::assertSame($order, $orderItem->getOrder());
        self::assertSame($product, $orderItem->getProduct());
        self::assertSame('Seeds', $orderItem->getProductName());
        self::assertSame(4, $orderItem->getQuantity());
        self::assertSame(5.0, $orderItem->getPricePerUnit());
        self::assertSame(20.0, $orderItem->getSubtotal());
    }

    public function testMessengerMessagesRoundTrip(): void
    {
        $message = new MessengerMessages();

        $message->setBody('payload')
            ->setHeaders('headers')
            ->setQueueName('default')
            ->setCreatedAt(new \DateTimeImmutable('2026-05-04'))
            ->setAvailableAt(new \DateTimeImmutable('2026-05-05'))
            ->setDeliveredAt(null);

        self::assertSame('payload', $message->getBody());
        self::assertSame('headers', $message->getHeaders());
        self::assertSame('default', $message->getQueueName());
        self::assertSame('2026-05-04', $message->getCreatedAt()->format('Y-m-d'));
        self::assertSame('2026-05-05', $message->getAvailableAt()->format('Y-m-d'));
        self::assertNull($message->getDeliveredAt());
    }
}