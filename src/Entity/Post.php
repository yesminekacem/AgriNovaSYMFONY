<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'post')]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $idPost = null;

    #[ORM\Column(type: 'string', length: 200)]
    #[Assert\NotBlank(message: 'Title is required')]
    #[Assert\Length(
        min: 3,
        minMessage: 'Title must be at least {{ limit }} characters long',
        max: 200,
        maxMessage: 'Title cannot be longer than {{ limit }} characters'
    )]
    private string $title;

    #[ORM\Column(type: 'text', length: 65535)]
    #[Assert\NotBlank(message: 'Content is required')]
    #[Assert\Length(
        min: 10,
        minMessage: 'Content must be at least {{ limit }} characters long'
    )]
    private string $content;

    #[ORM\Column(type: 'string', nullable: true, length: 255)]
    private ?string $imagePath = null;

    #[ORM\Column(type: 'string', length: 120)]
    #[Assert\NotBlank(message: 'Author is required')]
    #[Assert\Length(
        min: 2,
        minMessage: 'Author name must be at least {{ limit }} characters long',
        max: 120,
        maxMessage: 'Author name cannot be longer than {{ limit }} characters'
    )]
    private string $author;

    #[ORM\Column(type: 'string', nullable: true, length: 80)]
    #[Assert\Length(
        max: 80,
        maxMessage: 'Category cannot be longer than {{ limit }} characters'
    )]
    private ?string $category = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull(message: 'Author ID is required')]
    #[Assert\Positive(message: 'Author ID must be a positive number')]
    private int $authorId;

    public function getIdPost(): ?int
    {
        return $this->idPost;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
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

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): self
    {
        $this->imagePath = $imagePath;
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

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
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