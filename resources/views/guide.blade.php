@extends('layout')
@section('title', 'Panduan pengisian content plan')
@section('heading', 'Panduan pengisian content plan')
@section('content')
<div class="guide-hero">
    <span class="eyebrow">PUSAT BANTUAN · RUANG KONTEN</span>
    <h1>Panduan pengisian content plan</h1>
    <p>Ikuti alur sederhana ini untuk menyiapkan konten, mengerjakannya bersama tim, lalu meminta persetujuan klien.</p>
    <a class="btn primary" href="{{ auth()->user()->role==='pm' ? route('dashboard') : route('dashboard',['client'=>$clients->first()?->id]) }}">{{ auth()->user()->role==='pm' ? 'Buka dashboard PM' : 'Buka planner saya' }} →</a>
</div>

<section class="panel guide-section">
    <div class="guide-section-title"><span class="guide-number">01</span><div><span class="eyebrow">MULAI DARI WORKSPACE</span><h2>Pilih klien dan cek brief-nya</h2></div></div>
    <p>Pilih nama klien pada sidebar untuk membuka content plan. Sebelum menambahkan ide, cek tanggal periode posting, kuota format, pilar konten, informasi klien, dan link akun sosial atau Google Drive.</p>
    <div class="guide-callout">Satu planner hanya digunakan untuk satu klien. Kalender hanya menerima tanggal dalam periode posting klien tersebut.</div>
</section>

@if(auth()->user()->role==='pm')
<section class="panel guide-section">
    <div class="guide-section-title"><span class="guide-number">02</span><div><span class="eyebrow">UNTUK PROJECT MANAGER</span><h2>Siapkan klien dan tim</h2></div></div>
    <div class="guide-steps">
        <div><b>Tambah klien</b><p>Isi nama, tanggal mulai dan akhir posting, jumlah target Single Image, Carousel, Reels, Story, pilar, informasi klien, link sosial, serta folder Google Drive.</p></div>
        <div><b>Buat akun anggota tim</b><p>Buka <b>Tim & pengguna</b>. Isi nama, job/divisi, email login, dan password. Peran menentukan akses dan pekerjaan anggota.</p></div>
        <div><b>Tugaskan anggota ke planner</b><p>Di form edit klien, pilih anggota tim yang mengerjakan planner itu. Anggota tim hanya melihat planner yang ditugaskan kepadanya.</p></div>
        <div><b>Atur pilar dan review</b><p>Content Writer atau Sosmed Specialist dapat merapikan pilar pada planner. Pantau tenggat dan antrean persetujuan dari dashboard PM.</p></div>
    </div>
</section>
@endif

@if(auth()->user()->role!=='client')
<section class="panel guide-section">
    <div class="guide-section-title"><span class="guide-number">{{ auth()->user()->role==='pm'?'03':'02' }}</span><div><span class="eyebrow">MENAMBAHKAN KONTEN</span><h2>Isi card konten dengan lengkap</h2></div></div>
    <p>Klik tanda <b>＋</b> pada tanggal aktif di kalender, atau tombol <b>Buat konten</b>. Klik card yang sudah ada untuk melihat atau mengedit detailnya.</p>
    <div class="guide-fields">
        <div><b>Judul dan format</b><span>Judul singkat yang menjelaskan ide. Pilih Single Image, Carousel, Reels, atau Story.</span></div>
        <div><b>Tanggal dan jam posting</b><span>Tanggal tayang konten dan jam publikasi yang direncanakan.</span></div>
        <div><b>Tenggat dan PIC</b><span>Tenggat pengerjaan internal dan anggota tim yang bertanggung jawab.</span></div>
        <div><b>Pilar dan tujuan</b><span>Pilar konten yang sesuai strategi, serta tujuan seperti awareness atau conversion.</span></div>
        <div><b>Detail isi konten</b><span>Jelaskan hook, urutan pesan atau visual, informasi penting, dan arahan produksi.</span></div>
        <div><b>Caption</b><span>Tulis caption siap pakai. Di halaman detail, klik <b>Salin caption</b> untuk menyalinnya sebelum posting.</span></div>
        <div><b>Link referensi dan Google Drive</b><span>Tempel tautan referensi dan lokasi aset. File diakses dari Drive, bukan diunggah ke aplikasi.</span></div>
        <div><b>Langkah berikutnya dan catatan</b><span>Tulis kebutuhan lanjutan, CTA, atau catatan yang harus diketahui PIC.</span></div>
    </div>
    <div class="guide-callout">Perubahan akan meminta konfirmasi sebelum disimpan. Pastikan judul, tanggal, PIC, caption, dan link sudah benar.</div>
