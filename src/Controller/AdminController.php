<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Snappy\Pdf;
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

    #[Route('/admin/users/export/pdf', name: 'admin_users_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, UserRepository $userRepository, Pdf $knpSnappy): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $search = trim((string) $request->query->get('search', ''));
        $role = strtoupper(trim((string) $request->query->get('role', '')));
        $blocked = strtolower(trim((string) $request->query->get('blocked', '')));

        $users = $this->filterUsers($userRepository->findAll(), $search, $role, $blocked);

        $html = $this->renderView('Front/admin_users_pdf.html.twig', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'blocked' => $blocked,
            'generatedAt' => new \DateTimeImmutable(),
        ]);

        $pdf = $knpSnappy->getOutputFromHtml($html, [
            'encoding' => 'utf-8',
            'page-size' => 'A4',
            'orientation' => 'Landscape',
            'margin-top' => 8,
            'margin-bottom' => 8,
            'margin-left' => 8,
            'margin-right' => 8,
        ]);

        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="admin-users.pdf"',
        ]);
    }

    /** @param array<User> $users */
    private function filterUsers(array $users, string $search, string $role, string $blocked): array
    {
        return array_values(array_filter($users, static function (User $user) use ($search, $role, $blocked): bool {
            if ($search !== '') {
                $haystacks = [
                    strtolower((string) $user->getEmail()),
                    strtolower((string) ($user->getFullName() ?? '')),
                    (string) $user->getId(),
                ];
                $needle = strtolower($search);
                $found = false;
                foreach ($haystacks as $value) {
                    if (str_contains($value, $needle)) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    return false;
                }
            }

            if ($role !== '' && strtoupper((string) $user->getRole()) !== $role) {
                return false;
            }

            if ($blocked === 'blocked' && !$user->isBanned()) {
                return false;
            }

            if ($blocked === 'unblocked' && $user->isBanned()) {
                return false;
            }

            return true;
        }));
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
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
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
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
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
