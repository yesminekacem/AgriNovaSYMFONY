<?php

namespace App\Controller;

use App\Entity\Inventory;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/qrcode')]
final class QrCodeController extends AbstractController
{
    #[Route('/inventory/{id}', name: 'qrcode_inventory', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function generateInventoryQr(Inventory $inventory): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_encode([
            'id' => $inventory->getId(),
            'name' => $inventory->getItemName(),
            'type' => $inventory->getItemType(),
            'owner' => $inventory->getOwnerName(),
            'status' => $inventory->getRentalStatus(),
            'url' => $this->generateUrl('inventory_show', ['id' => $inventory->getId()], 0),
        ]) ?: '{}';

        $builder = new Builder(
            writer: new SvgWriter(),
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );
        $result = $builder->build();

        return new Response(
            $result->getString(),
            200,
            ['Content-Type' => 'image/svg+xml']
        );
    }

    #[Route('/inventory/{id}/print', name: 'qrcode_inventory_print', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function printQrLabel(Inventory $inventory): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_encode([
            'id' => $inventory->getId(),
            'name' => $inventory->getItemName(),
            'type' => $inventory->getItemType(),
            'owner' => $inventory->getOwnerName(),
            'status' => $inventory->getRentalStatus(),
            'url' => $this->generateUrl('inventory_show', ['id' => $inventory->getId()], 0),
        ]) ?: '{}';

        $builder = new Builder(
            writer: new SvgWriter(),
            data: $data,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );
        $result = $builder->build();

        return $this->render('inventory/qr_label.html.twig', [
            'item' => $inventory,
            'qrSvg' => $result->getString(),
        ]);
    }
}
