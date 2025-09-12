/**
 * Workflow Management JavaScript
 * Handles all frontend interactions for the automated workflow system
 */

class WorkflowManager {
    constructor() {
        this.ajaxUrl = donapp_admin.ajax_url;
        this.nonce = donapp_admin.nonce;
        this.currentUser = donapp_admin.current_user;

        this.init();
        this.bindEvents();
    }

    init() {
        // Initialize dashboard if we're on the workflow page
        if (document.getElementById('workflow-dashboard')) {
            this.loadDashboardData();
        }

        // Initialize manager assignment form
        if (document.getElementById('manager-assignment-form')) {
            this.initializeManagerForm();
        }

        // Initialize task management
        if (document.getElementById('workflow-tasks')) {
            this.loadUserTasks();
        }

        // Auto-refresh dashboard every 30 seconds
        setInterval(() => {
            if (document.getElementById('workflow-dashboard')) {
                this.loadDashboardData();
            }
        }, 30000);
    }

    bindEvents() {
        // Task approval/rejection buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('approve-task-btn')) {
                e.preventDefault();
                this.handleTaskAction(e.target, 'approve');
            }

            if (e.target.classList.contains('reject-task-btn')) {
                e.preventDefault();
                this.handleTaskAction(e.target, 'reject');
            }

            if (e.target.classList.contains('view-history-btn')) {
                e.preventDefault();
                this.viewEntryHistory(e.target.dataset.entryId);
            }

