@extends('layouts.app')

@section('title', 'Template Dokumen')

@section('content')
<div class="u-card u-card--glass u-p-lg">
    <div class="u-flex u-justify-between u-items-center u-mb-md">
        <div>
            <h2 class="u-title">Template Dokumen</h2>
            <p class="u-text-muted u-text-sm">Pengaturan format HTML dan CSS untuk dokumen kontrak.</p>
        </div>
        <a href="{{ route('admin.contract-templates.create') }}" class="u-btn u-btn--brand u-shadow-sm" style="border-radius: 999px;">
            <i class="fas fa-plus u-mr-xs"></i> Template Baru
        </a>
    </div>

    @if(session('success'))
        <div class="u-card u-p-sm u-mb-md u-success"><i class="fas fa-check-circle u-mr-sm"></i> {{ session('success') }}</div>
    @endif

    <div class="dt-wrapper">
        <div class="u-overflow-x-auto">
            <table id="templates-table" class="u-table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Template</th>
                        <th>Update Terakhir</th>
                        <th class="u-text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($templates as $t)
                    <tr>
                        <td><span class="u-badge u-badge--info">{{ $t->code }}</span></td>
                        <td class="u-font-bold">{{ $t->name }}</td>
                        <td class="u-text-muted u-text-sm">{{ $t->updated_at->format('d M Y H:i') }}</td>
                        <td class="u-text-right">
                            <a href="{{ route('admin.contract-templates.edit', $t->id) }}" class="u-btn u-btn--sm u-btn--outline u-btn--icon" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.contract-templates.destroy', $t->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Hapus template ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="u-btn u-btn--sm u-btn--danger u-btn--icon" title="Hapus"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if(window.initDataTables) {
        window.initDataTables('#templates-table', {
            serverSide: false,
            processing: false,
            ajax: null,
            order: [[1, 'asc']]
        });
    }
});
</script>
@endsection