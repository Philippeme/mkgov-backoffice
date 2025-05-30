<?php

namespace App\Controller;

use App\Service\PublicEntityDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de gestion des entités publiques camerounaises
 * 
 * Ce contrôleur centralise la gestion des ministères, départements,
 * agences gouvernementales et autres entités publiques participant
 * au système de services électroniques MK Gov.
 */
#[Route('/admin/public-entities', name: 'admin_public_entities_')]
class PublicEntityController extends AbstractController
{
    public function __construct(
        private PublicEntityDataService $entityService
    ) {}

    /**
     * Interface principale de gestion des entités publiques
     */
    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $filters = [
            'type' => $request->get('type'),
            'status' => $request->get('status'),
            'region' => $request->get('region'),
            'parent_entity' => $request->get('parent_entity'),
            'search' => $request->get('search')
        ];

        $entities = $this->entityService->getPaginatedEntities($page, $limit, $filters);
        $statistics = $this->entityService->getEntityStatistics($filters);
        $entityTypes = $this->entityService->getEntityTypes();
        $regions = $this->entityService->getRegions();

        return $this->render('public_entities/index.html.twig', [
            'entities' => $entities,
            'statistics' => $statistics,
            'entity_types' => $entityTypes,
            'regions' => $regions,
            'current_filters' => $filters,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $entities['total_pages'],
                'total_items' => $entities['total_items']
            ]
        ]);
    }

    /**
     * Création d'une nouvelle entité publique
     */
    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $result = $this->entityService->createEntity($data);
            
            if ($result['success']) {
                $this->addFlash('success', 'Entité publique créée avec succès.');
                return $this->redirectToRoute('admin_public_entities_show', ['id' => $result['id']]);
            } else {
                $this->addFlash('error', 'Erreur lors de la création: ' . $result['message']);
            }
        }

        $entityTypes = $this->entityService->getEntityTypes();
        $regions = $this->entityService->getRegions();
        $parentEntities = $this->entityService->getParentEntities();
        $serviceCategories = $this->entityService->getServiceCategories();

        return $this->render('public_entities/new.html.twig', [
            'entity_types' => $entityTypes,
            'regions' => $regions,
            'parent_entities' => $parentEntities,
            'service_categories' => $serviceCategories
        ]);
    }

    /**
     * Affichage détaillé d'une entité publique
     */
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $entity = $this->entityService->getEntityById($id);
        
        if (!$entity) {
            throw $this->createNotFoundException('Entité publique introuvable.');
        }

        $services = $this->entityService->getEntityServices($id);
        $staff = $this->entityService->getEntityStaff($id);
        $performance = $this->entityService->getEntityPerformance($id);
        $subEntities = $this->entityService->getSubEntities($id);

        return $this->render('public_entities/show.html.twig', [
            'entity' => $entity,
            'services' => $services,
            'staff' => $staff,
            'performance' => $performance,
            'sub_entities' => $subEntities
        ]);
    }

    /**
     * Modification d'une entité publique
     */
    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request): Response
    {
        $entity = $this->entityService->getEntityById($id);
        
        if (!$entity) {
            throw $this->createNotFoundException('Entité publique introuvable.');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $result = $this->entityService->updateEntity($id, $data);
            
            if ($result['success']) {
                $this->addFlash('success', 'Entité publique mise à jour avec succès.');
                return $this->redirectToRoute('admin_public_entities_show', ['id' => $id]);
            } else {
                $this->addFlash('error', 'Erreur lors de la mise à jour: ' . $result['message']);
            }
        }

        $entityTypes = $this->entityService->getEntityTypes();
        $regions = $this->entityService->getRegions();
        $parentEntities = $this->entityService->getParentEntities();

        return $this->render('public_entities/edit.html.twig', [
            'entity' => $entity,
            'entity_types' => $entityTypes,
            'regions' => $regions,
            'parent_entities' => $parentEntities
        ]);
    }

    /**
     * Vue hiérarchique des entités publiques
     */
    #[Route('/hierarchy', name: 'hierarchy')]
    public function hierarchy(Request $request): Response
    {
        $hierarchicalView = $this->entityService->getHierarchicalView();
        $organizationChart = $this->entityService->getOrganizationChart();
        $hierarchyStatistics = $this->entityService->getHierarchyStatistics();

        return $this->render('public_entities/hierarchy.html.twig', [
            'hierarchical_view' => $hierarchicalView,
            'organization_chart' => $organizationChart,
            'statistics' => $hierarchyStatistics
        ]);
    }

    /**
     * Gestion des services offerts par les entités
     */
    #[Route('/{id}/services', name: 'services', requirements: ['id' => '\d+'])]
    public function services(int $id, Request $request): Response
    {
        $entity = $this->entityService->getEntityById($id);
        
        if (!$entity) {
            throw $this->createNotFoundException('Entité publique introuvable.');
        }

        $services = $this->entityService->getEntityServices($id);
        $availableServices = $this->entityService->getAvailableServices();
        $serviceStatistics = $this->entityService->getServiceStatistics($id);

        return $this->render('public_entities/services.html.twig', [
            'entity' => $entity,
            'services' => $services,
            'available_services' => $availableServices,
            'statistics' => $serviceStatistics
        ]);
    }

    /**
     * Attribution de service à une entité
     */
    #[Route('/{id}/assign-service', name: 'assign_service', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function assignService(int $id, Request $request): JsonResponse
    {
        $serviceId = $request->get('service_id');
        $result = $this->entityService->assignServiceToEntity($id, $serviceId);

        return $this->json($result);
    }

    /**
     * Retrait de service d'une entité
     */
    #[Route('/{id}/remove-service', name: 'remove_service', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function removeService(int $id, Request $request): JsonResponse
    {
        $serviceId = $request->get('service_id');
        $result = $this->entityService->removeServiceFromEntity($id, $serviceId);

        return $this->json($result);
    }

    /**
     * Gestion du personnel des entités
     */
    #[Route('/{id}/staff', name: 'staff', requirements: ['id' => '\d+'])]
    public function staff(int $id, Request $request): Response
    {
        $entity = $this->entityService->getEntityById($id);
        
        if (!$entity) {
            throw $this->createNotFoundException('Entité publique introuvable.');
        }

        $staff = $this->entityService->getEntityStaff($id);
        $staffStatistics = $this->entityService->getStaffStatistics($id);
        $availablePositions = $this->entityService->getAvailablePositions();

        return $this->render('public_entities/staff.html.twig', [
            'entity' => $entity,
            'staff' => $staff,
            'statistics' => $staffStatistics,
            'available_positions' => $availablePositions
        ]);
    }

    /**
     * Ajout de personnel à une entité
     */
    #[Route('/{id}/add-staff', name: 'add_staff', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addStaff(int $id, Request $request): JsonResponse
    {
        $staffData = [
            'user_id' => $request->get('user_id'),
            'position' => $request->get('position'),
            'role' => $request->get('role'),
            'start_date' => $request->get('start_date')
        ];

        $result = $this->entityService->addStaffToEntity($id, $staffData);

        return $this->json($result);
    }

    /**
     * Rapports de performance des entités
     */
    #[Route('/performance', name: 'performance')]
    public function performance(Request $request): Response
    {
        $period = $request->get('period', '30');
        $entityType = $request->get('entity_type');

        $performanceData = $this->entityService->getPerformanceReport($period, $entityType);
        $benchmarks = $this->entityService->getPerformanceBenchmarks();
        $improvements = $this->entityService->getImprovementSuggestions();

        return $this->render('public_entities/performance.html.twig', [
            'performance_data' => $performanceData,
            'benchmarks' => $benchmarks,
            'improvements' => $improvements,
            'selected_period' => $period,
            'selected_type' => $entityType
        ]);
    }

    /**
     * Activation/désactivation d'une entité
     */
    #[Route('/{id}/toggle-status', name: 'toggle_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleStatus(int $id): JsonResponse
    {
        $result = $this->entityService->toggleEntityStatus($id);

        return $this->json($result);
    }

    /**
     * Configuration des paramètres d'une entité
     */
    #[Route('/{id}/settings', name: 'settings', requirements: ['id' => '\d+'])]
    public function settings(int $id, Request $request): Response
    {
        $entity = $this->entityService->getEntityById($id);
        
        if (!$entity) {
            throw $this->createNotFoundException('Entité publique introuvable.');
        }

        if ($request->isMethod('POST')) {
            $settings = $request->request->all();
            $result = $this->entityService->updateEntitySettings($id, $settings);
            
            if ($result['success']) {
                $this->addFlash('success', 'Paramètres mis à jour avec succès.');
            } else {
                $this->addFlash('error', 'Erreur lors de la mise à jour: ' . $result['message']);
            }
            
            return $this->redirectToRoute('admin_public_entities_settings', ['id' => $id]);
        }

        $entitySettings = $this->entityService->getEntitySettings($id);
        $configurationOptions = $this->entityService->getConfigurationOptions();

        return $this->render('public_entities/settings.html.twig', [
            'entity' => $entity,
            'settings' => $entitySettings,
            'configuration_options' => $configurationOptions
        ]);
    }

    /**
     * Cartographie géographique des entités
     */
    #[Route('/map', name: 'map')]
    public function map(Request $request): Response
    {
        $region = $request->get('region');
        $entityType = $request->get('entity_type');

        $mapData = $this->entityService->getEntitiesMapData($region, $entityType);
        $coverage = $this->entityService->getGeographicCoverage();

        return $this->render('public_entities/map.html.twig', [
            'map_data' => $mapData,
            'coverage' => $coverage,
            'regions' => $this->entityService->getRegions(),
            'entity_types' => $this->entityService->getEntityTypes()
        ]);
    }

    /**
     * Analyse comparative des entités
     */
    #[Route('/analytics', name: 'analytics')]
    public function analytics(Request $request): Response
    {
        $comparisonType = $request->get('comparison_type', 'efficiency');
        $selectedEntities = $request->get('entities', []);

        $analytics = $this->entityService->getComparativeAnalytics($comparisonType, $selectedEntities);
        $rankings = $this->entityService->getEntityRankings($comparisonType);
        $trends = $this->entityService->getPerformanceTrends();

        return $this->render('public_entities/analytics.html.twig', [
            'analytics' => $analytics,
            'rankings' => $rankings,
            'trends' => $trends,
            'comparison_type' => $comparisonType,
            'selected_entities' => $selectedEntities,
            'all_entities' => $this->entityService->getEntitiesForSelect()
        ]);
    }

    /**
     * Import en lot d'entités publiques
     */
    #[Route('/import', name: 'import', methods: ['GET', 'POST'])]
    public function import(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $uploadedFile = $request->files->get('entities_file');
            $importOptions = $request->request->all();
            
            if ($uploadedFile) {
                $result = $this->entityService->importEntities($uploadedFile, $importOptions);
                
                if ($result['success']) {
                    $this->addFlash('success', 'Import réalisé avec succès: ' . $result['imported_count'] . ' entités importées.');
                } else {
                    $this->addFlash('error', 'Erreur lors de l\'import: ' . $result['message']);
                }
            } else {
                $this->addFlash('error', 'Veuillez sélectionner un fichier à importer.');
            }
            
            return $this->redirectToRoute('admin_public_entities_index');
        }

        $importTemplate = $this->entityService->getImportTemplate();
        $importRules = $this->entityService->getImportRules();

        return $this->render('public_entities/import.html.twig', [
            'import_template' => $importTemplate,
            'import_rules' => $importRules
        ]);
    }

    /**
     * Export des entités publiques
     */
    #[Route('/export/{format}', name: 'export', methods: ['GET'])]
    public function export(string $format, Request $request): Response
    {
        $filters = [
            'type' => $request->get('type'),
            'status' => $request->get('status'),
            'region' => $request->get('region')
        ];

        return $this->entityService->exportEntities($format, $filters);
    }

    /**
     * Audit des changements sur les entités
     */
    #[Route('/audit', name: 'audit')]
    public function audit(Request $request): Response
    {
        $filters = [
            'entity_id' => $request->get('entity_id'),
            'action_type' => $request->get('action_type'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'user_id' => $request->get('user_id')
        ];

        $auditLogs = $this->entityService->getEntityAuditLogs($filters);
        $auditStatistics = $this->entityService->getAuditStatistics($filters);

        return $this->render('public_entities/audit.html.twig', [
            'audit_logs' => $auditLogs,
            'statistics' => $auditStatistics,
            'current_filters' => $filters,
            'entities' => $this->entityService->getEntitiesForSelect()
        ]);
    }

    /**
     * Suppression d'une entité publique
     */
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete'.$id, $request->get('_token'))) {
            $result = $this->entityService->deleteEntity($id);
            
            if ($result['success']) {
                $this->addFlash('success', 'Entité publique supprimée avec succès.');
            } else {
                $this->addFlash('error', 'Erreur lors de la suppression: ' . $result['message']);
            }
        }

        return $this->redirectToRoute('admin_public_entities_index');
    }

    /**
     * Duplication d'une entité existante
     */
    #[Route('/{id}/duplicate', name: 'duplicate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function duplicate(int $id): Response
    {
        $result = $this->entityService->duplicateEntity($id);
        
        if ($result['success']) {
            $this->addFlash('success', 'Entité dupliquée avec succès.');
            return $this->redirectToRoute('admin_public_entities_edit', ['id' => $result['new_id']]);
        } else {
            $this->addFlash('error', 'Erreur lors de la duplication: ' . $result['message']);
            return $this->redirectToRoute('admin_public_entities_show', ['id' => $id]);
        }
    }
}