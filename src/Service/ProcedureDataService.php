<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Service de gestion des données des procédures administratives
 * 
 * Ce service centralise la logique métier pour la gestion des procédures
 * gouvernementales, leurs workflows et leurs métriques de performance
 * dans le cadre du système d'administration MK Gov.
 */
class ProcedureDataService
{
    /**
     * Récupère les procédures paginées avec filtres appliqués
     */
    public function getPaginatedProcedures(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $mockProcedures = $this->generateMockProcedures();
        $filteredProcedures = $this->applyProcedureFilters($mockProcedures, $filters);
        
        $totalItems = count($filteredProcedures);
        $totalPages = ceil($totalItems / $limit);
        $offset = ($page - 1) * $limit;
        $paginatedItems = array_slice($filteredProcedures, $offset, $limit);
        
        return [
            'items' => $paginatedItems,
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'items_per_page' => $limit
        ];
    }

    /**
     * Génère les statistiques globales des procédures
     */
    public function getProcedureStatistics(array $filters = []): array
    {
        $procedures = $this->applyProcedureFilters($this->generateMockProcedures(), $filters);
        
        $stats = [
            'total' => count($procedures),
            'active' => 0,
            'draft' => 0,
            'archived' => 0,
            'avg_completion_time' => 0,
            'success_rate' => 0
        ];
        
        $totalCompletionTime = 0;
        $completedProcedures = 0;
        
        foreach ($procedures as $procedure) {
            $stats[$procedure['status']]++;
            if (isset($procedure['completion_time'])) {
                $totalCompletionTime += $procedure['completion_time'];
                $completedProcedures++;
            }
        }
        
        if ($completedProcedures > 0) {
            $stats['avg_completion_time'] = round($totalCompletionTime / $completedProcedures, 1);
            $stats['success_rate'] = round(($stats['active'] / $stats['total']) * 100, 1);
        }
        
        return $stats;
    }

    /**
     * Récupère une procédure par son identifiant
     */
    public function getProcedureById(int $id): ?array
    {
        $procedures = $this->generateMockProcedures();
        
        foreach ($procedures as $procedure) {
            if ($procedure['id'] === $id) {
                return $procedure;
            }
        }
        
        return null;
    }

    /**
     * Crée une nouvelle procédure dans le système
     */
    public function createProcedure(array $data): array
    {
        if (empty($data['name']) || empty($data['category'])) {
            return [
                'success' => false,
                'message' => 'Le nom et la catégorie de la procédure sont obligatoires'
            ];
        }
        
        $newId = rand(1000, 9999);
        
        return [
            'success' => true,
            'id' => $newId,
            'message' => 'Procédure créée avec succès'
        ];
    }

