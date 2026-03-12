<?php
// index.php
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>MealAPP</title>
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
            peach:    "#E8C4A8",
            lavender: "#C8BCDA",
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

<body class="bg-surface text-ink font-sans antialiased">

  <!-- LOADING -->
  <div id="loading" class="hidden fixed inset-0 z-50 bg-ink/20 backdrop-blur-sm">
    <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[92%] max-w-md rounded-3xl bg-white p-6 shadow-warm-lg border border-line">
      <div id="loadingTitle" class="font-semibold text-lg text-ink">Caricamento</div>
      <div id="loadingMsg" class="text-sm text-muted mt-2">…</div>
    </div>
  </div>

  <!-- TOAST -->
  <div id="toast" class="hidden fixed bottom-4 right-4 z-50 w-[92%] max-w-sm">
    <div class="rounded-3xl border border-line bg-white shadow-warm-lg p-4 flex gap-3 items-start">
      <div id="toastDot" class="mt-1 h-2.5 w-2.5 rounded-full bg-success shrink-0"></div>
      <div class="min-w-0">
        <div id="toastTitle" class="text-sm font-semibold text-ink">OK</div>
        <div id="toastMsg" class="text-sm text-muted mt-0.5 break-words">…</div>
      </div>
    </div>
  </div>

  <!-- MODAL -->
  <div id="modalWrap" class="hidden fixed inset-0 z-50 bg-ink/20 backdrop-blur-sm">
    <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[94%] max-w-lg rounded-3xl bg-white shadow-warm-lg border border-line">
      <div class="p-5 border-b border-line flex items-start justify-between gap-3">
        <div>
          <div id="modalTitle" class="font-semibold text-lg text-ink">Titolo</div>
          <div id="modalDesc" class="text-sm text-muted mt-1">Descrizione</div>
        </div>
        <button id="modalClose" class="rounded-2xl border border-line px-3 py-2 hover:bg-surface text-muted transition-colors">✕</button>
      </div>
      <div id="modalBody" class="p-5 text-sm text-ink"></div>
      <div class="p-5 border-t border-line flex justify-end gap-2">
        <button id="modalCancel" class="rounded-2xl border border-line px-4 py-2.5 hover:bg-surface text-ink transition-colors">Annulla</button>
        <button id="modalOk" class="rounded-2xl bg-ink text-white px-4 py-2.5 hover:opacity-90 transition-opacity">OK</button>
      </div>
    </div>
  </div>

  <!-- TOOLTIP -->
  <div id="tip" class="hidden fixed z-50 w-[92%] max-w-xs">
    <div class="rounded-3xl border border-line bg-white shadow-warm-lg p-4">
      <div id="tipTitle" class="text-sm font-semibold text-ink">Titolo</div>
      <div id="tipBody" class="text-sm text-muted mt-2 whitespace-pre-wrap">Testo</div>
    </div>
  </div>

  <!-- LOGIN -->
  <section id="loginSection" class="min-h-screen flex items-center justify-center p-5 bg-gradient-to-br from-surface via-[#F2EBE0] to-[#EDE3D6]">
    <div class="w-full max-w-md">

      <!-- Brand -->
      <div class="text-center mb-7">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-white shadow-warm border border-line mb-3 text-2xl">🍽️</div>
        <div class="text-2xl font-bold text-ink tracking-tight">MealAPP</div>
        <div class="text-sm text-muted mt-1">Pianificazione pasti del gruppo</div>
      </div>

      <!-- Card -->
      <div class="rounded-3xl bg-white shadow-warm-lg border border-line overflow-hidden">

        <!-- Tab switcher -->
        <div class="flex border-b border-line">
          <button id="btnTabLogin"
            class="flex-1 py-3.5 text-sm font-semibold text-ink border-b-2 border-ink transition-colors">
            🔐 Accedi
          </button>
          <button id="btnTabRegister"
            class="flex-1 py-3.5 text-sm font-medium text-muted border-b-2 border-transparent hover:text-ink transition-colors">
            📝 Registrati
          </button>
        </div>

        <!-- FORM LOGIN -->
        <div id="loginFormSection" class="p-7">
          <div class="space-y-3">
            <input id="loginUser"
              class="w-full rounded-2xl border border-line px-4 py-3 outline-none focus:ring-2 focus:ring-accent/40 bg-surface/50 text-ink placeholder:text-muted text-sm"
              placeholder="Username" autocomplete="username"/>
            <input id="loginPass" type="password"
              class="w-full rounded-2xl border border-line px-4 py-3 outline-none focus:ring-2 focus:ring-accent/40 bg-surface/50 text-ink placeholder:text-muted text-sm"
              placeholder="Password" autocomplete="current-password"/>
            <button id="btnLogin" type="button"
              class="w-full rounded-2xl bg-ink text-white py-3 font-semibold hover:opacity-90 transition-opacity text-sm">
              Entra
            </button>
            <div id="loginStatus" class="text-sm text-danger min-h-[18px]"></div>
          </div>
        </div>

        <!-- FORM REGISTRAZIONE -->
        <div id="registerFormSection" class="hidden p-7">
          <div class="space-y-4">

            <!-- Username -->
            <div>
              <label class="block text-xs font-semibold text-muted uppercase tracking-wide mb-1.5">Username</label>
              <input id="regUsername"
                class="w-full rounded-2xl border border-line px-4 py-3 outline-none focus:ring-2 focus:ring-accent/40 bg-surface/50 text-ink placeholder:text-muted text-sm"
                placeholder="es. mario_rossi" autocomplete="username" maxlength="30"/>
              <div class="text-[11px] text-muted mt-1.5">3–30 caratteri · lettere, numeri, punto, trattino, underscore</div>
            </div>

            <!-- Password -->
            <div>
              <label class="block text-xs font-semibold text-muted uppercase tracking-wide mb-1.5">Password</label>
              <input id="regPassword" type="password"
                class="w-full rounded-2xl border border-line px-4 py-3 outline-none focus:ring-2 focus:ring-accent/40 bg-surface/50 text-ink placeholder:text-muted text-sm"
                placeholder="Minimo 8 caratteri" autocomplete="new-password"/>
              <div class="text-[11px] text-muted mt-1.5">Almeno 8 caratteri · usa lettere maiuscole, numeri e simboli per maggiore sicurezza</div>
            </div>

            <!-- Conferma password -->
            <div>
              <label class="block text-xs font-semibold text-muted uppercase tracking-wide mb-1.5">Conferma password</label>
              <input id="regConfirm" type="password"
                class="w-full rounded-2xl border border-line px-4 py-3 outline-none focus:ring-2 focus:ring-accent/40 bg-surface/50 text-ink placeholder:text-muted text-sm"
                placeholder="Ripeti la password" autocomplete="new-password"/>
            </div>

            <!-- Codice invito -->
            <div>
              <label class="block text-xs font-semibold text-muted uppercase tracking-wide mb-1.5">Codice invito gruppo</label>
              <input id="regInviteCode"
                class="w-full rounded-2xl border border-line px-4 py-3 outline-none focus:ring-2 focus:ring-accent/40 bg-surface/50 text-ink placeholder:text-muted text-sm font-mono tracking-widest uppercase"
                placeholder="es. A3F2B891" maxlength="20" autocomplete="off"/>
              <div class="mt-2 rounded-2xl bg-surface border border-line px-3 py-2.5 flex gap-2">
                <span class="text-muted shrink-0 text-sm">ℹ️</span>
                <div class="text-[11px] text-muted leading-relaxed">Il codice invito viene fornito dall'admin del tuo gruppo. Senza di esso non è possibile registrarsi.</div>
              </div>
            </div>

            <button id="btnRegister" type="button"
              class="w-full rounded-2xl bg-ink text-white py-3 font-semibold hover:opacity-90 transition-opacity text-sm">
              Crea account
            </button>

            <div id="registerStatus" class="text-sm min-h-[18px]"></div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- APP -->
  <section id="appSection" class="hidden min-h-screen">

    <!-- TOPBAR MOBILE -->
    <div class="lg:hidden sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-line shadow-warm">
      <div class="px-4 py-2.5 flex items-center justify-between gap-3">
        <button id="btnOpenSidebar" class="rounded-2xl border border-line px-3 py-2 hover:bg-surface text-ink transition-colors">☰</button>
        <div id="whoamiMobile" class="text-xs text-muted truncate min-w-0 flex-1 text-center">—</div>
        <button id="btnLogoutMobile" class="rounded-2xl border border-line px-3 py-2 hover:bg-surface text-muted text-sm transition-colors">Esci</button>
      </div>
    </div>

    <!-- DRAWER MOBILE -->
    <div id="sidebarDrawer" class="hidden fixed inset-0 z-50 bg-ink/20 backdrop-blur-sm">
      <div class="absolute left-0 top-0 h-full w-[86%] max-w-sm bg-white border-r border-line shadow-warm-lg flex flex-col">
        <div class="relative bg-gradient-to-r from-[#2C1F14] to-[#3A2518] px-4 py-4 flex items-center justify-between gap-3 overflow-hidden">
          <div class="absolute right-0 top-0 w-24 h-full pointer-events-none">
            <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full bg-peach/10"></div>
          </div>
          <div class="relative flex items-center gap-3 min-w-0">
            <div class="w-8 h-8 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center text-base shrink-0">🍽️</div>
            <div class="min-w-0">
              <div class="text-sm font-bold text-white">MealAPP</div>
              <div id="whoami" class="text-xs text-white/50 truncate">—</div>
            </div>
          </div>
          <button id="btnCloseSidebar" class="relative rounded-2xl border border-white/20 px-3 py-2 hover:bg-white/10 text-white/70 transition-colors shrink-0">✕</button>
        </div>

        <div class="p-4 space-y-4 overflow-auto flex-1">
          <div class="flex flex-col gap-2">
            <button id="btnNavPlansMobile" class="rounded-2xl px-3 py-2.5 border border-line bg-ink text-white font-semibold text-sm">🗓️ Piani</button>
            <button id="btnNavSettingsMobile" class="rounded-2xl px-3 py-2.5 border border-line bg-white hover:bg-surface text-ink text-sm transition-colors">⚙️ Impostazioni</button>
            <button id="btnNavSuperadminMobile" class="hidden rounded-2xl px-3 py-2.5 border border-line bg-white hover:bg-surface text-ink text-sm transition-colors">🔧 Pannello Sistema</button>
          </div>

          <div class="rounded-3xl border border-line bg-white p-4 shadow-warm">
            <div class="flex items-center justify-between mb-3">
              <div class="font-semibold text-sm text-ink">📂 Piani salvati</div>
              <span class="text-xs text-muted bg-surface px-2 py-0.5 rounded-full border border-line"><span id="savedCountMobile">0</span></span>
            </div>
            <input id="savedSearchMobile"
              class="w-full rounded-2xl border border-line px-3 py-2.5 outline-none focus:ring-2 focus:ring-accent/40 bg-surface/50 text-ink placeholder:text-muted text-sm"
              placeholder="Cerca piano…"/>
            <div id="savedListMobile" class="mt-3 space-y-2"></div>
          </div>

          <button id="btnLogoutDrawer" class="w-full rounded-2xl border border-line py-2.5 hover:bg-surface text-muted text-sm transition-colors">Esci</button>
        </div>
      </div>
    </div>

    <!-- BRAND HEADER -->
    <div class="max-w-7xl mx-auto px-4 pt-4 lg:px-6 lg:pt-6">
      <div class="rounded-3xl overflow-hidden shadow-warm-lg">
        <div class="relative bg-gradient-to-r from-[#2C1F14] via-[#3A2518] to-[#2C1F14] px-5 py-4 lg:px-7 lg:py-5 flex items-center justify-between gap-4">
          <!-- Decorative blobs -->
          <div class="absolute inset-0 overflow-hidden pointer-events-none select-none">
            <div class="absolute -right-6 -top-10 w-44 h-44 rounded-full bg-peach/10"></div>
            <div class="absolute right-32 -bottom-6 w-28 h-28 rounded-full bg-accent/10"></div>
            <div class="absolute right-56 -top-4 w-16 h-16 rounded-full bg-accent2/10"></div>
          </div>
          <!-- Brand -->
          <div class="relative flex items-center gap-3 lg:gap-4 min-w-0">
            <div class="shrink-0 w-10 h-10 lg:w-12 lg:h-12 rounded-2xl bg-white/10 border border-white/15 flex items-center justify-center text-xl lg:text-2xl">
              🍽️
            </div>
            <div>
              <div class="text-lg lg:text-2xl font-bold text-white tracking-tight leading-none">MealAPP</div>
              <div class="text-[10px] lg:text-[11px] text-white/40 mt-1 font-semibold tracking-[0.15em] uppercase">Pianificazione pasti</div>
            </div>
          </div>
          <!-- User info (desktop) -->
          <div class="relative hidden lg:block text-right shrink-0">
            <div id="whoamiDesktop" class="text-xs text-white/55 leading-relaxed">—</div>
          </div>
        </div>
      </div>
    </div>

    <!-- LAYOUT -->
    <div class="max-w-7xl mx-auto p-4 lg:p-6 grid grid-cols-12 gap-4 lg:gap-6">

      <!-- SIDEBAR (solo desktop) -->
      <aside id="mainSidebar" class="hidden lg:block lg:col-span-4 xl:col-span-3 space-y-4">
        <div class="rounded-3xl border border-line bg-white p-5 shadow-warm">
          <div class="flex flex-col gap-2">
            <button id="btnNavPlans" class="rounded-2xl px-3 py-2.5 border border-line bg-ink text-white font-semibold text-sm text-left transition-opacity hover:opacity-90">🗓️ Piani</button>
            <button id="btnNavSettings" class="rounded-2xl px-3 py-2.5 border border-line bg-white hover:bg-surface text-ink text-sm text-left transition-colors">⚙️ Impostazioni</button>
            <button id="btnNavSuperadmin" class="hidden rounded-2xl px-3 py-2.5 border border-line bg-white hover:bg-surface text-ink text-sm text-left transition-colors">🔧 Pannello Sistema</button>
          </div>
          <button id="btnLogout" class="mt-3 w-full rounded-2xl border border-line py-2.5 hover:bg-surface text-muted text-sm transition-colors">Esci</button>
        </div>

        <div class="rounded-3xl border border-line bg-white p-5 shadow-warm">
          <div class="flex items-center justify-between mb-3">
            <div class="font-semibold text-sm text-ink">📂 Piani salvati</div>
            <span class="text-xs text-muted bg-surface px-2 py-0.5 rounded-full border border-line"><span id="savedCount">0</span></span>
          </div>
          <input id="savedSearch"
            class="w-full rounded-2xl border border-line px-3 py-2.5 outline-none focus:ring-2 focus:ring-accent/40 bg-surface/50 text-ink placeholder:text-muted text-sm"
            placeholder="Cerca piano…"/>
          <div id="savedList" class="mt-3 space-y-2"></div>
        </div>
      </aside>

      <!-- MAIN -->
      <main class="col-span-12 lg:col-span-8 xl:col-span-9">

        <!-- BANNER SUPERADMIN: stai gestendo gruppo X -->
        <div id="groupSwitcherBanner" class="hidden mb-4 rounded-3xl border border-accent2/40 bg-accent2/10 px-5 py-3 flex items-center justify-between gap-3 shadow-warm">
          <div class="flex items-center gap-2 min-w-0">
            <span class="text-base shrink-0">🔧</span>
            <div class="text-sm font-semibold text-ink">Stai gestendo: <span id="activeGroupLabel" class="font-bold">—</span></div>
          </div>
          <button onclick="exitGroupManagement()"
            class="shrink-0 rounded-2xl border border-line bg-white hover:bg-surface px-4 py-2 text-xs font-semibold text-ink transition-colors">
            ← Torna ai gruppi
          </button>
        </div>

        <!-- VIEW: PANNELLO SUPERADMIN -->
        <div id="superadminView" class="hidden space-y-6">

          <div class="rounded-3xl border border-line bg-white p-6 shadow-warm">
            <div class="flex items-center gap-3 mb-1">
              <span class="text-2xl">🔧</span>
              <div>
                <div class="text-xl font-bold text-ink">🔧 Pannello di Sistema</div>
                <div class="text-xs text-muted mt-0.5">Gestione globale di tutti i gruppi e utenti</div>
              </div>
            </div>
          </div>

          <!-- LISTA GRUPPI -->
          <div class="rounded-3xl border border-line bg-white p-5 shadow-warm">
            <div class="flex items-center justify-between gap-3 mb-4">
              <div class="text-sm font-bold text-ink">🏢 Gruppi esistenti</div>
              <button onclick="renderSuperadminDashboard()"
                class="rounded-2xl border border-line px-3 py-1.5 text-xs text-muted hover:bg-surface transition-colors">
                ↺ Aggiorna
              </button>
            </div>
            <div id="superadminGroupsGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
              <div class="text-sm text-muted">Caricamento…</div>
            </div>
          </div>

          <!-- CREA NUOVO GRUPPO -->
          <div class="rounded-3xl border border-line bg-white p-5 shadow-warm">
            <div class="text-sm font-bold text-ink mb-1">➕ Crea nuovo gruppo</div>
            <div class="text-xs text-muted mb-4">Crea un gruppo assegnandogli subito il primo admin.</div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
              <input id="newGroupName"
                class="rounded-2xl border border-line px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-accent/40 bg-surface/50 text-ink placeholder:text-muted uppercase"
                placeholder="Nome gruppo (es. A)"
                oninput="this.value=this.value.toUpperCase()"/>
              <input id="newGroupAdminUser"
                class="rounded-2xl border border-line px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-accent/40 bg-surface/50 text-ink placeholder:text-muted"
                placeholder="Username admin"/>
              <input id="newGroupAdminPass" type="password"
                class="rounded-2xl border border-line px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-accent/40 bg-surface/50 text-ink placeholder:text-muted"
                placeholder="Password admin"/>
            </div>
            <div class="mt-3 flex items-center gap-3 flex-wrap">
              <button onclick="createGroup()"
                class="rounded-2xl bg-ink text-white px-4 py-2.5 text-sm font-semibold hover:opacity-90 transition-opacity">
                Crea gruppo
              </button>
              <div id="createGroupStatus" class="text-xs text-muted min-h-[16px]">—</div>
            </div>
          </div>

          <!-- LOGOUT -->
          <div class="flex justify-end">
            <button onclick="logout()"
              class="rounded-2xl border border-line px-4 py-2.5 text-sm text-muted hover:bg-surface transition-colors">
              Esci
            </button>
          </div>

        </div>

        <!-- VIEW: PIANI -->
        <div id="plansView" class="space-y-4">

          <!-- BANNER OGGI -->
          <div id="todayBanner" class="hidden rounded-3xl border border-success/30 bg-white shadow-warm overflow-hidden">
            <div class="bg-gradient-to-r from-success/10 via-accent/10 to-surface px-5 py-3 border-b border-line flex items-center justify-between gap-3">
              <div class="flex items-center gap-2 min-w-0">
                <span class="text-base shrink-0">📅</span>
                <div class="min-w-0">
                  <div class="text-sm font-bold text-ink truncate" id="todayBannerDate">—</div>
                  <div class="text-[10px] text-muted font-semibold uppercase tracking-wider">Piano di oggi</div>
                </div>
              </div>
              <span class="shrink-0 text-[11px] px-2.5 py-1 rounded-full bg-success/20 border border-success/30 text-success font-semibold">Oggi</span>
            </div>
            <div class="px-5 py-4 grid grid-cols-2 gap-3">
              <div class="rounded-2xl border border-peach/40 bg-peach/10 px-4 py-3">
                <div class="flex items-center gap-1.5 mb-1.5">
                  <span class="text-xs">🌞</span>
                  <div class="text-[11px] font-semibold text-muted uppercase tracking-wide">Pranzo</div>
                </div>
                <div id="todayLunch" class="text-sm font-semibold text-ink leading-snug">—</div>
              </div>
              <div class="rounded-2xl border border-accent2/40 bg-accent2/10 px-4 py-3">
                <div class="flex items-center gap-1.5 mb-1.5">
                  <span class="text-xs">🌙</span>
                  <div class="text-[11px] font-semibold text-muted uppercase tracking-wide">Cena</div>
                </div>
                <div id="todayDinner" class="text-sm font-semibold text-ink leading-snug">—</div>
              </div>
            </div>
          </div>

          <!-- AVVISO PIANO IN SCADENZA (solo admin) -->
          <div id="expiryBanner" class="hidden rounded-3xl border border-warn/40 bg-warn/5 p-4 shadow-warm">
            <div class="flex items-start gap-3">
              <span class="text-xl shrink-0">⚠️</span>
              <div class="min-w-0 flex-1">
                <div id="expiryMsg" class="text-sm font-semibold text-ink">—</div>
                <div class="text-xs text-muted mt-1">Genera un nuovo piano per coprire le settimane successive.</div>
              </div>
              <button id="btnGenerateNext"
                class="shrink-0 rounded-2xl bg-warn text-white px-4 py-2 text-xs font-semibold hover:opacity-90 transition-opacity">
                Genera ora
              </button>
            </div>
          </div>

          <div id="adminControls" class="hidden rounded-3xl border border-line bg-white p-5 shadow-warm">
            <div class="flex flex-wrap items-end gap-3 justify-between">
              <div class="min-w-[200px]">
                <div class="text-sm font-bold text-ink">✨ Genera piano</div>
                <div class="text-xs text-muted mt-1">Seleziona il lunedì di partenza e il numero di settimane.</div>
                <div id="seasonNote" class="text-xs text-accent2 mt-1 font-medium"></div>
              </div>
              <div class="flex flex-wrap items-end gap-3">
                <div>
                  <div class="text-xs text-muted mb-1 font-medium">Lunedì</div>
                  <input id="startMonday" type="date"
                    class="rounded-2xl border border-line px-3 py-2.5 text-sm text-ink bg-surface/50 outline-none focus:ring-2 focus:ring-accent/40"/>
                </div>
                <div>
                  <div class="text-xs text-muted mb-1 font-medium">Settimane</div>
                  <select id="numWeeks"
                    class="rounded-2xl border border-line px-3 py-2.5 text-sm text-ink bg-surface/50 outline-none focus:ring-2 focus:ring-accent/40">
                    <option>1</option><option>2</option><option>3</option><option>4</option>
                  </select>
                </div>
                <button id="btnGenerate"
                  class="rounded-2xl bg-ink text-white px-4 py-2.5 text-sm font-semibold hover:opacity-90 transition-opacity">
                  Genera
                </button>
                <button id="btnSave"
                  class="rounded-2xl border border-line bg-white px-4 py-2.5 text-sm hover:bg-surface transition-colors">
                  Salva
                </button>
                <span id="dirtyIndicator" class="hidden text-xs text-warn font-semibold self-center">● non salvato</span>
              </div>
            </div>
            <div class="text-xs text-muted mt-3 min-h-[16px]"><span id="status">—</span></div>
          </div>

          <div class="rounded-3xl border border-line bg-white p-5 shadow-warm">
            <div class="flex items-center justify-between gap-3 flex-wrap">
              <div>
                <div class="text-sm font-bold text-ink">📅 Piano corrente</div>
                <div id="currentLabel" class="text-xs text-muted mt-1">—</div>
              </div>
              <div class="flex items-center gap-2 flex-wrap">
                <button id="btnCopyPlan" class="rounded-2xl border border-line px-4 py-2 hover:bg-surface text-sm text-ink transition-colors">Copia</button>
                <button id="btnPrintPlan" class="rounded-2xl border border-line px-4 py-2 hover:bg-surface text-sm text-ink transition-colors">Stampa</button>
                <button id="btnClearAll" class="rounded-2xl border border-line px-4 py-2 hover:bg-surface text-sm text-muted transition-colors">Pulisci</button>
              </div>
            </div>

            <div id="weekTabs" class="mt-4 flex flex-wrap gap-2"></div>
            <div id="weekContent" class="mt-4"></div>
          </div>
        </div>

        <!-- VIEW: IMPOSTAZIONI -->
        <div id="settingsView" class="hidden space-y-4">

          <div class="rounded-3xl border border-line bg-white shadow-warm overflow-hidden">
            <button id="settingsRulesToggle" class="w-full flex items-center justify-between gap-3 p-5 text-left">
              <div class="flex items-center gap-2">
                <span>⚙️</span>
                <div class="text-sm font-bold text-ink">⚙️ Impostazioni gruppo</div>
              </div>
              <div id="settingsRulesIcon" class="transition-transform text-muted text-sm shrink-0">▾</div>
            </button>

            <div id="settingsRulesBody" class="hidden px-5 pb-5">
              <div class="flex items-center justify-between gap-3 mb-5">
                <div class="text-xs text-muted">Gli utenti vedono le impostazioni, solo gli admin possono modificarle.</div>
                <button id="btnSaveSettings" class="hidden rounded-2xl bg-ink text-white px-4 py-2.5 text-sm font-semibold hover:opacity-90 transition-opacity shrink-0">Salva</button>
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Pizza -->
                <div class="rounded-3xl border border-line bg-surface/60 p-4">
                  <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                      <span>🍕</span>
                      <div class="text-sm font-semibold text-ink">Regola Pizza</div>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input id="pizzaEnabled" type="checkbox" class="scale-110 accent-ink">
                      <span class="text-xs text-muted">Attiva</span>
                    </label>
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <div>
                      <div class="text-xs text-muted mb-1 font-medium">Giorno</div>
                      <select id="pizzaDay" class="w-full rounded-2xl border border-line px-3 py-2 text-sm bg-white text-ink outline-none focus:ring-2 focus:ring-accent/40">
                        <option value="0">Lun</option><option value="1">Mar</option><option value="2">Mer</option>
                        <option value="3">Gio</option><option value="4">Ven</option><option value="5">Sab</option><option value="6">Dom</option>
                      </select>
                    </div>
                    <div>
                      <div class="text-xs text-muted mb-1 font-medium">Pasto</div>
                      <select id="pizzaMeal" class="w-full rounded-2xl border border-line px-3 py-2 text-sm bg-white text-ink outline-none focus:ring-2 focus:ring-accent/40">
                        <option value="dinner">Cena</option>
                        <option value="lunch">Pranzo</option>
                      </select>
                    </div>
                    <div>
                      <div class="text-xs text-muted mb-1 font-medium">Testo</div>
                      <input id="pizzaText" class="w-full rounded-2xl border border-line px-3 py-2 text-sm bg-white text-ink outline-none focus:ring-2 focus:ring-accent/40" placeholder="Pizza…"/>
                    </div>
                  </div>
                </div>

                <!-- Libero -->
                <div class="rounded-3xl border border-line bg-surface/60 p-4">
                  <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                      <span>🌿</span>
                      <div class="text-sm font-semibold text-ink">Pasto Libero</div>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input id="freeEnabled" type="checkbox" class="scale-110 accent-ink">
                      <span class="text-xs text-muted">Attiva</span>
                    </label>
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <div>
                      <div class="text-xs text-muted mb-1 font-medium">Giorno</div>
                      <select id="freeDay" class="w-full rounded-2xl border border-line px-3 py-2 text-sm bg-white text-ink outline-none focus:ring-2 focus:ring-accent/40">
                        <option value="0">Lun</option><option value="1">Mar</option><option value="2">Mer</option>
                        <option value="3">Gio</option><option value="4">Ven</option><option value="5">Sab</option><option value="6">Dom</option>
                      </select>
                    </div>
                    <div>
                      <div class="text-xs text-muted mb-1 font-medium">Pasto</div>
                      <select id="freeMeal" class="w-full rounded-2xl border border-line px-3 py-2 text-sm bg-white text-ink outline-none focus:ring-2 focus:ring-accent/40">
                        <option value="lunch">Pranzo</option>
                        <option value="dinner">Cena</option>
                      </select>
                    </div>
                    <div>
                      <div class="text-xs text-muted mb-1 font-medium">Testo</div>
                      <input id="freeText" class="w-full rounded-2xl border border-line px-3 py-2 text-sm bg-white text-ink outline-none focus:ring-2 focus:ring-accent/40" placeholder="LIBERO"/>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-4 rounded-3xl border border-line bg-surface/50 p-4">
                <div class="text-[11px] font-semibold text-muted uppercase tracking-wider mb-2">📝 Riepilogo regole</div>
                <div id="settingsSummary" class="text-sm text-ink whitespace-pre-wrap">—</div>
                <div id="settingsStatus" class="text-xs text-muted mt-2 min-h-[16px]">—</div>
              </div>

              <!-- Codice invito (solo admin) -->
              <div id="inviteCodeSection" class="hidden mt-4 rounded-3xl border border-accent/30 bg-accent/5 p-4">
                <div class="text-[11px] font-semibold text-muted uppercase tracking-wider mb-3">🎫 Codice invito gruppo</div>
                <div class="flex items-center gap-3 flex-wrap">
                  <div class="flex-1 min-w-[160px] rounded-2xl border border-line bg-white px-4 py-2.5 font-mono text-lg font-bold text-ink tracking-[0.2em]" id="inviteCodeDisplay">—</div>
                  <button id="btnCopyInviteCode"
                    class="rounded-2xl border border-accent/50 bg-accent/15 text-ink px-4 py-2.5 text-sm hover:bg-accent/25 transition-colors">
                    Copia
                  </button>
                  <button id="btnRegenInviteCode"
                    class="rounded-2xl border border-line bg-white text-muted px-4 py-2.5 text-sm hover:bg-surface transition-colors">
                    Rigenera
                  </button>
                </div>
                <div class="text-[11px] text-muted mt-3">Condividi questo codice con le persone che vuoi far registrare nel tuo gruppo. Puoi rigenerarlo in qualsiasi momento per invalidare quello vecchio.</div>
              </div>
            </div>
          </div>

          <!-- Piano Alimentare (solo admin) -->
          <div id="csvSettingsSection" class="hidden rounded-3xl border border-line bg-white shadow-warm overflow-hidden">
            <button id="csvSettingsToggle" class="w-full flex items-center justify-between gap-3 p-5 text-left">
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <span>📄</span>
                  <div class="text-sm font-bold text-ink">🍽️ Piano Alimentare</div>
                </div>
                <div id="csvCurrentStatus" class="text-xs text-muted mt-1">—</div>
              </div>
              <div id="csvSettingsIcon" class="transition-transform text-muted text-sm shrink-0">▾</div>
            </button>

            <div id="csvSettingsBody" class="hidden px-5 pb-5">
              <div class="flex items-center justify-between gap-3 flex-wrap mb-4">
                <div class="text-xs text-muted">Il Piano Alimentare è condiviso con tutti gli utenti del gruppo.</div>
                <a href="csv_viewer.php" target="_blank"
                   class="rounded-2xl border border-accent/50 bg-accent/15 text-ink px-4 py-2 text-sm hover:bg-accent/25 transition-colors shrink-0">
                  Visualizza / Modifica
                </a>
              </div>
              <div class="space-y-3">
                <input id="csvUploadSettings" type="file" accept=".csv"
                  class="block w-full text-sm text-muted file:mr-3 file:rounded-xl file:border file:border-line file:bg-surface file:px-3 file:py-1.5 file:text-xs file:text-ink file:cursor-pointer"/>
                <button id="btnUploadCsvSettings"
                  class="rounded-2xl bg-ink text-white px-4 py-2.5 text-sm font-semibold hover:opacity-90 transition-opacity">
                  Carica Piano Alimentare
                </button>
                <div id="csvStatusSettings" class="text-xs text-muted min-h-[16px]">—</div>
              </div>
            </div>
          </div>

          <!-- Piano Alimentare (utenti non-admin) -->
          <div id="csvViewSection" class="hidden rounded-3xl border border-line bg-white p-5 shadow-warm">
            <div class="flex items-center justify-between gap-3 flex-wrap">
              <div>
                <div class="text-sm font-bold text-ink">🍽️ Piano Alimentare</div>
                <div id="csvCurrentStatusUser" class="text-xs text-muted mt-1">—</div>
              </div>
              <a href="csv_viewer.php" target="_blank"
                 class="rounded-2xl border border-accent/50 bg-accent/15 text-ink px-4 py-2 text-sm hover:bg-accent/25 transition-colors shrink-0">
                Visualizza
              </a>
            </div>
          </div>

          <!-- GESTIONE UTENTI (solo admin) -->
          <div class="rounded-3xl border border-line bg-white p-5 shadow-warm">
            <button id="usersAccordionToggle" class="w-full flex items-center justify-between gap-3">
              <div class="flex items-center gap-2">
                <span>👥</span>
                <div class="text-sm font-bold text-ink">👥 Gestione utenti</div>
              </div>
              <div id="usersAccordionIcon" class="transition-transform text-muted text-sm">▾</div>
            </button>

            <div id="adminUsers" class="hidden mt-4">
              <div class="text-xs text-muted mb-4">Crea o elimina utenti del tuo gruppo.</div>

              <div class="grid grid-cols-1 lg:grid-cols-3 gap-2">
                <input id="newUserName"
                  class="rounded-2xl border border-line px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-accent/40 bg-surface/50 text-ink placeholder:text-muted"
                  placeholder="Username"/>
                <input id="newUserPass" type="password"
                  class="rounded-2xl border border-line px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-accent/40 bg-surface/50 text-ink placeholder:text-muted"
                  placeholder="Password"/>
                <select id="newUserRole"
                  class="rounded-2xl border border-line px-4 py-2.5 text-sm bg-surface/50 text-ink outline-none focus:ring-2 focus:ring-accent/40">
                  <option value="user">user</option>
                  <option value="admin">admin</option>
                </select>
              </div>

              <div class="mt-3 flex items-center gap-3 flex-wrap">
                <button id="btnCreateUser"
                  class="rounded-2xl bg-ink text-white px-4 py-2.5 text-sm font-semibold hover:opacity-90 transition-opacity">
                  Crea utente
                </button>
                <div id="userCreateStatus" class="text-xs text-muted min-h-[16px]">—</div>
              </div>

              <div class="mt-5">
                <div class="text-[11px] font-semibold text-muted uppercase tracking-wider mb-3">Utenti del gruppo</div>
                <div id="groupUsersList" class="space-y-2"></div>
              </div>
            </div>
          </div>

          <!-- CAMBIO PASSWORD -->
          <div class="rounded-3xl border border-line bg-white shadow-warm overflow-hidden">
            <button id="cpToggle" class="w-full flex items-center justify-between gap-3 p-5 text-left">
              <div class="flex items-center gap-2">
                <span>🔑</span>
                <div class="text-sm font-bold text-ink">🔑 Cambia password</div>
              </div>
              <div id="cpIcon" class="transition-transform text-muted text-sm shrink-0">▾</div>
            </button>

            <div id="cpBody" class="hidden px-5 pb-5">
              <div class="text-xs text-muted mb-4">Modifica la tua password personale.</div>
              <div class="space-y-3">
                <input id="cpCurrent" type="password"
                  class="w-full rounded-2xl border border-line px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-accent/40 bg-surface/50 text-ink placeholder:text-muted"
                  placeholder="Password attuale"/>
                <input id="cpNew" type="password"
                  class="w-full rounded-2xl border border-line px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-accent/40 bg-surface/50 text-ink placeholder:text-muted"
                  placeholder="Nuova password (min 8 caratteri)"/>
                <input id="cpConfirm" type="password"
                  class="w-full rounded-2xl border border-line px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-accent/40 bg-surface/50 text-ink placeholder:text-muted"
                  placeholder="Conferma nuova password"/>
                <div class="flex items-center gap-3 flex-wrap">
                  <button id="btnChangePassword"
                    class="rounded-2xl bg-ink text-white px-4 py-2.5 text-sm font-semibold hover:opacity-90 transition-opacity">
                    Aggiorna password
                  </button>
                  <div id="cpStatus" class="text-xs text-muted min-h-[16px]">—</div>
                </div>
              </div>
            </div>
          </div>

          <!-- STATISTICHE POOL -->
          <div class="rounded-3xl border border-line bg-white shadow-warm overflow-hidden">
            <button id="statsToggle" class="w-full flex items-center justify-between gap-3 p-5 text-left">
              <div class="flex items-center gap-2">
                <span>📊</span>
                <div class="text-sm font-bold text-ink">📊 Statistiche pasti</div>
              </div>
              <div id="statsIcon" class="transition-transform text-muted text-sm shrink-0">▾</div>
            </button>
            <div id="statsBody" class="hidden px-5 pb-5">
              <div id="statsContent" class="text-sm text-muted">Apri per caricare i dati.</div>
            </div>
          </div>

          <!-- LOG ATTIVITÀ (solo admin) -->
          <div id="logSection" class="hidden rounded-3xl border border-line bg-white shadow-warm overflow-hidden">
            <button id="logToggle" class="w-full flex items-center justify-between gap-3 p-5 text-left">
              <div class="flex items-center gap-2">
                <span>📋</span>
                <div class="text-sm font-bold text-ink">📋 Log attività</div>
              </div>
              <div id="logIcon" class="transition-transform text-muted text-sm shrink-0">▾</div>
            </button>
            <div id="logBody" class="hidden px-5 pb-5">
              <div id="logContent" class="text-sm text-muted">Apri per caricare il log.</div>
            </div>
          </div>

          <!-- BACKUP / RESTORE (solo admin) -->
          <div id="backupSection" class="hidden rounded-3xl border border-line bg-white shadow-warm overflow-hidden">
            <button id="backupToggle" class="w-full flex items-center justify-between gap-3 p-5 text-left">
              <div class="flex items-center gap-2">
                <span>💾</span>
                <div class="text-sm font-bold text-ink">💾 Backup e ripristino</div>
              </div>
              <div id="backupIcon" class="transition-transform text-muted text-sm shrink-0">▾</div>
            </button>
            <div id="backupBody" class="hidden px-5 pb-5 space-y-5">
              <div class="text-xs text-muted">Il backup include tutti i piani salvati, il file CSV delle coppie e le impostazioni del gruppo.</div>

              <!-- Scarica backup -->
              <div class="rounded-3xl border border-line bg-surface/60 p-4">
                <div class="text-[11px] font-semibold text-muted uppercase tracking-wider mb-3">📤 Esporta backup</div>
                <button id="btnDownloadBackup"
                  class="rounded-2xl bg-ink text-white px-4 py-2.5 text-sm font-semibold hover:opacity-90 transition-opacity">
                  Scarica backup
                </button>
                <div class="text-xs text-muted mt-2">Scarica un file JSON con tutti i dati del gruppo.</div>
              </div>

              <!-- Ripristina -->
              <div class="rounded-3xl border border-danger/20 bg-danger/5 p-4">
                <div class="text-[11px] font-semibold text-danger uppercase tracking-wider mb-3">📥 Ripristina da backup</div>
                <input id="restoreFile" type="file" accept=".json"
                  class="block w-full text-sm text-muted file:mr-3 file:rounded-xl file:border file:border-line file:bg-surface file:px-3 file:py-1.5 file:text-xs file:text-ink file:cursor-pointer mb-3"/>
                <button id="btnRestoreBackup"
                  class="rounded-2xl border border-danger/40 text-danger px-4 py-2.5 text-sm font-semibold hover:bg-danger/10 transition-colors">
                  Ripristina
                </button>
                <div id="restoreStatus" class="text-xs text-muted mt-2 min-h-[16px]">—</div>
                <div class="text-xs text-muted mt-2">I piani già esistenti non vengono sovrascritti. CSV e impostazioni vengono sostituiti.</div>
              </div>
            </div>
          </div>

        </div>
      </main>
    </div>
  </section>

  <script src="assets/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>
