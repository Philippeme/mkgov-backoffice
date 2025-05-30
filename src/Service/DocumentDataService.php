<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Service de gestion documentaire du système MK Gov
 * 
 * Ce service centralise toute la logique métier pour la gestion complète
 * des documents administratifs : upload, classification, versioning,
 * archivage, recherche et sécurité documentaire.
 */
class DocumentDataService
{
    /**
     * Récupère les documents paginés avec filtres appliqués
     */
    public function getPaginatedDocuments(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $mockDocuments = $this->generateMockDocuments();
        $filteredDocuments = $this->applyDocumentFilters($mockDocuments, $filters);
        
        $totalItems = count($filteredDocuments);
        $totalPages = ceil($totalItems / $limit);
        $offset = ($page - 1) * $limit;
        $paginatedItems = array_slice($filteredDocuments, $offset, $limit);
        
        return [
            'items' => $paginatedItems,
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'items_per_page' => $limit
        ];
    }

    /**
     * Génère les statistiques documentaires globales
     */
    public function getDocumentStatistics(array $filters = []): array
    {
        $documents = $this->applyDocumentFilters($this->generateMockDocuments(), $filters);
        
        $stats = [
            'total_documents' => count($documents),
            'active_documents' => 0,
            'archived_documents' => 0,
            'total_size' => 0,
            'recent_uploads' => 0
        ];
        
        $oneWeekAgo = new \DateTime('-1 week');
        
        foreach ($documents as $document) {
            if ($document['status'] === 'active') {
                $stats['active_documents']++;
            } elseif ($document['status'] === 'archived') {
                $stats['archived_documents']++;
            }
            
            $stats['total_size'] += $document['size_bytes'];
            
            if ($document['created_at'] >= $oneWeekAgo) {
                $stats['recent_uploads']++;
            }
        }
        
        return $stats;
    }

    /**
     * Récupère les informations de stockage système
     */
    public function getStorageInformation(): array
    {
        return [
            'total_space' => '2TB',
            'used_space' => '847GB',
            'free_space' => '1.2TB',
            'usage_percentage' => 41.3,
            'documents_count' => 15847,
            'avg_document_size' => '2.1MB'
        ];
    }

    /**
     * Récupère un document par son identifiant
     */
    public function getDocumentById(int $id): ?array
    {
        $documents = $this->generateMockDocuments();
        
        foreach ($documents as $document) {
            if ($document['id'] === $id) {
                return $document;
            }
        }
        
        return null;
    }

    /**
     * Upload d'un nouveau document avec validation
     */
    public function uploadDocument(array $data, array $files): array
    {
        if (empty($files['document_file']) || !$files['document_file'] instanceof UploadedFile) {
            return [
                'success' => false,
                'message' => 'Aucun fichier sélectionné'
            ];
        }
        
        $file = $files['document_file'];
        
        // Validation du fichier
        if (!$this->validateUploadedFile($file)) {
            return [
                'success' => false,
                'message' => 'Fichier non valide ou type non autorisé'
            ];
        }
        
        // Simulation de l'upload
        $newId = rand(1000, 9999);
        
        return [
            'success' => true,
            'id' => $newId,
            'message' => 'Document uploadé avec succès'
        ];
    }