    /**
     * Met à jour une procédure existante
     */
    public function updateProcedure(int $id, array $data): array
    {
        $procedure = $this->getProcedureById($id);
        
        if (!$procedure) {
            return [
                'success' => false,
                'message' => 'Procédure introuvable'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Procédure mise à jour avec succès'
        ];
    }

    /**
     * Supprime une procédure du système
     */
    public function deleteProcedure(int $id): array
    {
        $procedure = $this->getProcedureById($id);
        
        if (!$procedure) {
            return [
                'success' => false,
                'message' => 'Procédure introuvable'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Procédure supprimée avec succès'
        ];
    }

    /**
     * Active ou désactive une procédure
     */
    public function toggleProcedureStatus(int $id): array
    {
        $procedure = $this->getProcedureById($id);
        
        if (!$procedure) {
            return [
                'success' => false,
                'message' => 'Procédure introuvable'
            ];
        }
        
        $newStatus = $procedure['status'] === 'active' ? 'draft' : 'active';
        
        return [
            'success' => true,
            'new_status' => $newStatus,
            'message' => 'Statut de la procédure modifié avec succès'
        ];
    }

    /**
     * Duplique une procédure existante
     */
    public function duplicateProcedure(int $id): array
    {
        $procedure = $this->getProcedureById($id);
        
        if (!$procedure) {
            return [
                'success' => false,
                'message' => 'Procédure introuvable'
            ];
        }
        
        $newId = rand(1000, 9999);
        
        return [
            'success' => true,
            'new_id' => $newId,
            'message' => 'Procédure dupliquée avec succès'
        ];
    }

    /**
     * Récupère les catégories de procédures disponibles
     */
    public function getProcedureCategories(): array
    {
        return [
            ['id' => 'identity', 'name' => 'Identity Documents', 'color' => '#1a73e8'],
            ['id' => 'civil_status', 'name' => 'Civil Status', 'color' => '#34a853'],
            ['id' => 'transport', 'name' => 'Transport & Mobility', 'color' => '#ff6d01'],
            ['id' => 'education', 'name' => 'Education & Training', 'color' => '#9c27b0'],
            ['id' => 'business', 'name' => 'Business & Commerce', 'color' => '#607d8b'],
            ['id' => 'health', 'name' => 'Health & Social', 'color' => '#f44336'],
            ['id' => 'property', 'name' => 'Property & Land', 'color' => '#795548'],
            ['id' => 'taxation', 'name' => 'Taxation & Finance', 'color' => '#ff9800']
        ];
    }

    /**
     * Récupère les modèles de procédures prédéfinis
     */
    public function getProcedureTemplates(): array
    {
        return [
            ['id' => 'simple', 'name' => 'Simple Process', 'steps' => 3],
            ['id' => 'standard', 'name' => 'Standard Process', 'steps' => 5],
            ['id' => 'complex', 'name' => 'Complex Process', 'steps' => 8],
            ['id' => 'review_approval', 'name' => 'Review & Approval', 'steps' => 4],
            ['id' => 'verification', 'name' => 'Document Verification', 'steps' => 6]
        ];
    }

    /**
     * Récupère les entités publiques responsables
     */
    public function getPublicEntities(): array
    {
        return [
            ['id' => 'dgsn', 'name' => 'Direction Générale de la Sûreté Nationale'],
            ['id' => 'civil_registry', 'name' => 'Service de l\'État Civil'],
            ['id' => 'transport_ministry', 'name' => 'Ministère des Transports'],
            ['id' => 'education_ministry', 'name' => 'Ministère de l\'Éducation'],
            ['id' => 'health_ministry', 'name' => 'Ministère de la Santé'],
            ['id' => 'finance_ministry', 'name' => 'Ministère des Finances'],
            ['id' => 'justice_ministry', 'name' => 'Ministère de la Justice']
        ];
    }

    /**
     * Récupère les étapes d'une procédure spécifique
     */
    public function getProcedureSteps(int $procedureId): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Document Submission',
                'description' => 'Citizen submits required documents',
                'order' => 1,
                'estimated_duration' => '1 day',
                'responsible_entity' => 'Citizen',
                'status' => 'active'
            ],
            [
                'id' => 2,
                'name' => 'Initial Review',
                'description' => 'Administrative review of submitted documents',
                'order' => 2,
                'estimated_duration' => '2 days',
                'responsible_entity' => 'Admin Officer',
                'status' => 'active'
            ],
            [
                'id' => 3,
                'name' => 'Verification Process',
                'description' => 'Document verification and background check',
                'order' => 3,
                'estimated_duration' => '5 days',
                'responsible_entity' => 'Verification Unit',
                'status' => 'active'
            ],
            [
                'id' => 4,
                'name' => 'Approval Decision',
                'description' => 'Final approval or rejection decision',
                'order' => 4,
                'estimated_duration' => '1 day',
                'responsible_entity' => 'Senior Officer',
                'status' => 'active'
            ],
            [
                'id' => 5,
                'name' => 'Document Issuance',
                'description' => 'Generation and delivery of final document',
                'order' => 5,
                'estimated_duration' => '2 days',
                'responsible_entity' => 'Issuance Office',
                'status' => 'active'
            ]
        ];
    }

    /**
     * Récupère le workflow associé à une procédure
     */
    public function getProcedureWorkflow(int $procedureId): array
    {
        return [
            'id' => 1,
            'name' => 'Standard Document Processing Workflow',
            'description' => 'Standard workflow for document processing procedures',
            'version' => '1.2',
            'status' => 'active',
            'created_at' => '2024-01-01 10:00:00',
            'updated_at' => '2024-01-15 14:30:00'
        ];
    }

    /**
     * Récupère tous les workflows du système
     */
    public function getAllWorkflows(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Standard Document Processing',
                'description' => 'Workflow for standard document processing',
                'procedures_count' => 15,
                'status' => 'active',
                'efficiency_score' => 87.5
            ],
            [
                'id' => 2,
                'name' => 'Complex Verification Process',
                'description' => 'Enhanced workflow for complex verifications',
                'procedures_count' => 8,
                'status' => 'active',
                'efficiency_score' => 92.1
            ],
            [
                'id' => 3,
                'name' => 'Fast Track Processing',
                'description' => 'Expedited workflow for urgent requests',
                'procedures_count' => 5,
                'status' => 'active',
                'efficiency_score' => 95.3
            ]
        ];
    }

    /**
     * Récupère les workflows actuellement actifs
     */
    public function getActiveWorkflows(): array
    {
        return array_filter($this->getAllWorkflows(), function($workflow) {
            return $workflow['status'] === 'active';
        });
    }

    /**
     * Génère les statistiques des workflows
     */
    public function getWorkflowStatistics(): array
    {
        $workflows = $this->getAllWorkflows();
        
        return [
            'total_workflows' => count($workflows),
            'active_workflows' => count($this->getActiveWorkflows()),
            'avg_efficiency' => round(array_sum(array_column($workflows, 'efficiency_score')) / count($workflows), 1),
            'total_procedures_managed' => array_sum(array_column($workflows, 'procedures_count'))
        ];
    }

    /**
     * Crée un nouveau workflow
     */
    public function createWorkflow(array $data): array
    {
        if (empty($data['name']) || empty($data['description'])) {
            return [
                'success' => false,
                'message' => 'Le nom et la description du workflow sont obligatoires'
            ];
        }
        
        return [
            'success' => true,
            'id' => rand(100, 999),
            'message' => 'Workflow créé avec succès'
        ];
    }

    /**
     * Récupère un workflow par son identifiant
     */
    public function getWorkflowById(int $id): ?array
    {
        $workflows = $this->getAllWorkflows();
        
        foreach ($workflows as $workflow) {
            if ($workflow['id'] === $id) {
                return $workflow;
            }
        }
        
        return null;
    }

    /**
     * Récupère les nœuds d'un workflow pour l'éditeur visuel
     */
    public function getWorkflowNodes(int $workflowId): array
    {
        return [
            [
                'id' => 'start',
                'type' => 'start',
                'label' => 'Start',
                'x' => 100,
                'y' => 100
            ],
            [
                'id' => 'review',
                'type' => 'task',
                'label' => 'Document Review',
                'x' => 300,
                'y' => 100
            ],
            [
                'id' => 'decision',
                'type' => 'decision',
                'label' => 'Approval Decision',
                'x' => 500,
                'y' => 100
            ],
            [
                'id' => 'approve',
                'type' => 'task',
                'label' => 'Approve & Issue',
                'x' => 700,
                'y' => 50
            ],
            [
                'id' => 'reject',
                'type' => 'task',
                'label' => 'Reject & Notify',
                'x' => 700,
                'y' => 150
            ],
            [
                'id' => 'end',
                'type' => 'end',
                'label' => 'End',
                'x' => 900,
                'y' => 100
            ]
        ];
    }

    /**
     * Récupère les connexions entre nœuds d'un workflow
     */
    public function getWorkflowConnections(int $workflowId): array
    {
        return [
            ['from' => 'start', 'to' => 'review'],
            ['from' => 'review', 'to' => 'decision'],
            ['from' => 'decision', 'to' => 'approve', 'condition' => 'approved'],
            ['from' => 'decision', 'to' => 'reject', 'condition' => 'rejected'],
            ['from' => 'approve', 'to' => 'end'],
            ['from' => 'reject', 'to' => 'end']
        ];
    }

    /**
     * Sauvegarde les modifications d'un workflow
     */
    public function saveWorkflow(int $id, array $workflowData): array
    {
        return [
            'success' => true,
            'message' => 'Workflow sauvegardé avec succès'
        ];
    }

    /**
     * Récupère toutes les procédures pour les sélecteurs
     */
    public function getAllProcedures(): array
    {
        return array_map(function($procedure) {
            return [
                'id' => $procedure['id'],
                'name' => $procedure['name'],
                'category' => $procedure['category']
            ];
        }, $this->generateMockProcedures());
    }

    /**
     * Génère des analyses de performance des procédures
     */
    public function getProcedureAnalytics(string $period, ?string $category): array
    {
        return [
            'completion_rate' => 89.5,
            'avg_processing_time' => 7.2,
            'citizen_satisfaction' => 4.1,
            'bottleneck_count' => 3,
            'efficiency_trend' => 'increasing'
        ];
    }

    /**
     * Récupère les métriques de performance détaillées
     */
    public function getPerformanceMetrics(string $period): array
    {
        return [
            'total_processed' => 1456,
            'avg_time_per_step' => 1.8,
            'peak_processing_hours' => '10:00-12:00',
            'resource_utilization' => 76.3,
            'error_rate' => 2.1
        ];
    }

    /**
     * Identifie les goulots d'étranglement dans les procédures
     */
    public function identifyBottlenecks(): array
    {
        return [
            [
                'step_name' => 'Document Verification',
                'avg_delay' => '2.3 days',
                'frequency' => 'High',
                'impact' => 'Critical'
            ],
            [
                'step_name' => 'Senior Officer Approval',
                'avg_delay' => '1.1 days',
                'frequency' => 'Medium',
                'impact' => 'Moderate'
            ]
        ];
    }

    /**
     * Export des procédures dans différents formats
     */
    public function exportProcedures(string $format, array $filters): Response
    {
        $procedures = $this->applyProcedureFilters($this->generateMockProcedures(), $filters);
        
        switch ($format) {
            case 'csv':
                return $this->exportProceduresToCsv($procedures);
            case 'excel':
                return $this->exportProceduresToExcel($procedures);
            default:
                throw new \InvalidArgumentException('Format d\'export non supporté');
        }
    }

    /**
     * Import de procédures depuis un fichier
     */
    public function importProcedures(UploadedFile $file): array
    {
        if (!in_array($file->getClientMimeType(), ['text/csv', 'application/csv'])) {
            return [
                'success' => false,
                'message' => 'Format de fichier non supporté. Utilisez un fichier CSV.'
            ];
        }
        
        // Simulation d'import
        $importedCount = rand(5, 25);
        
        return [
            'success' => true,
            'imported_count' => $importedCount,
            'message' => 'Import réalisé avec succès'
        ];
    }

    /**
     * Génère des données de procédures simulées pour les tests
     */
    private function generateMockProcedures(): array
    {
        $categories = array_column($this->getProcedureCategories(), 'id');
        $statuses = ['active', 'draft', 'archived'];
        $complexities = ['simple', 'medium', 'complex'];
        
        $procedures = [];
        
        for ($i = 1; $i <= 50; $i++) {
            $procedures[] = [
                'id' => $i,
                'name' => 'Procedure ' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'description' => 'Description of procedure ' . $i,
                'category' => $categories[array_rand($categories)],
                'status' => $statuses[array_rand($statuses)],
                'complexity' => $complexities[array_rand($complexities)],
                'estimated_duration' => rand(1, 14) . ' days',
                'completion_time' => rand(5, 20),
                'success_rate' => rand(80, 98),
                'created_at' => new \DateTime('-' . rand(1, 365) . ' days'),
                'updated_at' => new \DateTime('-' . rand(1, 30) . ' days')
            ];
        }
        
        return $procedures;
    }

    /**
     * Applique les filtres aux procédures
     */
    private function applyProcedureFilters(array $procedures, array $filters): array
    {
        return array_filter($procedures, function($procedure) use ($filters) {
            if (!empty($filters['category']) && $procedure['category'] !== $filters['category']) {
                return false;
            }
            
            if (!empty($filters['status']) && $procedure['status'] !== $filters['status']) {
                return false;
            }
            
            if (!empty($filters['complexity']) && $procedure['complexity'] !== $filters['complexity']) {
                return false;
            }
            
            if (!empty($filters['search'])) {
                $searchTerm = strtolower($filters['search']);
                if (strpos(strtolower($procedure['name']), $searchTerm) === false &&
                    strpos(strtolower($procedure['description']), $searchTerm) === false) {
                    return false;
                }
            }
            
            return true;
        });
    }

    /**
     * Export au format CSV
     */
    private function exportProceduresToCsv(array $procedures): Response
    {
        $filename = 'procedures_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $content = "ID,Name,Category,Status,Complexity,Duration,Success Rate\n";
        
        foreach ($procedures as $procedure) {
            $content .= sprintf(
                "%d,%s,%s,%s,%s,%s,%.1f%%\n",
                $procedure['id'],
                $procedure['name'],
                $procedure['category'],
                $procedure['status'],
                $procedure['complexity'],
                $procedure['estimated_duration'],
                $procedure['success_rate']
            );
        }
        
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        return $response;
    }

    /**
     * Export au format Excel simulé
     */
    private function exportProceduresToExcel(array $procedures): Response
    {
        $response = $this->exportProceduresToCsv($procedures);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="procedures_export_' . date('Y-m-d_H-i-s') . '.xlsx"');
        
        return $response;
    }
}