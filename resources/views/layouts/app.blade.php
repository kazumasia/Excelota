<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg: #111318;
            --surface: #1a1d24;
            --surface2: #22262f;
            --border: #333842;
            --text: #e4e6eb;
            --muted: #8a9099;
            --accent: #4f8fc9;
            --danger: #c45c5c;
            --radius: 8px;
            --safe-b: env(safe-area-inset-bottom, 0);
        }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100dvh;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
            -webkit-text-size-adjust: 100%;
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .wrap {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 1rem 1rem calc(1.5rem + var(--safe-b));
        }
        .wrap.wrap--wide {
            max-width: min(1680px, 100%);
        }
        header.app-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        header.app-head h1 {
            margin: 0;
            font-size: clamp(1.15rem, 4vw, 1.4rem);
            font-weight: 600;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            padding: 0.5rem 0.9rem;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: var(--surface2);
            color: var(--text);
            font-size: 0.875rem;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover { background: #2a2f3a; text-decoration: none; }
        .btn-primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }
        .btn-primary:hover { filter: brightness(1.05); }
        .btn-ghost { background: transparent; }
        .btn-danger { border-color: #5a3030; background: #2a1818; color: #f0b4b4; }
        .btn-danger:hover { background: #352020; }
        .btn-sm { padding: 0.35rem 0.65rem; font-size: 0.8125rem; }
        .flash {
            padding: 0.65rem 0.9rem;
            border-radius: var(--radius);
            background: #1a2a22;
            border: 1px solid #2d4a3a;
            color: #a3d4b8;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        .errors {
            padding: 0.65rem 0.9rem;
            border-radius: var(--radius);
            background: #2a1818;
            border: 1px solid #5a3030;
            color: #f0b4b4;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem;
            margin-bottom: 1rem;
        }
        label { display: block; font-size: 0.8rem; color: var(--muted); margin-bottom: 0.25rem; }
        input[type="text"], input[type="search"], input[type="file"], textarea {
            width: 100%;
            max-width: 100%;
            padding: 0.5rem 0.65rem;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text);
            font-family: inherit;
            font-size: 1rem;
        }
        input:focus, textarea:focus { outline: 2px solid var(--accent); outline-offset: 0; }
        .row-actions { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.5rem; }

        .only-desktop { display: none; }
        @media (min-width: 768px) {
            .only-desktop { display: block; }
            .only-mobile { display: none !important; }
        }
        .table-shell {
            width: 100%;
            overflow: hidden;
        }
        .table-scroll {
            width: 100%;
            overflow-x: auto;
            overflow-y: visible;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            background: var(--surface);
            -webkit-overflow-scrolling: touch;
        }
        .table-scroll::-webkit-scrollbar { height: 8px; }
        .table-scroll::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }
        table.data-table {
            width: max-content;
            min-width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.8125rem;
        }
        table.data-table th, table.data-table td {
            padding: 0.5rem 0.75rem;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: middle;
        }
        table.data-table thead th {
            color: var(--text);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: none;
            white-space: nowrap;
            background: var(--surface2);
            position: sticky;
            top: 0;
            z-index: 2;
            box-shadow: 0 1px 0 var(--border);
        }
        table.data-table tbody td {
            max-width: 22rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        table.data-table tbody tr.row-click { cursor: pointer; }
        table.data-table tbody tr.row-click:nth-child(even) { background: rgba(255,255,255,.02); }
        table.data-table tbody tr.row-click:hover { background: rgba(79, 143, 201, 0.1); }

        .card-list { display: flex; flex-direction: column; gap: 0.5rem; }
        .data-card {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 0.75rem 0.9rem;
            cursor: pointer;
            width: 100%;
            text-align: left;
            font: inherit;
            color: inherit;
            -webkit-tap-highlight-color: transparent;
        }
        .data-card:active { opacity: 0.92; }
        .data-card .preview {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            line-height: 1.35;
        }
        .data-card .sub {
            font-size: 0.8rem;
            color: var(--muted);
            line-height: 1.35;
        }

        .pager {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
            font-size: 0.8125rem;
            color: var(--muted);
        }
        .pager a {
            padding: 0.3rem 0.55rem;
            border-radius: 6px;
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            text-decoration: none;
        }
        .pager a:hover { border-color: var(--accent); }

        .modal-root {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: max(1rem, env(safe-area-inset-top)) 1rem max(1rem, env(safe-area-inset-bottom));
        }
        .modal-root.is-open { display: flex; }
        .modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,.75);
            cursor: pointer;
        }
        .modal-panel {
            position: relative;
            z-index: 1;
            width: min(100vw - 1.5rem, 520px);
            max-height: min(90dvh, 720px);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--surface);
            color: var(--text);
            box-shadow: 0 12px 40px rgba(0,0,0,.5);
            display: flex;
            flex-direction: column;
        }
        .sheet-inner { padding: 1rem 1rem calc(1rem + var(--safe-b)); overflow-y: auto; flex: 1; min-height: 0; }
        .sheet-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding-bottom: 0.65rem;
            border-bottom: 1px solid var(--border);
        }
        .sheet-head h2 { margin: 0; font-size: 1rem; font-weight: 600; }
        dl.detail-grid { margin: 0; display: grid; gap: 0.65rem; }
        dl.detail-grid dt { font-size: 0.7rem; color: var(--muted); margin: 0; }
        dl.detail-grid dd { margin: 0.1rem 0 0; font-size: 0.9rem; word-break: break-word; }
        .sheet-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border); }
        .inline-form-delete { display: inline-flex; align-items: center; margin: 0; }
        textarea {
            width: 100%;
            max-width: 100%;
            padding: 0.5rem 0.65rem;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: var(--bg);
            color: var(--text);
            font-family: inherit;
            font-size: 0.9rem;
            resize: vertical;
            min-height: 2.5rem;
        }
        textarea:focus { outline: 2px solid var(--accent); outline-offset: 0; }

        .loading-overlay {
            position: fixed;
            inset: 0;
            z-index: 3000;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.6);
            padding: 1rem;
        }
        .loading-overlay.is-open { display: flex; }
        .loading-box {
            width: min(420px, 100%);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem;
            box-shadow: 0 12px 40px rgba(0,0,0,.5);
        }
        .loading-row { display: flex; align-items: center; gap: 0.75rem; }
        .spinner {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            border: 2px solid rgba(255,255,255,.2);
            border-top-color: rgba(255,255,255,.85);
            animation: spin .8s linear infinite;
            flex: 0 0 auto;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .loading-title { font-weight: 600; margin: 0; font-size: 0.95rem; }
        .loading-sub { margin: 0.15rem 0 0; color: var(--muted); font-size: 0.85rem; }
    </style>
    @stack('styles')
</head>
<body>
    <div class="wrap @yield('wrap_class')">
        @yield('content')
    </div>
    <div id="global-loading" class="loading-overlay" aria-hidden="true">
        <div class="loading-box" role="status" aria-live="polite">
            <div class="loading-row">
                <div class="spinner" aria-hidden="true"></div>
                <div>
                    <p class="loading-title" id="global-loading-title">Working…</p>
                    <p class="loading-sub" id="global-loading-sub">Please wait</p>
                </div>
            </div>
        </div>
    </div>
    @stack('scripts')
    <script>
        (function () {
            const overlay = document.getElementById('global-loading');
            const titleEl = document.getElementById('global-loading-title');
            const subEl = document.getElementById('global-loading-sub');

            window.otaLoading = {
                open: function (title, sub) {
                    if (!overlay) return;
                    titleEl.textContent = title || 'Working…';
                    subEl.textContent = sub || 'Please wait';
                    overlay.classList.add('is-open');
                    overlay.setAttribute('aria-hidden', 'false');
                },
                close: function () {
                    if (!overlay) return;
                    overlay.classList.remove('is-open');
                    overlay.setAttribute('aria-hidden', 'true');
                }
            };

            function isSwalReady() {
                return typeof window.Swal !== 'undefined' && window.Swal && typeof window.Swal.fire === 'function';
            }

            if (isSwalReady()) {
                const style = document.createElement('style');
                style.textContent = '.swal2-container{z-index:5000 !important;}';
                document.head.appendChild(style);
            }

            function swalTheme() {
                if (!isSwalReady()) return null;
                const css = getComputedStyle(document.documentElement);
                const surface = css.getPropertyValue('--surface').trim() || '#1a1d24';
                const text = css.getPropertyValue('--text').trim() || '#e4e6eb';
                const muted = css.getPropertyValue('--muted').trim() || '#8a9099';
                const accent = css.getPropertyValue('--accent').trim() || '#4f8fc9';
                const danger = css.getPropertyValue('--danger').trim() || '#c45c5c';

                return Swal.mixin({
                    background: surface,
                    color: text,
                    iconColor: text,
                    confirmButtonColor: accent,
                    cancelButtonColor: muted,
                    customClass: {
                        popup: 'swal2-popup',
                        title: 'swal2-title',
                        htmlContainer: 'swal2-html-container',
                        actions: 'swal2-actions',
                        confirmButton: 'btn btn-primary',
                        cancelButton: 'btn'
                    }
                });
            }

            const themedSwal = swalTheme();

            document.addEventListener('submit', async function (e) {
                const form = e.target.closest('form');
                if (!form) return;

                const confirmText = form.getAttribute('data-confirm');
                if (confirmText) {
                    e.preventDefault();
                    if (isSwalReady()) {
                        const fire = themedSwal ? themedSwal.fire.bind(themedSwal) : Swal.fire;
                        const res = await fire({
                            title: 'Confirm',
                            text: confirmText,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Yes',
                            cancelButtonText: 'Cancel',
                            reverseButtons: true
                        });
                        if (!res.isConfirmed) return;
                    } else {
                        if (!confirm(confirmText)) return;
                    }
                    form.removeAttribute('data-confirm');
                    form.requestSubmit();
                    return;
                }

                const loadingTitle = form.getAttribute('data-loading-title');
                if (loadingTitle) {
                    const loadingSub = form.getAttribute('data-loading-sub') || '';
                    window.otaLoading?.open(loadingTitle, loadingSub);
                    const btn = form.querySelector('button[type=\"submit\"], input[type=\"submit\"]');
                    if (btn) btn.disabled = true;
                }
            });

            document.addEventListener('click', function (e) {
                const a = e.target.closest('a[data-loading-title]');
                if (!a) return;
                const title = a.getAttribute('data-loading-title');
                const sub = a.getAttribute('data-loading-sub') || '';
                window.otaLoading?.open(title, sub);
            });

            document.addEventListener('click', async function (e) {
                const a = e.target.closest('a[data-export-xlsx]');
                if (!a) return;
                e.preventDefault();

                const url = a.getAttribute('href');
                if (!url) return;

                window.otaLoading?.open(a.getAttribute('data-loading-title') || 'Exporting…', a.getAttribute('data-loading-sub') || '');

                try {
                    const res = await fetch(url, { credentials: 'same-origin' });
                    if (!res.ok) throw new Error('Export failed');

                    const blob = await res.blob();
                    const cd = res.headers.get('Content-Disposition') || '';
                    const match = cd.match(/filename\\*=UTF-8''([^;]+)|filename=\"?([^\";]+)\"?/i);
                    const filename = match ? decodeURIComponent(match[1] || match[2]) : 'export.xlsx';

                    const blobUrl = URL.createObjectURL(blob);
                    const dl = document.createElement('a');
                    dl.href = blobUrl;
                    dl.download = filename;
                    document.body.appendChild(dl);
                    dl.click();
                    dl.remove();
                    setTimeout(function () { URL.revokeObjectURL(blobUrl); }, 1500);
                } catch (err) {
                    if (themedSwal) {
                        themedSwal.fire({ title: 'Error', text: 'Could not export the file.', icon: 'error' });
                    } else {
                        alert('Could not export the file.');
                    }
                } finally {
                    window.otaLoading?.close();
                }
            });
        })();
    </script>
</body>
</html>
