<?php
session_start();
$user = $_SESSION['user'] ?? null;
if (!$user) {
  header('Location: index.php');
  exit;
}
$isAdmin = ($user['role'] ?? '') === 'admin';
$cols    = $isAdmin ? 5 : 4;
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>MealAPP — Piano Alimentare</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
          colors: {
            ink:      "#2C1F14",
            muted:    "#9C8E82",
            line:     "#EAE0D5",
            surface:  "#F8F4EE",
            accent:   "#B8CDB2",
            accent2:  "#A8BDD0",
            warn:     "#D4A44A",
            danger:   "#C47A6A",
            success:  "#7DAA8A",
          },
          boxShadow: {
            warm:    "0 2px 12px 0 rgba(44,31,20,0.07)",
            "warm-lg": "0 8px 32px 0 rgba(44,31,20,0.12)",
          }
        }
      }
    }
  </script>
</head>
<body class="bg-gradient-to-br from-surface to-[#EDE3D6] min-h-screen p-4 lg:p-8 text-ink font-sans antialiased">

<div class="max-w-4xl mx-auto space-y-4">

  <!-- HEADER -->
  <div class="rounded-3xl border border-line bg-white p-5 shadow-warm flex items-start justify-between gap-4 flex-wrap">
    <div>
      <div class="flex items-center gap-2">
        <span class="text-xl">🍽️</span>
        <div class="text-lg font-bold text-ink">Piano Alimentare</div>
      </div>
      <div id="subtitle" class="text-sm text-muted mt-1">Caricamento…</div>
    </div>
    <div class="flex gap-2 flex-wrap items-center">
      <div id="searchWrap" class="hidden">
        <input id="searchBox" type="text"
          class="rounded-2xl border border-line px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-accent/40 bg-surface/50 placeholder:text-muted text-ink"
          placeholder="Cerca…"/>
      </div>
      <?php if($isAdmin): ?>
      <button id="btnAddRow"
        class="rounded-2xl bg-ink text-white px-4 py-2.5 hover:opacity-90 text-sm font-semibold transition-opacity">
        + Aggiungi riga
      </button>
      <button id="btnSave"
        class="rounded-2xl border border-line px-4 py-2.5 hover:bg-surface text-sm transition-colors text-ink">
        Salva modifiche
      </button>
      <?php endif; ?>
      <button onclick="window.close()"
        class="rounded-2xl border border-line px-4 py-2.5 hover:bg-surface text-sm transition-colors text-muted">
        Chiudi
      </button>
    </div>
  </div>

  <!-- STATUS MESSAGGIO -->
  <div id="saveStatus" class="hidden rounded-3xl border border-line bg-white p-4 text-sm"></div>

  <!-- TABELLA -->
  <div class="rounded-3xl border border-line bg-white shadow-warm overflow-hidden">
    <table class="w-full border-collapse">
      <thead>
        <tr class="bg-surface border-b border-line">
          <th class="text-left text-[11px] font-semibold text-muted uppercase tracking-wide px-4 py-3 w-10">#</th>
          <th class="text-left text-[11px] font-semibold text-muted uppercase tracking-wide px-4 py-3">Pranzo</th>
          <th class="text-left text-[11px] font-semibold text-muted uppercase tracking-wide px-4 py-3">Cena</th>
          <th class="text-left text-[11px] font-semibold text-muted uppercase tracking-wide px-4 py-3 w-32">Stagione</th>
          <?php if($isAdmin): ?>
          <th class="w-10 px-3 py-3"></th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody id="tbody">
        <tr>
          <td colspan="<?= $cols ?>" class="px-4 py-10 text-sm text-gray-400 text-center">
            Caricamento…
          </td>
        </tr>
      </tbody>
    </table>
  </div>

</div><!-- /max-w -->

<script>
const IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;
const COLS     = <?= $cols ?>;
let csrfToken  = '';
let allPairs   = [];   // tutti i dati
let filtered   = [];   // filtrati per ricerca (solo per render)

/* ---------- INIT ---------- */
async function init() {
  const me = await fetch('api/me.php', {cache:'no-store'}).then(r=>r.json()).catch(()=>null);
  if (!me || !me.logged) { window.location = 'index.php'; return; }
  csrfToken = me.csrf_token || '';

  const j = await fetch('api/pairs.php', {cache:'no-store'}).then(r=>r.json()).catch(()=>null);
  if (!j || !j.ok) { showError('Errore caricamento coppie'); return; }

  allPairs = j.pairs || [];
  filtered = allPairs;

  document.getElementById('searchWrap')?.classList.remove('hidden');
  updateSubtitle();
  renderTable();
}

