/**
 * MK Gov Back Office - Dashboard JavaScript
 * Gestion des composants interactifs du tableau de bord
 */

class Dashboard {
    constructor(data) {
        this.data = data;
        this.charts = {};
        this.map = null;
        this.currentFilters = {
            region: '',
            service_family: '',
            status: '',
            year: new Date().getFullYear()
        };
        
        this.init();
    }

    /**
     * Initialisation du tableau de bord
     */
    init() {
        console.log('Initializing MK Gov Dashboard...');
        
        // Initialisation des composants
        this.initEventListeners();
        this.initMap();
        this.initCharts();
        this.loadRecentRequests();
        
        // Auto-refresh des données
        this.setupAutoRefresh();
        
        console.log('Dashboard initialized successfully');
    }

    /**
     * Configuration des écouteurs d'événements
     */
    initEventListeners() {
        // Filtres
        document.getElementById('applyFilters')?.addEventListener('click', () => {
            this.applyFilters();
        });

        document.getElementById('clearFilters')?.addEventListener('click', () => {
            this.clearFilters();
        });

        // Refresh
        document.getElementById('refreshDashboard')?.addEventListener('click', () => {
            this.refreshData();
        });

        // Plein écran pour la carte
        document.getElementById('fullscreenMap')?.addEventListener('click', () => {
            this.toggleMapFullscreen();
        });

        // Changement des filtres en temps réel
        ['regionFilter', 'serviceFilter', 'statusFilter', 'yearFilter'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('change', () => {
                    this.onFilterChange();
                });
            }
        });
    }

    /**
     * Initialisation de la carte interactive du Cameroun
     */
    initMap() {
        try {
            // Coordonnées du centre du Cameroun
            const cameroonCenter = [7.3697, 12.3547];
            
            // Initialisation de la carte Leaflet
            this.map = L.map('cameroonMap', {
                center: cameroonCenter,
                zoom: 6,
                zoomControl: true,
                attributionControl: true
            });

            // Couche de base OpenStreetMap
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 18
            }).addTo(this.map);

            // Ajout des marqueurs pour les régions
            this.addRegionMarkers();

            // Légende de la carte
            this.addMapLegend();

            console.log('Map initialized successfully');
        } catch (error) {
            console.error('Error initializing map:', error);
        }
    }

    /**
     * Ajout des marqueurs pour chaque région
     */
    addRegionMarkers() {
        if (!this.data.regions) return;

        this.data.regions.forEach(region => {
            // Calcul de la taille du marqueur basé sur le nombre de demandes
            const requestCount = region.requests_count || 0;
            const radius = Math.max(8, Math.min(30, requestCount / 50));
            
            // Couleur basée sur la densité
            const color = this.getRegionColor(requestCount);
            
            // Création du marqueur circulaire
            const marker = L.circleMarker([region.coordinates[0], region.coordinates[1]], {
                radius: radius,
                fillColor: color,
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.7
            });

            // Popup avec informations détaillées
            const popupContent = `
                <div class="map-popup">
                    <h6><strong>${region.name}</strong></h6>
                    <p><strong>Capitale :</strong> ${region.capital}</p>
                    <hr>
                    <div class="popup-stats">
                        <div class="stat-item">
                            <span class="stat-label">Demandes totales :</span>
                            <span class="stat-value">${requestCount.toLocaleString()}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Population :</span>
                            <span class="stat-value">${region.population.toLocaleString()}</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Densité :</span>
                            <span class="stat-value">${(requestCount / (region.population / 100000)).toFixed(2)} pour 100k hab.</span>
                        </div>
                    </div>
                </div>
            `;

            marker.bindPopup(popupContent);
            marker.addTo(this.map);

            // Événement de clic sur la région
            marker.on('click', () => {
                this.filterByRegion(region.id);
            });
        });
    }

    /**
     * Détermine la couleur d'une région basée sur le nombre de demandes
     */
    getRegionColor(requestCount) {
        if (requestCount > 2000) return '#d32f2f';       // Rouge foncé
        if (requestCount > 1000) return '#f57c00';       // Orange
        if (requestCount > 500) return '#fbc02d';        // Jaune
        if (requestCount > 100) return '#689f38';        // Vert
        return '#1976d2';                               // Bleu
    }

    /**
     * Ajout de la légende de la carte
     */
    addMapLegend() {
        const legend = L.control({position: 'bottomright'});
        
        legend.onAdd = function(map) {
            const div = L.DomUtil.create('div', 'map-legend');
            div.innerHTML = `
                <h6>Nombre de demandes</h6>
                <div class="legend-item"><span class="legend-color" style="background: #d32f2f;"></span> > 2000</div>
                <div class="legend-item"><span class="legend-color" style="background: #f57c00;"></span> 1000 - 2000</div>
                <div class="legend-item"><span class="legend-color" style="background: #fbc02d;"></span> 500 - 1000</div>
                <div class="legend-item"><span class="legend-color" style="background: #689f38;"></span> 100 - 500</div>
                <div class="legend-item"><span class="legend-color" style="background: #1976d2;"></span> < 100</div>
            `;
            div.style.cssText = `
                background: white;
                padding: 10px;
                border-radius: 5px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                font-size: 12px;
            `;
            return div;
        };
        
        legend.addTo(this.map);
    }

    /**
     * Initialisation de tous les graphiques
     */
    initCharts() {
        this.initStatusDonutChart();
        this.initServicesBarChart();
        this.initMonthlyTrendsChart();
        this.initRegionalCompletionChart();
        this.initPendingByAdminChart();
    }

    /**
     * Graphique en donut des statuts
     */
    initStatusDonutChart() {
        const ctx = document.getElementById('statusDonutChart');
        if (!ctx) return;

        const data = {
            labels: ['Terminées', 'En cours', 'En attente', 'Rejetées'],
            datasets: [{
                data: [8450, 2340, 890, 320],
                backgroundColor: ['#28a745', '#007bff', '#ffc107', '#dc3545'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        };

        this.charts.statusDonut = new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }

    /**
     * Graphique en barres des services populaires
     */
    initServicesBarChart() {
        const ctx = document.getElementById('servicesBarChart');
        if (!ctx) return;

        const data = {
            labels: ['Passeport', 'Acte naissance', 'CNI', 'Permis conduire'],
            datasets: [{
                data: [3450, 2890, 1560, 890],
                backgroundColor: ['#1a73e8', '#34a853', '#ff6d01', '#9c27b0'],
                borderRadius: 4
            }]
        };

        this.charts.servicesBar = new Chart(ctx, {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    /**
     * Graphique linéaire des tendances mensuelles
     */
    initMonthlyTrendsChart() {
        const ctx = document.getElementById('monthlyTrendsChart');
        if (!ctx) return;

        const months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
        
        const data = {
            labels: months,
            datasets: [
                {
                    label: 'Demandes soumises',
                    data: [450, 520, 480, 630, 590, 720, 680, 750, 690, 580, 520, 480],
                    borderColor: '#1a73e8',
                    backgroundColor: 'rgba(26, 115, 232, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Demandes traitées',
                    data: [420, 480, 460, 580, 550, 680, 640, 710, 650, 540, 490, 450],
                    borderColor: '#34a853',
                    backgroundColor: 'rgba(52, 168, 83, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        };

        this.charts.monthlyTrends = new Chart(ctx, {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    /**
     * Graphique de complétion par région
     */
    initRegionalCompletionChart() {
        const ctx = document.getElementById('regionalCompletionChart');
        if (!ctx) return;

        const data = {
            labels: ['Centre', 'Littoral', 'Ouest', 'Nord-Ouest', 'Sud-Ouest', 'Nord', 'Adamaoua', 'Est', 'Sud', 'Extrême-Nord'],
            datasets: [{
                label: 'Taux de complétion (%)',
                data: [95, 92, 88, 85, 87, 90, 84, 86, 89, 82],
                backgroundColor: '#34a853',
                borderRadius: 4
            }]
        };

        this.charts.regionalCompletion = new Chart(ctx, {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    /**
     * Graphique des demandes en attente par administration
     */
    initPendingByAdminChart() {
        const ctx = document.getElementById('pendingByAdminChart');
        if (!ctx) return;

        const data = {
            labels: ['DGSN', 'État Civil', 'Ministère Transport', 'Ministère Éducation', 'CFCE'],
            datasets: [{
                label: 'Demandes en attente',
                data: [245, 189, 156, 98, 67],
                backgroundColor: ['#ff6d01', '#9c27b0', '#607d8b', '#795548', '#607d8b'],
                borderRadius: 4
            }]
        };

        this.charts.pendingByAdmin = new Chart(ctx, {
            type: 'bar',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    /**
     * Chargement des demandes récentes
     */
    loadRecentRequests() {
        const container = document.getElementById('recentRequestsList');
        if (!container) return;

        // Simulation de données récentes
        const recentRequests = [
            {
                id: 'REQ-001245',
                service: 'Passeport',
                citizen: 'Jean Dupont',
                status: 'processing',
                time: '2 min',
                icon: 'fas fa-passport',
                statusColor: 'primary'
            },
            {
                id: 'REQ-001244',
                service: 'Acte de naissance',
                citizen: 'Marie Ngono',
                status: 'completed',
                time: '15 min',
                icon: 'fas fa-certificate',
                statusColor: 'success'
            },
            {
                id: 'REQ-001243',
                service: 'CNI',
                citizen: 'Paul Mballa',
                status: 'pending',
                time: '1h',
                icon: 'fas fa-id-card',
                statusColor: 'warning'
            }
        ];

        let html = '';
        recentRequests.forEach(request => {
            html += `
                <div class="list-group-item list-group-item-action">
                    <div class="d-flex align-items-center">
                        <div class="request-icon me-3">
                            <i class="${request.icon} text-${request.statusColor}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">
                                <h6 class="mb-1">${request.id}</h6>
                                <small class="text-muted">${request.time}</small>
                            </div>
                            <p class="mb-1">${request.service}</p>
                            <small class="text-muted">${request.citizen}</small>
                        </div>
                        <div class="request-status">
                            <span class="badge bg-${request.statusColor}">${request.status}</span>
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    /**
     * Application des filtres
     */
    applyFilters() {
        // Récupération des valeurs des filtres
        this.currentFilters = {
            region: document.getElementById('regionFilter')?.value || '',
            service_family: document.getElementById('serviceFilter')?.value || '',
            status: document.getElementById('statusFilter')?.value || '',
            year: document.getElementById('yearFilter')?.value || new Date().getFullYear()
        };

        console.log('Applying filters:', this.currentFilters);

        // Affichage du loader
        this.showLoading();

        // Simulation d'un appel API
        setTimeout(() => {
            this.updateDashboardData();
            this.hideLoading();
        }, 1000);
    }

    /**
     * Effacement des filtres
     */
    clearFilters() {
        ['regionFilter', 'serviceFilter', 'statusFilter'].forEach(id => {
            const element = document.getElementById(id);
            if (element) element.value = '';
        });

        const yearFilter = document.getElementById('yearFilter');
        if (yearFilter) yearFilter.value = new Date().getFullYear();

        this.applyFilters();
    }

    /**
     * Changement de filtre en temps réel
     */
    onFilterChange() {
        // Debounce pour éviter trop d'appels
        clearTimeout(this.filterTimeout);
        this.filterTimeout = setTimeout(() => {
            this.applyFilters();
        }, 500);
    }

    /**
     * Mise à jour des données du tableau de bord
     */
    updateDashboardData() {
        // Simulation de nouvelles données basées sur les filtres
        this.updateStatistics();
        this.updateCharts();
        this.updateMap();
        this.loadRecentRequests();
    }

    /**
     * Mise à jour des statistiques
     */
    updateStatistics() {
        // Simulation de nouvelles valeurs
        const stats = {
            total: Math.floor(Math.random() * 10000) + 5000,
            completed: Math.floor(Math.random() * 8000) + 4000,
            processing: Math.floor(Math.random() * 2000) + 500,
            rate: (Math.random() * 10 + 85).toFixed(1)
        };

        document.getElementById('totalRequests').textContent = stats.total.toLocaleString();
        document.getElementById('completedRequests').textContent = stats.completed.toLocaleString();
        document.getElementById('processingRequests').textContent = stats.processing.toLocaleString();
        document.getElementById('completionRate').textContent = stats.rate + '%';
    }

    /**
     * Mise à jour des graphiques
     */
    updateCharts() {
        // Mise à jour avec de nouvelles données simulées
        Object.values(this.charts).forEach(chart => {
            if (chart && chart.data && chart.data.datasets) {
                chart.data.datasets.forEach(dataset => {
                    dataset.data = dataset.data.map(() => Math.floor(Math.random() * 1000) + 100);
                });
                chart.update();
            }
        });
    }

    /**
     * Mise à jour de la carte
     */
    updateMap() {
        if (this.map) {
            // Simulation de mise à jour des marqueurs
            this.map.eachLayer(layer => {
                if (layer instanceof L.CircleMarker) {
                    const newRadius = Math.max(8, Math.min(30, Math.random() * 50));
                    layer.setRadius(newRadius);
                }
            });
        }
    }

    /**
     * Filtrage par région depuis la carte
     */
    filterByRegion(regionId) {
        const regionFilter = document.getElementById('regionFilter');
        if (regionFilter) {
            regionFilter.value = regionId;
            this.applyFilters();
        }
    }

    /**
     * Basculement en plein écran pour la carte
     */
    toggleMapFullscreen() {
        const mapContainer = document.getElementById('cameroonMap').parentElement.parentElement;
        
        if (!document.fullscreenElement) {
            mapContainer.requestFullscreen().then(() => {
                setTimeout(() => {
                    this.map.invalidateSize();
                }, 100);
            });
        } else {
            document.exitFullscreen();
        }
    }

    /**
     * Actualisation des données
     */
    refreshData() {
        console.log('Refreshing dashboard data...');
        this.showLoading();
        
        setTimeout(() => {
            this.updateDashboardData();
            this.hideLoading();
            this.showNotification('Données actualisées avec succès', 'success');
        }, 1500);
    }

    /**
     * Configuration du rafraîchissement automatique
     */
    setupAutoRefresh() {
        // Rafraîchissement automatique toutes les 5 minutes
        setInterval(() => {
            this.refreshData();
        }, 5 * 60 * 1000);
    }

    /**
     * Affichage du loader
     */
    showLoading() {
        const loader = document.getElementById('loading-overlay');
        if (loader) {
            loader.classList.remove('d-none');
        }
    }

    /**
     * Masquage du loader
     */
    hideLoading() {
        const loader = document.getElementById('loading-overlay');
        if (loader) {
            loader.classList.add('d-none');
        }
    }

    /**
     * Affichage d'une notification
     */
    showNotification(message, type = 'info') {
        // Création d'une notification toast
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        // Ajout au DOM
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }

        container.appendChild(toast);

        // Affichage et suppression automatique
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
}

// Styles CSS additionnels pour la carte
const mapStyles = `
    <style>
        .map-popup {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .map-popup h6 {
            margin-bottom: 0.5rem;
            color: #1a73e8;
        }
        .popup-stats {
            margin-top: 0.5rem;
        }
        .stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.25rem;
        }
        .stat-label {
            font-weight: 500;
            color: #6c757d;
        }
        .stat-value {
            font-weight: 600;
            color: #212529;
        }
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 4px;
        }
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            margin-right: 8px;
            border: 1px solid rgba(0,0,0,0.2);
        }
    </style>
`;

// Injection des styles
if (!document.getElementById('map-styles')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'map-styles';
    styleElement.innerHTML = mapStyles;
    document.head.appendChild(styleElement);
}

// Export pour utilisation globale
window.Dashboard = Dashboard;