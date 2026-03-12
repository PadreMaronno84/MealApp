const $ = (id)=>document.getElementById(id);

/* =========================
   API
   ========================= */
function handleSessionExpired(){
  // Mostra messaggio e ricarica la pagina (tornerà al login)
  const wrap = $("loading");
  if(wrap){
    $("loadingTitle").textContent = "Sessione scaduta";
    $("loadingMsg").textContent   = "Stai per essere reindirizzato al login…";
    wrap.classList.remove("hidden");
  }
  setTimeout(()=>{ window.location.reload(); }, 1500);
}

async function apiGet(url){
  const r = await fetch(url, {cache:"no-store"});
  const j = await r.json().catch(()=>null);
  if(!j) throw new Error("bad_json");
  if(j.error === "not_logged"){ handleSessionExpired(); throw new Error("not_logged"); }
  return j;
}
async function apiPost(url, body){
  const r = await fetch(url, {
    method:"POST",
    headers: {
      "Content-Type":"application/json",
      "X-CSRF-Token": state.csrfToken
    },
    body: JSON.stringify(body)
  });
  const j = await r.json().catch(()=>null);
  if(!j) throw new Error("bad_json");
  if(j.error === "not_logged"){ handleSessionExpired(); throw new Error("not_logged"); }
  return j;
}


/* =========================
   UTILS
   ========================= */
function addDays(date, n){ const d=new Date(date); d.setDate(d.getDate()+n); return d; }
function fmtISO(d){
  const yyyy=d.getFullYear();
  const mm=String(d.getMonth()+1).padStart(2,'0');
  const dd=String(d.getDate()).padStart(2,'0');
  return `${yyyy}-${mm}-${dd}`;
}
function fmtDMY(d){
  const yyyy=d.getFullYear();
  const mm=String(d.getMonth()+1).padStart(2,'0');
  const dd=String(d.getDate()).padStart(2,'0');
  return `${dd}-${mm}-${yyyy}`;
}
const DOW = ["Lun","Mar","Mer","Gio","Ven","Sab","Dom"];
const SEASON_NAMES = {PRI:'🌸 Primavera', EST:'☀️ Estate', AUT:'🍂 Autunno', INV:'❄️ Inverno'};
function getCurrentSeason(date){
  const m = (date || new Date()).getMonth() + 1; // 1-12
  if(m >= 3 && m <= 5) return 'PRI';
  if(m >= 6 && m <= 8) return 'EST';
  if(m >= 9 && m <= 11) return 'AUT';
  return 'INV';
}
function filterPoolBySeason(pool, season){
  const seasonal = pool.filter(p => !p.season || p.season === season);
  return seasonal.length > 0 ? seasonal : pool; // fallback al pool completo
}
const MONTHS_IT = ["Gennaio","Febbraio","Marzo","Aprile","Maggio","Giugno","Luglio","Agosto","Settembre","Ottobre","Novembre","Dicembre"];
function fmtHumanDate(d){
  const dd = String(d.getDate()).padStart(2,'0');
  const m  = MONTHS_IT[d.getMonth()];
  const y  = d.getFullYear();
  return `${dd} ${m} ${y}`;
}
function makePlanLabel(startISO, weeksLen){
  const start = new Date(startISO + "T00:00:00");
  const end = addDays(start, (weeksLen*7)-1);
  return `DAL ${fmtHumanDate(start)} AL ${fmtHumanDate(end)}`;
}
function show(el, yes){
  if(!el) return;
  if(yes) el.classList.remove("hidden");
  else el.classList.add("hidden");
}
function loading(on, title="Caricamento", msg="…"){
  const w = $("loading");
  if(!w) return;
  if(on){
    $("loadingTitle").textContent = title;
    $("loadingMsg").textContent = msg;
    w.classList.remove("hidden");
  } else {
    w.classList.add("hidden");
  }
}
function toast(type, msg, title){
  const wrap = $("toast");
  const dot = $("toastDot");
  const t = $("toastTitle");
  const m = $("toastMsg");
  if(!wrap) return;

  if(type === "ok"){ dot.className="mt-1 h-2.5 w-2.5 rounded-full bg-success shrink-0"; t.textContent = title || "OK"; }
  if(type === "warn"){ dot.className="mt-1 h-2.5 w-2.5 rounded-full bg-warn shrink-0"; t.textContent = title || "Attenzione"; }
  if(type === "err"){ dot.className="mt-1 h-2.5 w-2.5 rounded-full bg-danger shrink-0"; t.textContent = title || "Errore"; }

  m.textContent = msg;
  wrap.classList.remove("hidden");

  clearTimeout(toast._t);
  toast._t = setTimeout(()=>wrap.classList.add("hidden"), 3200);
}
function modal({title, desc, body, okText="OK", cancelText="Annulla", showCancel=true}){
  $("modalTitle").textContent = title || "";
  $("modalDesc").textContent = desc || "";
  $("modalBody").innerHTML = body || "";
  $("modalOk").textContent = okText;
  $("modalCancel").textContent = cancelText;
  show($("modalCancel"), showCancel);
  $("modalWrap").classList.remove("hidden");

  return new Promise((resolve)=>{
    const close = (v)=>{
      $("modalWrap").classList.add("hidden");
      $("modalOk").onclick = null;
      $("modalCancel").onclick = null;
      $("modalClose").onclick = null;
      resolve(v);
    };
    $("modalOk").onclick = ()=>close(true);
    $("modalCancel").onclick = ()=>close(false);
    $("modalClose").onclick = ()=>close(false);
  });
}
function openDrawer(){ $("sidebarDrawer")?.classList.remove("hidden"); }
function closeDrawer(){ $("sidebarDrawer")?.classList.add("hidden"); }
function escapeHtml(s){
  return String(s||"")
    .replaceAll("&","&amp;")
    .replaceAll("<","&lt;")
    .replaceAll(">","&gt;")
    .replaceAll('"',"&quot;")
    .replaceAll("'","&#039;");
}
function shuffleArray(arr){
  const a = [...arr];
  for(let i = a.length - 1; i > 0; i--){
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
}

/* =========================
   TOOLTIP (hover desktop + tap mobile)
   ========================= */
const TIP_STATE = { open:false, anchor:null, hideTimer:null };
function isTouch(){
  return window.matchMedia && window.matchMedia("(pointer: coarse)").matches;
}
function tipShowNear(el, title, body){
  const wrap = $("tip");
  const t = $("tipTitle");
  const b = $("tipBody");
  if(!wrap || !t || !b) return;

  t.textContent = title || "";
  b.textContent = body || "";

  wrap.classList.remove("hidden");
  wrap.style.left = "0px";
  wrap.style.top = "0px";

  const r = el.getBoundingClientRect();
  const pad = 10;

  let x = r.left + window.scrollX;
  let y = r.bottom + window.scrollY + 10;

  requestAnimationFrame(()=>{
    const tr = wrap.getBoundingClientRect();
    const vw = window.innerWidth;
    const vh = window.innerHeight;

    if(x + tr.width + pad > vw) x = vw - tr.width - pad;
    if(x < pad) x = pad;

    if(y + tr.height + pad > (window.scrollY + vh)) {
      y = r.top + window.scrollY - tr.height - 10;
    }
    if(y < window.scrollY + pad) y = window.scrollY + pad;

    wrap.style.left = `${x}px`;
    wrap.style.top = `${y}px`;
  });

  TIP_STATE.open = true;
  TIP_STATE.anchor = el;
}
function tipHide(){
  const wrap = $("tip");
  if(!wrap) return;
  wrap.classList.add("hidden");
  TIP_STATE.open = false;
  TIP_STATE.anchor = null;
}
function bindTip(el, title, body){
  if(!el) return;

  el.addEventListener("mouseenter", ()=>{
    if(isTouch()) return;
    clearTimeout(TIP_STATE.hideTimer);
    tipShowNear(el, title, body);
  });

  el.addEventListener("mouseleave", ()=>{
    if(isTouch()) return;
    TIP_STATE.hideTimer = setTimeout(()=>tipHide(), 120);
  });

  el.addEventListener("click", (e)=>{
    if(!isTouch()) return;
    e.preventDefault();
    e.stopPropagation();
    if(TIP_STATE.open && TIP_STATE.anchor === el) tipHide();
    else tipShowNear(el, title, body);
  });
}
function tipGlobalCloseHandlers(){
  document.addEventListener("click", (e)=>{
    if(!TIP_STATE.open) return;
    const wrap = $("tip");
    if(wrap && (wrap.contains(e.target) || (TIP_STATE.anchor && TIP_STATE.anchor.contains(e.target)))) return;
    tipHide();
  }, true);

  document.addEventListener("keydown", (e)=>{
    if(e.key === "Escape") tipHide();
  });

  window.addEventListener("scroll", ()=>{
    if(TIP_STATE.open && isTouch()) tipHide();
  }, {passive:true});
}
function pillIcon(icon, text, extraCls="", tipTitle="", tipBody=""){
  return `
    <button type="button"
      class="js-tip inline-flex items-center gap-1.5 text-[11px] px-2 py-1 rounded-full border bg-surface/80 ${extraCls}"
      data-tip-title="${escapeHtml(tipTitle)}"
      data-tip-body="${escapeHtml(tipBody)}">
      <span class="text-[12px]">${icon}</span>
      <span>${text}</span>
    </button>
  `;
}
function bindAllTips(container){
  const els = container.querySelectorAll(".js-tip");
  els.forEach(el=>{
    const t = el.getAttribute("data-tip-title") || "";
    const b = el.getAttribute("data-tip-body") || "";
    bindTip(el, t, b);
  });
}

/* =========================
   STATE
   ========================= */
const state = {
  me: null,
  pool: [],
  plan: null,
  activeWeek: 0,
  saved: [],
  savedFilter: "",
  settings: null,
  view: "plans",
  csrfToken: "",
  dirty: false
};

// Helper ruoli
function isAdminOrAbove(){ return state.me?.role === "admin" || state.me?.role === "superadmin"; }
function isSuperAdmin(){ return state.me?.role === "superadmin"; }

/* =========================
   PASSWORD TOGGLE (occhio)
   ========================= */
function initPasswordToggles(){
  document.querySelectorAll('input[type="password"]').forEach(inp => {
    // Wrappa l'input in un div relativo
    const wrap = document.createElement('div');
    wrap.className = 'relative';
    inp.parentNode.insertBefore(wrap, inp);
    wrap.appendChild(inp);

    // Spazio a destra per il bottone
    inp.classList.add('pr-12');

    // Crea il bottone occhio
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-ink transition-colors px-1 py-1 text-base leading-none select-none';
    btn.title = 'Mostra/nascondi password';
    btn.textContent = '👁';
    btn.addEventListener('click', () => {
      const show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      btn.textContent = show ? '🙈' : '👁';
    });
    wrap.appendChild(btn);
  });
}

/* =========================
   AUTH
   ========================= */
async function refreshMe(){
  const j = await apiGet("api/me.php");
  if(!j.ok) return null;
  if(!j.logged){ state.me=null; return null; }
  state.me = j.user;
  state.csrfToken = j.csrf_token || "";
  return state.me;
}

/* =========================
   LOGIN / REGISTER TABS
   ========================= */
function setLoginTab(tab){
  const loginForm    = $("loginFormSection");
  const registerForm = $("registerFormSection");
  const btnLogin     = $("btnTabLogin");
  const btnReg       = $("btnTabRegister");
  if(!loginForm || !registerForm) return;

  const activeTab   = "flex-1 py-3.5 text-sm font-semibold text-ink border-b-2 border-ink transition-colors";
  const inactiveTab = "flex-1 py-3.5 text-sm font-medium text-muted border-b-2 border-transparent hover:text-ink transition-colors";

  if(tab === "login"){
    show(loginForm, true);
    show(registerForm, false);
    if(btnLogin)  btnLogin.className  = activeTab;
    if(btnReg)    btnReg.className    = inactiveTab;
  } else {
    show(loginForm, false);
    show(registerForm, true);
    if(btnLogin)  btnLogin.className  = inactiveTab;
    if(btnReg)    btnReg.className    = activeTab;
  }
}

async function login(){
  const u = $("loginUser").value.trim();
  const p = $("loginPass").value;

  $("loginStatus").textContent = "";

  if(!u || !p){
    $("loginStatus").textContent = "Inserisci username e password";
    return;
  }

  loading(true, "Login", "Verifico credenziali…");

  const params = new URLSearchParams();
  params.append("username", u);
  params.append("password", p);

  let response;

  try{
    const r = await fetch("api/login.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      },
      body: params.toString()
    });

    response = await r.json();
  }catch(e){
    loading(false);
    $("loginStatus").textContent = "Errore di connessione";
    return;
  }

  loading(false);

  if(!response.ok){
    if(response.error === "rate_limited"){
      $("loginStatus").textContent = "Troppi tentativi. Riprova tra un minuto.";
    } else {
      $("loginStatus").textContent = "Credenziali errate";
    }
    return;
  }

  await boot();
}

