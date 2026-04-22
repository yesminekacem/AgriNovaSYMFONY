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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;
use App\Entity\PostReaction;
use App\Entity\Notifications;
use App\Repository\PostReactionRepository;
use App\Service\GroqModerationService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Repository\NotificationsRepository;



final class ForumController extends AbstractController
{

#[Route('/forum', name: 'app_forum', methods: ['GET'])]
public function index(
    Request $request,
    PostRepository $postRepository,
    CommentRepository $commentRepository,
    UserRepository $userRepository,
    PostReactionRepository $postReactionRepository,
    PaginatorInterface $paginator,
     NotificationsRepository $notificationsRepository
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
            ];
        }

        $reactionCounts = $postReactionRepository->countGroupedByReaction($post);
        $totalReactions = $postReactionRepository->countByPost($post);

        $userReaction = null;
        if ($this->getUser()) {
            $existingReaction = $postReactionRepository->findUserReaction($post, $this->getUser());
            $userReaction = $existingReaction ? $existingReaction->getReaction() : null;
        }

        $postsData[$post->getIdPost()] = [
            'id' => $post->getIdPost(),
            'title' => $post->getTitle(),
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
            'comments' => $commentsData,
            'commentsCount' => count($comments),
            'reactionCounts' => $reactionCounts,
            'totalReactions' => $totalReactions,
            'userReaction' => $userReaction,
        ];
    }
    $user = $this->getUser();

$unreadCount = 0;

