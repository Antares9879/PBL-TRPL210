@extends('layouts.admin')

@section('title', 'Planning Kerja')
@section('breadcrumb-parent', 'Admin Outsource')
@section('breadcrumb-current', 'Planning Kerja')
@section('sidebar-role', 'Admin Outsource')

@section('sidebar-nav')
    @include('admin-outsource._sidebar-nav')
@endsection

@section('content')
{{--
    Seluruh UI dibangun oleh planning.js (buildUI()).
    Blade hanya menyediakan container kosong.
    CSS tambahan sudah di-push via @push('styles').
--}}
<div class="dashboard-wrap" id="planning-root">
    <div style="text-align:center;padding:60px;color:#94a3b8;">
        <div class="skel" style="height:32px;width:300px;border-radius:8px;margin:0 auto 16px;"></div>
        <div class="skel" style="height:16px;width:200px;border-radius:4px;margin:0 auto;"></div>
    </div>
</div>
@endsection

@push('styles')
    {{-- CSS planning sudah digabung ke admin.css, tidak perlu entry terpisah --}}
@endpush

@push('scripts')
    {{-- SheetJS dimuat dari CDN sebagai module --}}
    <script type="importmap">
    {
        "imports": {
            "https://cdn.sheetjs.com/xlsx-0.20.0/package/xlsx.mjs": "https://cdn.sheetjs.com/xlsx-0.20.0/package/xlsx.mjs"
        }
    }
    </script>
    @vite(['resources/js/admin-outsource/planning.js'])
@endpush