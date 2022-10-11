<?php

namespace App\Entity;

use App\Repository\QuestionTagRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionTagRepository::class)]
class QuestionTag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity:Question::class,inversedBy: 'questionTags')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Question $question = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tag $tag = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $taggedAt;

    public function __construct()
    {
        $this->taggedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    public function setTag(?Tag $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    public function getTaggedAt(): ?\DateTime
    {
        return $this->taggedAt;
    }

    public function setTaggedAt(\DateTime $taggedAt): self
    {
        $this->taggedAt = $taggedAt;

        return $this;
    }
}
