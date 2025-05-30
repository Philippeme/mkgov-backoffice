<?php

namespace App\Controller;

use App\Service\DocumentDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de gestion documentaire du système MK Gov
 * 
 * Ce contrôleur centralise la gestion complète des documents administratifs,
 * incluant l'upload, la classification, l'archivage et la recherche avancée
 * pour optimiser la gestion documentaire gouvernementale.
 */
#[Route('/admin/documents', name: 'admin_documents_')]
class DocumentController extends AbstractController
{
    public function __construct(
        private DocumentDataService $documentService
    ) {}

    /**
     * Interface principale de gestion documentaire avec vue d'ensemble
     */
    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $filters = [
            'category' => $request->get('category'),
            'status' => $request->get('status'),
            'file_type' => $request->get('file_type'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search')
        ];

        $documents = $this->documentService->getPaginatedDocuments($page, $limit, $filters);
        $statistics = $this->documentService->getDocumentStatistics($filters);
        $categories = $this->documentService->getDocumentCategories();
        $storageInfo = $this->documentService->getStorageInformation();

        return $this->render('documents/index.html.twig', [
            'documents' => $documents,
            'statistics' => $statistics,
            'categories' => $categories,
            'storage_info' => $storageInfo,
            'current_filters' => $filters,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $documents['total_pages'],
                'total_items' => $documents['total_items']
            ]
        ]);
    }

    /**
     * Interface d'upload et création de nouveaux documents
     */
    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $files = $request->files->all();
            
            $result = $this->documentService->uploadDocument($data, $files);
            
            if ($result['success']) {
                $this->addFlash('success', 'Document uploadé avec succès.');
                return $this->redirectToRoute('admin_documents_show', ['id' => $result['id']]);
            } else {
                $this->addFlash('error', 'Erreur lors de l\'upload: ' . $result['message']);
            }
        }

        $categories = $this->documentService->getDocumentCategories();
        $templates = $this->documentService->getDocumentTemplates();
        $maxFileSize = $this->documentService->getMaxFileSize();

        return $this->render('documents/new.html.twig', [
            'categories' => $categories,
            'templates' => $templates,
            'max_file_size' => $maxFileSize
        ]);
    }

    /**
     * Affichage détaillé d'un document avec métadonnées
     */
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $document = $this->documentService->getDocumentById($id);
        
        if (!$document) {
            throw $this->createNotFoundException('Document introuvable.');
        }

        $versions = $this->documentService->getDocumentVersions($id);
        $permissions = $this->documentService->getDocumentPermissions($id);
        $accessLog = $this->documentService->getDocumentAccessLog($id);

        return $this->render('documents/show.html.twig', [
            'document' => $document,
            'versions' => $versions,
            'permissions' => $permissions,
            'access_log' => $accessLog
        ]);
    }

    /**
     * Modification des métadonnées d'un document
     */
    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request): Response
    {
        $document = $this->documentService->getDocumentById($id);
        
        if (!$document) {
            throw $this->createNotFoundException('Document introuvable.');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $result = $this->documentService->updateDocument($id, $data);
            
            if ($result['success']) {
                $this->addFlash('success', 'Document mis à jour avec succès.');
                return $this->redirectToRoute('admin_documents_show', ['id' => $id]);
            } else {
                $this->addFlash('error', 'Erreur lors de la mise à jour: ' . $result['message']);
            }
        }

        $categories = $this->documentService->getDocumentCategories();
        $accessLevels = $this->documentService->getAccessLevels();

        return $this->render('documents/edit.html.twig', [
            'document' => $document,
            'categories' => $categories,
            'access_levels' => $accessLevels
        ]);
    }

    /**
     * Interface de gestion des archives documentaires
     */
    #[Route('/archive', name: 'archive')]
    public function archive(Request $request): Response
    {
        $filters = [
            'archive_date_from' => $request->get('archive_date_from'),
            'archive_date_to' => $request->get('archive_date_to'),
            'category' => $request->get('category'),
            'search' => $request->get('search')
        ];

        $archivedDocuments = $this->documentService->getArchivedDocuments($filters);
        $archiveStatistics = $this->documentService->getArchiveStatistics();
        $archivePolicies = $this->documentService->getArchivePolicies();

        return $this->render('documents/archive.html.twig', [
            'archived_documents' => $archivedDocuments,
            'statistics' => $archiveStatistics,
            'policies' => $archivePolicies,
            'current_filters' => $filters
        ]);
    }

    /**
     * Téléchargement sécurisé de documents
     */
    #[Route('/{id}/download', name: 'download', requirements: ['id' => '\d+'])]
    public function download(int $id, Request $request): Response
    {
        $document = $this->documentService->getDocumentById($id);
        
        if (!$document) {
            throw $this->createNotFoundException('Document introuvable.');
        }

        $version = $request->get('version', 'latest');
        $downloadResult = $this->documentService->downloadDocument($id, $version);

        if (!$downloadResult['success']) {
            $this->addFlash('error', $downloadResult['message']);
            return $this->redirectToRoute('admin_documents_show', ['id' => $id]);
        }

        return $downloadResult['response'];
    }

    /**
     * Upload d'une nouvelle version de document
     */
    #[Route('/{id}/upload-version', name: 'upload_version', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function uploadVersion(int $id, Request $request): JsonResponse
    {
        $uploadedFile = $request->files->get('document_file');
        $comment = $request->get('comment', '');

        $result = $this->documentService->uploadNewVersion($id, $uploadedFile, $comment);

        return $this->json($result);
    }

    /**
     * Archivage d'un document
     */
    #[Route('/{id}/archive', name: 'archive_document', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function archiveDocument(int $id, Request $request): JsonResponse
    {
        $reason = $request->get('archive_reason', '');
        $result = $this->documentService->archiveDocument($id, $reason);

        return $this->json($result);
    }

    /**
     * Restauration d'un document archivé
     */
    #[Route('/{id}/restore', name: 'restore', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function restore(int $id): JsonResponse
    {
        $result = $this->documentService->restoreDocument($id);

        return $this->json($result);
    }

    /**
     * Partage de document avec gestion des permissions
     */
    #[Route('/{id}/share', name: 'share', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function share(int $id, Request $request): JsonResponse
    {
        $shareData = [
            'users' => $request->get('users', []),
            'groups' => $request->get('groups', []),
            'permissions' => $request->get('permissions', []),
            'expiry_date' => $request->get('expiry_date'),
            'message' => $request->get('message', '')
        ];

        $result = $this->documentService->shareDocument($id, $shareData);

        return $this->json($result);
    }

    /**
     * Recherche avancée dans les documents
     */
    #[Route('/search', name: 'search')]
    public function search(Request $request): Response
    {
        $searchParams = [
            'query' => $request->get('query'),
            'categories' => $request->get('categories', []),
            'file_types' => $request->get('file_types', []),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'size_min' => $request->get('size_min'),
            'size_max' => $request->get('size_max'),
            'content_search' => $request->get('content_search', false)
        ];

        $searchResults = $this->documentService->advancedSearch($searchParams);
        $searchStatistics = $this->documentService->getSearchStatistics($searchParams);

        return $this->render('documents/search.html.twig', [
            'search_results' => $searchResults,
            'search_statistics' => $searchStatistics,
            'search_params' => $searchParams,
            'categories' => $this->documentService->getDocumentCategories()
        ]);
    }

    /**
     * Analyse et rapports documentaires
     */
    #[Route('/analytics', name: 'analytics')]
    public function analytics(Request $request): Response
    {
        $period = $request->get('period', '30');
        $category = $request->get('category');

        $analytics = $this->documentService->getDocumentAnalytics($period, $category);
        $storageAnalysis = $this->documentService->getStorageAnalysis();
        $accessPatterns = $this->documentService->getAccessPatterns($period);

        return $this->render('documents/analytics.html.twig', [
            'analytics' => $analytics,
            'storage_analysis' => $storageAnalysis,
            'access_patterns' => $accessPatterns,
            'selected_period' => $period,
            'selected_category' => $category
        ]);
    }

    /**
     * Gestion des modèles de documents
     */
    #[Route('/templates', name: 'templates')]
    public function templates(Request $request): Response
    {
        $templates = $this->documentService->getDocumentTemplates();
        $templateCategories = $this->documentService->getTemplateCategories();

        return $this->render('documents/templates.html.twig', [
            'templates' => $templates,
            'categories' => $templateCategories
        ]);
    }

    /**
     * Création d'un nouveau modèle de document
     */
    #[Route('/templates/new', name: 'templates_new')]
    public function newTemplate(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $files = $request->files->all();
            
            $result = $this->documentService->createTemplate($data, $files);
            
            if ($result['success']) {
                $this->addFlash('success', 'Modèle créé avec succès.');
                return $this->redirectToRoute('admin_documents_templates');
            } else {
                $this->addFlash('error', 'Erreur lors de la création: ' . $result['message']);
            }
        }

        $categories = $this->documentService->getTemplateCategories();

        return $this->render('documents/template_new.html.twig', [
            'categories' => $categories
        ]);
    }

    /**
     * Export de documents selon critères
     */
    #[Route('/export/{format}', name: 'export', methods: ['GET'])]
    public function export(string $format, Request $request): Response
    {
        $filters = [
            'category' => $request->get('category'),
            'status' => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to')
        ];

        return $this->documentService->exportDocuments($format, $filters);
    }

    /**
     * Import en lot de documents
     */
    #[Route('/bulk-import', name: 'bulk_import', methods: ['GET', 'POST'])]
    public function bulkImport(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $files = $request->files->get('document_files', []);
            $metadata = $request->request->all();
            
            $result = $this->documentService->bulkImportDocuments($files, $metadata);
            
            if ($result['success']) {
                $this->addFlash('success', 'Import réalisé avec succès: ' . $result['imported_count'] . ' documents importés.');
            } else {
                $this->addFlash('error', 'Erreur lors de l\'import: ' . $result['message']);
            }
            
            return $this->redirectToRoute('admin_documents_index');
        }

        $categories = $this->documentService->getDocumentCategories();
        $importRules = $this->documentService->getImportRules();

        return $this->render('documents/bulk_import.html.twig', [
            'categories' => $categories,
            'import_rules' => $importRules
        ]);
    }

    /**
     * Suppression définitive d'un document
     */
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete'.$id, $request->get('_token'))) {
            $result = $this->documentService->deleteDocument($id);
            
            if ($result['success']) {
                $this->addFlash('success', 'Document supprimé avec succès.');
            } else {
                $this->addFlash('error', 'Erreur lors de la suppression: ' . $result['message']);
            }
        }

        return $this->redirectToRoute('admin_documents_index');
    }

    /**
     * Prévisualisation de document dans le navigateur
     */
    #[Route('/{id}/preview', name: 'preview', requirements: ['id' => '\d+'])]
    public function preview(int $id): Response
    {
        $document = $this->documentService->getDocumentById($id);
        
        if (!$document) {
            throw $this->createNotFoundException('Document introuvable.');
        }

        $previewData = $this->documentService->generatePreview($id);

        return $this->render('documents/preview.html.twig', [
            'document' => $document,
            'preview_data' => $previewData
        ]);
    }
}