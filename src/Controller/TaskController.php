<?php

namespace App\Controller;

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

class TaskController extends AbstractController
{
    #[Route('/tasks/jour/{project_id}/{year}/{month}', name: 'app_tasks_day', defaults: ['year' => null, 'month' => null])]
    public function index(TaskRepository $taskRepository, int $project_id, ?int $year, ?int $month): Response
    {
        $currentDate = new DateTime();
        $year = $year ?? (int)$currentDate->format('Y');
        $month = $month ?? (int)$currentDate->format('m');
        
        $startDate = new DateTime("$year-$month-01");
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');
    
        $tasks = $taskRepository->findTasksByDateRange($project_id, $startDate, $endDate);
    
        $calendar = $this->generateCalendar($startDate, $tasks);
    
        // Créer un formulaire vierge pour l'affichage
        $form = $this->createForm(AddTaskType::class);
    
        return $this->render('task/tasks_jour.html.twig', [
            'calendar' => $calendar,
            'currentMonth' => $month,
            'currentYear' => $year,
            'project_id' => $project_id,
            'addTaskForm' => $form->createView(),
            'is_manager' => true,
            'target_task' => null,
        ]);
    }


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


    #[Route('/tasks/semaine/{project_id}', name: 'app_tasks_week')]
    public function calendarView(
    int $project_id,
    Request $request,
    TaskRepository $taskRepository
    ): Response {
    // Nombre de semaines par page
    $weeksPerPage = 8;

    // Calculer la date actuelle
    $currentDate = new \DateTime();
    $currentDate->modify('monday this week'); 

    // Calculer la page qui contient la semaine actuelle
    $startDate = new \DateTime('2023-01-01'); 
    $startDate->modify('monday this week'); 

    // Calculer le nombre de semaines entre le début de l'année et la semaine actuelle
    $interval = $startDate->diff($currentDate);
    $weeksSinceStart = (int)($interval->format('%a') / 7);

    // Calculer la page en fonction des semaines écoulées
    $page = (int)floor($weeksSinceStart / $weeksPerPage) + 1;

    // Obtenir la page demandée via la requête si elle existe, sinon utiliser la page calculée
    $page = $request->query->getInt('page', $page);

    // Calculer la date de début et de fin en fonction de la page
    $startDate = (new \DateTime('2023-01-01'))
        ->modify('monday this week')
        ->modify('+' . (($page - 1) * $weeksPerPage) . ' weeks');

    $endDate = clone $startDate;
    $endDate->modify('+' . ($weeksPerPage - 1) . ' weeks')->modify('sunday this week'); // Fin de la période (8 semaines après)

    $tasks = $taskRepository->findTasksByDateRange($project_id, $startDate, $endDate);

    $weeks = [];
    $currentWeek = clone $startDate;
    while ($currentWeek <= $endDate) {
        $weekKey = $currentWeek->format('Y-m-d');
        $weeks[$weekKey] = [];
        $currentWeek->modify('+1 week');
    }

    foreach ($tasks as $task) {
        $startOfWeek = $task->getDateDebutTache()->modify('monday this week')->format('Y-m-d');
        if (isset($weeks[$startOfWeek])) {
            $weeks[$startOfWeek][] = $task;
        }
    }

    // Créer un formulaire vierge pour l'affichage
    $form = $this->createForm(AddTaskType::class);

    return $this->render('task/tasks_semaine.html.twig', [
        'weeks' => $weeks,
        'page' => $page,
        'project_id' => $project_id,
        'addTaskForm' => $form->createView(),
        'is_manager' => true,
    ]);
    }