async function logout(){
  loading(true, "Logout", "Chiudo sessione…");
  await apiGet("api/logout.php");
  loading(false);
  location.reload();
}

async function register(){
  const username    = ($("regUsername")?.value    || "").trim();
  const password    = ($("regPassword")?.value    || "");
  const confirm     = ($("regConfirm")?.value     || "");
  const invite_code = ($("regInviteCode")?.value  || "").trim().toUpperCase();
  const status      = $("registerStatus");

  const setStatus = (msg, isErr=true)=>{
    if(!status) return;
    status.textContent = msg;
    status.className   = isErr ? "text-sm text-danger min-h-[18px]" : "text-sm text-success font-medium min-h-[18px]";
  };

  setStatus("");

  if(!username){ setStatus("Inserisci un username"); return; }
  if(!/^[a-zA-Z0-9._-]{3,30}$/.test(username)){ setStatus("Username non valido (3–30 caratteri, lettere/numeri/._-)"); return; }
  if(password.length < 8){ setStatus("La password deve avere almeno 8 caratteri"); return; }
  if(password !== confirm){ setStatus("Le password non coincidono"); return; }
  if(!invite_code){ setStatus("Inserisci il codice invito del gruppo"); return; }

  loading(true, "Registrazione", "Creo il tuo account…");
  let j;
  try {
    const r = await fetch("api/register.php", {
      method: "POST",
      headers: {"Content-Type":"application/json"},
      body: JSON.stringify({username, password, invite_code})
    });
    j = await r.json();
  } catch(e) {
    loading(false);
    setStatus("Errore di connessione");
    return;
  }
  loading(false);

  if(!j || !j.ok){
    let msg = j?.error || "errore";
    if(msg === "bad_username")        msg = "Username non valido (3–30 caratteri, lettere/numeri/._-)";
    if(msg === "password_too_short")  msg = "La password deve avere almeno 8 caratteri";
    if(msg === "missing_invite_code") msg = "Inserisci il codice invito del gruppo";
    if(msg === "invalid_invite_code") msg = "Codice invito non valido o non trovato";
    if(msg === "username_exists")     msg = "Username già in uso, scegline un altro";
    if(msg === "rate_limited")        msg = "Troppi tentativi. Riprova tra un'ora.";
    setStatus(msg);
    return;
  }

  // Successo
  setStatus(`Account creato! Ora puoi accedere.`, false);
  if($("regUsername"))   $("regUsername").value   = "";
  if($("regPassword"))   $("regPassword").value   = "";
  if($("regConfirm"))    $("regConfirm").value     = "";
  if($("regInviteCode")) $("regInviteCode").value  = "";

  setTimeout(()=>setLoginTab("login"), 2000);
}

/* =========================
   SETTINGS
   ========================= */
function defaultSettings(){
  return {
    rules: {
      pizza: { enabled:true, dayIndex:5, meal:"dinner", text:"Pizza" },
      freeMeal: { enabled:true, dayIndex:6, meal:"lunch", text:"LIBERO" }
    }
  };
}
async function loadSettings(){
  const j = await apiGet("api/settings_get.php");
  if(j.ok && j.settings) state.settings = j.settings;
  else state.settings = defaultSettings();
}
function getSettings(){
  return state.settings || defaultSettings();
}
function rulesSummaryText(){
  const s = getSettings();
  const r = s.rules || {};
  const p = r.pizza || {};
  const f = r.freeMeal || {};

  const parts = [];
  if(p.enabled) parts.push(`🍕 Pizza: ${DOW[p.dayIndex] || "?"} (${p.meal==="lunch"?"pranzo":"cena"}) → ${p.text||"Pizza"}`);
  else parts.push(`🍕 Pizza: disattivata`);

  if(f.enabled) parts.push(`🟡 Libero: ${DOW[f.dayIndex] || "?"} (${f.meal==="lunch"?"pranzo":"cena"}) → ${f.text||"LIBERO"}`);
  else parts.push(`🟡 Libero: disattivato`);

  return parts.join("\n");
}
function renderSettings(){
  const s = getSettings();
  const r = s.rules || {};
  const pizza = r.pizza || {};
  const free = r.freeMeal || {};

  const isAdmin = isAdminOrAbove();

  if($("pizzaEnabled")) $("pizzaEnabled").checked = !!pizza.enabled;
  if($("pizzaDay")) $("pizzaDay").value = String(Number.isFinite(pizza.dayIndex)?pizza.dayIndex:5);
  if($("pizzaMeal")) $("pizzaMeal").value = (pizza.meal === "lunch") ? "lunch" : "dinner";
  if($("pizzaText")) $("pizzaText").value = pizza.text ?? "Pizza";

  if($("freeEnabled")) $("freeEnabled").checked = !!free.enabled;
  if($("freeDay")) $("freeDay").value = String(Number.isFinite(free.dayIndex)?free.dayIndex:6);
  if($("freeMeal")) $("freeMeal").value = (free.meal === "dinner") ? "dinner" : "lunch";
  if($("freeText")) $("freeText").value = free.text ?? "LIBERO";

  const lock = !isAdmin;
  ["pizzaEnabled","pizzaDay","pizzaMeal","pizzaText","freeEnabled","freeDay","freeMeal","freeText"].forEach(id=>{
    const el = $(id);
    if(!el) return;
    el.disabled = lock;
    if(lock){
      el.classList.add("opacity-70","cursor-not-allowed");
    } else {
      el.classList.remove("opacity-70","cursor-not-allowed");
    }
  });

  show($("btnSaveSettings"), isAdmin);

  if($("settingsSummary")) $("settingsSummary").textContent = rulesSummaryText();
  if($("settingsStatus")) $("settingsStatus").textContent = "—";

  // Codice invito (solo admin/superadmin)
  const inviteSection = $("inviteCodeSection");
  const inviteDisplay = $("inviteCodeDisplay");
  if(inviteSection) show(inviteSection, isAdmin);
  if(isAdmin && inviteDisplay){
    inviteDisplay.textContent = s.invite_code || "—";
  }

  // Selettore ruolo nella creazione utente: solo superadmin può creare admin
  const roleSelect = $("newUserRole");
  if(roleSelect){
    const adminOpt = roleSelect.querySelector('option[value="admin"]');
    if(adminOpt) adminOpt.hidden = !isSuperAdmin();
  }

  renderCsvStatus();
}
async function saveSettings(){
  if(!isAdminOrAbove()) return;

  const s = getSettings();
  s.rules = s.rules || {};

  s.rules.pizza = {
    enabled: !!$("pizzaEnabled")?.checked,
    dayIndex: parseInt($("pizzaDay")?.value ?? "5", 10),
    meal: ($("pizzaMeal")?.value === "lunch") ? "lunch" : "dinner",
    text: ($("pizzaText")?.value || "").trim() || "Pizza"
  };

  s.rules.freeMeal = {
    enabled: !!$("freeEnabled")?.checked,
    dayIndex: parseInt($("freeDay")?.value ?? "6", 10),
    meal: ($("freeMeal")?.value === "dinner") ? "dinner" : "lunch",
    text: ($("freeText")?.value || "").trim() || "LIBERO"
  };

  ["pizza","freeMeal"].forEach(k=>{
    const rr = s.rules[k];
    rr.dayIndex = Math.max(0, Math.min(6, parseInt(rr.dayIndex,10)||0));
    rr.meal = (rr.meal === "lunch") ? "lunch" : "dinner";
  });

  // invite_code: preserva quello in state (non editabile dal form)
  s.invite_code = state.settings?.invite_code || "";

  loading(true,"Salvo impostazioni","Scrivo le regole…");
  const j = await apiPost("api/settings_save.php", {settings:s});
  loading(false);

  if(!j.ok){
    toast("err", j.error || "Errore salvataggio");
    if($("settingsStatus")) $("settingsStatus").textContent = "Errore salvataggio";
    return;
  }

  state.settings = s;
  renderSettings();
  renderPlan();
  toast("ok","Impostazioni salvate");
  if($("settingsStatus")) $("settingsStatus").textContent = "Salvato.";
}

