<?php

namespace App\Controller;

use App\Entity\ProductListing;
use App\Entity\User;
use App\Form\ProductType;
use App\Repository\ProductListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/product')]
final class ProductController extends AbstractController
{
    #[Route('/manage', name: 'product_manage')]
    #[IsGranted('ROLE_USER')]
    public function manage(ProductListingRepository $repository): Response
    {
        $user = $this->getUser();
        $products = $repository->findBy(['userId' => (string)$user->getEmail()]);

        return $this->render('product/manage.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/add', name: 'product_add')]
    #[IsGranted('ROLE_USER')]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $product = new ProductListing();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentUser = $this->getUser();
            $product->setUserId($currentUser instanceof User ? (string)$currentUser->getEmail() : '');
            
            // Handle file upload
            $imageFile = $form->get('picture')->getData();
            if ($imageFile) {
                $newFilename = $this->handleImageUpload($imageFile);
                $product->setPicture($newFilename);
            }
            
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Product created successfully!');
            return $this->redirectToRoute('product_manage');
        }

        return $this->render('product/add.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/edit/{id}', name: 'product_edit')]
    #[IsGranted('ROLE_USER')]
    public function edit(ProductListing $product, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        if ($product->getUserId() !== (string)$user->getEmail()) {
            throw $this->createAccessDeniedException('You are not allowed to edit this product.');
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload for edit
            $imageFile = $form->get('picture')->getData();
            if ($imageFile) {
                $newFilename = $this->handleImageUpload($imageFile);
                $product->setPicture($newFilename);
            }
            
            $em->flush();
            $this->addFlash('success', 'Product updated successfully!');
            return $this->redirectToRoute('product_manage');
        }

        return $this->render('product/edit.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }

    #[Route('/delete/{id}', name: 'product_delete')]
    #[IsGranted('ROLE_USER')]
    public function delete(ProductListing $product, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        if ($product->getUserId() !== (string)$user->getEmail()) {
            throw $this->createAccessDeniedException('You are not allowed to delete this product.');
        }

        $em->remove($product);
        $em->flush();

        $this->addFlash('success', 'Product deleted successfully!');
        return $this->redirectToRoute('product_manage');
    }

    #[Route('/marketplace', name: 'product_marketplace')]
    public function marketplace(Request $request, ProductListingRepository $repository): Response
    {
        $user = $this->getUser();
        $userId = $user ? (string)$user->getEmail() : null;
        $query = $request->query->get('q');
        $category = $request->query->get('category');

        // Get matching products except those belonging to current user
        $products = $repository->searchMarketplace($query, $userId, $category ?: null);

        // If it's an AJAX request (live search), return only the products grid
        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return $this->render('product/_marketplace_products.html.twig', [
                'products' => $products,
            ]);
        }

        return $this->render('product/marketplace.html.twig', [
            'products' => $products,
            'currentCategory' => $category,
        ]);
    }

    private function handleImageUpload(UploadedFile $file): string
    {
        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($extension), $allowedExtensions)) {
            $extension = 'jpg';
        }

        $newFilename = md5(uniqid()) . '.' . strtolower($extension);

        $uploadDir = $this->getParameter('kernel.project_dir')
            . DIRECTORY_SEPARATOR . 'public'
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'products';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file->move($uploadDir, $newFilename);

        // Return the full absolute path so Java can load it directly.
        // Twig templates extract the filename from this path for the web URL.
        return $uploadDir . DIRECTORY_SEPARATOR . $newFilename;
    }
}
