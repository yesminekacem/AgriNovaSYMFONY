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
use App\Repository\UserRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\PostReaction;
use App\Repository\PostReactionRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Mercure\HubInterface;

final class ForumBackController extends AbstractController
{
#[Route('/forumBack', name: 'app_forum_back')]
public function index(
    Request $request,
    PostRepository $postRepository,
    CommentRepository $commentRepository,
    UserRepository $userRepository,
    PostReactionRepository $postReactionRepository,
    PaginatorInterface $paginator
): Response {
   $query = $postRepository->createQueryBuilder('p')
    ->andWhere('p.status = :status')
    ->setParameter('status', 'ACTIVE')
    ->orderBy('p.createdAt', 'DESC')
    ->getQuery();

$posts = $paginator->paginate(
    $query,
    $request->query->getInt('page', 1),
    3
);
    $commentsByPost = [];
    $postsData = [];
    $postProfileImages = [];

    foreach ($posts as $post) {
        $postUser = $userRepository->find($post->getAuthorId());

        $postProfileImages[$post->getIdPost()] =
            $postUser && $postUser->getProfileImage()
                ? 'uploads/profile_images/' . $postUser->getProfileImage()
                : null;

        $comments = $commentRepository->findByPost($post->getIdPost());
        $commentsByPost[$post->getIdPost()] = $comments;

        $commentsData = [];

        foreach ($comments as $comment) {
            $commentUser = $userRepository->find($comment->getAuthorId());

            $commentsData[] = [
                'id' => $comment->getIdComment(),
                'author' => $comment->getAuthor(),
                'authorId' => $comment->getAuthorId(),
                'content' => $comment->getContent(),
                'createdAt' => $this->timeAgo($comment->getCreatedAt()),
                'profileImage' => $commentUser && $commentUser->getProfileImage()
                    ? 'uploads/profile_images/' . $commentUser->getProfileImage()
                    : null,
                'hasBadWordMask' => preg_match('/\*{3,}/', $comment->getContent() ?? '') === 1,
            ];
        }
        $hasMaskedComment = false;

foreach ($commentsData as $commentData) {
    if (preg_match('/\*{3,}/', $commentData['content'] ?? '')) {
        $hasMaskedComment = true;
        break;
    }
}
$userReaction = null;

if ($this->getUser()) {
    $existingReaction = $postReactionRepository->findUserReaction($post, $this->getUser());
    $userReaction = $existingReaction ? $existingReaction->getReaction() : null;
}

$reactionCounts = $postReactionRepository->countGroupedByReaction($post);
$totalReactions = $postReactionRepository->countByPost($post);
        $postsData[$post->getIdPost()] = [
            'id' => $post->getIdPost(),
            'title' => $post->getTitle(),
            'reactionCounts' => $reactionCounts,
'totalReactions' => $totalReactions,
'userReaction' => $userReaction,
            'author' => $post->getAuthor(),
            'authorId' => $post->getAuthorId(),
            'category' => $post->getCategory() ?: 'Organic Farming',
            'content' => $post->getContent(),
            'createdAt' => $this->timeAgo($post->getCreatedAt()),
            'image' => $post->getImagePath()
                ? 'Front/images/Forum/' . $post->getImagePath()
                : null,
            'profileImage' => $postUser && $postUser->getProfileImage()
                ? 'uploads/profile_images/' . $postUser->getProfileImage()
                : null,
            'commentsCount' => count($comments),
            'hasMaskedComment' => $hasMaskedComment,
            'comments' => $commentsData,
        ];
    }

    return $this->render('Back/forumBack.html.twig', [
        'posts' => $posts,
        'commentsByPost' => $commentsByPost,
        'postsData' => $postsData,
        'postProfileImages' => $postProfileImages,
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
private function timeAgo(?\DateTimeInterface $date): string
{
    if (!$date) {
        return 'No date';
    }

    $now = new \DateTime();
    $diff = $now->diff($date);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';

    return 'Just now';
}
#[Route('/forumBack/react/{id}', name: 'app_forum_back_react', methods: ['POST'])]
public function react(
    int $id,
    Request $request,
    PostRepository $postRepository,
    PostReactionRepository $postReactionRepository,
    EntityManagerInterface $entityManager
): Response {
    $user = $this->getUser();

    if (!$user) {
        return $this->json(['success' => false], 403);
    }

    $post = $postRepository->find($id);

    if (!$post) {
        return $this->json(['success' => false], 404);
    }

    $reaction = strtoupper(trim($request->request->get('reaction', '')));
    $allowed = ['LIKE','LOVE','HAHA','WOW','SAD','ANGRY'];

    if (!in_array($reaction, $allowed)) {
        return $this->json(['success' => false], 400);
    }

    $existing = $postReactionRepository->findUserReaction($post, $user);

    if ($existing) {
        if ($existing->getReaction() === $reaction) {
            $entityManager->remove($existing);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'userReaction' => null,
                'totalCount' => $postReactionRepository->countByPost($post),
            ]);
        }

        $existing->setReaction($reaction);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'userReaction' => $reaction,
            'totalCount' => $postReactionRepository->countByPost($post),
        ]);
    }