/* =========================
   VIEW SWITCH
   ========================= */
function setView(view){
  state.view = view;

  show($("plansView"),      view === "plans");
  show($("settingsView"),   view === "settings");
  show($("superadminView"), view === "superadmin");

  // La sidebar è sempre visibile; aggiorna solo lo stile attivo dei bottoni nav
  const CLS_ACTIVE   = "rounded-2xl px-3 py-2.5 border border-line bg-ink text-white font-semibold text-sm text-left transition-opacity hover:opacity-90";
  const CLS_INACTIVE = "rounded-2xl px-3 py-2.5 border border-line bg-white hover:bg-surface text-ink text-sm text-left transition-colors";

  const setBtn = (idPlans, idSet, idSup)=>{
    const bp = $(idPlans), bs = $(idSet), bsa = $(idSup);
    if(bp)  bp.className  = (view === "plans")      ? CLS_ACTIVE : CLS_INACTIVE;
    if(bs)  bs.className  = (view === "settings")   ? CLS_ACTIVE : CLS_INACTIVE;
    if(bsa) bsa.className = (view === "superadmin") ? CLS_ACTIVE : CLS_INACTIVE;
  };

  setBtn("btnNavPlans","btnNavSettings","btnNavSuperadmin");
  setBtn("btnNavPlansMobile","btnNavSettingsMobile","btnNavSuperadminMobile");

  if(view === "settings") renderSettings();
}

/* =========================
   DATA LOAD
   ========================= */
async function loadPairs(){
  const j = await apiGet("api/pairs.php");
  if(!j.ok) { toast("err","Errore lettura Piano Alimentare"); return; }
  state.pool = j.exists ? (j.pairs||[]) : [];
  if(!j.exists) toast("warn","Piano Alimentare non presente: caricalo nelle Impostazioni.");
  renderCsvStatus();
}
async function loadSaved(){
  loading(true, "Aggiorno", "Carico la lista dei piani…");
  const j = await apiGet("api/list.php");
  loading(false);
  state.saved = (j.ok && j.items) ? j.items : [];
  renderSavedLists();
}
async function loadGroupUsers(){
  if(!isAdminOrAbove()) return;
  const j = await apiGet("api/group_users.php");
  renderUsers(j.ok ? (j.items||[]) : []);
}

/* =========================
   ADMIN: CSV + USERS
   ========================= */
function renderCsvStatus(){
  const hasPool = !!(state.pool && state.pool.length);
  const text    = hasPool ? `Piano Alimentare attivo: ${state.pool.length} coppie pranzo+cena` : "Nessun Piano Alimentare caricato";
  const cls     = hasPool ? "text-xs text-success mt-1 font-medium" : "text-xs text-danger mt-1";

  [$("csvCurrentStatus"), $("csvCurrentStatusUser")].forEach(el=>{
    if(!el) return;
    el.textContent = text;
    el.className   = cls;
  });

  updateSeasonNote();
}

function updateSeasonNote(){
  const el = $("seasonNote");
  if(!el) return;
  const total = state.pool ? state.pool.length : 0;
  if(!total){ el.textContent = ''; return; }

  const season = getCurrentSeason(new Date());
  const seasonPool = filterPoolBySeason(state.pool, season);
  const hasSeasonData = state.pool.some(p => p.season);
  const isFallback = hasSeasonData && seasonPool.length === total;

  if(!hasSeasonData){
    el.textContent = `Stagione: ${SEASON_NAMES[season]} — ${total} coppie (nessun filtro stagionale nel Piano Alimentare)`;
  } else if(isFallback){
    el.textContent = `Stagione: ${SEASON_NAMES[season]} — nessuna coppia stagionale, userò tutto il pool (${total})`;
  } else {
    el.textContent = `Stagione: ${SEASON_NAMES[season]} — ${seasonPool.length} di ${total} coppie disponibili`;
  }
}

async function uploadCsv(inputId, statusId){
  const fileInput = $(inputId);
  const status = $(statusId);
  const f = fileInput?.files?.[0];
  if(!f){ if(status) status.textContent = "Seleziona un file Piano Alimentare (.csv)"; return; }

  // Modal scelta modalità
  const hasExisting = state.pool && state.pool.length > 0;
  const modeBody = `
    <div class="space-y-3">
      <label class="flex items-start gap-3 cursor-pointer p-3 rounded-2xl border border-line hover:bg-surface transition-colors">
        <input type="radio" name="uploadMode" value="append" ${hasExisting ? "checked" : ""} class="mt-0.5 accent-ink shrink-0">
        <div>
          <div class="text-sm font-semibold text-ink">Aggiungi in coda</div>
          <div class="text-xs text-muted mt-0.5">Le nuove coppie vengono aggiunte a quelle esistenti. I duplicati vengono ignorati automaticamente.</div>
        </div>
      </label>
      <label class="flex items-start gap-3 cursor-pointer p-3 rounded-2xl border border-line hover:bg-surface transition-colors">
        <input type="radio" name="uploadMode" value="replace" ${!hasExisting ? "checked" : ""} class="mt-0.5 accent-ink shrink-0">
        <div>
          <div class="text-sm font-semibold text-ink">Sostituisci tutto</div>
          <div class="text-xs text-muted mt-0.5">Il Piano Alimentare esistente viene sostituito interamente con il nuovo file.</div>
        </div>
      </label>
    </div>
  `;

  const confirmed = await modal({
    title:   "Carica Piano Alimentare",
    desc:    `File selezionato: ${escapeHtml(f.name)}`,
    body:    modeBody,
    okText:  "Carica",
    cancelText: "Annulla"
  });
  if(!confirmed) return;

  const mode = document.querySelector('input[name="uploadMode"]:checked')?.value || "replace";

  loading(true, "Carico Piano Alimentare", "Invio il file…");
  if(status) status.textContent = "Caricamento…";

  const fd = new FormData();
  fd.append("file", f);
  fd.append("mode", mode);
  const r = await fetch("api/upload_pairs.php", {
    method:"POST",
    headers: {"X-CSRF-Token": state.csrfToken},
    body:fd
  });
  const j = await r.json().catch(()=>null);

  loading(false);

  if(!j || !j.ok){
    if(status) status.textContent = "Errore caricamento";
    toast("err","Caricamento Piano Alimentare non riuscito");
    return;
  }

  let msg = "";
  if(j.mode === "append") msg = `Aggiunto: +${j.added} coppie (totale: ${j.total})`;
  else                    msg = `Piano Alimentare caricato: ${j.total} coppie`;

  if(status) status.textContent = msg;
  if(fileInput) fileInput.value = "";
  toast("ok", msg);
  await loadPairs();
}

async function createUser(){
  if(!isAdminOrAbove()) return;

  const un = ($("newUserName")?.value || "").trim();
  const pw = ($("newUserPass")?.value || "");
  const role = ($("newUserRole")?.value || "user");
  const status = $("userCreateStatus");

  if(status) status.textContent = "…";
  loading(true, "Creo utente", "Salvo credenziali…");
  const j = await apiPost("api/create_user.php", {username:un, password:pw, role});
  loading(false);

  if(!j.ok){
    let msg = j.error || "errore";
    if(msg==="bad_username")     msg="Username non valido";
    if(msg==="password_too_short") msg="Password troppo corta (min 8 caratteri)";
    if(msg==="username_exists")  msg="Username già esistente";
    if(msg==="forbidden_role")   msg="Solo il superadmin può creare utenti admin";
    if(status) status.textContent = "Errore: " + msg;
    toast("err", msg);
    return;
  }

  if(status) status.textContent = "Utente creato.";
  toast("ok","Utente creato");

  if($("newUserName")) $("newUserName").value="";
  if($("newUserPass")) $("newUserPass").value="";
  if($("newUserRole")) $("newUserRole").value="user";

  await loadGroupUsers();
}

async function deleteUser(username){
  const ok = await modal({
    title:"Elimina utente",
    desc:"Operazione definitiva",
    body:`Vuoi eliminare <b>${escapeHtml(username)}</b>?`,
    okText:"Elimina",
    cancelText:"Annulla"
  });
  if(!ok) return;

  loading(true, "Elimino utente", "Aggiorno lista…");
  const j = await apiPost("api/delete_user.php", {username});
  loading(false);

  if(!j.ok){
    toast("err", j.error || "Impossibile eliminare");
    return;
  }
  toast("ok","Utente eliminato");
  await loadGroupUsers();
}

/* =========================
   POOL / GENERATION / RULES
   ========================= */

function applyAlwaysOnRules(dayIndex, dayObj){
  // regole dal settings (per gruppo)
  const s = getSettings();
  const r = (s.rules || {});
  const pizza = r.pizza || {};
  const free = r.freeMeal || {};

  // IMPORTANTISSIMO: non devono uscire “pizza/libero” fuori dai giorni impostati
  // quindi li applichiamo SOLO nel dayIndex impostato e SOLO sul pasto impostato.

  if(pizza.enabled && Number(pizza.dayIndex) === dayIndex){
    if(pizza.meal === "lunch") dayObj.lunch = pizza.text || "Pizza";
    else dayObj.dinner = pizza.text || "Pizza";
  }
  if(free.enabled && Number(free.dayIndex) === dayIndex){
    if(free.meal === "lunch") dayObj.lunch = free.text || "LIBERO";
    else dayObj.dinner = free.text || "LIBERO";
  }
}

