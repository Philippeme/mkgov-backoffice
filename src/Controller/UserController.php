<?php

namespace App\Controller;

use App\Service\UserDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de gestion des utilisateurs et des droits d'accès
 * 
 * Ce contrôleur gère l'ensemble des opérations liées aux utilisateurs,
 * rôles, permissions et sécurité du système d'administration MK Gov,
 * assurant une gestion fine des accès et des autorisations.
 */
#[Route('/admin/users', name: 'admin_users_')]
class UserController extends AbstractController
{
    public function __construct(
        private UserDataService $userService
    ) {}

    /**
     * Interface principale de gestion des utilisateurs
     */
    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);
        $filters = [
            'role' => $request->get('role'),
            'status' => $request->get('status'),
            'department' => $request->get('department'),
            'last_login' => $request->get('last_login'),
            'search' => $request->get('search')
        ];

        $users = $this->userService->getPaginatedUsers($page, $limit, $filters);
        $statistics = $this->userService->getUserStatistics($filters);
        $roles = $this->userService->getAllRoles();
        $departments = $this->userService->getDepartments();

        return $this->render('users/index.html.twig', [
            'users' => $users,
            'statistics' => $statistics,
            'roles' => $roles,
            'departments' => $departments,
            'current_filters' => $filters,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $users['total_pages'],
                'total_items' => $users['total_items']
            ]
        ]);
    }

    /**
     * Création d'un nouvel utilisateur
     */
    #[Route('/new', name: 'new')]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $result = $this->userService->createUser($data);
            
            if ($result['success']) {
                $this->addFlash('success', 'Utilisateur créé avec succès.');
                return $this->redirectToRoute('admin_users_show', ['id' => $result['id']]);
            } else {
                $this->addFlash('error', 'Erreur lors de la création: ' . $result['message']);
            }
        }

        $roles = $this->userService->getAllRoles();
        $departments = $this->userService->getDepartments();
        $permissions = $this->userService->getAllPermissions();

        return $this->render('users/new.html.twig', [
            'roles' => $roles,
            'departments' => $departments,
            'permissions' => $permissions
        ]);
    }

    /**
     * Affichage détaillé d'un utilisateur
     */
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $user = $this->userService->getUserById($id);
        
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        $userPermissions = $this->userService->getUserPermissions($id);
        $activityLog = $this->userService->getUserActivityLog($id);
        $sessionInfo = $this->userService->getUserSessionInfo($id);

        return $this->render('users/show.html.twig', [
            'user' => $user,
            'permissions' => $userPermissions,
            'activity_log' => $activityLog,
            'session_info' => $sessionInfo
        ]);
    }

    /**
     * Modification d'un utilisateur
     */
    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request): Response
    {
        $user = $this->userService->getUserById($id);
        
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $result = $this->userService->updateUser($id, $data);
            
            if ($result['success']) {
                $this->addFlash('success', 'Utilisateur mis à jour avec succès.');
                return $this->redirectToRoute('admin_users_show', ['id' => $id]);
            } else {
                $this->addFlash('error', 'Erreur lors de la mise à jour: ' . $result['message']);
            }
        }

        $roles = $this->userService->getAllRoles();
        $departments = $this->userService->getDepartments();
        $userPermissions = $this->userService->getUserPermissions($id);

        return $this->render('users/edit.html.twig', [
            'user' => $user,
            'roles' => $roles,
            'departments' => $departments,
            'user_permissions' => $userPermissions
        ]);
    }

    /**
     * Gestion des rôles et permissions
     */
    #[Route('/roles', name: 'roles')]
    public function roles(Request $request): Response
    {
        $roles = $this->userService->getAllRoles();
        $permissions = $this->userService->getAllPermissions();
        $roleStatistics = $this->userService->getRoleStatistics();

        return $this->render('users/roles.html.twig', [
            'roles' => $roles,
            'permissions' => $permissions,
            'statistics' => $roleStatistics
        ]);
    }

    /**
     * Création d'un nouveau rôle
     */
    #[Route('/roles/new', name: 'roles_new')]
    public function newRole(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $result = $this->userService->createRole($data);
            
            if ($result['success']) {
                $this->addFlash('success', 'Rôle créé avec succès.');
                return $this->redirectToRoute('admin_users_roles');
            } else {
                $this->addFlash('error', 'Erreur lors de la création: ' . $result['message']);
            }
        }

        $permissions = $this->userService->getAllPermissions();
        $permissionCategories = $this->userService->getPermissionCategories();

        return $this->render('users/role_new.html.twig', [
            'permissions' => $permissions,
            'permission_categories' => $permissionCategories
        ]);
    }

    /**
     * Modification d'un rôle
     */
    #[Route('/roles/{id}/edit', name: 'roles_edit', requirements: ['id' => '\d+'])]
    public function editRole(int $id, Request $request): Response
    {
        $role = $this->userService->getRoleById($id);
        
        if (!$role) {
            throw $this->createNotFoundException('Rôle introuvable.');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $result = $this->userService->updateRole($id, $data);
            
            if ($result['success']) {
                $this->addFlash('success', 'Rôle mis à jour avec succès.');
                return $this->redirectToRoute('admin_users_roles');
            } else {
                $this->addFlash('error', 'Erreur lors de la mise à jour: ' . $result['message']);
            }
        }

        $permissions = $this->userService->getAllPermissions();
        $rolePermissions = $this->userService->getRolePermissions($id);
        $roleUsers = $this->userService->getUsersByRole($id);

        return $this->render('users/role_edit.html.twig', [
            'role' => $role,
            'permissions' => $permissions,
            'role_permissions' => $rolePermissions,
            'role_users' => $roleUsers
        ]);
    }

    /**
     * Profil et paramètres de l'utilisateur connecté
     */
    #[Route('/profile', name: 'profile')]
    public function profile(Request $request): Response
    {
        // Simulation de l'utilisateur connecté
        $currentUser = $this->userService->getCurrentUser();
        
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $result = $this->userService->updateProfile($data);
            
            if ($result['success']) {
                $this->addFlash('success', 'Profil mis à jour avec succès.');
            } else {
                $this->addFlash('error', 'Erreur lors de la mise à jour: ' . $result['message']);
            }
            
            return $this->redirectToRoute('admin_users_profile');
        }

        $securitySettings = $this->userService->getSecuritySettings();
        $loginHistory = $this->userService->getCurrentUserLoginHistory();

        return $this->render('users/profile.html.twig', [
            'user' => $currentUser,
            'security_settings' => $securitySettings,
            'login_history' => $loginHistory
        ]);
    }

    /**
     * Changement de mot de passe
     */
    #[Route('/change-password', name: 'change_password', methods: ['POST'])]
    public function changePassword(Request $request): JsonResponse
    {
        $data = [
            'current_password' => $request->get('current_password'),
            'new_password' => $request->get('new_password'),
            'confirm_password' => $request->get('confirm_password')
        ];

        $result = $this->userService->changePassword($data);

        return $this->json($result);
    }

    /**
     * Activation/désactivation d'un utilisateur
     */
    #[Route('/{id}/toggle-status', name: 'toggle_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleStatus(int $id): JsonResponse
    {
        $result = $this->userService->toggleUserStatus($id);

        return $this->json($result);
    }

    /**
     * Réinitialisation du mot de passe d'un utilisateur
     */
    #[Route('/{id}/reset-password', name: 'reset_password', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function resetPassword(int $id): JsonResponse
    {
        $result = $this->userService->resetUserPassword($id);

        return $this->json($result);
    }

    /**
     * Verrouillage/déverrouillage d'un compte utilisateur
     */
    #[Route('/{id}/toggle-lock', name: 'toggle_lock', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleLock(int $id): JsonResponse
    {
        $result = $this->userService->toggleUserLock($id);

        return $this->json($result);
    }

    /**
     * Attribution de permissions spécifiques à un utilisateur
     */
    #[Route('/{id}/permissions', name: 'manage_permissions', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function managePermissions(int $id, Request $request): JsonResponse
    {
        $permissions = $request->get('permissions', []);
        $result = $this->userService->updateUserPermissions($id, $permissions);

        return $this->json($result);
    }

    /**
     * Audit et historique des actions utilisateurs
     */
    #[Route('/audit', name: 'audit')]
    public function audit(Request $request): Response
    {
        $filters = [
            'user_id' => $request->get('user_id'),
            'action_type' => $request->get('action_type'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'ip_address' => $request->get('ip_address')
        ];

        $auditLogs = $this->userService->getAuditLogs($filters);
        $auditStatistics = $this->userService->getAuditStatistics($filters);
        $actionTypes = $this->userService->getAuditActionTypes();

        return $this->render('users/audit.html.twig', [
            'audit_logs' => $auditLogs,
            'statistics' => $auditStatistics,
            'action_types' => $actionTypes,
            'current_filters' => $filters,
            'users' => $this->userService->getUsersForSelect()
        ]);
    }

    /**
     * Sessions actives des utilisateurs
     */
    #[Route('/sessions', name: 'sessions')]
    public function sessions(Request $request): Response
    {
        $activeSessions = $this->userService->getActiveSessions();
        $sessionStatistics = $this->userService->getSessionStatistics();

        return $this->render('users/sessions.html.twig', [
            'active_sessions' => $activeSessions,
            'statistics' => $sessionStatistics
        ]);
    }

    /**
     * Terminer une session utilisateur
     */
    #[Route('/sessions/{sessionId}/terminate', name: 'terminate_session', methods: ['POST'])]
    public function terminateSession(string $sessionId): JsonResponse
    {
        $result = $this->userService->terminateSession($sessionId);

        return $this->json($result);
    }

    /**
     * Import en lot d'utilisateurs
     */
    #[Route('/import', name: 'import', methods: ['GET', 'POST'])]
    public function import(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $uploadedFile = $request->files->get('users_file');
            $defaultRole = $request->get('default_role');
            $defaultDepartment = $request->get('default_department');
            
            if ($uploadedFile) {
                $result = $this->userService->importUsers($uploadedFile, [
                    'default_role' => $defaultRole,
                    'default_department' => $defaultDepartment
                ]);
                
                if ($result['success']) {
                    $this->addFlash('success', 'Import réalisé avec succès: ' . $result['imported_count'] . ' utilisateurs importés.');
                } else {
                    $this->addFlash('error', 'Erreur lors de l\'import: ' . $result['message']);
                }
            } else {
                $this->addFlash('error', 'Veuillez sélectionner un fichier à importer.');
            }
            
            return $this->redirectToRoute('admin_users_index');
        }

        $roles = $this->userService->getAllRoles();
        $departments = $this->userService->getDepartments();
        $importRules = $this->userService->getImportRules();

        return $this->render('users/import.html.twig', [
            'roles' => $roles,
            'departments' => $departments,
            'import_rules' => $importRules
        ]);
    }

    /**
     * Export des utilisateurs
     */
    #[Route('/export/{format}', name: 'export', methods: ['GET'])]
    public function export(string $format, Request $request): Response
    {
        $filters = [
            'role' => $request->get('role'),
            'status' => $request->get('status'),
            'department' => $request->get('department')
        ];

        return $this->userService->exportUsers($format, $filters);
    }

    /**
     * Notifications et alertes de sécurité
     */
    #[Route('/security-alerts', name: 'security_alerts')]
    public function securityAlerts(Request $request): Response
    {
        $alerts = $this->userService->getSecurityAlerts();
        $alertStatistics = $this->userService->getSecurityAlertStatistics();
        $securityPolicies = $this->userService->getSecurityPolicies();

        return $this->render('users/security_alerts.html.twig', [
            'alerts' => $alerts,
            'statistics' => $alertStatistics,
            'policies' => $securityPolicies
        ]);
    }

    /**
     * Suppression d'un utilisateur
     */
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete'.$id, $request->get('_token'))) {
            $result = $this->userService->deleteUser($id);
            
            if ($result['success']) {
                $this->addFlash('success', 'Utilisateur supprimé avec succès.');
            } else {
                $this->addFlash('error', 'Erreur lors de la suppression: ' . $result['message']);
            }
        }

        return $this->redirectToRoute('admin_users_index');
    }

    /**
     * Suppression d'un rôle
     */
    #[Route('/roles/{id}/delete', name: 'roles_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteRole(int $id, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete_role'.$id, $request->get('_token'))) {
            $result = $this->userService->deleteRole($id);
            
            if ($result['success']) {
                $this->addFlash('success', 'Rôle supprimé avec succès.');
            } else {
                $this->addFlash('error', 'Erreur lors de la suppression: ' . $result['message']);
            }
        }

        return $this->redirectToRoute('admin_users_roles');
    }
}