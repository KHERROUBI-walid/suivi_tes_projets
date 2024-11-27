<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Project;
use App\Form\AddProjectType;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProjectsController extends AbstractController
{
    #[Route('/projects', name: 'app_projects', methods: ['GET'])]
    public function userProjects(
        ProjectRepository $projectRepository,
        TaskRepository $taskRepository,
        EntityManagerInterface $entityManager
    ): Response {

        if ($this->isGranted('ROLE_MANAGER')) {
            return new RedirectResponse('/projects/manager');
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return new RedirectResponse('/projects/admin');
        }

        // Récupérer tous les projets triés par date de fin
        $projects = $projectRepository->findBy([], ['date_fin' => 'ASC']);

        // Mise à jour des statuts des projets
        foreach ($projects as $project) {
            $this->updateProjectStatus($project, $taskRepository, $entityManager);
        }

        return $this->render('projects/projects.html.twig', [
            'projects' => $projects,
            'is_manager' => false,
        ]);
    }

    #[Route('/projects/manager', name: 'app_projects_manager', methods: ['GET', 'POST'])]
    public function managerProjects(
        Request $request,
        ProjectRepository $projectRepository,
        TaskRepository $taskRepository,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();

        // Vérification des droits d'accès
        if (!$this->isGranted('ROLE_MANAGER')) {
            throw $this->createAccessDeniedException('Accès réservé aux gestionnaires.');
        }

        // Récupérer les projets liés au gestionnaire
        $projects = $projectRepository->findByManager($user->getId());

        // Mise à jour des statuts des projets
        foreach ($projects as $project) {
            $this->updateProjectStatus($project, $taskRepository, $entityManager);
        }

        // Gestion de l'ajout d'un projet
        $project = new Project();
        $form = $this->createForm(AddProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $project->setUser($user);
            $project->setStatutProjet('pas_commence');
            $entityManager->persist($project);
            $entityManager->flush();

            $this->addFlash('success', 'Projet ajouté avec succès.');

            return $this->redirectToRoute('app_projects_manager');
        }

        return $this->render('projects/projects.html.twig', [
            'projects' => $projects,
            'addProjectForm' => $form->createView(),
            'is_manager' => true,
        ]);
    }

    private function updateProjectStatus(Project $project, TaskRepository $taskRepository, EntityManagerInterface $entityManager): void
    {
        $now = new \DateTime();
        $dateDebut = $project->getDateDebut();
        $dateFin = $project->getDateFin();
        $tasks = $taskRepository->findBy(['project' => $project]);

        // Gestion des statuts
        $allTasksCompleted = true;
        $atLeastOneTaskCompleted = false;

        foreach ($tasks as $task) {
            if ($task->getStatutTask() !== 'termine') {
                $allTasksCompleted = false;
            } else {
                $atLeastOneTaskCompleted = true;
            }
        }

        if ($allTasksCompleted && count($tasks) !== 0) {
            $project->setStatutProjet('termine');
        } elseif ($now > $dateFin && !$allTasksCompleted) {
            $project->setStatutProjet('en_retard');
        } elseif ($now < $dateDebut || !$atLeastOneTaskCompleted || count($tasks) === 0) {
            $project->setStatutProjet('pas_commence');
        } else {
            $project->setStatutProjet('en_cours');
        }

        $entityManager->persist($project);
        $entityManager->flush();
    }

#[IsGranted('ROLE_MANAGER')]
#[Route('/projects/manager/delete/{project_id}', name: 'project_delete', methods: ['POST'])]
    public function delete(
        int $project_id, 
        Request $request, 
        EntityManagerInterface $entityManager, 
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        // Vérification du token CSRF
        $token = $request->request->get('_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('delete_project_' . $project_id, $token))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        // Récupération du projet
        $project = $entityManager->getRepository(Project::class)->find($project_id);
        if (!$project) {
            throw $this->createNotFoundException('Le projet n’existe pas.');
        }

        // Suppression
        $entityManager->remove($project);
        $entityManager->flush();

        $this->addFlash('success', 'Projet supprimé avec succès.');

        return $this->redirectToRoute('app_projects_manager');
    }
}