function generatePlan(){
  if(!isAdminOrAbove()) return;
  if(!state.pool.length){ toast("warn","Pool vuoto: carica CSV"); return; }

  const start = $("startMonday")?.value;
  if(!start){ toast("warn","Seleziona il lunedì"); return; }

  const startMonday = new Date(start + "T00:00:00");
  if(startMonday.getDay() !== 1){
    toast("warn","La data selezionata non è un lunedì");
    return;
  }

  const numWeeks = parseInt($("numWeeks")?.value || "1",10);

  loading(true, "Genero", "Creo la settimana…");

  // Filtra per stagione
  const season = getCurrentSeason(startMonday);
  const seasonPool = filterPoolBySeason(state.pool, season);
  const isFallback = seasonPool.length === state.pool.length && state.pool.some(p => p.season);

  // Deck shufflato ciclico: evita ripetizioni consecutive
  let deck = shuffleArray(seasonPool);
  let deckIdx = 0;
  function pickFromDeck(){
    if(deckIdx >= deck.length){
      deck = shuffleArray(seasonPool);
      deckIdx = 0;
    }
    return deck[deckIdx++];
  }

  const weeks = [];
  for(let w=0; w<numWeeks; w++){
    const weekStart = addDays(startMonday, w*7);
    const days = [];
    for(let d=0; d<7; d++){
      const p = pickFromDeck();
      const day = { lunch: p.lunch, dinner: p.dinner, locked: false };
      applyAlwaysOnRules(d, day);
      days.push(day);
    }
    weeks.push({ weekStartISO: fmtISO(weekStart), days });
  }

  const startISO = fmtISO(startMonday);
  state.plan = {
    id: "",
    startMondayISO: startISO,
    createdAt: new Date().toISOString(),
    weeks,
    displayLabel: makePlanLabel(startISO, weeks.length)
  };

  state.dirty = true;
  state.activeWeek = 0;
  renderPlan();
  const seasonInfo = `${SEASON_NAMES[season]} — ${seasonPool.length} coppie${isFallback ? ' (fallback: pool completo)' : ''}`;
  if($("status")) $("status").textContent = `Generato. ${seasonInfo}`;
  loading(false);
  toast("ok","Piano generato");
}

async function savePlan(){
  if(!isAdminOrAbove()) return;
  if(!state.plan){ toast("warn","Nessun piano da salvare"); return; }

  state.plan.id = "";
  state.plan.createdAt = new Date().toISOString();
  state.plan.displayLabel = makePlanLabel(state.plan.startMondayISO, state.plan.weeks.length);

  loading(true, "Salvo", "Controllo date e salvo…");
  const j = await apiPost("api/save.php", state.plan);
  loading(false);

  if(!j.ok){
    if(j.error === "date_overlap"){
      const d = j.details || {};
      await modal({
        title:"Date sovrapposte",
        desc:"Non posso salvare questo file",
        body:
          `<div class="text-sm">
            <div><b>Nuovo:</b> ${escapeHtml(d.newStart)} → ${escapeHtml(d.newEnd)}</div>
            <div class="mt-2"><b>Conflitto:</b> ${escapeHtml(d.conflictStart)} → ${escapeHtml(d.conflictEnd)}</div>
            <div class="mt-2"><b>File:</b> ${escapeHtml(d.conflictLabel || d.conflictId)}</div>
          </div>`,
        okText:"Ho capito",
        showCancel:false
      });
      return;
    }
    toast("err","Errore salvataggio");
    return;
  }

  state.dirty = false;
  toast("ok","Salvato");
  await loadSaved();
}

function clearView(){
  state.plan = null;
  state.dirty = false;
  state.activeWeek = 0;
  renderPlan();
  toast("ok","Vista azzerata");
}

async function removeWeek(weekIndex){
  if(!isAdminOrAbove()) return;
  if(!state.plan?.id){ toast("warn","Salva il piano prima di rimuovere settimane"); return; }
  if((state.plan.weeks?.length || 0) <= 1){ toast("warn","Non puoi rimuovere l'unica settimana"); return; }

  const isMid = weekIndex > 0 && weekIndex < state.plan.weeks.length - 1;
  const ok = await modal({
    title: "Rimuovi settimana",
    desc: `Settimana ${weekIndex + 1}`,
    body: isMid
      ? "Questa settimana è nel mezzo: il piano verrà diviso in due piani separati."
      : "Vuoi rimuovere questa settimana dal piano?",
    okText: "Rimuovi",
    cancelText: "Annulla"
  });
  if(!ok) return;

  loading(true, "Rimuovo settimana", "Aggiorno il piano…");
  const j = await apiPost("api/remove_week.php", {id: state.plan.id, weekIndex});
  loading(false);

  if(!j.ok){ toast("err", j.error || "Errore rimozione settimana"); return; }

  await loadSaved();

  if(j.mode === "updated"){
    const plan = await apiGet("api/load.php?id=" + encodeURIComponent(state.plan.id));
    state.plan = plan;
    state.activeWeek = Math.min(state.activeWeek, (state.plan.weeks?.length || 1) - 1);
    renderPlan();
    toast("ok", "Settimana rimossa");
  } else {
    // split: piano originale eliminato, creati 2 nuovi
    state.plan = null;
    state.activeWeek = 0;
    renderPlan();
    toast("ok", "Piano diviso in due parti — seleziona un piano dalla lista");
  }
}

/* =========================
   INVITE CODE
   ========================= */
async function regenInviteCode(){
  const ok = await modal({
    title: "Rigenera codice invito",
    desc:  "Il vecchio codice non funzionerà più",
    body:  "Vuoi generare un nuovo codice invito? Chi ha il vecchio codice non potrà più usarlo per registrarsi.",
    okText: "Rigenera",
    cancelText: "Annulla"
  });
  if(!ok) return;

  // Genera nuovo codice lato client (8 hex chars uppercase)
  const arr  = new Uint8Array(4);
  crypto.getRandomValues(arr);
  const code = Array.from(arr).map(b=>b.toString(16).padStart(2,'0')).join('').toUpperCase();

  if(!state.settings) state.settings = defaultSettings();
  state.settings.invite_code = code;

  loading(true, "Rigenero codice", "Salvo…");
  const j = await apiPost("api/settings_save.php", {settings: state.settings});
  loading(false);

  if(!j.ok){ toast("err", "Errore salvataggio"); return; }

  renderSettings();
  toast("ok", "Nuovo codice generato");
}

/* =========================
   CAMBIO PASSWORD
   ========================= */
async function changePassword(){
  const curr    = $("cpCurrent")?.value || "";
  const newp    = $("cpNew")?.value || "";
  const conf    = $("cpConfirm")?.value || "";
  const status  = $("cpStatus");

  if(!curr || !newp || !conf){ if(status) status.textContent = "Compila tutti i campi"; return; }
  if(newp !== conf){ if(status) status.textContent = "Le password non coincidono"; return; }
  if(newp.length < 8){ if(status) status.textContent = "Minimo 8 caratteri"; return; }

  loading(true, "Aggiorno password", "Salvo…");
  const j = await apiPost("api/change_password.php", {current_password: curr, new_password: newp});
  loading(false);

  if(!j.ok){
    let msg = j.error || "errore";
    if(msg === "wrong_password") msg = "Password attuale errata";
    if(msg === "password_too_short") msg = "Minimo 8 caratteri";
    if(status) status.textContent = "Errore: " + msg;
    toast("err", msg);
    return;
  }

  if($("cpCurrent")) $("cpCurrent").value = "";
  if($("cpNew"))     $("cpNew").value = "";
  if($("cpConfirm")) $("cpConfirm").value = "";
  if(status) status.textContent = "Password aggiornata.";
  toast("ok", "Password aggiornata");
}

/* =========================
   EXPORT PIANO
   ========================= */
function exportPlanText(){
  if(!state.plan){ toast("warn","Nessun piano da esportare"); return; }

  const lines = [];
  lines.push("PIANO PASTI: " + (state.plan.displayLabel || ""));
  lines.push("");

  state.plan.weeks.forEach((w, wi)=>{
    const ws = new Date(w.weekStartISO + "T00:00:00");
    lines.push(`── SETTIMANA ${wi+1} ── Dal ${fmtDMY(ws)} al ${fmtDMY(addDays(ws,6))}`);
    w.days.forEach((d, di)=>{
      const date = addDays(ws, di);
      lines.push(`${DOW[di]} ${fmtDMY(date)}`);
      lines.push(`  Pranzo: ${d.lunch || "—"}`);
      lines.push(`  Cena:   ${d.dinner || "—"}`);
    });
    lines.push("");
  });

  const text = lines.join("\n");
  navigator.clipboard.writeText(text).then(()=>{
    toast("ok","Piano copiato negli appunti");
  }).catch(()=>{
    const w = window.open("","_blank");
    w.document.write("<pre style='font-family:monospace;padding:20px'>" + escapeHtml(text) + "</pre>");
    w.document.close();
  });
}

function printPlan(){
  if(!state.plan){ toast("warn","Nessun piano da stampare"); return; }

  const rows = [];
  state.plan.weeks.forEach((w, wi)=>{
    const ws = new Date(w.weekStartISO + "T00:00:00");
    rows.push(`<h3 style="font-family:system-ui;margin:16px 0 8px">Settimana ${wi+1} — Dal ${fmtDMY(ws)} al ${fmtDMY(addDays(ws,6))}</h3>`);
    rows.push('<table style="width:100%;border-collapse:collapse;font-family:system-ui;font-size:14px">');
    rows.push('<tr><th style="border:1px solid #ddd;padding:8px;text-align:left">Giorno</th><th style="border:1px solid #ddd;padding:8px;text-align:left">Pranzo</th><th style="border:1px solid #ddd;padding:8px;text-align:left">Cena</th></tr>');
    w.days.forEach((d, di)=>{
      const date = addDays(ws, di);
      rows.push(`<tr><td style="border:1px solid #ddd;padding:8px"><b>${DOW[di]}</b> ${fmtDMY(date)}</td><td style="border:1px solid #ddd;padding:8px">${escapeHtml(d.lunch||"—")}</td><td style="border:1px solid #ddd;padding:8px">${escapeHtml(d.dinner||"—")}</td></tr>`);
    });
    rows.push('</table>');
  });

  const pw = window.open("","_blank");
  pw.document.write(`<!doctype html><html><head><title>Piano pasti</title></head><body style="padding:24px"><h2 style="font-family:system-ui">${escapeHtml(state.plan.displayLabel||"Piano pasti")}</h2>${rows.join("")}</body></html>`);
  pw.document.close();
  pw.print();
}

