<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\Orders;
use App\Entity\OrderItems;
use App\Entity\ProductListing;
use App\Form\CheckoutType;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart')]
#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    #[Route('/', name: 'cart_index')]
    public function index(CartRepository $cartRepository): Response
    {
        $user = $this->getUser();
        $cartItems = $cartRepository->findBy(['userId' => (string)$user->getEmail()]);

        return $this->render('cart/index.html.twig', [
            'cart_items' => $cartItems,
        ]);
    }

    #[Route('/add/{listingId}', name: 'cart_add', methods: ['POST'])]
    public function add(int $listingId, Request $request, EntityManagerInterface $em, CartRepository $cartRepository, \App\Repository\ProductListingRepository $productRepository, \Symfony\Component\Validator\Validator\ValidatorInterface $validator): Response
    {
        $product = $productRepository->find($listingId);
        
        if (!$product) {
            $this->addFlash('error', 'Product not found.');
            return $this->redirectToRoute('product_marketplace');
        }

        $quantity = (int)$request->request->get('quantity', 1);

        $user = $this->getUser();
        $userId = (string)$user->getEmail();

        // Check if item is already in cart
        $cartItem = $cartRepository->findOneBy([
            'userId' => $userId,
            'product' => $product
        ]);

        if ($cartItem) {
            $newQuantity = $cartItem->getQuantity() + $quantity;
            $cartItem->setQuantity($newQuantity);
        } else {
            $cartItem = new Cart();
            $cartItem->setUserId($userId);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);
            $cartItem->setAddedAt(new \DateTime());
            $em->persist($cartItem);
        }

        // Validate the Cart entity
        $errors = $validator->validate($cartItem);
        
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error->getMessage());
            }
            return $this->redirectToRoute('product_marketplace');
        }

        $em->flush();

        $this->addFlash('success', 'Product added to cart!');

        return $this->redirectToRoute('cart_index');
    }

    #[Route('/remove/{id}', name: 'cart_remove')]
    public function remove(Cart $cartItem, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        if ($cartItem->getUserId() !== (string)$user->getEmail()) {
            throw $this->createAccessDeniedException('You cannot modify this cart item.');
        }

        $em->remove($cartItem);
        $em->flush();

        $this->addFlash('success', 'Item removed from cart.');

        return $this->redirectToRoute('cart_index');
    }

    #[Route('/checkout', name: 'cart_checkout', methods: ['GET', 'POST'])]
    public function checkout(Request $request, CartRepository $cartRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $userId = (string)$user->getEmail();
        
        // Get cart items
        $cartItems = $cartRepository->findBy(['userId' => $userId]);
        
        if (empty($cartItems)) {
            $this->addFlash('warning', 'Your cart is empty.');
            return $this->redirectToRoute('cart_index');
        }
        
        // Create form
        $form = $this->createForm(CheckoutType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Calculate total price
            $totalPrice = 0;
            foreach ($cartItems as $cartItem) {
                $totalPrice += $cartItem->getProduct()->getPricePerUnit() * $cartItem->getQuantity();
            }
            
            // Create order
            $order = new Orders();
            $order->setUserId($userId);
            $order->setDeliveryAddress($form->get('deliveryAddress')->getData());
            $order->setPaymentMethod($form->get('paymentMethod')->getData());
            $order->setOrderDate(new \DateTime());
            $order->setStatus('Pending');
            $order->setTotalPrice($totalPrice);
            $order->setCreatedAt(new \DateTime());
            
            $em->persist($order);
            $em->flush();
            
            // Create order items
            foreach ($cartItems as $cartItem) {
                $product = $cartItem->getProduct();
                $pricePerUnit = $product->getPricePerUnit();
                $quantity = $cartItem->getQuantity();
                $subtotal = $pricePerUnit * $quantity;
                
                $orderItem = new OrderItems();
                $orderItem->setOrder($order);
                $orderItem->setProduct($product);
                $orderItem->setProductName($product->getProductName());
                $orderItem->setQuantity($quantity);
                $orderItem->setPricePerUnit($pricePerUnit);
                $orderItem->setSubtotal($subtotal);
                
                $em->persist($orderItem);
            }
            
            // Clear cart
            foreach ($cartItems as $cartItem) {
                $em->remove($cartItem);
            }
            
            $em->flush();
            
            $this->addFlash('success', 'Order created successfully! Order ID: ' . $order->getId());
            
            return $this->redirectToRoute('cart_index');
        }
        
        return $this->render('cart/checkout.html.twig', [
            'form' => $form,
            'cart_items' => $cartItems,
        ]);
    }
}
