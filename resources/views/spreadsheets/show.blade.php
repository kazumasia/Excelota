@extends('layouts.app')

@section('wrap_class', 'wrap--wide')

@section('title', $spreadsheet->original_name.' — '.config('app.name'))

@section('content')
<header class="app-head">
    <h1 style="word-break:break-word;">{{ $spreadsheet->original_name }}</h1>
    <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
        <a class="btn btn-sm btn-ghost" href="{{ route('spreadsheets.index') }}">All imports</a>
        <a class="btn btn-sm btn-primary"
            href="{{ route('spreadsheets.export', $spreadsheet) }}"
            data-export-xlsx="1"
            data-loading-title="Exporting…"
            data-loading-sub="Preparing the Excel file">Export Excel</a>
    </div>
</header>

@if (session('status'))
    <div class="flash" role="status">{{ session('status') }}</div>
@endif

<div class="panel">
    <form id="search-form" method="get" action="{{ route('spreadsheets.show', $spreadsheet) }}" style="display:flex;flex-direction:column;gap:0.5rem;">
        <label for="q">Search</label>
        <div style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:stretch;">
            <input id="q" type="search" name="q" value="{{ $q }}" autocomplete="off" placeholder="…" style="flex:1;min-width:0;">
            <button type="submit" class="btn btn-primary" style="flex-shrink:0;">Search</button>
            <a href="{{ route('spreadsheets.show', $spreadsheet) }}" class="btn js-clear-search" style="flex-shrink:0;">Clear</a>
        </div>
    </form>
</div>

@include('spreadsheets.partials.live-results', ['spreadsheet' => $spreadsheet, 'rows' => $rows, 'q' => $q])

<div class="panel" style="margin-top:1rem;">
    <form method="post"
        action="{{ route('spreadsheets.destroy', $spreadsheet) }}"
        data-confirm="Delete this import and all rows?"
        data-loading-title="Deleting…"
        data-loading-sub="Removing the import">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm">Delete this import</button>
    </form>
</div>

