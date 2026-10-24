<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('contents')->where('status','Dikerjakan')->update(['status'=>'Dalam proses']);
        DB::table('contents')->where('status','Terpublikasi')->update(['status'=>'Tayang']);
    }
    public function down(): void
    {
        DB::table('contents')->where('status','Dalam proses')->update(['status'=>'Dikerjakan']);
        DB::table('contents')->where('status','Tayang')->update(['status'=>'Terpublikasi']);
    }
};