if ($user) {
    $unreadCount = count(
        $notificationsRepository->findUnreadByRecipient($user->getId())
    );
}

    return $this->render('Front/forum.html.twig', [
        'posts' => $posts,
        'postsData' => $postsData,
        'unreadCount' => $unreadCount,
        'postProfileImages' => $postProfileImages,
    ]);
}
#[Route('/forum/new', name: 'app_forum_new', methods: ['POST'])]
public function new(
    Request $request,
    EntityManagerInterface $entityManager,
    HubInterface $hub,
    UserRepository $userRepository
): Response {
    $title = trim($request->request->get('title', ''));
    $category = trim($request->request->get('category', ''));
    $content = trim($request->request->get('content', ''));

    if ($title === '' || $category === '' || $content === '') {
        $this->addFlash('error', 'Please fill in all required fields.');
        return $this->redirectToRoute('app_forum');
    }

    $user = $this->getUser();

    if (!$user) {
        $this->addFlash('error', 'You must be logged in.');
        return $this->redirectToRoute('app_login');
    }

    $post = new Post();
    $post->setTitle($title);
    $post->setCategory($category);
    $post->setContent($content);
    $post->setStatus('ACTIVE');
    $post->setCreatedAt(new \DateTime());
    $post->setAuthor($user->getFullName());
    $post->setAuthorId($user->getId());
    $post->setImagePath(null);

    $imageFile = $request->files->get('image');
    $generatedImage = $request->request->get('generatedImage');

    if ($generatedImage) {
        $post->setImagePath('uploads/generated/' . $generatedImage);

    } elseif ($imageFile) {
        $newFilename = uniqid() . '.' . $imageFile->guessExtension();

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/posts';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $imageFile->move($uploadDir, $newFilename);

        $post->setImagePath('uploads/posts/' . $newFilename);
    }

    $entityManager->persist($post);
    $entityManager->flush();

    $postUser = $userRepository->find($post->getAuthorId());

    $payload = [
        'type' => 'post_created',
        'post' => [
            'id' => $post->getIdPost(),
            'title' => $post->getTitle(),
            'author' => $post->getAuthor(),
            'authorId' => $post->getAuthorId(),
            'category' => $post->getCategory() ?: 'Organic Farming',
            'content' => $post->getContent(),
            'createdAt' => $this->timeAgo($post->getCreatedAt()),
            'image' => $post->getImagePath(),
            'profileImage' => $postUser && $postUser->getProfileImage()
                ? 'uploads/profile_images/' . $postUser->getProfileImage()
                : null,
            'comments' => [],
            'commentsCount' => 0,
            'reactionCounts' => [],
            'totalReactions' => 0,
            'userReaction' => null,
        ]
    ];

    try {
        $hub->publish(new Update(
            'http://127.0.0.1/forum/posts',
            json_encode($payload)
        ));
    } catch (\Throwable $e) {}

    if ($request->isXmlHttpRequest()) {
        return $this->json([
            'success' => true
        ]);
    }

    $this->addFlash('success', 'Post created successfully.');

    return $this->redirectToRoute('app_forum');
}
#[Route('/forum/update/{id}', name: 'app_forum_update', methods: ['POST'])]
public function update(
    int $id,
    Request $request,
    PostRepository $postRepository,
    EntityManagerInterface $entityManager,
    HubInterface $hub,
    UserRepository $userRepository,
    CommentRepository $commentRepository,
    PostReactionRepository $postReactionRepository
): Response {
    $post = $postRepository->find($id);

    if (!$post) {
        return $this->json([
            'success' => false,
            'message' => 'Post not found'
        ], 404);
    }

    if (!$this->getUser() || $post->getAuthorId() !== $this->getUser()->getId()) {
        return $this->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

    $title = trim((string) $request->request->get('title'));
    $category = trim((string) $request->request->get('category'));
    $content = trim((string) $request->request->get('content'));

    if (!$title || !$category || !$content) {
        return $this->json([
            'success' => false,
            'message' => 'All fields are required'
        ], 400);
    }

    $post->setTitle($title);
    $post->setCategory($category);
    $post->setContent($content);

    $entityManager->flush();

    $postUser = $userRepository->find($post->getAuthorId());
    $comments = $commentRepository->findByPost($post->getIdPost());
    $totalReactions = $postReactionRepository->countByPost($post);

    $userReaction = null;
    if ($this->getUser()) {
        $existingReaction = $postReactionRepository->findUserReaction($post, $this->getUser());
        $userReaction = $existingReaction ? $existingReaction->getReaction() : null;
    }

    $updatedPostData = [
        'id' => $post->getIdPost(),
        'title' => $post->getTitle(),
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
        'totalReactions' => $totalReactions,
        'userReaction' => $userReaction,
    ];

    $payload = [
        'type' => 'post_updated',
        'post' => $updatedPostData
    ];

    $hub->publish(new Update(
        sprintf('http://127.0.0.1/forum/posts/%d', $post->getIdPost()),
        json_encode($payload)
    ));

    $hub->publish(new Update(
        'http://127.0.0.1/forum/posts',
        json_encode($payload)
    ));

    if ($request->isXmlHttpRequest()) {
        return $this->json([
            'success' => true,
            'post' => $updatedPostData
        ]);
    }

    return $this->redirectToRoute('app_forum_index');
}

#[Route('/forum/delete/{id}', name: 'app_forum_delete', methods: ['POST'])]
public function delete(
    int $id,
    Request $request,
    PostRepository $postRepository,
    EntityManagerInterface $entityManager,
    HubInterface $hub
): Response {
    $post = $postRepository->find($id);

    if (!$post) {
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => false,
                'message' => 'Post not found.'
            ], 404);
        }

        $this->addFlash('error', 'Post not found.');
        return $this->redirectToRoute('app_forum');
    }

    $postId = $post->getIdPost();

    $entityManager->remove($post);
    $entityManager->flush();

    $payload = [
        'type' => 'post_deleted',
        'postId' => $postId,
    ];

    try {
        $hub->publish(new Update(
            'http://127.0.0.1/forum/posts',
            json_encode($payload)
        ));
    } catch (\Throwable $e) {
    }

    if ($request->isXmlHttpRequest()) {
        return $this->json([
            'success' => true
        ]);
    }

    $this->addFlash('success', 'Post deleted successfully.');
    return $this->redirectToRoute('app_forum');
}
 #[Route('/forum/comment/{id}', name: 'app_forum_comment_new', methods: ['POST'])]