    $new = new PostReaction();
    $new->setIdPost($post);
    $new->setUser($user);
    $new->setReaction($reaction);
    $new->setCreatedAt(new \DateTime());

    $entityManager->persist($new);
    $entityManager->flush();

    return $this->json([
        'success' => true,
        'userReaction' => $reaction,
        'totalCount' => $postReactionRepository->countByPost($post),
    ]);
}
#[Route('/forumBack/reactions/{id}', name: 'app_forum_back_reactions', methods: ['GET'])]
public function getPostReactionsBack(
    int $id,
    PostRepository $postRepository,
    PostReactionRepository $postReactionRepository
): Response {
    try {
        $post = $postRepository->find($id);

        if (!$post) {
            return $this->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        $reactions = $postReactionRepository->findBy(
            ['idPost' => $post],
            ['createdAt' => 'DESC']
        );

        $items = [];

        foreach ($reactions as $reaction) {
            $user = $reaction->getUser();

            $author = 'Unknown user';
            if ($user) {
                $author = $user->getFullName() ?: $user->getUsername();
            }

            $items[] = [
                'userId' => $user ? $user->getId() : null,
                'author' => $author,
                'profileImage' => ($user && $user->getProfileImage())
                    ? 'uploads/profile_images/' . $user->getProfileImage()
                    : null,
                'reaction' => $reaction->getReaction(),
            ];
        }

        return $this->json([
            'success' => true,
            'reactions' => $items
        ]);
    } catch (\Throwable $e) {
        return $this->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

#[Route('/forumBack/translate/{id}', name: 'app_forum_back_translate', methods: ['POST'])]
public function translatePostBack(
    int $id,
    Request $request,
    PostRepository $postRepository,
    HttpClientInterface $httpClient
): Response {
    try {
        $post = $postRepository->find($id);

        if (!$post) {
            return $this->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        $targetLanguage = trim((string) $request->request->get('language'));

        if (!in_array($targetLanguage, ['en', 'fr', 'ar'], true)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid language'
            ], 400);
        }

        $groqApiKey = $_ENV['GROQ_API_KEY'] ?? $_SERVER['GROQ_API_KEY'] ?? null;

        if (!$groqApiKey) {
            return $this->json([
                'success' => false,
                'message' => 'Groq API key is missing'
            ], 500);
        }

        $langMap = [
            'en' => 'English',
            'fr' => 'French',
            'ar' => 'Arabic',
        ];

        $targetLabel = $langMap[$targetLanguage];
        $text = $post->getContent();

        $prompt = "Translate the following forum post into {$targetLabel}.\n"
            . "Keep the exact meaning.\n"
            . "Do not summarize.\n"
            . "Do not add explanations.\n"
            . "Return only the translated text.\n\n"
            . "Text:\n{$text}";

        $response = $httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $groqApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'llama-3.3-70b-versatile',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a precise translation assistant.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.2,
            ],
        ]);

        $data = $response->toArray(false);

        $translated = $data['choices'][0]['message']['content'] ?? null;

        if (!$translated) {
            return $this->json([
                'success' => false,
                'message' => 'Groq returned no translation',
                'groq_response' => $data
            ], 500);
        }

        return $this->json([
            'success' => true,
            'translatedText' => trim($translated),
            'language' => $targetLanguage
        ]);
    } catch (\Throwable $e) {
        return $this->json([
            'success' => false,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
}
}