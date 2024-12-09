<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Task;
use App\Form\AddTaskType;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ManagerController extends AbstractController
{
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

        $project = $entityManager->getRepository(Project::class)->find($project_id);
        if (!$project) {
            throw $this->createNotFoundException('Le projet n’existe pas.');
        }

        $entityManager->remove($project);
        $entityManager->flush();

        $this->addFlash('success', 'Projet supprimé avec succès.');

        return $this->redirectToRoute('app_projects_manager');
    }



    #[IsGranted('ROLE_MANAGER')]
    #[Route('/tasks/ajouter-tache/{project_id}', name: 'add-task', methods: ['POST'])]
    public function addTask(
        Request $request, 
        EntityManagerInterface $entityManager,
        ProjectRepository $projectRepository,
        int $project_id
    ): Response {
        // Récupération du projet
        $project = $projectRepository->find($project_id);
        if (!$project) {
            throw $this->createNotFoundException('Projet non trouvé.');
        }

        // Traitement du formulaire
        $task = new Task();
        $form = $this->createForm(AddTaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setProject($project);
            $task->setStatutTask('pas_commence');
            $entityManager->persist($task);
            $entityManager->flush();

            $this->addFlash('success', 'Tâche ajoutée avec succès.');

            //- Pour Centrer le calendrier au nouvelle tache
            $dateCible = $task->getDateDebutTache();
            $year = (int)$dateCible->format('Y');
            $month = (int)$dateCible->format('m');

            $new_task = $task->getIdTask();

            return $this->redirectToRoute('app_tasks_day', [
                'project_id' => $project_id,
                'year' => $year,
                'month' => $month,
                'target_task' => $new_task,
            ]);
        }

        // Redirection en cas d'échec (reste sur la page actuelle)
        return $this->redirectToRoute('app_tasks_day', [
            'project_id' => $project_id,
        ]);
    }


    #[IsGranted('ROLE_MANAGER')]
    #[Route('/task/{id}/edit', name: 'edit-task')]
    public function editTask(Request $request, Task $task, EntityManagerInterface $entityManager): Response
{
    // Récupérer l'URL de retour (si elle existe)
    $returnUrl = $request->query->get('return_url');


    // Lier l'entité Task au formulaire
    $form = $this->createForm(AddTaskType::class, $task);
    $form->handleRequest($request);

    $project_id = $task->getProject()->getIdProject();
    
    if ($form->isSubmitted() && $form->isValid()) {

        //- Pour Centrer le calendrier au tache tache modifié
        $dateCible = $task->getDateDebutTache();
        $year = (int)$dateCible->format('Y');
        $month = (int)$dateCible->format('m');     

        $entityManager->persist($task);
        $entityManager->flush();

        
        return $this->redirectToRoute('app_tasks_day', [
            'project_id' => $project_id,
            'target_task' => $task->getIdTask(),
            'year' => $year,
            'month' => $month,
        ]);
    }

    
    return $this->render('Form/editTask.html.twig', [
        'project_id' => $project_id,
        'lien_clique' => true,
        'editTaskForm' => $form->createView(),
        'idTask' => $task->getIdTask(),
        'return_url' => $returnUrl,
    ]);
}

#[IsGranted('ROLE_MANAGER')]
#[Route('/task/{task_id}/delete', name: 'task_delete', methods: ['POST'])]
    public function deleteTask(
        int $task_id, 
        Request $request, 
        EntityManagerInterface $entityManager, 
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        // Vérification du token CSRF
        $token = $request->request->get('_token');
        if (!$csrfTokenManager->isTokenValid(new CsrfToken('delete_task_' . $task_id, $token))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $task = $entityManager->getRepository(Task::class)->find($task_id);
        if (!$task) {
            throw $this->createNotFoundException('Le projet n’existe pas.');
        }

        $entityManager->remove($task);
        $entityManager->flush();

        $this->addFlash('success', 'Tache supprimé avec succès.');

        $project_id = $task->getProject()->getIdProject();

        //- Pour Centrer le calendrier au tache tache modifié
        $dateCible = $task->getDateDebutTache();
        $year = (int)$dateCible->format('Y');
        $month = (int)$dateCible->format('m');

        return $this->redirectToRoute('app_tasks_day', [
            'project_id' => $project_id,
            'target_task' => $task->getIdTask(),
            'year' => $year,
            'month' => $month,
        ]);
    }
}