/* =========================
   EDIT DAY: picker pranzo+cena (solo admin)
   ========================= */
function openMealPicker(weekIndex, dayIndex){
  if(!isAdminOrAbove()) return;
  if(!state.plan?.weeks?.[weekIndex]?.days?.[dayIndex]) return;

  const pool = state.pool || [];
  if(!pool.length){ toast("warn","Pool vuoto: carica CSV"); return; }

  const html = `
    <div class="space-y-3">
      <input id="mealSearch"
        class="w-full rounded-2xl border border-line px-4 py-3 outline-none focus:ring-2 focus:ring-accent/30"
        placeholder="Cerca piatto (pranzo o cena)…" />
      <div id="mealList" class="max-h-72 overflow-auto space-y-2"></div>
      <div class="text-xs text-gray-500">Se scegli, cambia pranzo+cena insieme (coppia fissa).</div>
    </div>
  `;

  modal({
    title:"Modifica giorno",
    desc:"Scegli una nuova combinazione pranzo + cena",
    body:html,
    okText:"Chiudi",
    showCancel:false
  });

  const list = $("mealList");
  function renderList(filter=""){
    list.innerHTML = "";
    const f = filter.toLowerCase();

    pool
      .filter(p =>
        (p.lunch||"").toLowerCase().includes(f) ||
        (p.dinner||"").toLowerCase().includes(f)
      )
      .slice(0,60)
      .forEach(p=>{
        const row = document.createElement("button");
        row.type = "button";
        row.className="w-full text-left rounded-2xl border border-line bg-white hover:bg-surface px-4 py-3 transition-colors";
        row.innerHTML = `
          <div class="text-xs text-muted">Pranzo</div>
          <div class="text-sm font-semibold text-ink">${escapeHtml(p.lunch)}</div>
          <div class="mt-2 text-xs text-muted">Cena</div>
          <div class="text-sm font-semibold text-ink">${escapeHtml(p.dinner)}</div>
        `;
        row.onclick = ()=>{
          state.plan.weeks[weekIndex].days[dayIndex].lunch = p.lunch;
          state.plan.weeks[weekIndex].days[dayIndex].dinner = p.dinner;
          state.dirty = true;
          $("modalWrap").classList.add("hidden");
          renderPlan();
          toast("ok","Giorno aggiornato");
        };
        list.appendChild(row);
      });
  }

  renderList();
  $("mealSearch")?.addEventListener("input",(e)=>renderList(e.target.value));
}

/* =========================
   RENDER: SAVED LISTS
   ========================= */
function renderSavedLists(){
  const q = (state.savedFilter || "").toLowerCase();
  const items = state.saved.filter(it => (it.label||it.id||"").toLowerCase().includes(q));

  const count = state.saved.length;
  if($("savedCount")) $("savedCount").textContent = String(count);
  if($("savedCountMobile")) $("savedCountMobile").textContent = String(count);

  const renderOne = (wrapId)=>{
    const wrap = $(wrapId);
    if(!wrap) return;
    wrap.innerHTML = "";

    if(!items.length){
      wrap.innerHTML = `<div class="text-sm text-muted">Nessun piano</div>`;
      return;
    }

    items.forEach(it=>{
      const row = document.createElement("div");
      row.className = "flex items-center gap-2";

      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "flex-1 text-left rounded-2xl border border-line bg-white hover:bg-surface px-4 py-3 transition-colors";
      btn.innerHTML = `
        <div class="text-sm font-semibold text-ink">${escapeHtml(it.label || it.id)}</div>
        <div class="text-xs text-muted mt-1">${it.createdBy ? ("Creato da " + escapeHtml(it.createdBy)) : ""}</div>
      `;
      btn.onclick = async ()=>{
        loading(true, "Carico", "Apro il piano…");
        const plan = await apiGet("api/load.php?id=" + encodeURIComponent(it.id));
        loading(false);
        state.plan = plan;
        state.dirty = false;
        state.activeWeek = 0;
        renderPlan();
        toast("ok","Piano caricato");
        closeDrawer();
      };

      row.appendChild(btn);

      if(isAdminOrAbove()){
        const del = document.createElement("button");
        del.type = "button";
        del.className = "shrink-0 rounded-2xl border border-line bg-white hover:bg-danger/10 px-3 py-3 text-danger transition-colors";
        del.textContent = "🗑";
        del.onclick = async ()=>{
          const ok = await modal({
            title:"Elimina piano",
            desc:"Operazione definitiva",
            body:`Vuoi eliminare questo piano?<br><b>${escapeHtml(it.label || it.id)}</b>`,
            okText:"Elimina",
            cancelText:"Annulla"
          });
          if(!ok) return;
          loading(true, "Elimino", "Rimuovo il file…");
          await apiPost("api/delete.php", {id: it.id});
          loading(false);
          toast("ok","Piano eliminato");
          await loadSaved();
        };
        row.appendChild(del);
      }

      wrap.appendChild(row);
    });
  };

  renderOne("savedList");
  renderOne("savedListMobile");
}

/* =========================
   RENDER: USERS
   ========================= */
function renderUsers(items){
  const wrap = $("groupUsersList");
  if(!wrap) return;

  wrap.innerHTML = "";

  if(!items.length){
    wrap.innerHTML = `<div class="text-sm text-muted">—</div>`;
    return;
  }

  items.forEach(u=>{
    const row = document.createElement("div");
    row.className = "flex items-center justify-between gap-2 rounded-2xl border border-line bg-white px-3 py-2.5";

    const left = document.createElement("div");
    left.className = "min-w-0";

    const top = document.createElement("div");
    top.className = "text-sm font-semibold truncate text-ink";
    top.textContent = u.username;

    const meta = document.createElement("div");
    meta.className = "text-xs text-muted";
    meta.textContent = `ruolo: ${u.role}`;

    left.appendChild(top);
    left.appendChild(meta);

    row.appendChild(left);

    const right = document.createElement("div");
    right.className = "flex items-center gap-2";

    if(isAdminOrAbove()){
      const del = document.createElement("button");
      del.type = "button";
      del.className = "rounded-2xl border border-line px-3 py-2 hover:bg-danger/10 text-danger transition-colors";
      del.textContent = "Elimina";
      del.onclick = ()=>deleteUser(u.username);
      right.appendChild(del);
    }

    row.appendChild(right);
    wrap.appendChild(row);
  });
}

/* =========================
   RENDER: PLAN
   ========================= */
function renderPlan(){
  const content = $("weekContent");
  const tabs = $("weekTabs");
  if(!content || !tabs) return;

  const isAdmin = isAdminOrAbove();

  if(!state.plan){
    if($("currentLabel")) $("currentLabel").textContent = "—";
    tabs.innerHTML = "";
    content.innerHTML = `<div class="text-sm text-muted">Seleziona un piano salvato o genera un nuovo piano (admin).</div>`;
    return;
  }

  if($("currentLabel")) $("currentLabel").textContent = state.plan.displayLabel || makePlanLabel(state.plan.startMondayISO, state.plan.weeks.length);
  show($("dirtyIndicator"), state.dirty);

  // tabs
  tabs.innerHTML = "";
  state.plan.weeks.forEach((w, idx)=>{
    const b = document.createElement("button");
    b.type="button";
    const active = idx === state.activeWeek;
    b.className = active
      ? "rounded-2xl px-3 py-2 border border-line bg-ink text-white text-sm font-semibold transition-opacity hover:opacity-90"
      : "rounded-2xl px-3 py-2 border border-line bg-white hover:bg-surface text-sm transition-colors";
    b.textContent = `Settimana ${idx+1}`;
    b.onclick = ()=>{ state.activeWeek = idx; renderPlan(); };
    tabs.appendChild(b);
  });

  const week = state.plan.weeks[state.activeWeek];
  const weekStart = new Date(week.weekStartISO + "T00:00:00");

  const header = document.createElement("div");
  header.className = "flex items-center justify-between gap-3 flex-wrap";

  const hleft = document.createElement("div");
  hleft.innerHTML = `
    <div class="text-sm font-semibold">Dal ${fmtDMY(weekStart)} al ${fmtDMY(addDays(weekStart, 6))}</div>
    <div class="text-xs text-muted mt-1">Regole gruppo: ${escapeHtml(rulesSummaryText().replaceAll("\n"," • "))}</div>
  `;
  header.appendChild(hleft);

  if(isAdmin && state.plan?.id && state.plan.weeks.length > 1){
    const removeBtn = document.createElement("button");
    removeBtn.type = "button";
    removeBtn.className = "rounded-2xl border border-danger/40 text-danger px-3 py-2 text-xs hover:bg-danger/10 transition-colors";
    removeBtn.textContent = "Rimuovi settimana";
    removeBtn.onclick = ()=>removeWeek(state.activeWeek);
    header.appendChild(removeBtn);
  }

  const daysWrap = document.createElement("div");
  daysWrap.className = "mt-4 space-y-3";

  const todayISO = fmtISO(new Date());

  week.days.forEach((d, idx)=>{
    const date = addDays(weekStart, idx);
    const isToday = fmtISO(date) === todayISO;

    const card = document.createElement("div");
    card.className = isToday
      ? "rounded-3xl border-2 border-accent shadow-md overflow-hidden"
      : "rounded-3xl border border-line bg-white shadow-sm overflow-hidden";

    // top
    const top = document.createElement("div");
    top.className = "px-5 py-4 bg-surface border-b border-line";

    const badges = [];

    if(isToday) badges.push('<span class="inline-flex items-center text-[11px] px-2 py-1 rounded-full bg-success/20 border border-success/40 text-success font-semibold">Oggi</span>');

    // badge pizza/libero con tooltip
    const s = getSettings();
    const r = (s.rules || {});
    const p = r.pizza || {};
    const f = r.freeMeal || {};

    const isPizzaHere = !!p.enabled && Number(p.dayIndex) === idx && ((p.meal==="lunch" && d.lunch === p.text) || (p.meal==="dinner" && d.dinner === p.text));
    const isFreeHere  = !!f.enabled && Number(f.dayIndex) === idx && ((f.meal==="lunch" && d.lunch === f.text) || (f.meal==="dinner" && d.dinner === f.text));

    if(isPizzaHere){
      badges.push(pillIcon("🍕","Pizza","border-line",
        "Regola Pizza",
        `${DOW[p.dayIndex]} • ${p.meal==="lunch"?"pranzo":"cena"}\n${p.text || "Pizza"}`
      ));
    }
    if(isFreeHere){
      badges.push(pillIcon("🟡","Libero","border-line",
        "Pasto libero",
        `${DOW[f.dayIndex]} • ${f.meal==="lunch"?"pranzo":"cena"}\n${f.text || "LIBERO"}`
      ));
    }

    top.innerHTML = `
      <div class="flex items-start justify-between gap-3">
        <div>
          <div class="text-sm font-semibold">${DOW[idx]} • ${fmtDMY(date)}</div>
          <div class="text-xs text-muted mt-1">${d.locked ? "bloccato" : "libero"}</div>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2">
          ${badges.join("")}
        </div>
      </div>
    `;

    // Pulsanti admin (modifica + blocco)
    if(isAdmin){
      const right = top.querySelector(".flex.flex-wrap.items-center.justify-end.gap-2");
      if(right){
        // Modifica solo se non bloccato
        if(!d.locked){
          const editBtn = document.createElement("button");
          editBtn.type = "button";
          editBtn.className = "shrink-0 rounded-full border border-line bg-white/80 hover:bg-white px-2.5 py-2 text-sm";
          editBtn.title = "Modifica pranzo + cena";
          editBtn.textContent = "✏️";
          editBtn.onclick = (e)=>{
            e.preventDefault();
            e.stopPropagation();
            openMealPicker(state.activeWeek, idx);
          };
          right.appendChild(editBtn);
        }

        // Lock toggle
        const lockBtn = document.createElement("button");
        lockBtn.type = "button";
        lockBtn.className = "shrink-0 rounded-full border border-line bg-white/80 hover:bg-white px-2.5 py-2 text-sm";
        lockBtn.title = d.locked ? "Sblocca giorno" : "Blocca giorno";
        lockBtn.textContent = d.locked ? "🔒" : "🔓";
        lockBtn.onclick = (e)=>{
          e.preventDefault();
          e.stopPropagation();
          state.plan.weeks[state.activeWeek].days[idx].locked = !d.locked;
          state.dirty = true;
          renderPlan();
        };
        right.appendChild(lockBtn);
      }
    }

    card.appendChild(top);

    const body = document.createElement("div");
    body.className = "px-5 py-4 space-y-3";

    const mealBox = (title, value, dotCls, boxCls)=>{
      const box = document.createElement("div");
      box.className = `rounded-2xl border px-4 py-3 ${boxCls}`;
      box.innerHTML = `
        <div class="flex items-center gap-2">
          <span class="inline-block h-2.5 w-2.5 rounded-full ${dotCls}"></span>
          <div class="text-xs font-semibold text-muted">${title}</div>
        </div>
        <div class="text-sm text-ink mt-2 whitespace-pre-wrap">${escapeHtml(value || "—")}</div>
      `;
      return box;
    };

    body.appendChild(mealBox("Pranzo", d.lunch, "bg-peach", "border-peach/40 bg-peach/10"));
    body.appendChild(mealBox("Cena",   d.dinner, "bg-accent2", "border-accent2/40 bg-accent2/10"));

    card.appendChild(body);
    daysWrap.appendChild(card);
  });

  content.innerHTML = "";
  content.appendChild(header);
  content.appendChild(daysWrap);

  bindAllTips(content);
  renderTodayBanner();
}

