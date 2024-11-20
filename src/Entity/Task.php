<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(length: 400)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_debut_tache = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_fin_tache = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column(length: 100)]
    private ?string $statut_task = null;

    public function getIdTask(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDateDebutTache(): ?\DateTimeInterface
    {
        return $this->date_debut_tache;
    }

    public function setDateDebutTache(\DateTimeInterface $date_debut_tache): static
    {
        $this->date_debut_tache = $date_debut_tache;
        return $this;
    }

    public function getDateFinTache(): ?\DateTimeInterface
    {
        return $this->date_fin_tache;
    }

    public function setDateFinTache(\DateTimeInterface $date_fin_tache): static
    {
        $this->date_fin_tache = $date_fin_tache;
        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;
        return $this;
    }

    public function getStatutTask(): ?string
    {
        return $this->statut_task;
    }

    public function setStatutTask(string $statut_task): static
    {
        $this->statut_task = $statut_task;

        return $this;
    }
}
