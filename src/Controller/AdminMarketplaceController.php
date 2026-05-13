<?php

namespace App\Controller;

use App\Entity\ProductListing;
use App\Form\ProductType;
use App\Repository\OrdersRepository;
use App\Repository\ProductListingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[Route('/admin/marketplace')]
class AdminMarketplaceController extends AbstractController
{
    #[Route('', name: 'admin_marketplace')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('Back/marketplace/index.html.twig');
    }

    // ─── Products ────────────────────────────────────────────────────────────

    #[Route('/products', name: 'admin_marketplace_products')]
    public function products(Request $request, ProductListingRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $category = $request->query->get('category') ?: null;

        return $this->render('Back/marketplace/products.html.twig', [
            'products'        => $repository->searchMarketplace(null, null, $category),
            'currentCategory' => $category,
        ]);
    }

    #[Route('/products/create', name: 'admin_marketplace_product_create')]
    public function createProduct(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $product = new ProductListing();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setUserId((string) $this->getUser()->getEmail());

            $imageFile = $form->get('picture')->getData();
            if ($imageFile) {
                $product->setPicture($this->handleImageUpload($imageFile));
            }

            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Product created successfully.');
            return $this->redirectToRoute('admin_marketplace_products');
        }

        return $this->render('Back/marketplace/product_form.html.twig', [
            'form'    => $form,
            'product' => $product,
            'is_edit' => false,
        ]);
    }

    #[Route('/products/{id}/edit', name: 'admin_marketplace_product_edit')]
    public function editProduct(ProductListing $product, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('picture')->getData();
            if ($imageFile) {
                $product->setPicture($this->handleImageUpload($imageFile));
            }

            $em->flush();
            $this->addFlash('success', 'Product updated successfully.');
            return $this->redirectToRoute('admin_marketplace_products');
        }

        return $this->render('Back/marketplace/product_form.html.twig', [
            'form'    => $form,
            'product' => $product,
            'is_edit' => true,
        ]);
    }

    #[Route('/products/{id}/delete', name: 'admin_marketplace_product_delete', methods: ['POST'])]
    public function deleteProduct(int $id, Request $request, ProductListingRepository $repository, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('delete_product_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin_marketplace_products');
        }

        $product = $repository->find($id);
        if (!$product) {
            $this->addFlash('error', 'Product not found.');
            return $this->redirectToRoute('admin_marketplace_products');
        }

        $em->remove($product);
        $em->flush();

        $this->addFlash('success', 'Product deleted.');
        return $this->redirectToRoute('admin_marketplace_products');
    }

    // ─── Orders ──────────────────────────────────────────────────────────────

    #[Route('/orders', name: 'admin_marketplace_orders')]
    public function orders(Request $request, OrdersRepository $repository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $status   = $request->query->get('status') ?: null;
        $criteria = $status ? ['status' => $status] : [];

        return $this->render('Back/marketplace/orders.html.twig', [
            'orders'        => $repository->findBy($criteria, ['id' => 'DESC']),
            'currentStatus' => $status,
        ]);
    }

    #[Route('/orders/{id}/validate', name: 'admin_marketplace_order_validate', methods: ['POST'])]
    public function validateOrder(int $id, Request $request, OrdersRepository $repository, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('validate_order_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin_marketplace_orders');
        }

        $order = $repository->find($id);
        if (!$order) {
            $this->addFlash('error', 'Order not found.');
            return $this->redirectToRoute('admin_marketplace_orders');
        }

        $order->setStatus('Delivered');
        $em->flush();

        $this->addFlash('success', 'Order #' . $id . ' marked as Delivered.');
        return $this->redirectToRoute('admin_marketplace_orders');
    }

    #[Route('/orders/{id}/delete', name: 'admin_marketplace_order_delete', methods: ['POST'])]
    public function deleteOrder(int $id, Request $request, OrdersRepository $repository, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('delete_order_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin_marketplace_orders');
        }

        $order = $repository->find($id);
        if (!$order) {
            $this->addFlash('error', 'Order not found.');
            return $this->redirectToRoute('admin_marketplace_orders');
        }

        $em->remove($order);
        $em->flush();

        $this->addFlash('success', 'Order #' . $id . ' deleted.');
        return $this->redirectToRoute('admin_marketplace_orders');
    }

    #[Route('/orders/{id}/invoice', name: 'admin_marketplace_order_invoice')]
    public function invoiceOrder(int $id, OrdersRepository $repository, Environment $twig): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $order = $repository->find($id);
        if (!$order) {
            throw $this->createNotFoundException('Order not found.');
        }

        $html = $twig->render('Back/marketplace/invoice.html.twig', ['order' => $order]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-order-' . $id . '.pdf"',
        ]);
    }

    // ─── Shared ──────────────────────────────────────────────────────────────

    private function handleImageUpload(UploadedFile $file): string
    {
        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
        $allowed   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array(strtolower($extension), $allowed)) {
            $extension = 'jpg';
        }

        $filename  = md5(uniqid()) . '.' . strtolower($extension);
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/products';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file->move($uploadDir, $filename);

        // Store the ABSOLUTE path so Java (and Symfony) can locate the file
        // without any additional path construction. Symfony extracts the
        // filename from this path when building the web-accessible URL.
        return realpath($uploadDir . '/' . $filename);
    }

    /**
     * Extracts just the filename from an absolute path stored in the database.
     * Used by Twig templates to build the /uploads/products/<filename> web URL.
     */
    public static function pictureFilename(?string $absolutePath): string
    {
        if (!$absolutePath) return '';
        return basename($absolutePath);
    }
}
