<?php

namespace App\Controller\Api;

use App\Service\DiagnosisService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;

final class DiagnosisController extends AbstractController
{
    private DiagnosisService $diagnosisService;

    public function __construct(DiagnosisService $diagnosisService)
    {
        $this->diagnosisService = $diagnosisService;
    }

    #[Route('/api/v1/diagnose', name: 'api_diagnose', methods: ['POST'])]
    public function diagnose(Request $request): JsonResponse
    {
        $file = $request->files->get('image');
        if (!$file instanceof UploadedFile) {
            return $this->json(['error' => 'No image file uploaded'], 400);
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowed, true)) {
            return $this->json(['error' => 'Invalid image type'], 400);
        }

        $max = 5 * 1024 * 1024; // 5MB
        if ($file->getSize() > $max) {
            return $this->json(['error' => 'File too large'], 400);
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $targetDir = $projectDir . '/var/uploads/diagnosis';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $filename = uniqid('img_', true) . '.' . $file->guessExtension();
        try {
            $file->move($targetDir, $filename);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to save uploaded file'], 500);
        }

        $path = $targetDir . '/' . $filename;
        $result = $this->diagnosisService->diagnose($path);

        $response = [
            'disease' => $result['disease'] ?? 'unknown',
            'confidence' => isset($result['confidence']) ? (float) $result['confidence'] : 0.0,
        ];

        if (isset($result['error'])) {
            $response['error'] = $result['error'];
        }

        return $this->json($response);
    }
}
