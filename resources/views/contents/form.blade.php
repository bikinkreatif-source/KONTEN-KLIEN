@extends('layout')
@section('heading', $content ? 'Edit konten' : 'Tambah konten')
@section('content')
<div class="heading-row"><div><div class="eyebrow">{{ $client->name }} / KONTEN</div><h1>{{ $content ? 'Perbarui detail konten' : 'Buat kartu konten' }}</h1><p>Isi brief, caption, PIC, dan tautan agar tim tahu langkah berikutnya.</p></div><div class="heading-actions"><a class="btn secondary" href="{{ route('dashboard',['client'=>$client->id]) }}">← Kalender</a>@if($content)<a class="btn secondary" href="{{ route('contents.show',$content) }}">Lihat kartu</a>@endif</div></div>
<form class="panel form-panel" method="post" action="{{ $content ? route('contents.update',$content) : route('contents.store') }}">@csrf @if($content) @method('PUT') @else <input type="hidden" name="client_id" value="{{ $client->id }}"> @endif
    <div class="form-grid">
        <label class="span-2">Judul konten *<input name="title" required value="{{ old('title',$content?->title) }}" placeholder="Contoh: 5 tips memilih rumah pertama"></label>
        <label>Kanal<select name="type">@foreach(['Single Image','Carousel','Reels','Story'] as $type)<option @selected(old('type',$content?->type)===$type)>{{ $type }}</option>@endforeach</select></label>
        <label>Status alur kerja<select name="status">@foreach(['Ide','Dalam proses','Menunggu review','Menunggu persetujuan klien','Perlu revisi','Disetujui','Terjadwal','Tayang'] as $status)<option @selected(old('status',$content?->status??'Ide')===$status)>{{ $status }}</option>@endforeach</select></label>
        <label>Tanggal posting *<input type="date" name="publish_date" required min="{{ $client->start_date->format('Y-m-d') }}" max="{{ $client->end_date->format('Y-m-d') }}" value="{{ old('publish_date',request('date',$content?->publish_date?->format('Y-m-d'))) }}"></label>
        <label>Tenggat pengerjaan<input type="date" name="due_date" value="{{ old('due_date',$content?->due_date?->format('Y-m-d')) }}"></label>
        <label>Jam posting<input type="time" name="publish_time" value="{{ old('publish_time',$content ? substr($content->publish_time,0,5) : '') }}"></label>
        <label>PIC<select name="assignee_id"><option value="">Belum ditentukan</option>@foreach($team as $member)<option value="{{ $member->id }}" @selected(old('assignee_id',$content?->assignee_id)===$member->id)>{{ $member->name }} · {{ $member->division ?: 'Project Manager' }}</option>@endforeach</select></label>
        <label>Pilar konten<input name="pillar" value="{{ old('pillar',$content?->pillar) }}" placeholder="Pilih / tulis pilar"></label>
        <label>Tujuan konten<input name="funnel" value="{{ old('funnel',$content?->funnel) }}" placeholder="Awareness, consideration, conversion"></label>
        <label class="span-2">Detail isi / arahan desain<textarea name="brief" rows="5" placeholder="Apa yang perlu dibuat? Masukkan poin utama, urutan slide, visual, atau arahan video.">{{ old('brief',$content?->brief) }}</textarea></label>
        <label class="span-2">Caption<textarea name="caption" rows="5" placeholder="Tulis caption dan hashtag di sini">{{ old('caption',$content?->caption) }}</textarea></label>
        <label>Link referensi<input type="url" name="reference_url" value="{{ old('reference_url',$content?->reference_url) }}" placeholder="https://..."></label>
        <label>Link file Google Drive<input type="url" name="drive_url" value="{{ old('drive_url',$content?->drive_url) }}" placeholder="https://drive.google.com/..."></label>
        <label>Langkah berikutnya<textarea name="next_step" rows="2" placeholder="Contoh: Desainer buat visual awal sebelum Kamis">{{ old('next_step',$content?->next_step) }}</textarea></label>
        <label>CTA / catatan<textarea name="note" rows="2" placeholder="Catatan internal atau ajakan tindakan">{{ old('note',$content?->note) }}</textarea></label>
        <fieldset class="span-2 checklist"><legend>Checklist sebelum publikasi</legend>@foreach(['Brief sudah jelas','Caption sudah diperiksa','Link Drive / aset siap','Jadwal publikasi sudah dicek'] as $item)<label><input type="checkbox" name="checklist[]" value="{{ $item }}" @checked(in_array($item,old('checklist',$content?->checklist??[])))> {{ $item }}</label>@endforeach</fieldset>
        @if($content)<label class="span-2">Catatan aktivitas / revisi<textarea name="revision_comment" rows="3" placeholder="Tuliskan perubahan atau arahan untuk anggota tim lainnya"></textarea></label>@endif
    </div>
    @if($content&&$content->revisions->count())<div class="history"><h3>Riwayat aktivitas</h3>@foreach($content->revisions as $revision)<div class="history-event"><b>{{ $revision->action }}</b><small>{{ $revision->user?->name??'Sistem' }} · {{ $revision->created_at->translatedFormat('d M Y, H:i') }}</small>@if($revision->comment)<p>{{ $revision->comment }}</p>@endif</div>@endforeach</div>@endif
    <div class="form-actions">@if($content)<button type="submit" form="delete-content" class="btn danger" onclick="return confirm('Hapus konten ini?')">Hapus konten</button>@endif<a class="btn secondary" href="{{ route('dashboard',['client'=>$client->id]) }}">Batal</a><button class="btn primary" onclick="return confirm('{{ $content ? 'Simpan perubahan konten ini?' : 'Simpan konten ini ke kalender?' }}')">Simpan konten</button></div>
</form>
@if($content)<form id="delete-content" method="post" action="{{ route('contents.destroy',$content) }}">@csrf @method('DELETE')</form>@endif
@endsection
