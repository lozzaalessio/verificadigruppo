// Configurazione API
const API_BASE = window.location.origin;

// Stato globale
const AppState = {
    user: null,
    currentSection: 'fornitori',
    currentPage: 1,
    perPage: 20,
    searchTerm: '',
    data: [],
    totalItems: 0,
    lastPage: 1
};

// Verifica autenticazione
async function checkAuth() {
    try {
        const response = await fetch(`${API_BASE}/api/auth/me`);
        const data = await response.json();
        
        if (!data.authenticated) {
            window.location.href = 'login.html';
            return null;
        }
        
        AppState.user = data.user;
        return data.user;
    } catch (error) {
        console.error('Errore verifica autenticazione:', error);
        window.location.href = 'login.html';
        return null;
    }
}

// Logout
async function logout() {
    try {
        await fetch(`${API_BASE}/api/auth/logout`, {
            method: 'POST'
        });
        
        localStorage.removeItem('user');
        window.location.href = 'login.html';
    } catch (error) {
        console.error('Errore logout:', error);
        window.location.href = 'login.html';
    }
}

// API Call con gestione errori
async function apiCall(endpoint, options = {}) {
    try {
        const response = await fetch(`${API_BASE}${endpoint}`, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || data.error || 'Errore nella richiesta');
        }
        
        return data;
    } catch (error) {
        console.error('Errore API:', error);
        throw error;
    }
}

// Formatta data
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Formatta numero
function formatNumber(num, decimals = 2) {
    if (num === null || num === undefined) return '-';
    return parseFloat(num).toFixed(decimals);
}

// Mostra/Nascondi Modal
function showModal() {
    document.getElementById('modal').classList.add('show');
}

function hideModal() {
    document.getElementById('modal').classList.remove('show');
}

// Mostra Loading
function showLoading(containerId = 'tableBody') {
    const container = document.getElementById(containerId);
    container.innerHTML = `
        <tr>
            <td colspan="100" class="loading">
                <div class="spinner"></div>
                <p>Caricamento in corso...</p>
            </td>
        </tr>
    `;
}

// Mostra Empty State
function showEmptyState(containerId = 'tableBody', message = 'Nessun dato disponibile') {
    const container = document.getElementById(containerId);
    container.innerHTML = `
        <tr>
            <td colspan="100">
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <p>${message}</p>
                </div>
            </td>
        </tr>
    `;
}

// Mostra Alert
function showAlert(message, type = 'info', duration = 5000) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.style.minWidth = '300px';
    alert.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.style.animation = 'fadeOut 0.3s';
        setTimeout(() => alert.remove(), 300);
    }, duration);
}

// Conferma azione
function confirmAction(message) {
    return confirm(message);
}

// Filtra dati in base alla ricerca
function filterData(data, searchTerm) {
    if (!searchTerm) return data;
    
    const term = searchTerm.toLowerCase();
    
    return data.filter(item => {
        return Object.values(item).some(value => {
            if (value === null || value === undefined) return false;
            return String(value).toLowerCase().includes(term);
        });
    });
}

// Pagina dati
function paginateData(data, page, perPage) {
    const start = (page - 1) * perPage;
    const end = start + perPage;
    return data.slice(start, end);
}

// Aggiorna info paginazione
function updatePagination(totalItems, currentPage, perPage) {
    const totalPages = Math.ceil(totalItems / perPage);
    AppState.totalItems = totalItems;
    AppState.lastPage = Math.max(1, totalPages);
    
    document.getElementById('pageInfo').textContent = 
        `Pagina ${currentPage} di ${totalPages} (${totalItems} elementi)`;
    
    document.getElementById('prevBtn').disabled = currentPage === 1;
    document.getElementById('nextBtn').disabled = currentPage === totalPages || totalPages === 0;
}

