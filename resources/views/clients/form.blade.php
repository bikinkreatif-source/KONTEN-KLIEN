@extends('layout')
@section('heading', $client ? 'Edit klien' : 'Tambah klien')
@section('content')
<div class="heading-row"><div><div class="eyebrow">WORKSPACE / KLIEN</div><h1>{{ $client ? 'Edit informasi klien' : 'Buat workspace klien' }}</h1><p>Satu klien memiliki periode dan rencana konten tersendiri.</p></div><a class="btn secondary" href="{{ route('dashboard', $client ? ['client'=>$client->id] : []) }}">← Kembali</a></div>
<form class="panel form-panel" method="post" enctype="multipart/form-data" action="{{ $client ? route('clients.update',$client) : route('clients.store') }}">
    @csrf @if($client) @method('PUT') @endif
    <div class="form-grid">
        <label class="span-2">Nama klien *<input name="name" required value="{{ old('name',$client?->name) }}" placeholder="Contoh: Juragan Property"></label>
        <label>Tanggal mulai posting *<input type="date" name="start_date" required value="{{ old('start_date',$client?->start_date?->format('Y-m-d')) }}"></label>
        <label>Tanggal akhir posting *<input type="date" name="end_date" required value="{{ old('end_date',$client?->end_date?->format('Y-m-d')) }}"></label>
        <div class="span-2"><h3>Target produksi konten</h3><p class="muted">Jumlah konten yang perlu disiapkan tim dalam periode ini.</p></div>
        @foreach(['single'=>'Single image','carousel'=>'Carousel','reels'=>'Reels','story'=>'Story'] as $key=>$label)<label>{{ $label }}<input type="number" min="0" name="quota_{{ $key }}" value="{{ old('quota_'.$key,$client?->{'quota_'.$key}??0) }}"></label>@endforeach
        <div class="span-2"><h3>Akun sosial & aset</h3><p class="muted">Tautan ini bisa diperbarui oleh PM kapan saja.</p></div>
        <label>Instagram<input type="url" name="instagram" value="{{ old('instagram',$client?->instagram) }}" placeholder="https://instagram.com/akun"></label>
        <label>TikTok<input type="url" name="tiktok" value="{{ old('tiktok',$client?->tiktok) }}" placeholder="https://tiktok.com/@akun"></label>
        <label>Facebook<input type="url" name="facebook" value="{{ old('facebook',$client?->facebook) }}" placeholder="https://facebook.com/akun"></label>
        <label>Folder Google Drive<input type="url" name="drive_url" value="{{ old('drive_url',$client?->drive_url) }}" placeholder="https://drive.google.com/..."></label>
        <label class="span-2">Logo klien (maks. 350 KB)<input type="file" name="logo" accept="image/*">@if($client?->logo_path)<small class="muted">Logo saat ini: <img class="client-logo-preview" src="{{ Storage::disk('public')->url($client->logo_path) }}" alt=""></small>@endif</label>
        <label class="span-2">Informasi untuk tim<textarea name="information" rows="4" placeholder="Target audiens, tone of voice, catatan penting">{{ old('information',$client?->information) }}</textarea></label>
        <label class="span-2">Pilar konten<textarea name="pillars" rows="3" placeholder="Contoh: Edukasi, inspirasi, promosi">{{ old('pillars',$client?->pillars) }}</textarea><small class="muted">Content writer dan sosmed specialist juga bisa memperbarui pilar di planner.</small></label>
    </div>
    <div class="form-actions"><a class="btn secondary" href="{{ route('dashboard', $client ? ['client'=>$client->id] : []) }}">Batal</a><button class="btn primary" onclick="return confirm('Simpan perubahan informasi klien?')">Simpan klien</button></div>
</form>
@if($client)
<section class="panel form-panel team-assignment-panel"><div class="eyebrow">TIM PLANNER</div><h2>Orang yang mengerjakan planner ini</h2><p class="muted">Anggota yang dipilih akan melihat planner klien ini di dashboard mereka.</p>
    <form method="post" action="{{ route('clients.team',$client) }}">@csrf @method('PUT')
        @if($teamMembers->isEmpty())<p class="empty">Belum ada akun tim. Buat akun terlebih dahulu di menu Tim & pengguna.</p>@else
            <div class="team-checkbox-grid">@foreach($teamMembers as $member)<label class="team-checkbox"><input type="checkbox" name="team_ids[]" value="{{ $member->id }}" @checked(in_array($member->id,$assignedTeamIds))><span class="avatar">{{ strtoupper(substr($member->name,0,1)) }}</span><span><b>{{ $member->name }}</b><small>{{ $member->division }}</small></span></label>@endforeach</div>
        @endif
        <div class="form-actions"><button class="btn primary" onclick="return confirm('Simpan penugasan tim untuk {{ $client->name }}?')">Simpan tim planner</button></div>
    </form>
</section>
@endif
@endsection
