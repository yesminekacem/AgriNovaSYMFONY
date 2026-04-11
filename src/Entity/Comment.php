<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\Table(name: 'comment')]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $idComment = null;

    #[ORM\ManyToOne(targetEntity: Post::class)]
    #[ORM\JoinColumn(name: 'id_post', referencedColumnName: 'id_post', nullable: false)]
    #[Assert\NotNull(message: 'Post is required')]
    private ?Post $idPost = null;

    #[ORM\Column(type: 'text', length: 65535)]
    #[Assert\NotBlank(message: 'Comment content is required')]
    #[Assert\Length(
        min: 2,
        minMessage: 'Comment must be at least {{ limit }} characters long'
    )]
    private string $content;

    #[ORM\Column(type: 'string', length: 120)]
    #[Assert\NotBlank(message: 'Author is required')]
    #[Assert\Length(
        min: 2,
        minMessage: 'Author name must be at least {{ limit }} characters long',
        max: 120,
        maxMessage: 'Author name cannot be longer than {{ limit }} characters'
    )]
    private string $author;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\PositiveOrZero(message: 'Likes must be zero or a positive number')]
    private ?int $likes = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull(message: 'Author ID is required')]
    #[Assert\Positive(message: 'Author ID must be a positive number')]
    private int $authorId;

    public function getIdComment(): ?int
    {
        return $this->idComment;
    }

    public function getIdPost(): ?Post
    {
        return $this->idPost;
    }

    public function setIdPost(?Post $idPost): self
    {
        $this->idPost = $idPost;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function getLikes(): ?int
    {
        return $this->likes;
    }

    public function setLikes(?int $likes): self
    {
        $this->likes = $likes;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getAuthorId(): int
    {
        return $this->authorId;
    }

    public function setAuthorId(int $authorId): self
    {
        $this->authorId = $authorId;
        return $this;
    }
}