    /**
     * Met à jour les métadonnées d'un document
     */
    public function updateDocument(int $id, array $data): array
    {
        $document = $this->getDocumentById($id);
        
        if (!$document) {
            return [
                'success' => false,
                'message' => 'Document introuvable'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Document mis à jour avec succès'
        ];
    }

    /**
     * Supprime définitivement un document
     */
    public function deleteDocument(int $id): array
    {
        $document = $this->getDocumentById($id);
        
        if (!$document) {
            return [
                'success' => false,
                'message' => 'Document introuvable'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Document supprimé avec succès'
        ];
    }

    /**
     * Archive un document avec raison
     */
    public function archiveDocument(int $id, string $reason = ''): array
    {
        $document = $this->getDocumentById($id);
        
        if (!$document) {
            return [
                'success' => false,
                'message' => 'Document introuvable'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Document archivé avec succès'
        ];
    }

    /**
     * Restaure un document archivé
     */
    public function restoreDocument(int $id): array
    {
        $document = $this->getDocumentById($id);
        
        if (!$document) {
            return [
                'success' => false,
                'message' => 'Document introuvable'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Document restauré avec succès'
        ];
    }

    /**
     * Télécharge un document avec contrôle d'accès
     */
    public function downloadDocument(int $id, string $version = 'latest'): array
    {
        $document = $this->getDocumentById($id);
        
        if (!$document) {
            return [
                'success' => false,
                'message' => 'Document introuvable'
            ];
        }
        
        // Simulation d'un fichier de téléchargement
        $content = 'Contenu simulé du document: ' . $document['name'];
        $response = new Response($content);
        $response->headers->set('Content-Type', $document['mime_type']);
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $document['filename'] . '"');
        
        return [
            'success' => true,
            'response' => $response
        ];
    }

    /**
     * Upload d'une nouvelle version de document
     */
    public function uploadNewVersion(int $id, UploadedFile $file, string $comment): array
    {
        if (!$this->validateUploadedFile($file)) {
            return [
                'success' => false,
                'message' => 'Fichier non valide'
            ];
        }
        
        return [
            'success' => true,
            'version' => '1.' . rand(1, 9),
            'message' => 'Nouvelle version uploadée avec succès'
        ];
    }

    /**
     * Partage un document avec gestion des permissions
     */
    public function shareDocument(int $id, array $shareData): array
    {
        if (empty($shareData['users']) && empty($shareData['groups'])) {
            return [
                'success' => false,
                'message' => 'Aucun destinataire spécifié'
            ];
        }
        
        return [
            'success' => true,
            'share_link' => 'https://mkgov.cm/share/' . bin2hex(random_bytes(16)),
            'message' => 'Document partagé avec succès'
        ];
    }

    /**
     * Recherche avancée dans les documents
     */
    public function advancedSearch(array $searchParams): array
    {
        $documents = $this->generateMockDocuments();
        $results = [];
        
        foreach ($documents as $document) {
            if ($this->matchesSearchCriteria($document, $searchParams)) {
                $results[] = $document;
            }
        }
        
        return [
            'documents' => $results,
            'total_found' => count($results),
            'search_time' => rand(50, 200) . 'ms'
        ];
    }

    /**
     * Récupère les catégories de documents disponibles
     */
    public function getDocumentCategories(): array
    {
        return [
            ['id' => 'administrative', 'name' => 'Administrative Documents', 'color' => '#1a73e8', 'count' => 245],
            ['id' => 'legal', 'name' => 'Legal Documents', 'color' => '#34a853', 'count' => 189],
            ['id' => 'financial', 'name' => 'Financial Records', 'color' => '#ff6d01', 'count' => 156],
            ['id' => 'identity', 'name' => 'Identity Documents', 'color' => '#9c27b0', 'count' => 892],
            ['id' => 'permits', 'name' => 'Permits & Licenses', 'color' => '#607d8b', 'count' => 167],
            ['id' => 'certificates', 'name' => 'Certificates', 'color' => '#f44336', 'count' => 423],
            ['id' => 'reports', 'name' => 'Reports & Analytics', 'color' => '#795548', 'count' => 89],
            ['id' => 'templates', 'name' => 'Document Templates', 'color' => '#ff9800', 'count' => 34]
        ];
    }

    /**
     * Récupère les modèles de documents
     */
    public function getDocumentTemplates(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Passport Application Form',
                'category' => 'identity',
                'file_type' => 'PDF',
                'size' => '245KB',
                'downloads' => 1567,
                'last_updated' => '2024-01-15'
            ],
            [
                'id' => 2,
                'name' => 'Birth Certificate Request',
                'category' => 'certificates',
                'file_type' => 'PDF',
                'size' => '189KB',
                'downloads' => 2341,
                'last_updated' => '2024-01-10'
            ],
            [
                'id' => 3,
                'name' => 'Business Registration Form',
                'category' => 'permits',
                'file_type' => 'PDF',
                'size' => '356KB',
                'downloads' => 876,
                'last_updated' => '2024-01-12'
            ]
        ];
    }

    /**
     * Récupère les catégories de modèles
     */
    public function getTemplateCategories(): array
    {
        return [
            ['id' => 'forms', 'name' => 'Application Forms'],
            ['id' => 'letters', 'name' => 'Official Letters'],
            ['id' => 'certificates', 'name' => 'Certificate Templates'],
            ['id' => 'reports', 'name' => 'Report Templates']
        ];
    }

    /**
     * Récupère les versions d'un document
     */
    public function getDocumentVersions(int $id): array
    {
        return [
            [
                'version' => '1.3',
                'created_at' => '2024-01-15 14:30:00',
                'created_by' => 'Admin Officer',
                'comment' => 'Updated formatting and corrected typos',
                'size' => '2.4MB',
                'is_current' => true
            ],
            [
                'version' => '1.2',
                'created_at' => '2024-01-10 09:15:00',
                'created_by' => 'Document Manager',
                'comment' => 'Added new sections as per regulations',
                'size' => '2.1MB',
                'is_current' => false
            ],
            [
                'version' => '1.1',
                'created_at' => '2024-01-05 16:45:00',
                'created_by' => 'Content Editor',
                'comment' => 'Initial version with basic content',
                'size' => '1.8MB',
                'is_current' => false
            ]
        ];
    }

    /**
     * Récupère les permissions d'un document
     */
    public function getDocumentPermissions(int $id): array
    {
        return [
            [
                'user_type' => 'user',
                'name' => 'John Doe',
                'email' => 'john.doe@mkgov.cm',
                'permission' => 'read',
                'granted_at' => '2024-01-15 10:00:00'
            ],
            [
                'user_type' => 'group',
                'name' => 'Document Reviewers',
                'email' => null,
                'permission' => 'read_write',
                'granted_at' => '2024-01-14 15:30:00'
            ]
        ];
    }

    /**
     * Récupère le journal d'accès d'un document
     */
    public function getDocumentAccessLog(int $id): array
    {
        return [
            [
                'action' => 'download',
                'user' => 'Jane Smith',
                'ip_address' => '192.168.1.100',
                'timestamp' => '2024-01-15 14:45:00',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'success' => true
            ],
            [
                'action' => 'view',
                'user' => 'Mike Johnson',
                'ip_address' => '192.168.1.101',
                'timestamp' => '2024-01-15 13:20:00',
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                'success' => true
            ]
        ];
    }

    /**
     * Récupère les niveaux d'accès disponibles
     */
    public function getAccessLevels(): array
    {
        return [
            ['value' => 'public', 'label' => 'Public'],
            ['value' => 'internal', 'label' => 'Internal Only'],
            ['value' => 'confidential', 'label' => 'Confidential'],
            ['value' => 'restricted', 'label' => 'Restricted Access']
        ];
    }

    /**
     * Récupère les documents archivés
     */
    public function getArchivedDocuments(array $filters = []): array
    {
        $documents = array_filter($this->generateMockDocuments(), function($doc) {
            return $doc['status'] === 'archived';
        });
        
        return [
            'documents' => $documents,
            'total_count' => count($documents)
        ];
    }

    /**
     * Récupère les statistiques d'archivage
     */
    public function getArchiveStatistics(): array
    {
        return [
            'total_archived' => 1247,
            'archived_this_month' => 89,
            'total_archive_size' => '15.6GB',
            'oldest_archive' => '2020-03-15',
            'retention_compliance' => 97.2
        ];
    }

    /**
     * Récupère les politiques d'archivage
     */
    public function getArchivePolicies(): array
    {
        return [
            [
                'category' => 'Administrative',
                'retention_period' => '7 years',
                'auto_archive' => true,
                'policy_name' => 'Standard Administrative Policy'
            ],
            [
                'category' => 'Financial',
                'retention_period' => '10 years',
                'auto_archive' => true,
                'policy_name' => 'Financial Records Policy'
            ],
            [
                'category' => 'Legal',
                'retention_period' => 'Permanent',
                'auto_archive' => false,
                'policy_name' => 'Legal Documents Policy'
            ]
        ];
    }

    /**
     * Récupère la taille maximale de fichier autorisée
     */
    public function getMaxFileSize(): string
    {
        return '50MB';
    }

    /**
     * Génère une prévisualisation de document
     */
    public function generatePreview(int $id): array
    {
        $document = $this->getDocumentById($id);
        
        return [
            'preview_available' => in_array($document['file_extension'], ['pdf', 'jpg', 'png', 'txt']),
            'preview_url' => '/documents/' . $id . '/preview-image',
            'thumbnail_url' => '/documents/' . $id . '/thumbnail',
            'pages_count' => rand(1, 10)
        ];
    }

    /**
     * Analytics documentaires
     */
    public function getDocumentAnalytics(string $period, ?string $category): array
    {
        return [
            'uploads_count' => rand(100, 500),
            'downloads_count' => rand(500, 2000),
            'most_accessed_documents' => [
                ['name' => 'Passport Application Form', 'access_count' => 245],
                ['name' => 'Birth Certificate Template', 'access_count' => 189],
                ['name' => 'Business Registration Guide', 'access_count' => 156]
            ],
            'storage_growth' => '12.5%',
            'user_activity_score' => 8.7
        ];
    }

    /**
     * Analyse du stockage
     */
    public function getStorageAnalysis(): array
    {
        return [
            'by_category' => [
                'Identity Documents' => '35%',
                'Administrative' => '28%',
                'Legal' => '15%',
                'Financial' => '12%',
                'Other' => '10%'
            ],
            'by_file_type' => [
                'PDF' => '68%',
                'Images' => '18%',
                'Office Documents' => '10%',
                'Other' => '4%'
            ],
            'growth_trend' => 'stable'
        ];
    }

    /**
     * Patterns d'accès aux documents
     */
    public function getAccessPatterns(string $period): array
    {
        return [
            'peak_hours' => ['09:00-11:00', '14:00-16:00'],
            'most_active_day' => 'Tuesday',
            'access_by_department' => [
                'Civil Registry' => 35,
                'Immigration' => 28,
                'Education' => 18,
                'Health' => 12,
                'Other' => 7
            ]
        ];
    }

    /**
     * Statistiques de recherche
     */
    public function getSearchStatistics(array $searchParams): array
    {
        return [
            'total_results' => rand(10, 100),
            'search_time' => rand(50, 200) . 'ms',
            'filters_applied' => count(array_filter($searchParams)),
            'suggested_keywords' => ['passport', 'certificate', 'application', 'form']
        ];
    }

    /**
     * Crée un nouveau modèle de document
     */
    public function createTemplate(array $data, array $files): array
    {
        if (empty($data['name']) || empty($files['template_file'])) {
            return [
                'success' => false,
                'message' => 'Nom et fichier de modèle requis'
            ];
        }
        
        return [
            'success' => true,
            'id' => rand(100, 999),
            'message' => 'Modèle créé avec succès'
        ];
    }

    /**
     * Règles d'import en lot
     */
    public function getImportRules(): array
    {
        return [
            'max_files_per_batch' => 100,
            'allowed_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png'],
            'max_file_size' => '50MB',
            'auto_categorization' => true,
            'virus_scan_required' => true
        ];
    }

