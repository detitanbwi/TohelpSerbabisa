<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::findOrCreate('super_admin');
        Role::findOrCreate('owner');
        Role::findOrCreate('manager_cabang');
        Role::findOrCreate('karyawan');
    }
}
