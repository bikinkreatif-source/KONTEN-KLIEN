@extends('layout')
@section('heading', 'Pengaturan tim')
@section('content')
<div class="heading-row"><div><div class="eyebrow">PENGATURAN WORKSPACE</div><h1>Tim & pengguna</h1><p>Buat akun masuk untuk project manager, anggota tim, atau klien.</p></div><a class="btn secondary" href="{{ route('dashboard') }}">← Kembali ke planner</a></div>
<div class="two-col users-layout">
    <section class="panel form-panel"><h2>Tambah akun</h2><p>Anggota tim memiliki akses hanya ke planner klien tempat PM menugaskannya.</p>
        <form method="post" action="{{ route('users.store') }}" class="user-account-form">@csrf
            <div class="form-grid">
                <label>Nama lengkap<input name="name" required></label>
                <label>Email login<input type="email" name="email" required></label>
                <label>Jenis akun<select name="role" class="role-choice"><option value="pm">Project manager</option><option value="team">Anggota tim</option><option value="client">Klien · view only</option></select></label>
                <label class="job-field">Job / divisi<select name="division" required><option value="">Pilih job</option><option>Graphic Designer</option><option>Content Writer</option><option>Video Creator</option><option>Admin Sosmed</option><option>Sosmed Specialist</option></select></label>
                <label class="client-account-field span-2" hidden>Klien untuk akun ini<select name="client_id"><option value="">Pilih klien</option>@foreach($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select></label>
                <label class="span-2">Kata sandi awal · minimal 8 karakter<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
            </div>
            <div class="form-actions"><button class="btn primary" onclick="return confirm('Buat akun pengguna ini?')">＋ Buat akun</button></div>
        </form>
    </section>
    <section class="panel form-panel"><h2>Hak akses</h2><div class="role-info"><b>Project manager</b><p>Mengelola klien, menugaskan anggota tim, kalender, dan akun.</p><b>Anggota tim</b><p>Melihat planner yang ditugaskan PM. Job menentukan fokus pekerjaan.</p><b>Klien</b><p>Melihat planner sendiri dan menyetujui atau meminta revisi konten.</p></div></section>
</div>
<section class="panel content-list"><div class="panel-head"><div><span class="eyebrow">AKUN TERDAFTAR</span><h2>{{ $users->count() }} pengguna</h2></div></div>
@foreach($users as $user)
<details class="user-edit"><summary><span class="avatar">{{ strtoupper(substr($user->name,0,1)) }}</span><span><b>{{ $user->name }}</b><small>{{ $user->email }} · {{ ['pm'=>'Project manager','team'=>$user->division?:'Tim','client'=>'Klien'][ $user->role] }}{{ $user->client?' · '.$user->client->name:'' }}</small></span></summary>
    <form method="post" action="{{ route('users.update',$user) }}" class="user-account-form">@csrf @method('PUT')
        <div class="form-grid">
            <label>Nama<input name="name" value="{{ $user->name }}" required></label>
            <label>Email login<input type="email" name="email" value="{{ $user->email }}" required></label>
            <label>Jenis akun<select name="role" class="role-choice"><option value="pm" @selected($user->role==='pm')>Project manager</option><option value="team" @selected($user->role==='team')>Anggota tim</option><option value="client" @selected($user->role==='client')>Klien · view only</option></select></label>
            <label class="job-field">Job / divisi<select name="division"><option value="">Pilih job</option>@foreach(['Graphic Designer','Content Writer','Video Creator','Admin Sosmed','Sosmed Specialist'] as $job)<option @selected($user->division===$job)>{{ $job }}</option>@endforeach</select></label>
            <label class="client-account-field span-2" hidden>Klien untuk akun ini<select name="client_id"><option value="">Pilih klien</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected($user->client_id===$client->id)>{{ $client->name }}</option>@endforeach</select></label>
            <label class="span-2">Password baru · kosongkan agar tidak berubah<input type="password" name="password" minlength="8" autocomplete="new-password"></label>
        </div>
        <div class="form-actions"><button class="btn secondary" onclick="return confirm('Simpan perubahan akun {{ $user->name }}?')">Simpan perubahan</button></div>
    </form>
    <form class="delete-user" method="post" action="{{ route('users.destroy',$user) }}">@csrf @method('DELETE')<button class="btn danger" onclick="return confirm('Hapus akun {{ $user->name }}?')">Hapus akun</button></form>
</details>
@endforeach
</section>
<script>
document.querySelectorAll('.user-account-form').forEach(form => {
    const role=form.querySelector('.role-choice'), job=form.querySelector('.job-field'), client=form.querySelector('.client-account-field');
    function sync(){const team=role.value==='team',isClient=role.value==='client';job.hidden=!team;job.querySelector('select').required=team;client.hidden=!isClient;client.querySelector('select').required=isClient;}
    role.addEventListener('change',sync);sync();
});
</script>
@endsection
