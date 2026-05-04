<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sucursal;

class SucursalSeeder extends Seeder
{
    public function run(): void
    {
        Sucursal::firstOrCreate(['nombre' => 'Izamal']);
        Sucursal::firstOrCreate(['nombre' => 'Buctzotz']);
    }
}