/* ---------- SUBTITLE ---------- */
function updateSubtitle() {
  const el = document.getElementById('subtitle');
  if (!el) return;
  const tot = allPairs.length;
  const vis = filtered.length;
  if (!tot) {
    el.textContent = 'Nessuna coppia caricata';
    el.className = 'text-sm text-danger mt-1';
  } else if (vis < tot) {
    el.textContent = `${vis} di ${tot} coppie nel Piano Alimentare` + (IS_ADMIN ? ' — clicca su una cella per modificarla' : '');
    el.className = 'text-sm text-muted mt-1';
  } else {
    el.textContent = `${tot} coppie nel Piano Alimentare` + (IS_ADMIN ? ' — clicca su una cella per modificarla' : '');
    el.className = 'text-sm text-muted mt-1';
  }
}

/* ---------- RENDER TABLE ---------- */
function renderTable() {
  const tbody = document.getElementById('tbody');
  if (!filtered.length) {
    tbody.innerHTML = `<tr><td colspan="${COLS}" class="px-4 py-10 text-sm text-muted text-center">
      ${allPairs.length ? 'Nessun risultato per la ricerca.' : IS_ADMIN ? 'Nessuna coppia. Aggiungi righe o carica un Piano Alimentare.' : 'Nessuna coppia disponibile.'}
    </td></tr>`;
    return;
  }

  tbody.innerHTML = '';
  filtered.forEach((p, visIdx) => {
    // trova l'indice reale in allPairs
    const realIdx = allPairs.indexOf(p);

    const tr = document.createElement('tr');
    tr.className = (visIdx % 2 === 0 ? 'border-b border-line' : 'border-b border-line bg-surface/40')
      + ' transition-colors';

    // N.
    const tdN = document.createElement('td');
    tdN.className = 'px-4 py-3 text-xs text-muted tabular-nums';
    tdN.textContent = realIdx + 1;
    tr.appendChild(tdN);

    // Pranzo
    tr.appendChild(makeCell(p, 'lunch', realIdx));
    // Cena
    tr.appendChild(makeCell(p, 'dinner', realIdx));
    // Stagione
    tr.appendChild(makeSeasonCell(p, realIdx));

    // Delete (admin)
    if (IS_ADMIN) {
      const tdDel = document.createElement('td');
      tdDel.className = 'px-3 py-3 text-center';
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'rounded-full border border-line px-2 py-1 hover:bg-danger/10 text-danger text-xs transition-colors';
      btn.title = 'Elimina riga';
      btn.textContent = '✕';
      btn.onclick = () => {
        allPairs.splice(realIdx, 1);
        applySearch();
      };
      tdDel.appendChild(btn);
      tr.appendChild(tdDel);
    }

    tbody.appendChild(tr);
  });
}

/* ---------- CELL ---------- */
function makeCell(pair, field, realIdx) {
  const td = document.createElement('td');
  td.className = 'px-4 py-3 text-sm text-ink';

  if (!IS_ADMIN) {
    td.textContent = pair[field] || '—';
    return td;
  }

  const span = document.createElement('span');
  span.textContent = pair[field] || '';
  span.className = 'cursor-text rounded px-1 -mx-1 hover:bg-accent/20 block min-h-[1.25rem] min-w-[60px]';
  span.title = 'Clicca per modificare';

  span.onclick = () => {
    const input = document.createElement('input');
    input.type  = 'text';
    input.value = pair[field] || '';
    input.className = 'w-full rounded border border-line px-2 py-1 text-sm outline-none focus:ring-2 focus:ring-accent/40 bg-surface/50 text-ink';

    td.innerHTML = '';
    td.appendChild(input);
    input.focus();
    input.select();

    const commit = () => {
      const val = input.value.trim();
      allPairs[realIdx][field] = val;
      td.innerHTML = '';
      td.appendChild(span);
      span.textContent = val || '';
      updateSubtitle();
    };

    input.onblur    = commit;
    input.onkeydown = (e) => {
      if (e.key === 'Enter')  { e.preventDefault(); commit(); }
      if (e.key === 'Escape') { td.innerHTML = ''; td.appendChild(span); }
      if (e.key === 'Tab')    { e.preventDefault(); commit();
        // sposta focus alla cella successiva
        const nextField = field === 'lunch' ? 'dinner' : 'lunch';
        const nextRealIdx = field === 'dinner' ? realIdx + 1 : realIdx;
        if (nextRealIdx < allPairs.length) {
          setTimeout(() => {
            const spans = document.querySelectorAll('#tbody span');
            // trova lo span giusto: ogni riga ha 2 span (lunch, dinner)
            const spanIdx = nextRealIdx * 2 + (nextField === 'lunch' ? 0 : 1);
            spans[spanIdx]?.click();
          }, 10);
        }
      }
    };
  };

  td.appendChild(span);
  return td;
}

