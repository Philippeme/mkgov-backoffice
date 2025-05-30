<?php

namespace App\Controller;

use App\Service\SystemDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de gestion et administration système MK Gov
 * 
 * Ce contrôleur centralise les fonctionnalités d'administration système
 * incluant la configuration, surveillance, maintenance, logs système
 * et gestion des sauvegardes du portail gouvernemental.
 */
#[Route('/admin/system', name: 'admin_system_')]
class SystemController extends AbstractController
{
    public function __construct(
        private SystemDataService $systemService
    ) {}

    /**
     * Interface principale d'administration système
     */
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        $systemInfo = $this->systemService->getSystemInformation();
        $healthCheck = $this->systemService->getSystemHealthCheck();
        $recentActivity = $this->systemService->getRecentSystemActivity();
        $alertsSummary = $this->systemService->getSystemAlertsSummary();

        return $this->render('system/index.html.twig', [
            'system_info' => $systemInfo,
            'health_check' => $healthCheck,
            'recent_activity' => $recentActivity,
            'alerts_summary' => $alertsSummary
        ]);
    }

    /**
     * Gestion des paramètres système globaux
     */
    #[Route('/settings', name: 'settings')]
    public function settings(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $settings = $request->request->all();
            $result = $this->systemService->updateSystemSettings($settings);
            
            if ($result['success']) {
                $this->addFlash('success', 'Paramètres système mis à jour avec succès.');
            } else {
                $this->addFlash('error', 'Erreur lors de la mise à jour: ' . $result['message']);
            }
            
            return $this->redirectToRoute('admin_system_settings');
        }

        $currentSettings = $this->systemService->getSystemSettings();
        $settingCategories = $this->systemService->getSettingCategories();
        $configurationSchema = $this->systemService->getConfigurationSchema();

        return $this->render('system/settings.html.twig', [
            'current_settings' => $currentSettings,
            'setting_categories' => $settingCategories,
            'configuration_schema' => $configurationSchema
        ]);
    }

    /**
     * Gestion des données de référence système
     */
    #[Route('/reference-data', name: 'reference_data')]
    public function referenceData(Request $request): Response
    {
        $category = $request->get('category', 'all');
        
        $referenceData = $this->systemService->getReferenceData($category);
        $dataCategories = $this->systemService->getReferenceDataCategories();
        $dataStatistics = $this->systemService->getReferenceDataStatistics();

        return $this->render('system/reference_data.html.twig', [
            'reference_data' => $referenceData,
            'data_categories' => $dataCategories,
            'data_statistics' => $dataStatistics,
            'selected_category' => $category
        ]);
    }

    /**
     * Mise à jour des données de référence
     */
    #[Route('/reference-data/update', name: 'reference_data_update', methods: ['POST'])]
    public function updateReferenceData(Request $request): JsonResponse
    {
        $category = $request->get('category');
        $data = $request->get('data', []);
        
        $result = $this->systemService->updateReferenceData($category, $data);
        
        return $this->json($result);
    }

    /**
     * Consultation des logs système
     */
    #[Route('/logs', name: 'logs')]
    public function logs(Request $request): Response
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 50);
        $filters = [
            'level' => $request->get('level'),
            'component' => $request->get('component'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search')
        ];

        $logs = $this->systemService->getSystemLogs($page, $limit, $filters);
        $logStatistics = $this->systemService->getLogStatistics($filters);
        $logLevels = $this->systemService->getLogLevels();
        $systemComponents = $this->systemService->getSystemComponents();

        return $this->render('system/logs.html.twig', [
            'logs' => $logs,
            'log_statistics' => $logStatistics,
            'log_levels' => $logLevels,
            'system_components' => $systemComponents,
            'current_filters' => $filters,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $logs['total_pages'],
                'total_items' => $logs['total_items']
            ]
        ]);
    }

    /**
     * Analyse approfondie des logs
     */
    #[Route('/logs/analysis', name: 'logs_analysis')]
    public function logsAnalysis(Request $request): Response
    {
        $period = $request->get('period', '24h');
        $analysisType = $request->get('analysis_type', 'errors');

        $logAnalysis = $this->systemService->getLogAnalysis($period, $analysisType);
        $errorPatterns = $this->systemService->getErrorPatterns($period);
        $performanceMetrics = $this->systemService->getPerformanceMetrics($period);

        return $this->render('system/logs_analysis.html.twig', [
            'log_analysis' => $logAnalysis,
            'error_patterns' => $errorPatterns,
            'performance_metrics' => $performanceMetrics,
            'selected_period' => $period,
            'analysis_type' => $analysisType
        ]);
    }

    /**
     * Gestion des sauvegardes système
     */
    #[Route('/backup', name: 'backup')]
    public function backup(Request $request): Response
    {
        $backups = $this->systemService->getSystemBackups();
        $backupSettings = $this->systemService->getBackupSettings();
        $storageInfo = $this->systemService->getBackupStorageInfo();
        $scheduleInfo = $this->systemService->getBackupScheduleInfo();

        return $this->render('system/backup.html.twig', [
            'backups' => $backups,
            'backup_settings' => $backupSettings,
            'storage_info' => $storageInfo,
            'schedule_info' => $scheduleInfo
        ]);
    }

    /**
     * Création d'une sauvegarde manuelle
     */
    #[Route('/backup/create', name: 'backup_create', methods: ['POST'])]
    public function createBackup(Request $request): JsonResponse
    {
        $backupType = $request->get('backup_type', 'full');
        $description = $request->get('description', '');
        
        $result = $this->systemService->createBackup($backupType, $description);
        
        return $this->json($result);
    }

    /**
     * Restauration depuis une sauvegarde
     */
    #[Route('/backup/{id}/restore', name: 'backup_restore', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function restoreBackup(int $id, Request $request): JsonResponse
    {
        $restoreOptions = [
            'database' => $request->get('restore_database', false),
            'files' => $request->get('restore_files', false),
            'settings' => $request->get('restore_settings', false)
        ];
        
        $result = $this->systemService->restoreFromBackup($id, $restoreOptions);
        
        return $this->json($result);
    }

    /**
     * Suppression d'une sauvegarde
     */
    #[Route('/backup/{id}/delete', name: 'backup_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteBackup(int $id): JsonResponse
    {
        $result = $this->systemService->deleteBackup($id);
        
        return $this->json($result);
    }

    /**
     * Téléchargement d'une sauvegarde
     */
    #[Route('/backup/{id}/download', name: 'backup_download', requirements: ['id' => '\d+'])]
    public function downloadBackup(int $id): Response
    {
        $downloadResult = $this->systemService->downloadBackup($id);
        
        if (!$downloadResult['success']) {
            $this->addFlash('error', $downloadResult['message']);
            return $this->redirectToRoute('admin_system_backup');
        }
        
        return $downloadResult['response'];
    }

    /**
     * Configuration de la planification des sauvegardes
     */
    #[Route('/backup/schedule', name: 'backup_schedule', methods: ['POST'])]
    public function scheduleBackup(Request $request): JsonResponse
    {
        $scheduleConfig = [
            'frequency' => $request->get('frequency'),
            'time' => $request->get('time'),
            'backup_type' => $request->get('backup_type'),
            'retention_days' => $request->get('retention_days'),
            'enabled' => $request->get('enabled', false)
        ];
        
        $result = $this->systemService->updateBackupSchedule($scheduleConfig);
        
        return $this->json($result);
    }

    /**
     * Monitoring système en temps réel
     */
    #[Route('/monitoring', name: 'monitoring')]
    public function monitoring(Request $request): Response
    {
        $monitoringData = $this->systemService->getSystemMonitoring();
        $performanceMetrics = $this->systemService->getRealTimeMetrics();
        $activeProcesses = $this->systemService->getActiveProcesses();
        $resourceUsage = $this->systemService->getResourceUsage();

        return $this->render('system/monitoring.html.twig', [
            'monitoring_data' => $monitoringData,
            'performance_metrics' => $performanceMetrics,
            'active_processes' => $activeProcesses,
            'resource_usage' => $resourceUsage
        ]);
    }

    /**
     * API pour les données de monitoring en temps réel
     */
    #[Route('/monitoring/api/realtime', name: 'monitoring_api_realtime', methods: ['GET'])]
    public function realtimeMonitoringApi(): JsonResponse
    {
        $realtimeData = $this->systemService->getRealTimeMonitoringData();
        
        return $this->json($realtimeData);
    }

    /**
     * Gestion des tâches planifiées (Cron Jobs)
     */
    #[Route('/tasks', name: 'tasks')]
    public function tasks(Request $request): Response
    {
        $scheduledTasks = $this->systemService->getScheduledTasks();
        $taskHistory = $this->systemService->getTaskExecutionHistory();
        $taskStatistics = $this->systemService->getTaskStatistics();

        return $this->render('system/tasks.html.twig', [
            'scheduled_tasks' => $scheduledTasks,
            'task_history' => $taskHistory,
            'task_statistics' => $taskStatistics
        ]);
    }

    /**
     * Exécution manuelle d'une tâche
     */
    #[Route('/tasks/{id}/run', name: 'tasks_run', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function runTask(int $id): JsonResponse
    {
        $result = $this->systemService->executeTask($id);
        
        return $this->json($result);
    }

    /**
     * Activation/désactivation d'une tâche planifiée
     */
    #[Route('/tasks/{id}/toggle', name: 'tasks_toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleTask(int $id): JsonResponse
    {
        $result = $this->systemService->toggleTask($id);
        
        return $this->json($result);
    }

    /**
     * Cache système et optimisation
     */
    #[Route('/cache', name: 'cache')]
    public function cache(Request $request): Response
    {
        $cacheInfo = $this->systemService->getCacheInformation();
        $cacheStatistics = $this->systemService->getCacheStatistics();
        $optimizationSuggestions = $this->systemService->getOptimizationSuggestions();

        return $this->render('system/cache.html.twig', [
            'cache_info' => $cacheInfo,
            'cache_statistics' => $cacheStatistics,
            'optimization_suggestions' => $optimizationSuggestions
        ]);
    }

    /**
     * Nettoyage du cache système
     */
    #[Route('/cache/clear', name: 'cache_clear', methods: ['POST'])]
    public function clearCache(Request $request): JsonResponse
    {
        $cacheTypes = $request->get('cache_types', []);
        $result = $this->systemService->clearCache($cacheTypes);
        
        return $this->json($result);
    }

    /**
     * Rechargement du cache système
     */
    #[Route('/cache/warm-up', name: 'cache_warm_up', methods: ['POST'])]
    public function warmUpCache(): JsonResponse
    {
        $result = $this->systemService->warmUpCache();
        
        return $this->json($result);
    }

    /**
     * Sécurité et mise à jour système
     */
    #[Route('/security', name: 'security')]
    public function security(Request $request): Response
    {
        $securityAudit = $this->systemService->getSecurityAudit();
        $securityUpdates = $this->systemService->getAvailableSecurityUpdates();
        $vulnerabilityScans = $this->systemService->getVulnerabilityScans();
        $securityConfig = $this->systemService->getSecurityConfiguration();

        return $this->render('system/security.html.twig', [
            'security_audit' => $securityAudit,
            'security_updates' => $securityUpdates,
            'vulnerability_scans' => $vulnerabilityScans,
            'security_config' => $securityConfig
        ]);
    }

    /**
     * Lancement d'un scan de sécurité
     */
    #[Route('/security/scan', name: 'security_scan', methods: ['POST'])]
    public function securityScan(Request $request): JsonResponse
    {
        $scanType = $request->get('scan_type', 'full');
        $result = $this->systemService->runSecurityScan($scanType);
        
        return $this->json($result);
    }

    /**
     * Application des mises à jour de sécurité
     */
    #[Route('/security/update', name: 'security_update', methods: ['POST'])]
    public function applySecurityUpdates(Request $request): JsonResponse
    {
        $updateIds = $request->get('update_ids', []);
        $result = $this->systemService->applySecurityUpdates($updateIds);
        
        return $this->json($result);
    }

    /**
     * Maintenance système
     */
    #[Route('/maintenance', name: 'maintenance')]
    public function maintenance(Request $request): Response
    {
        $maintenanceInfo = $this->systemService->getMaintenanceInfo();
        $maintenanceHistory = $this->systemService->getMaintenanceHistory();
        $upcomingMaintenance = $this->systemService->getUpcomingMaintenance();

        return $this->render('system/maintenance.html.twig', [
            'maintenance_info' => $maintenanceInfo,
            'maintenance_history' => $maintenanceHistory,
            'upcoming_maintenance' => $upcomingMaintenance
        ]);
    }

    /**
     * Activation du mode maintenance
     */
    #[Route('/maintenance/enable', name: 'maintenance_enable', methods: ['POST'])]
    public function enableMaintenance(Request $request): JsonResponse
    {
        $maintenanceConfig = [
            'message' => $request->get('message', 'System under maintenance'),
            'duration' => $request->get('duration'),
            'allowed_ips' => $request->get('allowed_ips', [])
        ];
        
        $result = $this->systemService->enableMaintenanceMode($maintenanceConfig);
        
        return $this->json($result);
    }

    /**
     * Désactivation du mode maintenance
     */
    #[Route('/maintenance/disable', name: 'maintenance_disable', methods: ['POST'])]
    public function disableMaintenance(): JsonResponse
    {
        $result = $this->systemService->disableMaintenanceMode();
        
        return $this->json($result);
    }

    /**
     * Configuration des notifications système
     */
    #[Route('/notifications', name: 'notifications')]
    public function notifications(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $notificationSettings = $request->request->all();
            $result = $this->systemService->updateNotificationSettings($notificationSettings);
            
            if ($result['success']) {
                $this->addFlash('success', 'Paramètres de notification mis à jour avec succès.');
            } else {
                $this->addFlash('error', 'Erreur lors de la mise à jour: ' . $result['message']);
            }
            
            return $this->redirectToRoute('admin_system_notifications');
        }

        $notificationSettings = $this->systemService->getNotificationSettings();
        $notificationChannels = $this->systemService->getNotificationChannels();
        $alertTypes = $this->systemService->getAlertTypes();

        return $this->render('system/notifications.html.twig', [
            'notification_settings' => $notificationSettings,
            'notification_channels' => $notificationChannels,
            'alert_types' => $alertTypes
        ]);
    }

    /**
     * Test des notifications système
     */
    #[Route('/notifications/test', name: 'notifications_test', methods: ['POST'])]
    public function testNotifications(Request $request): JsonResponse
    {
        $channel = $request->get('channel');
        $message = $request->get('message', 'Test notification from MK Gov System');
        
        $result = $this->systemService->sendTestNotification($channel, $message);
        
        return $this->json($result);
    }

    /**
     * Export des configurations système
     */
    #[Route('/export-config', name: 'export_config')]
    public function exportConfiguration(): Response
    {
        $configExport = $this->systemService->exportSystemConfiguration();
        
        return $configExport;
    }

    /**
     * Import de configuration système
     */
    #[Route('/import-config', name: 'import_config', methods: ['POST'])]
    public function importConfiguration(Request $request): Response
    {
        $configFile = $request->files->get('config_file');
        
        if ($configFile) {
            $result = $this->systemService->importSystemConfiguration($configFile);
            
            if ($result['success']) {
                $this->addFlash('success', 'Configuration importée avec succès.');
            } else {
                $this->addFlash('error', 'Erreur lors de l\'import: ' . $result['message']);
            }
        } else {
            $this->addFlash('error', 'Veuillez sélectionner un fichier de configuration.');
        }
        
        return $this->redirectToRoute('admin_system_settings');
    }
}