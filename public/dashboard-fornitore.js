// Dashboard Fornitore - Gestione catalogo proprio

let fornitoreData = null;
let pezziDisponibili = [];

// Carica info fornitore
async function loadFornitoreInfo() {
    const user = AppState.user;
    
    if (!user.fornitore_id) {
        showAlert('Nessun fornitore associato a questo utente', 'error');
        return;
    }
    
    try {
        const response = await apiCall(`/api/fornitori/${user.fornitore_id}`);
        fornitoreData = response.data || response;
        
        document.getElementById('fornitoreNome').textContent = fornitoreData.fnome;
        
        updateStats();
    } catch (error) {
        console.error('Errore caricamento fornitore:', error);
        showAlert('Errore nel caricamento dei dati fornitore', 'error');
    }
}

// Aggiorna statistiche
function updateStats() {
    if (!AppState.data) return;
    
    const totalPezzi = AppState.data.length;
    const valoreTotale = AppState.data.reduce((sum, item) => sum + (item.costo * item.quantita), 0);
    const quantitaTotale = AppState.data.reduce((sum, item) => sum + (item.quantita || 0), 0);
    
    document.getElementById('totalPezzi').textContent = totalPezzi;
    document.getElementById('valoreTotale').textContent = `€ ${formatNumber(valoreTotale)}`;
    document.getElementById('quantitaTotale').textContent = quantitaTotale;
}

// Carica catalogo fornitore
async function loadCatalogo() {
    showLoading();
    
    try {
        const response = await apiCall('/api/catalogo');
        
        if (response.data && Array.isArray(response.data)) {
            AppState.data = response.data;
        } else if (Array.isArray(response)) {
            AppState.data = response;
        } else {
            AppState.data = [];
        }
        
        // Filtra e pagina
        const filtered = filterData(AppState.data, AppState.searchTerm);
        const paginated = paginateData(filtered, AppState.currentPage, AppState.perPage);
        
        // Render
        renderCatalogoTable(paginated);
        updatePagination(filtered.length, AppState.currentPage, AppState.perPage);
        updateStats();
        
    } catch (error) {
        console.error('Errore caricamento catalogo:', error);
        showAlert(error.message, 'error');
        showEmptyState('tableBody', 'Errore nel caricamento del catalogo');
    }
}

