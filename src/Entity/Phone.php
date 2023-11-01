<?php

namespace App\Entity;

use App\Repository\PhoneRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @Hateoas\Relation(
 *      "[POST]",
 *      href = @Hateoas\Route(
 *          "app_phones_create"
 *      ),
 *      exclusion = @Hateoas\Exclusion(excludeIf = "expr(not is_granted('ROLE_ADMIN'))")
 * )
 * 
 * @Hateoas\Relation(
 *      "[GET]",
 *      href = @Hateoas\Route(
 *          "app_phones_show",
 *          parameters = { "id" = "expr(object.getId())" }
 *      )
 * )
 * 
 * @Hateoas\Relation(
 *      "[PUT]",
 *      href = @Hateoas\Route(
 *          "app_phones_update",
 *          parameters = { "id" = "expr(object.getId())" }
 *      ),
 *      exclusion = @Hateoas\Exclusion(excludeIf = "expr(not is_granted('ROLE_ADMIN'))")
 * )
 *
 * @Hateoas\Relation(
 *      "[DELETE]",
 *      href = @Hateoas\Route(
 *          "app_phones_delete",
 *          parameters = { "id" = "expr(object.getId())" }
 *      ),
 *      exclusion = @Hateoas\Exclusion(excludeIf = "expr(not is_granted('ROLE_ADMIN'))")
 * )
 * 
 */

#[ORM\Entity(repositoryClass: PhoneRepository::class)]
class Phone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['getPhones'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Brand is required')]
    #[Assert\Length(max: 255, maxMessage: 'Brand cannot be longer than {{ limit }} characters')]
    #[Groups(['getPhones'])]
    private ?string $brand = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Model is required')]
    #[Assert\Length(max: 255, maxMessage: 'Model cannot be longer than {{ limit }} characters')]
    #[Groups(['getPhones'])]
    private ?string $model = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Image is required')]
    #[Assert\Length(max: 255, maxMessage: 'Image cannot be longer than {{ limit }} characters')]
    #[Groups(['getPhones'])]
    private ?string $image = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank(message: 'Price is required')]
    #[Assert\Positive(message: 'Price must be positive')]
    #[Groups(['getPhones'])]
    private ?float $price = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: 'Stock is required')]
    #[Assert\PositiveOrZero(message: 'Stock must be positive or zero')]
    #[Groups(['getPhones'])]
    private ?int $stock = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Assert\NotBlank(message: 'Release date is required')]
    #[Groups(['getPhones'])]
    private ?\DateTimeImmutable $releaseAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getReleaseAt(): ?\DateTimeImmutable
    {
        return $this->releaseAt;
    }

    public function setReleaseAt(\DateTimeImmutable $releaseAt): static
    {
        $this->releaseAt = $releaseAt;

        return $this;
    }
}
