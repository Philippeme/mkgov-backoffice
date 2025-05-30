<?php

namespace App\Controller;

use App\Service\ProcedureDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de gestion des procédures et workflows administratifs
 * 
 * Ce contrôleur gère l'ensemble des opérations relatives aux procédures
 * gouvernementales, leurs étapes, et les workflows associés pour optimiser
 * les processus administratifs du système MK Gov.
 */
#[Route('/admin/procedures', name: 'admin_procedures_')]
class ProcedureController extends AbstractController
{
    public function __construct(
        private ProcedureDataService $procedureService
    ) {}

    /**
     * Liste des procédures avec pagination et filtres
     */
    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $filters = [
            'category' => $request->get('category'),
            'status' => $request->get('status'),
            'complexity' => $request->get('complexity'),
            'search' => $request->get('search')
        ];

        $procedures = $this->procedureService->getPaginatedProcedures($page, $limit, $filters);
        $statistics = $this->procedureService->getProcedureStatistics($filters);
        $categories = $this->procedureService->getProcedureCategories();

        return $this->render('procedures/index.html.twig', [
            'procedures' => $procedures,
            'statistics' => $statistics,
            'categories' => $categories,
            'current_filters' => $filters,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $procedures['total_pages'],
                'total_items' => $procedures['total_items']
            ]
        ]);
    }

    /**
     * Création d'une nouvelle procédure
     */
    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $result = $this->procedureService->createProcedure($data);
            
            if ($result['success']) {
                $this->addFlash('success', 'La procédure a été créée avec succès.');
                return $this->redirectToRoute('admin_procedures_show', ['id' => $result['id']]);
            } else {
                $this->addFlash('error', 'Erreur lors de la création de la procédure: ' . $result['message']);
            }
        }

        $categories = $this->procedureService->getProcedureCategories();
        $templates = $this->procedureService->getProcedureTemplates();
        $entities = $this->procedureService->getPublicEntities();

        return $this->render('procedures/new.html.twig', [
            'categories' => $categories,
            'templates' => $templates,
            'entities' => $entities
        ]);
    }

    /**
     * Affichage détaillé d'une procédure
     */
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $procedure = $this->procedureService->getProcedureById($id);
        
        if (!$procedure) {
            throw $this->createNotFoundException('Procédure introuvable.');
        }

        $steps = $this->procedureService->getProcedureSteps($id);
        $workflow = $this->procedureService->getProcedureWorkflow($id);
        $statistics = $this->procedureService->getProcedureStatistics(['procedure_id' => $id]);

        return $this->render('procedures/show.html.twig', [
            'procedure' => $procedure,
            'steps' => $steps,
            'workflow' => $workflow,
            'statistics' => $statistics
        ]);
    }

    /**
     * Modification d'une procédure
     */
    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request): Response
    {
        $procedure = $this->procedureService->getProcedureById($id);
        
        if (!$procedure) {
            throw $this->createNotFoundException('Procédure introuvable.');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $result = $this->procedureService->updateProcedure($id, $data);
            
            if ($result['success']) {
                $this->addFlash('success', 'La procédure a été mise à jour avec succès.');
                return $this->redirectToRoute('admin_procedures_show', ['id' => $id]);
            } else {
                $this->addFlash('error', 'Erreur lors de la mise à jour: ' . $result['message']);
            }
        }

        $categories = $this->procedureService->getProcedureCategories();
        $entities = $this->procedureService->getPublicEntities();

        return $this->render('procedures/edit.html.twig', [
            'procedure' => $procedure,
            'categories' => $categories,
            'entities' => $entities
        ]);
    }

    /**
     * Gestion des workflows
     */
    #[Route('/workflows', name: 'workflows')]
    public function workflows(Request $request): Response
    {
        $workflows = $this->procedureService->getAllWorkflows();
        $activeWorkflows = $this->procedureService->getActiveWorkflows();
        $workflowStatistics = $this->procedureService->getWorkflowStatistics();

        return $this->render('procedures/workflows.html.twig', [
            'workflows' => $workflows,
            'active_workflows' => $activeWorkflows,
            'statistics' => $workflowStatistics
        ]);
    }

    /**
     * Création d'un nouveau workflow
     */
    #[Route('/workflows/new', name: 'workflows_new')]
    public function newWorkflow(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $result = $this->procedureService->createWorkflow($data);
            
            if ($result['success']) {
                $this->addFlash('success', 'Le workflow a été créé avec succès.');
                return $this->redirectToRoute('admin_procedures_workflows');
            } else {
                $this->addFlash('error', 'Erreur lors de la création du workflow: ' . $result['message']);
            }
        }

        $procedures = $this->procedureService->getAllProcedures();
        $entities = $this->procedureService->getPublicEntities();

        return $this->render('procedures/workflow_new.html.twig', [
            'procedures' => $procedures,
            'entities' => $entities
        ]);
    }

    /**
     * Éditeur de workflow visuel
     */
    #[Route('/workflows/{id}/editor', name: 'workflow_editor', requirements: ['id' => '\d+'])]
    public function workflowEditor(int $id): Response
    {
        $workflow = $this->procedureService->getWorkflowById($id);
        
        if (!$workflow) {
            throw $this->createNotFoundException('Workflow introuvable.');
        }

        $nodes = $this->procedureService->getWorkflowNodes($id);
        $connections = $this->procedureService->getWorkflowConnections($id);

        return $this->render('procedures/workflow_editor.html.twig', [
            'workflow' => $workflow,
            'nodes' => $nodes,
            'connections' => $connections
        ]);
    }

    /**
     * Sauvegarde d'un workflow via AJAX
     */
    #[Route('/workflows/{id}/save', name: 'workflow_save', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function saveWorkflow(int $id, Request $request): JsonResponse
    {
        $workflowData = json_decode($request->getContent(), true);
        
        $result = $this->procedureService->saveWorkflow($id, $workflowData);
        
        return $this->json($result);
    }

    /**
     * Activation/désactivation d'une procédure
     */
    #[Route('/{id}/toggle-status', name: 'toggle_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleStatus(int $id): JsonResponse
    {
        $result = $this->procedureService->toggleProcedureStatus($id);
        
        return $this->json($result);
    }

    /**
     * Duplication d'une procédure
     */
    #[Route('/{id}/duplicate', name: 'duplicate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function duplicate(int $id): Response
    {
        $result = $this->procedureService->duplicateProcedure($id);
        
        if ($result['success']) {
            $this->addFlash('success', 'La procédure a été dupliquée avec succès.');
            return $this->redirectToRoute('admin_procedures_edit', ['id' => $result['new_id']]);
        } else {
            $this->addFlash('error', 'Erreur lors de la duplication: ' . $result['message']);
            return $this->redirectToRoute('admin_procedures_show', ['id' => $id]);
        }
    }

    /**
     * Export des procédures
     */
    #[Route('/export/{format}', name: 'export', methods: ['GET'])]
    public function export(string $format, Request $request): Response
    {
        $filters = [
            'category' => $request->get('category'),
            'status' => $request->get('status'),
            'complexity' => $request->get('complexity')
        ];

        return $this->procedureService->exportProcedures($format, $filters);
    }

    /**
     * Import de procédures depuis un fichier
     */
    #[Route('/import', name: 'import', methods: ['GET', 'POST'])]
    public function import(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $uploadedFile = $request->files->get('procedure_file');
            
            if ($uploadedFile) {
                $result = $this->procedureService->importProcedures($uploadedFile);
                
                if ($result['success']) {
                    $this->addFlash('success', 'Import réalisé avec succès: ' . $result['imported_count'] . ' procédures importées.');
                } else {
                    $this->addFlash('error', 'Erreur lors de l\'import: ' . $result['message']);
                }
            } else {
                $this->addFlash('error', 'Veuillez sélectionner un fichier à importer.');
            }
            
            return $this->redirectToRoute('admin_procedures_index');
        }

        return $this->render('procedures/import.html.twig');
    }

    /**
     * Analyse de performance des procédures
     */
    #[Route('/analytics', name: 'analytics')]
    public function analytics(Request $request): Response
    {
        $period = $request->get('period', '30');
        $category = $request->get('category');

        $analytics = $this->procedureService->getProcedureAnalytics($period, $category);
        $performanceMetrics = $this->procedureService->getPerformanceMetrics($period);
        $bottlenecks = $this->procedureService->identifyBottlenecks();

        return $this->render('procedures/analytics.html.twig', [
            'analytics' => $analytics,
            'performance_metrics' => $performanceMetrics,
            'bottlenecks' => $bottlenecks,
            'selected_period' => $period,
            'selected_category' => $category
        ]);
    }

    /**
     * Suppression d'une procédure
     */
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete'.$id, $request->get('_token'))) {
            $result = $this->procedureService->deleteProcedure($id);
            
            if ($result['success']) {
                $this->addFlash('success', 'La procédure a été supprimée avec succès.');
            } else {
                $this->addFlash('error', 'Erreur lors de la suppression: ' . $result['message']);
            }
        }

        return $this->redirectToRoute('admin_procedures_index');
    }
}