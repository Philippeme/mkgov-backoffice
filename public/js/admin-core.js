/**
 * MK Gov Back Office - Core JavaScript
 * Fonctionnalités communes à toutes les pages d'administration
 */

class AdminCore {
    constructor() {
        this.init();
    }

    /**
     * Initialisation des fonctionnalités communes
     */
    init() {
        console.log('Initializing MK Gov Admin Core...');

        this.initSidebar();
        this.initTooltips();
        this.initConfirmDialogs();
        this.initFormValidation();
        this.initTableFeatures();
        this.initNotifications();

        console.log('Admin Core initialized successfully');
    }

    /**
     * Gestion de la sidebar responsive
     */
    initSidebar() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const body = document.body;

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                body.classList.toggle('sidebar-collapsed');

                // Sauvegarde de l'état dans localStorage
                localStorage.setItem('sidebar-collapsed', body.classList.contains('sidebar-collapsed'));
            });
        }

        // Restauration de l'état de la sidebar
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            body.classList.add('sidebar-collapsed');
        }

        // Gestion du responsive
        this.handleResponsiveSidebar();
        window.addEventListener('resize', () => this.handleResponsiveSidebar());
    }

    /**
     * Gestion responsive de la sidebar
     */
    handleResponsiveSidebar() {
        const body = document.body;

        if (window.innerWidth < 1200) {
            body.classList.add('sidebar-mobile');
        } else {
            body.classList.remove('sidebar-mobile');
        }
    }

    /**
     * Initialisation des tooltips Bootstrap
     */
    initTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }

    /**
     * Gestion des dialogues de confirmation
     */
    initConfirmDialogs() {
        document.addEventListener('click', (e) => {
            const confirmButton = e.target.closest('[data-confirm]');
            if (confirmButton) {
                e.preventDefault();

                const message = confirmButton.getAttribute('data-confirm') || 'Êtes-vous sûr de vouloir continuer ?';
                const type = confirmButton.getAttribute('data-confirm-type') || 'warning';

                this.showConfirmDialog(message, type).then((confirmed) => {
                    if (confirmed) {
                        // Si c'est un lien, naviguer
                        if (confirmButton.tagName === 'A') {
                            window.location.href = confirmButton.href;
                        }
                        // Si c'est un formulaire, soumettre
                        else if (confirmButton.type === 'submit') {
                            confirmButton.closest('form').submit();
                        }
                        // Si c'est un bouton avec data-action, exécuter
                        else if (confirmButton.getAttribute('data-action')) {
                            const action = confirmButton.getAttribute('data-action');
                            this.executeAction(action, confirmButton);
                        }
                    }
                });
            }
        });
    }

    /**
     * Affichage d'un dialogue de confirmation
     */
    showConfirmDialog(message, type = 'warning') {
        return new Promise((resolve) => {
            // Création du modal de confirmation
            const modalHtml = `
                <div class="modal fade" id="confirmModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title">
                                    <i class="fas fa-${this.getIconForType(type)} me-2 text-${type}"></i>
                                    Confirmation
                                </h5>
                            </div>
                            <div class="modal-body">
                                <p class="mb-0">${message}</p>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    Annuler
                                </button>
                                <button type="button" class="btn btn-${type}" id="confirmAction">
                                    Confirmer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Suppression de l'ancien modal s'il existe
            const existingModal = document.getElementById('confirmModal');
            if (existingModal) {
                existingModal.remove();
            }

            // Ajout du nouveau modal
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('confirmModal'));

            // Gestion des événements
            document.getElementById('confirmAction').addEventListener('click', () => {
                modal.hide();
                resolve(true);
            });

            modal._element.addEventListener('hidden.bs.modal', () => {
                modal._element.remove();
                resolve(false);
            });

            modal.show();
        });
    }

    /**
     * Retourne l'icône appropriée pour un type
     */
    getIconForType(type) {
        const icons = {
            'warning': 'exclamation-triangle',
            'danger': 'times-circle',
            'info': 'info-circle',
            'success': 'check-circle'
        };
        return icons[type] || 'question-circle';
    }

    /**
     * Exécution d'une action personnalisée
     */
    executeAction(action, button) {
        const actions = {
            'delete': () => this.handleDelete(button),
            'archive': () => this.handleArchive(button),
            'restore': () => this.handleRestore(button),
            'export': () => this.handleExport(button)
        };

        if (actions[action]) {
            actions[action]();
        }
    }

    /**
     * Validation de formulaires
     */
    initFormValidation() {
        // Validation en temps réel
        document.querySelectorAll('form[data-validate]').forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                form.classList.add('was-validated');
            });

            // Validation des champs individuels
            form.querySelectorAll('input, select, textarea').forEach(field => {
                field.addEventListener('blur', () => this.validateField(field));
            });
        });
    }

    /**
     * Validation d'un formulaire complet
     */
    validateForm(form) {
        let isValid = true;
        const fields = form.querySelectorAll('input[required], select[required], textarea[required]');

        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        return isValid;
    }

    /**
     * Validation d'un champ individuel
     */
    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let message = '';

        // Validation des champs requis
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            message = 'Ce champ est requis';
        }
        // Validation email
        else if (field.type === 'email' && value && !this.isValidEmail(value)) {
            isValid = false;
            message = 'Veuillez saisir une adresse email valide';
        }
        // Validation téléphone
        else if (field.type === 'tel' && value && !this.isValidPhone(value)) {
            isValid = false;
            message = 'Veuillez saisir un numéro de téléphone valide';
        }
        // Validation longueur minimale
        else if (field.hasAttribute('minlength') && value.length < parseInt(field.getAttribute('minlength'))) {
            isValid = false;
            message = `Minimum ${field.getAttribute('minlength')} caractères requis`;
        }

        // Affichage de l'erreur
        this.showFieldError(field, message, !isValid);

        return isValid;
    }

    /**
     * Affichage d'une erreur de champ
     */
    showFieldError(field, message, hasError) {
        // Suppression des anciens messages d'erreur
        const existingError = field.parentNode.querySelector('.invalid-feedback');
        if (existingError) {
            existingError.remove();
        }

        if (hasError) {
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');

            // Ajout du message d'erreur
            const errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            errorDiv.textContent = message;
            field.parentNode.appendChild(errorDiv);
        } else {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        }
    }

    /**
     * Fonctionnalités des tableaux
     */
    initTableFeatures() {
        // Sélection multiple
        this.initTableSelection();

        // Tri des colonnes
        this.initTableSorting();

        // Recherche dans les tableaux
        this.initTableSearch();
    }

    /**
     * Sélection multiple dans les tableaux
     */
    initTableSelection() {
        document.querySelectorAll('#selectAll').forEach(selectAll => {
            selectAll.addEventListener('change', (e) => {
                const table = e.target.closest('table');
                const checkboxes = table.querySelectorAll('.request-checkbox, .item-checkbox');

                checkboxes.forEach(checkbox => {
                    checkbox.checked = e.target.checked;
                });

                this.updateBulkActions();
            });
        });

        document.addEventListener('change', (e) => {
            if (e.target.matches('.request-checkbox, .item-checkbox')) {
                this.updateBulkActions();
            }
        });
    }

    /**
     * Mise à jour des actions groupées
     */
    updateBulkActions() {
        const selectedItems = document.querySelectorAll('.request-checkbox:checked, .item-checkbox:checked');
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');

        if (bulkActions) {
            if (selectedItems.length > 0) {
                bulkActions.style.display = 'block';
                if (selectedCount) {
                    selectedCount.textContent = selectedItems.length;
                }
            } else {
                bulkActions.style.display = 'none';
            }
        }
    }

    /**
     * Tri des colonnes de tableau
     */
    initTableSorting() {
        document.querySelectorAll('th[data-sortable]').forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                this.sortTable(header);
            });
        });
    }

    /**
     * Tri d'un tableau
     */
    sortTable(header) {
        const table = header.closest('table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const columnIndex = Array.from(header.parentNode.children).indexOf(header);
        const isAscending = header.classList.contains('sort-desc');

        // Tri des lignes
        rows.sort((a, b) => {
            const aValue = a.cells[columnIndex].textContent.trim();
            const bValue = b.cells[columnIndex].textContent.trim();

            if (isAscending) {
                return aValue.localeCompare(bValue);
            } else {
                return bValue.localeCompare(aValue);
            }
        });

        // Réorganisation du tableau
        rows.forEach(row => tbody.appendChild(row));

        // Mise à jour des classes de tri
        table.querySelectorAll('th').forEach(th => {
            th.classList.remove('sort-asc', 'sort-desc');
        });

        header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
    }

    /**
     * Recherche dans les tableaux
     */
    initTableSearch() {
        document.querySelectorAll('[data-table-search]').forEach(searchInput => {
            const tableId = searchInput.getAttribute('data-table-search');
            const table = document.getElementById(tableId);

            if (table) {
                searchInput.addEventListener('input', (e) => {
                    this.filterTable(table, e.target.value);
                });
            }
        });
    }

    /**
     * Filtrage d'un tableau
     */
    filterTable(table, searchTerm) {
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const matches = text.includes(searchTerm.toLowerCase());
            row.style.display = matches ? '' : 'none';
        });
    }

    /**
     * Système de notifications
     */
    initNotifications() {
        // Initialisation du conteneur de notifications
        this.createNotificationContainer();

        // Auto-fermeture des alertes
        document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
            const delay = parseInt(alert.getAttribute('data-auto-dismiss')) || 5000;
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, delay);
        });
    }

    /**
     * Création du conteneur de notifications
     */
    createNotificationContainer() {
        if (!document.getElementById('notification-container')) {
            const container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
    }

    /**
     * Affichage d'une notification
     */
    showNotification(message, type = 'info', duration = 5000) {
        const container = document.getElementById('notification-container');
        const notification = document.createElement('div');
        notification.className = `toast align-items-center text-white bg-${type} border-0`;
        notification.setAttribute('role', 'alert');

        notification.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${this.getIconForType(type)} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        container.appendChild(notification);

        const bsToast = new bootstrap.Toast(notification, {
            autohide: duration > 0,
            delay: duration
        });

        bsToast.show();

        notification.addEventListener('hidden.bs.toast', () => {
            notification.remove();
        });

        return bsToast;
    }

    /**
     * Validation d'email
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Validation de numéro de téléphone
     */
    isValidPhone(phone) {
        const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
        return phoneRegex.test(phone);
    }

    /**
     * Gestion des suppressions
     */
    handleDelete(button) {
        const url = button.getAttribute('data-url');
        const id = button.getAttribute('data-id');

        if (url) {
            this.makeAjaxRequest('DELETE', url)
                .then(response => {
                    this.showNotification('Élément supprimé avec succès', 'success');
                    // Recharger la page ou supprimer l'élément du DOM
                    setTimeout(() => window.location.reload(), 1000);
                })
                .catch(error => {
                    this.showNotification('Erreur lors de la suppression', 'danger');
                });
        }
    }

    /**
     * Requête AJAX générique
     */
    makeAjaxRequest(method, url, data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        // Ajout du token CSRF si disponible
        if (window.APP_CONFIG && window.APP_CONFIG.csrfToken) {
            options.headers['X-CSRF-Token'] = window.APP_CONFIG.csrfToken;
        }

        return fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            });
    }

    /**
     * Formatage des dates
     */
    formatDate(date, format = 'short') {
        const d = new Date(date);

        if (format === 'short') {
            return d.toLocaleDateString('fr-FR');
        } else if (format === 'long') {
            return d.toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        } else if (format === 'datetime') {
            return d.toLocaleDateString('fr-FR') + ' ' + d.toLocaleTimeString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        return d.toLocaleDateString('fr-FR');
    }

    /**
     * Utilitaire pour débouncer les fonctions
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Fonctions globales pour les templates
window.changeStatus = function (requestId, status) {
    const adminCore = new AdminCore();
    const data = { status: status };

    adminCore.makeAjaxRequest('POST', `/admin/requests/${requestId}/status`, data)
        .then(response => {
            if (response.success) {
                adminCore.showNotification('Statut mis à jour avec succès', 'success');

                // Mise à jour du badge de statut
                const statusBadge = document.querySelector(`.status-badge[data-id="${requestId}"]`);
                if (statusBadge) {
                    statusBadge.className = `badge bg-${getStatusColor(status)} status-badge`;
                    statusBadge.innerHTML = `<i class="fas fa-${getStatusIcon(status)} me-1"></i>${status}`;
                }
            } else {
                adminCore.showNotification('Erreur lors de la mise à jour', 'danger');
            }
        })
        .catch(error => {
            adminCore.showNotification('Erreur de communication', 'danger');
        });
};

window.bulkChangeStatus = function (status) {
    const selectedItems = document.querySelectorAll('.request-checkbox:checked');
    const ids = Array.from(selectedItems).map(item => item.value);

    if (ids.length === 0) {
        return;
    }

    const adminCore = new AdminCore();
    const promises = ids.map(id =>
        adminCore.makeAjaxRequest('POST', `/admin/requests/${id}/status`, { status: status })
    );

    Promise.all(promises)
        .then(responses => {
            adminCore.showNotification(`${ids.length} demande(s) mise(s) à jour`, 'success');
            setTimeout(() => window.location.reload(), 1000);
        })
        .catch(error => {
            adminCore.showNotification('Erreur lors de la mise à jour groupée', 'danger');
        });
};

window.changeItemsPerPage = function (limit) {
    const url = new URL(window.location);
    url.searchParams.set('limit', limit);
    url.searchParams.set('page', 1); // Reset à la première page
    window.location = url;
};

// Utilitaires globaux
window.getStatusColor = function (status) {
    const colors = {
        'pending': 'warning',
        'processing': 'primary',
        'completed': 'success',
        'rejected': 'danger'
    };
    return colors[status] || 'secondary';
};

window.getStatusIcon = function (status) {
    const icons = {
        'pending': 'clock',
        'processing': 'sync-alt',
        'completed': 'check',
        'rejected': 'times'
    };
    return icons[status] || 'question';
};

// Initialisation automatique
document.addEventListener('DOMContentLoaded', function () {
    window.adminCore = new AdminCore();
});