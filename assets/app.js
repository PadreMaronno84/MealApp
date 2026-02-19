const $ = (id)=>document.getElementById(id);

/* =========================
   API
   ========================= */
async function apiGet(url){
  const r = await fetch(url, {cache:"no-store"});
  const j = await r.json().catch(()=>null);
  if(!j) throw new Error("bad_json");
  return j;
}
async function apiPost(url, body){
  const r = await fetch(url, {
    method:"POST",
    headers: {"Content-Type":"application/json"},
    body: JSON.stringify(body)
  });
  const j = await r.json().catch(()=>null);
  if(!j) throw new Error("bad_json");
  return j;
}

// ✅ NUOVO: POST form-urlencoded (per compatibilità con PHP $_POST)
async function apiPostForm(url, bodyObj){
  const params = new URLSearchParams();
  Object.entries(bodyObj || {}).forEach(([k,v])=>{
    if(v === undefined || v === null) return;
    params.append(k, String(v));
  });

  const r = await fetch(url, {
    method: "POST",
    headers: {"Content-Type":"application/x-www-form-urlencoded; charset=UTF-8"},
    body: params.toString()
  });

  // il backend deve comunque rispondere JSON (come fa già il tuo sistema)
  const j = await r.json().catch(()=>null);
  if(!j) throw new Error("bad_json");
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

  if(type === "ok"){ dot.className="mt-1 h-2.5 w-2.5 rounded-full bg-accent"; t.textContent = title || "OK"; }
  if(type === "warn"){ dot.className="mt-1 h-2.5 w-2.5 rounded-full bg-warn"; t.textContent = title || "Attenzione"; }
  if(type === "err"){ dot.className="mt-1 h-2.5 w-2.5 rounded-full bg-danger"; t.textContent = title || "Errore"; }

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
      class="js-tip inline-flex items-center gap-1.5 text-[11px] px-2 py-1 rounded-full border bg-white/70 ${extraCls}"
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
  view: "plans"
};

/* =========================
   AUTH
   ========================= */
async function refreshMe(){
  const j = await apiGet("api/me.php");
  if(!j.ok) return null;
  if(!j.logged){ state.me=null; return null; }
  state.me = j.user;
  return state.me;
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
    $("loginStatus").textContent = "Credenziali errate";
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

  const isAdmin = state.me?.role === "admin";

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
}
async function saveSettings(){
  if(state.me?.role !== "admin") return;

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

  show($("plansView"), view === "plans");
  show($("settingsView"), view === "settings");

  const setBtn = (idPlans, idSet)=>{
    const bp = $(idPlans), bs = $(idSet);
    if(!bp || !bs) return;
    if(view === "plans"){
      bp.className = "rounded-2xl px-3 py-2.5 border border-line bg-ink text-white font-semibold";
      bs.className = "rounded-2xl px-3 py-2.5 border border-line bg-white hover:bg-gray-50";
    } else {
      bs.className = "rounded-2xl px-3 py-2.5 border border-line bg-ink text-white font-semibold";
      bp.className = "rounded-2xl px-3 py-2.5 border border-line bg-white hover:bg-gray-50";
    }
  };

  setBtn("btnNavPlans","btnNavSettings");
  setBtn("btnNavPlansMobile","btnNavSettingsMobile");

  if(view === "settings"){
    renderSettings();
  }
}

/* =========================
   DATA LOAD
   ========================= */
async function loadPairs(){
  const j = await apiGet("api/pairs_get.php");
  if(!j.ok) { toast("err","Errore lettura CSV"); return; }
  state.pool = j.exists ? (j.pairs||[]) : [];
  if(!j.exists) toast("warn","CSV non presente: caricalo da admin.");
}
async function loadSaved(){
  loading(true, "Aggiorno", "Carico la lista dei piani…");
  const j = await apiGet("api/list.php");
  loading(false);
  state.saved = (j.ok && j.items) ? j.items : [];
  renderSavedLists();
}
async function loadGroupUsers(){
  if(state.me?.role !== "admin") return;
  const j = await apiGet("api/group_users.php");
  renderUsers(j.ok ? (j.items||[]) : []);
}

