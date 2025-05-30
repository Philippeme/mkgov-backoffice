<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Service de gestion des données des demandes citoyennes
 * 
 * Ce service centralise toute la logique métier pour les opérations CRUD
 * sur les demandes soumises par les citoyens via l'application MK Gov.
 */
class RequestDataService
{
    /**
     * Récupère les demandes paginées avec filtres
     */
    public function getPaginatedRequests(int $page = 1, int $limit = 20, array $filters = []): array
    {
        // Simulation de données de demandes
        $mockRequests = $this->generateMockRequests();
        
        // Application des filtres
        $filteredRequests = $this->applyFilters($mockRequests, $filters);
        
        // Pagination
        $totalItems = count($filteredRequests);
        $totalPages = ceil($totalItems / $limit);
        $offset = ($page - 1) * $limit;
        $paginatedItems = array_slice($filteredRequests, $offset, $limit);
        
        return [
            'items' => $paginatedItems,
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'items_per_page' => $limit
        ];
    }

    /**
     * Récupère les statistiques des demandes
     */
    public function getRequestStatistics(array $filters = []): array
    {
        $mockRequests = $this->generateMockRequests();
        $filteredRequests = $this->applyFilters($mockRequests, $filters);
        
        $stats = [
            'total' => count($filteredRequests),
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'rejected' => 0
        ];
        
        foreach ($filteredRequests as $request) {
            $stats[$request['status']]++;
        }
        
        return $stats;
    }

    /**
     * Récupère une demande par son ID
     */
    public function getRequestById(int $id): ?array
    {
        $mockRequests = $this->generateMockRequests();
        
        foreach ($mockRequests as $request) {
            if ($request['id'] === $id) {
                return $request;
            }
        }
        
        return null;
    }

    /**
     * Crée une nouvelle demande
     */
    public function createRequest(array $data): array
    {
        // Validation des données
        if (empty($data['service_type']) || empty($data['citizen_name'])) {
            return [
                'success' => false,
                'message' => 'Données obligatoires manquantes'
            ];
        }
        
        // Simulation de création
        $newId = rand(1000, 9999);
        
        return [
            'success' => true,
            'id' => $newId,
            'message' => 'Demande créée avec succès'
        ];
    }

    /**
     * Met à jour une demande
     */
    public function updateRequest(int $id, array $data): array
    {
        $request = $this->getRequestById($id);
        
        if (!$request) {
            return [
                'success' => false,
                'message' => 'Demande introuvable'
            ];
        }
        
        // Simulation de mise à jour
        return [
            'success' => true,
            'message' => 'Demande mise à jour avec succès'
        ];
    }

    /**
     * Supprime une demande
     */
    public function deleteRequest(int $id): array
    {
        $request = $this->getRequestById($id);
        
        if (!$request) {
            return [
                'success' => false,
                'message' => 'Demande introuvable'
            ];
        }
        
        // Simulation de suppression
        return [
            'success' => true,
            'message' => 'Demande supprimée avec succès'
        ];
    }

    /**
     * Met à jour le statut d'une demande
     */
    public function updateRequestStatus(int $id, string $status, string $comment = ''): array
    {
        $request = $this->getRequestById($id);
        
        if (!$request) {
            return [
                'success' => false,
                'message' => 'Demande introuvable'
            ];
        }
        
        $validStatuses = ['pending', 'processing', 'completed', 'rejected'];
        if (!in_array($status, $validStatuses)) {
            return [
                'success' => false,
                'message' => 'Statut invalide'
            ];
        }
        
        // Simulation de mise à jour du statut
        return [
            'success' => true,
            'message' => 'Statut mis à jour avec succès',
            'new_status' => $status
        ];
    }

    /**
     * Ajoute un commentaire à une demande
     */
    public function addComment(int $id, string $comment, bool $isInternal = false): array
    {
        if (empty($comment)) {
            return [
                'success' => false,
                'message' => 'Le commentaire ne peut pas être vide'
            ];
        }
        
        // Simulation d'ajout de commentaire
        return [
            'success' => true,
            'message' => 'Commentaire ajouté avec succès',
            'comment_id' => rand(1, 1000)
        ];
    }

