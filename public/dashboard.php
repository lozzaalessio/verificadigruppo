<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>FornitoriPezziDB – Dashboard</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"/>
<style>
:root{--sidebar:260px;--dark:#0f172a;--dark2:#1e3a5f;}
body{background:#f0f4f8;font-family:'Segoe UI',system-ui,sans-serif;margin:0;}

/* Login */
#loginPage{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#0f172a,#1e40af);}
.login-box{background:#fff;border-radius:1rem;padding:2.5rem;width:100%;max-width:380px;box-shadow:0 20px 60px rgba(0,0,0,.3);}
.login-box h2{font-size:1.3rem;font-weight:800;color:var(--dark);margin-bottom:1.5rem;text-align:center;}

/* App */
#appPage{display:none;}
#sidebar{width:var(--sidebar);min-height:100vh;background:var(--dark);color:#e2e8f0;position:fixed;top:0;left:0;z-index:100;overflow-y:auto;display:flex;flex-direction:column;}
#sidebar .brand{padding:1.3rem;border-bottom:1px solid rgba(255,255,255,.08);}
#sidebar .brand h5{font-size:.9rem;font-weight:800;color:#93c5fd;margin:0;}
#sidebar .brand small{font-size:.68rem;color:#64748b;}
.sl{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#475569;padding:.9rem 1.3rem .2rem;}
#sidebar a.nl{color:#cbd5e1;font-size:.82rem;padding:.45rem 1.3rem;display:flex;align-items:center;gap:.55rem;text-decoration:none;transition:background .15s,color .15s;cursor:pointer;border:none;background:none;width:100%;}
#sidebar a.nl:hover,#sidebar a.nl.active{background:rgba(99,179,237,.12);color:#93c5fd;}
#sidebar a.nl i{width:1rem;text-align:center;}
#main{margin-left:var(--sidebar);padding:1.6rem 1.8rem;}
.topbar{background:#fff;border-radius:.7rem;padding:.9rem 1.3rem;margin-bottom:1.5rem;box-shadow:0 1px 4px rgba(0,0,0,.06);display:flex;align-items:center;gap:.8rem;}
.topbar h1{font-size:1.1rem;font-weight:700;margin:0;color:var(--dark);}

/* Sections */
.section{display:none;}
.section.active{display:block;}

/* Table card */
.tcard{background:#fff;border-radius:.7rem;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden;}
.tcard-head{background:linear-gradient(135deg,var(--dark),var(--dark2));color:#e2e8f0;padding:.8rem 1.2rem;display:flex;align-items:center;gap:.6rem;}
.tcard-head h6{margin:0;font-size:.88rem;font-weight:600;flex:1;}
.tcard-body{padding:1rem;}

/* Pag */
.pag-bar{display:flex;align-items:center;gap:.5rem;margin-top:.8rem;font-size:.8rem;flex-wrap:wrap;}
.pag-bar select{font-size:.78rem;padding:.2rem .4rem;border-radius:.35rem;border:1px solid #cbd5e1;}

/* Badges colore */
.badge-rosso{background:#fee2e2;color:#dc2626;}
.badge-verde{background:#dcfce7;color:#16a34a;}
.badge-blu{background:#dbeafe;color:#1d4ed8;}

/* Action buttons */
.btn-icon{border:none;background:none;padding:.25rem .4rem;border-radius:.35rem;cursor:pointer;font-size:.85rem;transition:background .15s;}
.btn-icon:hover{background:#f1f5f9;}
.btn-icon.del:hover{background:#fee2e2;color:#dc2626;}
.btn-icon.edt:hover{background:#dbeafe;color:#1d4ed8;}
.btn-icon.view:hover{background:#f0fdf4;color:#16a34a;}

/* Dialog overlay */
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9000;align-items:center;justify-content:center;}
.overlay.show{display:flex;}
.dialog{background:#fff;border-radius:.9rem;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3);}
.dialog-head{background:linear-gradient(135deg,var(--dark),var(--dark2));color:#e2e8f0;padding:1rem 1.3rem;border-radius:.9rem .9rem 0 0;display:flex;align-items:center;gap:.6rem;}
.dialog-head h6{margin:0;font-size:.95rem;font-weight:700;flex:1;}
.dialog-body{padding:1.3rem;}
.dialog-foot{padding:.9rem 1.3rem;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:.6rem;}
.fl{font-size:.76rem;font-weight:600;color:#374151;margin-bottom:.2rem;display:block;}
.form-control,.form-select{font-size:.82rem;border-radius:.4rem;}
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:.6rem .8rem;}
.detail-item .dk{font-size:.7rem;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:.05em;}
.detail-item .dv{font-size:.88rem;color:var(--dark);font-weight:600;}

/* Toast */
#tc{position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;display:flex;flex-direction:column;gap:.5rem;}
.tmsg{background:#1e293b;color:#f1f5f9;padding:.65rem 1.1rem;border-radius:.55rem;font-size:.82rem;box-shadow:0 4px 16px rgba(0,0,0,.25);animation:si .25s ease;}
.tmsg.success{border-left:3px solid #22c55e;}
.tmsg.error{border-left:3px solid #ef4444;}
@keyframes si{from{transform:translateX(60px);opacity:0}to{transform:translateX(0);opacity:1}}

/* Query ep cards */
details.ep-card{background:#fff;border-radius:.7rem;box-shadow:0 1px 4px rgba(0,0,0,.06);margin-bottom:1rem;overflow:hidden;}
details.ep-card summary{background:linear-gradient(135deg,var(--dark),var(--dark2));color:#e2e8f0;padding:.8rem 1.2rem;display:flex;align-items:center;gap:.7rem;cursor:pointer;list-style:none;}
details.ep-card summary::-webkit-details-marker{display:none;}
details.ep-card summary h6{margin:0;font-size:.87rem;font-weight:600;flex:1;}
details.ep-card summary .gb{background:rgba(34,197,94,.15);color:#4ade80;font-size:.65rem;font-weight:700;padding:.12rem .44rem;border-radius:.3rem;}
details.ep-card[open] summary .chv{transform:rotate(180deg);}
details.ep-card summary .chv{transition:transform .25s;color:#64748b;}
.ep-body{padding:1.1rem;}
.ep-desc{font-size:.8rem;color:#475569;margin-bottom:.8rem;}
.url-row{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.45rem;padding:.4rem .85rem;font-family:monospace;font-size:.78rem;display:flex;gap:.5rem;margin-bottom:.8rem;}
.url-row .method{color:#16a34a;font-weight:700;}
.resp-pre{background:#0f172a;color:#a5f3fc;border-radius:.55rem;padding:.85rem 1rem;font-size:.75rem;max-height:300px;overflow:auto;white-space:pre;font-family:'Courier New',monospace;margin-top:.8rem;}
.resp-meta{font-size:.72rem;color:#64748b;margin-bottom:.3rem;display:flex;gap:.8rem;flex-wrap:wrap;}
.resp-meta .ok{color:#16a34a;font-weight:700;}
.resp-meta .er{color:#dc2626;font-weight:700;}
</style>
</head>
<body>

<!-- ══ LOGIN ══ -->
<div id="loginPage">
  <div class="login-box">
    <h2><i class="bi bi-boxes text-primary me-2"></i>FornitoriPezziDB</h2>
    <div id="lgErr" class="alert alert-danger py-2 mb-3" style="display:none;font-size:.82rem;"></div>
    <div class="mb-3"><label class="fl">Username</label>
      <input id="lgUser" class="form-control" value="admin" placeholder="admin / acme_user / widget_user"/></div>
    <div class="mb-3"><label class="fl">Password</label>
      <input id="lgPass" type="password" class="form-control" value="password"/></div>
    <button class="btn btn-primary w-100 fw-semibold" onclick="doLogin()">
      <i class="bi bi-box-arrow-in-right me-2"></i>Accedi</button>
    <p class="text-center mt-3" style="font-size:.71rem;color:#94a3b8;">
      Demo · admin/password · acme_user/password · widget_user/password</p>
  </div>
</div>

<!-- ══ APP ══ -->
<div id="appPage">
  <nav id="sidebar">
    <div class="brand">
      <h5><i class="bi bi-boxes me-2"></i>FornitoriPezziDB</h5>
      <small id="sbUser">—</small>
    </div>
    <div id="adminNav">
      <div class="sl">Amministrazione</div>
      <a class="nl" onclick="show('sFornitori')"><i class="bi bi-building"></i>Fornitori</a>
      <a class="nl" onclick="show('sPezzi')"><i class="bi bi-gear"></i>Pezzi</a>
      <a class="nl" onclick="show('sCatalogo')"><i class="bi bi-journal-text"></i>Catalogo</a>
      <a class="nl" onclick="show('sUtenti')"><i class="bi bi-people"></i>Utenti</a>
      <div class="sl">Query</div>
      <a class="nl" onclick="show('sQuery')"><i class="bi bi-search"></i>Query 1–10</a>
    </div>
    <div id="fornNav" style="display:none">
      <div class="sl">Il mio catalogo</div>
      <a class="nl" onclick="show('sMioCatalogo')"><i class="bi bi-journal-text"></i>Catalogo</a>
      <a class="nl" onclick="show('sPezziDisp')"><i class="bi bi-plus-circle"></i>Aggiungi pezzi</a>
    </div>
    <div class="mt-auto p-3">
      <button class="btn btn-sm btn-outline-secondary w-100" onclick="doLogout()">
        <i class="bi bi-box-arrow-right me-1"></i>Logout</button>
    </div>
  </nav>

  <div id="main">
    <div class="topbar">
      <h1 id="pageTitle"><i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>Dashboard</h1>
      <span class="badge bg-success ms-auto" id="roleBadge">—</span>
    </div>

    <!-- FORNITORI -->
    <div id="sFornitori" class="section">
      <div class="tcard">
        <div class="tcard-head"><i class="bi bi-building"></i><h6>Gestione Fornitori</h6>
          <button class="btn btn-sm btn-light ms-auto" onclick="openCreate('fornitore')"><i class="bi bi-plus-lg me-1"></i>Nuovo</button></div>
        <div class="tcard-body">
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem;">
              <thead class="table-light"><tr><th>FID</th><th>Nome</th><th>Indirizzo</th><th style="width:90px"></th></tr></thead>
              <tbody id="tbFornitori"></tbody></table></div>
          <div class="pag-bar" id="pagFornitori"></div></div></div></div>

    <!-- PEZZI -->
    <div id="sPezzi" class="section">
      <div class="tcard">
        <div class="tcard-head"><i class="bi bi-gear"></i><h6>Gestione Pezzi</h6>
          <button class="btn btn-sm btn-light ms-auto" onclick="openCreate('pezzo')"><i class="bi bi-plus-lg me-1"></i>Nuovo</button></div>
        <div class="tcard-body">
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem;">
              <thead class="table-light"><tr><th>PID</th><th>Nome</th><th>Colore</th><th style="width:90px"></th></tr></thead>
              <tbody id="tbPezzi"></tbody></table></div>
          <div class="pag-bar" id="pagPezzi"></div></div></div></div>

    <!-- CATALOGO -->
    <div id="sCatalogo" class="section">
      <div class="tcard">
        <div class="tcard-head"><i class="bi bi-journal-text"></i><h6>Gestione Catalogo</h6>
          <button class="btn btn-sm btn-light ms-auto" onclick="openCreate('catalogoAdmin')"><i class="bi bi-plus-lg me-1"></i>Nuova voce</button></div>
        <div class="tcard-body">
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem;">
              <thead class="table-light"><tr><th>Fornitore</th><th>Pezzo</th><th>Colore</th><th>Costo</th><th style="width:90px"></th></tr></thead>
              <tbody id="tbCatalogo"></tbody></table></div>
          <div class="pag-bar" id="pagCatalogo"></div></div></div></div>

    <!-- UTENTI -->
    <div id="sUtenti" class="section">
      <div class="tcard">
        <div class="tcard-head"><i class="bi bi-people"></i><h6>Gestione Utenti</h6>
          <button class="btn btn-sm btn-light ms-auto" onclick="openCreate('utente')"><i class="bi bi-plus-lg me-1"></i>Nuovo</button></div>
        <div class="tcard-body">
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem;">
              <thead class="table-light"><tr><th>UID</th><th>Username</th><th>Ruolo</th><th>Fornitore</th><th>Creato</th><th style="width:90px"></th></tr></thead>
              <tbody id="tbUtenti"></tbody></table></div>
          <div class="pag-bar" id="pagUtenti"></div></div></div></div>

    <!-- MIO CATALOGO (fornitore) -->
    <div id="sMioCatalogo" class="section">
      <div class="tcard">
        <div class="tcard-head"><i class="bi bi-journal-text"></i><h6 id="mioCatTitle">Il mio catalogo</h6></div>
        <div class="tcard-body">
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem;">
              <thead class="table-light"><tr><th>PID</th><th>Nome</th><th>Colore</th><th>Costo</th><th style="width:90px"></th></tr></thead>
              <tbody id="tbMioCat"></tbody></table></div>
          <div class="pag-bar" id="pagMioCat"></div></div></div></div>

    <!-- PEZZI DISPONIBILI (fornitore) -->
    <div id="sPezziDisp" class="section">
      <div class="tcard">
        <div class="tcard-head"><i class="bi bi-plus-circle"></i><h6>Pezzi da aggiungere</h6></div>
        <div class="tcard-body">
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:.82rem;">
              <thead class="table-light"><tr><th>PID</th><th>Nome</th><th>Colore</th><th style="width:130px"></th></tr></thead>
              <tbody id="tbPezziDisp"></tbody></table></div>
          <div class="pag-bar" id="pagPezziDisp"></div></div></div></div>

    <!-- QUERY 1-10 -->
    <div id="sQuery" class="section">
      <div class="tcard mb-3" style="padding:.85rem 1.2rem;">
        <div style="display:flex;align-items:center;gap:.8rem;flex-wrap:wrap;">
          <label class="fl mb-0"><i class="bi bi-hdd-network me-1 text-primary"></i>Base URL:</label>
          <input id="baseUrl" class="form-control" style="max-width:280px;font-size:.8rem;font-family:monospace;" value=""/>
        </div>
      </div>
      <!-- ep 1 -->
      <details class="ep-card"><summary><span class="badge bg-primary me-1">1</span><h6>Pezzi distinti in catalogo</h6><span class="gb">GET</span><i class="bi bi-chevron-down chv ms-2"></i></summary><div class="ep-body"><p class="ep-desc">Pezzi presenti in almeno una riga del Catalogo.</p><div class="url-row"><span class="method">GET</span>/1</div><div style="display:flex;gap:.5rem;align-items:flex-end;"><div><label class="fl">page</label><input type="number" id="q1p" value="1" min="1" class="form-control" style="width:70px;font-size:.8rem;"></div><div><label class="fl">per_page</label><input type="number" id="q1pp" value="20" min="1" max="100" class="form-control" style="width:80px;font-size:.8rem;"></div><button class="btn btn-sm btn-primary" onclick="qcall(1)"><i class="bi bi-play-fill me-1"></i>Esegui</button></div><div id="qr1" style="display:none;"><div class="resp-meta" id="qm1"></div><pre class="resp-pre" id="qp1"></pre></div></div></details>
      <!-- ep 2 -->
      <details class="ep-card"><summary><span class="badge bg-primary me-1">2</span><h6>Fornitori con TUTTI i pezzi</h6><span class="gb">GET</span><i class="bi bi-chevron-down chv ms-2"></i></summary><div class="ep-body"><p class="ep-desc">Fornitori il cui catalogo copre l'intero insieme dei pezzi.</p><div class="url-row"><span class="method">GET</span>/2</div><button class="btn btn-sm btn-primary" onclick="qcall(2)"><i class="bi bi-play-fill me-1"></i>Esegui</button><div id="qr2" style="display:none;"><div class="resp-meta" id="qm2"></div><pre class="resp-pre" id="qp2"></pre></div></div></details>
      <!-- ep 3 -->
      <details class="ep-card"><summary><span class="badge bg-primary me-1">3</span><h6>Fornitori con TUTTI i pezzi di un colore</h6><span class="gb">GET</span><i class="bi bi-chevron-down chv ms-2"></i></summary><div class="ep-body"><p class="ep-desc">Fornitori il cui catalogo include tutti i pezzi del colore scelto.</p><div class="url-row"><span class="method">GET</span>/3?colore=...</div><div style="display:flex;gap:.5rem;align-items:flex-end;"><div><label class="fl">colore</label><select id="q3c" class="form-select" style="width:110px;font-size:.8rem;"><option>rosso</option><option>verde</option><option>blu</option></select></div><button class="btn btn-sm btn-primary" onclick="qcall(3)"><i class="bi bi-play-fill me-1"></i>Esegui</button></div><div id="qr3" style="display:none;"><div class="resp-meta" id="qm3"></div><pre class="resp-pre" id="qp3"></pre></div></div></details>
      <!-- ep 4 -->
      <details class="ep-card"><summary><span class="badge bg-primary me-1">4</span><h6>Pezzi venduti SOLO da un fornitore</h6><span class="gb">GET</span><i class="bi bi-chevron-down chv ms-2"></i></summary><div class="ep-body"><p class="ep-desc">Pezzi esclusivi di un fornitore (non in altri cataloghi).</p><div class="url-row"><span class="method">GET</span>/4?fnome=...</div><div style="display:flex;gap:.5rem;align-items:flex-end;"><div><label class="fl">fnome</label><input type="text" id="q4f" value="Acme" class="form-control" style="width:150px;font-size:.8rem;"></div><button class="btn btn-sm btn-primary" onclick="qcall(4)"><i class="bi bi-play-fill me-1"></i>Esegui</button></div><div id="qr4" style="display:none;"><div class="resp-meta" id="qm4"></div><pre class="resp-pre" id="qp4"></pre></div></div></details>
      <!-- ep 5 -->
      <details class="ep-card"><summary><span class="badge bg-primary me-1">5</span><h6>Fornitori con prezzo sopra la media</h6><span class="gb">GET</span><i class="bi bi-chevron-down chv ms-2"></i></summary><div class="ep-body"><p class="ep-desc">Fornitori con almeno un pezzo a costo superiore alla media.</p><div class="url-row"><span class="method">GET</span>/5</div><div style="display:flex;gap:.5rem;align-items:flex-end;"><div><label class="fl">page</label><input type="number" id="q5p" value="1" min="1" class="form-control" style="width:70px;font-size:.8rem;"></div><div><label class="fl">per_page</label><input type="number" id="q5pp" value="20" min="1" max="100" class="form-control" style="width:80px;font-size:.8rem;"></div><button class="btn btn-sm btn-primary" onclick="qcall(5)"><i class="bi bi-play-fill me-1"></i>Esegui</button></div><div id="qr5" style="display:none;"><div class="resp-meta" id="qm5"></div><pre class="resp-pre" id="qp5"></pre></div></div></details>
      <!-- ep 6 -->
      <details class="ep-card"><summary><span class="badge bg-primary me-1">6</span><h6>Fornitore con costo minimo per pezzo</h6><span class="gb">GET</span><i class="bi bi-chevron-down chv ms-2"></i></summary><div class="ep-body"><p class="ep-desc">Per ogni pezzo: fornitore/i con il prezzo minimo.</p><div class="url-row"><span class="method">GET</span>/6</div><div style="display:flex;gap:.5rem;align-items:flex-end;"><div><label class="fl">page</label><input type="number" id="q6p" value="1" min="1" class="form-control" style="width:70px;font-size:.8rem;"></div><div><label class="fl">per_page</label><input type="number" id="q6pp" value="20" min="1" max="100" class="form-control" style="width:80px;font-size:.8rem;"></div><button class="btn btn-sm btn-primary" onclick="qcall(6)"><i class="bi bi-play-fill me-1"></i>Esegui</button></div><div id="qr6" style="display:none;"><div class="resp-meta" id="qm6"></div><pre class="resp-pre" id="qp6"></pre></div></div></details>
      <!-- ep 7 -->
      <details class="ep-card"><summary><span class="badge bg-primary me-1">7</span><h6>Fornitori con SOLO pezzi rossi</h6><span class="gb">GET</span><i class="bi bi-chevron-down chv ms-2"></i></summary><div class="ep-body"><p class="ep-desc">Fornitori con esclusivamente pezzi rossi in catalogo.</p><div class="url-row"><span class="method">GET</span>/7</div><button class="btn btn-sm btn-primary" onclick="qcall(7)"><i class="bi bi-play-fill me-1"></i>Esegui</button><div id="qr7" style="display:none;"><div class="resp-meta" id="qm7"></div><pre class="resp-pre" id="qp7"></pre></div></div></details>
      <!-- ep 8 -->
      <details class="ep-card"><summary><span class="badge bg-primary me-1">8</span><h6>Fornitori con pezzi rossi E verdi</h6><span class="gb">GET</span><i class="bi bi-chevron-down chv ms-2"></i></summary><div class="ep-body"><p class="ep-desc">Fornitori con almeno un pezzo rosso e uno verde.</p><div class="url-row"><span class="method">GET</span>/8</div><div style="display:flex;gap:.5rem;align-items:flex-end;"><div><label class="fl">page</label><input type="number" id="q8p" value="1" min="1" class="form-control" style="width:70px;font-size:.8rem;"></div><div><label class="fl">per_page</label><input type="number" id="q8pp" value="20" min="1" max="100" class="form-control" style="width:80px;font-size:.8rem;"></div><button class="btn btn-sm btn-primary" onclick="qcall(8)"><i class="bi bi-play-fill me-1"></i>Esegui</button></div><div id="qr8" style="display:none;"><div class="resp-meta" id="qm8"></div><pre class="resp-pre" id="qp8"></pre></div></div></details>
      <!-- ep 9 -->
      <details class="ep-card"><summary><span class="badge bg-primary me-1">9</span><h6>Fornitori con pezzi di colori selezionati (OR)</h6><span class="gb">GET</span><i class="bi bi-chevron-down chv ms-2"></i></summary><div class="ep-body"><p class="ep-desc">Fornitori con almeno un pezzo del colore selezionato.</p><div class="url-row"><span class="method">GET</span>/9?colori=...</div><div style="display:flex;gap:.8rem;align-items:flex-end;flex-wrap:wrap;"><div><label class="fl">colori</label><div style="display:flex;gap:.6rem;"><div class="form-check"><input class="form-check-input" type="checkbox" id="c9r" value="rosso" checked><label class="form-check-label" for="c9r" style="font-size:.78rem">rosso</label></div><div class="form-check"><input class="form-check-input" type="checkbox" id="c9v" value="verde" checked><label class="form-check-label" for="c9v" style="font-size:.78rem">verde</label></div><div class="form-check"><input class="form-check-input" type="checkbox" id="c9b" value="blu"><label class="form-check-label" for="c9b" style="font-size:.78rem">blu</label></div></div></div><div><label class="fl">page</label><input type="number" id="q9p" value="1" min="1" class="form-control" style="width:70px;font-size:.8rem;"></div><div><label class="fl">per_page</label><input type="number" id="q9pp" value="20" min="1" max="100" class="form-control" style="width:80px;font-size:.8rem;"></div><button class="btn btn-sm btn-primary" onclick="qcall(9)"><i class="bi bi-play-fill me-1"></i>Esegui</button></div><div id="qr9" style="display:none;"><div class="resp-meta" id="qm9"></div><pre class="resp-pre" id="qp9"></pre></div></div></details>
      <!-- ep 10 -->
      <details class="ep-card"><summary><span class="badge bg-primary me-1">10</span><h6>Pezzi con almeno N fornitori</h6><span class="gb">GET</span><i class="bi bi-chevron-down chv ms-2"></i></summary><div class="ep-body"><p class="ep-desc">Pezzi presenti nel catalogo di almeno N fornitori distinti.</p><div class="url-row"><span class="method">GET</span>/10?min_fornitori=...</div><div style="display:flex;gap:.5rem;align-items:flex-end;"><div><label class="fl">min_fornitori</label><input type="number" id="q10m" value="2" min="1" class="form-control" style="width:105px;font-size:.8rem;"></div><div><label class="fl">page</label><input type="number" id="q10p" value="1" min="1" class="form-control" style="width:70px;font-size:.8rem;"></div><div><label class="fl">per_page</label><input type="number" id="q10pp" value="20" min="1" max="100" class="form-control" style="width:80px;font-size:.8rem;"></div><button class="btn btn-sm btn-primary" onclick="qcall(10)"><i class="bi bi-play-fill me-1"></i>Esegui</button></div><div id="qr10" style="display:none;"><div class="resp-meta" id="qm10"></div><pre class="resp-pre" id="qp10"></pre></div></div></details>
    </div>
  </div>
</div>

<!-- ══ DIALOG DETTAGLIO ══ -->
<div class="overlay" id="dlgDetail" onclick="closeDlg('dlgDetail')">
  <div class="dialog" onclick="event.stopPropagation()">
    <div class="dialog-head"><i class="bi bi-info-circle"></i><h6 id="dlgDTitle">Dettaglio</h6>
      <button class="btn-icon ms-auto text-white" onclick="closeDlg('dlgDetail')"><i class="bi bi-x-lg"></i></button></div>
    <div class="dialog-body"><div class="detail-grid" id="dlgDGrid"></div></div>
    <div class="dialog-foot"><button class="btn btn-sm btn-secondary" onclick="closeDlg('dlgDetail')">Chiudi</button></div>
  </div>
</div>

<!-- ══ DIALOG FORM ══ -->
<div class="overlay" id="dlgForm" onclick="closeDlg('dlgForm')">
  <div class="dialog" onclick="event.stopPropagation()">
    <div class="dialog-head"><i class="bi bi-pencil-square"></i><h6 id="dlgFTitle">Form</h6>
      <button class="btn-icon ms-auto text-white" onclick="closeDlg('dlgForm')"><i class="bi bi-x-lg"></i></button></div>
    <div class="dialog-body" id="dlgFBody"></div>
    <div class="dialog-foot">
      <button class="btn btn-sm btn-secondary" onclick="closeDlg('dlgForm')">Annulla</button>
      <button class="btn btn-sm btn-primary" onclick="saveForm()"><i class="bi bi-check-lg me-1"></i>Salva</button>
    </div>
  </div>
</div>

<!-- ══ DIALOG ELIMINA ══ -->
<div class="overlay" id="dlgDel" onclick="closeDlg('dlgDel')">
  <div class="dialog" style="max-width:380px;" onclick="event.stopPropagation()">
    <div class="dialog-head" style="background:linear-gradient(135deg,#7f1d1d,#dc2626);">
      <i class="bi bi-trash3"></i><h6>Conferma eliminazione</h6>
      <button class="btn-icon ms-auto text-white" onclick="closeDlg('dlgDel')"><i class="bi bi-x-lg"></i></button></div>
    <div class="dialog-body"><p id="dlgDelMsg" style="font-size:.88rem;color:#374151;margin:0;"></p></div>
    <div class="dialog-foot">
      <button class="btn btn-sm btn-secondary" onclick="closeDlg('dlgDel')">Annulla</button>
      <button class="btn btn-sm btn-danger" id="dlgDelBtn"><i class="bi bi-trash3 me-1"></i>Elimina</button>
    </div>
  </div>
</div>

<div id="tc"></div>

<script>
/* ── State ── */
let TOK='', USR=null;
let fType='', fData=null;
const PG={};
function pg(k){return PG[k]||(PG[k]={page:1,pp:10});}
const BASE=()=>(document.getElementById('baseUrl')?.value||'').replace(/\/$/,'');

/* ── API ── */
async function api(m,path,body){
  const o={method:m,headers:{'Content-Type':'application/json'}};
  if(TOK) o.headers['Authorization']='Bearer '+TOK;
  if(body) o.body=JSON.stringify(body);
  const r=await fetch(BASE()+path,o);
  const d=await r.json().catch(()=>({}));
  return{ok:r.ok,status:r.status,data:d};
}

/* ── Toast ── */
function toast(msg,t='success'){
  const e=document.createElement('div');
  e.className='tmsg '+t; e.textContent=msg;
  document.getElementById('tc').appendChild(e);
  setTimeout(()=>e.remove(),3200);
}

/* ── Login/Logout ── */
async function doLogin(){
  const r=await api('POST','/auth/login',{username:v('lgUser'),password:v('lgPass')});
  if(!r.ok){show_err(r.data.error||'Errore');return;}
  // Salva il token e i dati utente in sessionStorage
  sessionStorage.setItem('token', r.data.token);
  sessionStorage.setItem('user', JSON.stringify(r.data.user));
  // Reindirizza al file user-info.php
  window.location.href = 'user-info.php';
}
function show_err(msg){const e=document.getElementById('lgErr');e.style.display='';e.textContent=msg;}
function doLogout(){TOK='';USR=null;document.getElementById('appPage').style.display='none';document.getElementById('loginPage').style.display='flex';}

/* ── Navigation ── */
const TITLES={sFornitori:'Gestione Fornitori',sPezzi:'Gestione Pezzi',sCatalogo:'Gestione Catalogo',sUtenti:'Gestione Utenti',sMioCatalogo:'Il mio catalogo',sPezziDisp:'Aggiungi pezzi',sQuery:'Query API 1–10'};
function show(id){
  document.querySelectorAll('.section').forEach(s=>s.classList.remove('active'));
  document.getElementById(id)?.classList.add('active');
  document.getElementById('pageTitle').innerHTML='<i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>'+(TITLES[id]||id);
  ({sFornitori:loadFornitori,sPezzi:loadPezzi,sCatalogo:loadCatalogo,sUtenti:loadUtenti,sMioCatalogo:loadMioCat,sPezziDisp:loadPezziDisp})[id]?.();
}

/* ── Paginazione ── */
function renderPag(key,meta,fn){
  const el=document.getElementById('pag'+key); if(!el) return;
  const{total,current_page:cp,last_page:lp,per_page:pp}=meta;
  el.innerHTML=`<span style="color:#64748b;">Tot: <b>${total}</b></span>
    <button class="btn btn-sm btn-outline-secondary py-0" ${cp<=1?'disabled':''} onclick="goPage('${key}',${cp-1})">‹</button>
    <span>${cp}/${lp}</span>
    <button class="btn btn-sm btn-outline-secondary py-0" ${cp>=lp?'disabled':''} onclick="goPage('${key}',${cp+1})">›</button>
    <select onchange="setPP('${key}',this.value)">${[5,10,20,50].map(n=>`<option value="${n}" ${n==pp?'selected':''}>${n}/pag</option>`).join('')}</select>`;
}
function goPage(k,p){pg(k).page=p;reload(k);}
function setPP(k,v){pg(k).pp=parseInt(v);pg(k).page=1;reload(k);}
function reload(k){({Fornitori:loadFornitori,Pezzi:loadPezzi,Catalogo:loadCatalogo,Utenti:loadUtenti,MioCat:loadMioCat,PezziDisp:loadPezziDisp})[k]?.();}
function qs(k){const{page,pp}=pg(k);return`?page=${page}&per_page=${pp}`;}

/* ── Utils ── */
function esc(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function v(id){return document.getElementById(id)?.value?.trim()||'';}
function closeDlg(id){document.getElementById(id).classList.remove('show');}
function cb(c){const m={rosso:'badge-rosso',verde:'badge-verde',blu:'badge-blu'};return`<span class="badge ${m[c]||'bg-secondary text-white'}">${c}</span>`;}

/* ── Load tables ── */
async function loadFornitori(){
  const r=await api('GET','/admin/fornitori'+qs('Fornitori')); if(!r.ok)return;
  const{data,meta}=r.data;
  document.getElementById('tbFornitori').innerHTML=data.map(f=>`
    <tr><td><code>${f.fid}</code></td><td>${esc(f.fnome)}</td><td style="color:#64748b;font-size:.78rem;">${esc(f.indirizzo||'—')}</td>
    <td>
      <button class="btn-icon view" title="Dettaglio" onclick='showDetail("Fornitore",${JSON.stringify(f)})'><i class="bi bi-eye"></i></button>
      <button class="btn-icon edt" title="Modifica" onclick='openEdit("fornitore",${JSON.stringify(f)})'><i class="bi bi-pencil"></i></button>
      <button class="btn-icon del" title="Elimina" onclick='confirmDel("fornitore","${f.fid}","${esc(f.fnome)}")'><i class="bi bi-trash3"></i></button>
    </td></tr>`).join('');
  renderPag('Fornitori',meta,loadFornitori);
}

async function loadPezzi(){
  const r=await api('GET','/admin/pezzi'+qs('Pezzi')); if(!r.ok)return;
  const{data,meta}=r.data;
  document.getElementById('tbPezzi').innerHTML=data.map(p=>`
    <tr><td><code>${p.pid}</code></td><td>${esc(p.pnome)}</td><td>${cb(p.colore)}</td>
    <td>
      <button class="btn-icon view" onclick='showDetail("Pezzo",${JSON.stringify(p)})'><i class="bi bi-eye"></i></button>
      <button class="btn-icon edt" onclick='openEdit("pezzo",${JSON.stringify(p)})'><i class="bi bi-pencil"></i></button>
      <button class="btn-icon del" onclick='confirmDel("pezzo","${p.pid}","${esc(p.pnome)}")'><i class="bi bi-trash3"></i></button>
    </td></tr>`).join('');
  renderPag('Pezzi',meta,loadPezzi);
}

async function loadCatalogo(){
  const r=await api('GET','/admin/catalogo'+qs('Catalogo')); if(!r.ok)return;
  const{data,meta}=r.data;
  document.getElementById('tbCatalogo').innerHTML=data.map(c=>`
    <tr><td>${esc(c.fnome)} <small class="text-muted">(${c.fid})</small></td>
    <td>${esc(c.pnome)} <small class="text-muted">(${c.pid})</small></td>
    <td>${cb(c.colore)}</td><td><b>€ ${parseFloat(c.costo).toFixed(2)}</b></td>
    <td>
      <button class="btn-icon view" onclick='showDetail("Catalogo",${JSON.stringify(c)})'><i class="bi bi-eye"></i></button>
      <button class="btn-icon edt" onclick='openEdit("catalogoAdmin",${JSON.stringify(c)})'><i class="bi bi-pencil"></i></button>
      <button class="btn-icon del" onclick='confirmDel("catalogoAdmin","${c.fid}/${c.pid}","${esc(c.fnome)} → ${esc(c.pnome)}")'><i class="bi bi-trash3"></i></button>
    </td></tr>`).join('');
  renderPag('Catalogo',meta,loadCatalogo);
}

async function loadUtenti(){
  const r=await api('GET','/admin/utenti'+qs('Utenti')); if(!r.ok)return;
  const{data,meta}=r.data;
  document.getElementById('tbUtenti').innerHTML=data.map(u=>`
    <tr><td>${u.uid}</td><td>${esc(u.username)}</td>
    <td><span class="badge ${u.ruolo==='admin'?'bg-danger':'bg-primary'}">${u.ruolo}</span></td>
    <td>${u.fid||'—'}</td><td style="font-size:.74rem;color:#64748b;">${(u.created_at||'').substring(0,10)}</td>
    <td>
      <button class="btn-icon view" onclick='showDetail("Utente",${JSON.stringify(u)})'><i class="bi bi-eye"></i></button>
      <button class="btn-icon edt" onclick='openEdit("utente",${JSON.stringify(u)})'><i class="bi bi-pencil"></i></button>
      <button class="btn-icon del" onclick='confirmDel("utente","${u.uid}","${esc(u.username)}")'><i class="bi bi-trash3"></i></button>
    </td></tr>`).join('');
  renderPag('Utenti',meta,loadUtenti);
}

async function loadMioCat(){
  const r=await api('GET','/fornitore/catalogo'+qs('MioCat')); if(!r.ok)return;
  const{data,meta}=r.data;
  document.getElementById('tbMioCat').innerHTML=data.map(c=>`
    <tr><td><code>${c.pid}</code></td><td>${esc(c.pnome)}</td><td>${cb(c.colore)}</td><td><b>€ ${parseFloat(c.costo).toFixed(2)}</b></td>
    <td>
      <button class="btn-icon view" onclick='showDetail("Voce catalogo",${JSON.stringify(c)})'><i class="bi bi-eye"></i></button>
      <button class="btn-icon edt" onclick='openEdit("mioCatalogo",${JSON.stringify(c)})'><i class="bi bi-pencil"></i></button>
      <button class="btn-icon del" onclick='confirmDel("mioCatalogo","${c.pid}","${esc(c.pnome)}")'><i class="bi bi-trash3"></i></button>
    </td></tr>`).join('');
  renderPag('MioCat',meta,loadMioCat);
}

async function loadPezziDisp(){
  const r=await api('GET','/fornitore/pezzi-disponibili'+qs('PezziDisp')); if(!r.ok)return;
  const{data,meta}=r.data;
  document.getElementById('tbPezziDisp').innerHTML=data.map(p=>`
    <tr><td><code>${p.pid}</code></td><td>${esc(p.pnome)}</td><td>${cb(p.colore)}</td>
    <td>
      <button class="btn-icon view" onclick='showDetail("Pezzo",${JSON.stringify(p)})'><i class="bi bi-eye"></i></button>
      <button class="btn btn-sm btn-success py-0" style="font-size:.75rem;" onclick='openAddToMio(${JSON.stringify(p)})'><i class="bi bi-plus-lg me-1"></i>Aggiungi</button>
    </td></tr>`).join('');
  renderPag('PezziDisp',meta,loadPezziDisp);
}

/* ── Dialog Dettaglio ── */
function showDetail(tipo,obj){
  document.getElementById('dlgDTitle').textContent='Dettaglio '+tipo;
  document.getElementById('dlgDGrid').innerHTML=Object.entries(obj).map(([k,val])=>`
    <div class="detail-item"><div class="dk">${k}</div>
    <div class="dv">${val===null||val===undefined||val===''?'<span style="color:#94a3b8;">—</span>':esc(String(val))}</div></div>`).join('');
  document.getElementById('dlgDetail').classList.add('show');
}

/* ── Dialog Form ── */
const FT={
  fornitore:(d)=>`
    <div class="row g-2"><div class="col"><label class="fl">FID *</label><input id="ff_fid" class="form-control" value="${d?.fid||''}" ${d?'readonly':''}></div>
    <div class="col"><label class="fl">Nome *</label><input id="ff_fnome" class="form-control" value="${d?.fnome||''}"></div></div>
    <div class="mt-2"><label class="fl">Indirizzo</label><input id="ff_ind" class="form-control" value="${d?.indirizzo||''}"></div>`,
  pezzo:(d)=>`
    <div class="row g-2"><div class="col"><label class="fl">PID *</label><input id="fp_pid" class="form-control" value="${d?.pid||''}" ${d?'readonly':''}></div>
    <div class="col"><label class="fl">Nome *</label><input id="fp_pnome" class="form-control" value="${d?.pnome||''}"></div></div>
    <div class="mt-2"><label class="fl">Colore *</label><select id="fp_colore" class="form-select">${['rosso','verde','blu'].map(c=>`<option ${c===(d?.colore||'rosso')?'selected':''}>${c}</option>`).join('')}</select></div>`,
  catalogoAdmin:(d)=>`
    <div class="row g-2"><div class="col"><label class="fl">FID *</label><input id="fca_fid" class="form-control" value="${d?.fid||''}" ${d?'readonly':''}></div>
    <div class="col"><label class="fl">PID *</label><input id="fca_pid" class="form-control" value="${d?.pid||''}" ${d?'readonly':''}></div></div>
    <div class="mt-2"><label class="fl">Costo (€) *</label><input id="fca_costo" type="number" step="0.01" min="0.01" class="form-control" value="${d?.costo||''}"></div>`,
  utente:(d)=>`
    <div class="row g-2"><div class="col"><label class="fl">Username *</label><input id="fu_user" class="form-control" value="${d?.username||''}"></div>
    <div class="col"><label class="fl">Password ${d?'(vuoto=invariata)':'*'}</label><input id="fu_pass" type="password" class="form-control" placeholder="${d?'••••':'min 4 car.'}"></div></div>
    <div class="row g-2 mt-1"><div class="col"><label class="fl">Ruolo *</label><select id="fu_ruolo" class="form-select"><option ${(d?.ruolo||'fornitore')==='fornitore'?'selected':''}>fornitore</option><option ${d?.ruolo==='admin'?'selected':''}>admin</option></select></div>
    <div class="col"><label class="fl">FID Fornitore</label><input id="fu_fid" class="form-control" value="${d?.fid||''}" placeholder="es. F01"></div></div>`,
  mioCatalogo:(d)=>`
    <div><label class="fl">Pezzo</label><input class="form-control" value="${d?.pid} – ${esc(d?.pnome||'')}" readonly></div>
    <div class="mt-2"><label class="fl">Nuovo costo (€) *</label><input id="fmc_costo" type="number" step="0.01" min="0.01" class="form-control" value="${d?.costo||''}"></div>`,
  addPezzo:(d)=>`
    <div><label class="fl">Pezzo da aggiungere</label><input class="form-control" value="${d?.pid} – ${esc(d?.pnome||'')}" readonly></div>
    <div class="mt-2"><label class="fl">Costo (€) *</label><input id="fap_costo" type="number" step="0.01" min="0.01" class="form-control" placeholder="0.00"></div>`,
};

function openCreate(t){fType=t;fData=null;document.getElementById('dlgFTitle').textContent='Nuovo '+t;document.getElementById('dlgFBody').innerHTML=FT[t](null);document.getElementById('dlgForm').classList.add('show');}
function openEdit(t,d){fType=t;fData=d;document.getElementById('dlgFTitle').textContent='Modifica '+t;document.getElementById('dlgFBody').innerHTML=FT[t](d);document.getElementById('dlgForm').classList.add('show');}
function openAddToMio(p){fType='addPezzo';fData=p;document.getElementById('dlgFTitle').textContent='Aggiungi al catalogo';document.getElementById('dlgFBody').innerHTML=FT.addPezzo(p);document.getElementById('dlgForm').classList.add('show');}

async function saveForm(){
  const t=fType,d=fData;
  let r;
  if(t==='fornitore'){
    const b={fid:v('ff_fid'),fnome:v('ff_fnome'),indirizzo:v('ff_ind')};
    if(!b.fid||!b.fnome){toast('fid e fnome obbligatori','error');return;}
    r=d?await api('PUT',`/admin/fornitori/${d.fid}`,b):await api('POST','/admin/fornitori',b);
    if(r.ok){toast(d?'Aggiornato':'Creato');closeDlg('dlgForm');loadFornitori();}else toast(r.data.error||'Errore','error');
  } else if(t==='pezzo'){
    const b={pid:v('fp_pid'),pnome:v('fp_pnome'),colore:v('fp_colore')};
    if(!b.pid||!b.pnome){toast('Campi obbligatori mancanti','error');return;}
    r=d?await api('PUT',`/admin/pezzi/${d.pid}`,b):await api('POST','/admin/pezzi',b);
    if(r.ok){toast(d?'Aggiornato':'Creato');closeDlg('dlgForm');loadPezzi();}else toast(r.data.error||'Errore','error');
  } else if(t==='catalogoAdmin'){
    const costo=parseFloat(v('fca_costo'));
    if(!costo||costo<=0){toast('Costo non valido','error');return;}
    if(d){r=await api('PUT',`/admin/catalogo/${d.fid}/${d.pid}`,{costo});}
    else{const fid=v('fca_fid'),pid=v('fca_pid');if(!fid||!pid){toast('fid e pid obbligatori','error');return;}r=await api('POST','/admin/catalogo',{fid,pid,costo});}
    if(r.ok){toast(d?'Aggiornato':'Creato');closeDlg('dlgForm');loadCatalogo();}else toast(r.data.error||'Errore','error');
  } else if(t==='utente'){
    const b={username:v('fu_user'),ruolo:v('fu_ruolo'),fid:v('fu_fid')||null};
    const pw=v('fu_pass'); if(pw) b.password=pw;
    if(!d&&!pw){toast('Password obbligatoria','error');return;}
    r=d?await api('PUT',`/admin/utenti/${d.uid}`,b):await api('POST','/admin/utenti',{...b,password:pw});
    if(r.ok){toast(d?'Aggiornato':'Creato');closeDlg('dlgForm');loadUtenti();}else toast(r.data.error||'Errore','error');
  } else if(t==='mioCatalogo'){
    const costo=parseFloat(v('fmc_costo')); if(!costo||costo<=0){toast('Costo non valido','error');return;}
    r=await api('PUT',`/fornitore/catalogo/${d.pid}`,{costo});
    if(r.ok){toast('Costo aggiornato');closeDlg('dlgForm');loadMioCat();}else toast(r.data.error||'Errore','error');
  } else if(t==='addPezzo'){
    const costo=parseFloat(v('fap_costo')); if(!costo||costo<=0){toast('Costo non valido','error');return;}
    r=await api('POST','/fornitore/catalogo',{pid:fData.pid,costo});
    if(r.ok){toast('Pezzo aggiunto');closeDlg('dlgForm');loadPezziDisp();loadMioCat();}else toast(r.data.error||'Errore','error');
  }
}

/* ── Dialog Elimina ── */
function confirmDel(type,id,label){
  document.getElementById('dlgDelMsg').textContent=`Eliminare "${label}" in modo permanente?`;
  document.getElementById('dlgDelBtn').onclick=()=>doDelete(type,id);
  document.getElementById('dlgDel').classList.add('show');
}
async function doDelete(type,id){
  const map={fornitore:`/admin/fornitori/${id}`,pezzo:`/admin/pezzi/${id}`,catalogoAdmin:`/admin/catalogo/${id}`,utente:`/admin/utenti/${id}`,mioCatalogo:`/fornitore/catalogo/${id}`};
  const r=await api('DELETE',map[type]);
  if(r.ok){toast('Eliminato');closeDlg('dlgDel');({fornitore:loadFornitori,pezzo:loadPezzi,catalogoAdmin:loadCatalogo,utente:loadUtenti,mioCatalogo:loadMioCat})[type]?.();}
  else toast(r.data.error||'Errore','error');
}

/* ── Query 1-10 ── */
function qurl(ep){
  const b=BASE(),p=id=>document.getElementById(id)?.value||'';
  const cases={
    1:`${b}/1?page=${p('q1p')}&per_page=${p('q1pp')}`,
    2:`${b}/2`,
    3:`${b}/3?colore=${p('q3c')}`,
    4:`${b}/4?fnome=${encodeURIComponent(p('q4f'))}`,
    5:`${b}/5?page=${p('q5p')}&per_page=${p('q5pp')}`,
    6:`${b}/6?page=${p('q6p')}&per_page=${p('q6pp')}`,
    7:`${b}/7`,
    8:`${b}/8?page=${p('q8p')}&per_page=${p('q8pp')}`,
    9:(()=>{const cols=['rosso','verde','blu'].filter(c=>document.getElementById('c9'+c[0])?.checked);return`${b}/9?colori=${cols.join(',')}&page=${p('q9p')}&per_page=${p('q9pp')}`})(),
    10:`${b}/10?min_fornitori=${p('q10m')}&page=${p('q10p')}&per_page=${p('q10pp')}`,
  };
  return cases[ep];
}
async function qcall(ep){
  const url=qurl(ep);
  document.getElementById('qr'+ep).style.display='block';
  document.getElementById('qm'+ep).innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>';
  document.getElementById('qp'+ep).textContent='';
  const t=Date.now();
  try{
    const opts=TOK?{headers:{Authorization:'Bearer '+TOK}}:{};
    const res=await fetch(url,opts); const ms=Date.now()-t;
    const txt=await res.text(); let pretty=txt;
    try{pretty=JSON.stringify(JSON.parse(txt),null,2);}catch{}
    document.getElementById('qm'+ep).innerHTML=`<span class="${res.ok?'ok':'er'}">${res.status} ${res.statusText}</span><span>${ms}ms</span><code style="font-size:.7rem">${url}</code>`;
    document.getElementById('qp'+ep).textContent=pretty;
  }catch(e){
    document.getElementById('qm'+ep).innerHTML='<span class="er">Errore di rete</span>';
    document.getElementById('qp'+ep).textContent=e.message+'\n\nAvvia Slim:\n  php -S localhost:8080 -t public/';
  }
}

document.addEventListener('keydown',e=>{if(e.key==='Enter'&&document.getElementById('loginPage').style.display!=='none')doLogin();});
</script>
</body>
</html>