public function addComment(
    int $id,
    Request $request,
    PostRepository $postRepository,
    CommentRepository $commentRepository,
    NotificationsRepository $notificationsRepository,
    EntityManagerInterface $entityManager,
    GroqModerationService $groqModerationService,
    HubInterface $hub
): Response {
    $post = $postRepository->find($id);

    if (!$post) {
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => false,
                'message' => 'Post not found.'
            ], 404);
        }

        $this->addFlash('error', 'Post not found.');
        return $this->redirectToRoute('app_forum');
    }

    $content = trim($request->request->get('content', ''));

    if ($content === '') {
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => false,
                'message' => 'Comment cannot be empty.'
            ], 400);
        }

        $this->addFlash('error', 'Comment cannot be empty.');
        return $this->redirectToRoute('app_forum');
    }

    $moderation = $groqModerationService->moderate($content);

    if (!$moderation['safe']) {
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => false,
                'message' => 'Your comment could not be published because it may contain harmful language.'
            ], 400);
        }

        $this->addFlash('error', 'Your comment could not be published because it may contain harmful language.');
        return $this->redirectToRoute('app_forum');
    }

    $user = $this->getUser();

    if (!$user) {
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => false,
                'message' => 'You must be logged in.'
            ], 403);
        }

        $this->addFlash('error', 'You must be logged in.');
        return $this->redirectToRoute('app_login');
    }

    $comment = new Comment();
    $comment->setIdPost($post);

    $cleanContent = $this->censorText($content);
    $comment->setContent($cleanContent);

    $comment->setAuthor($user->getFullName());
    $comment->setAuthorId($user->getId());
    $comment->setLikes(0);
    $comment->setCreatedAt(new \DateTime());

    $entityManager->persist($comment);

// create notification only if commenter is not the post owner
if ($post->getAuthorId() !== $user->getId()) {
    $notification = new Notifications();
    $notification->setRecipientId($post->getAuthorId());
    $notification->setActorId($user->getId());
    $notification->setPostId($post->getIdPost());
    $notification->setType('comment');
    $notification->setMessage($user->getFullName() . ' commented on your post');
    $notification->setIsRead(false);
    $notification->setCreatedAt(new \DateTime());

    $entityManager->persist($notification);
}

$entityManager->flush();

    $commentsCount = count($commentRepository->findByPost($post->getIdPost()));

    $payload = [
        'type' => 'comment_created',
        'postId' => $post->getIdPost(),
        'comment' => [
            'id' => $comment->getIdComment(),
            'author' => $comment->getAuthor(),
            'authorId' => $comment->getAuthorId(),
            'content' => $comment->getContent(),
            'createdAt' => $this->timeAgo($comment->getCreatedAt()),
            'profileImage' => $user->getProfileImage()
                ? 'uploads/profile_images/' . $user->getProfileImage()
                : null,
        ],
        'commentsCount' => $commentsCount,
    ];

    try {
        $hub->publish(new Update(
            sprintf('http://127.0.0.1/forum/posts/%d', $post->getIdPost()),
            json_encode($payload)
        ));
    } catch (\Throwable $e) {
        // do not crash the whole comment system if Mercure fails
    }

    if ($request->isXmlHttpRequest()) {
        return $this->json([
            'success' => true
        ]);
    }

    $this->addFlash('success', 'Comment added successfully.');

    return $this->redirectToRoute('app_forum');
}
#[Route('/forum/comment/delete/{id}', name: 'app_forum_comment_delete', methods: ['POST'])]
public function deleteComment(
    int $id,
    CommentRepository $commentRepository,
    EntityManagerInterface $entityManager,
    HubInterface $hub,
    Request $request
): Response {
    $comment = $commentRepository->find($id);

    if (!$comment) {
        return $this->json([
            'success' => false,
            'message' => 'Comment not found.'
        ], 404);
    }

    $post = $comment->getIdPost();

    $entityManager->remove($comment);
    $entityManager->flush();

    $commentsCount = count($commentRepository->findByPost($post->getIdPost()));

    $payload = [
        'type' => 'comment_deleted',
        'postId' => $post->getIdPost(),
        'commentId' => $id,
        'commentsCount' => $commentsCount,
    ];

    try {
        $hub->publish(new Update(
            sprintf('http://127.0.0.1/forum/posts/%d', $post->getIdPost()),
            json_encode($payload)
        ));
    } catch (\Throwable $e) {
        // do nothing, don't crash
    }

    if ($request->isXmlHttpRequest()) {
        return $this->json([
            'success' => true
        ]);
    }

    return $this->redirectToRoute('app_forum');
}

 #[Route('/forum/comment/update/{id}', name: 'app_forum_comment_update', methods: ['POST'])]
