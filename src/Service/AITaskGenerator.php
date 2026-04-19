<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task;
use App\Repository\CropRepository;

class AITaskGenerator
{
    private $client;
    private $em;

    public function __construct(
        HttpClientInterface $client,
        EntityManagerInterface $em,
        CropRepository $cropRepo
    ) {
        $this->client = $client;
        $this->em = $em;
    }

    // Returns array of task strings — does NOT save
    public function generateTasksPreview($crop): array
    {
        $cropName = $crop->getName();
        $stage    = $crop->getGrowthStage();

        $response = $this->client->request('POST', 'http://localhost:11434/api/generate', [
            'json' => [
                'model'  => 'llama3',
                'prompt' => "Generate 5 short farming tasks for $cropName currently in the $stage stage. No numbering, one per line.",
                'stream' => false
            ]
        ]);

        $data  = $response->toArray();
        $lines = preg_split('/\r\n|\r|\n/', $data['response']);

        $tasks = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $tasks[] = substr($line, 0, 100);
            }
        }

        return $tasks;
    }

    // Saves only the tasks the user selected
    public function saveSelectedTasks($crop, array $taskNames): void
    {
        foreach ($taskNames as $name) {
            $name = trim($name);
            if ($name === '') continue;

            $task = new Task();
            $task->setTaskName(substr($name, 0, 100));
            $task->setDescription('AI generated (Ollama)');
            $task->setTaskType('AI');
            $task->setScheduledDate(new \DateTime('+1 day'));
            $task->setStatus('pending');
            $task->setAssignedTo('System');
            $task->setCost(0);
            $task->setCrop($crop);

            $this->em->persist($task);
        }

        $this->em->flush();
    }
}