</section>
@endif

<section class="panel guide-section">
    <div class="guide-section-title"><span class="guide-number">{{ auth()->user()->role==='pm'?'04':(auth()->user()->role==='team'?'03':'02') }}</span><div><span class="eyebrow">STATUS KONTEN</span><h2>Gunakan status sesuai tahap kerja</h2></div></div>
    <div class="guide-statuses">
        @foreach([
            ['ide','Ide','Konsep dicatat, tim belum mulai produksi.'],
            ['dalam-proses','Dalam proses','PIC sedang menulis, desain, membuat video, atau menyiapkan jadwal.'],
            ['menunggu-review','Menunggu review','Pekerjaan tim sudah siap diperiksa internal.'],
            ['menunggu-persetujuan-klien','Menunggu persetujuan klien','Konten sudah dikirim untuk ditinjau klien.'],
            ['perlu-revisi','Perlu revisi','Ada masukan yang perlu dikerjakan; baca catatan revisinya.'],
            ['disetujui','Disetujui','Klien atau reviewer sudah menyetujui konten.'],
            ['terjadwal','Terjadwal','Konten sudah masuk antrean publikasi.'],
            ['tayang','Tayang','Konten sudah dipublikasikan.'],
        ] as [$status,$label,$description])
            <div class="guide-status-row"><span class="status-key status-{{ $status }}"><i></i>{{ $label }}</span><span>{{ $description }}</span></div>
        @endforeach
    </div>
</section>

@if(auth()->user()->role==='team')
<section class="panel guide-section">
    <div class="guide-section-title"><span class="guide-number">04</span><div><span class="eyebrow">UNTUK ANGGOTA TIM</span><h2>Kerjakan bagian sesuai job Anda</h2></div></div>
    <div class="guide-steps">
        <div><b>Content Writer / Sosmed Specialist</b><p>Kembangkan ide dan pilar, isi brief serta caption, lalu tetapkan status dan beri tahu PIC berikutnya lewat catatan.</p></div>
        <div><b>Graphic Designer / Video Creator</b><p>Baca brief, cek link referensi atau Drive, produksi aset, lalu isi langkah berikutnya dan perbarui status.</p></div>
        <div><b>Admin Sosmed</b><p>Pastikan caption dan jadwal siap. Salin caption dari detail konten, lalu ubah status ke <b>Tayang</b> setelah konten dipublikasikan.</p></div>
    </div>
</section>
@endif

@if(auth()->user()->role==='client')
<section class="panel guide-section">
    <div class="guide-section-title"><span class="guide-number">03</span><div><span class="eyebrow">UNTUK KLIEN</span><h2>Tinjau konten dan kirim masukan</h2></div></div>
    <p>Buka detail konten dari kalender atau public view. Periksa isi konten, caption, jadwal, dan tautan. Pilih <b>Setujui konten</b> jika sudah sesuai, atau <b>Minta revisi</b> dan tuliskan bagian yang perlu diperbaiki.</p>
</section>
@endif

<section class="guide-footer"><div><b>Butuh membuka planner?</b><span>Pilih klien di sidebar, lalu klik tanggal atau card konten yang ingin dikerjakan.</span></div><a class="btn primary" href="{{ auth()->user()->role==='pm' ? route('dashboard') : route('dashboard',['client'=>$clients->first()?->id]) }}">Kembali ke aplikasi →</a></section>
@endsection
