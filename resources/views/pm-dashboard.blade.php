@extends('layout')
@section('title', 'Dashboard Project Manager')
@section('heading', 'Dashboard Project Manager')
@section('content')
<div class="pm-welcome" id="summary">
    <div><span class="eyebrow">WORKSPACE AGENSI · {{ now()->translatedFormat('l, d F Y') }}</span><h1>Selamat {{ now()->hour < 11 ? 'pagi' : (now()->hour < 15 ? 'siang' : (now()->hour < 18 ? 'sore' : 'malam')) }}, {{ auth()->user()->name }} <span>✳</span></h1><p>Ringkasan pekerjaan tim dan semua klien Anda.</p></div>
    <div class="pm-actions"><a class="btn secondary" href="{{ route('users.index') }}">♙ Kelola tim</a><a class="btn primary" href="{{ route('clients.create') }}">＋ Tambah klien</a></div>
</div>

<form class="pm-period" method="get" action="{{ route('dashboard') }}"><label>Periode ringkasan<select name="month" onchange="this.form.submit()">@foreach(collect(range(-5, 6))->map(fn($offset)=>now()->startOfMonth()->addMonths($offset)) as $period)<option value="{{ $period->format('Y-m') }}" @selected($month===$period->format('Y-m'))>{{ $period->translatedFormat('F Y') }}</option>@endforeach</select></label><span>Angka konten dan status mengikuti bulan yang dipilih.</span></form>

<div class="stats pm-stats" id="pm-stats">
    <article class="stat"><div class="stat-top">Klien aktif <span class="stat-ico">♙</span></div><strong>{{ $clients->count() }}</strong><div class="hint">{{ $clients->filter(fn($c)=>$c->start_date->lte(now()->startOfDay()) && $c->end_date->gte(now()->startOfDay()))->count() }} memiliki periode berjalan</div></article>
    <article class="stat"><div class="stat-top">Konten periode ini <span class="stat-ico">▤</span></div><strong>{{ $monthContents->count() }}</strong><div class="hint">{{ $monthContents->whereIn('status',['Disetujui','Terjadwal','Tayang'])->count() }} siap atau sudah tayang</div></article>
    <article class="stat"><div class="stat-top">Menunggu persetujuan <span class="stat-ico">◷</span></div><strong>{{ $awaitingApproval->count() }}</strong><div class="hint">Konten menunggu keputusan klien</div></article>
    <article class="stat"><div class="stat-top">Tenggat terlewat <span class="stat-ico">!</span></div><strong>{{ $overdueContents->count() }}</strong><div class="hint">{{ $teamCount }} anggota tim terdaftar</div></article>
</div>

<div class="pm-dashboard-grid">
    <section class="panel pm-clients" id="pm-work">
        <div class="panel-head"><div><span class="eyebrow">WORKSPACE KLIEN</span><h2>Planner klien</h2></div><a class="seeall" href="{{ route('clients.create') }}">＋ Tambah klien</a></div>
        @if($clients->isEmpty())<div class="empty">Belum ada klien. Tambahkan klien untuk mulai membuat content plan.</div>@else
        <div class="pm-client-list">
            @foreach($clients as $clientItem)
                @php($target=$clientItem->quota_single+$clientItem->quota_carousel+$clientItem->quota_reels+$clientItem->quota_story)
                <a class="pm-client-card" href="{{ route('dashboard',['client'=>$clientItem->id,'month'=>$month]) }}">
                    <div class="pm-client-top"><span class="pm-client-avatar">@if($clientItem->logo_path)<img src="{{ Storage::disk('public')->url($clientItem->logo_path) }}" alt="">@else{{ strtoupper(substr($clientItem->name,0,1)) }}@endif</span><div class="pm-client-name"><b>{{ $clientItem->name }}</b><small>{{ $clientItem->start_date->translatedFormat('d M') }} – {{ $clientItem->end_date->translatedFormat('d M Y') }}</small></div><span class="pm-open">Buka planner →</span></div>
                    <div class="pm-client-metrics"><span><b>{{ $clientMonthCounts->get($clientItem->id,0) }}</b> konten bulan ini</span><span><b>{{ $clientItem->team_members_count }}</b> anggota tim</span><span><b>{{ $target }}</b> target plan</span></div>
                    <div class="pm-client-progress"><span>Progress konten</span><div class="track"><div class="fill" style="width:{{ $target?min(100,round($clientMonthCounts->get($clientItem->id,0)/$target*100)):0 }}%"></div></div><b>{{ $target?min(100,round($clientMonthCounts->get($clientItem->id,0)/$target*100)):0 }}%</b></div>
                </a>
            @endforeach
        </div>@endif
    </section>

    <section class="panel pm-status-panel">
        <div class="panel-head"><div><span class="eyebrow">ALUR KERJA</span><h2>Status konten · {{ \Carbon\Carbon::createFromFormat('Y-m',$month)->translatedFormat('F') }}</h2></div></div>
        @foreach([['Ide','Ide'],['Dalam proses','Dalam proses'],['Menunggu review','Menunggu review'],['Menunggu persetujuan klien','Persetujuan klien'],['Perlu revisi','Perlu revisi'],['Disetujui','Disetujui'],['Terjadwal','Terjadwal'],['Tayang','Tayang']] as [$status,$label])
            <div class="pm-status-row"><span class="status-key status-{{ Str::slug($status) }}"><i></i>{{ $label }}</span><b>{{ $statusCounts->get($status,0) }}</b></div>
        @endforeach
    </section>