/* =========================
   BANNER OGGI
   ========================= */
function renderTodayBanner(){
  const banner = $("todayBanner");
  if(!banner) return;

  if(!state.plan){ show(banner, false); return; }

  const todayISO = fmtISO(new Date());
  let foundDay = null, todayDate = null, foundDayIdx = null;

  outer:
  for(const week of state.plan.weeks){
    const ws = new Date(week.weekStartISO + "T00:00:00");
    for(let d = 0; d < week.days.length; d++){
      const date = addDays(ws, d);
      if(fmtISO(date) === todayISO){
        foundDay    = week.days[d];
        todayDate   = date;
        foundDayIdx = d;
        break outer;
      }
    }
  }

  if(!foundDay){ show(banner, false); return; }

  show(banner, true);
  if($("todayBannerDate"))
    $("todayBannerDate").textContent = `${DOW[foundDayIdx]} • ${fmtHumanDate(todayDate)}`;
  if($("todayLunch"))  $("todayLunch").textContent  = foundDay.lunch  || "—";
  if($("todayDinner")) $("todayDinner").textContent = foundDay.dinner || "—";
}

/* =========================
   STATISTICHE POOL
   ========================= */
async function loadStats(){
  const content = $("statsContent");
  if(!content) return;

  const isAdmin = isAdminOrAbove();

  content.innerHTML = `<div class="text-sm text-muted text-center py-6">Caricamento…</div>`;

  let j;
  try { j = await apiGet("api/stats.php"); }
  catch(e){
    content.innerHTML = `<div class="text-sm text-danger">Errore caricamento statistiche.</div>`;
    return;
  }

  // Header: info reset + bottone (admin) + totali
  const resetInfo = j.reset_at
    ? `<span class="text-[11px] text-muted">Azzerato il ${new Date(j.reset_at).toLocaleDateString("it-IT",{day:"2-digit",month:"long",year:"numeric"})}</span>`
    : "";

  const resetBtn = isAdmin
    ? `<button id="btnResetStats" class="rounded-2xl border border-danger/40 text-danger px-3 py-1.5 text-xs hover:bg-danger/10 transition-colors">Azzera statistiche</button>`
    : "";

  let html = `
    <div class="flex items-center justify-between gap-3 flex-wrap mb-4">
      <div class="flex items-center gap-3 flex-wrap">
        ${resetInfo}
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        ${j.ok && j.items?.length ? `<span class="text-xs font-semibold text-ink bg-surface border border-line px-2.5 py-1 rounded-full">${j.total_days} giorni analizzati</span>` : ""}
        ${resetBtn}
      </div>
    </div>
  `;

  if(!j.ok || !j.items?.length){
    html += `<div class="text-sm text-muted">Nessun dato disponibile. Salva almeno un piano per vedere le statistiche.</div>`;
    content.innerHTML = html;
    bindStatsResetBtn();
    return;
  }

  const items    = j.items;
  const maxCount = items[0].count;

  html += `<div class="space-y-2">`;

  items.forEach((item, i)=>{
    const pct   = Math.round((item.count / maxCount) * 100);
    const medal = i === 0 ? "🥇" : i === 1 ? "🥈" : i === 2 ? "🥉"
                : `<span class="text-[11px] font-bold text-muted w-5 inline-block text-center">${i+1}</span>`;
    html += `
      <div class="rounded-2xl border border-line bg-white px-4 py-3">
        <div class="flex items-center justify-between gap-3 mb-2">
          <div class="flex items-center gap-2 min-w-0">
            <span class="shrink-0 w-5 text-center text-sm">${medal}</span>
            <div class="min-w-0">
              <span class="text-xs font-semibold text-ink">${escapeHtml(item.lunch)}</span>
              <span class="text-muted mx-1 text-xs">·</span>
              <span class="text-xs font-semibold text-ink">${escapeHtml(item.dinner)}</span>
            </div>
          </div>
          <div class="shrink-0 text-xs font-bold text-ink">${item.count}×</div>
        </div>
        <div class="h-1.5 rounded-full bg-surface overflow-hidden">
          <div class="h-full rounded-full bg-gradient-to-r from-peach to-accent2" style="width:${pct}%"></div>
        </div>
      </div>
    `;
  });

  html += `</div>`;
  content.innerHTML = html;
  bindStatsResetBtn();
}

function bindStatsResetBtn(){
  $("btnResetStats")?.addEventListener("click", resetStats);
}

async function resetStats(){
  const ok = await modal({
    title:      "Azzera statistiche",
    desc:       "Operazione irreversibile",
    body:       "Vuoi azzerare le statistiche? I piani salvati non verranno eliminati, ma le statistiche ripartiranno da zero contando solo i piani salvati da questo momento in poi.",
    okText:     "Azzera",
    cancelText: "Annulla"
  });
  if(!ok) return;

  loading(true, "Azzero statistiche", "Salvo timestamp…");
  const j = await apiPost("api/stats_reset.php", {});
  loading(false);

  if(!j.ok){ toast("err", "Errore azzeramento"); return; }

  toast("ok", "Statistiche azzerate");
  await loadStats();
}

/* =========================
   LOG ATTIVITÀ
   ========================= */
const LOG_ACTION_LABELS = {
  piano_salvato:        { icon:"💾", label:"Piano salvato" },
  piano_eliminato:      { icon:"🗑", label:"Piano eliminato" },
  utente_creato:        { icon:"👤", label:"Utente creato" },
  utente_eliminato:     { icon:"❌", label:"Utente eliminato" },
  csv_caricato:                  { icon:"📄", label:"Piano Alimentare caricato" },
  piano_alimentare_aggiunto:     { icon:"📄", label:"Piano Alimentare — aggiunto" },
  piano_alimentare_sostituito:   { icon:"📄", label:"Piano Alimentare — sostituito" },
  statistiche_azzerate: { icon:"🔄", label:"Statistiche azzerate" },
  impostazioni_salvate: { icon:"⚙️", label:"Impostazioni salvate" },
  backup_esportato:     { icon:"📦", label:"Backup esportato" },
  backup_ripristinato:  { icon:"♻️", label:"Backup ripristinato" },
};

