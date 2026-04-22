<?php

namespace App\Controller;

use App\Entity\Rental;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/invoice')]
final class InvoiceController extends AbstractController
{
    #[Route('/rental/{id}', name: 'invoice_rental', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function generateRentalInvoice(Rental $rental): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        $html = $this->renderView('rental/invoice.html.twig', [
            'rental' => $rental,
            'invoiceDate' => new \DateTime(),
            'invoiceNumber' => sprintf('INV-%s-%04d', date('Y'), $rental->getId()),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('attachment; filename="invoice-rental-%d.pdf"', $rental->getId()),
            ]
        );
    }

    #[Route('/rental/{id}/preview', name: 'invoice_rental_preview', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function previewRentalInvoice(Rental $rental): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('rental/invoice.html.twig', [
            'rental' => $rental,
            'invoiceDate' => new \DateTime(),
            'invoiceNumber' => sprintf('INV-%s-%04d', date('Y'), $rental->getId()),
        ]);
    }
}
