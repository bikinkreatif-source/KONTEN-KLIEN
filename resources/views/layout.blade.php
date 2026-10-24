<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Ruang Konten') · Bikin Kreatif</title>
    <link rel="stylesheet" href="{{ asset('app.css') }}?v={{ md5_file(public_path('app.css')) }}">
</head>
<body>
@auth
<div class="shell">
    @php($navClientId = isset($client) && $client ? $client->id : (isset($clients) ? $clients->first()?->id : null))
    <aside class="sidebar" id="sidebar">
        <a href="{{ route('dashboard') }}" class="brand">
            <img src="{{ asset('logo-bikin-kreatif.png') }}" alt="Bikin Kreatif">
            <span>Ruang Konten</span>
        </a>
        <div class="nav-label">WORKSPACE</div>
        <a class="nav-link {{ request()->routeIs('dashboard') && !request('status') && request('view')!=='report' ? 'active' : '' }}" href="{{ auth()->user()->role==='pm' ? route('dashboard').'#summary' : route('dashboard',['client'=>$navClientId]).'#summary' }}"><span>▦</span> {{ auth()->user()->role==='pm'?'Dashboard PM':'Ringkasan' }}</a>
        <a class="nav-link" href="{{ route('dashboard', ['client'=>$navClientId]) }}#calendar"><span>▣</span> Kalender konten</a>
        <a class="nav-link {{ request()->routeIs('guide') ? 'active' : '' }}" href="{{ route('guide') }}"><span>ⓘ</span> Panduan</a>
        @if(isset($clients) && $clients->count())
            <div class="nav-label">DAFTAR KLIEN</div>
            <div class="sidebar-client-list">
                @foreach($clients as $sidebarClient)
                    <a class="sidebar-client {{ isset($client) && $client?->id === $sidebarClient->id ? 'selected' : '' }}" href="{{ route('dashboard', ['client' => $sidebarClient->id, 'month' => $month ?? now()->format('Y-m')]) }}">
                        @if($sidebarClient->logo_path)<img src="{{ Storage::disk('public')->url($sidebarClient->logo_path) }}" alt="">@else<span class="sidebar-client-avatar">{{ strtoupper(substr($sidebarClient->name, 0, 1)) }}</span>@endif
                        <span>{{ $sidebarClient->name }}</span>
                    </a>
                @endforeach
            </div>
            @if(auth()->user()->role === 'pm')<a class="sidebar-add-client" href="{{ route('clients.create') }}">＋ Tambah klien</a>@endif
        @endif
        @if(auth()->user()->role === 'pm')
            <div class="nav-label settings-nav">PENGATURAN</div>
            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><span>♙</span> Tim & pengguna</a>
        @endif
        <div class="account">
            <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <div><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->division ?: ['pm' => 'Project manager', 'team' => 'Tim', 'client' => 'Klien'][auth()->user()->role] }}</small></div>
        </div>
        <form method="post" action="{{ route('logout') }}">@csrf<button class="nav-link logout"><span>↪</span> Keluar</button></form>
    </aside>
    <main class="main">
        <header class="topbar">
            <button class="menu-btn" onclick="document.getElementById('sidebar').classList.toggle('open')" aria-label="Buka menu">☰</button>
            <button class="collapse-btn" onclick="document.querySelector('.shell').classList.toggle('collapsed')" aria-label="Ciutkan sidebar">☰</button>
            <div><small>RUANG KONTEN / {{ now()->translatedFormat('l, d F Y') }}</small><b>@yield('heading', 'Dashboard')</b></div>
            <span class="role-tag">{{ ['pm' => 'PROJECT MANAGER', 'team' => 'TIM', 'client' => 'KLIEN'][auth()->user()->role] }}</span>
        </header>
        <section class="page">
            @if(session('success'))<div class="flash">✓ {{ session('success') }}</div>@endif
            @if($errors->any())<div class="errors">{{ $errors->first() }}</div>@endif
            @yield('content')
        </section>
    </main>
</div>
@else
    <main>@yield('content')</main>
@endauth
</body>
</html>