async function loadLog(){
  const content = $("logContent");
  if(!content) return;
  content.innerHTML = `<div class="text-sm text-muted text-center py-6">Caricamento…</div>`;

  let j;
  try { j = await apiGet("api/log.php"); }
  catch(e){
    content.innerHTML = `<div class="text-sm text-danger">Errore caricamento log.</div>`;
    return;
  }

  if(!j.ok || !j.items?.length){
    content.innerHTML = `<div class="text-sm text-muted">Nessuna attività registrata.</div>`;
    return;
  }

  const rows = j.items.map(item=>{
    const meta  = LOG_ACTION_LABELS[item.action] || { icon:"•", label: item.action };
    const ts    = item.ts ? new Date(item.ts).toLocaleString("it-IT", {
      day:"2-digit", month:"2-digit", year:"numeric",
      hour:"2-digit", minute:"2-digit"
    }) : "—";

    const detailParts = [];
    if(item.details?.label)    detailParts.push(escapeHtml(item.details.label));
    if(item.details?.username) detailParts.push("utente: " + escapeHtml(item.details.username));
    if(item.details?.filename) detailParts.push(escapeHtml(item.details.filename));
    if(item.details?.id && !item.details?.label) detailParts.push(escapeHtml(item.details.id));
    if(item.details?.plans_count !== undefined) detailParts.push(`${item.details.plans_count} piani`);
    if(item.details?.plans !== undefined) detailParts.push(`+${item.details.plans} piani, skip:${item.details.plans_skipped||0}`);

    return `
      <div class="flex items-start gap-3 rounded-2xl border border-line bg-white px-4 py-3">
        <span class="shrink-0 text-base mt-0.5">${meta.icon}</span>
        <div class="min-w-0 flex-1">
          <div class="flex items-center justify-between gap-2 flex-wrap">
            <span class="text-sm font-semibold text-ink">${meta.label}</span>
            <span class="text-[11px] text-muted shrink-0">${ts}</span>
          </div>
          <div class="text-xs text-muted mt-0.5">
            <span class="font-medium">${escapeHtml(item.user || "—")}</span>
            ${detailParts.length ? " · " + detailParts.join(" · ") : ""}
          </div>
        </div>
      </div>
    `;
  });

  content.innerHTML = `<div class="space-y-2">${rows.join("")}</div>`;
}

/* =========================
   AVVISO PIANO IN SCADENZA
   ========================= */
function checkPlanExpiry(){
  if(!isAdminOrAbove()) return;

  const banner  = $("expiryBanner");
  const msg     = $("expiryMsg");
  if(!banner || !msg) return;

  const todayISO = fmtISO(new Date());

  // Trova il piano con end date più lontana
  const latest = state.saved
    .filter(it => it.endISO)
    .sort((a,b) => b.endISO.localeCompare(a.endISO))[0];

  if(!latest){
    // Nessun piano salvato
    show(banner, true);
    msg.textContent = "Nessun piano salvato. Genera il primo piano.";
    prefillNextMonday(null);
    return;
  }

  const daysLeft = Math.ceil(
    (new Date(latest.endISO + "T00:00:00") - new Date(todayISO + "T00:00:00"))
    / (1000 * 60 * 60 * 24)
  );

  if(daysLeft <= 7){
    show(banner, true);
    if(daysLeft < 0)     msg.textContent = `Il piano è scaduto ${Math.abs(daysLeft)} giorni fa. Genera un nuovo piano.`;
    else if(daysLeft === 0) msg.textContent = "Il piano termina oggi. Genera il piano per la prossima settimana.";
    else                 msg.textContent = `Il piano termina tra ${daysLeft} giorn${daysLeft===1?"o":"i"}. Genera il piano successivo.`;
    prefillNextMonday(latest.endISO);
  } else {
    show(banner, false);
  }
}

function prefillNextMonday(lastEndISO){
  const input = $("startMonday");
  if(!input) return;
  const base = lastEndISO ? new Date(lastEndISO + "T00:00:00") : new Date();
  // prossimo lunedì dopo l'end date
  const day  = base.getDay(); // 0=dom
  const diff = day === 0 ? 1 : (8 - day);
  const next = addDays(base, day === 1 ? 7 : diff); // se già lun, +7
  input.value = fmtISO(next);
}

/* =========================
   BACKUP / RESTORE
   ========================= */
async function downloadBackup(){
  // Apre direttamente la URL di download (il PHP restituisce attachment)
  window.location.href = "api/backup.php";
  toast("ok", "Download backup avviato");
  // Log già scritto lato server
}

async function restoreBackup(){
  const fileInput = $("restoreFile");
  const statusEl  = $("restoreStatus");
  if(!fileInput?.files?.length){
    if(statusEl) statusEl.textContent = "Seleziona un file JSON di backup";
    return;
  }

  const ok = await modal({
    title: "Ripristina backup",
    desc:  "Operazione importante",
    body:  "I piani già esistenti non verranno sovrascritti, ma CSV e impostazioni verranno sostituiti con quelli del backup. Continuare?",
    okText: "Ripristina",
    cancelText: "Annulla"
  });
  if(!ok) return;

  const formData = new FormData();
  formData.append("file", fileInput.files[0]);

  loading(true, "Ripristino", "Importo dati…");
  let j;
  try {
    const r = await fetch("api/restore.php", {
      method: "POST",
      headers: { "X-CSRF-Token": state.csrfToken },
      body: formData
    });
    j = await r.json().catch(()=>null);
  } catch(e) {
    j = null;
  }
  loading(false);

  if(!j?.ok){
    const err = j?.error || "errore";
    let errMsg = err;
    if(err === "group_mismatch") errMsg = `Il backup è del gruppo "${j.backup_group}", non del tuo gruppo`;
    if(err === "invalid_backup_format") errMsg = "File non valido o formato non riconosciuto";
    if(statusEl) statusEl.textContent = "Errore: " + errMsg;
    toast("err", errMsg);
    return;
  }

  const r = j.restored || {};
  const summary = [
    `${r.plans || 0} piani importati`,
    r.plans_skipped ? `${r.plans_skipped} già esistenti (saltati)` : null,
    r.csv ? "CSV aggiornato" : null,
    r.settings ? "impostazioni aggiornate" : null,
  ].filter(Boolean).join(", ");

  if(statusEl) statusEl.textContent = summary;
  toast("ok", "Backup ripristinato — " + summary);

  // Ricarica dati
  await loadSaved();
  await loadSettings();
  await loadPairs();
  renderPlan();
  renderSettings();
}

/* =========================
   BOOT
   ========================= */
async function boot(){
  let me = null;
  try{ me = await refreshMe(); }catch(err){ console.error("refreshMe failed", err); }

  show($("loginSection"), !me);
  show($("appSection"), !!me);
  if(!me) return;

  // Superadmin senza gruppo attivo → mostra il pannello di sistema
  if(me.role === "superadmin" && !me.active_group){
    updateWhoami(me, null);
    show($("groupSwitcherBanner"), false);
    // Mostra il pulsante Pannello Sistema nella sidebar anche senza gruppo attivo
    show($("btnNavSuperadmin"),       true);
    show($("btnNavSuperadminMobile"), true);
    // Nasconde i pulsanti di navigazione gruppo (non applicabili senza gruppo attivo)
    show($("adminControls"),      false);
    show($("expiryBanner"),       false);
    show($("csvSettingsSection"), false);
    show($("csvViewSection"),     false);
    show($("logSection"),         false);
    show($("backupSection"),      false);
    setView("superadmin");
    await renderSuperadminDashboard();
    return;
  }

  await bootNormal(me);
}

function updateWhoami(me, activeGroup){
  let whoText;
  if(me.role === "superadmin"){
    whoText = activeGroup
      ? `${me.username} • superadmin • gestione: Gruppo ${activeGroup}`
      : `${me.username} • superadmin`;
  } else {
    whoText = `${me.username} • ${me.role} • gruppo: ${me.group}`;
  }
  if($("whoami")) $("whoami").textContent = whoText;
  if($("whoamiDesktop")) $("whoamiDesktop").textContent = whoText;
  if($("whoamiMobile")) $("whoamiMobile").textContent = whoText;
}

async function bootNormal(me){
  const activeGroup = me.active_group || me.group;
  updateWhoami(me, activeGroup);

  const isAdmin = isAdminOrAbove();
  show($("csvSettingsSection"), isAdmin);
  show($("csvViewSection"), !isAdmin);
  show($("adminControls"), isAdmin);
  show($("logSection"), isAdmin);
  show($("backupSection"), isAdmin);

  // Banner "stai gestendo gruppo X" visibile solo al superadmin quando ha un gruppo attivo
  const banner = $("groupSwitcherBanner");
  if(banner){
    show(banner, isSuperAdmin());
    const lbl = $("activeGroupLabel");
    if(lbl) lbl.textContent = activeGroup || "";
  }

  // Pulsante "Pannello Sistema" visibile solo al superadmin
  show($("btnNavSuperadmin"),       isSuperAdmin());
  show($("btnNavSuperadminMobile"), isSuperAdmin());

  if(isAdmin && $("startMonday")){
    const now = new Date();
    const day = now.getDay();
    const diffToMon = (day===0) ? 1 : (8 - day);
    const nextMon = addDays(now, diffToMon);
    $("startMonday").value = fmtISO(nextMon);
  }

  loading(true, "Avvio", "Carico dati…");

  try{
    await loadSettings();
    await loadPairs();
    await loadSaved();
    if(isAdmin) await loadGroupUsers();

    // Auto-load piano attivo (copre la data di oggi)
    const todayISO   = fmtISO(new Date());
    const activeMeta = state.saved.find(it =>
      it.startISO && it.endISO &&
      it.startISO <= todayISO && todayISO <= it.endISO
    );
    if(activeMeta){
      try{
        const plan  = await apiGet("api/load.php?id=" + encodeURIComponent(activeMeta.id));
        state.plan  = plan;
        state.dirty = false;
        if(plan.weeks){
          for(let i = 0; i < plan.weeks.length; i++){
            const ws  = new Date(plan.weeks[i].weekStartISO + "T00:00:00");
            const we  = addDays(ws, 6);
            const tod = new Date(todayISO + "T00:00:00");
            if(tod >= ws && tod <= we){ state.activeWeek = i; break; }
          }
        }
      }catch(e){ console.warn("Auto-load piano attivo fallito", e); }
    }
  }catch(err){
    console.error("BOOT load failed:", err);
    toast("err", "Errore avvio: " + (err?.message || err));
  }finally{
    loading(false);
  }

  // Se si arriva da "superadmin" (selezione gruppo), torna sempre ai piani
  const nextView = (state.view === "superadmin") ? "plans" : (state.view || "plans");
  setView(nextView);
  renderSettings();
  renderPlan();
  checkPlanExpiry();
}

