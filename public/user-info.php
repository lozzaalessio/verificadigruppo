<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Query API - FornitoriPezziDB</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<style>
body{background:linear-gradient(135deg,#0f172a,#1e40af);min-height:100vh;font-family:'Segoe UI',system-ui,sans-serif;padding:2rem;}
.container{max-width:1200px;}
.header-card{background:#fff;border-radius:1rem;padding:1.5rem;margin-bottom:1.5rem;box-shadow:0 10px 40px rgba(0,0,0,.3);}
.header-card h1{font-size:1.8rem;font-weight:800;color:#0f172a;margin:0;display:flex;align-items:center;gap:.7rem;}
.header-card .user-badge{font-size:.85rem;color:#64748b;margin-top:.5rem;}
.query-menu{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;margin-bottom:2rem;}
.query-card{background:#fff;border-radius:.8rem;padding:1.2rem;box-shadow:0 4px 15px rgba(0,0,0,.2);cursor:pointer;transition:transform .2s,box-shadow .2s;}
.query-card:hover{transform:translateY(-4px);box-shadow:0 8px 25px rgba(0,0,0,.3);}
.query-card.active{border:2px solid #3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.2);}
.query-card .badge-num{background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;font-size:.7rem;font-weight:700;padding:.3rem .6rem;border-radius:.4rem;display:inline-block;margin-bottom:.5rem;}
.query-card h6{font-size:.9rem;font-weight:700;color:#0f172a;margin:.5rem 0;}
.query-card .desc{font-size:.75rem;color:#64748b;margin:0;}
.result-card{background:#fff;border-radius:1rem;padding:0;box-shadow:0 10px 40px rgba(0,0,0,.3);overflow:hidden;}
.result-header{background:linear-gradient(135deg,#0f172a,#1e40af);color:#fff;padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between;}
.result-header h5{margin:0;font-size:1.1rem;font-weight:700;}
.result-body{padding:1.5rem;}
.json-display{background:#0f172a;color:#a5f3fc;border-radius:.7rem;padding:1.5rem;font-family:'Courier New',monospace;font-size:.8rem;overflow-x:auto;white-space:pre-wrap;word-break:break-all;max-height:500px;overflow-y:auto;}
.endpoint-badge{background:#dcfce7;color:#16a34a;font-size:.75rem;font-weight:700;padding:.3rem .7rem;border-radius:.4rem;font-family:monospace;}
.loading{text-align:center;padding:3rem;color:#64748b;}
.loading i{font-size:2rem;animation:spin 1s linear infinite;}
@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}
.btn-back{background:linear-gradient(135deg,#0f172a,#1e40af);border:none;color:#fff;}
.btn-back:hover{background:linear-gradient(135deg,#1e40af,#3b82f6);color:#fff;}
.meta-info{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.8rem;margin-bottom:1rem;}
.meta-item{background:#f1f5f9;border-radius:.5rem;padding:.8rem;text-align:center;}
.meta-label{font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:.3rem;}
.meta-value{font-size:1.2rem;font-weight:800;color:#0f172a;}
</style>
</head>
<body>

<div class="container">
  <div class="header-card">
    <h1><i class="bi bi-database-fill-gear text-primary"></i>Query API - FornitoriPezziDB</h1>
    <div class="user-badge" id="userInfo">Caricamento...</div>
  </div>

  <h5 class="text-white mb-3"><i class="bi bi-list-ul me-2"></i>Seleziona una Query</h5>
  <div class="query-menu" id="queryMenu"></div>

  <div id="resultSection" style="display:none;">
    <div class="result-card">
      <div class="result-header">
        <h5 id="resultTitle"><i class="bi bi-code-square me-2"></i>Risultato Query</h5>
        <span class="endpoint-badge" id="resultEndpoint"></span>
      </div>
      <div class="result-body">
        <div id="metaInfo"></div>
        <div class="json-display" id="jsonResult">Seleziona una query dal menu sopra</div>
      </div>
    </div>
  </div>

  <div class="text-center mt-4">
    <button class="btn btn-lg btn-back" onclick="window.location.href='dashboard.php'">
      <i class="bi bi-arrow-left me-2"></i>Torna alla Dashboard
    </button>
  </div>
</div>

<script>
// Recupera i dati dal sessionStorage
const token = sessionStorage.getItem('token');
const userStr = sessionStorage.getItem('user');

if (!token || !userStr) {
  window.location.href = 'dashboard.php';
} else {
  const user = JSON.parse(userStr);
  document.getElementById('userInfo').innerHTML = `
    <i class="bi bi-person-circle me-2"></i>
    <strong>${user.username}</strong> (${user.ruolo}) 
    ${user.fid ? `- Fornitore: ${user.fid}` : ''}
  `;
}

// Definizione delle 10 query
const queries = [
  {
    num: 1,
    title: 'Pezzi distinti in catalogo',
    desc: 'Pezzi presenti in almeno una riga del Catalogo',
    endpoint: 'GET /1',
    path: '/1?page=1&per_page=20'
  },
  {
    num: 2,
    title: 'Fornitori con TUTTI i pezzi',
    desc: 'Fornitori il cui catalogo copre l\'intero insieme dei pezzi',
    endpoint: 'GET /2',
    path: '/2'
  },
  {
    num: 3,
    title: 'Fornitori con TUTTI i pezzi di un colore',
    desc: 'Fornitori con tutti i pezzi del colore specificato',
    endpoint: 'GET /3?colore=rosso',
    path: '/3?colore=rosso'
  },
  {
    num: 4,
    title: 'Pezzi venduti SOLO da un fornitore',
    desc: 'Pezzi esclusivi di un fornitore specifico',
    endpoint: 'GET /4?fnome=Acme',
    path: '/4?fnome=Acme'
  },
  {
    num: 5,
    title: 'Fornitori con prezzo sopra la media',
    desc: 'Fornitori con almeno un pezzo a costo superiore alla media',
    endpoint: 'GET /5',
    path: '/5?page=1&per_page=20'
  },
  {
    num: 6,
    title: 'Fornitore con costo minimo per pezzo',
    desc: 'Per ogni pezzo: fornitore/i con il prezzo minimo',
    endpoint: 'GET /6',
    path: '/6?page=1&per_page=20'
  },
  {
    num: 7,
    title: 'Fornitori con SOLO pezzi rossi',
    desc: 'Fornitori con esclusivamente pezzi rossi in catalogo',
    endpoint: 'GET /7',
    path: '/7'
  },
  {
    num: 8,
    title: 'Fornitori con pezzi rossi E verdi',
    desc: 'Fornitori con almeno un pezzo rosso e uno verde',
    endpoint: 'GET /8',
    path: '/8?page=1&per_page=20'
  },
  {
    num: 9,
    title: 'Fornitori con pezzi di colori selezionati',
    desc: 'Fornitori con almeno un pezzo dei colori specificati (OR)',
    endpoint: 'GET /9?colori=rosso,verde',
    path: '/9?colori=rosso,verde&page=1&per_page=20'
  },
  {
    num: 10,
    title: 'Pezzi con almeno N fornitori',
    desc: 'Pezzi presenti nel catalogo di almeno N fornitori distinti',
    endpoint: 'GET /10?min_fornitori=2',
    path: '/10?min_fornitori=2&page=1&per_page=20'
  }
];

// Renderizza il menu delle query
const menuHtml = queries.map(q => `
  <div class="query-card" onclick="executeQuery(${q.num})" id="card${q.num}">
    <span class="badge-num">Query ${q.num}</span>
    <h6>${q.title}</h6>
    <p class="desc">${q.desc}</p>
  </div>
`).join('');
document.getElementById('queryMenu').innerHTML = menuHtml;

let currentQuery = null;

// Esegue una query
async function executeQuery(num) {
  const query = queries.find(q => q.num === num);
  if (!query) return;

  currentQuery = num;

  // Aggiorna UI
  document.querySelectorAll('.query-card').forEach(card => card.classList.remove('active'));
  document.getElementById(`card${num}`).classList.add('active');
  
  document.getElementById('resultSection').style.display = 'block';
  document.getElementById('resultTitle').innerHTML = `<i class="bi bi-code-square me-2"></i>Query ${num}: ${query.title}`;
  document.getElementById('resultEndpoint').textContent = query.endpoint;
  document.getElementById('jsonResult').innerHTML = '<div class="loading"><i class="bi bi-hourglass-split"></i><br>Caricamento...</div>';
  document.getElementById('metaInfo').innerHTML = '';

  // Scrolla al risultato
  document.getElementById('resultSection').scrollIntoView({ behavior: 'smooth', block: 'nearest' });

  try {
    const response = await fetch(query.path, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });

    const data = await response.json();

    // Mostra metadata se presente
    if (data.meta) {
      document.getElementById('metaInfo').innerHTML = `
        <div class="meta-info">
          <div class="meta-item">
            <div class="meta-label">Totale</div>
            <div class="meta-value">${data.meta.total || 0}</div>
          </div>
          <div class="meta-item">
            <div class="meta-label">Pagina</div>
            <div class="meta-value">${data.meta.current_page || 1} / ${data.meta.last_page || 1}</div>
          </div>
          <div class="meta-item">
            <div class="meta-label">Per Pagina</div>
            <div class="meta-value">${data.meta.per_page || 'N/A'}</div>
          </div>
          <div class="meta-item">
            <div class="meta-label">Status</div>
            <div class="meta-value" style="color:${response.ok ? '#16a34a' : '#dc2626'}">${response.status}</div>
          </div>
        </div>
      `;
    }

    // Mostra JSON
    document.getElementById('jsonResult').textContent = JSON.stringify(data, null, 2);

  } catch (error) {
    document.getElementById('jsonResult').innerHTML = `
      <div style="color:#ef4444;">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Errore durante l'esecuzione della query: ${error.message}
      </div>
    `;
  }
}

// Esegui la prima query all'avvio
executeQuery(1);
</script>

</body>
</html>