// Render tabella catalogo
function renderCatalogoTable(data) {
    const thead = document.getElementById('tableHead');
    const tbody = document.getElementById('tableBody');
    
    // Header
    thead.innerHTML = `
        <tr>
            <th>ID Pezzo</th>
            <th>Nome</th>
            <th>Colore</th>
            <th>Costo €</th>
            <th>Quantità</th>
            <th>Valore €</th>
            <th>Note</th>
            <th>Azioni</th>
        </tr>
    `;
    
    // Body
    if (data.length === 0) {
        showEmptyState('tableBody', 'Il tuo catalogo è vuoto. Aggiungi pezzi per iniziare!');
        return;
    }
    
    tbody.innerHTML = data.map(item => `
        <tr>
            <td>${escapeHtml(item.pid)}</td>
            <td><strong>${escapeHtml(item.pnome)}</strong></td>
            <td><span class="badge badge-info">${escapeHtml(item.colore)}</span></td>
            <td>€ ${formatNumber(item.costo)}</td>
            <td>${item.quantita || 0}</td>
            <td>€ ${formatNumber(item.costo * (item.quantita || 0))}</td>
            <td>${escapeHtml(item.note) || '-'}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-sm btn-secondary" onclick="viewCatalogoDetails(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                        👁️
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="editCatalogoItem(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                        ✏️
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteCatalogoItem('${item.pid}')">
                        🗑️
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Carica pezzi disponibili
async function loadPezziDisponibili() {
    showLoading();
    
    try {
        const response = await apiCall('/api/pezzi');
        
        if (response.data && Array.isArray(response.data)) {
            pezziDisponibili = response.data;
        } else if (Array.isArray(response)) {
            pezziDisponibili = response;
        } else {
            pezziDisponibili = [];
        }
        
        // Filtra e pagina
        const filtered = filterData(pezziDisponibili, AppState.searchTerm);
        const paginated = paginateData(filtered, AppState.currentPage, AppState.perPage);
        
        // Render
        renderPezziTable(paginated);
        updatePagination(filtered.length, AppState.currentPage, AppState.perPage);
        
    } catch (error) {
        console.error('Errore caricamento pezzi:', error);
        showAlert(error.message, 'error');
        showEmptyState('tableBody', 'Errore nel caricamento dei pezzi');
    }
}

// Render tabella pezzi
function renderPezziTable(data) {
    const thead = document.getElementById('tableHead');
    const tbody = document.getElementById('tableBody');
    
    // Header
    thead.innerHTML = `
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Colore</th>
            <th>Descrizione</th>
            <th>N. Fornitori</th>
            <th>Costo Min</th>
            <th>Azioni</th>
        </tr>
    `;
    
    // Body
    if (data.length === 0) {
        showEmptyState('tableBody', 'Nessun pezzo disponibile');
        return;
    }
    
    tbody.innerHTML = data.map(item => {
        const giaNelCatalogo = AppState.data.some(c => c.pid === item.pid);
        
        return `
            <tr>
                <td>${escapeHtml(item.pid)}</td>
                <td><strong>${escapeHtml(item.pnome)}</strong></td>
                <td><span class="badge badge-info">${escapeHtml(item.colore)}</span></td>
                <td>${escapeHtml(item.descrizione) || '-'}</td>
                <td>${item.num_fornitori || 0}</td>
                <td>${item.costo_minimo ? '€ ' + formatNumber(item.costo_minimo) : '-'}</td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-sm btn-secondary" onclick="viewPezzoDetails('${item.pid}')">
                            👁️
                        </button>
                        ${giaNelCatalogo ? 
                            '<span class="badge badge-success">Nel catalogo</span>' :
                            `<button class="btn btn-sm btn-success" onclick="addToCatalogo('${item.pid}')">
                                + Aggiungi
                            </button>`
                        }
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// Visualizza dettagli pezzo dal catalogo
function viewCatalogoDetails(item) {
    showModal();
    document.getElementById('modalTitle').textContent = 'Dettagli Pezzo';
    document.getElementById('modalActionBtn').style.display = 'none';
    
    document.getElementById('modalBody').innerHTML = `
        <div class="details-grid">
            <div class="detail-item">
                <div class="detail-label">ID Pezzo</div>
                <div class="detail-value">${escapeHtml(item.pid)}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Nome</div>
                <div class="detail-value">${escapeHtml(item.pnome)}</div>
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
                <div class="detail-label">Quantità Disponibile</div>
                <div class="detail-value">${item.quantita || 0}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Valore Totale</div>
                <div class="detail-value">€ ${formatNumber(item.costo * (item.quantita || 0))}</div>
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
                <h3>Descrizione</h3>
                <p>${escapeHtml(item.descrizione)}</p>
            </div>
        ` : ''}
        ${item.note ? `
            <div class="detail-section">
                <h3>Note</h3>
                <p>${escapeHtml(item.note)}</p>
            </div>
        ` : ''}
    `;
}

// Visualizza dettagli pezzo dalla lista completa
async function viewPezzoDetails(pid) {
    showModal();
    document.getElementById('modalTitle').textContent = 'Dettagli Pezzo';
    document.getElementById('modalActionBtn').style.display = 'none';
    document.getElementById('modalBody').innerHTML = '<div class="loading"><div class="spinner"></div><p>Caricamento...</p></div>';
    
    try {
        const response = await apiCall(`/api/pezzi/${pid}`);
        const pezzo = response.data || response;
        
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
                <h3>Altri Fornitori (${pezzo.fornitori?.length || 0})</h3>
                ${pezzo.fornitori && pezzo.fornitori.length > 0 ? `
                    <table class="sub-table">
                        <thead>
                            <tr>
                                <th>Fornitore</th>
                                <th>Costo</th>
                                <th>Quantità</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${pezzo.fornitori.map(f => `
                                <tr>
                                    <td>${escapeHtml(f.fnome)}</td>
                                    <td>€ ${formatNumber(f.costo)}</td>
                                    <td>${f.quantita || 0}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                ` : '<p><em>Nessun altro fornitore</em></p>'}
            </div>
        `;
    } catch (error) {
        console.error('Errore caricamento dettagli:', error);
        document.getElementById('modalBody').innerHTML = `<div class="alert alert-error">${error.message}</div>`;
    }
}

// Aggiungi pezzo al catalogo
function addToCatalogo(pid) {
    const pezzo = pezziDisponibili.find(p => p.pid === pid);
    
    if (!pezzo) return;
    
    showModal();
    document.getElementById('modalTitle').textContent = 'Aggiungi al Catalogo';
    document.getElementById('modalActionBtn').style.display = 'inline-block';
    document.getElementById('modalActionBtn').textContent = 'Aggiungi';
    
    const formHtml = `
        <form id="catalogoForm">
            <div class="form-group">
                <label>Pezzo</label>
                <input type="text" value="${escapeHtml(pezzo.pnome)} (${escapeHtml(pezzo.pid)})" disabled>
                <input type="hidden" name="pid" value="${escapeHtml(pezzo.pid)}">
                <input type="hidden" name="fid" value="${escapeHtml(AppState.user.fornitore_id)}">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="costo">Costo € *</label>
                    <input type="number" id="costo" name="costo" step="0.01" min="0.01" required 
                           value="${pezzo.costo_minimo || ''}">
                </div>
                <div class="form-group">
                    <label for="quantita">Quantità</label>
                    <input type="number" id="quantita" name="quantita" min="0" value="0">
                </div>
            </div>
            <div class="form-group">
                <label for="note">Note</label>
                <textarea id="note" name="note"></textarea>
            </div>
        </form>
    `;
    
    document.getElementById('modalBody').innerHTML = formHtml;
    
    document.getElementById('modalActionBtn').onclick = async () => {
        await saveCatalogoItem(true);
    };
}

// Modifica pezzo nel catalogo
function editCatalogoItem(item) {
    showModal();
    document.getElementById('modalTitle').textContent = 'Modifica Pezzo nel Catalogo';
    document.getElementById('modalActionBtn').style.display = 'inline-block';
    document.getElementById('modalActionBtn').textContent = 'Salva';
    
    const formHtml = `
        <form id="catalogoForm">
            <div class="form-group">
                <label>Pezzo</label>
                <input type="text" value="${escapeHtml(item.pnome)} (${escapeHtml(item.pid)})" disabled>
                <input type="hidden" name="pid" value="${escapeHtml(item.pid)}">
                <input type="hidden" name="fid" value="${escapeHtml(item.fid)}">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="costo">Costo € *</label>
                    <input type="number" id="costo" name="costo" step="0.01" min="0.01" required 
                           value="${item.costo}">
                </div>
                <div class="form-group">
                    <label for="quantita">Quantità</label>
                    <input type="number" id="quantita" name="quantita" min="0" value="${item.quantita || 0}">
                </div>
            </div>
            <div class="form-group">
                <label for="note">Note</label>
                <textarea id="note" name="note">${escapeHtml(item.note) || ''}</textarea>
            </div>
        </form>
    `;
    
    document.getElementById('modalBody').innerHTML = formHtml;
    
    document.getElementById('modalActionBtn').onclick = async () => {
        await saveCatalogoItem(false, item);
    };
}

// Salva voce catalogo
async function saveCatalogoItem(isNew, originalItem = null) {
    const formData = getFormValues('catalogoForm');
    
    try {
        let endpoint = '/api/catalogo';
        let method = isNew ? 'POST' : 'PUT';
        
        if (!isNew) {
            endpoint += `/${formData.fid}/${formData.pid}`;
        }
        
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
        
        showAlert(isNew ? 'Pezzo aggiunto al catalogo' : 'Pezzo aggiornato', 'success');
        hideModal();
        
        if (AppState.currentSection === 'catalogo') {
            loadCatalogo();
        } else {
            // Ricarica anche il catalogo in background per aggiornare i badge
            const response = await apiCall('/api/catalogo');
            AppState.data = response.data || response;
            loadPezziDisponibili();
        }
        
    } catch (error) {
        showAlert(error.message, 'error');
    }
}

// Elimina pezzo dal catalogo
async function deleteCatalogoItem(pid) {
    if (!confirmAction('Sei sicuro di voler rimuovere questo pezzo dal catalogo?')) {
        return;
    }
    
    try {
        const fid = AppState.user.fornitore_id;
        await apiCall(`/api/catalogo/${fid}/${pid}`, {
            method: 'DELETE'
        });
        
        showAlert('Pezzo rimosso dal catalogo', 'success');
        loadCatalogo();
        
    } catch (error) {
        showAlert(error.message, 'error');
    }
}

// Cambia sezione
function changeSection(section) {
    AppState.currentSection = section;
    AppState.currentPage = 1;
    AppState.searchTerm = '';
    
    document.getElementById('searchInput').value = '';
    
    // Update nav
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
        if (item.dataset.section === section) {
            item.classList.add('active');
        }
    });
    
    // Update UI e carica dati
    if (section === 'catalogo') {
        document.getElementById('sectionTitle').textContent = 'Il Mio Catalogo';
        document.getElementById('addBtn').textContent = '+ Aggiungi Pezzo';
        document.getElementById('addBtn').style.display = 'inline-block';
        document.getElementById('infoCards').style.display = 'grid';
        loadCatalogo();
    } else if (section === 'pezzi') {
        document.getElementById('sectionTitle').textContent = 'Pezzi Disponibili';
        document.getElementById('addBtn').style.display = 'none';
        document.getElementById('infoCards').style.display = 'none';
        loadPezziDisponibili();
    }
}

// Init
async function init() {
    const user = await checkAuth();
    
    if (!user) return;
    
    // Verifica ruolo fornitore
    if (user.role !== 'fornitore') {
        alert('Accesso negato. Solo i fornitori possono accedere a questa pagina.');
        window.location.href = 'dashboard-admin.html';
        return;
    }
    
    // Verifica fornitore associato
    if (!user.fornitore_id) {
        alert('Nessun fornitore associato a questo utente. Contatta un amministratore.');
        logout();
        return;
    }
    
    // Mostra info utente
    document.getElementById('username').textContent = user.username;
    
    // Carica info fornitore
    await loadFornitoreInfo();
    
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
    document.getElementById('addBtn').addEventListener('click', () => {
        // Mostra lista pezzi per selezionare
        changeSection('pezzi');
    });
    
    // Carica catalogo iniziale
    AppState.currentSection = 'catalogo';
    loadCatalogo();
}

// Start
window.addEventListener('DOMContentLoaded', init);