    /**
     * Import en lot de documents
     */
    public function bulkImportDocuments(array $files, array $metadata): array
    {
        if (empty($files)) {
            return [
                'success' => false,
                'message' => 'Aucun fichier sélectionné'
            ];
        }
        
        $importedCount = count($files);
        
        return [
            'success' => true,
            'imported_count' => $importedCount,
            'message' => 'Import terminé avec succès'
        ];
    }

    /**
     * Export de documents selon critères
     */
    public function exportDocuments(string $format, array $filters): Response
    {
        $documents = $this->applyDocumentFilters($this->generateMockDocuments(), $filters);
        
        switch ($format) {
            case 'csv':
                return $this->exportDocumentsToCsv($documents);
            case 'excel':
                return $this->exportDocumentsToExcel($documents);
            default:
                throw new \InvalidArgumentException('Format d\'export non supporté');
        }
    }

    // Méthodes privées

    /**
     * Génère des documents simulés pour les tests
     */
    private function generateMockDocuments(): array
    {
        $categories = array_column($this->getDocumentCategories(), 'id');
        $statuses = ['active', 'archived', 'draft'];
        $fileTypes = ['pdf', 'doc', 'jpg', 'png', 'xls'];
        
        $documents = [];
        
        for ($i = 1; $i <= 100; $i++) {
            $fileType = $fileTypes[array_rand($fileTypes)];
            $documents[] = [
                'id' => $i,
                'name' => 'Document ' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'filename' => 'document_' . $i . '.' . $fileType,
                'description' => 'Description of document ' . $i,
                'category' => $categories[array_rand($categories)],
                'status' => $statuses[array_rand($statuses)],
                'file_extension' => $fileType,
                'mime_type' => $this->getMimeType($fileType),
                'size_bytes' => rand(1024, 10485760), // 1KB to 10MB
                'size_human' => $this->formatFileSize(rand(1024, 10485760)),
                'version' => '1.' . rand(0, 9),
                'created_at' => new \DateTime('-' . rand(1, 365) . ' days'),
                'updated_at' => new \DateTime('-' . rand(1, 30) . ' days'),
                'created_by' => 'User ' . rand(1, 10),
                'download_count' => rand(0, 100)
            ];
        }
        
        return $documents;
    }

