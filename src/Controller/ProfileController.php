<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Service\FaceApiClient;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET','POST'])]
    public function profile(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository, \App\Service\FaceApiClient $faceClient): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $formType = $request->request->get('form_type', 'profile');

            // Choose token name per form
            if ($formType === 'security') {
                if (!$this->isCsrfTokenValid('profile_security', $request->request->get('_csrf_token'))) {
                    $this->addFlash('error', 'Invalid CSRF token.');
                    return $this->redirectToRoute('app_profile');
                }

                $current = (string) $request->request->get('current_password', '');
                $new = (string) $request->request->get('new_password', '');
                $confirm = (string) $request->request->get('confirm_password', '');

                // verify current password
                if (!$passwordHasher->isPasswordValid($user, $current)) {
                    $this->addFlash('error', 'Current password is incorrect.');
                    return $this->redirectToRoute('app_profile', ['_fragment' => 'security-section']);
                }

                if ($new !== $confirm) {
                    $this->addFlash('error', 'New passwords do not match.');
                    return $this->redirectToRoute('app_profile', ['_fragment' => 'security-section']);
                }

                // password format rules
                $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/';
                if (!preg_match($pattern, $new)) {
                    $this->addFlash('error', 'New password must be at least 8 characters and include upper and lower case letters, a number and a special character.');
                    return $this->redirectToRoute('app_profile', ['_fragment' => 'security-section']);
                }

                $hashed = $passwordHasher->hashPassword($user, $new);
                $user->setPassword($hashed);
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Password updated.');
                return $this->redirectToRoute('app_profile', ['_fragment' => 'security-section']);
            }

            // profile form (default)
            if (!$this->isCsrfTokenValid('profile_edit', $request->request->get('_csrf_token'))) {
                $this->addFlash('error', 'Invalid CSRF token.');
                return $this->redirectToRoute('app_profile');
            }

            // Face enrollment (file upload or camera base64)
            if ($formType === 'face_enroll') {
                /** @var UploadedFile|null $face */
                $face = $request->files->get('face_image');
                $faceBase64 = trim((string) $request->request->get('face_image', '')) ?: null;

                if ($face instanceof UploadedFile || $faceBase64) {
                    try {
                        if ($face instanceof UploadedFile) {
                            $base64 = $faceClient->enroll($face, '');
                        } else {
                            $base64 = $faceClient->enroll($faceBase64, '');
                        }
                        $user->setFaceData($base64);
                        $em->persist($user);
                        $em->flush();

                        $this->addFlash('success', 'Face enrolled successfully. You can now login using Face ID.');
                    } catch (\Throwable $e) {
                        $this->addFlash('error', 'Failed to enroll face: ' . $e->getMessage());
                    }
                } else {
                    $this->addFlash('error', 'No face image provided.');
                }

                return $this->redirectToRoute('app_profile');
            }

            if ($formType === 'face_remove') {
                // remove enrolled face
                $user->setFaceData(null);
                $em->persist($user);
                $em->flush();
                $this->addFlash('success', 'Face enrollment removed.');
                return $this->redirectToRoute('app_profile');
            }

            $fullName = trim($request->request->get('fullName', '')) ?: null;
            $email = trim($request->request->get('email', ''));

            if ($email && $email !== $user->getEmail()) {
                $existing = $userRepository->findOneBy(['email' => $email]);
                if ($existing && $existing->getId() !== $user->getId()) {
                    $this->addFlash('error', 'Email already in use by another account.');
                    return $this->redirectToRoute('app_profile');
                }
                $user->setEmail($email);
            }

            $user->setFullName($fullName);

            // Handle profile image upload
            /** @var UploadedFile|null $uploaded */
            $uploaded = $request->files->get('profile_image');
            if ($uploaded instanceof UploadedFile) {
                // Basic validation: image mime and size <= 2MB
                // Try to get a reliable mime type. If php_fileinfo is not available
                // fall back to the client-provided mime type.
                try {
                    $mime = $uploaded->getMimeType();
                } catch (\Throwable $e) {
                    $mime = $uploaded->getClientMimeType();
                }
                if (!$mime) {
                    $mime = $uploaded->getClientMimeType();
                }

                $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                // If mime is not one of allowed values, try to validate by client extension as a last resort
                if (!in_array($mime, $allowed, true)) {
                    $clientExt = strtolower(pathinfo($uploaded->getClientOriginalName(), PATHINFO_EXTENSION));
                    $extAllowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    if (!in_array($clientExt, $extAllowed, true)) {
                        $this->addFlash('error', 'Invalid image type. Allowed: JPG, PNG, GIF, WEBP.');
                        return $this->redirectToRoute('app_profile');
                    }
                }

                if ($uploaded->getSize() > 2_000_000) {
                    $this->addFlash('error', 'Image is too large (max 2MB).');
                    return $this->redirectToRoute('app_profile');
                }

                $uploadsDir = $this->getParameter('profile_images_directory');
                if (!is_dir($uploadsDir)) {
                    @mkdir($uploadsDir, 0775, true);
                }

                // guessExtension() may rely on fileinfo; fallback to client original extension if needed
                $ext = null;
                try {
                    $ext = $uploaded->guessExtension();
                } catch (\Throwable $e) {
                    $ext = null;
                }
                if (!$ext) {
                    $ext = strtolower(pathinfo($uploaded->getClientOriginalName(), PATHINFO_EXTENSION)) ?: 'bin';
                }

                $safe = bin2hex(random_bytes(8));
                $filename = $safe . '.' . $ext;

                try {
                    $moved = $uploaded->move($uploadsDir, $filename);

                    // remove old file if present
                    $old = $user->getProfileImage();
                    if ($old) {
                        $oldPath = $old;
                        if (!is_file($oldPath)) {
                            $oldPath = $uploadsDir . DIRECTORY_SEPARATOR . basename($old);
                        }
                        if (is_file($oldPath)) {
                            @unlink($oldPath);
                        }
                    }

                    $user->setProfileImage($moved->getPathname());

                } catch (\Exception $e) {
                    $this->addFlash('error', 'Failed to upload image.');
                    return $this->redirectToRoute('app_profile');
                }
            }

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Profile updated.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('Front/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/image', name: 'app_profile_image', methods: ['GET'])]
    public function profileImage(Request $request, UserRepository $userRepository): Response
    {
        $path = $request->query->get('path');
        if (!is_string($path) || $path === '') {
            throw new NotFoundHttpException();
        }

        $allowedUser = $userRepository->findOneBy(['profileImage' => $path]);
        if (!$allowedUser || !is_file($path)) {
            throw new NotFoundHttpException();
        }

        $mime = mime_content_type($path) ?: 'application/octet-stream';
        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $mime);
        $response->trustXSendfileTypeHeader(false);

        return $response;
    }
}