public function updateComment(
    int $id,
    Request $request,
    CommentRepository $commentRepository,
    EntityManagerInterface $entityManager,
    HubInterface $hub
): Response {
    $comment = $commentRepository->find($id);

    if (!$comment) {
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => false,
                'message' => 'Comment not found.'
            ], 404);
        }

        return $this->redirectToRoute('app_forum');
    }

    $content = trim($request->request->get('content', ''));

    if ($content === '') {
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => false,
                'message' => 'Comment cannot be empty.'
            ], 400);
        }

        return $this->redirectToRoute('app_forum');
    }

    $comment->setContent($content);
    $entityManager->flush();

    $post = $comment->getIdPost();

    $payload = [
        'type' => 'comment_updated',
        'postId' => $post->getIdPost(),
        'comment' => [
            'id' => $comment->getIdComment(),
            'author' => $comment->getAuthor(),
            'authorId' => $comment->getAuthorId(),
            'content' => $comment->getContent(),
            'createdAt' => $this->timeAgo($comment->getCreatedAt()),
        ]
    ];

    try {
        $hub->publish(new Update(
            sprintf('http://127.0.0.1/forum/posts/%d', $post->getIdPost()),
            json_encode($payload)
        ));
    } catch (\Throwable $e) {
    }

    if ($request->isXmlHttpRequest()) {
        return $this->json([
            'success' => true
        ]);
    }

    return $this->redirectToRoute('app_forum');
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
#[Route('/forum/react/{id}', name: 'app_forum_react', methods: ['POST'])]
public function react(
    int $id,
    Request $request,
    PostRepository $postRepository,
    PostReactionRepository $postReactionRepository,
    EntityManagerInterface $entityManager,
    HubInterface $hub
): JsonResponse {
    $user = $this->getUser();

    if (!$user) {
        return $this->json([
            'success' => false,
            'message' => 'You must be logged in.'
        ], 403);
    }

    $post = $postRepository->find($id);

    if (!$post) {
        return $this->json([
            'success' => false,
            'message' => 'Post not found.'
        ], 404);
    }

    $reaction = strtoupper(trim($request->request->get('reaction', '')));
    $allowedReactions = ['LIKE', 'LOVE', 'HAHA', 'WOW', 'SAD', 'ANGRY'];

    if (!in_array($reaction, $allowedReactions, true)) {
        return $this->json([
            'success' => false,
            'message' => 'Invalid reaction.'
        ], 400);
    }

    $existingReaction = $postReactionRepository->findUserReaction($post, $user);

    if ($existingReaction && $existingReaction->getReaction() === $reaction) {
        $entityManager->remove($existingReaction);
        $entityManager->flush();
        $payload = [
    'type' => 'reaction_updated',
    'postId' => $post->getIdPost(),
    'userReaction' => null,
    'totalCount' => $postReactionRepository->countByPost($post),
    'counts' => $postReactionRepository->countGroupedByReaction($post),
];

try {
    $hub->publish(new Update(
        sprintf('http://127.0.0.1/forum/posts/%d', $post->getIdPost()),
        json_encode($payload)
    ));
} catch (\Throwable $e) {
}

        return $this->json([
            'success' => true,
            'removed' => true,
            'userReaction' => null,
            'totalCount' => $postReactionRepository->countByPost($post),
            'counts' => $postReactionRepository->countGroupedByReaction($post),
        ]);
    }

    if ($existingReaction) {
        $existingReaction->setReaction($reaction);
        $entityManager->flush();
        $payload = [
    'type' => 'reaction_updated',
    'postId' => $post->getIdPost(),
    'userReaction' => $reaction,
    'totalCount' => $postReactionRepository->countByPost($post),
    'counts' => $postReactionRepository->countGroupedByReaction($post),
];

try {
    $hub->publish(new Update(
        sprintf('http://127.0.0.1/forum/posts/%d', $post->getIdPost()),
        json_encode($payload)
    ));
} catch (\Throwable $e) {
}

        return $this->json([
            'success' => true,
            'removed' => false,
            'userReaction' => $reaction,
            'totalCount' => $postReactionRepository->countByPost($post),
            'counts' => $postReactionRepository->countGroupedByReaction($post),
        ]);
    }

    $newReaction = new PostReaction();
    $newReaction->setIdPost($post);
    $newReaction->setUser($user);
    $newReaction->setReaction($reaction);
    $newReaction->setCreatedAt(new \DateTime());

    $entityManager->persist($newReaction);
    $postOwnerId = $post->getAuthorId();
$actorId = $this->getUser()->getId();

// ❌ Do not notify yourself
if ($postOwnerId !== $actorId) {

    $notification = new Notifications();
    $notification->setRecipientId($postOwnerId);
    $notification->setActorId($actorId);
    $notification->setPostId($post->getIdPost());
    $notification->setType('reaction');
    $notification->setMessage($this->getUser()->getFullName() . ' reacted to your post.');
    $notification->setIsRead(false);
    $notification->setCreatedAt(new \DateTime());

    $entityManager->persist($notification);
}
    $entityManager->flush();
    $payload = [
    'type' => 'reaction_updated',
    'postId' => $post->getIdPost(),
    'userReaction' => $reaction,
    'totalCount' => $postReactionRepository->countByPost($post),
    'counts' => $postReactionRepository->countGroupedByReaction($post),
];

try {
    $hub->publish(new Update(
        sprintf('http://127.0.0.1/forum/posts/%d', $post->getIdPost()),
        json_encode($payload)
    ));
} catch (\Throwable $e) {
}

    return $this->json([
        'success' => true,
        'removed' => false,
        'userReaction' => $reaction,
        'totalCount' => $postReactionRepository->countByPost($post),
        'counts' => $postReactionRepository->countGroupedByReaction($post),
    ]);
}
private function censorText(string $text): string
{
    $badWords = ['fuck', 'shit', 'stupid', 'idiot'];

    foreach ($badWords as $word) {
        $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
        $text = preg_replace($pattern, str_repeat('*', strlen($word)), $text);
    }

    return $text;
}
#[Route('/forum/reactions/{id}', name: 'app_forum_reactions', methods: ['GET'])]
public function getPostReactions(
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


#[Route('/forum/translate/{id}', name: 'app_forum_translate', methods: ['POST'])]
public function translatePost(
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
                'message' => 'Groq API key is missing.'
            ], 500);
        }

        $langMap = [
            'en' => 'English',
            'fr' => 'French',
            'ar' => 'Arabic',
        ];

        $targetLabel = $langMap[$targetLanguage];
        $textToTranslate = $post->getContent();

        $prompt = "Translate the following forum post into {$targetLabel}.\n"
            . "Keep the exact meaning.\n"
            . "Do not summarize.\n"
            . "Do not add explanations.\n"
            . "Return only the translated text.\n\n"
            . "Text:\n{$textToTranslate}";

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
        
        


        $translatedText = $data['choices'][0]['message']['content'] ?? null;

        if (!$translatedText) {
            return $this->json([
                'success' => false,
                'message' => 'Translation failed.'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'translatedText' => trim($translatedText),
            'language' => $targetLanguage
        ]);
    } catch (\Throwable $e) {
        return $this->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

#[Route('/forum/crop-advice', name: 'app_forum_crop_advice', methods: ['POST'])]
public function cropAdvice(
    Request $request,
    HttpClientInterface $httpClient
): Response {
    try {
        $question = trim((string) $request->request->get('question'));

        if (!$question) {
            return $this->json([
                'success' => false,
                'message' => 'Question is required.'
            ], 400);
        }

        $groqApiKey = $_ENV['GROQ_API_KEY'] ?? $_SERVER['GROQ_API_KEY'] ?? null;

        if (!$groqApiKey) {
            return $this->json([
                'success' => false,
                'message' => 'Groq API key is missing.'
            ], 500);
        }

        $prompt = "You are an agriculture advisor for farmers.\n"
            . "Answer clearly and simply.\n"
            . "The user will describe soil, weather, or crop problems.\n"
            . "Give:\n"
            . "1. Recommended crops or actions\n"
            . "2. Short explanation why\n"
            . "3. 3 practical tips\n"
            . "Keep the answer concise.\n"
            . "Do not use markdown tables.\n"
            . "Do not invent certainty if information is limited.\n"
            . "User question:\n{$question}";

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
                        'content' => 'You are a helpful crop recommendation assistant.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.4,
            ],
        ]);

        $data = $response->toArray(false);
        $advice = $data['choices'][0]['message']['content'] ?? null;

        if (!$advice) {
            return $this->json([
                'success' => false,
                'message' => 'No advice returned.'
            ], 500);
        }

        return $this->json([
            'success' => true,
            'advice' => trim($advice)
        ]);
    } catch (\Throwable $e) {
        return $this->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
private function createNotification(
    EntityManagerInterface $entityManager,
    int $recipientId,
    int $actorId,
    int $postId,
    string $type,
    string $message
): void {
    if ($recipientId === $actorId) {
        return;
    }

    $notification = new Notifications();
    $notification->setRecipientId($recipientId);
    $notification->setActorId($actorId);
    $notification->setPostId($postId);
    $notification->setType($type);
    $notification->setMessage($message);
    $notification->setIsRead(false);
    $notification->setCreatedAt(new \DateTime());

    $entityManager->persist($notification);
}
#[Route('/notifications/json', name: 'app_notifications_json', methods: ['GET'])]
public function getNotifications(
    NotificationsRepository $repo,
    UserRepository $userRepository
): Response {
    $user = $this->getUser();

    if (!$user) {
        return $this->json(['success' => false], 403);
    }

    $notifications = $repo->findBy(
        ['recipientId' => $user->getId()],
        ['createdAt' => 'DESC'],
        30
    );

    $grouped = [];

    foreach ($notifications as $n) {
        $key = $n->getType() . '_' . $n->getPostId();

        $actor = $userRepository->find($n->getActorId());
        $actorName = $actor ? $actor->getFullName() : 'Someone';

        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'type' => $n->getType(),
                'postId' => $n->getPostId(),
                'actors' => [],
                'ids' => [],
                'createdAt' => $n->getCreatedAt(),
                'isRead' => true,
            ];
        }

        $grouped[$key]['actors'][] = $actorName;
        $grouped[$key]['ids'][] = $n->getId();

        if ($n->getCreatedAt() > $grouped[$key]['createdAt']) {
            $grouped[$key]['createdAt'] = $n->getCreatedAt();
        }

        if (!$n->getIsRead()) {
            $grouped[$key]['isRead'] = false;
        }
    }

    $data = [];

    foreach ($grouped as $item) {
        $actors = array_values(array_unique($item['actors']));
        $count = count($actors);

        if ($item['type'] === 'comment') {
            if ($count === 1) {
                $message = $actors[0] . ' commented on your post';
            } elseif ($count === 2) {
                $message = $actors[0] . ' and ' . $actors[1] . ' commented on your post';
            } else {
                $message = $actors[0] . ' and ' . ($count - 1) . ' others commented on your post';
            }
        } elseif ($item['type'] === 'reaction') {
            if ($count === 1) {
                $message = $actors[0] . ' reacted to your post';
            } elseif ($count === 2) {
                $message = $actors[0] . ' and ' . $actors[1] . ' reacted to your post';
            } else {
                $message = $actors[0] . ' and ' . ($count - 1) . ' others reacted to your post';
            }
        } else {
            $message = 'New activity on your post';
        }

        $data[] = [
            'type' => $item['type'],
            'postId' => $item['postId'],
            'ids' => $item['ids'],
            'message' => $message,
            'isRead' => $item['isRead'],
            'createdAt' => $item['createdAt']->format('Y-m-d H:i'),
        ];
    }

    usort($data, function ($a, $b) {
        return strtotime($b['createdAt']) <=> strtotime($a['createdAt']);
    });

    return $this->json([
        'success' => true,
        'notifications' => array_slice($data, 0, 10)
    ]);
}
#[Route('/notifications/read-group', name: 'app_notifications_read_group', methods: ['POST'])]
public function readNotificationGroup(
    Request $request,
    NotificationsRepository $repo,
    EntityManagerInterface $entityManager
): Response {
    $user = $this->getUser();

    if (!$user) {
        return $this->json(['success' => false], 403);
    }

    $payload = json_decode($request->getContent(), true);
    $ids = $payload['ids'] ?? [];

    foreach ($ids as $id) {
        $notification = $repo->find($id);

        if ($notification && $notification->getRecipientId() === $user->getId()) {
            $notification->setIsRead(true);
        }
    }

    $entityManager->flush();

    return $this->json(['success' => true]);
}

