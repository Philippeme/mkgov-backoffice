<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;

/**
 * Service de gestion des données du tableau de bord
 * 
 * Ce service centralise la logique métier pour la récupération et le traitement
 * des données affichées sur le tableau de bord principal de l'administration.
 */
class DashboardDataService
{
    /**
     * Récupère les régions du Cameroun avec leurs coordonnées géographiques
     */
    public function getCameroonRegions(): array
    {
        return [
            [
                'id' => 'adamawa',
                'name' => 'Adamawa',
                'name_fr' => 'Adamaoua',
                'capital' => 'Ngaoundéré',
                'coordinates' => [7.3167, 13.5833],
                'population' => 1010000,
                'area' => 63701,
                'requests_count' => 450
            ],
            [
                'id' => 'centre',
                'name' => 'Centre',
                'name_fr' => 'Centre',
                'capital' => 'Yaoundé',
                'coordinates' => [3.8667, 11.5167],
                'population' => 4665000,
                'area' => 68953,
                'requests_count' => 2890
            ],
            [
                'id' => 'east',
                'name' => 'East',
                'name_fr' => 'Est',
                'capital' => 'Bertoua',
                'coordinates' => [4.5833, 13.6833],
                'population' => 802000,
                'area' => 109002,
                'requests_count' => 280
            ],
            [
                'id' => 'far_north',
                'name' => 'Far North',
                'name_fr' => 'Extrême-Nord',
                'capital' => 'Maroua',
                'coordinates' => [10.5833, 14.3167],
                'population' => 3720000,
                'area' => 34263,
                'requests_count' => 1250
            ],
            [
                'id' => 'littoral',
                'name' => 'Littoral',
                'name_fr' => 'Littoral',
                'capital' => 'Douala',
                'coordinates' => [4.0833, 9.7],
                'population' => 3240000,
                'area' => 20248,
                'requests_count' => 3456
            ],
            [
                'id' => 'north',
                'name' => 'North',
                'name_fr' => 'Nord',
                'capital' => 'Garoua',
                'coordinates' => [9.3, 13.3833],
                'population' => 2050000,
                'area' => 66090,
                'requests_count' => 780
            ],
            [
                'id' => 'northwest',
                'name' => 'Northwest',
                'name_fr' => 'Nord-Ouest',
                'capital' => 'Bamenda',
                'coordinates' => [5.9667, 10.15],
                'population' => 1840000,
                'area' => 17300,
                'requests_count' => 890
            ],
            [
                'id' => 'south',
                'name' => 'South',
                'name_fr' => 'Sud',
                'capital' => 'Ebolowa',
                'coordinates' => [2.9167, 11.15],
                'population' => 690000,
                'area' => 47191,
                'requests_count' => 320
            ],
            [
                'id' => 'southwest',
                'name' => 'Southwest',
                'name_fr' => 'Sud-Ouest',
                'capital' => 'Buea',
                'coordinates' => [4.1567, 9.2842],
                'population' => 1390000,
                'area' => 25410,
                'requests_count' => 650
            ],
            [
                'id' => 'west',
                'name' => 'West',
                'name_fr' => 'Ouest',
                'capital' => 'Bafoussam',
                'coordinates' => [5.4667, 10.4167],
                'population' => 1930000,
                'area' => 13892,
                'requests_count' => 1120
            ]
        ];
    }

    /**
     * Récupère les catégories de services gouvernementaux
     */
    public function getServiceCategories(): array
    {
        return [
            [
                'id' => 'police_justice',
                'name' => 'Police & Justice',
                'name_fr' => 'Police & Justice',
                'color' => '#1a73e8',
                'icon' => 'fas fa-balance-scale',
                'requests_count' => 3450,
                'completion_rate' => 85.2
            ],
            [
                'id' => 'family',
                'name' => 'Family Services',
                'name_fr' => 'Services Familiaux',
                'color' => '#34a853',
                'icon' => 'fas fa-users',
                'requests_count' => 2890,
                'completion_rate' => 92.1
            ],
            [
                'id' => 'transport',
                'name' => 'Transport',
                'name_fr' => 'Transport',
                'color' => '#ff6d01',
                'icon' => 'fas fa-car',
                'requests_count' => 1560,
                'completion_rate' => 78.4
            ],
            [
                'id' => 'education',
                'name' => 'Education',
                'name_fr' => 'Éducation',
                'color' => '#9c27b0',
                'icon' => 'fas fa-graduation-cap',
                'requests_count' => 890,
                'completion_rate' => 88.7
            ],
            [
                'id' => 'business',
                'name' => 'Business',
                'name_fr' => 'Entreprises',
                'color' => '#607d8b',
                'icon' => 'fas fa-briefcase',
                'requests_count' => 1230,
                'completion_rate' => 82.3
            ],
            [
                'id' => 'health',
                'name' => 'Health',
                'name_fr' => 'Santé',
                'color' => '#f44336',
                'icon' => 'fas fa-heartbeat',
                'requests_count' => 567,
                'completion_rate' => 91.5
            ]
        ];
    }

    /**
     * Génère les données pour les graphiques du tableau de bord
     */
    public function getChartData(array $filters = []): array
    {
        return [
            'requests_by_month' => $this->getRequestsByMonth($filters),
            'requests_by_service' => $this->getRequestsByService($filters),
            'requests_by_status' => $this->getRequestsByStatus($filters),
            'completion_rates' => $this->getCompletionRates($filters),
            'regional_distribution' => $this->getRegionalDistribution($filters)
        ];
    }

