<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProjectsController extends AbstractController
{
    #[Route('/', name: 'app_projects')]
    public function show(ProjectRepository $projectRepository): Response
    {
        // Récupérer les projets triés par date_debut_fin en ordre croissant
        $projects = $projectRepository->findAll();

        return $this->render('projects/projects.html.twig', [
            'projects' => $projects,
        ]);
    }
}