/* =========================
   SUPERADMIN DASHBOARD
   ========================= */
async function renderSuperadminDashboard(){
  const content = $("superadminGroupsGrid");
  if(!content) return;
  content.innerHTML = `<div class="text-sm text-muted">Caricamento gruppi…</div>`;

  let j;
  try{ j = await apiGet("api/superadmin_groups.php"); }
  catch(e){ content.innerHTML = `<div class="text-sm text-danger">Errore caricamento gruppi.</div>`; return; }

  if(!j.ok || !j.groups?.length){
    content.innerHTML = `<div class="text-sm text-muted">Nessun gruppo trovato. Crea il primo gruppo qui sotto.</div>`;
    return;
  }

  content.innerHTML = j.groups.map(g => `
    <div class="rounded-3xl border border-line bg-white p-5 shadow-warm flex flex-col gap-3">
      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
          <span class="text-lg">🏷️</span>
          <div class="font-bold text-ink">Gruppo ${escapeHtml(g.group)}</div>
        </div>
        <span class="text-[11px] px-2.5 py-1 rounded-full border ${g.plans > 0 ? 'bg-success/10 border-success/30 text-success' : 'bg-surface border-line text-muted'} font-semibold">
          ${g.plans} ${g.plans === 1 ? 'piano' : 'piani'}
        </span>
      </div>
      <div class="flex items-center gap-3 text-xs text-muted">
        <span>👤 ${g.admins} admin</span>
        <span>•</span>
        <span>👥 ${g.users} utenti</span>
      </div>
      <button
        onclick="selectGroup('${escapeHtml(g.group)}')"
        class="mt-1 rounded-2xl bg-ink text-white px-4 py-2.5 text-sm font-semibold hover:opacity-90 transition-opacity">
        Gestisci
      </button>
    </div>
  `).join("");
}

async function selectGroup(groupName){
  loading(true, "Cambio gruppo", `Carico Gruppo ${groupName}…`);
  const j = await apiPost("api/set_active_group.php", {group: groupName});
  loading(false);
  if(!j.ok){ toast("err", j.error || "Errore selezione gruppo"); return; }

  // Reset completo di tutto lo stato del gruppo precedente
  state.plan = null; state.saved = []; state.pool = []; state.settings = null; state.dirty = false;

  // Chiudi gli accordion lazy-loaded (log, stats) così al prossimo open
  // ricaricano i dati del nuovo gruppo. NON cancellare innerHTML: contiene HTML statico.
  const closeAccordion = (bodyId, iconId) => {
    const body = $(bodyId), icon = $(iconId);
    if(body) body.classList.add("hidden");
    if(icon) icon.classList.remove("rotate-180");
  };
  closeAccordion("statsBody", "statsIcon");
  closeAccordion("logBody",   "logIcon");

  await boot();
}

async function exitGroupManagement(){
  loading(true, "Pannello sistema", "Torno alla lista gruppi…");
  const j = await apiPost("api/set_active_group.php", {group: ""});
  loading(false);
  if(!j.ok){ toast("err", "Errore uscita gruppo"); return; }
  state.plan = null; state.saved = []; state.pool = []; state.dirty = false;
  await boot();
}

async function createGroup(){
  const groupName = ($("newGroupName")?.value || "").trim().toUpperCase();
  const adminUser = ($("newGroupAdminUser")?.value || "").trim();
  const adminPass = ($("newGroupAdminPass")?.value || "");
  const status    = $("createGroupStatus");

  if(!groupName){ if(status) status.textContent = "Inserisci il nome del gruppo"; return; }
  if(!/^[A-Z0-9_\-]{1,20}$/.test(groupName)){ if(status) status.textContent = "Nome gruppo: solo lettere maiuscole, numeri, _ o - (max 20)"; return; }
  if(!adminUser){ if(status) status.textContent = "Inserisci l'username dell'admin"; return; }
  if(adminPass.length < 8){ if(status) status.textContent = "Password admin: min 8 caratteri"; return; }

  if(status) status.textContent = "…";
  loading(true, "Creo gruppo", `Creo Gruppo ${groupName}…`);
  const j = await apiPost("api/create_user.php", {
    username: adminUser,
    password: adminPass,
    role: "admin",
    group: groupName
  });
  loading(false);

  if(!j.ok){
    let msg = j.error || "errore";
    if(msg === "bad_username")     msg = "Username admin non valido";
    if(msg === "password_too_short") msg = "Password troppo corta (min 8 caratteri)";
    if(msg === "username_exists")  msg = "Username già in uso";
    if(status) status.textContent = "Errore: " + msg;
    toast("err", msg);
    return;
  }

  toast("ok", `Gruppo ${groupName} creato. Admin: ${adminUser}`);
  if($("newGroupName"))      $("newGroupName").value = "";
  if($("newGroupAdminUser")) $("newGroupAdminUser").value = "";
  if($("newGroupAdminPass")) $("newGroupAdminPass").value = "";
  if(status) status.textContent = `Gruppo ${groupName} creato.`;
  await renderSuperadminDashboard();
}

/* =========================
   EVENTS
   ========================= */
document.addEventListener("DOMContentLoaded", ()=>{
  tipGlobalCloseHandlers();

  // Login / Register tabs
  $("btnTabLogin")?.addEventListener("click",    ()=>setLoginTab("login"));
  $("btnTabRegister")?.addEventListener("click", ()=>setLoginTab("register"));

  // Toggle occhio su tutti i campi password
  initPasswordToggles();

  // Uppercase automatico sul codice invito
  $("regInviteCode")?.addEventListener("input", (e)=>{
    const pos = e.target.selectionStart;
    e.target.value = e.target.value.toUpperCase();
    e.target.setSelectionRange(pos, pos);
  });

  $("btnRegister")?.addEventListener("click", register);

  // Invio con Enter nei campi registrazione
  ["regUsername","regPassword","regConfirm","regInviteCode"].forEach(id=>{
    $(id)?.addEventListener("keydown", (e)=>{ if(e.key==="Enter") register(); });
  });

  // Invite code: copia e rigenera
  $("btnCopyInviteCode")?.addEventListener("click", ()=>{
    const code = $("inviteCodeDisplay")?.textContent || "";
    navigator.clipboard.writeText(code).then(()=>toast("ok","Codice copiato")).catch(()=>toast("warn","Copia manuale: " + code));
  });
  $("btnRegenInviteCode")?.addEventListener("click", regenInviteCode);

  const btn = $("btnLogin");
if(btn){
  btn.onclick = (e) => {
    e.preventDefault();
    e.stopPropagation();
    login();
  };
}

  ["loginUser","loginPass"].forEach(id=>{
    $(id)?.addEventListener("keydown", (e)=>{ if(e.key==="Enter") login(); });
  });

  $("btnLogout")?.addEventListener("click", logout);
  $("btnLogoutMobile")?.addEventListener("click", logout);
  $("btnLogoutDrawer")?.addEventListener("click", logout);

  $("btnOpenSidebar")?.addEventListener("click", openDrawer);
  $("btnCloseSidebar")?.addEventListener("click", closeDrawer);
  $("sidebarDrawer")?.addEventListener("click", (e)=>{ if(e.target === $("sidebarDrawer")) closeDrawer(); });

  $("savedSearch")?.addEventListener("input", (e)=>{ state.savedFilter = e.target.value; renderSavedLists(); });
  $("savedSearchMobile")?.addEventListener("input", (e)=>{ state.savedFilter = e.target.value; renderSavedLists(); });

  $("btnUploadCsvSettings")?.addEventListener("click", ()=>uploadCsv("csvUploadSettings","csvStatusSettings"));

  $("btnCreateUser")?.addEventListener("click", createUser);

  $("btnGenerate")?.addEventListener("click", generatePlan);
  $("btnSave")?.addEventListener("click", savePlan);
  $("btnClearAll")?.addEventListener("click", clearView);

  $("btnNavPlans")?.addEventListener("click", ()=>setView("plans"));
  $("btnNavSettings")?.addEventListener("click", ()=>setView("settings"));
  $("btnNavPlansMobile")?.addEventListener("click", ()=>{ setView("plans"); closeDrawer(); });
  $("btnNavSettingsMobile")?.addEventListener("click", ()=>{ setView("settings"); closeDrawer(); });
  $("btnNavSuperadmin")?.addEventListener("click", ()=>exitGroupManagement());
  $("btnNavSuperadminMobile")?.addEventListener("click", ()=>{ exitGroupManagement(); closeDrawer(); });

  $("btnSaveSettings")?.addEventListener("click", saveSettings);
  $("btnChangePassword")?.addEventListener("click", changePassword);
  $("btnCopyPlan")?.addEventListener("click", exportPlanText);
  $("btnPrintPlan")?.addEventListener("click", printPlan);

  const makeAccordion = (toggleId, bodyId, iconId, onOpen=null)=>{
    const toggle = document.getElementById(toggleId);
    const body   = document.getElementById(bodyId);
    const icon   = document.getElementById(iconId);
    if(toggle && body){
      toggle.addEventListener("click", ()=>{
        const wasHidden = body.classList.contains("hidden");
        body.classList.toggle("hidden");
        icon?.classList.toggle("rotate-180");
        if(wasHidden && onOpen) onOpen();
      });
    }
  };

  makeAccordion("settingsRulesToggle", "settingsRulesBody", "settingsRulesIcon");
  makeAccordion("csvSettingsToggle",   "csvSettingsBody",   "csvSettingsIcon");
  makeAccordion("cpToggle",            "cpBody",            "cpIcon");
  makeAccordion("usersAccordionToggle","adminUsers",        "usersAccordionIcon");
  makeAccordion("statsToggle",         "statsBody",         "statsIcon", loadStats);
  makeAccordion("logToggle",           "logBody",           "logIcon",   loadLog);
  makeAccordion("backupToggle",        "backupBody",        "backupIcon");

  $("btnGenerateNext")?.addEventListener("click", ()=>{
    setView("plans");
    $("adminControls")?.scrollIntoView({ behavior:"smooth", block:"start" });
  });

  $("btnDownloadBackup")?.addEventListener("click", downloadBackup);
  $("btnRestoreBackup")?.addEventListener("click",  restoreBackup);

  boot();
});
