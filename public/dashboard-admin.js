// Dashboard Admin - Gestione completa

// Configurazione sezioni
const SECTIONS = {
    fornitori: {
        title: 'Gestione Fornitori',
        endpoint: '/api/fornitori',
        columns: [
            { key: 'fid', label: 'ID' },
            { key: 'fnome', label: 'Nome' },
            { key: 'indirizzo', label: 'Indirizzo' },
            { key: 'username', label: 'Utente' },
            { key: 'num_pezzi', label: 'N. Pezzi' },
            { key: 'actions', label: 'Azioni' }
        ],
        fields: [
            { name: 'fid', label: 'ID Fornitore', type: 'text', required: true },
            { name: 'fnome', label: 'Nome', type: 'text', required: true },
            { name: 'indirizzo', label: 'Indirizzo', type: 'text' },
            { name: 'user_id', label: 'ID Utente esistente (opzionale)', type: 'number', min: 1 },
            {
                name: 'register_user',
                label: 'Registra nuovo utente fornitore',
                type: 'select',
                options: [
                    { value: 'false', label: 'No' },
                    { value: 'true', label: 'Si' }
                ],
                default: 'false'
            },
            { name: 'username', label: 'Username nuovo utente', type: 'text' },
            { name: 'email', label: 'Email nuovo utente', type: 'email' },
            { name: 'password', label: 'Password nuovo utente', type: 'password' }
        ]
    },
    pezzi: {
        title: 'Gestione Pezzi',
        endpoint: '/api/pezzi',
        columns: [
            { key: 'pid', label: 'ID' },
            { key: 'pnome', label: 'Nome' },
            { key: 'colore', label: 'Colore' },
            { key: 'num_fornitori', label: 'N. Fornitori' },
            { key: 'costo_minimo', label: 'Costo Min' },
            { key: 'actions', label: 'Azioni' }
        ],
        fields: [
            { name: 'pid', label: 'ID Pezzo', type: 'text', required: true },
            { name: 'pnome', label: 'Nome', type: 'text', required: true },
            { name: 'colore', label: 'Colore', type: 'text', required: true },
            { name: 'descrizione', label: 'Descrizione', type: 'textarea' }
        ]
    },
    catalogo: {
        title: 'Gestione Catalogo',
        endpoint: '/api/catalogo',
        columns: [
            { key: 'fnome', label: 'Fornitore' },
            { key: 'pnome', label: 'Pezzo' },
            { key: 'colore', label: 'Colore' },
            { key: 'costo', label: 'Costo €' },
            { key: 'quantita', label: 'Quantità' },
            { key: 'actions', label: 'Azioni' }
        ],
        fields: [
            { name: 'fid', label: 'ID Fornitore', type: 'text', required: true },
            { name: 'pid', label: 'ID Pezzo', type: 'text', required: true },
            { name: 'costo', label: 'Costo €', type: 'number', step: '0.01', min: '0.01', required: true },
            { name: 'quantita', label: 'Quantità', type: 'number', min: '0', default: '0' },
            { name: 'note', label: 'Note', type: 'textarea' }
        ]
    }
};

// Carica sezione corrente
async function loadCurrentSection() {
    const section = SECTIONS[AppState.currentSection];
    const query = new URLSearchParams({
        page: String(AppState.currentPage),
        per_page: String(AppState.perPage)
    });

    if (AppState.searchTerm.trim() !== '') {
        query.set('search', AppState.searchTerm.trim());
    }
    
    showLoading();
    
    try {
        const data = await apiCall(`${section.endpoint}?${query.toString()}`);
        
        // Store data
        if (data.data && Array.isArray(data.data)) {
            AppState.data = data.data;
        } else if (Array.isArray(data)) {
            AppState.data = data;
        } else {
            AppState.data = [];
        }

        const meta = data.meta || {
            total: AppState.data.length,
            current_page: AppState.currentPage,
            per_page: AppState.perPage
        };
        
        // Render
        renderTable(section.columns, AppState.data);
        updatePagination(meta.total || 0, meta.current_page || 1, meta.per_page || AppState.perPage);
        
    } catch (error) {
        console.error('Errore caricamento:', error);
        showAlert(error.message, 'error');
        showEmptyState('tableBody', 'Errore nel caricamento dei dati');
    }
}

