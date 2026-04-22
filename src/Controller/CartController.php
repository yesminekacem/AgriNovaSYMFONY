<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\ProductListing;
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
}