    #[Route('/tasks/mois/{project_id}', name: 'app_tasks_month')]
    public function monthlyCalendarView(
        int $project_id,
        Request $request,
        TaskRepository $taskRepository
    ): Response {
    
        $monthsPerPage = 4;


        $currentDate = new \DateTime();
        $currentDate->modify('first day of this month'); 


        $startDate = new \DateTime('2023-01-01'); 
        $startDate->modify('first day of this month'); 

        // Calculer le nombre de mois entre le début de l'année et le mois actuel
        $interval = $startDate->diff($currentDate);
        $monthsSinceStart = (int)($interval->format('%a') / 30); 

        // Calculer la page en fonction des mois écoulés
        $page = (int)floor($monthsSinceStart / $monthsPerPage) + 1;

        // Obtenir la page demandée via la requête si elle existe, sinon utiliser la page calculée
        $page = $request->query->getInt('page', $page);

        // Calculer la date de début et de fin en fonction de la page
        $startDate = (new \DateTime('2023-01-01'))
            ->modify('first day of this month')
            ->modify('+' . (($page - 1) * $monthsPerPage) . ' months');

        $endDate = clone $startDate;
        $endDate->modify('+' . ($monthsPerPage - 1) . ' months'); 

        // Récupérer les tâches pour cette plage de dates
        $tasks = $taskRepository->findTasksByDateRange($project_id, $startDate, $endDate);

        // Organiser les tâches par mois
        $months = [];
        $currentMonth = clone $startDate;
        while ($currentMonth <= $endDate) {
            $monthKey = $currentMonth->format('Y-m');
            $months[$monthKey] = []; 
            $currentMonth->modify('+1 month');
        }

        foreach ($tasks as $task) {
            $startOfMonth = $task->getDateDebutTache()->modify('first day of this month')->format('Y-m');
            if (isset($months[$startOfMonth])) {
                $months[$startOfMonth][] = $task;
            }
        }

        // Créer un formulaire vierge pour l'affichage
        $form = $this->createForm(AddTaskType::class);

        return $this->render('task/tasks_mois.html.twig', [
            'months' => $months,
            'page' => $page,
            'project_id' => $project_id,
            'addTaskForm' => $form->createView(),
            'is_manager' => true,
        ]);
    }

    // Créer un formulaire vierge pour l'affichage
    $form = $this->createForm(AddTaskType::class);

    return $this->render('task/tasks_mois.html.twig', [
        'months' => $months,
        'page' => $page,
        'project_id' => $project_id,
        'addTaskForm' => $form->createView(),
        'is_manager' => true,
    ]);
}


    #[Route('project/{project_id}/task/status/{id}', name: "change_task_status", methods: ['POST'])]
    public function TaskStatus(
        int $project_id,
        int $id,
        Request $request,
        TaskRepository $tasksRepository,
        ProjectRepository $projectsRepository,
        EntityManagerInterface $em
    ): RedirectResponse {
        $project = $projectsRepository->find($project_id);
        if (!$project) throw $this->createNotFoundException('Projet introuvable.');

        $task = $tasksRepository->findOneBy(['id' => $id, 'project' => $project_id]);
        if (!$task) throw $this->createNotFoundException('Tâche introuvable.');

        $selected_status = $request->request->get('new_status');
        $task->setStatutTask($selected_status);

        $em->persist($task);
        $em->flush();

        $year = $task->getDateDebutTache()->format('Y');
        $month = $task->getDateDebutTache()->format('m');

        return $this->redirectToRoute('app_tasks_day', [
            'project_id' => $project_id,
            'year' => $year,
            'month' => $month,
            'target_task' => $id,
        ]);
    }

    





    /*---------------------------------------- fonctions ------------------------------------------------------*/
    private function generateCalendar(DateTime $startDate, array $tasks): array
    {
        $calendar = [];
        $firstDayOfMonth = (int)$startDate->format('N'); 
        $daysInMonth = (int)$startDate->format('t');
        $currentDay = 1;

        for ($week = 0; $week < 6; $week++) {
            $weekData = [];

            // Boucle sur chaque jour de la semaine
            for ($day = 1; $day <= 7; $day++) {
                if (($week === 0 && $day < $firstDayOfMonth) || $currentDay > $daysInMonth) {
                
                    $weekData[] = ['date' => null, 'tasks' => []];
                } else {
                    $date = (clone $startDate)->setDate($startDate->format('Y'), $startDate->format('m'), $currentDay);
                    $tasksForDay = array_filter($tasks, function($task) use ($date) {
                        return $task->getDateDebutTache()->format('Y-m-d') === $date->format('Y-m-d');
                    });

                    $weekData[] = ['date' => $date, 'tasks' => $tasksForDay];
                    $currentDay++;
                }
            }
            $calendar[] = $weekData;

            if ($currentDay > $daysInMonth) break;
        }

        return $calendar;
    }
}
