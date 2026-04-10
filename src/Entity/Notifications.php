<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Notifications
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $recipient_id;

    #[ORM\Column(type: "integer")]
    private int $actor_id;

    #[ORM\Column(type: "integer")]
    private int $post_id;

    #[ORM\Column(type: "string", length: 20)]
    private string $type;

    #[ORM\Column(type: "string", length: 255)]
    private string $message;

    #[ORM\Column(type: "boolean")]
    private bool $is_read;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getRecipient_id()
    {
        return $this->recipient_id;
    }

    public function setRecipient_id($value)
    {
        $this->recipient_id = $value;
    }

    public function getActor_id()
    {
        return $this->actor_id;
    }

    public function setActor_id($value)
    {
        $this->actor_id = $value;
    }

    public function getPost_id()
    {
        return $this->post_id;
    }

    public function setPost_id($value)
    {
        $this->post_id = $value;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($value)
    {
        $this->type = $value;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($value)
    {
        $this->message = $value;
    }

    public function getIs_read()
    {
        return $this->is_read;
    }

    public function setIs_read($value)
    {
        $this->is_read = $value;
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

    public function setCreated_at($value)
    {
        $this->created_at = $value;
    }
}