            if (e.target.classList.contains('create-test-workflow')) {
                e.preventDefault();
                this.createTestWorkflow();
            }
        });

        // Manager assignment form submission
        document.addEventListener('submit', (e) => {
            if (e.target.id === 'manager-assignment-form') {
                e.preventDefault();
                this.submitManagerAssignment(e.target);
            }
        });

        // Search and filter functionality
        const searchInput = document.getElementById('task-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.filterTasks(e.target.value);
            });
        }

        const statusFilter = document.getElementById('status-filter');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.filterTasksByStatus(e.target.value);
            });
        }
    }

    async loadDashboardData() {
        try {
            const response = await this.makeRequest('workflow/dashboard', 'GET');
            if (response.success) {
                this.updateDashboardStats(response.data.stats);
                this.updateRecentActivities(response.data.recent_activities);
                this.updatePendingTasks(response.data.pending_tasks);
            }
        } catch (error) {
            console.error('Failed to load dashboard data:', error);
            this.showNotification('خطا در بارگیری داده‌های داشبورد', 'error');
        }
    }

    async loadUserTasks() {
        try {
            this.showSpinner('#workflow-tasks');

            const response = await this.makeRequest('workflow/my-tasks', 'GET');
            if (response.success) {
                this.renderUserTasks(response.data.tasks, response.data.user_role);
                this.updateTasksCounter(response.data.total_count);
            }
        } catch (error) {
            console.error('Failed to load user tasks:', error);
            this.showNotification('خطا در بارگیری وظایف', 'error');
        } finally {
            this.hideSpinner('#workflow-tasks');
        }
    }

    async handleTaskAction(button, action) {
        const entryId = button.dataset.entryId;
        const stepId = button.dataset.stepId;
        const stepName = button.dataset.stepName;
        const stepOrder = button.dataset.stepOrder;

        // Show confirmation dialog
        const actionText = action === 'approve' ? 'تأیید' : 'رد';
        const confirmed = confirm(`آیا مطمئن هستید که می‌خواهید این درخواست را ${actionText} کنید؟`);

        if (!confirmed) return;

        // Get notes if rejecting
        let notes = '';
        if (action === 'reject') {
            notes = prompt('لطفاً دلیل رد درخواست را وارد کنید:');
            if (notes === null) return; // User cancelled
        }

        try {
            button.disabled = true;
            button.innerHTML = 'در حال پردازش...';

            const response = await this.makeRequest('workflow/task-action', 'POST', {
                entry_id: entryId,
                step_id: stepId,
                step_name: stepName,
                step_order: stepOrder,
                task_action: action,
                notes: notes,
                nonce: this.nonce
            });

            if (response.success) {
                this.showNotification(response.data.message, 'success');

                // Remove task from UI or update status
                const taskRow = button.closest('.task-row');
                if (taskRow) {
                    taskRow.style.opacity = '0.5';
                    taskRow.querySelector('.task-status').textContent =
                        action === 'approve' ? 'تأیید شده' : 'رد شده';
                }

                // Reload tasks after a short delay
                setTimeout(() => {
                    this.loadUserTasks();
                }, 1000);

            } else {
                throw new Error(response.data.message || 'خطا در انجام عملیات');
            }

        } catch (error) {
            console.error('Task action failed:', error);
            this.showNotification(error.message || 'خطا در انجام عملیات', 'error');
        } finally {
            button.disabled = false;
            button.innerHTML = actionText;
        }
    }

    async viewEntryHistory(entryId) {
        try {
            const response = await this.makeRequest(`workflow/history?entry_id=${entryId}`, 'GET');
            if (response.success) {
                this.showHistoryModal(response.data.history, entryId);
            }
        } catch (error) {
            console.error('Failed to load entry history:', error);
            this.showNotification('خطا در بارگیری تاریخچه', 'error');
        }
    }

    async createTestWorkflow() {
        if (!confirm('آیا می‌خواهید یک گردش کاری تست ایجاد کنید؟')) return;

        try {
            const response = await this.makeRequest('workflow/test', 'POST', {
                nonce: this.nonce
            });

            if (response.success) {
                this.showNotification(response.data.message, 'success');
                // Refresh dashboard
                setTimeout(() => {
                    this.loadDashboardData();
                    this.loadUserTasks();
                }, 1000);
            }
        } catch (error) {
            console.error('Failed to create test workflow:', error);
            this.showNotification('خطا در ایجاد گردش کاری تست', 'error');
        }
    }

    async submitManagerAssignment(form) {
        try {
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');

            submitButton.disabled = true;
            submitButton.textContent = 'در حال پردازش...';

            // Here you would typically send the manager assignment data
            // For now, we'll just show a success message
            this.showNotification('مدیر با موفقیت تعیین شد', 'success');

            // Reset form
            form.reset();

        } catch (error) {
            console.error('Manager assignment failed:', error);
            this.showNotification('خطا در تعیین مدیر', 'error');
        } finally {
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = false;
            submitButton.textContent = 'تعیین مدیر';
        }
    }

    updateDashboardStats(stats) {
        const elements = {
            'total-workflows': stats.total_workflows,
            'pending-approvals': stats.pending_approvals,
            'completed-workflows': stats.completed_workflows,
            'today-activities': stats.today_activities,
            'this-month-workflows': stats.this_month_workflows,
            'active-managers': stats.active_managers
        };

        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value.toLocaleString('fa-IR');
            }
        });
    }

    updateRecentActivities(activities) {
        const container = document.getElementById('recent-activities');
        if (!container || !activities.length) return;

        const html = activities.map(activity => `
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="dashicons ${this.getActivityIcon(activity.action)}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-description">
                        ${this.getActivityDescription(activity)}
                    </div>
                    <div class="activity-time">
                        ${activity.formatted_time} پیش
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    updatePendingTasks(tasks) {
        const container = document.getElementById('pending-tasks-summary');
        if (!container) return;

        if (!tasks.length) {
            container.innerHTML = '<p>هیچ وظیفه معلقی وجود ندارد</p>';
            return;
        }

        const html = tasks.slice(0, 5).map(task => `
            <div class="pending-task-item">
                <div class="task-form">${task.form_title}</div>
                <div class="task-date">${task.entry_date}</div>
                <div class="task-status">در انتظار بررسی</div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    renderUserTasks(tasks, userRole) {
        const container = document.getElementById('user-tasks-list');
        if (!container) return;

        if (!tasks.length) {
            container.innerHTML = `
                <div class="no-tasks">
                    <i class="dashicons dashicons-yes-alt"></i>
                    <p>شما هیچ وظیفه معلقی ندارید</p>
                </div>
            `;
            return;
        }

        const html = tasks.map(task => this.renderTaskItem(task, userRole)).join('');
        container.innerHTML = html;
    }

    renderTaskItem(task, userRole) {
        const canApprove = userRole === 'school_manager' || userRole === 'province_manager' || userRole === 'admin';

        return `
            <div class="task-row" data-entry-id="${task.entry_id}">
                <div class="task-info">
                    <div class="task-title">${task.form_title}</div>
                    <div class="task-meta">
                        <span class="task-date">${task.entry_date}</span>
                        <span class="task-submitter">توسط: ${task.submitter ? task.submitter.display_name : 'نامشخص'}</span>
                    </div>
                </div>
                <div class="task-status">${task.status === 'pending' ? 'در انتظار بررسی' : task.status}</div>
                <div class="task-actions">
                    ${canApprove ? `
                        <button class="button button-primary approve-task-btn" 
                                data-entry-id="${task.entry_id}"
                                data-step-id="1"
                                data-step-name="بررسی مدیر"
                                data-step-order="1">
                            تأیید
                        </button>
                        <button class="button reject-task-btn"
                                data-entry-id="${task.entry_id}"
                                data-step-id="1"
                                data-step-name="بررسی مدیر"
                                data-step-order="1">
                            رد
                        </button>
                    ` : ''}
                    <button class="button view-history-btn" data-entry-id="${task.entry_id}">
                        تاریخچه
                    </button>
                </div>
            </div>
        `;
    }

    updateTasksCounter(count) {
        const counter = document.getElementById('tasks-count');
        if (counter) {
            counter.textContent = count.toLocaleString('fa-IR');
        }
    }

    filterTasks(searchTerm) {
        const taskRows = document.querySelectorAll('.task-row');
        taskRows.forEach(row => {
            const title = row.querySelector('.task-title').textContent.toLowerCase();
            const submitter = row.querySelector('.task-submitter').textContent.toLowerCase();

            if (title.includes(searchTerm.toLowerCase()) || submitter.includes(searchTerm.toLowerCase())) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterTasksByStatus(status) {
        const taskRows = document.querySelectorAll('.task-row');
        taskRows.forEach(row => {
            const taskStatus = row.querySelector('.task-status').textContent.trim();

            if (status === 'all' || taskStatus.includes(status)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    showHistoryModal(history, entryId) {
        // Create modal HTML
        const modalHtml = `
            <div id="workflow-history-modal" class="workflow-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>تاریخچه گردش کاری - ورودی #${entryId}</h3>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="modal-body">
                        ${history.map(item => `
                            <div class="history-item">
                                <div class="history-date">${item.timestamp}</div>
                                <div class="history-action">${this.getActivityDescription(item)}</div>
                                <div class="history-user">${item.user ? item.user.display_name : 'سیستم'}</div>
                                ${item.details && item.details.notes ? `<div class="history-notes">${item.details.notes}</div>` : ''}
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;

        // Add modal to DOM
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        // Bind close events
        const modal = document.getElementById('workflow-history-modal');
        const closeBtn = modal.querySelector('.close-modal');

        closeBtn.addEventListener('click', () => {
            modal.remove();
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }

    getActivityIcon(action) {
        const icons = {
            'step_created': 'dashicons-plus-alt',
            'step_approve': 'dashicons-yes',
            'step_reject': 'dashicons-no',
            'workflow_completed': 'dashicons-saved',
            'entry_rejected': 'dashicons-dismiss'
        };
        return icons[action] || 'dashicons-admin-generic';
    }

    getActivityDescription(activity) {
        const descriptions = {
            'step_created': 'وظیفه جدید ایجاد شد',
            'step_approve': 'وظیفه تأیید شد',
            'step_reject': 'وظیفه رد شد',
            'workflow_completed': 'گردش کاری تکمیل شد',
            'entry_rejected': 'ورودی به ارسال کننده بازگشت داده شد'
        };
        return descriptions[activity.action] || activity.action;
    }

    async makeRequest(endpoint, method = 'GET', data = null) {
        const url = `${this.ajaxUrl}?action=donap_api&endpoint=${endpoint}`;

        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        if (data && method !== 'GET') {
            if (data instanceof FormData) {
                delete options.headers['Content-Type'];
                options.body = data;
            } else {
                options.body = JSON.stringify(data);
            }
        }

        const response = await fetch(url, options);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    showSpinner(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.innerHTML = '<div class="workflow-spinner">در حال بارگیری...</div>';
        }
    }

    hideSpinner(selector) {
        const spinner = document.querySelector(`${selector} .workflow-spinner`);
        if (spinner) {
            spinner.remove();
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notice notice-${type} is-dismissible workflow-notification`;
        notification.innerHTML = `
            <p>${message}</p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text">نادیده گیری این اعلان</span>
            </button>
        `;

        // Add to page
        const noticesContainer = document.querySelector('.wrap h1');
        if (noticesContainer) {
            noticesContainer.parentNode.insertBefore(notification, noticesContainer.nextSibling);
        }

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);

        // Bind dismiss button
        const dismissBtn = notification.querySelector('.notice-dismiss');
        dismissBtn.addEventListener('click', () => {
            notification.remove();
        });
    }

    initializeManagerForm() {
        const provinceSelect = document.getElementById('manager-province');
        const citySelect = document.getElementById('manager-city');
        const schoolSelect = document.getElementById('manager-school');

        if (provinceSelect && citySelect) {
            provinceSelect.addEventListener('change', () => {
                this.loadCities(provinceSelect.value, citySelect);
                if (schoolSelect) {
                    schoolSelect.innerHTML = '<option value="">ابتدا شهر را انتخاب کنید</option>';
                }
            });
        }

        if (citySelect && schoolSelect) {
            citySelect.addEventListener('change', () => {
                this.loadSchools(citySelect.value, schoolSelect);
            });
        }
    }

    loadCities(province, citySelect) {
        // Placeholder for loading cities based on province
        // In a real implementation, this would make an AJAX call
        citySelect.innerHTML = '<option value="">شهر را انتخاب کنید</option>';
    }

    loadSchools(city, schoolSelect) {
        // Placeholder for loading schools based on city
        // In a real implementation, this would make an AJAX call
        schoolSelect.innerHTML = '<option value="">مدرسه را انتخاب کنید</option>';
    }
}

// Initialize workflow manager when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (typeof donapp_admin !== 'undefined') {
        window.workflowManager = new WorkflowManager();
    }
});