/* =========================
   ADMIN: CSV + USERS
   ========================= */
async function uploadCsv(isMobile=false){
  const fileInput = isMobile ? $("csvUploadMobile") : $("csvUpload");
  const status = isMobile ? $("csvStatusMobile") : $("csvStatus");
  const f = fileInput?.files?.[0];
  if(!f){ if(status) status.textContent = "Seleziona un CSV"; return; }

  loading(true, "Upload CSV", "Carico il file…");
  if(status) status.textContent = "Caricamento…";

  const fd = new FormData();
  fd.append("file", f);
  const r = await fetch("api/upload_pairs.php", {method:"POST", body:fd});
  const j = await r.json().catch(()=>null);

  loading(false);

  if(!j || !j.ok){
    if(status) status.textContent="Errore upload";
    toast("err","Upload CSV non riuscito");
    return;
  }

  if(status) status.textContent="OK";
  toast("ok","CSV caricato");
  await loadPairs();
}

async function createUser(){
  if(state.me?.role !== "admin") return;

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
    if(msg==="bad_username") msg="Username non valido";
    if(msg==="password_too_short") msg="Password troppo corta (min 6)";
    if(msg==="username_exists") msg="Username già esistente";
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
function pickRandomPair(){
  const i = Math.floor(Math.random() * state.pool.length);
  return state.pool[i];
}
function makeRandomDay(){
  const p = pickRandomPair();
  return { lunch: p.lunch, dinner: p.dinner, locked:false };
}
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
  if(state.me?.role !== "admin") return;
  if(!state.pool.length){ toast("warn","Pool vuoto: carica CSV"); return; }

  const start = $("startMonday")?.value;
  if(!start){ toast("warn","Seleziona il lunedì"); return; }

  const numWeeks = parseInt($("numWeeks")?.value || "1",10);
  const startMonday = new Date(start + "T00:00:00");

  loading(true, "Genero", "Creo la settimana…");

  const weeks = [];
  for(let w=0; w<numWeeks; w++){
    const weekStart = addDays(startMonday, w*7);
    const days = [];
    for(let d=0; d<7; d++){
      const day = makeRandomDay();
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

  state.activeWeek = 0;
  renderPlan();
  if($("status")) $("status").textContent = "Generato.";
  loading(false);
  toast("ok","Piano generato");
}

async function savePlan(){
  if(state.me?.role !== "admin") return;
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

  toast("ok","Salvato");
  await loadSaved();
}

function clearView(){
  state.plan = null;
  state.activeWeek = 0;
  renderPlan();
  toast("ok","Vista azzerata");
}

/* =========================
   EDIT DAY: picker pranzo+cena (solo admin)
   ========================= */
function openMealPicker(weekIndex, dayIndex){
  if(state.me?.role !== "admin") return;
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
        row.className="w-full text-left rounded-2xl border border-line bg-white hover:bg-gray-50 px-4 py-3";
        row.innerHTML = `
          <div class="text-xs text-gray-500">Pranzo</div>
          <div class="text-sm font-semibold">${escapeHtml(p.lunch)}</div>
          <div class="mt-2 text-xs text-gray-500">Cena</div>
          <div class="text-sm font-semibold">${escapeHtml(p.dinner)}</div>
        `;
        row.onclick = ()=>{
          state.plan.weeks[weekIndex].days[dayIndex].lunch = p.lunch;
          state.plan.weeks[weekIndex].days[dayIndex].dinner = p.dinner;
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
      wrap.innerHTML = `<div class="text-sm text-gray-500">Nessun piano</div>`;
      return;
    }

    items.forEach(it=>{
      const row = document.createElement("div");
      row.className = "flex items-center gap-2";

      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "flex-1 text-left rounded-2xl border border-line bg-white hover:bg-gray-50 px-4 py-3";
      btn.innerHTML = `
        <div class="text-sm font-semibold">${escapeHtml(it.label || it.id)}</div>
        <div class="text-xs text-gray-500 mt-1">${it.createdBy ? ("Creato da " + escapeHtml(it.createdBy)) : ""}</div>
      `;
      btn.onclick = async ()=>{
        loading(true, "Carico", "Apro il piano…");
        const plan = await apiGet("api/load.php?id=" + encodeURIComponent(it.id));
        loading(false);
        state.plan = plan;
        state.activeWeek = 0;
        renderPlan();
        toast("ok","Piano caricato");
        closeDrawer();
      };

      row.appendChild(btn);

      if(state.me?.role === "admin"){
        const del = document.createElement("button");
        del.type = "button";
        del.className = "shrink-0 rounded-2xl border border-line bg-white hover:bg-red-50 px-3 py-3 text-danger";
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
          await apiGet("api/delete.php?id=" + encodeURIComponent(it.id));
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
    wrap.innerHTML = `<div class="text-sm text-gray-500">—</div>`;
    return;
  }

  items.forEach(u=>{
    const row = document.createElement("div");
    row.className = "flex items-center justify-between gap-2 rounded-2xl border border-line bg-white px-3 py-2.5";

    const left = document.createElement("div");
    left.className = "min-w-0";

    const top = document.createElement("div");
    top.className = "text-sm font-semibold truncate";
    top.textContent = u.username;

    const meta = document.createElement("div");
    meta.className = "text-xs text-gray-600";
    meta.textContent = `ruolo: ${u.role}`;

    left.appendChild(top);
    left.appendChild(meta);

    row.appendChild(left);

    const right = document.createElement("div");
    right.className = "flex items-center gap-2";

    if(state.me?.role === "admin"){
      const del = document.createElement("button");
      del.type = "button";
      del.className = "rounded-2xl border border-line px-3 py-2 hover:bg-red-50 text-danger";
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

  const isAdmin = state.me?.role === "admin";

  if(!state.plan){
    if($("currentLabel")) $("currentLabel").textContent = "—";
    tabs.innerHTML = "";
    content.innerHTML = `<div class="text-sm text-gray-600">Seleziona un piano salvato o genera un nuovo piano (admin).</div>`;
    return;
  }

  if($("currentLabel")) $("currentLabel").textContent = state.plan.displayLabel || makePlanLabel(state.plan.startMondayISO, state.plan.weeks.length);

  // tabs
  tabs.innerHTML = "";
  state.plan.weeks.forEach((w, idx)=>{
    const b = document.createElement("button");
    b.type="button";
    const active = idx === state.activeWeek;
    b.className = active
      ? "rounded-2xl px-3 py-2 border border-line bg-ink text-white text-sm font-semibold"
      : "rounded-2xl px-3 py-2 border border-line bg-white hover:bg-gray-50 text-sm";
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
    <div class="text-xs text-gray-600 mt-1">Regole gruppo: ${escapeHtml(rulesSummaryText().replaceAll("\n"," • "))}</div>
  `;
  header.appendChild(hleft);

  const daysWrap = document.createElement("div");
  daysWrap.className = "mt-4 space-y-3";

  week.days.forEach((d, idx)=>{
    const date = addDays(weekStart, idx);

    const card = document.createElement("div");
    card.className = "rounded-3xl border border-line bg-white shadow-sm overflow-hidden";

    // top
    const top = document.createElement("div");
    top.className = "px-5 py-4 bg-slate-50 border-b border-line";

    const badges = [];

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
          <div class="text-xs text-gray-600 mt-1">${d.locked ? "bloccato" : "libero"}</div>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2">
          ${badges.join("")}
        </div>
      </div>
    `;

    // ✏️ Icona modifica (solo admin)
    if(isAdmin){
      const right = top.querySelector(".flex.flex-wrap.items-center.justify-end.gap-2");
      if(right){
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
    }

    card.appendChild(top);

    const body = document.createElement("div");
    body.className = "px-5 py-4 space-y-3";

    const mealBox = (title, value, dotCls)=>{
      const box = document.createElement("div");
      box.className = "rounded-2xl border border-line bg-white px-4 py-3";
      box.innerHTML = `
        <div class="flex items-center gap-2">
          <span class="inline-block h-2.5 w-2.5 rounded-full ${dotCls}"></span>
          <div class="text-xs font-semibold text-gray-700">${title}</div>
        </div>
        <div class="text-sm text-gray-800 mt-2 whitespace-pre-wrap">${escapeHtml(value || "—")}</div>
      `;
      return box;
    };

    body.appendChild(mealBox("Pranzo", d.lunch, "bg-accent"));
    body.appendChild(mealBox("Cena", d.dinner, "bg-accent2"));

    card.appendChild(body);
    daysWrap.appendChild(card);
  });

  content.innerHTML = "";
  content.appendChild(header);
  content.appendChild(daysWrap);

  bindAllTips(content);
}

/* =========================
   BOOT
   ========================= */
async function boot(){
  const me = await refreshMe();

  show($("loginSection"), !me);
  show($("appSection"), !!me);

  if(!me) return;

  const whoText = `${me.username} • ${me.role} • gruppo: ${me.group}`;
  if($("whoami")) $("whoami").textContent = whoText;
  if($("whoamiDesktop")) $("whoamiDesktop").textContent = whoText;
  if($("whoamiMobile")) $("whoamiMobile").textContent = whoText;

  const isAdmin = me.role === "admin";
  show($("adminUpload"), isAdmin);
  show($("adminUploadMobile"), isAdmin);
  show($("adminControls"), isAdmin);

  if(isAdmin && $("startMonday")){
    const now = new Date();
    const day = now.getDay(); // 0=dom
    const diffToMon = (day===0) ? 1 : (8 - day);
    const nextMon = addDays(now, diffToMon);
    $("startMonday").value = fmtISO(nextMon);
  }

  loading(true, "Avvio", "Carico dati…");
  await loadSettings();
  await loadPairs();
  await loadSaved();
  if(isAdmin) await loadGroupUsers();
  loading(false);

  setView(state.view || "plans");
  renderSettings();
  renderPlan();
}

/* =========================
   EVENTS
   ========================= */
document.addEventListener("DOMContentLoaded", ()=>{
  tipGlobalCloseHandlers();

  const btn = $("btnLogin");
if(btn){
  btn.onclick = (e) => {
    e.preventDefault();
    e.stopPropagation();
    login();
  };
}

  $("btnLogout")?.addEventListener("click", logout);
  $("btnLogoutMobile")?.addEventListener("click", logout);
  $("btnLogoutDrawer")?.addEventListener("click", logout);

  $("btnOpenSidebar")?.addEventListener("click", openDrawer);
  $("btnCloseSidebar")?.addEventListener("click", closeDrawer);
  $("sidebarDrawer")?.addEventListener("click", (e)=>{ if(e.target === $("sidebarDrawer")) closeDrawer(); });

  $("savedSearch")?.addEventListener("input", (e)=>{ state.savedFilter = e.target.value; renderSavedLists(); });
  $("savedSearchMobile")?.addEventListener("input", (e)=>{ state.savedFilter = e.target.value; renderSavedLists(); });

  $("btnUploadCsv")?.addEventListener("click", ()=>uploadCsv(false));
  $("btnUploadCsvMobile")?.addEventListener("click", ()=>uploadCsv(true));

  $("btnCreateUser")?.addEventListener("click", createUser);

  $("btnGenerate")?.addEventListener("click", generatePlan);
  $("btnSave")?.addEventListener("click", savePlan);
  $("btnClearAll")?.addEventListener("click", clearView);

  $("btnNavPlans")?.addEventListener("click", ()=>setView("plans"));
  $("btnNavSettings")?.addEventListener("click", ()=>setView("settings"));
  $("btnNavPlansMobile")?.addEventListener("click", ()=>{ setView("plans"); closeDrawer(); });
  $("btnNavSettingsMobile")?.addEventListener("click", ()=>{ setView("settings"); closeDrawer(); });

  $("btnSaveSettings")?.addEventListener("click", saveSettings);

  const usersToggle = document.getElementById("usersAccordionToggle");
  const usersBody = document.getElementById("adminUsers");
  const usersIcon = document.getElementById("usersAccordionIcon");
  if(usersToggle && usersBody){
    usersToggle.addEventListener("click", ()=>{
      usersBody.classList.toggle("hidden");
      usersIcon?.classList.toggle("rotate-180");
    });
  }

  boot();
});