    /**
     * Récupère les types de services
     */
    public function getServiceTypes(): array
    {
        return [
            ['id' => 'passport', 'name' => 'Passport Application', 'icon' => 'passport'],
            ['id' => 'birth_certificate', 'name' => 'Birth Certificate', 'icon' => 'certificate'],
            ['id' => 'national_id', 'name' => 'National ID Card', 'icon' => 'id-card'],
            ['id' => 'driving_license', 'name' => 'Driving License', 'icon' => 'car'],
            ['id' => 'business_registration', 'name' => 'Business Registration', 'icon' => 'briefcase'],
            ['id' => 'marriage_certificate', 'name' => 'Marriage Certificate', 'icon' => 'heart'],
            ['id' => 'property_title', 'name' => 'Property Title', 'icon' => 'home'],
            ['id' => 'tax_certificate', 'name' => 'Tax Certificate', 'icon' => 'receipt']
        ];
    }

    /**
     * Récupère les régions du Cameroun
     */
    public function getRegions(): array
    {
        return [
            ['id' => 'adamawa', 'name' => 'Adamawa'],
            ['id' => 'centre', 'name' => 'Centre'],
            ['id' => 'east', 'name' => 'East'],
            ['id' => 'far_north', 'name' => 'Far North'],
            ['id' => 'littoral', 'name' => 'Littoral'],
            ['id' => 'north', 'name' => 'North'],
            ['id' => 'northwest', 'name' => 'Northwest'],
            ['id' => 'south', 'name' => 'South'],
            ['id' => 'southwest', 'name' => 'Southwest'],
            ['id' => 'west', 'name' => 'West']
        ];
    }

    /**
     * Récupère les statuts possibles
     */
    public function getRequestStatuses(): array
    {
        return [
            ['value' => 'pending', 'label' => 'Pending'],
            ['value' => 'processing', 'label' => 'Processing'],
            ['value' => 'completed', 'label' => 'Completed'],
            ['value' => 'rejected', 'label' => 'Rejected']
        ];
    }

    /**
     * Récupère la timeline d'une demande
     */
    public function getRequestTimeline(int $id): array
    {
        return [
            [
                'date' => '2024-01-15 09:30:00',
                'status' => 'submitted',
                'title' => 'Request Submitted',
                'description' => 'Application submitted by citizen',
                'user' => 'System',
                'icon' => 'paper-plane'
            ],
            [
                'date' => '2024-01-15 14:20:00',
                'status' => 'review',
                'title' => 'Under Review',
                'description' => 'Application is being reviewed by administration',
                'user' => 'Admin Officer',
                'icon' => 'search'
            ],
            [
                'date' => '2024-01-16 11:15:00',
                'status' => 'processing',
                'title' => 'Processing Started',
                'description' => 'Documents verification in progress',
                'user' => 'Document Officer',
                'icon' => 'cogs'
            ]
        ];
    }

