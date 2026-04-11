<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PostRepository;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ForumController extends AbstractController
{
#[Route('/forum', name: 'app_forum')]
public function index(PostRepository $postRepository, CommentRepository $commentRepository): Response
{
    $posts = $postRepository->findActivePosts();
    $commentsByPost = [];
    $postsData = [];

    foreach ($posts as $post) {
        $comments = $commentRepository->findByPost($post->getIdPost());
        $commentsByPost[$post->getIdPost()] = $comments;

        $commentsData = [];

        foreach ($comments as $comment) {
            $commentsData[] = [
                'id' => $comment->getIdComment(),
                'author' => $comment->getAuthor(),
                'authorId' => $comment->getAuthorId(),
                'content' => $comment->getContent(),
                'createdAt' => $comment->getCreatedAt()
                    ? $comment->getCreatedAt()->format('Y-m-d H:i')
                    : 'now',
            ];
        }

        $postsData[$post->getIdPost()] = [
            'id' => $post->getIdPost(),
            'title' => $post->getTitle(),
            'author' => $post->getAuthor(),
            'authorId' => $post->getAuthorId(),
            'category' => $post->getCategory() ?: 'Organic Farming',
            'content' => $post->getContent(),
            'createdAt' => $post->getCreatedAt()
                ? $post->getCreatedAt()->format('Y-m-d H:i')
                : 'No date',
            'image' => $post->getImagePath()
                ? 'Front/images/Forum/' . $post->getImagePath()
                : null,
            'comments' => $commentsData,
        ];
    }

    return $this->render('Front/forum.html.twig', [
        'posts' => $posts,
        'commentsByPost' => $commentsByPost,
        'postsData' => $postsData,
    ]);
}

    #[Route('/forum/new', name: 'app_forum_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $title = trim($request->request->get('title', ''));
        $category = trim($request->request->get('category', ''));
        $content = trim($request->request->get('content', ''));
        $imageFile = $request->files->get('image');

        if ($title === '' || $category === '' || $content === '') {
            $this->addFlash('error', 'Please fill in all required fields.');
            return $this->redirectToRoute('app_forum');
        }

        $post = new Post();
        $post->setTitle($title);
        $post->setCategory($category);
        $post->setContent($content);
        $post->setStatus('ACTIVE');
        $post->setCreatedAt(new \DateTime());
        $user = $this->getUser();

if (!$user) {
    $this->addFlash('error', 'You must be logged in.');
    return $this->redirectToRoute('app_login'); // or your login route
}

$post->setAuthor($user->getFullName());
$post->setAuthorId($user->getId());
        $post->setImagePath(null);

        if ($imageFile) {
            $newFilename = uniqid() . '.' . $imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/Front/images/Forum',
                    $newFilename
                );
                $post->setImagePath($newFilename);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Image upload failed.');
            }
        }

        $entityManager->persist($post);
        $entityManager->flush();

        $this->addFlash('success', 'Post created successfully.');

        return $this->redirectToRoute('app_forum');
    }

    #[Route('/forum/update/{id}', name: 'app_forum_update', methods: ['POST'])]
public function update(
    int $id,
    Request $request,
    PostRepository $postRepository,
    EntityManagerInterface $entityManager
): Response {
    $post = $postRepository->find($id);

    if (!$post) {
        $this->addFlash('error', 'Post not found.');
        return $this->redirectToRoute('app_forum');
    }

    $user = $this->getUser();

    if (!$user) {
        $this->addFlash('error', 'You must be logged in.');
        return $this->redirectToRoute('app_login');
    }

    if ($post->getAuthorId() !== $user->getId()) {
        $this->addFlash('error', 'You cannot edit this post.');
        return $this->redirectToRoute('app_forum');
    }

    $title = trim($request->request->get('title', ''));
    $category = trim($request->request->get('category', ''));
    $content = trim($request->request->get('content', ''));

    if ($title === '' || $category === '' || $content === '') {
        $this->addFlash('error', 'Please fill in all required fields.');
        return $this->redirectToRoute('app_forum');
    }

    $post->setTitle($title);
    $post->setCategory($category);
    $post->setContent($content);

    $entityManager->flush();

    $this->addFlash('success', 'Post updated successfully.');

    return $this->redirectToRoute('app_forum');
}

    #[Route('/forum/delete/{id}', name: 'app_forum_delete', methods: ['POST'])]
