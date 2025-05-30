<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Service de gestion des entités publiques camerounaises
 * 
 * Ce service centralise la logique métier pour la gestion des ministères,
 * départements, agences gouvernementales et autres entités publiques
 * participant au système de services électroniques MK Gov.
 */
class PublicEntityDataService
{
    /**
     * Récupère les entités paginées avec filtres appliqués
     */
    public function getPaginatedEntities(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $mockEntities = $this->generateMockEntities();
        $filteredEntities = $this->applyEntityFilters($mockEntities, $filters);
        
        $totalItems = count($filteredEntities);
        $totalPages = ceil($totalItems / $limit);
        $offset = ($page - 1) * $limit;
        $paginatedItems = array_slice($filteredEntities, $offset, $limit);
        
        return [
            'items' => $paginatedItems,
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'items_per_page' => $limit
        ];
    }

    /**
     * Génère les statistiques globales des entités publiques
     */
    public function getEntityStatistics(array $filters = []): array
    {
        $entities = $this->applyEntityFilters($this->generateMockEntities(), $filters);
        
        $stats = [
            'total_entities' => count($entities),
            'active_entities' => 0,
            'inactive_entities' => 0,
            'ministries' => 0,
            'departments' => 0,
            'agencies' => 0,
            'regional_offices' => 0
        ];
        
        foreach ($entities as $entity) {
            if ($entity['status'] === 'active') {
                $stats['active_entities']++;
            } else {
                $stats['inactive_entities']++;
            }
            
            $stats[$entity['type']]++;
        }
        
        return $stats;
    }

    /**
     * Récupère une entité par son identifiant
     */
    public function getEntityById(int $id): ?array
    {
        $entities = $this->generateMockEntities();
        
        foreach ($entities as $entity) {
            if ($entity['id'] === $id) {
                return $entity;
            }
        }
        
        return null;
    }

