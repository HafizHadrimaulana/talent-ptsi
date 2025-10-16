@php $count = (int)($masterCount ?? 0); @endphp
@if($count > 0 && $count <= 1200)
  <div class="alert card-glass warn" style="margin-bottom:12px">
    <strong>⚠️ Data Master Belum Lengkap:</strong>
    Sinkronisasi SITMS saat ini <strong>{{ $count }}</strong> (kemungkinan hanya aktif).
    Proses tetap bisa dilanjutkan; data non-aktif akan ter-backfill begitu API vendor dibuka.
  </div>
@endif
