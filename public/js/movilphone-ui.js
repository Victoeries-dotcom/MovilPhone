/*
 * MovilPhone UI: interacciones globales conectadas con resources/views/layout.blade.php.
 * Mejora iconos, navegación, tablas y confirmaciones sin modificar datos ni controladores.
 */
window.MovilPhoneUI = window.MovilPhoneUI || {};

document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const topbar = document.querySelector('.topbar');
    const sidebar = document.querySelector('.sidebar');

    /* Renderiza los elementos data-lucide con la biblioteca local instalada en public/js. */
    function refreshIcons(scope) {
        if (!window.lucide) return;

        window.lucide.createIcons({
            // El catálogo local permite resolver cualquier data-lucide usado por las vistas del sistema.
            icons: window.lucide.icons,
            root: scope || document,
            attrs: {
                'aria-hidden': 'true',
                'stroke-width': 2,
            },
        });
    }

    /*
     * Alterna el tema claro u oscuro y conserva la eleccion en este navegador.
     * Se conecta con #themeToggle, layout.blade.php y las reglas data-ui-theme del CSS global.
     */
    function initializeThemeToggle() {
        const button = document.getElementById('themeToggle');
        if (!button) return;

        const root = document.documentElement;
        const storageKey = 'movilphone.ui.theme';

        function applyTheme(theme, persist) {
            const selectedTheme = theme === 'light' ? 'light' : 'dark';
            const lightMode = selectedTheme === 'light';
            const nextLabel = lightMode ? 'Cambiar a modo oscuro' : 'Cambiar a modo claro';

            root.dataset.uiTheme = selectedTheme;
            button.classList.toggle('is-light', lightMode);
            button.setAttribute('aria-pressed', lightMode ? 'true' : 'false');
            button.setAttribute('aria-label', nextLabel);
            button.setAttribute('title', nextLabel);
            button.innerHTML = '<i data-lucide="' + (lightMode ? 'moon' : 'sun') + '" aria-hidden="true"></i>';

            const themeMeta = document.querySelector('meta[name="theme-color"]');
            if (themeMeta) themeMeta.content = lightMode ? '#f8fafc' : '#0d1429';
            if (persist) window.localStorage.setItem(storageKey, selectedTheme);

            refreshIcons(button);
            document.dispatchEvent(new CustomEvent('movilphone:themechange', {
                detail: { theme: selectedTheme },
            }));
        }

        applyTheme(root.dataset.uiTheme, false);
        button.addEventListener('click', function () {
            applyTheme(root.dataset.uiTheme === 'light' ? 'dark' : 'light', true);
        });
    }

    /* Traduce emojis heredados de vistas antiguas a iconos Lucide sin alterar su texto funcional. */
    function normalizeLegacyIcons() {
        if (!window.lucide) return;

        const legacyIcons = new Map([
            ['📱', 'smartphone'], ['🔧', 'wrench'], ['👤', 'user'], ['📦', 'package-open'],
            ['💰', 'wallet-cards'], ['👥', 'users'], ['🏪', 'store'], ['🏷️', 'tag'],
            ['🏷', 'tag'], ['🛒', 'shopping-cart'], ['🔔', 'bell-ring'], ['📊', 'chart-no-axes-combined'],
            ['⭐', 'star'], ['📋', 'clipboard-list'], ['🚪', 'log-out'], ['🔍', 'search'],
            ['📅', 'calendar-days'], ['💵', 'banknote'], ['🏦', 'landmark'], ['💳', 'credit-card'],
            ['🛡️', 'shield-check'], ['🛡', 'shield-check'], ['✅', 'circle-check'], ['❌', 'circle-x'],
        ]);
        const roots = document.querySelectorAll('.content, .topbar, [id^="modal-"]');

        roots.forEach(function (root) {
            const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
            const textNodes = [];

            while (walker.nextNode()) textNodes.push(walker.currentNode);

            textNodes.forEach(function (textNode) {
                const parent = textNode.parentElement;
                if (!parent || parent.closest('script, style, option, textarea')) return;

                const value = textNode.nodeValue || '';
                const trimmed = value.trimStart();
                const match = Array.from(legacyIcons.keys()).find(function (symbol) {
                    return trimmed.startsWith(symbol);
                });
                if (!match) return;

                const leadingSpace = value.slice(0, value.length - trimmed.length);
                const icon = document.createElement('i');
                icon.setAttribute('data-lucide', legacyIcons.get(match));
                icon.className = 'ui-inline-icon';
                parent.insertBefore(icon, textNode);
                textNode.nodeValue = leadingSpace + trimmed.slice(match.length).trimStart();
            });
        });

        // Los controles nativos no aceptan SVG; elimina únicamente el emoji y conserva su descripción.
        document.querySelectorAll('input[placeholder]').forEach(function (input) {
            const placeholder = input.getAttribute('placeholder') || '';
            legacyIcons.forEach(function (_icon, symbol) {
                if (placeholder.trimStart().startsWith(symbol)) {
                    input.setAttribute('placeholder', placeholder.replace(symbol, '').trimStart());
                }
            });
        });
        document.querySelectorAll('option').forEach(function (option) {
            legacyIcons.forEach(function (_icon, symbol) {
                if (option.textContent.trimStart().startsWith(symbol)) {
                    option.textContent = option.textContent.replace(symbol, '').trimStart();
                }
            });
        });
    }

    /* Añade iconos semánticos a comandos comunes y se conecta con sus rutas o formularios existentes. */
    function decorateCommandButtons() {
        const commandIcons = [
            [/^eliminar/i, 'trash-2'], [/^editar/i, 'pencil'], [/^ver detalle/i, 'eye'],
            [/^ver datos/i, 'eye'], [/^ver orden/i, 'eye'], [/^detalle/i, 'search'], [/^ver$/i, 'eye'], [/^volver/i, 'arrow-left'],
            [/^guardar/i, 'save'], [/^cancelar/i, 'x'], [/^agregar/i, 'plus'],
            [/^nuev[ao]/i, 'plus'], [/^imprimir/i, 'printer'], [/^consultar/i, 'calendar-search'],
            [/^sticker/i, 'tag'], [/^entregar/i, 'package-check'], [/^buscar/i, 'search'],
            [/^continuar/i, 'arrow-right'], [/^siguiente/i, 'arrow-right'], [/^anterior/i, 'arrow-left'],
        ];

        document.querySelectorAll('.btn, .os-btn, .registro-next, .registro-prev, .registro-save').forEach(function (button) {
            if (button.querySelector('svg, [data-lucide]') || button.classList.contains('ui-command-ready')) return;

            const text = button.textContent.trim();
            const command = commandIcons.find(function (item) { return item[0].test(text); });
            if (!command) return;

            const icon = document.createElement('i');
            icon.setAttribute('data-lucide', command[1]);
            icon.className = 'ui-button-icon';
            button.prepend(icon);
            button.classList.add('ui-command-ready');
        });
    }

    /* Convierte mensajes “No hay…” en estados vacíos visibles dentro de tablas y listas. */
    function enhanceEmptyStates() {
        document.querySelectorAll('td[colspan]').forEach(function (cell) {
            const text = cell.textContent.trim();
            if (!/^no hay/i.test(text) || cell.classList.contains('ui-empty-state')) return;

            cell.textContent = '';
            cell.classList.add('ui-empty-state');
            const icon = document.createElement('i');
            icon.setAttribute('data-lucide', 'inbox');
            const message = document.createElement('span');
            message.textContent = text;
            const help = document.createElement('small');
            help.textContent = 'Los nuevos registros aparecerán aquí automáticamente.';
            cell.parentElement?.classList.add('ui-empty-row');
            cell.append(icon, message, help);
        });
    }

    /*
     * Convierte el texto de una celda en un valor comparable.
     * Se conecta con el ordenamiento local y reconoce fechas, importes, cantidades y texto.
     */
    function sortableValue(cell) {
        const text = (cell?.textContent || '').replace(/\s+/g, ' ').trim();
        const dateMatch = text.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})(?:\s+(\d{1,2}):(\d{2}))?/);
        if (dateMatch) {
            return new Date(
                Number(dateMatch[3]), Number(dateMatch[2]) - 1, Number(dateMatch[1]),
                Number(dateMatch[4] || 0), Number(dateMatch[5] || 0)
            ).getTime();
        }

        const numericText = text.replace(/[$,%+#]/g, '').replace(/,/g, '').trim();
        if (numericText !== '' && /^-?\d+(?:\.\d+)?$/.test(numericText)) return Number(numericText);
        return text.toLocaleLowerCase('es-MX');
    }

    /*
     * Presenta estados operativos con badges de color sin cambiar el valor almacenado.
     * Se conecta únicamente con celdas de estado simples y conserva los componentes ya diseñados por cada vista.
     */
    function enhanceTableStatuses(table) {
        const statusGroups = {
            success: ['COMPLETADA', 'COMPLETADO', 'ENTREGADO', 'LISTO PARA RECOGER', 'ACTIVO', 'DISPONIBLE'],
            info: ['RECIBIDO', 'DIAGNOSTICO', 'EN DIAGNOSTICO', 'REPARACION', 'EN REPARACION', 'GARANTIA'],
            warning: ['PENDIENTE', 'EN ESPERA', 'BAJO STOCK'],
            danger: ['RECHAZADO', 'NO QUEDO', 'NO QUEDO / RECHAZADO', 'CANCELADA', 'CANCELADO'],
            neutral: ['INACTIVO', 'VENDIDO'],
        };

        table.querySelectorAll('tbody td').forEach(function (cell) {
            if (cell.children.length || cell.hasAttribute('colspan')) return;

            const originalText = cell.textContent.replace(/\s+/g, ' ').trim();
            const normalized = originalText.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase();
            const tone = Object.keys(statusGroups).find(function (group) {
                return statusGroups[group].includes(normalized);
            });
            if (!tone) return;

            const badge = document.createElement('span');
            badge.className = 'ui-status-badge ui-status-' + tone;
            badge.textContent = originalText;
            cell.textContent = '';
            cell.appendChild(badge);
        });
    }

    /*
     * Compacta acciones conocidas en iconos con nombre accesible y ayuda nativa.
     * Cada control conserva su href, método, name y value, por lo que continúa conectado con su ruta o formulario.
     */
    function compactTableActions(actionCells) {
        const compactCommands = /^(ver|ver detalle|ver datos|ver orden|detalle|editar|eliminar)$/i;

        actionCells.forEach(function (cell) {
            cell.querySelectorAll('a.btn, button.btn').forEach(function (button) {
                const label = button.textContent.replace(/\s+/g, ' ').trim();
                const icon = button.querySelector('[data-lucide]');
                if (!compactCommands.test(label) || !icon) return;

                button.classList.add('ui-icon-action');
                button.setAttribute('aria-label', label);
                button.setAttribute('title', label);
                button.replaceChildren(icon.cloneNode(true));
            });
        });
    }

    /*
     * Crea el selector de columnas inspirado en MoonShine y recuerda la vista elegida en este navegador.
     * Se conecta con los encabezados y celdas de la tabla actual; no modifica consultas ni registros.
     */
    function createColumnPicker(meta, table, headings, tableIndex, actionColumns) {
        const availableColumns = headings.map(function (heading, index) {
            return { index: index, label: heading.textContent.replace(/\s+/g, ' ').trim() };
        }).filter(function (column) {
            return column.label && !actionColumns.has(column.index);
        });
        if (availableColumns.length < 2) return;

        const tools = document.createElement('div');
        tools.className = 'ui-table-tools';
        const picker = document.createElement('div');
        picker.className = 'ui-column-picker';
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'ui-column-toggle';
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Elegir columnas visibles');
        toggle.innerHTML = '<i data-lucide="columns-3"></i><span>Columnas</span>';
        const menu = document.createElement('div');
        menu.className = 'ui-column-menu';
        menu.innerHTML = '<div class="ui-column-menu-title">Columnas visibles</div>';

        const storageKey = 'movilphone.table.columns.' + window.location.pathname + '.' + tableIndex;
        let hiddenColumns = [];
        try {
            hiddenColumns = JSON.parse(window.localStorage.getItem(storageKey) || '[]');
            if (!Array.isArray(hiddenColumns)) hiddenColumns = [];
        } catch (_error) {
            hiddenColumns = [];
        }

        function setColumnVisibility(columnIndex, visible) {
            const heading = headings[columnIndex];
            const liveColumnIndex = heading?.cellIndex ?? columnIndex;
            heading?.classList.toggle('ui-column-hidden', !visible);
            Array.from(table.rows).slice(1).forEach(function (row) {
                row.cells[liveColumnIndex]?.classList.toggle('ui-column-hidden', !visible);
            });
        }

        availableColumns.forEach(function (column) {
            const option = document.createElement('label');
            option.className = 'ui-column-option';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.checked = !hiddenColumns.includes(column.index);
            const text = document.createElement('span');
            text.textContent = column.label;
            option.append(checkbox, text);
            menu.appendChild(option);
            setColumnVisibility(column.index, checkbox.checked);

            checkbox.addEventListener('change', function () {
                setColumnVisibility(column.index, checkbox.checked);
                const hidden = availableColumns.filter(function (item) {
                    return !menu.querySelectorAll('input')[availableColumns.indexOf(item)].checked;
                }).map(function (item) { return item.index; });
                try { window.localStorage.setItem(storageKey, JSON.stringify(hidden)); } catch (_error) { /* Preferencia opcional. */ }
            });
        });

        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            const open = !menu.classList.contains('is-open');
            document.querySelectorAll('.ui-column-menu.is-open').forEach(function (otherMenu) {
                if (otherMenu !== menu) otherMenu.classList.remove('is-open');
            });
            menu.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        menu.addEventListener('click', function (event) { event.stopPropagation(); });
        picker.append(toggle, menu);
        tools.appendChild(picker);
        meta.appendChild(tools);
    }

    /*
     * Añade panel, contador, columnas semánticas y ordenamiento a todas las tablas.
     * Conserva los elementos originales, por lo que formularios, rutas y botones siguen conectados igual.
     */
    function enhanceTables() {
        const actionHeaders = /^(acciones?|opciones?)$/i;
        const numericHeaders = /^(#|total|monto|precio|ingresos?|egresos?|balance|cantidad|existencia|stock|compras|productos|piezas?|servicios anteriores|valor costo)$/i;

        document.querySelectorAll('.content table').forEach(function (table, tableIndex) {
            if (table.closest('.ui-table-panel') || table.closest('[id*="ticket"]')) return;

            const bodySection = table.tBodies[0];
            const rows = bodySection ? Array.from(bodySection.rows) : [];
            const dataRows = rows.filter(row => !row.querySelector('td[colspan]'));
            const panel = document.createElement('div');
            panel.className = 'ui-table-panel';

            const meta = document.createElement('div');
            meta.className = 'ui-table-meta';
            const summary = document.createElement('div');
            summary.className = 'ui-table-summary';
            summary.innerHTML = '<span class="ui-table-summary-icon"><i data-lucide="table-2"></i></span>'
                + '<span><strong class="ui-table-count">' + dataRows.length + '</strong> '
                + (dataRows.length === 1 ? 'registro' : 'registros') + '</span>';
            const hint = document.createElement('span');
            hint.className = 'ui-table-hint';
            hint.innerHTML = '<i data-lucide="move-horizontal"></i><span>Desliza para ver más</span>';
            meta.append(summary, hint);

            const shell = document.createElement('div');
            shell.className = 'ui-table-shell';
            table.parentNode.insertBefore(panel, table);
            panel.append(meta, shell);
            shell.appendChild(table);

            const headings = table.tHead ? Array.from(table.tHead.rows[0]?.cells || []) : [];
            const actionColumns = new Set();
            const actionCells = [];
            headings.forEach(function (heading, columnIndex) {
                const label = heading.textContent.replace(/\s+/g, ' ').trim();
                const cells = dataRows.map(row => row.cells[columnIndex]).filter(Boolean);

                if (actionHeaders.test(label)) {
                    actionColumns.add(columnIndex);
                    heading.classList.add('ui-actions-heading');
                    cells.forEach(function (cell) {
                        cell.classList.add('ui-actions-cell');
                        actionCells.push(cell);
                    });
                    return;
                }
                if (numericHeaders.test(label)) {
                    heading.classList.add('ui-cell-number');
                    cells.forEach(cell => cell.classList.add('ui-cell-number'));
                }
                if (dataRows.length < 2 || !bodySection) return;

                heading.classList.add('ui-sortable-heading');
                heading.setAttribute('role', 'button');
                heading.setAttribute('tabindex', '0');
                heading.setAttribute('aria-sort', 'none');
                heading.setAttribute('aria-label', 'Ordenar por ' + label);

                const content = document.createElement('span');
                content.className = 'ui-heading-content';
                const labelNode = document.createElement('span');
                labelNode.textContent = label;
                const sortIcon = document.createElement('i');
                sortIcon.className = 'ui-sort-icon';
                sortIcon.setAttribute('data-lucide', 'chevron-up');
                content.append(labelNode, sortIcon);
                heading.textContent = '';
                heading.appendChild(content);

                function sortColumn() {
                    /*
                     * Lee el indice actual porque la tabla profesional agrega una casilla al inicio.
                     * Se conecta con el selector de filas sin desplazar la columna usada para ordenar.
                     */
                    const liveColumnIndex = heading.cellIndex;
                    const ascending = heading.getAttribute('aria-sort') !== 'ascending';
                    headings.forEach(item => {
                        if (item !== heading && item.classList.contains('ui-sortable-heading')) {
                            item.setAttribute('aria-sort', 'none');
                        }
                    });

                    dataRows.sort(function (rowA, rowB) {
                        const valueA = sortableValue(rowA.cells[liveColumnIndex]);
                        const valueB = sortableValue(rowB.cells[liveColumnIndex]);
                        const comparison = typeof valueA === 'number' && typeof valueB === 'number'
                            ? valueA - valueB
                            : String(valueA).localeCompare(String(valueB), 'es-MX', { numeric: true });
                        return ascending ? comparison : -comparison;
                    });
                    dataRows.forEach(row => bodySection.appendChild(row));
                    heading.setAttribute('aria-sort', ascending ? 'ascending' : 'descending');
                }

                heading.addEventListener('click', sortColumn);
                heading.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        sortColumn();
                    }
                });
            });

            enhanceTableStatuses(table);
            compactTableActions(actionCells);
            createColumnPicker(meta, table, headings, tableIndex, actionColumns);
        });

        // Cierra cualquier selector de columnas abierto al continuar trabajando fuera del panel.
        document.addEventListener('click', function () {
            document.querySelectorAll('.ui-column-menu.is-open').forEach(function (menu) {
                menu.classList.remove('is-open');
                menu.closest('.ui-column-picker')?.querySelector('.ui-column-toggle')?.setAttribute('aria-expanded', 'false');
            });
        });
    }

    /*
     * Normaliza filtros y campos obligatorios en formularios existentes.
     * Se conecta con sus métodos GET/POST originales y solo añade clases visuales y accesibilidad.
     */
    function enhanceForms() {
        document.querySelectorAll('.content form').forEach(function (form) {
            const method = (form.getAttribute('method') || 'GET').toUpperCase();
            const hasVisibleControls = form.querySelector('input:not([type="hidden"]), select, textarea');
            if (method === 'GET' && hasVisibleControls && !form.closest('table') && !form.classList.contains('reporte-calendario')) {
                form.classList.add('ui-filter-bar');
            }
        });

        document.querySelectorAll('.content input[required], .content select[required], .content textarea[required]').forEach(function (field) {
            const fieldId = field.getAttribute('id');
            const label = fieldId
                ? document.querySelector('label[for="' + CSS.escape(fieldId) + '"]')
                : field.closest('.form-group')?.querySelector('label');
            label?.classList.add('ui-required');
        });
    }

    /*
     * Convierte cada tabla mejorada en un espacio de trabajo con busqueda, densidad,
     * seleccion, paginacion y CSV. Se conecta con las filas ya entregadas por Laravel,
     * por lo que no modifica consultas ni registros almacenados.
     */
    function enhanceTableWorkspaces() {
        document.querySelectorAll('.ui-table-panel').forEach(function (panel, tableIndex) {
            const table = panel.querySelector('table');
            const meta = panel.querySelector('.ui-table-meta');
            const tbody = table?.tBodies[0];
            const headerRow = table?.tHead?.rows[0];
            if (!table || !meta || !tbody || !headerRow || table.dataset.workspaceReady) return;

            const rows = Array.from(tbody.rows).filter(function (row) {
                return !row.querySelector('td[colspan]');
            });
            if (!rows.length) return;

            table.dataset.workspaceReady = 'true';
            const storageBase = 'movilphone.table.workspace.' + window.location.pathname + '.' + tableIndex;
            let page = 1;
            let pageSize = Number(window.localStorage.getItem(storageBase + '.size') || 10);
            let query = '';

            // Seleccion: agrega casillas sin tocar botones, formularios ni rutas de cada fila.
            const selectHeading = document.createElement('th');
            selectHeading.className = 'ui-selection-cell';
            selectHeading.setAttribute('aria-label', 'Seleccionar todos los registros visibles');
            const selectAll = document.createElement('input');
            selectAll.type = 'checkbox';
            selectAll.setAttribute('aria-label', 'Seleccionar todos los registros visibles');
            selectHeading.appendChild(selectAll);
            headerRow.insertBefore(selectHeading, headerRow.firstElementChild);

            rows.forEach(function (row) {
                const cell = document.createElement('td');
                cell.className = 'ui-selection-cell';
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'ui-row-select';
                checkbox.setAttribute('aria-label', 'Seleccionar este registro');
                cell.appendChild(checkbox);
                row.insertBefore(cell, row.firstElementChild);
            });
            Array.from(tbody.rows).filter(row => row.querySelector('td[colspan]')).forEach(function (row) {
                const cell = row.querySelector('td[colspan]');
                cell.colSpan = Math.max(1, Number(cell.colSpan || headerRow.cells.length - 1) + 1);
            });

            const workspace = document.createElement('div');
            workspace.className = 'ui-table-workspace';
            workspace.innerHTML = [
                '<label class="ui-table-search"><i data-lucide="search"></i><input type="search" placeholder="Filtrar esta tabla" aria-label="Filtrar registros de esta tabla"></label>',
                '<label class="ui-table-size"><span>Filas</span><select aria-label="Cantidad de filas por pagina"><option value="10">10</option><option value="25">25</option><option value="0">Todas</option></select></label>',
                '<div class="ui-density-control" role="group" aria-label="Densidad de la tabla"><button type="button" data-density="comfortable" title="Vista comoda"><i data-lucide="rows-3"></i></button><button type="button" data-density="compact" title="Vista compacta"><i data-lucide="rows-4"></i></button></div>',
                '<button type="button" class="ui-table-export"><i data-lucide="download"></i><span>CSV</span></button>',
                '<span class="ui-selected-count" hidden>0 seleccionados</span>',
            ].join('');
            meta.insertAdjacentElement('afterend', workspace);

            const pagination = document.createElement('div');
            pagination.className = 'ui-client-pagination';
            panel.appendChild(pagination);

            const searchInput = workspace.querySelector('input[type="search"]');
            const sizeSelect = workspace.querySelector('select');
            const selectedCount = workspace.querySelector('.ui-selected-count');
            const countElement = meta.querySelector('.ui-table-count');
            const storedDensity = window.localStorage.getItem(storageBase + '.density') || 'comfortable';
            table.classList.toggle('is-compact', storedDensity === 'compact');
            sizeSelect.value = String([0, 10, 25].includes(pageSize) ? pageSize : 10);

            function matchingRows() {
                return rows.filter(function (row) {
                    return !query || row.textContent.toLocaleLowerCase('es-MX').includes(query);
                });
            }

            function updateSelectionCount() {
                const selected = rows.filter(row => row.querySelector('.ui-row-select')?.checked);
                selectedCount.hidden = selected.length === 0;
                selectedCount.textContent = selected.length + (selected.length === 1 ? ' seleccionado' : ' seleccionados');
            }

            function render() {
                const matching = matchingRows();
                const totalPages = pageSize === 0 ? 1 : Math.max(1, Math.ceil(matching.length / pageSize));
                page = Math.min(page, totalPages);
                const start = pageSize === 0 ? 0 : (page - 1) * pageSize;
                const end = pageSize === 0 ? matching.length : start + pageSize;

                rows.forEach(row => { row.hidden = true; });
                matching.slice(start, end).forEach(row => { row.hidden = false; });
                if (countElement) countElement.textContent = matching.length;

                pagination.replaceChildren();
                if (totalPages > 1) {
                    const previous = document.createElement('button');
                    previous.type = 'button';
                    previous.innerHTML = '<i data-lucide="chevron-left"></i>';
                    previous.disabled = page === 1;
                    previous.setAttribute('aria-label', 'Pagina anterior');
                    const status = document.createElement('span');
                    status.textContent = 'Pagina ' + page + ' de ' + totalPages;
                    const next = document.createElement('button');
                    next.type = 'button';
                    next.innerHTML = '<i data-lucide="chevron-right"></i>';
                    next.disabled = page === totalPages;
                    next.setAttribute('aria-label', 'Pagina siguiente');
                    previous.addEventListener('click', function () { page -= 1; render(); });
                    next.addEventListener('click', function () { page += 1; render(); });
                    pagination.append(previous, status, next);
                }
                refreshIcons(panel);
            }

            function csvCell(value) {
                return '"' + String(value || '').replace(/\s+/g, ' ').trim().replace(/"/g, '""') + '"';
            }

            function exportCsv() {
                const selected = rows.filter(row => row.querySelector('.ui-row-select')?.checked);
                const exportRows = selected.length ? selected : matchingRows();
                const actionColumns = Array.from(headerRow.cells).map(function (heading, index) {
                    const label = heading.textContent.trim().toLocaleLowerCase('es-MX');
                    return label === 'acciones' || heading.classList.contains('ui-selection-cell') ? index : -1;
                }).filter(index => index >= 0);
                const visibleIndexes = Array.from(headerRow.cells).map((_heading, index) => index)
                    .filter(index => !actionColumns.includes(index));
                const csv = [
                    visibleIndexes.map(index => csvCell(headerRow.cells[index]?.textContent)).join(','),
                    ...exportRows.map(row => visibleIndexes.map(index => csvCell(row.cells[index]?.textContent)).join(',')),
                ].join('\r\n');
                const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'movilphone-' + window.location.pathname.split('/').filter(Boolean).join('-') + '.csv';
                link.click();
                URL.revokeObjectURL(link.href);
            }

            searchInput.addEventListener('input', function () {
                query = searchInput.value.trim().toLocaleLowerCase('es-MX');
                page = 1;
                render();
            });
            sizeSelect.addEventListener('change', function () {
                pageSize = Number(sizeSelect.value);
                page = 1;
                window.localStorage.setItem(storageBase + '.size', String(pageSize));
                render();
            });
            workspace.querySelectorAll('[data-density]').forEach(function (button) {
                button.classList.toggle('is-active', button.dataset.density === storedDensity);
                button.addEventListener('click', function () {
                    const compact = button.dataset.density === 'compact';
                    table.classList.toggle('is-compact', compact);
                    workspace.querySelectorAll('[data-density]').forEach(item => item.classList.toggle('is-active', item === button));
                    window.localStorage.setItem(storageBase + '.density', button.dataset.density);
                });
            });
            workspace.querySelector('.ui-table-export').addEventListener('click', exportCsv);
            selectAll.addEventListener('change', function () {
                matchingRows().filter(row => !row.hidden).forEach(function (row) {
                    row.querySelector('.ui-row-select').checked = selectAll.checked;
                });
                updateSelectionCount();
            });
            rows.forEach(row => row.querySelector('.ui-row-select').addEventListener('change', updateSelectionCount));
            headerRow.addEventListener('click', function () { window.setTimeout(render, 0); });

            render();
        });
    }

    /*
     * Activa la busqueda transversal con Ctrl+K y abre una ficha lateral.
     * Se conecta por JSON con GlobalSearchController y respeta rol y sucursal en el servidor.
     */
    function initializeGlobalSearch() {
        const search = document.querySelector('.global-search');
        const input = document.getElementById('globalSearchInput');
        const results = document.getElementById('globalSearchResults');
        const drawer = document.getElementById('quickViewDrawer');
        const backdrop = document.getElementById('quickViewBackdrop');
        if (!search || !input || !results || !drawer || !backdrop) return;

        let timer = null;
        let controller = null;

        function closeResults() {
            results.classList.remove('is-open');
            input.setAttribute('aria-expanded', 'false');
        }

        function closeDrawer() {
            drawer.classList.remove('is-open');
            backdrop.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
        }

        function openQuickView(item) {
            const template = search.dataset.detailTemplate;
            const url = template.replace('__TYPE__', encodeURIComponent(item.tipo)).replace('__ID__', encodeURIComponent(item.id));
            drawer.classList.add('is-open');
            backdrop.classList.add('is-open');
            drawer.setAttribute('aria-hidden', 'false');
            document.getElementById('quickViewTitle').textContent = item.titulo;
            document.getElementById('quickViewSubtitle').textContent = item.tipo.toUpperCase();
            document.getElementById('quickViewBody').innerHTML = '<div class="quick-view-loading"><span></span><span></span><span></span></div>';

            fetch(url, { headers: { Accept: 'application/json' } })
                .then(response => {
                    if (!response.ok) throw new Error('No fue posible consultar el registro.');
                    return response.json();
                })
                .then(function (data) {
                    document.getElementById('quickViewTitle').textContent = data.titulo;
                    document.getElementById('quickViewSubtitle').textContent = data.subtitulo;
                    document.getElementById('quickViewBody').innerHTML = data.campos.map(function (field) {
                        return '<div class="quick-view-field"><span>' + escapeHtml(field.etiqueta) + '</span><strong>' + escapeHtml(field.valor) + '</strong></div>';
                    }).join('');
                    document.getElementById('quickViewOpen').href = data.url;
                    refreshIcons(drawer);
                })
                .catch(function (error) {
                    document.getElementById('quickViewBody').innerHTML = '<div class="quick-view-error">' + escapeHtml(error.message) + '</div>';
                });
        }

        function escapeHtml(value) {
            const element = document.createElement('span');
            element.textContent = String(value ?? '');
            return element.innerHTML;
        }

        function runSearch() {
            const term = input.value.trim();
            if (term.length < 2) {
                closeResults();
                results.replaceChildren();
                return;
            }
            controller?.abort();
            controller = new AbortController();
            results.innerHTML = '<div class="global-search-state">Buscando...</div>';
            results.classList.add('is-open');
            input.setAttribute('aria-expanded', 'true');

            fetch(search.dataset.searchUrl + '?q=' + encodeURIComponent(term), {
                headers: { Accept: 'application/json' }, signal: controller.signal,
            }).then(response => response.json()).then(function (payload) {
                const items = payload.resultados || [];
                results.replaceChildren();
                if (!items.length) {
                    results.innerHTML = '<div class="global-search-state">No se encontraron coincidencias.</div>';
                    return;
                }
                items.forEach(function (item) {
                    const row = document.createElement('button');
                    row.type = 'button';
                    row.className = 'global-search-result';
                    row.innerHTML = '<span class="global-result-icon"><i data-lucide="' + item.icono + '"></i></span><span><strong>' + escapeHtml(item.titulo) + '</strong><small>' + escapeHtml(item.detalle) + '</small></span><i data-lucide="panel-right-open"></i>';
                    row.addEventListener('click', function () { closeResults(); openQuickView(item); });
                    results.appendChild(row);
                });
                refreshIcons(results);
            }).catch(function (error) {
                if (error.name !== 'AbortError') results.innerHTML = '<div class="global-search-state">No se pudo realizar la busqueda.</div>';
            });
        }

        input.addEventListener('input', function () {
            window.clearTimeout(timer);
            timer = window.setTimeout(runSearch, 230);
        });
        document.addEventListener('keydown', function (event) {
            if ((event.ctrlKey || event.metaKey) && event.key.toLocaleLowerCase() === 'k') {
                event.preventDefault(); input.focus(); input.select();
            }
            if (event.key === 'Escape') { closeResults(); closeDrawer(); }
        });
        document.addEventListener('click', function (event) { if (!search.contains(event.target)) closeResults(); });
        backdrop.addEventListener('click', closeDrawer);
        document.getElementById('quickViewClose')?.addEventListener('click', closeDrawer);
    }

    /*
     * Actualiza la campana administrativa cada quince segundos y marca lecturas.
     * Se conecta con AdminActivityController sin recargar el modulo abierto.
     */
    function initializeNotifications() {
        const center = document.querySelector('.notification-center');
        const toggle = document.getElementById('notificationToggle');
        const panel = document.getElementById('notificationPanel');
        const list = document.getElementById('notificationList');
        const count = center?.querySelector('.notification-count');
        if (!center || !toggle || !panel || !list || !count) return;

        function renderNotifications(payload) {
            const unread = Math.min(99, Number(payload.no_leidas || 0));
            count.textContent = unread === 99 ? '99+' : unread;
            count.hidden = unread === 0;
            list.replaceChildren();
            if (!(payload.actividades || []).length) {
                list.innerHTML = '<div class="notification-empty">No hay actividad reciente.</div>';
                return;
            }
            payload.actividades.forEach(function (item) {
                const row = document.createElement('div');
                row.className = 'notification-item';
                row.innerHTML = '<span class="notification-dot"></span><div><strong>' + item.accion + ' · ' + item.modulo + '</strong><p>' + item.descripcion + '</p><small>' + item.usuario + ' · ' + item.fecha + '</small></div>';
                list.appendChild(row);
            });
        }

        function loadNotifications() {
            fetch(center.dataset.notificationsUrl, { headers: { Accept: 'application/json' } })
                .then(response => response.json()).then(renderNotifications).catch(function () {
                    list.innerHTML = '<div class="notification-empty">No fue posible actualizar la actividad.</div>';
                });
        }

        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            const open = !panel.classList.contains('is-open');
            panel.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (!open) return;
            fetch(center.dataset.readUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': window.MovilPhoneConfig?.csrfToken || '' },
            }).then(function () { count.hidden = true; }).catch(function () { /* La campana seguira intentando en el siguiente ciclo. */ });
        });
        panel.addEventListener('click', event => event.stopPropagation());
        document.addEventListener('click', function () { panel.classList.remove('is-open'); toggle.setAttribute('aria-expanded', 'false'); });
        loadNotifications();
        window.setInterval(loadNotifications, 15000);
    }

    /* Crea el botón móvil y el fondo que abren el menú lateral sin cambiar sus enlaces. */
    if (topbar && sidebar) {
        const menuButton = document.createElement('button');
        menuButton.type = 'button';
        menuButton.className = 'ui-menu-toggle';
        menuButton.setAttribute('aria-label', 'Abrir menú principal');
        menuButton.setAttribute('aria-expanded', 'false');
        menuButton.innerHTML = '<i data-lucide="menu"></i>';
        topbar.prepend(menuButton);

        const backdrop = document.createElement('button');
        backdrop.type = 'button';
        backdrop.className = 'ui-sidebar-backdrop';
        backdrop.setAttribute('aria-label', 'Cerrar menú principal');
        document.body.appendChild(backdrop);

        function setSidebar(open) {
            body.classList.toggle('ui-sidebar-open', open);
            menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
            menuButton.setAttribute('aria-label', open ? 'Cerrar menú principal' : 'Abrir menú principal');
            menuButton.innerHTML = open ? '<i data-lucide="x"></i>' : '<i data-lucide="menu"></i>';
            refreshIcons(menuButton);
        }

        menuButton.addEventListener('click', function () {
            setSidebar(!body.classList.contains('ui-sidebar-open'));
        });
        backdrop.addEventListener('click', function () { setSidebar(false); });
        sidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () { setSidebar(false); });
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth > 900) setSidebar(false);
        });
    }

    /* Muestra progreso al navegar o enviar y reduce la sensación de espera entre controladores. */
    const routeProgress = document.createElement('div');
    routeProgress.className = 'ui-route-progress';
    routeProgress.setAttribute('aria-hidden', 'true');
    document.body.appendChild(routeProgress);

    function startProgress() {
        routeProgress.classList.add('is-active');
    }

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey) return;
        if (link.target === '_blank' || link.hasAttribute('download') || link.getAttribute('href').startsWith('#')) return;

        const destination = new URL(link.href, window.location.href);
        if (destination.origin === window.location.origin) startProgress();
    });
    document.addEventListener('submit', function (event) {
        if (event.defaultPrevented) return;
        startProgress();

        // El botón conserva name/value para seguir conectado con acciones como cambios de estado.
        const submitButton = event.submitter;
        if (submitButton?.classList.contains('btn') && event.target.checkValidity()) {
            submitButton.classList.add('is-loading');
            submitButton.setAttribute('aria-busy', 'true');
        }
    });
    window.addEventListener('pageshow', function () {
        routeProgress.classList.remove('is-active');
    });

    /* Administra el modal de avisos y se conecta con las validaciones que antes usaban alert(). */
    const noticeDialog = document.getElementById('ui-notice-dialog');
    const noticeMessage = document.getElementById('ui-notice-message');
    let previousFocus = null;

    function openNotice(message) {
        if (!noticeDialog) return;
        previousFocus = document.activeElement;
        noticeMessage.textContent = String(message || 'Revisa la información capturada.');
        noticeDialog.classList.add('is-open');
        noticeDialog.setAttribute('aria-hidden', 'false');
        body.classList.add('ui-dialog-open');
        noticeDialog.querySelector('[data-ui-notice-close]').focus();
    }

    function closeNotice() {
        if (!noticeDialog) return;
        noticeDialog.classList.remove('is-open');
        noticeDialog.setAttribute('aria-hidden', 'true');
        body.classList.remove('ui-dialog-open');
        if (previousFocus && previousFocus.focus) previousFocus.focus();
    }

    noticeDialog?.querySelectorAll('[data-ui-notice-close]').forEach(function (button) {
        button.addEventListener('click', closeNotice);
    });
    noticeDialog?.addEventListener('click', function (event) {
        if (event.target === noticeDialog) closeNotice();
    });
    window.alert = openNotice;

    /* Controla dos etapas de eliminación y envía el mismo formulario DELETE al confirmar. */
    const confirmDialog = document.getElementById('ui-confirm-dialog');
    const confirmTitle = document.getElementById('ui-confirm-title');
    const confirmMessage = document.getElementById('ui-confirm-message');
    const confirmStep = document.getElementById('ui-confirm-step');
    const confirmContinue = document.getElementById('ui-confirm-continue');
    const confirmIcon = confirmDialog?.querySelector('.ui-dialog-icon');
    let deletePayload = null;
    let deleteStage = 1;

    function closeConfirm() {
        if (!confirmDialog) return;
        confirmDialog.classList.remove('is-open');
        confirmDialog.setAttribute('aria-hidden', 'true');
        body.classList.remove('ui-dialog-open');
        deletePayload = null;
        deleteStage = 1;
        if (previousFocus && previousFocus.focus) previousFocus.focus();
    }

    function renderDeleteStage() {
        const type = deletePayload.recordType || 'el registro';
        const name = deletePayload.recordName || 'seleccionado';

        if (deleteStage === 1) {
            confirmStep.textContent = 'Paso 1 de 2';
            confirmTitle.textContent = '¿Eliminar ' + type + '?';
            confirmMessage.textContent = name + ' dejará de aparecer en este módulo.';
            confirmContinue.innerHTML = '<i data-lucide="arrow-right"></i><span>Sí, continuar</span>';
            confirmIcon.className = 'ui-dialog-icon ui-dialog-icon-warning';
            confirmIcon.innerHTML = '<i data-lucide="triangle-alert"></i>';
        } else {
            confirmStep.textContent = 'Paso 2 de 2';
            confirmTitle.textContent = 'Confirmación definitiva';
            confirmMessage.textContent = 'Al eliminar ' + type + ' ' + name + ', ' + deletePayload.detail + '. Esta acción no se puede deshacer.';
            confirmContinue.innerHTML = '<i data-lucide="trash-2"></i><span>Eliminar definitivamente</span>';
            confirmIcon.className = 'ui-dialog-icon ui-dialog-icon-danger';
            confirmIcon.innerHTML = '<i data-lucide="shield-alert"></i>';
        }

        refreshIcons(confirmDialog);
    }

    window.MovilPhoneUI.confirmDelete = function (payload) {
        if (!confirmDialog || !payload?.form) return;
        previousFocus = document.activeElement;
        deletePayload = payload;
        deleteStage = 1;
        renderDeleteStage();
        confirmDialog.classList.add('is-open');
        confirmDialog.setAttribute('aria-hidden', 'false');
        body.classList.add('ui-dialog-open');
        confirmContinue.focus();
    };

    confirmContinue?.addEventListener('click', function () {
        if (!deletePayload) return;
        if (deleteStage === 1) {
            deleteStage = 2;
            renderDeleteStage();
            return;
        }

        const form = deletePayload.form;
        closeConfirm();
        startProgress();
        HTMLFormElement.prototype.submit.call(form);
    });
    confirmDialog?.querySelectorAll('[data-ui-dialog-close]').forEach(function (button) {
        button.addEventListener('click', closeConfirm);
    });
    confirmDialog?.addEventListener('click', function (event) {
        if (event.target === confirmDialog) closeConfirm();
    });

    /* Escape cierra cualquier diálogo abierto y devuelve el foco al control que lo inició. */
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        if (confirmDialog?.classList.contains('is-open')) closeConfirm();
        if (noticeDialog?.classList.contains('is-open')) closeNotice();
    });

    /* Cierra selectores de sucursal cuando el usuario hace clic fuera de ellos. */
    document.addEventListener('click', function (event) {
        document.querySelectorAll('details.branch-switcher[open]').forEach(function (details) {
            if (!details.contains(event.target)) details.removeAttribute('open');
        });
    });

    initializeThemeToggle();
    normalizeLegacyIcons();
    decorateCommandButtons();
    enhanceForms();
    enhanceTables();
    enhanceTableWorkspaces();
    enhanceEmptyStates();
    initializeGlobalSearch();
    initializeNotifications();
    refreshIcons();
});