</div>

<div class="pm-queues">
    <section class="panel pm-queue" id="pm-approvals"><div class="panel-head"><div><span class="eyebrow">TINJAUAN KLIEN</span><h2>Menunggu persetujuan</h2></div><span class="queue-count">{{ $awaitingApproval->count() }}</span></div>
        @forelse($awaitingApproval->take(5) as $item)<a class="pm-queue-item" href="{{ route('contents.show',$item) }}"><span class="queue-date">{{ $item->publish_date->translatedFormat('d M') }}</span><span class="queue-info"><b>{{ $item->title }}</b><small>{{ $item->client->name }} · {{ $item->assignee?->name ?? 'PIC belum dipilih' }}</small></span><span class="status status-menunggu-persetujuan-klien">Menunggu persetujuan</span></a>@empty<p class="pm-empty">Tidak ada konten yang menunggu persetujuan.</p>@endforelse
    </section>
    <section class="panel pm-queue"><div class="panel-head"><div><span class="eyebrow">PERLU DITINDAKLANJUTI</span><h2>Tenggat terlewat</h2></div><span class="queue-count overdue-count">{{ $overdueContents->count() }}</span></div>
        @forelse($overdueContents->take(5) as $item)<a class="pm-queue-item" href="{{ route('contents.show',$item) }}"><span class="queue-date overdue-date">{{ $item->due_date->translatedFormat('d M') }}</span><span class="queue-info"><b>{{ $item->title }}</b><small>{{ $item->client->name }} · {{ $item->assignee?->name ?? 'PIC belum dipilih' }}</small></span><span class="status status-perlu-revisi">{{ $item->status }}</span></a>@empty<p class="pm-empty">Semua tenggat aman.</p>@endforelse
    </section>
</div>

<section class="panel pm-upcoming"><div class="panel-head"><div><span class="eyebrow">7 HARI KE DEPAN</span><h2>Jadwal publikasi</h2></div></div>
    <div class="pm-upcoming-list">@forelse($upcomingContents->take(8) as $item)<a class="pm-upcoming-item" href="{{ route('contents.show',$item) }}"><span class="datebox"><small>{{ $item->publish_date->translatedFormat('M') }}</small><b>{{ $item->publish_date->format('d') }}</b></span><span class="queue-info"><b>{{ $item->title }}</b><small>{{ $item->client->name }} · {{ $item->type }} · {{ $item->assignee?->name ?? 'PIC belum dipilih' }}</small></span><span class="status status-{{ Str::slug($item->status) }}">{{ $item->status }}</span></a>@empty<p class="pm-empty">Belum ada jadwal publikasi dalam 7 hari ke depan.</p>@endforelse</div>
</section>
@endsection
