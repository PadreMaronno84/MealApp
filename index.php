<?php
// index.php
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>MealAPP</title>

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            ink: "#111827",
            line: "#E5E7EB",
            accent: "#86EFAC",   // verde pastello
            accent2: "#93C5FD",  // azzurro pastello
            warn: "#FBBF24",
            danger: "#EF4444",
          }
        }
      }
    }
  </script>
</head>

<body class="bg-gradient-to-br from-slate-50 to-slate-100 text-ink">

  <!-- LOADING -->
  <div id="loading" class="hidden fixed inset-0 z-50 bg-black/30 backdrop-blur-sm">
    <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[92%] max-w-md rounded-3xl bg-white p-6 shadow-xl border border-line">
      <div id="loadingTitle" class="font-semibold text-lg">Caricamento</div>
      <div id="loadingMsg" class="text-sm text-gray-600 mt-2">…</div>
    </div>
  </div>

  <!-- TOAST -->
  <div id="toast" class="hidden fixed bottom-4 right-4 z-50 w-[92%] max-w-sm">
    <div class="rounded-3xl border border-line bg-white shadow-xl p-4 flex gap-3">
      <div id="toastDot" class="mt-1 h-2.5 w-2.5 rounded-full bg-accent"></div>
      <div class="min-w-0">
        <div id="toastTitle" class="text-sm font-semibold">OK</div>
        <div id="toastMsg" class="text-sm text-gray-700 mt-1 break-words">…</div>
      </div>
    </div>
  </div>

  <!-- MODAL -->
  <div id="modalWrap" class="hidden fixed inset-0 z-50 bg-black/30 backdrop-blur-sm">
    <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[94%] max-w-lg rounded-3xl bg-white shadow-xl border border-line">
      <div class="p-5 border-b border-line flex items-start justify-between gap-3">
        <div>
          <div id="modalTitle" class="font-semibold text-lg">Titolo</div>
          <div id="modalDesc" class="text-sm text-gray-600 mt-1">Descrizione</div>
        </div>
        <button id="modalClose" class="rounded-2xl border border-line px-3 py-2 hover:bg-gray-50">✕</button>
      </div>
      <div id="modalBody" class="p-5 text-sm text-gray-800"></div>
      <div class="p-5 border-t border-line flex justify-end gap-2">
        <button id="modalCancel" class="rounded-2xl border border-line px-4 py-2.5 hover:bg-gray-50">Annulla</button>
        <button id="modalOk" class="rounded-2xl bg-ink text-white px-4 py-2.5 hover:opacity-95">OK</button>
      </div>
    </div>
  </div>

  <!-- TOOLTIP (unico, usato da JS) -->
  <div id="tip" class="hidden fixed z-50 w-[92%] max-w-xs">
    <div class="rounded-3xl border border-line bg-white shadow-xl p-4">
      <div id="tipTitle" class="text-sm font-semibold">Titolo</div>
      <div id="tipBody" class="text-sm text-gray-700 mt-2 whitespace-pre-wrap">Testo</div>
    </div>
  </div>

  <!-- LOGIN -->
  <section id="loginSection" class="min-h-screen flex items-center justify-center p-5">
    <div class="w-full max-w-md rounded-3xl bg-white shadow-xl border border-line p-6">
      <div class="text-xl font-semibold">MealAPP</div>
      <div class="text-sm text-gray-600 mt-2">Accedi</div>

      <div class="mt-5 space-y-3">
        <input id="loginUser" class="w-full rounded-2xl border border-line px-4 py-3 outline-none focus:ring-2 focus:ring-accent/30" placeholder="Username"/>
        <input id="loginPass" type="password" class="w-full rounded-2xl border border-line px-4 py-3 outline-none focus:ring-2 focus:ring-accent/30" placeholder="Password"/>
        <button id="btnLogin" type="button" class="w-full rounded-2xl bg-ink text-white py-3 hover:opacity-95">Entra</button>
        <div id="loginStatus" class="text-sm text-danger min-h-[18px]"></div>
      </div>
    </div>
  </section>

  <!-- APP -->
  <section id="appSection" class="hidden min-h-screen">
    <!-- TOPBAR MOBILE -->
    <div class="lg:hidden sticky top-0 z-40 bg-white/80 backdrop-blur border-b border-line">
      <div class="px-4 py-3 flex items-center justify-between gap-3">
        <button id="btnOpenSidebar" class="rounded-2xl border border-line px-3 py-2 hover:bg-gray-50">☰</button>
        <div class="min-w-0">
          <div class="text-sm font-semibold">MealAPP</div>
          <div id="whoamiMobile" class="text-xs text-gray-600 truncate">—</div>
        </div>
        <button id="btnLogoutMobile" class="rounded-2xl border border-line px-3 py-2 hover:bg-gray-50">Logout</button>
      </div>
    </div>

    <!-- DRAWER MOBILE -->
    <div id="sidebarDrawer" class="hidden fixed inset-0 z-50 bg-black/30">
      <div class="absolute left-0 top-0 h-full w-[86%] max-w-sm bg-white border-r border-line shadow-xl">
        <div class="p-4 flex items-center justify-between gap-3 border-b border-line">
          <div class="min-w-0">
            <div class="text-sm font-semibold">Menu</div>
            <div id="whoami" class="text-xs text-gray-600 truncate">—</div>
          </div>
          <button id="btnCloseSidebar" class="rounded-2xl border border-line px-3 py-2 hover:bg-gray-50">✕</button>
        </div>

        <div class="p-4 space-y-4 overflow-auto h-[calc(100%-64px)]">
          <div class="grid grid-cols-2 gap-2">
            <button id="btnNavPlansMobile" class="rounded-2xl px-3 py-2.5 border border-line bg-ink text-white font-semibold">Piani</button>
            <button id="btnNavSettingsMobile" class="rounded-2xl px-3 py-2.5 border border-line bg-white hover:bg-gray-50">Impostazioni</button>
          </div>

          <div id="adminUploadMobile" class="hidden rounded-3xl border border-line bg-slate-50 p-4">
            <div class="font-semibold text-sm">Upload CSV</div>
            <div class="text-xs text-gray-600 mt-1">Carica il file delle coppie pranzo+cena.</div>
            <div class="mt-3 space-y-2">
              <input id="csvUploadMobile" type="file" accept=".csv" class="block w-full text-sm"/>
              <button id="btnUploadCsvMobile" class="w-full rounded-2xl bg-ink text-white py-2.5 hover:opacity-95">Carica CSV</button>
              <div id="csvStatusMobile" class="text-xs text-gray-600 min-h-[16px]">—</div>
            </div>
          </div>

          <div class="rounded-3xl border border-line bg-white p-4">
            <div class="flex items-center justify-between">
              <div class="font-semibold text-sm">Piani salvati</div>
              <div class="text-xs text-gray-600"><span id="savedCountMobile">0</span></div>
            </div>
            <input id="savedSearchMobile" class="mt-3 w-full rounded-2xl border border-line px-3 py-2.5 outline-none focus:ring-2 focus:ring-accent/30" placeholder="Cerca…"/>
            <div id="savedListMobile" class="mt-3 space-y-2"></div>
          </div>

          <button id="btnLogoutDrawer" class="w-full rounded-2xl border border-line py-2.5 hover:bg-gray-50">Logout</button>
        </div>
      </div>
    </div>

    <!-- LAYOUT DESKTOP -->
    <div class="hidden lg:block">
      <div class="max-w-7xl mx-auto p-6 grid grid-cols-12 gap-6">
        <!-- SIDEBAR -->
        <aside class="col-span-4 xl:col-span-3 space-y-4">
          <div class="rounded-3xl border border-line bg-white p-5 shadow-sm">
            <div class="text-sm font-semibold">Utente</div>
            <div id="whoamiDesktop" class="text-xs text-gray-600 mt-1 break-words">—</div>

            <div class="mt-4 grid grid-cols-2 gap-2">
              <button id="btnNavPlans" class="rounded-2xl px-3 py-2.5 border border-line bg-ink text-white font-semibold">Piani</button>
              <button id="btnNavSettings" class="rounded-2xl px-3 py-2.5 border border-line bg-white hover:bg-gray-50">Impostazioni</button>
            </div>

            <button id="btnLogout" class="mt-4 w-full rounded-2xl border border-line py-2.5 hover:bg-gray-50">Logout</button>
          </div>

          <div id="adminUpload" class="hidden rounded-3xl border border-line bg-slate-50 p-5 shadow-sm">
            <div class="font-semibold text-sm">Upload CSV</div>
            <div class="text-xs text-gray-600 mt-1">Carica il file delle coppie pranzo+cena.</div>
            <div class="mt-3 space-y-2">
              <input id="csvUpload" type="file" accept=".csv" class="block w-full text-sm"/>
              <button id="btnUploadCsv" class="w-full rounded-2xl bg-ink text-white py-2.5 hover:opacity-95">Carica CSV</button>
              <div id="csvStatus" class="text-xs text-gray-600 min-h-[16px]">—</div>
            </div>
          </div>

          <div class="rounded-3xl border border-line bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
              <div class="font-semibold text-sm">Piani salvati</div>
              <div class="text-xs text-gray-600"><span id="savedCount">0</span></div>
            </div>
            <input id="savedSearch" class="mt-3 w-full rounded-2xl border border-line px-3 py-2.5 outline-none focus:ring-2 focus:ring-accent/30" placeholder="Cerca…"/>
            <div id="savedList" class="mt-3 space-y-2"></div>
          </div>
        </aside>

        <!-- MAIN -->
        <main class="col-span-8 xl:col-span-9">
          <!-- VIEW: PIANI -->
          <div id="plansView" class="space-y-4">
            <div id="adminControls" class="hidden rounded-3xl border border-line bg-white p-5 shadow-sm">
              <div class="flex flex-wrap items-end gap-3 justify-between">
                <div class="min-w-[220px]">
                  <div class="text-sm font-semibold">Generazione</div>
                  <div class="text-xs text-gray-600 mt-1">Seleziona un lunedì + numero settimane.</div>
                </div>
                <div class="flex flex-wrap items-end gap-3">
                  <div>
                    <div class="text-xs text-gray-600 mb-1">Lunedì</div>
                    <input id="startMonday" type="date" class="rounded-2xl border border-line px-3 py-2.5"/>
                  </div>
                  <div>
                    <div class="text-xs text-gray-600 mb-1">Settimane</div>
                    <select id="numWeeks" class="rounded-2xl border border-line px-3 py-2.5">
                      <option>1</option><option>2</option><option>3</option><option>4</option>
                    </select>
                  </div>
                  <button id="btnGenerate" class="rounded-2xl bg-ink text-white px-4 py-2.5 hover:opacity-95">Genera</button>
                  <button id="btnSave" class="rounded-2xl border border-line px-4 py-2.5 hover:bg-gray-50">Salva</button>
                </div>
              </div>
              <div class="text-xs text-gray-600 mt-3 min-h-[16px]">
                <span id="status">—</span>
              </div>
            </div>

            <div class="rounded-3xl border border-line bg-white p-5 shadow-sm">
              <div class="flex items-center justify-between gap-3 flex-wrap">
                <div>
                  <div class="text-sm font-semibold">Piano</div>
                  <div id="currentLabel" class="text-xs text-gray-600 mt-1">—</div>
                </div>
                <button id="btnClearAll" class="rounded-2xl border border-line px-4 py-2.5 hover:bg-gray-50">Pulisci vista</button>
              </div>

              <div id="weekTabs" class="mt-4 flex flex-wrap gap-2"></div>
              <div id="weekContent" class="mt-4"></div>
            </div>
          </div>

          <!-- VIEW: IMPOSTAZIONI -->
          <div id="settingsView" class="hidden space-y-4">
            <div class="rounded-3xl border border-line bg-white p-5 shadow-sm">
              <div class="flex items-start justify-between gap-3 flex-wrap">
                <div>
                  <div class="text-sm font-semibold">Impostazioni (per gruppo)</div>
                  <div class="text-xs text-gray-600 mt-1">Gli utenti vedono, solo admin modifica.</div>
                </div>
                <button id="btnSaveSettings" class="hidden rounded-2xl bg-ink text-white px-4 py-2.5 hover:opacity-95">Salva impostazioni</button>
              </div>

              <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Pizza -->
                <div class="rounded-3xl border border-line bg-slate-50 p-4">
                  <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold">Regola Pizza</div>
                    <label class="text-sm flex items-center gap-2">
                      <input id="pizzaEnabled" type="checkbox" class="scale-110">
                      <span class="text-sm">Attiva</span>
                    </label>
                  </div>

                  <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <div>
                      <div class="text-xs text-gray-600 mb-1">Giorno</div>
                      <select id="pizzaDay" class="w-full rounded-2xl border border-line px-3 py-2.5">
                        <option value="0">Lun</option><option value="1">Mar</option><option value="2">Mer</option>
                        <option value="3">Gio</option><option value="4">Ven</option><option value="5">Sab</option><option value="6">Dom</option>
                      </select>
                    </div>
                    <div>
                      <div class="text-xs text-gray-600 mb-1">Pasto</div>
                      <select id="pizzaMeal" class="w-full rounded-2xl border border-line px-3 py-2.5">
                        <option value="dinner">Cena</option>
                        <option value="lunch">Pranzo</option>
                      </select>
                    </div>
                    <div class="sm:col-span-1">
                      <div class="text-xs text-gray-600 mb-1">Testo</div>
                      <input id="pizzaText" class="w-full rounded-2xl border border-line px-3 py-2.5" placeholder="Pizza, insalata…"/>
                    </div>
                  </div>
                </div>

                <!-- Libero -->
                <div class="rounded-3xl border border-line bg-slate-50 p-4">
                  <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold">Pasto Libero</div>
                    <label class="text-sm flex items-center gap-2">
                      <input id="freeEnabled" type="checkbox" class="scale-110">
                      <span class="text-sm">Attiva</span>
                    </label>
                  </div>

                  <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <div>
                      <div class="text-xs text-gray-600 mb-1">Giorno</div>
                      <select id="freeDay" class="w-full rounded-2xl border border-line px-3 py-2.5">
                        <option value="0">Lun</option><option value="1">Mar</option><option value="2">Mer</option>
                        <option value="3">Gio</option><option value="4">Ven</option><option value="5">Sab</option><option value="6">Dom</option>
                      </select>
                    </div>
                    <div>
                      <div class="text-xs text-gray-600 mb-1">Pasto</div>
                      <select id="freeMeal" class="w-full rounded-2xl border border-line px-3 py-2.5">
                        <option value="lunch">Pranzo</option>
                        <option value="dinner">Cena</option>
                      </select>
                    </div>
                    <div>
                      <div class="text-xs text-gray-600 mb-1">Testo</div>
                      <input id="freeText" class="w-full rounded-2xl border border-line px-3 py-2.5" placeholder="LIBERO"/>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-4 rounded-3xl border border-line bg-white p-4">
                <div class="text-sm font-semibold">Riepilogo</div>
                <div id="settingsSummary" class="text-sm text-gray-700 mt-2">—</div>
                <div id="settingsStatus" class="text-xs text-gray-600 mt-2 min-h-[16px]">—</div>
              </div>
            </div>

            <!-- GESTIONE UTENTI (solo admin) -->
            <div class="rounded-3xl border border-line bg-white p-5 shadow-sm">
              <button id="usersAccordionToggle" class="w-full flex items-center justify-between gap-3">
                <div class="text-sm font-semibold">Gestione utenti</div>
                <div id="usersAccordionIcon" class="transition-transform">▾</div>
              </button>

              <div id="adminUsers" class="hidden mt-4">
                <div class="text-xs text-gray-600">Crea/elimina utenti del tuo gruppo.</div>

                <div class="mt-4 grid grid-cols-1 lg:grid-cols-3 gap-2">
                  <input id="newUserName" class="rounded-2xl border border-line px-4 py-2.5 outline-none focus:ring-2 focus:ring-accent/30" placeholder="Username"/>
                  <input id="newUserPass" type="password" class="rounded-2xl border border-line px-4 py-2.5 outline-none focus:ring-2 focus:ring-accent/30" placeholder="Password"/>
                  <select id="newUserRole" class="rounded-2xl border border-line px-4 py-2.5">
                    <option value="user">user</option>
                    <option value="admin">admin</option>
                  </select>
                </div>

                <div class="mt-3 flex items-center gap-2">
                  <button id="btnCreateUser" class="rounded-2xl bg-ink text-white px-4 py-2.5 hover:opacity-95">Crea utente</button>
                  <div id="userCreateStatus" class="text-xs text-gray-600 min-h-[16px]">—</div>
                </div>

                <div class="mt-4">
                  <div class="text-sm font-semibold">Utenti del gruppo</div>
                  <div id="groupUsersList" class="mt-3 space-y-2"></div>
                </div>
              </div>
            </div>

          </div>
        </main>
      </div>
    </div>
  </section>

  <script src="api/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>
