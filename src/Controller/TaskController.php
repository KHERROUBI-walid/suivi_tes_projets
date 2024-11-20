<?php

namespace App\Controller;

use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;
use Symfony\Component\HttpFoundation\Request;

class TaskController extends AbstractController
{
    #[Route('/tasks/jour/{project_id}/{year}/{month}', name: 'app_tasks_day', defaults: ['year' => null, 'month' => null])]
    public function index(TaskRepository $taskRepository, int $project_id, ?int $year, ?int $month): Response
    {
        // Si l'année et le mois ne sont pas fournis, on utilise le mois et l'année actuels
        $currentDate = new DateTime();
        $year = $year ?? (int)$currentDate->format('Y');
        $month = $month ?? (int)$currentDate->format('m');
        
        // Calcul de la date de début du mois et de la fin du mois
        $startDate = new DateTime("$year-$month-01");
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');

        // Récupération des tâches pour la plage de dates spécifique
        $tasks = $taskRepository->findTasksByDateRange($project_id, $startDate, $endDate);

        // Génération du calendrier avec les tâches
        $calendar = $this->generateCalendar($startDate, $tasks);

        return $this->render('task/tasks_jour.html.twig', [
            'calendar' => $calendar,
            'currentMonth' => $month,
            'currentYear' => $year,
            'project_id' => $project_id,
        ]);
    }

    private function generateCalendar(DateTime $startDate, array $tasks): array
    {
        $calendar = [];
        $firstDayOfMonth = (int)$startDate->format('N'); // 1 (Lundi) à 7 (Dimanche)
        $daysInMonth = (int)$startDate->format('t');
        $currentDay = 1;

        // Boucle sur chaque semaine (6 semaines maximum pour s'adapter aux mois longs)
        for ($week = 0; $week < 6; $week++) {
            $weekData = [];

            // Boucle sur chaque jour de la semaine
            for ($day = 1; $day <= 7; $day++) {
                if (($week === 0 && $day < $firstDayOfMonth) || $currentDay > $daysInMonth) {
                    // Si le jour est avant le début du mois ou après la fin du mois, on crée une cellule vide
                    $weekData[] = ['date' => null, 'tasks' => []];
                } else {
                    // Sinon, on crée la date pour le jour actuel
                    $date = (clone $startDate)->setDate($startDate->format('Y'), $startDate->format('m'), $currentDay);
                    
                    // Filtre les tâches pour le jour actuel
                    $tasksForDay = array_filter($tasks, function($task) use ($date) {
                        return $task->getDateDebutTache()->format('Y-m-d') === $date->format('Y-m-d');
                    });
                    
                    // Ajoute le jour avec ses tâches au tableau de la semaine
                    $weekData[] = ['date' => $date, 'tasks' => $tasksForDay];
                    $currentDay++;
                }
            }
            // Ajoute la semaine au calendrier
            $calendar[] = $weekData;
            
            // Si tous les jours du mois ont été affichés, on arrête la boucle
            if ($currentDay > $daysInMonth) break;
        }

        return $calendar;
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
    $currentDate->modify('monday this week');  // Commencer à partir du lundi de la semaine actuelle

    // Calculer la page qui contient la semaine actuelle
    $startDate = new \DateTime('2023-01-01'); // Début de la première page (1er janvier 2023)
    $startDate->modify('monday this week');  // Début de la première semaine de l'année 2023

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

    // Récupérer les tâches pour cette plage de dates
    $tasks = $taskRepository->findTasksByDateRange($project_id, $startDate, $endDate);

    // Organiser les tâches par semaines
    $weeks = [];
    $currentWeek = clone $startDate;
    while ($currentWeek <= $endDate) {
        $weekKey = $currentWeek->format('Y-m-d');
        $weeks[$weekKey] = []; // Par défaut, aucune tâche
        $currentWeek->modify('+1 week');
    }

    foreach ($tasks as $task) {
        $startOfWeek = $task->getDateDebutTache()->modify('monday this week')->format('Y-m-d');
        if (isset($weeks[$startOfWeek])) {
            $weeks[$startOfWeek][] = $task;
        }
    }

    return $this->render('task/tasks_semaine.html.twig', [
        'weeks' => $weeks,
        'page' => $page,
        'project_id' => $project_id,
    ]);
}


#[Route('/tasks/mois/{project_id}', name: 'app_tasks_month')]
public function monthlyCalendarView(
    int $project_id,
    Request $request,
    TaskRepository $taskRepository
): Response {
    // Nombre de mois par page
    $monthsPerPage = 4;

    // Calculer la date actuelle
    $currentDate = new \DateTime();
    $currentDate->modify('first day of this month');  // Commence à partir du premier jour du mois actuel

    // Calculer la page qui contient le mois actuel
    $startDate = new \DateTime('2023-01-01'); // Début de la première page (1er janvier 2023)
    $startDate->modify('first day of this month');  // Commence à partir du premier jour du mois de janvier 2023

    // Calculer le nombre de mois entre le début de l'année et le mois actuel
    $interval = $startDate->diff($currentDate);
    $monthsSinceStart = (int)($interval->format('%a') / 30); // Convertir les jours en mois (approximativement)

    // Calculer la page en fonction des mois écoulés
    $page = (int)floor($monthsSinceStart / $monthsPerPage) + 1;

    // Obtenir la page demandée via la requête si elle existe, sinon utiliser la page calculée
    $page = $request->query->getInt('page', $page);

    // Calculer la date de début et de fin en fonction de la page
    $startDate = (new \DateTime('2023-01-01'))
        ->modify('first day of this month')
        ->modify('+' . (($page - 1) * $monthsPerPage) . ' months');

    $endDate = clone $startDate;
    $endDate->modify('+' . ($monthsPerPage - 1) . ' months'); // Fin de la période (4 mois après)

    // Récupérer les tâches pour cette plage de dates
    $tasks = $taskRepository->findTasksByDateRange($project_id, $startDate, $endDate);

    // Organiser les tâches par mois
    $months = [];
    $currentMonth = clone $startDate;
    while ($currentMonth <= $endDate) {
        $monthKey = $currentMonth->format('Y-m');
        $months[$monthKey] = []; // Par défaut, aucune tâche
        $currentMonth->modify('+1 month');
    }

    foreach ($tasks as $task) {
        $startOfMonth = $task->getDateDebutTache()->modify('first day of this month')->format('Y-m');
        if (isset($months[$startOfMonth])) {
            $months[$startOfMonth][] = $task;
        }
    }

    return $this->render('task/tasks_mois.html.twig', [
        'months' => $months,
        'page' => $page,
        'project_id' => $project_id,
    ]);
}



}
