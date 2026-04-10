<?php

namespace App\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Form\TaskType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/task')]
class TaskController extends AbstractController
{
    #[Route('/', name: 'app_task_index', methods: ['GET'])]
    public function index(TaskRepository $taskRepository): Response
    {
        return $this->render('front/crop/view_task.html.twig', [
            'tasks' => $taskRepository->findAll(),
        ]);
    }

    // ✅ /new MUST be declared before /{id} routes
    #[Route('/new', name: 'app_task_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($task);
            $em->flush();

            return $this->redirectToRoute('app_task_index');
        }

        return $this->render('front/crop/newtask.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ✅ requirements: id must be a number, never matches "new"
    #[Route('/{id}', name: 'app_task_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Task $task): Response
    {
        return $this->render('front/crop/showtask.html.twig', [
            'task' => $task,
        ]);
    }

    // ✅ requirements: id must be a number
    #[Route('/{id}/edit', name: 'app_task_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Task $task, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_task_index');
        }

        return $this->render('front/crop/edittask.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    #[Route('/{id}', name: 'app_task_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
public function delete(Request $request, Task $task, EntityManagerInterface $em): Response
{
    if ($this->isCsrfTokenValid('delete' . $task->getTaskId(), $request->request->get('_token'))) {
        $em->remove($task);
        $em->flush();
    }

    return $this->redirectToRoute('app_task_index');
}
}