#[Route('/forum/suggest-title', name: 'app_forum_suggest_title', methods: ['POST'])]
public function suggestTitle(Request $request, HttpClientInterface $httpClient): JsonResponse
{
    $content = trim($request->request->get('content', ''));

    if ($content === '') {
        return $this->json([
            'success' => false,
            'message' => 'Content is empty'
        ], 400);
    }

    $apiKey = $_ENV['GROQ_API_KEY'] ?? $_SERVER['GROQ_API_KEY'] ?? null;

    if (!$apiKey) {
        return $this->json([
            'success' => false,
            'message' => 'Missing GROQ_API_KEY'
        ], 500);
    }

    $prompt = <<<PROMPT
Generate ONE short, clear forum title based on this content.

Rules:
- Maximum 12 words
- No quotation marks
- No prefix like "Suggested title:"
- No explanation
- Natural and specific
- Return only the title text

Content:
{$content}
PROMPT;

    try {
        $response = $httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                "model" => "llama-3.3-70b-versatile",
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.5,
            ],
        ]);

        $statusCode = $response->getStatusCode();
        $data = $response->toArray(false);

        if ($statusCode >= 400) {
            return $this->json([
                'success' => false,
                'message' => $data['error']['message'] ?? 'Groq request failed',
                'debug' => $data,
            ], $statusCode);
        }

        $title = trim($data['choices'][0]['message']['content'] ?? '');

        return $this->json([
            'success' => true,
            'title' => $title,
        ]);
    } catch (\Throwable $e) {
        return $this->json([
            'success' => false,
            'message' => 'AI error: ' . $e->getMessage(),
        ], 500);
    }
}
#[Route('/forum/fix-grammar', name: 'app_forum_fix_grammar', methods: ['POST'])]
public function fixGrammar(Request $request, HttpClientInterface $httpClient): JsonResponse
{
    $content = trim($request->request->get('content', ''));

    if (!$content) {
        return $this->json([
            'success' => false,
            'message' => 'Content is empty'
        ]);
    }

    $apiKey = $_ENV['GROQ_API_KEY'];

    $prompt = <<<PROMPT
Correct the grammar, spelling, and clarity of this text.

Rules:
- Keep the original meaning
- Do not shorten too much
- Do not add new information
- Return ONLY the corrected text
- No explanations

Text:
{$content}
PROMPT;

    try {
        $response = $httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'llama-3.3-70b-versatile',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
            ],
        ]);

        $data = $response->toArray(false);

        if ($response->getStatusCode() >= 400) {
            return $this->json([
                'success' => false,
                'message' => $data['error']['message'] ?? 'AI error'
            ]);
        }

        $corrected = trim($data['choices'][0]['message']['content'] ?? '');

        return $this->json([
            'success' => true,
            'content' => $corrected
        ]);

    } catch (\Throwable $e) {
        return $this->json([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

#[Route('/forum/generate-image', name: 'forum_generate_image', methods: ['POST'])]
public function generateImage(Request $request): JsonResponse
{
    $content = trim($request->request->get('content', ''));
    $title = trim($request->request->get('title', ''));

    if (!$content) {
        return $this->json([
            'success' => false,
            'message' => 'Content is required'
        ]);
    }

    // 🧠 Build prompt
    $prompt = urlencode("agriculture, farming, " . $title . " " . $content);

    // 🌍 Free AI image URL
    $imageUrl = "https://image.pollinations.ai/prompt/" . $prompt;

    // 📁 Save locally
    $filename = 'ai_' . uniqid() . '.png';
    $targetDir = $this->getParameter('kernel.project_dir') . '/public/uploads/generated/';
    $targetPath = $targetDir . $filename;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $imageBytes = file_get_contents($imageUrl);

    if (!$imageBytes) {
        return $this->json([
            'success' => false,
            'message' => 'Failed to generate image'
        ]);
    }

    file_put_contents($targetPath, $imageBytes);

    return $this->json([
        'success' => true,
        'path' => 'uploads/generated/' . $filename,
        'filename' => $filename
    ]);
}
}