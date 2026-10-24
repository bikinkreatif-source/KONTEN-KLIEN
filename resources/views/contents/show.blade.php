@extends('layout')
@section('heading', 'Detail konten')
@section('content')
<div class="heading-row"><div><div class="eyebrow">{{ $content->client->name }} / {{ $content->type }} / {{ $content->publish_date->translatedFormat('d F Y') }}</div><h1>{{ $content->title }}</h1><p><span class="status status-{{ Str::slug($content->status) }}">{{ $content->status }}</span> · PIC: {{ $content->assignee?->name ?? 'Belum ditentukan' }}</p></div><div class="heading-actions"><a class="btn secondary" href="{{ route('dashboard',['client'=>$content->client_id,'month'=>$content->publish_date->format('Y-m')]) }}">← Kalender</a>@if(in_array(auth()->user()->role,['pm','team']))<a class="btn primary" href="{{ route('contents.edit',$content) }}">Edit konten</a>@endif</div></div>
<div class="two-col content-detail-layout"><section class="panel detail-panel"><div class="detail-meta"><span>Tanggal posting <b>{{ $content->publish_date->translatedFormat('l, d F Y') }}{{ $content->publish_time?' · '.substr($content->publish_time,0,5).' WIB':'' }}</b></span><span>Tenggat kerja <b>{{ $content->due_date?->translatedFormat('d F Y') ?? 'Belum diatur' }}</b></span><span>Pilar <b>{{ $content->pillar ?: '—' }}</b></span><span>Tujuan <b>{{ $content->funnel ?: '—' }}</b></span></div><h2>Detail isi konten</h2><div class="detail-copy">{{ $content->brief ?: 'Brief belum diisi.' }}</div><div class="caption-heading"><h2>Caption</h2>@if($content->caption)<button type="button" class="btn secondary copy-caption" data-copy-caption="contentCaption">Salin caption</button>@endif</div><div class="detail-copy caption-copy" id="contentCaption">{{ $content->caption ?: 'Caption belum diisi.' }}</div><span class="copy-feedback" id="copyFeedback" role="status" aria-live="polite"></span><h2>Langkah berikutnya</h2><div class="detail-copy">{{ $content->next_step ?: 'Belum ada langkah berikutnya.' }}</div>@if($content->note)<h2>Catatan / CTA</h2><div class="detail-copy">{{ $content->note }}</div>@endif<div class="link-row">@if($content->reference_url)<a target="_blank" rel="noopener" href="{{ $content->reference_url }}">Buka link referensi ↗</a>@endif @if($content->drive_url)<a target="_blank" rel="noopener" href="{{ $content->drive_url }}">Buka file Google Drive ↗</a>@endif</div><h2>Checklist sebelum publikasi</h2><div class="checklist-read">@foreach(['Brief sudah jelas','Caption sudah diperiksa','Link Drive / aset siap','Jadwal publikasi sudah dicek'] as $item)<div>{{ in_array($item,$content->checklist??[])?'☑':'☐' }} {{ $item }}</div>@endforeach</div></section><aside class="panel detail-panel"><span class="eyebrow">RIWAYAT & PERSETUJUAN</span><h2>Aktivitas konten</h2><div class="history-list">@forelse($content->revisions as $revision)<div class="history-event"><b>{{ $revision->action }}</b><small>{{ $revision->user?->name ?? 'Sistem' }} · {{ $revision->created_at->translatedFormat('d M Y, H:i') }}</small>@if($revision->comment)<p>{{ $revision->comment }}</p>@endif</div>@empty<p class="muted">Belum ada aktivitas.</p>@endforelse</div>
@if(auth()->user()->role==='client')<div class="approval-box"><h2>Persetujuan klien</h2><p class="muted">Konten hanya dapat dibaca. Setujui atau kirim catatan revisi kepada tim.</p><form method="post" action="{{ route('contents.review',$content) }}">@csrf<input type="hidden" name="decision" value="Perlu revisi"><label>Catatan revisi<textarea name="comment" rows="4" placeholder="Jelaskan bagian yang perlu diperbaiki"></textarea></label><button class="btn danger" onclick="return confirm('Kirim permintaan revisi kepada tim?')">Minta revisi</button></form><form method="post" action="{{ route('contents.review',$content) }}" class="approve-form">@csrf<input type="hidden" name="decision" value="Disetujui"><label>Catatan persetujuan (opsional)<input name="comment" placeholder="Catatan untuk tim"></label><button class="btn primary" onclick="return confirm('Setujui konten ini untuk dijadwalkan?')">Setujui konten</button></form></div>@endif
</aside></div>
<script>
document.querySelector('[data-copy-caption]')?.addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const caption = document.getElementById(button.dataset.copyCaption)?.innerText ?? '';
    const feedback = document.getElementById('copyFeedback');
    try {
        await navigator.clipboard.writeText(caption);
    } catch {
        const field = document.createElement('textarea');
        field.value = caption;
        field.style.position = 'fixed';
        field.style.opacity = '0';
        document.body.appendChild(field);
        field.select();
        document.execCommand('copy');
        field.remove();
    }
    button.textContent = 'Tersalin ✓';
    feedback.textContent = 'Caption tersalin. Siap ditempel ke media sosial.';
    window.setTimeout(() => { button.textContent = 'Salin caption'; feedback.textContent = ''; }, 2400);
});
</script>
@endsection
