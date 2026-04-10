<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Post;

#[ORM\Entity]
class Comment
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_comment;

        #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: "comments")]
    #[ORM\JoinColumn(name: 'id_post', referencedColumnName: 'id_post', onDelete: 'CASCADE')]
    private Post $id_post;

    #[ORM\Column(type: "text")]
    private string $content;

    #[ORM\Column(type: "string", length: 120)]
    private string $author;

    #[ORM\Column(type: "integer")]
    private int $likes;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: "integer")]
    private int $author_id;

    public function getId_comment()
    {
        return $this->id_comment;
    }

    public function setId_comment($value)
    {
        $this->id_comment = $value;
    }

    public function getId_post()
    {
        return $this->id_post;
    }

    public function setId_post($value)
    {
        $this->id_post = $value;
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

    public function getLikes()
    {
        return $this->likes;
    }

    public function setLikes($value)
    {
        $this->likes = $value;
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
}
