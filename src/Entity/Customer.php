<?php

namespace App\Entity;

use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['getCustomers', 'getConsumers'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getCustomers', 'getConsumers'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getCustomers', 'getConsumers'])]
    private ?string $email = null;

    #[ORM\Column]
    #[Groups(['getCustomers', 'getConsumers'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Consumer::class)]
    #[Groups(['getCustomers'])]
    private Collection $consumers;

    public function __construct()
    {
        $this->consumers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, Consumer>
     */
    public function getConsumers(): Collection
    {
        return $this->consumers;
    }

    public function addConsumer(Consumer $consumer): static
    {
        if (!$this->consumers->contains($consumer)) {
            $this->consumers->add($consumer);
            $consumer->setCustomer($this);
        }

        return $this;
    }

    public function removeConsumer(Consumer $consumer): static
    {
        if ($this->consumers->removeElement($consumer)) {
            // set the owning side to null (unless already changed)
            if ($consumer->getCustomer() === $this) {
                $consumer->setCustomer(null);
            }
        }

        return $this;
    }
}
