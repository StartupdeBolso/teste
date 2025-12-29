<?php

namespace App\Entity;

use App\Repository\DispatchRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DispatchRepository::class)]
#[ORM\Table(name: 'dispatches')]
class Dispatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'dispatches')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Campaign $campaign = null;

    #[ORM\Column(type: Types::JSON)]
    private array $contactData = [];

    #[ORM\Column(length: 50)]
    private ?string $status = 'pending'; // pending, processing, sent, failed

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $responseData = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $retryCount = 0;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    public function setCampaign(?Campaign $campaign): static
    {
        $this->campaign = $campaign;
        return $this;
    }

    public function getContactData(): array
    {
        return $this->contactData;
    }

    public function setContactData(array $contactData): static
    {
        $this->contactData = $contactData;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getResponseData(): ?string
    {
        return $this->responseData;
    }

    public function setResponseData(?string $responseData): static
    {
        $this->responseData = $responseData;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;
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

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function getRetryCount(): ?int
    {
        return $this->retryCount;
    }

    public function setRetryCount(?int $retryCount): static
    {
        $this->retryCount = $retryCount;
        return $this;
    }
}
