@extends('layouts.app')

@section('title', 'Imports — '.config('app.name'))

@section('content')
<header class="app-head">
    <h1>Imports</h1>
    <a class="btn btn-ghost btn-sm" href="{{ route('spreadsheets.index') }}">Refresh</a>
</header>

@if (session('status'))
    <div class="flash" role="status">{{ session('status') }}</div>
@endif

<div class="panel">
    <h2 style="margin:0 0 0.75rem;font-size:1rem;">Upload</h2>
    <p style="margin:0 0 1rem;color:var(--muted);font-size:0.875rem;">xlsx / xls / csv — row 1 = headers</p>
    <form action="{{ route('spreadsheets.store') }}" method="post" enctype="multipart/form-data"
        data-loading-title="Importing…"
        data-loading-sub="Uploading and reading the file">
        @csrf
        <label for="file">File</label>
        <input id="file" type="file" name="file" accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv" required>
        @error('file')
            <p style="color:var(--danger);font-size:0.85rem;margin:0.35rem 0 0;">{{ $message }}</p>
        @enderror
        <div style="margin-top:1rem;">
            <button type="submit" class="btn btn-primary">Import</button>
        </div>
    </form>
</div>

<div class="panel">
    <h2 style="margin:0 0 0.75rem;font-size:1rem;">Imports</h2>
    @if ($spreadsheets->isEmpty())
        <p style="color:var(--muted);margin:0;">Nothing here yet</p>
    @else
        <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:0.5rem;">
            @foreach ($spreadsheets as $s)
                <li style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:0.5rem;padding:0.65rem 0;border-bottom:1px solid var(--border);">
                    <a href="{{ route('spreadsheets.show', $s) }}" style="font-weight:600;word-break:break-all;">{{ $s->original_name }}</a>
                    <span style="font-size:0.8rem;color:var(--muted);">{{ $s->created_at->diffForHumans() }}</span>
                </li>
            @endforeach
        </ul>
        @if ($spreadsheets->hasPages())
            <nav class="pager" aria-label="Pagination">
                @if ($spreadsheets->onFirstPage())
                    <span style="opacity:0.4;">← Prev</span>
                @else
                    <a href="{{ $spreadsheets->previousPageUrl() }}">← Prev</a>
                @endif
                <span>Page {{ $spreadsheets->currentPage() }} of {{ $spreadsheets->lastPage() }}</span>
                @if ($spreadsheets->hasMorePages())
                    <a href="{{ $spreadsheets->nextPageUrl() }}">Next →</a>
                @else
                    <span style="opacity:0.4;">Next →</span>
                @endif
            </nav>
        @endif
    @endif
</div>
@endsection
