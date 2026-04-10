<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Comment;

#[ORM\Entity]
class Post
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_post;

    #[ORM\Column(type: "string", length: 200)]
    private string $title;

    #[ORM\Column(type: "text")]
    private string $content;

    #[ORM\Column(type: "string", length: 120)]
    private string $author;

    #[ORM\Column(type: "string", length: 80)]
    private string $category;

    #[ORM\Column(type: "string")]
    private string $status;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: "integer")]
    private int $author_id;

    public function getId_post()
    {
        return $this->id_post;
    }

    public function setId_post($value)
    {
        $this->id_post = $value;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($value)
    {
        $this->title = $value;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($value)
    {
        $this->content = $value;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor($value)
    {
        $this->author = $value;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory($value)
    {
        $this->category = $value;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

    public function setCreated_at($value)
    {
        $this->created_at = $value;
    }

    public function getAuthor_id()
    {
        return $this->author_id;
    }

    public function setAuthor_id($value)
    {
        $this->author_id = $value;
    }

    #[ORM\OneToMany(mappedBy: "id_post", targetEntity: Comment::class)]
    private Collection $comments;

        public function getComments(): Collection
        {
            return $this->comments;
        }
    
        public function addComment(Comment $comment): self
        {
            if (!$this->comments->contains($comment)) {
                $this->comments[] = $comment;
                $comment->setId_post($this);
            }
    
            return $this;
        }
    
        public function removeComment(Comment $comment): self
        {
            if ($this->comments->removeElement($comment)) {
                // set the owning side to null (unless already changed)
                if ($comment->getId_post() === $this) {
                    $comment->setId_post(null);
                }
            }
    
            return $this;
        }
}
