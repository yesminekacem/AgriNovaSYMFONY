<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class MarketplaceController extends AbstractController
{
    #[Route('/marketplace', name: 'app_marketplace')]
    public function index(): Response
    {
        return $this->render('Front/marketplace.html.twig', [
            'heading' => 'Marketplace',
            'description' => 'Browse the marketplace and access product management, cart, and order pages.',
        ]);
    }

    #[Route('/marketplace/manage-products', name: 'app_marketplace_manage_products')]
    public function manageProducts(): Response
    {
        return $this->render('Front/marketplace.html.twig', [
            'heading' => 'Manage Products',
            'description' => 'Create, edit, and remove marketplace products from this section.',
        ]);
    }

    #[Route('/marketplace/cart', name: 'app_marketplace_cart')]
    public function cart(): Response
    {
        return $this->render('Front/marketplace.html.twig', [
            'heading' => 'Cart',
            'description' => 'Review your cart items before checking out.',
        ]);
    }

    #[Route('/marketplace/orders', name: 'app_marketplace_orders')]
    public function orders(): Response
    {
        return $this->render('Front/marketplace.html.twig', [
            'heading' => 'Orders',
            'description' => 'View your orders and track order status here.',
        ]);
    }
}