    /**
     * Récupère les documents d'une demande
     */
    public function getRequestDocuments(int $id): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Identity_Document.pdf',
                'type' => 'application/pdf',
                'size' => '2.4 MB',
                'uploaded_at' => '2024-01-15 09:30:00',
                'category' => 'Identity'
            ],
            [
                'id' => 2,
                'name' => 'Passport_Photo.jpg',
                'type' => 'image/jpeg',
                'size' => '856 KB',
                'uploaded_at' => '2024-01-15 09:32:00',
                'category' => 'Photo'
            ]
        ];
    }

    /**
     * Récupère les commentaires d'une demande
     */
    public function getRequestComments(int $id): array
    {
        return [
            [
                'id' => 1,
                'comment' => 'Application received and initial review completed.',
                'author' => 'Admin Officer',
                'created_at' => '2024-01-15 14:20:00',
                'is_internal' => true
            ],
            [
                'id' => 2,
                'comment' => 'Please provide additional documentation for verification.',
                'author' => 'Document Officer',
                'created_at' => '2024-01-16 11:15:00',
                'is_internal' => false
            ]
        ];
    }

    /**
     * Récupère les utilisateurs
     */
    public function getUsers(): array
    {
        return [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ['id' => 3, 'name' => 'Robert Johnson', 'email' => 'robert@example.com']
        ];
    }

    /**
     * Export des demandes
     */
    public function exportRequests(string $format, array $filters = []): Response
    {
        $requests = $this->applyFilters($this->generateMockRequests(), $filters);
        
        switch ($format) {
            case 'csv':
                return $this->exportToCsv($requests);
            case 'excel':
                return $this->exportToExcel($requests);
            default:
                throw new \InvalidArgumentException('Format d\'export non supporté');
        }
    }

    /**
     * Génère des données de demandes simulées
     */
    private function generateMockRequests(): array
    {
        $serviceTypes = $this->getServiceTypes();
        $regions = $this->getRegions();
        $statuses = ['pending', 'processing', 'completed', 'rejected'];
        $statusColors = [
            'pending' => 'warning',
            'processing' => 'primary',
            'completed' => 'success',
            'rejected' => 'danger'
        ];
        $statusIcons = [
            'pending' => 'clock',
            'processing' => 'sync-alt',
            'completed' => 'check',
            'rejected' => 'times'
        ];
        
        $names = [
            'Jean Dubois', 'Marie Kouam', 'Paul Ngono', 'Sophie Ndam',
            'Pierre Mboua', 'Anne Talla', 'Michel Fotso', 'Catherine Biya',
            'Robert Essomba', 'Julie Mengue', 'David Tchoua', 'Sarah Bile'
        ];
        
        $requests = [];
        
        for ($i = 1; $i <= 150; $i++) {
            $service = $serviceTypes[array_rand($serviceTypes)];
            $region = $regions[array_rand($regions)];
            $status = $statuses[array_rand($statuses)];
            $name = $names[array_rand($names)];
            
            $requests[] = [
                'id' => $i,
                'reference' => 'REQ-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'service_name' => $service['name'],
                'service_icon' => $service['icon'],
                'citizen_name' => $name,
                'citizen_email' => strtolower(str_replace(' ', '.', $name)) . '@email.cm',
                'region' => $region['name'],
                'status' => $status,
                'status_color' => $statusColors[$status],
                'status_icon' => $statusIcons[$status],
                'created_at' => new \DateTime('-' . rand(1, 30) . ' days'),
                'priority' => rand(1, 5),
                'service_type' => $service['id'],
                'region_id' => $region['id']
            ];
        }
        
        return $requests;
    }

    /**
     * Applique les filtres aux demandes
     */
    private function applyFilters(array $requests, array $filters): array
    {
        return array_filter($requests, function($request) use ($filters) {
            if (!empty($filters['status']) && $request['status'] !== $filters['status']) {
                return false;
            }
            
            if (!empty($filters['service_type']) && $request['service_type'] !== $filters['service_type']) {
                return false;
            }
            
            if (!empty($filters['region']) && $request['region_id'] !== $filters['region']) {
                return false;
            }
            
            if (!empty($filters['search'])) {
                $searchTerm = strtolower($filters['search']);
                $searchFields = [
                    strtolower($request['reference']),
                    strtolower($request['citizen_name']),
                    strtolower($request['citizen_email']),
                    strtolower($request['service_name'])
                ];
                
                $found = false;
                foreach ($searchFields as $field) {
                    if (strpos($field, $searchTerm) !== false) {
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    return false;
                }
            }
            
            return true;
        });
    }

    /**
     * Export au format CSV
     */
    private function exportToCsv(array $requests): Response
    {
        $filename = 'requests_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $content = "Reference,Service,Citizen Name,Email,Region,Status,Created At\n";
        
        foreach ($requests as $request) {
            $content .= sprintf(
                "%s,%s,%s,%s,%s,%s,%s\n",
                $request['reference'],
                $request['service_name'],
                $request['citizen_name'],
                $request['citizen_email'],
                $request['region'],
                $request['status'],
                $request['created_at']->format('Y-m-d H:i:s')
            );
        }
        
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        return $response;
    }

    /**
     * Export au format Excel (simulation)
     */
    private function exportToExcel(array $requests): Response
    {
        // Pour une vraie implémentation, utiliser PhpSpreadsheet
        // Ici on simule en retournant un CSV avec extension xlsx
        $response = $this->exportToCsv($requests);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="requests_export_' . date('Y-m-d_H-i-s') . '.xlsx"');
        
        return $response;
    }
}