// Render tabella
function renderTable(columns, data) {
    const thead = document.getElementById('tableHead');
    const tbody = document.getElementById('tableBody');
    
    // Header
    thead.innerHTML = `
        <tr>
            ${columns.map(col => `<th>${col.label}</th>`).join('')}
        </tr>
    `;
    
    // Body
    if (data.length === 0) {
        showEmptyState();
        return;
    }
    
    tbody.innerHTML = data.map(row => `
        <tr>
            ${columns.map(col => {
                if (col.key === 'actions') {
                    return `<td>${renderActions(row)}</td>`;
                } else if (col.key === 'costo' || col.key === 'costo_minimo' || col.key === 'costo_massimo' || col.key === 'costo_medio') {
                    return `<td>€ ${formatNumber(row[col.key])}</td>`;
                } else if (col.key === 'username') {
                    return `<td>${row[col.key] ? `<span class="badge badge-success">${escapeHtml(row[col.key])}</span>` : '<span class="badge badge-warning">Non registrato</span>'}</td>`;
                } else if (col.key === 'colore') {
                    return `<td><span class="badge badge-info">${escapeHtml(row[col.key])}</span></td>`;
                } else {
                    return `<td>${escapeHtml(row[col.key]) || '-'}</td>`;
                }
            }).join('')}
        </tr>
    `).join('');
}

// Render azioni
function renderActions(item) {
    const section = AppState.currentSection;
    const itemId = item.fid || item.pid || item.id;
    const secondId = section === 'catalogo' ? item.pid : '';
    
    return `
        <div class="action-buttons">
            <button class="btn btn-sm btn-secondary" onclick="viewDetails('${section}', '${itemId}', '${secondId}')">
                👁️
            </button>
            <button class="btn btn-sm btn-primary" onclick="editItem('${section}', ${JSON.stringify(item).replace(/"/g, '&quot;')})">
                ✏️
            </button>
            <button class="btn btn-sm btn-danger" onclick="deleteItem('${section}', '${item.fid || item.pid}', '${item.fid && item.pid ? item.pid : ''}')">
                🗑️
            </button>
        </div>
    `;
}

// Visualizza dettagli
async function viewDetails(section, id, secondId = null) {
    showModal();
    document.getElementById('modalTitle').textContent = 'Dettagli';
    document.getElementById('modalActionBtn').style.display = 'none';
    document.getElementById('modalBody').innerHTML = '<div class="loading"><div class="spinner"></div><p>Caricamento...</p></div>';
    
    try {
        let endpoint = SECTIONS[section].endpoint;
        
        if (section === 'catalogo') {
            endpoint += `/${id}/${secondId}`;
            const response = await apiCall(endpoint);
            renderCatalogoDetails(response.data || response);
        } else {
            endpoint += `/${id}`;
            const response = await apiCall(endpoint);
            const data = response.data || response;
            
            if (section === 'fornitori') {
                renderFornitoreDetails(data);
            } else if (section === 'pezzi') {
                renderPezzoDetails(data);
            }
        }
    } catch (error) {
        console.error('Errore caricamento dettagli:', error);
        document.getElementById('modalBody').innerHTML = `<div class="alert alert-error">${error.message}</div>`;
    }
}

