<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class User
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 100)]
    private string $full_name;

    #[ORM\Column(type: "string", length: 255)]
    private string $email;

    #[ORM\Column(type: "string", length: 255)]
    private string $password;

    #[ORM\Column(type: "string", length: 100)]
    private string $role;

    #[ORM\Column(type: "string", length: 255)]
    private string $profile_image;

    #[ORM\Column(type: "boolean")]
    private bool $email_verified;

    #[ORM\Column(type: "text")]
    private string $face_data;

    #[ORM\Column(type: "boolean")]
    private bool $banned;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getFull_name()
    {
        return $this->full_name;
    }

    public function setFull_name($value)
    {
        $this->full_name = $value;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($value)
    {
        $this->email = $value;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($value)
    {
        $this->password = $value;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setRole($value)
    {
        $this->role = $value;
    }

    public function getProfile_image()
    {
        return $this->profile_image;
    }

    public function setProfile_image($value)
    {
        $this->profile_image = $value;
    }

    public function getEmail_verified()
    {
        return $this->email_verified;
    }

    public function setEmail_verified($value)
    {
        $this->email_verified = $value;
    }

    public function getFace_data()
    {
        return $this->face_data;
    }

    public function setFace_data($value)
    {
        $this->face_data = $value;
    }

    public function getBanned()
    {
        return $this->banned;
    }

    public function setBanned($value)
    {
        $this->banned = $value;
    }
}
