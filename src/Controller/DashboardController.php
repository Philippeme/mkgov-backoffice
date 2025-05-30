<?php

namespace App\Controller;

use App\Service\DashboardDataService;
use App\Service\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur principal du tableau de bord administratif MK Gov
 * 
 * Ce contrôleur gère l'affichage du dashboard principal avec tous les composants
 * interactifs incluant les cartes, graphiques et statistiques en temps réel.
 */
#[Route('/admin/dashboard', name: 'admin_dashboard_')]
class DashboardController extends AbstractController
{
    public function __construct(
        private DashboardDataService $dashboardService,
        private StatisticsService $statisticsService
    ) {}

    /**
     * Page principale du tableau de bord
     */
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $statistics = $this->statisticsService->getOverallStatistics();
        $regions = $this->dashboardService->getCameroonRegions();
        $serviceCategories = $this->dashboardService->getServiceCategories();
        
        return $this->render('dashboard/index.html.twig', [
            'statistics' => $statistics,
            'regions' => $regions,
            'service_categories' => $serviceCategories,
            'current_year' => date('Y'),
            'available_years' => range(2020, (int)date('Y'))
        ]);
    }

    /**
     * API pour les données du dashboard avec filtres
     */
    #[Route('/api/data', name: 'api_data', methods: ['GET'])]
    public function getDashboardData(Request $request): JsonResponse
    {
        $filters = [
            'region' => $request->get('region'),
            'service_family' => $request->get('service_family'),
            'status' => $request->get('status'),
            'year' => $request->get('year', date('Y'))
        ];

        $data = [
            'statistics' => $this->statisticsService->getFilteredStatistics($filters),
            'chart_data' => $this->dashboardService->getChartData($filters),
            'map_data' => $this->dashboardService->getMapData($filters),
            'recent_requests' => $this->dashboardService->getRecentRequests($filters, 10)
        ];

        return $this->json($data);
    }

    /**
     * API pour les données de la carte interactive
     */
    #[Route('/api/map-data', name: 'api_map_data', methods: ['GET'])]
    public function getMapData(Request $request): JsonResponse
    {
        $filters = [
            'region' => $request->get('region'),
            'service_family' => $request->get('service_family'),
            'status' => $request->get('status'),
            'year' => $request->get('year', date('Y'))
        ];

        $mapData = $this->dashboardService->getDetailedMapData($filters);
        
        return $this->json($mapData);
    }

    /**
     * API pour les données des graphiques
     */
    #[Route('/api/charts', name: 'api_charts', methods: ['GET'])]
    public function getChartsData(Request $request): JsonResponse
    {
        $type = $request->get('type', 'all');
        $filters = [
            'region' => $request->get('region'),
            'service_family' => $request->get('service_family'),
            'status' => $request->get('status'),
            'year' => $request->get('year', date('Y'))
        ];

        $chartsData = $this->dashboardService->getChartsData($type, $filters);
        
        return $this->json($chartsData);
    }

    /**
     * Export des données du dashboard
     */
    #[Route('/export/{format}', name: 'export', methods: ['GET'])]
    public function exportData(string $format, Request $request): Response
    {
        $filters = [
            'region' => $request->get('region'),
            'service_family' => $request->get('service_family'),
            'status' => $request->get('status'),
            'year' => $request->get('year', date('Y'))
        ];

        $data = $this->dashboardService->getExportData($filters);
        
        switch ($format) {
            case 'csv':
                return $this->dashboardService->exportToCsv($data);
            case 'excel':
                return $this->dashboardService->exportToExcel($data);
            case 'pdf':
                return $this->dashboardService->exportToPdf($data);
            default:
                throw $this->createNotFoundException('Format d\'export non supporté');
        }
    }
}