// Crea riga tabella con azioni
function createActionButtons(item, onView, onEdit, onDelete, showEdit = true, showDelete = true) {
    const actionsHtml = `
        <div class="action-buttons">
            <button class="btn btn-sm btn-secondary" onclick="(${onView})(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                👁️ Dettagli
            </button>
            ${showEdit ? `
                <button class="btn btn-sm btn-primary" onclick="(${onEdit})(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                    ✏️ Modifica
                </button>
            ` : ''}
            ${showDelete ? `
                <button class="btn btn-sm btn-danger" onclick="(${onDelete})('${item.fid || item.pid || item.id}')">
                    🗑️ Elimina
                </button>
            ` : ''}
        </div>
    `;
    return actionsHtml;
}

// Escape HTML
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Init eventi comuni
function initCommonEvents() {
    // Logout
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', logout);
    }
    
    // Chiudi modal
    const modalClose = document.getElementById('modalClose');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    
    if (modalClose) {
        modalClose.addEventListener('click', hideModal);
    }
    
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', hideModal);
    }
    
    // Click fuori dal modal per chiudere
    const modal = document.getElementById('modal');
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                hideModal();
            }
        });
    }
    
    // Search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                AppState.searchTerm = e.target.value;
                AppState.currentPage = 1;
                if (typeof loadCurrentSection === 'function') {
                    loadCurrentSection();
                }
            }, 300);
        });
    }
    
    // Pagination
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (AppState.currentPage > 1) {
                AppState.currentPage--;
                if (typeof loadCurrentSection === 'function') {
                    loadCurrentSection();
                }
            }
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (AppState.currentPage < AppState.lastPage) {
                AppState.currentPage++;
                if (typeof loadCurrentSection === 'function') {
                    loadCurrentSection();
                }
            }
        });
    }
}

// Crea form generico
function createForm(fields, data = {}) {
    let formHtml = '';
    
    fields.forEach(field => {
        const value = data[field.name] || field.default || '';
        
        if (field.type === 'textarea') {
            formHtml += `
                <div class="form-group">
                    <label for="${field.name}">${field.label}</label>
                    <textarea 
                        id="${field.name}" 
                        name="${field.name}"
                        ${field.required ? 'required' : ''}
                        ${field.disabled ? 'disabled' : ''}
                    >${escapeHtml(value)}</textarea>
                </div>
            `;
        } else if (field.type === 'select') {
            formHtml += `
                <div class="form-group">
                    <label for="${field.name}">${field.label}</label>
                    <select 
                        id="${field.name}" 
                        name="${field.name}"
                        ${field.required ? 'required' : ''}
                        ${field.disabled ? 'disabled' : ''}
                    >
                        ${field.options.map(opt => `
                            <option value="${opt.value}" ${value == opt.value ? 'selected' : ''}>
                                ${opt.label}
                            </option>
                        `).join('')}
                    </select>
                </div>
            `;
        } else {
            formHtml += `
                <div class="form-group">
                    <label for="${field.name}">${field.label}</label>
                    <input 
                        type="${field.type || 'text'}" 
                        id="${field.name}" 
                        name="${field.name}"
                        value="${escapeHtml(value)}"
                        ${field.required ? 'required' : ''}
                        ${field.disabled ? 'disabled' : ''}
                        ${field.min !== undefined ? `min="${field.min}"` : ''}
                        ${field.max !== undefined ? `max="${field.max}"` : ''}
                        ${field.step !== undefined ? `step="${field.step}"` : ''}
                    >
                </div>
            `;
        }
    });
    
    return formHtml;
}

// Ottieni valori form
function getFormValues(formId) {
    const formData = {};
    const form = document.getElementById(formId) || document.querySelector('.modal-body form');
    
    if (!form) return formData;
    
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        if (input.type === 'checkbox') {
            formData[input.name] = input.checked;
        } else if (input.type === 'number') {
            formData[input.name] = input.value ? parseFloat(input.value) : null;
        } else {
            formData[input.name] = input.value;
        }
    });
    
    return formData;
}