/* ---------- SEASON CELL ---------- */
const SEASON_LABELS = { '': 'Tutto l\'anno', 'PRI': '🌸 Primavera', 'EST': '☀️ Estate', 'AUT': '🍂 Autunno', 'INV': '❄️ Inverno' };
const SEASON_BADGE  = { '': 'text-muted', 'PRI': 'text-success', 'EST': 'text-warn', 'AUT': 'text-danger', 'INV': 'text-accent2' };

function makeSeasonCell(pair, realIdx) {
  const td = document.createElement('td');
  td.className = 'px-4 py-3 text-sm';

  const val = pair.season || '';

  if (!IS_ADMIN) {
    td.textContent = SEASON_LABELS[val] || val || '—';
    td.className += ' ' + (SEASON_BADGE[val] || 'text-muted');
    return td;
  }

  const sel = document.createElement('select');
  sel.className = 'rounded-xl border border-line px-2 py-1 text-xs bg-surface/50 text-ink outline-none focus:ring-2 focus:ring-accent/40 cursor-pointer';
  [['', 'Tutto l\'anno'], ['PRI', '🌸 Primavera'], ['EST', '☀️ Estate'], ['AUT', '🍂 Autunno'], ['INV', '❄️ Inverno']].forEach(([v, label]) => {
    const opt = document.createElement('option');
    opt.value = v;
    opt.textContent = label;
    if (v === val) opt.selected = true;
    sel.appendChild(opt);
  });
  sel.onchange = () => {
    allPairs[realIdx].season = sel.value;
    updateSubtitle();
  };
  td.appendChild(sel);
  return td;
}

/* ---------- SEARCH ---------- */
function applySearch() {
  const q = (document.getElementById('searchBox')?.value || '').toLowerCase().trim();
  filtered = q
    ? allPairs.filter(p =>
        (p.lunch  || '').toLowerCase().includes(q) ||
        (p.dinner || '').toLowerCase().includes(q)
      )
    : allPairs;
  updateSubtitle();
  renderTable();
}

/* ---------- SAVE ---------- */
async function savePairs() {
  const statusEl = document.getElementById('saveStatus');
  statusEl.textContent = 'Salvataggio in corso…';
  statusEl.className = 'rounded-3xl border border-line bg-white p-4 text-sm text-muted';
  statusEl.classList.remove('hidden');

  const valid = allPairs.filter(p => (p.lunch||'').trim() || (p.dinner||'').trim());
  if (!valid.length) {
    statusEl.textContent = 'Nessuna coppia valida da salvare.';
    statusEl.className = 'rounded-3xl border border-danger/30 bg-danger/5 p-4 text-sm text-danger';
    return;
  }

  const r = await fetch('api/pairs_save.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
    body: JSON.stringify({ pairs: valid })
  }).then(r => r.json()).catch(() => null);

  if (!r || !r.ok) {
    statusEl.textContent = 'Errore salvataggio: ' + (r?.error || 'sconosciuto');
    statusEl.className = 'rounded-3xl border border-danger/30 bg-danger/5 p-4 text-sm text-danger';
    return;
  }

  allPairs = valid;
  applySearch();
  statusEl.textContent = `Salvato — ${r.count} coppie.`;
  statusEl.className = 'rounded-3xl border border-success/30 bg-success/10 p-4 text-sm text-success font-medium';
  setTimeout(() => statusEl.classList.add('hidden'), 4000);
}

/* ---------- ADD ROW ---------- */
function addRow() {
  allPairs.push({ lunch: '', dinner: '', season: '' });
  // Forza ricerca vuota per vedere tutte le righe
  if (document.getElementById('searchBox')) {
    document.getElementById('searchBox').value = '';
  }
  applySearch();
  // Scrolla in fondo e apre l'edit sull'ultima cella pranzo
  requestAnimationFrame(() => {
    const spans = document.querySelectorAll('#tbody span');
    const last  = spans[spans.length - 2]; // penultima = pranzo dell'ultima riga
    if (last) {
      last.scrollIntoView({ behavior: 'smooth', block: 'center' });
      setTimeout(() => last.click(), 150);
    }
  });
}

/* ---------- EVENTS ---------- */
document.getElementById('searchBox')?.addEventListener('input', applySearch);

if (IS_ADMIN) {
  document.getElementById('btnAddRow')?.addEventListener('click', addRow);
  document.getElementById('btnSave')?.addEventListener('click', savePairs);
}

/* ---------- START ---------- */
function showError(msg) {
  document.getElementById('tbody').innerHTML =
    `<tr><td colspan="${COLS}" class="px-4 py-10 text-sm text-danger text-center font-medium">${msg}</td></tr>`;
  document.getElementById('subtitle').textContent = msg;
}

init();
</script>
</body>
</html>
