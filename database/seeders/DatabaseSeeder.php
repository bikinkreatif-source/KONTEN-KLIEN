<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Content;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! User::where('role', 'pm')->exists()) {
            $adminEmail = env('ADMIN_EMAIL');
            $adminPassword = env('ADMIN_PASSWORD');

            if (blank($adminEmail) || blank($adminPassword)) {
                throw new \RuntimeException('Set ADMIN_EMAIL dan ADMIN_PASSWORD sebelum membuat akun PM pertama.');
            }

            User::firstOrCreate(['email' => $adminEmail], [
                'name' => env('ADMIN_NAME', app()->environment('local') ? 'Nawasena' : 'Project Manager'),
                'role' => 'pm',
                'division' => 'Project Manager',
                'password' => Hash::make($adminPassword),
            ]);
        }

        if (app()->environment('local')) {
            $plans = [
                'Juragan Property' => [8, 8, 8, 20, "Edukasi properti\nTestimoni pembeli\nPromo cluster", 'Target audiens: keluarga muda dan first-time home buyers. Gunakan bahasa yang informatif, hangat, dan hindari klaim harga atau ketersediaan tanpa konfirmasi admin.'],
                'Kopi Senja' => [6, 4, 8, 16, "Cerita biji kopi\nMenu andalan\nSuasana kedai", 'Brand kopi lokal dengan suasana santai. Tampilkan produk dan pengalaman di kedai.'],
                'Rona Skincare' => [6, 6, 6, 20, "Edukasi skincare\nRutinitas harian\nTestimoni", 'Gunakan klaim produk yang sudah disetujui. Fokus pada edukasi dan rutinitas perawatan.'],
                'STEMATEL' => [4, 8, 8, 20, "Stematel Life\nNextGen Tech\nNextGen Experience\nNextGen Project\nNextGen Story\nNext Pathway", 'Positioning: School of Global Technopreneur. Planned content membangun brand; real-time content membuktikan brand hidup.'],
            ];
            $clients = [];
            foreach ($plans as $name => [$single, $carousel, $reels, $story, $pillars, $information]) {
                $clients[$name] = Client::firstOrCreate(['name' => $name], [
                    'start_date' => '2026-10-01', 'end_date' => '2026-10-31',
                    'quota_single' => $single, 'quota_carousel' => $carousel, 'quota_reels' => $reels, 'quota_story' => $story,
                    'pillars' => $pillars, 'information' => $information,
                ]);
            }

            $demoTeam = [];
            foreach ([
                ['Graphic Designer','graphic@demo.local'],
                ['Content Writer','writer@demo.local'],
                ['Video Creator','video@demo.local'],
                ['Admin Sosmed','admin.sosmed@demo.local'],
                ['Sosmed Specialist','specialist@demo.local'],
            ] as [$job,$email]) {
                $demoTeam[$job] = User::firstOrCreate(['email'=>$email], [
                    'name' => $job, 'role' => 'team', 'division' => $job,
                    'password' => Hash::make(env('DEMO_TEAM_PASSWORD', Str::random(48))),
                ]);
            }
            $projectManager = User::where('role','pm')->first();
            foreach (['Graphic Designer','Content Writer','Video Creator','Admin Sosmed'] as $job) {
                $clients['Juragan Property']->teamMembers()->syncWithoutDetaching([$demoTeam[$job]->id => ['assigned_by' => $projectManager?->id]]);
            }
            $clients['STEMATEL']->teamMembers()->syncWithoutDetaching([$demoTeam['Sosmed Specialist']->id => ['assigned_by' => $projectManager?->id], $demoTeam['Content Writer']->id => ['assigned_by' => $projectManager?->id]]);

            $samplePosts = [
                ['Juragan Property',2,'Edukasi: KPR untuk rumah pertama','Carousel','Disetujui','10:00','Edukasi properti','Panduan sederhana memahami KPR pertama kali.','Punya rencana punya rumah pertama? Mulai dengan memahami pilihan KPR yang sesuai kebutuhan.'],
                ['Juragan Property',3,'Behind the scene kantor','Story','Disetujui','12:00','Cerita tim','Cuplikan tim menyiapkan layanan untuk calon pembeli.','Kenalan dengan tim yang siap menemani perjalanan punya rumah.'],
                ['Juragan Property',5,'Promo rumah cluster Harmoni','Carousel','Menunggu review','19:00','Promo cluster','Sorot benefit cluster Harmoni. Konfirmasi harga dan ketersediaan kepada admin sebelum publikasi.','Temukan rumah yang pas untuk keluarga. Hubungi admin untuk info terbaru.'],
                ['Juragan Property',7,'Tur rumah 60 detik','Reels','Dalam proses','18:30','Promo cluster','Video tur singkat dari ruang utama hingga area keluarga.','Yuk, intip suasana rumah yang bisa jadi awal cerita baru keluarga Anda.'],
                ['Juragan Property',9,'Polling: area favoritmu?','Story','Disetujui','11:00','Edukasi properti','Polling interaktif tentang area rumah idaman.','Pilih area favoritmu: dekat kantor atau dekat keluarga?'],
                ['Juragan Property',12,'Checklist sebelum survei rumah','Carousel','Dalam proses','10:00','Edukasi properti','Daftar hal penting yang sebaiknya diperiksa saat survei rumah.','Simpan checklist ini sebelum jadwal survei rumah berikutnya.'],
                ['Juragan Property',14,'Testimoni keluarga Pak Andi','Reels','Menunggu review','19:30','Testimoni pembeli','Cerita pengalaman keluarga setelah menempati rumah baru.','Terima kasih sudah mempercayakan perjalanan rumah pertama kepada kami.'],
                ['Juragan Property',16,'Q&A seputar DP rumah','Story','Ide','13:00','Edukasi properti','Jawab pertanyaan umum tentang uang muka rumah.','Ada pertanyaan soal DP rumah? Kirimkan di kolom tanya jawab.'],
                ['Juragan Property',19,'Investasi properti untuk pemula','Carousel','Dalam proses','10:00','Edukasi properti','Bahas faktor dasar yang perlu dipertimbangkan sebelum membeli properti.','Mulai dengan riset lokasi, tujuan, dan kemampuan finansial.'],
                ['Juragan Property',21,'Kuis tebak harga rumah','Story','Disetujui','12:00','Interaksi','Ajak audiens menebak kisaran harga rumah dari foto.','Coba tebak, berapa kisaran harga rumah ini?'],
                ['Juragan Property',23,'Promo akhir bulan — Harmoni','Carousel','Menunggu review','19:00','Promo cluster','Materi promo menunggu konfirmasi admin untuk harga dan masa berlaku.','Tanyakan promo yang sedang tersedia langsung ke admin kami.'],
                ['Juragan Property',24,'Open house akhir pekan','Reels','Dalam proses','16:00','Promo cluster','Undangan video untuk kunjungan open house akhir pekan.','Jadwalkan kunjungan dan lihat langsung lingkungan rumahnya.'],
                ['Juragan Property',26,'Tips cek legalitas properti','Carousel','Ide','10:00','Edukasi properti','Poin dasar dokumen legalitas yang perlu ditanyakan kepada pihak terkait.','Pastikan dokumen properti diperiksa dengan teliti sebelum transaksi.'],
                ['Juragan Property',28,'Suasana sekitar perumahan','Story','Disetujui','12:00','Cerita lingkungan','Tampilkan akses dan suasana sekitar perumahan.','Seperti apa suasana di sekitar cluster? Lihat ceritanya.'],
                ['Juragan Property',30,'Rangkuman properti bulan ini','Carousel','Ide','18:00','Edukasi properti','Rangkuman konten dan informasi properti bulan ini.','Simpan rangkuman ini dan hubungi admin untuk info terbaru.'],
                ['STEMATEL',3,'Sehari jadi student STEMATEL','Reels','Disetujui','16:00','Stematel Life','Ikuti keseharian siswa dari kelas teknologi sampai mengerjakan project kolaboratif.','Belajar teknologi, bikin karya, dan tumbuh bareng teman satu visi. #SchoolOfGlobalTechnopreneur'],
                ['STEMATEL',8,'Project siswa: solusi smart farming','Carousel','Menunggu review','18:00','NextGen Project','Tampilkan masalah, proses prototyping, dan hasil project siswa.','Dari ide jadi prototipe. Swipe untuk lihat proses tim mengembangkan solusi smart farming.'],
                ['STEMATEL',17,'Kenalan dengan pathway technopreneur','Story','Dalam proses','12:00','Next Pathway','Rangkaian story FAQ tentang jalur belajar dan ajakan mengikuti open house.','Punya rasa ingin tahu tentang teknologi dan bisnis? Cari tahu pathway-mu di STEMATEL.'],
            ];
            foreach ($samplePosts as [$clientName, $day, $title, $type, $status, $time, $pillar, $brief, $caption]) {
                $job = match ($type) { 'Reels' => 'Video Creator', 'Story' => 'Admin Sosmed', default => 'Content Writer' };
                $content = Content::firstOrCreate(['client_id' => $clients[$clientName]->id, 'title' => $title], [
                    'type' => $type, 'publish_date' => sprintf('2026-10-%02d', $day), 'publish_time' => $time,
                    'status' => $status, 'pillar' => $pillar, 'brief' => $brief, 'caption' => $caption,
                    'reference_url' => 'https://example.com/reference',
                    'due_date' => sprintf('2026-10-%02d', max(1,$day-2)),
                    'assignee_id' => $demoTeam[$job]->id,
                ]);
                if (! $content->assignee_id) $content->update(['assignee_id'=>$demoTeam[$job]->id,'due_date'=>sprintf('2026-10-%02d',max(1,$day-2))]);
            }
        }
    }
}