<div id="modal-edit" class="modal-root" aria-hidden="true">
    <div class="modal-backdrop" tabindex="-1" aria-hidden="true"></div>
    <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="modal-edit-title">
        <div class="sheet-inner">
            <div class="sheet-head">
                <h2 id="modal-edit-title">Edit row</h2>
                <button type="button" class="btn btn-sm btn-ghost" data-close-modal>Close</button>
            </div>
            <form method="post" id="form-edit-row" style="display:flex;flex-direction:column;gap:0.75rem;">
                @csrf
                @method('PATCH')
                <input type="hidden" name="q" value="{{ $q }}" data-sync-from-search>
                <div id="dlg-edit-fields" style="display:flex;flex-direction:column;gap:0.75rem;"></div>
            </form>
            <div class="sheet-actions" style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);">
                <button type="submit" form="form-edit-row" class="btn btn-primary" id="btn-save-row">Save</button>
                <form method="post"
                    id="form-delete-row"
                    class="inline-form-delete"
                    data-confirm="Delete this row?"
                    data-loading-title="Deleting…"
                    data-loading-sub="Removing the row">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="q" value="{{ $q }}" data-sync-from-search>
                    <button type="submit" class="btn btn-danger btn-sm">Delete row</button>
                </form>
                <button type="button" class="btn btn-ghost" data-close-modal>Cancel</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const columns = @json($spreadsheet->columns);
    const showUrl = @json(route('spreadsheets.show', $spreadsheet));
    const baseRowUrl = @json(rtrim((string) url('/spreadsheets/'.$spreadsheet->id.'/rows'), '/'));
    const rowUrl = function (id) { return baseRowUrl + '/' + id; };

    const modalEdit = document.getElementById('modal-edit');
    const formEdit = document.getElementById('form-edit-row');
    const formDelete = document.getElementById('form-delete-row');
    const editFields = document.getElementById('dlg-edit-fields');
    const searchInput = document.getElementById('q');
    const searchForm = document.getElementById('search-form');

    let debounceTimer = null;
    let lastLiveQuery = searchInput ? searchInput.value.trim() : '';

    function getRowsById() {
        const el = document.getElementById('rows-page-json');
        const m = {};
        if (el) {
            try {
                JSON.parse(el.textContent || '[]').forEach(function (r) {
                    m[r.id] = r;
                });
            } catch (e) {}
        }
        return m;
    }

    function syncSearchHiddenInputs() {
        const qv = searchInput ? searchInput.value : '';
        document.querySelectorAll('input[name="q"][data-sync-from-search]').forEach(function (inp) {
            inp.value = qv;
        });
    }

    function syncBodyScroll() {
        document.body.style.overflow = document.querySelector('.modal-root.is-open') ? 'hidden' : '';
    }

    function setModal(el, open) {
        if (!el) return;
        el.classList.toggle('is-open', open);
        el.setAttribute('aria-hidden', open ? 'false' : 'true');
        syncBodyScroll();
    }

    function closeAllModals() {
        setModal(modalEdit, false);
    }

    function openEdit(row) {
        formEdit.action = rowUrl(row.id);
        formDelete.action = rowUrl(row.id);
        syncSearchHiddenInputs();
        editFields.innerHTML = '';
        columns.forEach(function (c) {
            const wrap = document.createElement('div');
            const lab = document.createElement('label');
            lab.setAttribute('for', 'f-' + c.key);
            lab.textContent = c.label;
            const inp = document.createElement('textarea');
            inp.name = 'data[' + c.key + ']';
            inp.id = 'f-' + c.key;
            inp.rows = Math.min(4, Math.max(2, Math.ceil(String(row.data[c.key] || '').length / 60)));
            inp.value = row.data[c.key] != null ? String(row.data[c.key]) : '';
            wrap.appendChild(lab);
            wrap.appendChild(inp);
            editFields.appendChild(wrap);
        });
        setModal(modalEdit, true);
        setTimeout(function () {
            const first = editFields.querySelector('textarea, input');
            if (first) first.focus();
        }, 50);
    }

    async function fetchLive(url) {
        const res = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
            credentials: 'same-origin',
        });
        if (!res.ok) return;
        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const next = doc.getElementById('sheet-live-region');
        const cur = document.getElementById('sheet-live-region');
        if (next && cur) {
            cur.replaceWith(document.importNode(next, true));
        }
        syncSearchHiddenInputs();
        lastLiveQuery = searchInput ? searchInput.value.trim() : '';
    }

    function scheduleLiveSearch() {
        const q = searchInput.value.trim();
        if (q === lastLiveQuery) return;
        lastLiveQuery = q;
        const u = new URL(showUrl, window.location.origin);
        u.searchParams.set('q', q);
        u.searchParams.set('page', '1');
        fetchLive(u.toString());
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(scheduleLiveSearch, 320);
        });
    }

    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const u = new URL(showUrl, window.location.origin);
            u.searchParams.set('q', searchInput.value.trim());
            u.searchParams.set('page', '1');
            fetchLive(u.toString());
        });
    }

    document.querySelector('.js-clear-search')?.addEventListener('click', function (e) {
        e.preventDefault();
        searchInput.value = '';
        const u = new URL(showUrl, window.location.origin);
        u.searchParams.set('page', '1');
        fetchLive(u.toString());
    });

    document.addEventListener('click', function (e) {
        const pager = e.target.closest('a.js-live-pager');
        if (pager && document.getElementById('sheet-live-region')?.contains(pager)) {
            e.preventDefault();
            fetchLive(pager.href);
            return;
        }
        const card = e.target.closest('#card-list .data-card[data-row-id]');
        if (card) {
            const id = parseInt(card.getAttribute('data-row-id'), 10);
            const row = getRowsById()[id];
            if (row) openEdit(row);
            return;
        }
        const tr = e.target.closest('#data-table-desktop tr[data-row-id]');
        if (tr) {
            const id = parseInt(tr.getAttribute('data-row-id'), 10);
            const row = getRowsById()[id];
            if (row) openEdit(row);
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAllModals();
            return;
        }
        const tr = e.target.closest('#data-table-desktop tr[data-row-id]');
        if (!tr) return;
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            const id = parseInt(tr.getAttribute('data-row-id'), 10);
            const row = getRowsById()[id];
            if (row) openEdit(row);
        }
    });

    modalEdit.querySelector('.modal-backdrop').addEventListener('click', function () {
        setModal(modalEdit, false);
    });
    modalEdit.querySelectorAll('[data-close-modal]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            setModal(modalEdit, false);
        });
    });

    formEdit.addEventListener('submit', function () {
        window.otaLoading?.open('Saving…', 'Updating the row');
        const saveBtn = document.getElementById('btn-save-row');
        if (saveBtn) saveBtn.disabled = true;
    });
})();
</script>
@endpush
@endsection
