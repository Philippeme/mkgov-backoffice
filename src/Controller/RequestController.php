<?php

namespace App\Controller;

use App\Service\RequestDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de gestion des demandes citoyennes
 * 
 * Ce contrôleur gère l'ensemble des opérations CRUD pour les demandes
 * soumises par les citoyens via l'application Angular MK Gov.
 */
#[Route('/admin/requests', name: 'admin_requests_')]
class RequestController extends AbstractController
{
    public function __construct(
        private RequestDataService $requestService
    ) {}

    /**
     * Liste des demandes avec pagination et filtres
     */
    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $filters = [
            'status' => $request->get('status'),
            'service_type' => $request->get('service_type'),
            'region' => $request->get('region'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search')
        ];

        $requests = $this->requestService->getPaginatedRequests($page, $limit, $filters);
        $statistics = $this->requestService->getRequestStatistics($filters);
        $serviceTypes = $this->requestService->getServiceTypes();
        $regions = $this->requestService->getRegions();
        $statuses = $this->requestService->getRequestStatuses();

        return $this->render('requests/index.html.twig', [
            'requests' => $requests,
            'statistics' => $statistics,
            'service_types' => $serviceTypes,
            'regions' => $regions,
            'statuses' => $statuses,
            'current_filters' => $filters,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $requests['total_pages'],
                'total_items' => $requests['total_items']
            ]
        ]);
    }

    /**
     * Création d'une nouvelle demande (formulaire administrateur)
     */
    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $result = $this->requestService->createRequest($data);
            
            if ($result['success']) {
                $this->addFlash('success', 'La demande a été créée avec succès.');
                return $this->redirectToRoute('admin_requests_show', ['id' => $result['id']]);
            } else {
                $this->addFlash('error', 'Erreur lors de la création de la demande: ' . $result['message']);
            }
        }

        $serviceTypes = $this->requestService->getServiceTypes();
        $regions = $this->requestService->getRegions();
        $users = $this->requestService->getUsers();

        return $this->render('requests/new.html.twig', [
            'service_types' => $serviceTypes,
            'regions' => $regions,
            'users' => $users
        ]);
    }

    /**
     * Affichage détaillé d'une demande
     */
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $request = $this->requestService->getRequestById($id);
        
        if (!$request) {
            throw $this->createNotFoundException('Demande introuvable.');
        }

        $timeline = $this->requestService->getRequestTimeline($id);
        $documents = $this->requestService->getRequestDocuments($id);
        $comments = $this->requestService->getRequestComments($id);

        return $this->render('requests/show.html.twig', [
            'request' => $request,
            'timeline' => $timeline,
            'documents' => $documents,
            'comments' => $comments
        ]);
    }

    /**
     * Modification d'une demande
     */
    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request): Response
    {
        $requestData = $this->requestService->getRequestById($id);
        
        if (!$requestData) {
            throw $this->createNotFoundException('Demande introuvable.');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $result = $this->requestService->updateRequest($id, $data);
            
            if ($result['success']) {
                $this->addFlash('success', 'La demande a été mise à jour avec succès.');
                return $this->redirectToRoute('admin_requests_show', ['id' => $id]);
            } else {
                $this->addFlash('error', 'Erreur lors de la mise à jour: ' . $result['message']);
            }
        }

        $serviceTypes = $this->requestService->getServiceTypes();
        $regions = $this->requestService->getRegions();
        $statuses = $this->requestService->getRequestStatuses();

        return $this->render('requests/edit.html.twig', [
            'request' => $requestData,
            'service_types' => $serviceTypes,
            'regions' => $regions,
            'statuses' => $statuses
        ]);
    }

    /**
     * Suppression d'une demande
     */
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete'.$id, $request->get('_token'))) {
            $result = $this->requestService->deleteRequest($id);
            
            if ($result['success']) {
                $this->addFlash('success', 'La demande a été supprimée avec succès.');
            } else {
                $this->addFlash('error', 'Erreur lors de la suppression: ' . $result['message']);
            }
        }

        return $this->redirectToRoute('admin_requests_index');
    }

    /**
     * Mise à jour du statut d'une demande
     */
    #[Route('/{id}/status', name: 'update_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $status = $request->get('status');
        $comment = $request->get('comment', '');
        
        $result = $this->requestService->updateRequestStatus($id, $status, $comment);
        
        return $this->json($result);
    }

    /**
     * Ajout d'un commentaire à une demande
     */
    #[Route('/{id}/comments', name: 'add_comment', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addComment(int $id, Request $request): JsonResponse
    {
        $comment = $request->get('comment');
        $isInternal = $request->get('is_internal', false);
        
        $result = $this->requestService->addComment($id, $comment, $isInternal);
        
        return $this->json($result);
    }

    /**
     * Export des demandes
     */
    #[Route('/export/{format}', name: 'export', methods: ['GET'])]
    public function export(string $format, Request $request): Response
    {
        $filters = [
            'status' => $request->get('status'),
            'service_type' => $request->get('service_type'),
            'region' => $request->get('region'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to')
        ];

        return $this->requestService->exportRequests($format, $filters);
    }
}