<?php

namespace App\Controller;

use App\Repository\OrdersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/orders')]
#[IsGranted('ROLE_USER')]
class OrdersController extends AbstractController
{
    #[Route('/', name: 'orders_index')]
    public function index(OrdersRepository $ordersRepository): Response
    {
        $user = $this->getUser();
        $userId = (string)$user->getEmail();
        
        // Get all orders for the current user, ordered by date descending
        $orders = $ordersRepository->findBy(
            ['userId' => $userId],
            ['orderDate' => 'DESC']
        );

        return $this->render('orders/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/{id}', name: 'orders_detail')]
    public function detail(int $id, OrdersRepository $ordersRepository): Response
    {
        $user = $this->getUser();
        $userId = (string)$user->getEmail();
        
        $order = $ordersRepository->find($id);
        
        if (!$order || $order->getUserId() !== $userId) {
            throw $this->createAccessDeniedException('You cannot view this order.');
        }

        return $this->render('orders/detail.html.twig', [
            'order' => $order,
        ]);
    }
}