// Render dettagli fornitore
function renderFornitoreDetails(fornitore) {
    document.getElementById('modalBody').innerHTML = `
        <div class="details-grid">
            <div class="detail-item">
                <div class="detail-label">ID Fornitore</div>
                <div class="detail-value">${escapeHtml(fornitore.fid)}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Nome</div>
                <div class="detail-value">${escapeHtml(fornitore.fnome)}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Indirizzo</div>
                <div class="detail-value">${escapeHtml(fornitore.indirizzo) || '-'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Utente Associato</div>
                <div class="detail-value">
                    ${fornitore.username ? `${escapeHtml(fornitore.username)}<br><small>${escapeHtml(fornitore.email)}</small>` : '<em>Non registrato</em>'}
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Data Creazione</div>
                <div class="detail-value">${formatDate(fornitore.created_at)}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Ultimo Aggiornamento</div>
                <div class="detail-value">${formatDate(fornitore.updated_at)}</div>
            </div>
        </div>
        
        <div class="detail-section">
            <h3>Catalogo (${fornitore.catalogo?.length || 0} pezzi)</h3>
            ${fornitore.catalogo && fornitore.catalogo.length > 0 ? `
                <table class="sub-table">
                    <thead>
                        <tr>
                            <th>Pezzo</th>
                            <th>Colore</th>
                            <th>Costo</th>
                            <th>Quantità</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${fornitore.catalogo.map(item => `
                            <tr>
                                <td>${escapeHtml(item.pnome)}</td>
                                <td><span class="badge badge-info">${escapeHtml(item.colore)}</span></td>
                                <td>€ ${formatNumber(item.costo)}</td>
                                <td>${item.quantita || 0}</td>
                                <td>${escapeHtml(item.note) || '-'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            ` : '<p><em>Nessun pezzo nel catalogo</em></p>'}
        </div>
    `;
}

// Render dettagli pezzo
function renderPezzoDetails(pezzo) {
    document.getElementById('modalBody').innerHTML = `
        <div class="details-grid">
            <div class="detail-item">
                <div class="detail-label">ID Pezzo</div>
                <div class="detail-value">${escapeHtml(pezzo.pid)}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Nome</div>
                <div class="detail-value">${escapeHtml(pezzo.pnome)}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Colore</div>
                <div class="detail-value"><span class="badge badge-info">${escapeHtml(pezzo.colore)}</span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Descrizione</div>
                <div class="detail-value">${escapeHtml(pezzo.descrizione) || '-'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Data Creazione</div>
                <div class="detail-value">${formatDate(pezzo.created_at)}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Ultimo Aggiornamento</div>
                <div class="detail-value">${formatDate(pezzo.updated_at)}</div>
            </div>
        </div>
        
        <div class="detail-section">
            <h3>Fornitori (${pezzo.fornitori?.length || 0})</h3>
            ${pezzo.fornitori && pezzo.fornitori.length > 0 ? `
                <table class="sub-table">
                    <thead>
                        <tr>
                            <th>Fornitore</th>
                            <th>Indirizzo</th>
                            <th>Costo</th>
                            <th>Quantità</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${pezzo.fornitori.map(item => `
                            <tr>
                                <td>${escapeHtml(item.fnome)}</td>
                                <td>${escapeHtml(item.indirizzo) || '-'}</td>
                                <td>€ ${formatNumber(item.costo)}</td>
                                <td>${item.quantita || 0}</td>
                                <td>${escapeHtml(item.note) || '-'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            ` : '<p><em>Nessun fornitore disponibile</em></p>'}
        </div>
    `;
}

// Render dettagli catalogo  
function renderCatalogoDetails(item) {
    document.getElementById('modalBody').innerHTML = `
        <div class="details-grid">
            <div class="detail-item">
                <div class="detail-label">Fornitore</div>
                <div class="detail-value">${escapeHtml(item.fnome)} (${escapeHtml(item.fid)})</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Pezzo</div>
                <div class="detail-value">${escapeHtml(item.pnome)} (${escapeHtml(item.pid)})</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Colore</div>
                <div class="detail-value"><span class="badge badge-info">${escapeHtml(item.colore)}</span></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Costo</div>
                <div class="detail-value">€ ${formatNumber(item.costo)}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Quantità</div>
                <div class="detail-value">${item.quantita || 0}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Note</div>
                <div class="detail-value">${escapeHtml(item.note) || '-'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Data Aggiunta</div>
                <div class="detail-value">${formatDate(item.created_at)}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Ultimo Aggiornamento</div>
                <div class="detail-value">${formatDate(item.updated_at)}</div>
            </div>
        </div>
        ${item.descrizione ? `
            <div class="detail-section">
                <h3>Descrizione Pezzo</h3>
                <p>${escapeHtml(item.descrizione)}</p>
            </div>
        ` : ''}
    `;
}

// Aggiungi nuovo elemento
function addItem() {
    const section = SECTIONS[AppState.currentSection];
    
    showModal();
    document.getElementById('modalTitle').textContent = `Aggiungi ${section.title.replace('Gestione ', '')}`;
    document.getElementById('modalActionBtn').style.display = 'inline-block';
    document.getElementById('modalActionBtn').textContent = 'Crea';
    
    const formHtml = `
        <form id="itemForm">
            ${createForm(section.fields)}
        </form>
    `;
    
    document.getElementById('modalBody').innerHTML = formHtml;
    
    // Handler salvataggio
    document.getElementById('modalActionBtn').onclick = async () => {
        await saveItem(true);
    };
}

// Modifica elemento
function editItem(section, item) {
    const sectionConfig = SECTIONS[section];
    
    showModal();
    document.getElementById('modalTitle').textContent = `Modifica ${sectionConfig.title.replace('Gestione ', '')}`;
    document.getElementById('modalActionBtn').style.display = 'inline-block';
    document.getElementById('modalActionBtn').textContent = 'Salva';
    
    // Per catalogo usiamo fid e pid come chiavi
    const isNew = false;
    
    const formHtml = `
        <form id="itemForm">
            ${createForm(sectionConfig.fields.map(f => {
                // Disabilita ID in modifica
                if ((f.name === 'fid' || f.name === 'pid') && !isNew) {
                    return { ...f, disabled: true };
                }
                return f;
            }), item)}
        </form>
    `;
    
    document.getElementById('modalBody').innerHTML = formHtml;
    
    // Handler salvataggio
    document.getElementById('modalActionBtn').onclick = async () => {
        await saveItem(false, item);
    };
}

// Salva elemento
async function saveItem(isNew, originalItem = null) {
    const section = SECTIONS[AppState.currentSection];
    const formData = getFormValues('itemForm');
    
    try {
        let endpoint = section.endpoint;
        let method = isNew ? 'POST' : 'PUT';
        
        if (!isNew) {
            if (AppState.currentSection === 'catalogo') {
                endpoint += `/${originalItem.fid}/${originalItem.pid}`;
            } else {
                const id = originalItem.fid || originalItem.pid;
                endpoint += `/${id}`;
            }
        }
        
        // Converti form per invio
        const body = new URLSearchParams();
        for (const [key, value] of Object.entries(formData)) {
            if (value !== null && value !== '') {
                body.append(key, value);
            }
        }
        
        await apiCall(endpoint, {
            method: method,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: body.toString()
        });
        
        showAlert(isNew ? 'Elemento creato con successo' : 'Elemento aggiornato con successo', 'success');
        hideModal();
        loadCurrentSection();
        
    } catch (error) {
        showAlert(error.message, 'error');
    }
}

// Elimina elemento
async function deleteItem(section, id, secondId = null) {
    const sectionConfig = SECTIONS[section];
    const itemName = sectionConfig.title.replace('Gestione ', '');
    
    if (!confirmAction(`Sei sicuro di voler eliminare questo ${itemName.toLowerCase()}?`)) {
        return;
    }
    
    try {
        let endpoint = sectionConfig.endpoint + `/${id}`;
        
        if (section === 'catalogo' && secondId) {
            endpoint += `/${secondId}`;
        }
        
        await apiCall(endpoint, {
            method: 'DELETE'
        });
        
        showAlert('Elemento eliminato con successo', 'success');
        loadCurrentSection();
        
    } catch (error) {
        showAlert(error.message, 'error');
    }
}

// Cambia sezione
function changeSection(section) {
    AppState.currentSection = section;
    AppState.currentPage = 1;
    AppState.searchTerm = '';
    
    // Update UI
    document.getElementById('sectionTitle').textContent = SECTIONS[section].title;
    document.getElementById('searchInput').value = '';
    
    // Update nav
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
        if (item.dataset.section === section) {
            item.classList.add('active');
        }
    });
    
    loadCurrentSection();
}

// Init
async function init() {
    const user = await checkAuth();
    
    if (!user) return;
    
    // Verifica ruolo admin
    if (user.role !== 'admin') {
        alert('Accesso negato. Solo gli amministratori possono accedere a questa pagina.');
        window.location.href = 'dashboard-fornitore.html';
        return;
    }
    
    // Mostra info utente
    document.getElementById('username').textContent = user.username;
    document.getElementById('email').textContent = user.email;
    
    // Init eventi
    initCommonEvents();
    
    // Nav click
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            changeSection(item.dataset.section);
        });
    });
    
    // Add button
    document.getElementById('addBtn').addEventListener('click', addItem);
    
    // Carica prima sezione
    loadCurrentSection();
}

// Start
window.addEventListener('DOMContentLoaded', init);
