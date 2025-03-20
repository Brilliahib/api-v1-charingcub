<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class RestoreOldDataSeeder extends Seeder
{
    public function run()
    {
        $sql = File::get(database_path('charingcub-v2.sql'));
        DB::unprepared($sql);
        $this->command->info('Data lama berhasil dipulihkan!');
    }
}
