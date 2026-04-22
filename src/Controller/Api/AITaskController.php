<?php

namespace App\Controller\Api;

use App\Repository\CropRepository;
use App\Service\AITaskGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class AITaskController extends AbstractController
{
    #[Route('/api/ai-generate/{crop_id}', name: 'ai_generate', methods: ['POST'])]
    public function aiGenerate(
        int $crop_id,
        AITaskGenerator $ai,
        CropRepository $cropRepo
    ): JsonResponse {
        $crop = $cropRepo->find($crop_id);

        if (!$crop) {
            return $this->json(['error' => 'Crop not found'], 404);
        }

        try {
            // Just generate and return — don't save yet
            $tasks = $ai->generateTasksPreview($crop);
            return $this->json(['success' => true, 'tasks' => $tasks]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/api/ai-save-tasks/{crop_id}', name: 'ai_save_tasks', methods: ['POST'])]
    public function saveTasks(
        int $crop_id,
        \Symfony\Component\HttpFoundation\Request $request,
        AITaskGenerator $ai,
        CropRepository $cropRepo
    ): JsonResponse {
        $crop = $cropRepo->find($crop_id);

        if (!$crop) {
            return $this->json(['error' => 'Crop not found'], 404);
        }

        $body = json_decode($request->getContent(), true);
        $tasks = $body['tasks'] ?? [];

        if (empty($tasks)) {
            return $this->json(['error' => 'No tasks selected'], 400);
        }

        try {
            $ai->saveSelectedTasks($crop, $tasks);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}