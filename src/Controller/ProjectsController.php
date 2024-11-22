<?php

// src/Controller/ProjectsController.php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProjectsController extends AbstractController
{
    #[Route('/', name: 'app_projects')]
    public function show(ProjectRepository $projectRepository, TaskRepository $taskRepository, EntityManagerInterface $entityManager): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        // Vérifier les projets en fonction du rôle de l'utilisateur
        if ($user instanceof User && $this->isGranted('ROLE_MANAGER')) {
            $projects = $projectRepository->findByManager($user->getId());
        } else {
            $projects = $projectRepository->findBy([], ['date_fin' => 'ASC']);
        }

        // Mise à jour du statut de chaque projet avant affichage
        foreach ($projects as $project) {
            $this->updateProjectStatus($project, $taskRepository, $entityManager);
        }

        return $this->render('projects/projects.html.twig', [
            'projects' => $projects,
        ]);
    }

    private function updateProjectStatus(Project $project, TaskRepository $taskRepository, EntityManagerInterface $entityManager): void
    {
        $now = new \DateTime();
        $dateDebut = $project->getDateDebut();
        $dateFin = $project->getDateFin();
        $tasks = $taskRepository->findBy(['project' => $project]);

        // gerer les statuts
        $allTasksCompleted = true;
        $atLeastOneTaskCompleted = false;

        foreach ($tasks as $task) {
            if ($task->getStatutTask() !== 'termine') {
                $allTasksCompleted = false;
            } else {
                $atLeastOneTaskCompleted = true;
            }
        }

        if ($allTasksCompleted) {
            $project->setStatutProjet('termine');
        }

        elseif ($now > $dateFin && !$allTasksCompleted) {
            $project->setStatutProjet('en_retard');
        }

        elseif ($now < $dateDebut) {
            $project->setStatutProjet('pas_commence');
        }
    
        elseif ($atLeastOneTaskCompleted && $now >= $dateDebut && $now <= $dateFin) {
            $project->setStatutProjet('en_cours');
        }

        $entityManager->persist($project);
        $entityManager->flush();
    }
}
