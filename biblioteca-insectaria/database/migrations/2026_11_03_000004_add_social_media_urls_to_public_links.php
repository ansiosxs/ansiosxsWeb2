<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const LINKS = [
        7 => 'https://www.instagram.com/ansiosxs',
        8 => 'https://www.facebook.com/Ansiosxs/',
        9 => 'https://www.youtube.com/channel/UCMWvXVIqLlI72pXkdeWM62g',
        10 => 'https://www.linkedin.com/company/ong-ansiosxs-nuevas-lecturas/?viewAsMember=true',
    ];

    public function up(): void
    {
        foreach (self::LINKS as $orden => $url) {
            DB::table('enlaces_publicos')
                ->where('seccion', 'Redes Sociales')
                ->where('orden', $orden)
                ->update([
                    'url' => $url,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::LINKS) as $orden) {
            DB::table('enlaces_publicos')
                ->where('seccion', 'Redes Sociales')
                ->where('orden', $orden)
                ->update([
                    'url' => null,
                    'updated_at' => now(),
                ]);
        }
    }
};