    /**
     * Récupère les données cartographiques avec marqueurs
     */
    public function getMapData(array $filters = []): array
    {
        $regions = $this->getCameroonRegions();
        $mapData = [];

        foreach ($regions as $region) {
            $requestCount = $this->applyFiltersToRegion($region, $filters);
            
            if ($requestCount > 0) {
                $mapData[] = [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [$region['coordinates'][1], $region['coordinates'][0]]
                    ],
                    'properties' => [
                        'name' => $region['name'],
                        'name_fr' => $region['name_fr'],
                        'capital' => $region['capital'],
                        'requests_count' => $requestCount,
                        'population' => $region['population'],
                        'density' => round($requestCount / ($region['population'] / 100000), 2)
                    ]
                ];
            }
        }

        return $mapData;
    }

    /**
     * Récupère les données détaillées pour la carte interactive
     */
    public function getDetailedMapData(array $filters = []): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => $this->getMapData($filters),
            'statistics' => [
                'total_requests' => array_sum(array_column($this->getMapData($filters), 'properties.requests_count')),
                'total_regions' => count($this->getMapData($filters)),
                'average_density' => $this->calculateAverageDensity($filters)
            ]
        ];
    }

    /**
     * Récupère les demandes récentes
     */
    public function getRecentRequests(array $filters = [], int $limit = 10): array
    {
        // Simulation de données récentes
        $recentRequests = [];
        $services = ['Passport', 'Birth Certificate', 'National ID', 'Driving License'];
        $statuses = ['pending', 'processing', 'completed', 'rejected'];
        $regions = array_column($this->getCameroonRegions(), 'name');

        for ($i = 0; $i < $limit; $i++) {
            $recentRequests[] = [
                'id' => 'REQ-' . str_pad($i + 1000, 6, '0', STR_PAD_LEFT),
                'service' => $services[array_rand($services)],
                'citizen_name' => $this->generateRandomName(),
                'region' => $regions[array_rand($regions)],
                'status' => $statuses[array_rand($statuses)],
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 720) . ' hours')),
                'priority' => rand(1, 5)
            ];
        }

        return $recentRequests;
    }

    /**
     * Prépare les données pour l'export
     */
    public function getExportData(array $filters = []): array
    {
        return [
            'summary' => $this->getChartData($filters),
            'regions' => $this->getCameroonRegions(),
            'services' => $this->getServiceCategories(),
            'generated_at' => date('Y-m-d H:i:s'),
            'filters_applied' => $filters
        ];
    }

    // Méthodes privées pour le traitement des données

    private function getRequestsByMonth(array $filters): array
    {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $data = [];

        foreach ($months as $month) {
            $data[] = [
                'month' => $month,
                'requests' => rand(50, 500),
                'completed' => rand(40, 450)
            ];
        }

        return $data;
    }

    private function getRequestsByService(array $filters): array
    {
        $services = $this->getServiceCategories();
        $data = [];

        foreach ($services as $service) {
            $data[] = [
                'service' => $service['name'],
                'count' => $service['requests_count'],
                'percentage' => round(($service['requests_count'] / 12000) * 100, 1)
            ];
        }

        return $data;
    }

    private function getRequestsByStatus(array $filters): array
    {
        return [
            ['status' => 'Completed', 'count' => 8450, 'color' => '#28a745'],
            ['status' => 'Processing', 'count' => 2340, 'color' => '#007bff'],
            ['status' => 'Pending', 'count' => 890, 'color' => '#ffc107'],
            ['status' => 'Rejected', 'count' => 320, 'color' => '#dc3545']
        ];
    }

    private function getCompletionRates(array $filters): array
    {
        $regions = $this->getCameroonRegions();
        $data = [];

        foreach ($regions as $region) {
            $data[] = [
                'region' => $region['name'],
                'rate' => rand(75, 95)
            ];
        }

        return $data;
    }

    private function getRegionalDistribution(array $filters): array
    {
        $regions = $this->getCameroonRegions();
        return array_map(function($region) {
            return [
                'region' => $region['name'],
                'requests' => $region['requests_count'],
                'coordinates' => $region['coordinates']
            ];
        }, $regions);
    }

    private function applyFiltersToRegion(array $region, array $filters): int
    {
        $baseCount = $region['requests_count'];
        
        // Application des filtres (simulation)
        if (isset($filters['service_family']) && $filters['service_family']) {
            $baseCount = (int)($baseCount * 0.7);
        }
        
        if (isset($filters['status']) && $filters['status']) {
            $baseCount = (int)($baseCount * 0.8);
        }

        return $baseCount;
    }

    private function calculateAverageDensity(array $filters): float
    {
        $mapData = $this->getMapData($filters);
        $densities = array_column(array_column($mapData, 'properties'), 'density');
        
        return count($densities) > 0 ? round(array_sum($densities) / count($densities), 2) : 0.0;
    }

    private function generateRandomName(): string
    {
        $firstNames = ['Jean', 'Marie', 'Paul', 'Sophie', 'Pierre', 'Anne', 'Michel', 'Catherine'];
        $lastNames = ['Durand', 'Martin', 'Bernard', 'Thomas', 'Robert', 'Richard', 'Petit', 'Dubois'];
        
        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }
}