public function delete(int $id, PostRepository $postRepository, EntityManagerInterface $entityManager): Response
{
    $post = $postRepository->find($id);

    if (!$post) {
        $this->addFlash('error', 'Post not found.');
        return $this->redirectToRoute('app_forum');
    }

    $user = $this->getUser();

    if (!$user) {
        $this->addFlash('error', 'You must be logged in.');
        return $this->redirectToRoute('app_login');
    }

    if ($post->getAuthorId() !== $user->getId()) {
        $this->addFlash('error', 'You cannot delete this post.');
        return $this->redirectToRoute('app_forum');
    }

    $entityManager->remove($post);
    $entityManager->flush();

    $this->addFlash('success', 'Post deleted successfully.');

    return $this->redirectToRoute('app_forum');
}

    #[Route('/forum/comment/{id}', name: 'app_forum_comment_new', methods: ['POST'])]
    public function addComment(
        int $id,
        Request $request,
        PostRepository $postRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $post = $postRepository->find($id);

        if (!$post) {
            $this->addFlash('error', 'Post not found.');
            return $this->redirectToRoute('app_forum');
        }

        $content = trim($request->request->get('content', ''));

        if ($content === '') {
            $this->addFlash('error', 'Comment cannot be empty.');
            return $this->redirectToRoute('app_forum');
        }

        $comment = new Comment();
        $comment->setIdPost($post);
        $comment->setContent($content);
      $user = $this->getUser();

if (!$user) {
    $this->addFlash('error', 'You must be logged in.');
    return $this->redirectToRoute('app_login');
}

$comment->setAuthor($user->getFullName());
$comment->setAuthorId($user->getId());

        $comment->setLikes(0);
        $comment->setCreatedAt(new \DateTime());

        $entityManager->persist($comment);
        $entityManager->flush();

        $this->addFlash('success', 'Comment added successfully.');

        return $this->redirectToRoute('app_forum');
    }

#[Route('/forum/comment/delete/{id}', name: 'app_comment_delete', methods: ['POST'])]
public function deleteComment(
    int $id,
    CommentRepository $commentRepository,
    EntityManagerInterface $entityManager
): Response {
    $comment = $commentRepository->find($id);

    if (!$comment) {
        $this->addFlash('error', 'Comment not found.');
        return $this->redirectToRoute('app_forum');
    }

    $user = $this->getUser();

    if (!$user) {
        $this->addFlash('error', 'You must be logged in.');
        return $this->redirectToRoute('app_login');
    }

    if ($comment->getAuthorId() !== $user->getId()) {
        $this->addFlash('error', 'You cannot delete this comment.');
        return $this->redirectToRoute('app_forum');
    }

    $entityManager->remove($comment);
    $entityManager->flush();

    return $this->redirectToRoute('app_forum');
}

 #[Route('/forum/comment/update/{id}', name: 'app_comment_update', methods: ['POST'])]
public function updateComment(
    int $id,
    Request $request,
    CommentRepository $commentRepository,
    EntityManagerInterface $entityManager
): Response {
    $comment = $commentRepository->find($id);

    if (!$comment) {
        $this->addFlash('error', 'Comment not found.');
        return $this->redirectToRoute('app_forum');
    }

    $user = $this->getUser();

    if (!$user) {
        $this->addFlash('error', 'You must be logged in.');
        return $this->redirectToRoute('app_login');
    }

    if ($comment->getAuthorId() !== $user->getId()) {
        $this->addFlash('error', 'You cannot edit this comment.');
        return $this->redirectToRoute('app_forum');
    }

    $content = trim($request->request->get('content', ''));

    if ($content !== '') {
        $comment->setContent($content);
        $entityManager->flush();
    }

    return $this->redirectToRoute('app_forum');
}
}