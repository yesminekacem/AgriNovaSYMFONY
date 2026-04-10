<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin/users', name: 'admin_users', methods: ['GET'])]
    public function users(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $userRepository->findAll();

        return $this->render('Front/admin_users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/users/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('delete_user_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin_users');
        }

        $user = $userRepository->find($id);
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('admin_users');
        }

        // Prevent deleting yourself
        if ($this->getUser() && $this->getUser()->getId() === $user->getId()) {
            $this->addFlash('error', 'You cannot delete your own account.');
            return $this->redirectToRoute('admin_users');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'User deleted.');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/admin/users/{id}/promote', name: 'admin_user_promote', methods: ['POST'])]
    public function promote(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->isCsrfTokenValid('promote_user_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin_users');
        }

        $user = $userRepository->find($id);
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('admin_users');
        }

        $user->setRole('ADMIN');
        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'User promoted to ADMIN.');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/admin/users/{id}/demote', name: 'admin_user_demote', methods: ['POST'])]
    public function demote(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->isCsrfTokenValid('demote_user_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin_users');
        }

        $user = $userRepository->find($id);
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('admin_users');
        }

        // Prevent demoting yourself
        if ($this->getUser() && $this->getUser()->getId() === $user->getId()) {
            $this->addFlash('error', 'You cannot demote your own account.');
            return $this->redirectToRoute('admin_users');
        }

        $user->setRole('USER');
        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'User demoted to USER.');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/admin/users/{id}/toggle-block', name: 'admin_user_toggle_block', methods: ['POST'])]
    public function toggleBlock(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->isCsrfTokenValid('block_user_' . $id, $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin_users');
        }

        $user = $userRepository->find($id);
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('admin_users');
        }

        // Toggle the legacy 'banned' column: set to 1 or 0
        // We assume the entity has no explicit banned property, so use a raw change via Entity
        $conn = $em->getConnection();
        $current = (int)$conn->fetchOne('SELECT banned FROM user WHERE id = :id', ['id' => $id]);
        $new = $current ? 0 : 1;
        $conn->executeStatement('UPDATE user SET banned = :new WHERE id = :id', ['new' => $new, 'id' => $id]);

        $this->addFlash('success', $new ? 'User blocked.' : 'User unblocked.');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/admin/users/{id}/edit', name: 'admin_user_edit', methods: ['GET','POST'])]
    public function edit(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $userRepository->find($id);
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('admin_users');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('edit_user_' . $id, $request->request->get('_csrf_token'))) {
                $this->addFlash('error', 'Invalid CSRF token.');
                return $this->redirectToRoute('admin_users');
            }

            $fullName = trim($request->request->get('fullName', '')) ?: null;
            $email = trim($request->request->get('email', ''));
            $role = strtoupper(trim($request->request->get('role', 'USER')));
            $isVerified = $request->request->get('isVerified') ? true : false;

            // Check for duplicate email
            $existing = $userRepository->findOneBy(['email' => $email]);
            if ($existing && $existing->getId() !== $user->getId()) {
                $this->addFlash('error', 'Email already used by another account.');
                return $this->redirectToRoute('admin_user_edit', ['id' => $id]);
            }

            $user->setFullName($fullName);
            $user->setEmail($email);
            $user->setRole($role === 'ADMIN' ? 'ADMIN' : 'USER');
            $user->setIsVerified($isVerified);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'User updated.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('Front/admin_user_edit.html.twig', [
            'user' => $user,
        ]);
    }
}
