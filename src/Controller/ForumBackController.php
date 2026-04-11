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

use Symfony\Component\Validator\Validator\ValidatorInterface;


final class ForumBackController extends AbstractController
{
    #[Route('/forumBack', name: 'app_forum_back')]
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

        return $this->render('Back/forumBack.html.twig', [
            'posts' => $posts,
            'commentsByPost' => $commentsByPost,
            'postsData' => $postsData,
        ]);
    }

#[Route('/forumBack/new', name: 'app_forum_back_new', methods: ['POST'])]
public function new(
    Request $request,
    EntityManagerInterface $entityManager,
    ValidatorInterface $validator
): Response {
    $user = $this->getUser();

    if (!$user) {
        $this->addFlash('error', 'You must be logged in.');
        return $this->redirectToRoute('app_login');
    }

    $title = trim($request->request->get('title', ''));
    $category = trim($request->request->get('category', ''));
    $content = trim($request->request->get('content', ''));
    $imageFile = $request->files->get('image');

    $post = new Post();
    $post->setTitle($title);
    $post->setCategory($category);
    $post->setContent($content);
    $post->setStatus('ACTIVE');
    $post->setCreatedAt(new \DateTime());
    $post->setAuthor($user->getFullName());
    $post->setAuthorId($user->getId());
    $post->setImagePath(null);

    $errors = $validator->validate($post);

    if (count($errors) > 0) {
        foreach ($errors as $error) {
            $this->addFlash('error', $error->getMessage());
        }

        return $this->redirectToRoute('app_forum_back');
    }

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
            return $this->redirectToRoute('app_forum_back');
        }
    }

    $entityManager->persist($post);
    $entityManager->flush();

    $this->addFlash('success', 'Post created successfully.');
    return $this->redirectToRoute('app_forum_back');
}

#[Route('/forumBack/update/{id}', name: 'app_forum_back_update', methods: ['POST'])]
public function update(
    int $id,
    Request $request,
    PostRepository $postRepository,
    EntityManagerInterface $entityManager,
    ValidatorInterface $validator
): Response {
    $post = $postRepository->find($id);

    if (!$post) {
        $this->addFlash('error', 'Post not found.');
        return $this->redirectToRoute('app_forum_back');
    }

    $user = $this->getUser();

    if (!$user) {
        $this->addFlash('error', 'You must be logged in.');
        return $this->redirectToRoute('app_login');
    }

    if ($post->getAuthorId() !== $user->getId()) {
        $this->addFlash('error', 'You cannot edit this post.');
        return $this->redirectToRoute('app_forum_back');
    }

    $title = trim($request->request->get('title', ''));
    $category = trim($request->request->get('category', ''));
    $content = trim($request->request->get('content', ''));

    $post->setTitle($title);
    $post->setCategory($category);
    $post->setContent($content);

    $errors = $validator->validate($post);

    if (count($errors) > 0) {
        foreach ($errors as $error) {
            $this->addFlash('error', $error->getMessage());
        }

        return $this->redirectToRoute('app_forum_back');
    }

    $entityManager->flush();

    $this->addFlash('success', 'Post updated successfully.');
    return $this->redirectToRoute('app_forum_back');
}



    #[Route('/forumBack/delete/{id}', name: 'app_forum_back_delete', methods: ['POST'])]
public function delete(int $id, PostRepository $postRepository, EntityManagerInterface $entityManager): Response
{
    $post = $postRepository->find($id);

    if (!$post) {
        $this->addFlash('error', 'Post not found.');
        return $this->redirectToRoute('app_forum_back');
    }

    $user = $this->getUser();

    if (!$user) {
        $this->addFlash('error', 'You must be logged in.');
        return $this->redirectToRoute('app_login');
    }


    $entityManager->remove($post);
    $entityManager->flush();

    $this->addFlash('success', 'Post deleted successfully.');

    return $this->redirectToRoute('app_forum_back');
}

#[Route('/forumBack/comment/{id}', name: 'app_forum_back_comment_new', methods: ['POST'])]
public function addComment(
    int $id,
    Request $request,
    PostRepository $postRepository,
    EntityManagerInterface $entityManager,
    ValidatorInterface $validator
): Response {
    $post = $postRepository->find($id);

    if (!$post) {
        $this->addFlash('error', 'Post not found.');
        return $this->redirectToRoute('app_forum_back');
    }

    $user = $this->getUser();

    if (!$user) {
        $this->addFlash('error', 'You must be logged in.');
        return $this->redirectToRoute('app_login');
    }

    $content = trim($request->request->get('content', ''));

    $comment = new Comment();
    $comment->setIdPost($post);
    $comment->setContent($content);
    $comment->setAuthor($user->getFullName());
    $comment->setAuthorId($user->getId());
    $comment->setLikes(0);
    $comment->setCreatedAt(new \DateTime());

    $errors = $validator->validate($comment);

    if (count($errors) > 0) {
        foreach ($errors as $error) {
            $this->addFlash('error', $error->getMessage());
        }

        return $this->redirectToRoute('app_forum_back');
    }

    $entityManager->persist($comment);
    $entityManager->flush();

    $this->addFlash('success', 'Comment added successfully.');
    return $this->redirectToRoute('app_forum_back');
}

    #[Route('/forumBack/comment/delete/{id}', name: 'app_forum_back_comment_delete', methods: ['POST'])]
    public function deleteComment(
        int $id,
        CommentRepository $commentRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $comment = $commentRepository->find($id);

        if ($comment) {
            $entityManager->remove($comment);
            $entityManager->flush();
            $this->addFlash('success', 'Comment deleted.');
        }

        return $this->redirectToRoute('app_forum_back');
    }

  

 #[Route('/forumBack/comment/update/{id}', name: 'app_forum_back_comment_update', methods: ['POST'])]
public function updateComment(
    int $id,
    Request $request,
    CommentRepository $commentRepository,
    EntityManagerInterface $entityManager,
    ValidatorInterface $validator
): Response {
    $comment = $commentRepository->find($id);

    if (!$comment) {
        $this->addFlash('error', 'Comment not found.');
        return $this->redirectToRoute('app_forum_back');
    }

    $user = $this->getUser();

    if (!$user) {
        $this->addFlash('error', 'You must be logged in.');
        return $this->redirectToRoute('app_login');
    }

    if ($comment->getAuthorId() !== $user->getId()) {
        $this->addFlash('error', 'You cannot edit this comment.');
        return $this->redirectToRoute('app_forum_back');
    }

    $content = trim($request->request->get('content', ''));
    $comment->setContent($content);

    $errors = $validator->validate($comment);

    if (count($errors) > 0) {
        foreach ($errors as $error) {
            $this->addFlash('error', $error->getMessage());
        }

        return $this->redirectToRoute('app_forum_back');
    }

    $entityManager->flush();

    $this->addFlash('success', 'Comment updated successfully.');
    return $this->redirectToRoute('app_forum_back');
}
}