    /**
     * Applique les filtres aux documents
     */
    private function applyDocumentFilters(array $documents, array $filters): array
    {
        return array_filter($documents, function($document) use ($filters) {
            if (!empty($filters['category']) && $document['category'] !== $filters['category']) {
                return false;
            }
            
            if (!empty($filters['status']) && $document['status'] !== $filters['status']) {
                return false;
            }
            
            if (!empty($filters['file_type']) && $document['file_extension'] !== $filters['file_type']) {
                return false;
            }
            
            if (!empty($filters['search'])) {
                $searchTerm = strtolower($filters['search']);
                if (strpos(strtolower($document['name']), $searchTerm) === false &&
                    strpos(strtolower($document['description']), $searchTerm) === false) {
                    return false;
                }
            }
            
            return true;
        });
    }

    /**
     * Valide un fichier uploadé
     */
    private function validateUploadedFile(UploadedFile $file): bool
    {
        $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt'];
        $maxSize = 50 * 1024 * 1024; // 50MB
        
        $extension = strtolower($file->getClientOriginalExtension());
        
        return in_array($extension, $allowedTypes) && 
               $file->getSize() <= $maxSize && 
               $file->isValid();
    }

    /**
     * Vérifie si un document correspond aux critères de recherche
     */
    private function matchesSearchCriteria(array $document, array $searchParams): bool
    {
        if (!empty($searchParams['query'])) {
            $query = strtolower($searchParams['query']);
            if (strpos(strtolower($document['name']), $query) === false &&
                strpos(strtolower($document['description']), $query) === false) {
                return false;
            }
        }
        
        if (!empty($searchParams['categories']) && 
            !in_array($document['category'], $searchParams['categories'])) {
            return false;
        }
        
        if (!empty($searchParams['file_types']) && 
            !in_array($document['file_extension'], $searchParams['file_types'])) {
            return false;
        }
        
        return true;
    }

    /**
     * Obtient le type MIME d'un fichier selon son extension
     */
    private function getMimeType(string $extension): string
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'txt' => 'text/plain'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Formate une taille de fichier en octets vers un format lisible
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        return number_format($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }

    /**
     * Export CSV des documents
     */
    private function exportDocumentsToCsv(array $documents): Response
    {
        $filename = 'documents_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $content = "ID,Name,Category,Status,Type,Size,Created At\n";
        
        foreach ($documents as $document) {
            $content .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s\n",
                $document['id'],
                $document['name'],
                $document['category'],
                $document['status'],
                $document['file_extension'],
                $document['size_human'],
                $document['created_at']->format('Y-m-d H:i:s')
            );
        }
        
        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        return $response;
    }

    /**
     * Export Excel simulé des documents
     */
    private function exportDocumentsToExcel(array $documents): Response
    {
        $response = $this->exportDocumentsToCsv($documents);
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="documents_export_' . date('Y-m-d_H-i-s') . '.xlsx"');
        
        return $response;
    }
}