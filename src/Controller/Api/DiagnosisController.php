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

    if ($file->getSize() > 5 * 1024 * 1024) {
        return $this->json(['error' => 'File too large'], 400);
    }

    $projectDir = $this->getParameter('kernel.project_dir');
    $targetDir  = $projectDir . '/var/uploads/diagnosis';

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true); // 0755 is safer than 0777
    }

    $filename = uniqid('img_', true) . '.' . ($file->guessExtension() ?? 'bin');

    try {
        $file->move($targetDir, $filename);
    } catch (\Exception $e) {
        return $this->json(['error' => 'Failed to save uploaded file'], 500);
    }

    $path   = $targetDir . '/' . $filename;
    $result = $this->diagnosisService->diagnose($path);

    // Always clean up the temp file
    if (file_exists($path)) {
        unlink($path);
    }

    // Propagate ML service errors with a proper HTTP status
    if (isset($result['error'])) {
        return $this->json(['error' => $result['error']], 502);
    }

    return $this->json([
        'disease'    => $result['disease']    ?? 'unknown',
        'confidence' => isset($result['confidence']) ? (float) $result['confidence'] : 0.0,
    ]);
}
}
