<?php

namespace App\Controller;

use App\Entity\Crop;
use App\Repository\CropRepository;
use App\Form\CropType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/crops')]
final class CropController extends AbstractController
{
    #[Route('', name: 'app_crop_index', methods: ['GET'])]
    public function index(CropRepository $cropRepository): Response
    {
        $crops = $cropRepository->findAll();
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->render('Back/crop/tablecrop.html.twig', [
                'crops' => $crops,
            ]);
        }

        return $this->render('Front/crop/indexcrop.html.twig', [
            'crops' => $crops,
        ]);
    }

    #[Route('/new', name: 'app_crop_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $crop = new Crop();
        $form = $this->createForm(CropType::class, $crop);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $ext = null;
                try {
                    $ext = $imageFile->guessExtension();
                } catch (\Throwable $e) {
                    $ext = null;
                }
                if (!$ext) {
                    $ext = strtolower(pathinfo($imageFile->getClientOriginalName(), PATHINFO_EXTENSION)) ?: 'bin';
                }
                $newFilename = $safeFilename.'-'.uniqid().'.'.$ext;
                $imageFile->move(
                    $this->getParameter('kernel.project_dir').'/public/cropsimages',
                    $newFilename
                );
                $crop->setImagePath($newFilename);
            }

            $entityManager->persist($crop);
            $entityManager->flush();

            return $this->redirectToRoute('app_crop_table');
        }

        return $this->render('Back/crop/newcrop.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{crop_id}', name: 'app_crop_show', methods: ['GET'], requirements: ['crop_id' => '\\d+'])]
    public function show(int $cropId, CropRepository $cropRepository): Response
    {
        $crop = $cropRepository->find($cropId);
        if (!$crop) {
            throw $this->createNotFoundException('Crop not found');
        }

        return $this->render('Front/crop/showcrop.html.twig', [
            'crop' => $crop,
        ]);
    }

    #[Route('/{crop_id}/edit', name: 'app_crop_edit', methods: ['GET', 'POST'], requirements: ['crop_id' => '\\d+'])]
    public function edit(
        Request $request,
        #[MapEntity(expr: 'repository.find(crop_id)')] Crop $crop,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(CropType::class, $crop);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $ext = null;
                try {
                    $ext = $imageFile->guessExtension();
                } catch (\Throwable $e) {
                    $ext = null;
                }
                if (!$ext) {
                    $ext = strtolower(pathinfo($imageFile->getClientOriginalName(), PATHINFO_EXTENSION)) ?: 'bin';
                }
                $newFilename = $safeFilename.'-'.uniqid().'.'.$ext;
                $imageFile->move(
                    $this->getParameter('kernel.project_dir').'/public/cropsimages',
                    $newFilename
                );
                $crop->setImagePath($newFilename);
            }

            $entityManager->flush();
            return $this->redirectToRoute('app_crop_table', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('Back/crop/editcrop.html.twig', [
            'crop' => $crop,
            'form' => $form,
        ]);
    }

    #[Route('/{crop_id}', name: 'app_crop_delete', methods: ['POST'], requirements: ['crop_id' => '\\d+'])]
    public function delete(
        Request $request,
        #[MapEntity(expr: 'repository.find(crop_id)')] Crop $crop,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$crop->getCropId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($crop);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crop_table', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/table', name: 'app_crop_table')]
public function table(CropRepository $cropRepository): Response
{
    return $this->render('Back/crop/tablecrop.html.twig', [
        'crops' => $cropRepository->findAll(),
    ]);
}
}