    /**
     * Crée une nouvelle entité publique
     */
    public function createEntity(array $data): array
    {
        // Validation des données obligatoires
        $required = ['name', 'type', 'region'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'message' => "Le champ {$field} est obligatoire"
                ];
            }
        }
        
        // Vérification de l'unicité du nom
        if ($this->entityNameExists($data['name'])) {
            return [
                'success' => false,
                'message' => 'Une entité avec ce nom existe déjà'
            ];
        }
        
        $newId = rand(1000, 9999);
        
        return [
            'success' => true,
            'id' => $newId,
            'message' => 'Entité publique créée avec succès'
        ];
    }

    /**
     * Met à jour une entité existante
     */
    public function updateEntity(int $id, array $data): array
    {
        $entity = $this->getEntityById($id);
        
        if (!$entity) {
            return [
                'success' => false,
                'message' => 'Entité introuvable'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Entité mise à jour avec succès'
        ];
    }

    /**
     * Supprime une entité publique
     */
    public function deleteEntity(int $id): array
    {
        $entity = $this->getEntityById($id);
        
        if (!$entity) {
            return [
                'success' => false,
                'message' => 'Entité introuvable'
            ];
        }
        
        // Vérification des dépendances
        if ($this->entityHasDependencies($id)) {
            return [
                'success' => false,
                'message' => 'Impossible de supprimer cette entité: elle a des services ou du personnel associés'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Entité supprimée avec succès'
        ];
    }

    /**
     * Active ou désactive une entité
     */
    public function toggleEntityStatus(int $id): array
    {
        $entity = $this->getEntityById($id);
        
        if (!$entity) {
            return [
                'success' => false,
                'message' => 'Entité introuvable'
            ];
        }
        
        $newStatus = $entity['status'] === 'active' ? 'inactive' : 'active';
        
        return [
            'success' => true,
            'new_status' => $newStatus,
            'message' => 'Statut de l\'entité modifié avec succès'
        ];
    }

    /**
     * Duplique une entité existante
     */
    public function duplicateEntity(int $id): array
    {
        $entity = $this->getEntityById($id);
        
        if (!$entity) {
            return [
                'success' => false,
                'message' => 'Entité introuvable'
            ];
        }
        
        $newId = rand(1000, 9999);
        
        return [
            'success' => true,
            'new_id' => $newId,
            'message' => 'Entité dupliquée avec succès'
        ];
    }

    /**
     * Récupère les types d'entités disponibles
     */
    public function getEntityTypes(): array
    {
        return [
            ['id' => 'ministries', 'name' => 'Ministries', 'name_fr' => 'Ministères', 'color' => '#dc3545'],
            ['id' => 'departments', 'name' => 'Departments', 'name_fr' => 'Départements', 'color' => '#fd7e14'],
            ['id' => 'agencies', 'name' => 'Agencies', 'name_fr' => 'Agences', 'color' => '#198754'],
            ['id' => 'regional_offices', 'name' => 'Regional Offices', 'name_fr' => 'Bureaux Régionaux', 'color' => '#0d6efd'],
            ['id' => 'public_establishments', 'name' => 'Public Establishments', 'name_fr' => 'Établissements Publics', 'color' => '#6610f2'],
            ['id' => 'local_authorities', 'name' => 'Local Authorities', 'name_fr' => 'Collectivités Locales', 'color' => '#6f42c1']
        ];
    }

    /**
     * Récupère les régions du Cameroun
     */
    public function getRegions(): array
    {
        return [
            ['id' => 'adamawa', 'name' => 'Adamawa', 'name_fr' => 'Adamaoua'],
            ['id' => 'centre', 'name' => 'Centre', 'name_fr' => 'Centre'],
            ['id' => 'east', 'name' => 'East', 'name_fr' => 'Est'],
            ['id' => 'far_north', 'name' => 'Far North', 'name_fr' => 'Extrême-Nord'],
            ['id' => 'littoral', 'name' => 'Littoral', 'name_fr' => 'Littoral'],
            ['id' => 'north', 'name' => 'North', 'name_fr' => 'Nord'],
            ['id' => 'northwest', 'name' => 'Northwest', 'name_fr' => 'Nord-Ouest'],
            ['id' => 'south', 'name' => 'South', 'name_fr' => 'Sud'],
            ['id' => 'southwest', 'name' => 'Southwest', 'name_fr' => 'Sud-Ouest'],
            ['id' => 'west', 'name' => 'West', 'name_fr' => 'Ouest']
        ];
    }

    /**
     * Récupère les entités parentes possibles
     */
    public function getParentEntities(): array
    {
        $entities = array_filter($this->generateMockEntities(), function($entity) {
            return in_array($entity['type'], ['ministries', 'departments']);
        });
        
        return array_map(function($entity) {
            return [
                'id' => $entity['id'],
                'name' => $entity['name'],
                'type' => $entity['type']
            ];
        }, $entities);
    }

    /**
     * Récupère les catégories de services
     */
    public function getServiceCategories(): array
    {
        return [
            ['id' => 'identity', 'name' => 'Identity & Civil Status'],
            ['id' => 'permits', 'name' => 'Permits & Licenses'],
            ['id' => 'certificates', 'name' => 'Certificates'],
            ['id' => 'registrations', 'name' => 'Registrations'],
            ['id' => 'authorizations', 'name' => 'Authorizations'],
            ['id' => 'declarations', 'name' => 'Declarations']
        ];
    }

    /**
     * Récupère les services d'une entité
     */
    public function getEntityServices(int $entityId): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Passport Application',
                'category' => 'identity',
                'status' => 'active',
                'requests_count' => 1247,
                'avg_processing_time' => '7 days',
                'success_rate' => 92.5
            ],
            [
                'id' => 2,
                'name' => 'Birth Certificate',
                'category' => 'certificates',
                'status' => 'active',
                'requests_count' => 856,
                'avg_processing_time' => '3 days',
                'success_rate' => 96.8
            ],
            [
                'id' => 3,
                'name' => 'National ID Card',
                'category' => 'identity',
                'status' => 'active',
                'requests_count' => 2341,
                'avg_processing_time' => '5 days',
                'success_rate' => 89.2
            ]
        ];
    }

    /**
     * Récupère le personnel d'une entité
     */
    public function getEntityStaff(int $entityId): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Jean Dubois',
                'position' => 'Director',
                'department' => 'Administration',
                'email' => 'jean.dubois@entity.gov.cm',
                'phone' => '+237 6XX XXX XXX',
                'start_date' => '2020-01-15',
                'status' => 'active'
            ],
            [
                'id' => 2,
                'name' => 'Marie Kouam',
                'position' => 'Deputy Director',
                'department' => 'Operations',
                'email' => 'marie.kouam@entity.gov.cm',
                'phone' => '+237 6XX XXX XXX',
                'start_date' => '2021-03-10',
                'status' => 'active'
            ],
            [
                'id' => 3,
                'name' => 'Paul Ngono',
                'position' => 'Department Head',
                'department' => 'Document Processing',
                'email' => 'paul.ngono@entity.gov.cm',
                'phone' => '+237 6XX XXX XXX',
                'start_date' => '2019-09-01',
                'status' => 'active'
            ]
        ];
    }

    /**
     * Récupère les métriques de performance d'une entité
     */
    public function getEntityPerformance(int $entityId): array
    {
        return [
            'overall_score' => 87.5,
            'efficiency_rating' => 'Good',
            'citizen_satisfaction' => 4.2,
            'processing_time_score' => 82.3,
            'quality_score' => 91.7,
            'monthly_requests' => 456,
            'completed_requests' => 421,
            'pending_requests' => 35,
            'staff_productivity' => 89.2,
            'compliance_rate' => 96.8
        ];
    }

    /**
     * Récupère les sous-entités d'une entité
     */
    public function getSubEntities(int $entityId): array
    {
        return [
            [
                'id' => 101,
                'name' => 'Regional Office Douala',
                'type' => 'regional_offices',
                'region' => 'Littoral',
                'status' => 'active',
                'staff_count' => 25,
                'services_count' => 8
            ],
            [
                'id' => 102,
                'name' => 'Regional Office Yaoundé',
                'type' => 'regional_offices',
                'region' => 'Centre',
                'status' => 'active',
                'staff_count' => 30,
                'services_count' => 10
            ]
        ];
    }

    /**
     * Récupère la vue hiérarchique des entités
     */
    public function getHierarchicalView(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Ministry of Territorial Administration',
                'type' => 'ministries',
                'level' => 0,
                'children' => [
                    [
                        'id' => 11,
                        'name' => 'Department of Civil Affairs',
                        'type' => 'departments',
                        'level' => 1,
                        'children' => [
                            [
                                'id' => 111,
                                'name' => 'Civil Registry Office',
                                'type' => 'agencies',
                                'level' => 2,
                                'children' => []
                            ]
                        ]
                    ]
                ]
            ],
            [
                'id' => 2,
                'name' => 'Ministry of Justice',
                'type' => 'ministries',
                'level' => 0,
                'children' => [
                    [
                        'id' => 21,
                        'name' => 'Department of Legal Affairs',
                        'type' => 'departments',
                        'level' => 1,
                        'children' => []
                    ]
                ]
            ]
        ];
    }

    /**
     * Récupère l'organigramme pour visualisation
     */
    public function getOrganizationChart(): array
    {
        return [
            'nodes' => [
                ['id' => '1', 'label' => 'Ministry of Territorial Administration', 'level' => 0],
                ['id' => '11', 'label' => 'Department of Civil Affairs', 'level' => 1],
                ['id' => '111', 'label' => 'Civil Registry Office', 'level' => 2],
                ['id' => '2', 'label' => 'Ministry of Justice', 'level' => 0],
                ['id' => '21', 'label' => 'Department of Legal Affairs', 'level' => 1]
            ],
            'links' => [
                ['source' => '1', 'target' => '11'],
                ['source' => '11', 'target' => '111'],
                ['source' => '2', 'target' => '21']
            ]
        ];
    }

    /**
     * Récupère les statistiques hiérarchiques
     */
    public function getHierarchyStatistics(): array
    {
        return [
            'total_levels' => 4,
            'avg_children_per_entity' => 2.8,
            'max_depth' => 3,
            'entities_without_parent' => 8,
            'entities_without_children' => 45
        ];
    }

    /**
     * Récupère les services disponibles pour attribution
     */
    public function getAvailableServices(): array
    {
        return [
            ['id' => 10, 'name' => 'Marriage Certificate', 'category' => 'certificates'],
            ['id' => 11, 'name' => 'Death Certificate', 'category' => 'certificates'],
            ['id' => 12, 'name' => 'Business License', 'category' => 'permits'],
            ['id' => 13, 'name' => 'Driving License', 'category' => 'permits'],
            ['id' => 14, 'name' => 'Property Title', 'category' => 'registrations']
        ];
    }

    /**
     * Attribue un service à une entité
     */
    public function assignServiceToEntity(int $entityId, int $serviceId): array
    {
        return [
            'success' => true,
            'message' => 'Service attribué avec succès à l\'entité'
        ];
    }

    /**
     * Retire un service d'une entité
     */
    public function removeServiceFromEntity(int $entityId, int $serviceId): array
    {
        return [
            'success' => true,
            'message' => 'Service retiré de l\'entité avec succès'
        ];
    }

    /**
     * Récupère les statistiques des services d'une entité
     */
    public function getServiceStatistics(int $entityId): array
    {
        return [
            'total_services' => 8,
            'active_services' => 7,
            'inactive_services' => 1,
            'avg_requests_per_service' => 156.3,
            'most_requested_service' => 'National ID Card',
            'best_performing_service' => 'Birth Certificate'
        ];
    }

    /**
     * Récupère les statistiques du personnel d'une entité
     */
    public function getStaffStatistics(int $entityId): array
    {
        return [
            'total_staff' => 45,
            'active_staff' => 42,
            'on_leave' => 3,
            'avg_experience' => '5.2 years',
            'staff_turnover_rate' => 8.5,
            'positions_filled' => 93.3
        ];
    }

    /**
     * Récupère les postes disponibles
     */
    public function getAvailablePositions(): array
    {
        return [
            'Director',
            'Deputy Director',
            'Department Head',
            'Senior Officer',
            'Officer',
            'Administrative Assistant',
            'Secretary',
            'IT Specialist',
            'Customer Service Representative'
        ];
    }

    /**
     * Ajoute du personnel à une entité
     */
    public function addStaffToEntity(int $entityId, array $staffData): array
    {
        if (empty($staffData['user_id']) || empty($staffData['position'])) {
            return [
                'success' => false,
                'message' => 'Utilisateur et poste sont obligatoires'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Personnel ajouté avec succès à l\'entité'
        ];
    }

    /**
     * Génère un rapport de performance
     */
    public function getPerformanceReport(string $period, ?string $entityType): array
    {
        return [
            'overall_performance' => [
                'avg_score' => 84.7,
                'top_performer' => 'Ministry of Education',
                'improvement_rate' => 12.3,
                'efficiency_trend' => 'increasing'
            ],
            'by_type' => [
                'ministries' => ['avg_score' => 88.2, 'count' => 15],
                'departments' => ['avg_score' => 82.5, 'count' => 45],
                'agencies' => ['avg_score' => 79.8, 'count' => 78],
                'regional_offices' => ['avg_score' => 81.3, 'count' => 120]
            ],
            'key_metrics' => [
                'citizen_satisfaction' => 4.1,
                'processing_efficiency' => 87.3,
                'staff_productivity' => 82.9,
                'digital_adoption' => 76.4
            ]
        ];
    }

    /**
     * Récupère les benchmarks de performance
     */
    public function getPerformanceBenchmarks(): array
    {
        return [
            'excellent' => ['min' => 90, 'color' => '#28a745'],
            'good' => ['min' => 80, 'color' => '#17a2b8'],
            'average' => ['min' => 70, 'color' => '#ffc107'],
            'poor' => ['min' => 0, 'color' => '#dc3545']
        ];
    }

    /**
     * Récupère les suggestions d'amélioration
     */
    public function getImprovementSuggestions(): array
    {
        return [
            [
                'entity' => 'Department of Civil Affairs',
                'area' => 'Processing Time',
                'current_score' => 65,
                'target_score' => 80,
                'suggestion' => 'Implement digital document verification system',
                'priority' => 'high'
            ],
            [
                'entity' => 'Regional Office Bamenda',
                'area' => 'Staff Training',
                'current_score' => 72,
                'target_score' => 85,
                'suggestion' => 'Organize quarterly training sessions on new procedures',
                'priority' => 'medium'
            ]
        ];
    }

    /**
     * Met à jour les paramètres d'une entité
     */
    public function updateEntitySettings(int $entityId, array $settings): array
    {
        return [
            'success' => true,
            'message' => 'Paramètres mis à jour avec succès'
        ];
    }

    /**
     * Récupère les paramètres d'une entité
     */
    public function getEntitySettings(int $entityId): array
    {
        return [
            'operating_hours' => '08:00-17:00',
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'holiday_schedule' => 'national_holidays',
            'notification_emails' => ['admin@entity.gov.cm'],
            'auto_approval_enabled' => false,
            'maximum_daily_requests' => 50,
            'priority_processing' => true,
            'citizen_feedback_enabled' => true
        ];
    }

    /**
     * Récupère les options de configuration
     */
    public function getConfigurationOptions(): array
    {
        return [
            'operating_hours' => [
                '08:00-16:00', '08:00-17:00', '09:00-17:00', '24/7'
            ],
            'holiday_schedules' => [
                'national_holidays' => 'National Holidays Only',
                'extended_holidays' => 'National + Regional Holidays',
                'custom' => 'Custom Schedule'
            ],
            'approval_workflows' => [
                'manual' => 'Manual Approval Required',
                'semi_auto' => 'Semi-Automatic',
                'auto' => 'Fully Automatic'
            ]
        ];
    }

    /**
     * Récupère les données cartographiques des entités
     */
    public function getEntitiesMapData(?string $region, ?string $entityType): array
    {
        $entities = $this->generateMockEntities();
        
        if ($region) {
            $entities = array_filter($entities, function($e) use ($region) {
                return $e['region'] === $region;
            });
        }
        
        if ($entityType) {
            $entities = array_filter($entities, function($e) use ($entityType) {
                return $e['type'] === $entityType;
            });
        }
        
        return array_map(function($entity) {
            return [
                'id' => $entity['id'],
                'name' => $entity['name'],
                'type' => $entity['type'],
                'coordinates' => $this->getEntityCoordinates($entity['region']),
                'services_count' => rand(3, 15),
                'staff_count' => rand(10, 100),
                'performance_score' => rand(70, 95)
            ];
        }, $entities);
    }

    /**
     * Récupère la couverture géographique
     */
    public function getGeographicCoverage(): array
    {
        return [
            'total_coverage' => 87.5,
            'urban_coverage' => 95.2,
            'rural_coverage' => 64.8,
            'underserved_areas' => ['Far North Rural', 'East Rural', 'Adamawa Rural']
        ];
    }

    /**
     * Génère des analyses comparatives
     */
    public function getComparativeAnalytics(string $comparisonType, array $selectedEntities): array
    {
        return [
            'comparison_type' => $comparisonType,
            'entities_data' => [
                [
                    'entity' => 'Ministry of Education',
                    'efficiency_score' => 92.5,
                    'citizen_satisfaction' => 4.3,
                    'processing_time' => 3.2,
                    'cost_per_request' => 2500
                ],
                [
                    'entity' => 'Ministry of Health',
                    'efficiency_score' => 87.8,
                    'citizen_satisfaction' => 4.1,
                    'processing_time' => 4.1,
                    'cost_per_request' => 3200
                ]
            ],
            'benchmarks' => [
                'efficiency_score' => 85.0,
                'citizen_satisfaction' => 4.0,
                'processing_time' => 5.0,
                'cost_per_request' => 3000
            ]
        ];
    }

    /**
     * Récupère les classements des entités
     */
    public function getEntityRankings(string $metric): array
    {
        return [
            ['rank' => 1, 'entity' => 'Ministry of Education', 'score' => 92.5],
            ['rank' => 2, 'entity' => 'Ministry of Finance', 'score' => 89.3],
            ['rank' => 3, 'entity' => 'Ministry of Health', 'score' => 87.8],
            ['rank' => 4, 'entity' => 'Ministry of Justice', 'score' => 85.2],
            ['rank' => 5, 'entity' => 'Ministry of Transport', 'score' => 82.7]
        ];
    }

    /**
     * Récupère les tendances de performance
     */
    public function getPerformanceTrends(): array
    {
        return [
            'monthly_trends' => [
                ['month' => 'Jan', 'score' => 82.1],
                ['month' => 'Feb', 'score' => 83.5],
                ['month' => 'Mar', 'score' => 84.2],
                ['month' => 'Apr', 'score' => 85.8],
                ['month' => 'May', 'score' => 87.3]
            ],
            'trend_direction' => 'increasing',
            'improvement_rate' => 6.2
        ];
    }

    /**
     * Récupère les entités pour les sélecteurs
     */
    public function getEntitiesForSelect(): array
    {
        $entities = $this->generateMockEntities();
        
        return array_map(function($entity) {
            return [
                'id' => $entity['id'],
                'name' => $entity['name'],
                'type' => $entity['type']
            ];
        }, $entities);
    }

    /**
     * Récupère les logs d'audit des entités
     */
    public function getEntityAuditLogs(array $filters = []): array
    {
        return [
            'logs' => [
                [
                    'id' => 1,
                    'entity_name' => 'Ministry of Education',
                    'action' => 'entity_updated',
                    'description' => 'Updated entity contact information',
                    'user' => 'Admin User',
                    'timestamp' => new \DateTime('-2 hours'),
                    'changes' => ['phone', 'email']
                ],
                [
                    'id' => 2,
                    'entity_name' => 'Regional Office Douala',
                    'action' => 'staff_added',
                    'description' => 'Added new staff member: John Doe',
                    'user' => 'HR Manager',
                    'timestamp' => new \DateTime('-5 hours'),
                    'changes' => ['staff_count']
                ]
            ],
            'total_count' => 2
        ];
    }

    /**
     * Récupère les statistiques d'audit
     */
    public function getAuditStatistics(array $filters = []): array
    {
        return [
            'total_events' => 456,
            'events_this_week' => 23,
            'most_active_entity' => 'Ministry of Education',
            'most_common_action' => 'entity_updated'
        ];
    }

    /**
     * Récupère le modèle d'import
     */
    public function getImportTemplate(): array
    {
        return [
            'filename' => 'entities_import_template.csv',
            'columns' => ['name', 'type', 'region', 'parent_entity', 'contact_email', 'contact_phone'],
            'sample_data' => [
                ['Regional Office Example', 'regional_offices', 'centre', 'Ministry of Education', 'contact@example.gov.cm', '+237XXXXXXXXX']
            ]
        ];
    }

    /**
     * Récupère les règles d'import
     */
    public function getImportRules(): array
    {
        return [
            'required_columns' => ['name', 'type', 'region'],
            'optional_columns' => ['parent_entity', 'contact_email', 'contact_phone', 'description'],
            'max_records_per_import' => 500,
            'supported_formats' => ['CSV', 'Excel'],
            'validation_rules' => [
                'name' => 'Unique, max 255 characters',
                'type' => 'Must be valid entity type',
                'region' => 'Must be valid Cameroon region'
            ]
        ];
    }

    /**
     * Importe des entités en lot
     */
    public function importEntities(UploadedFile $file, array $options): array
    {
        if (!in_array($file->getClientMimeType(), ['text/csv', 'application/csv'])) {
            return [
                'success' => false,
                'message' => 'Format de fichier non supporté. Utilisez un fichier CSV.'
            ];
        }
        
        $importedCount = rand(5, 25);
        
        return [
            'success' => true,
            'imported_count' => $importedCount,
            'message' => 'Import réalisé avec succès'
        ];
    }

    /**
     * Exporte les entités selon critères
     */
    public function exportEntities(string $format, array $filters): Response
    {
        $entities = $this->applyEntityFilters($this->generateMockEntities(), $filters);
        
        switch ($format) {
            case 'csv':
                return $this->exportEntitiesToCsv($entities);
            case 'excel':
                return $this->exportEntitiesToExcel($entities);
            default:
                throw new \InvalidArgumentException('Format d\'export non supporté');
        }
    }

    // Méthodes privées

    /**
     * Génère des entités publiques simulées
     */
    private function generateMockEntities(): array
    {
        $types = array_column($this->getEntityTypes(), 'id');
        $regions = array_column($this->getRegions(), 'id');
        $statuses = ['active', 'inactive'];
        
        $entities = [
            // Ministères
            ['id' => 1, 'name' => 'Ministry of Territorial Administration', 'type' => 'ministries', 'region' => 'centre'],
            ['id' => 2, 'name' => 'Ministry of Justice', 'type' => 'ministries', 'region' => 'centre'],
            ['id' => 3, 'name' => 'Ministry of Education', 'type' => 'ministries', 'region' => 'centre'],
            ['id' => 4, 'name' => 'Ministry of Health', 'type' => 'ministries', 'region' => 'centre'],
            ['id' => 5, 'name' => 'Ministry of Transport', 'type' => 'ministries', 'region' => 'centre'],
            
            // Départements
            ['id' => 11, 'name' => 'Department of Civil Affairs', 'type' => 'departments', 'region' => 'centre'],
            ['id' => 12, 'name' => 'Department of Legal Affairs', 'type' => 'departments', 'region' => 'centre'],
            ['id' => 13, 'name' => 'Department of Basic Education', 'type' => 'departments', 'region' => 'centre'],
            
            // Agences
            ['id' => 21, 'name' => 'Civil Registry Office', 'type' => 'agencies', 'region' => 'centre'],
            ['id' => 22, 'name' => 'National Security Agency', 'type' => 'agencies', 'region' => 'centre'],
            ['id' => 23, 'name' => 'Road Transport Agency', 'type' => 'agencies', 'region' => 'centre'],
        ];
        
        // Ajout d'entités générées
        for ($i = 50; $i <= 100; $i++) {
            $type = $types[array_rand($types)];
            $region = $regions[array_rand($regions)];
            
            $entities[] = [
                'id' => $i,
                'name' => 'Entity ' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'type' => $type,
                'region' => $region,
                'status' => $statuses[array_rand($statuses)],
                'description' => 'Description of entity ' . $i,
                'contact_email' => 'contact' . $i . '@gov.cm',
                'contact_phone' => '+237' . rand(600000000, 699999999),
                'created_at' => new \DateTime('-' . rand(30, 365) . ' days'),
                'staff_count' => rand(5, 100),
                'services_count' => rand(2, 20),
                'parent_entity_id' => rand(0, 100) < 30 ? rand(1, 25) : null
            ];
        }
        
        return $entities;
    }

    /**
     * Applique les filtres aux entités
     */
    private function applyEntityFilters(array $entities, array $filters): array
    {
        return array_filter($entities, function($entity) use ($filters) {
            if (!empty($filters['type']) && $entity['type'] !== $filters['type']) {
                return false;
            }
            
            if (!empty($filters['status']) && isset($entity['status']) && $entity['status'] !== $filters['status']) {
                return false;
            }
            
            if (!empty($filters['region']) && $entity['region'] !== $filters['region']) {
                return false;
            }
            
            if (!empty($filters['search'])) {
                $searchTerm = strtolower($filters['search']);
                if (strpos(strtolower($entity['name']), $searchTerm) === false) {
                    return false;
                }
            }
            
            return true;
        });
    }

    /**
     * Vérifie si un nom d'entité existe déjà
     */
    private function entityNameExists(string $name): bool
    {
        return rand(0, 100) < 5; // 5% de chance que le nom existe déjà
    }

    /**
     * Vérifie si une entité a des dépendances
     */
    private function entityHasDependencies(int $entityId): bool
    {
        return rand(0, 100) < 30; // 30% de chance d'avoir des dépendances
    }

    /**
     * Récupère les coordonnées géographiques d'une région
     */
    private function getEntityCoordinates(string $region): array
    {
        $coordinates = [
            'adamawa' => [7.3167, 13.5833],
            'centre' => [3.8667, 11.5167],
            'east' => [4.5833, 13.6833],
            'far_north' => [10.5833, 14.3167],
            'littoral' => [4.0833, 9.7],
            'north' => [9.3, 13.3833],
            'northwest' => [5.9667, 10.15],
            'south' => [2.9167, 11.15],
            'southwest' => [4.1567, 9.2842],
            'west' => [5.4667, 10.4167]
        ];
        
        return $coordinates[$region] ?? [3.8667, 11.5167]; // Défaut: Yaoundé
    }

    /**
     * Export CSV des entités
     */
    private function exportEntitiesToCsv(array $entities): Response
    {
        $filename = 'entities_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $content = "ID,Name,Type,Region,Status,Contact Email,Staff Count\n";
        
        foreach ($entities as $entity) {
            $content .= sprintf(
                "%d,%s,%s,%s,%s,%s,%d\n",
                $entity['id'],
                $entity['name'],
                $entity['type'],
                $entity['region'],
                $entity['status'] ?? 'active',
                $entity['contact_email'] ?? '',
                $entity['staff_count'] ?? 0
            );
        }
        
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        return $response;
    }

    /**
     * Export Excel simulé des entités
     */
    private function exportEntitiesToExcel(array $entities): Response
    {
        $response = $this->exportEntitiesToCsv($entities);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="entities_export_' . date('Y-m-d_H-i-s') . '.xlsx"');
        
        return $response;
    }
}