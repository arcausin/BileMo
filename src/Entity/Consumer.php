<?php

namespace App\Entity;

use App\Repository\ConsumerRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @Hateoas\Relation(
 *      "[POST]",
 *      href = @Hateoas\Route(
 *          "app_consumers_create"
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getConsumers")
 * )
 * 
 * @Hateoas\Relation(
 *      "[GET]",
 *      href = @Hateoas\Route(
 *          "app_consumers_show",
 *          parameters = { "id" = "expr(object.getId())" }
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getConsumers")
 * )
 * 
 * @Hateoas\Relation(
 *      "[PUT]",
 *      href = @Hateoas\Route(
 *          "app_consumers_update",
 *          parameters = { "id" = "expr(object.getId())" }
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getConsumers")
 * )
 *
 * @Hateoas\Relation(
 *      "[DELETE]",
 *      href = @Hateoas\Route(
 *          "app_consumers_delete",
 *          parameters = { "id" = "expr(object.getId())" }
 *      ),
 *      exclusion = @Hateoas\Exclusion(groups="getConsumers")
 * )
 * 
 */

#[ORM\Entity(repositoryClass: ConsumerRepository::class)]
class Consumer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['getCustomers', 'getConsumers'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'consumers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['getConsumers'])]
    //#[Assert\NotBlank(message: 'Customer is required')]
    private ?Customer $customer = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['getCustomers', 'getConsumers'])]
    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(max: 255, maxMessage: 'First name cannot be longer than {{ limit }} characters')]
    private ?string $firstName = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['getCustomers', 'getConsumers'])]
    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(max: 255, maxMessage: 'Last name cannot be longer than {{ limit }} characters')]
    private ?string $lastName = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['getCustomers', 'getConsumers'])]
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Email is not valid')]
    private ?string $email = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['getCustomers', 'getConsumers'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

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
}
