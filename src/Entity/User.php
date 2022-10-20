<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups("user:read")]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Groups("user:read")]
    private ?string $email = null;

    #[ORM\Column(type:Types::JSON)]
    private array $roles = [];

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups("user:read")]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    private $plainPassword = false;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Question::class)]
    private Collection $questions;

    #[ORM\Column]
    private ?bool $isVerified = null;

    #[ORM\Column(type:"string", length:255, nullable:true)]
    private $totpSecret;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\OneToMany(mappedBy: 'updatedBy', targetEntity: Question::class)]
    private Collection $updatedQuestion;

    public function __construct()
    {
        $this->updatedQuestion = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get the value of plainPassword
     */ 
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * Set the value of plainPassword
     *
     * @return  self
     */ 
    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getAvatarUri(int $size = 32): string
    {
        if (!$this->avatar) {
            return 'https://ui-avatars.com/api/?' . http_build_query([
                'name' => $this->getFirstName() ?: $this->getEmail(),
                'size' => $size,
                'background' => 'random',
            ]);
        }
        if (strpos($this->avatar, '/') !== false) {
            return $this->avatar;
        }
        return sprintf('/uploads/avatars/%s', $this->avatar);
    }

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setOwner($this);
        }

        return $this;
    }

    public function removeQuestion(Question $question): self
    {
        if ($this->questions->removeElement($question)) {
            // set the owning side to null (unless already changed)
            if ($question->getOwner() === $this) {
                $question->setOwner(null);
            }
        }

        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->getFirstName() ?: $this->getEmail();
    }

    public function __toString(): string
    {
        return $this->getDisplayName();
    }

    public function isIsVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return $this->totpSecret ? true : false;
    }
    public function getTotpAuthenticationUsername(): string
    {
        return $this->getUserIdentifier();
    }
    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        return new TotpConfiguration($this->totpSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6);
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }
    public function setTotpSecret(?string $totpSecret): self
    {
        $this->totpSecret = $totpSecret;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @return Collection<int, Question>
     */
    public function getUpdatedQuestion(): Collection
    {
        return $this->updatedQuestion;
    }

    public function addUpdatedQuestion(Question $updatedQuestion): self
    {
        if (!$this->updatedQuestion->contains($updatedQuestion)) {
            $this->updatedQuestion->add($updatedQuestion);
            $updatedQuestion->setUpdatedBy($this);
        }

        return $this;
    }

    public function removeUpdatedQuestion(Question $updatedQuestion): self
    {
        if ($this->updatedQuestion->removeElement($updatedQuestion)) {
            // set the owning side to null (unless already changed)
            if ($updatedQuestion->getUpdatedBy() === $this) {
                $updatedQuestion->setUpdatedBy(null);
            }
        }

        return $this;
    }
}
