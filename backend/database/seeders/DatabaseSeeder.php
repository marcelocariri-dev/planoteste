<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Roda a cada subida do container: cada seeder é idempotente.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategoriasDocumentoSeeder::class,
            PadraoBacklogSeeder::class,
            PlanoExemploSeeder::class,
            RegraExemploSeeder::class,
        ]);
    }
}
