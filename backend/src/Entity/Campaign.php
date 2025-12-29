<?php

namespace App\Entity;

use App\Repository\CampaignRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CampaignRepository::class)]
#[ORM\Table(name: 'campaigns')]
class Campaign
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileName = null;

    #[ORM\Column]
    private ?int $totalContacts = 0;

    #[ORM\Column]
    private ?int $dispatchLimit = 0;

    #[ORM\Column]
    private ?int $dailyLimit = 0;

    #[ORM\Column]
    private ?int $dispatchedCount = 0;

    #[ORM\Column]
    private ?int $dispatchedToday = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastDispatchDate = null;

    #[ORM\Column(length: 50)]
    private ?string $status = 'draft'; // draft, active, paused, completed, failed

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\ManyToOne(inversedBy: 'campaigns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'campaigns')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Organization $organization = null;

    #[ORM\OneToMany(targetEntity: Dispatch::class, mappedBy: 'campaign', orphanRemoval: true)]
    private Collection $dispatches;

    #[ORM\OneToMany(targetEntity: Contact::class, mappedBy: 'campaign', orphanRemoval: true)]
    private Collection $contacts;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $configuration = null;

    public function __construct()
    {
        $this->dispatches = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): static
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getTotalContacts(): ?int
    {
        return $this->totalContacts;
    }

    public function setTotalContacts(int $totalContacts): static
    {
        $this->totalContacts = $totalContacts;
        return $this;
    }

    public function getDispatchLimit(): ?int
    {
        return $this->dispatchLimit;
    }

    public function setDispatchLimit(int $dispatchLimit): static
    {
        $this->dispatchLimit = $dispatchLimit;
        return $this;
    }

    public function getDailyLimit(): ?int
    {
        return $this->dailyLimit;
    }

    public function setDailyLimit(int $dailyLimit): static
    {
        $this->dailyLimit = $dailyLimit;
        return $this;
    }

    public function getDispatchedCount(): ?int
    {
        return $this->dispatchedCount;
    }

    public function setDispatchedCount(int $dispatchedCount): static
    {
        $this->dispatchedCount = $dispatchedCount;
        return $this;
    }

    public function getDispatchedToday(): ?int
    {
        return $this->dispatchedToday;
    }

    public function setDispatchedToday(int $dispatchedToday): static
    {
        $this->dispatchedToday = $dispatchedToday;
        return $this;
    }

    public function getLastDispatchDate(): ?\DateTimeImmutable
    {
        return $this->lastDispatchDate;
    }

    public function setLastDispatchDate(?\DateTimeImmutable $lastDispatchDate): static
    {
        $this->lastDispatchDate = $lastDispatchDate;
        return $this;
    }

    public function resetDailyCountIfNeeded(): void
    {
        $today = new \DateTimeImmutable('today');

        if ($this->lastDispatchDate === null || $this->lastDispatchDate < $today) {
            $this->dispatchedToday = 0;
        }
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;
        return $this;
    }

    /**
     * @return Collection<int, Dispatch>
     */
    public function getDispatches(): Collection
    {
        return $this->dispatches;
    }

    public function addDispatch(Dispatch $dispatch): static
    {
        if (!$this->dispatches->contains($dispatch)) {
            $this->dispatches->add($dispatch);
            $dispatch->setCampaign($this);
        }

        return $this;
    }

    public function removeDispatch(Dispatch $dispatch): static
    {
        if ($this->dispatches->removeElement($dispatch)) {
            if ($dispatch->getCampaign() === $this) {
                $dispatch->setCampaign(null);
            }
        }

        return $this;
    }

    public function getConfiguration(): ?array
    {
        return $this->configuration;
    }

    public function setConfiguration(?array $configuration): static
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(Contact $contact): static
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts->add($contact);
            $contact->setCampaign($this);
        }

        return $this;
    }

    public function removeContact(Contact $contact): static
    {
        if ($this->contacts->removeElement($contact)) {
            if ($contact->getCampaign() === $this) {
                $contact->setCampaign(null);
            }
        }

        return $this;
    }
}
