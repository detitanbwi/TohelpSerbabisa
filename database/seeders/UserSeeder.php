<?php

namespace Database\Seeders;

use App\Models\Cabang;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure roles exist
        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'manager_cabang']);
        Role::firstOrCreate(['name' => 'karyawan']);

        $defaultCabang = Cabang::first();

        // 1. Akun Lama / Existing: Admin (admin@gmail.com)
        $legacyAdmin = User::where('email', 'admin@gmail.com')->first();
        if (! $legacyAdmin) {
            $legacyAdmin = User::create([
                'name' => 'Admin',
                'username' => 'admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('password'),
                'cabang_id' => $defaultCabang?->id,
            ]);
        } else {
            $legacyAdmin->update([
                'name' => 'Admin',
                'username' => 'admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('password'),
                'cabang_id' => $defaultCabang?->id,
            ]);
        }
        $legacyAdmin->syncRoles(['super_admin']);

        // 2. Akun Baru: Super Admin (admin@tohelp.com)
        $admin = User::where('email', 'admin@tohelp.com')->first();
        if (! $admin) {
            $admin = User::create([
                'name' => 'Super Admin ToHelp',
                'username' => 'admin_tohelp',
                'email' => 'admin@tohelp.com',
                'password' => Hash::make('password'),
                'cabang_id' => $defaultCabang?->id,
            ]);
        } else {
            $admin->update([
                'name' => 'Super Admin ToHelp',
                'username' => 'admin_tohelp',
                'email' => 'admin@tohelp.com',
                'password' => Hash::make('password'),
                'cabang_id' => $defaultCabang?->id,
            ]);
        }
        $admin->syncRoles(['super_admin']);

        // 3. Owner (owner@tohelp.com)
        $owner = User::where('email', 'owner@tohelp.com')->first();
        if (! $owner) {
            $owner = User::create([
                'name' => 'Owner ToHelp',
                'username' => 'owner',
                'email' => 'owner@tohelp.com',
                'password' => Hash::make('password'),
                'cabang_id' => $defaultCabang?->id,
            ]);
        } else {
            $owner->update([
                'name' => 'Owner ToHelp',
                'username' => 'owner',
                'email' => 'owner@tohelp.com',
                'password' => Hash::make('password'),
                'cabang_id' => $defaultCabang?->id,
            ]);
        }
        $owner->syncRoles(['super_admin']);

        // 4. Manager Cabang (manager.bwi@tohelp.com)
        $manager = User::where('email', 'manager.bwi@tohelp.com')->first();
        if (! $manager) {
            $manager = User::create([
                'name' => 'Manager Banyuwangi',
                'username' => '001_ANDI',
                'email' => 'manager.bwi@tohelp.com',
                'password' => Hash::make('password'),
                'cabang_id' => $defaultCabang?->id,
            ]);
        } else {
            $manager->update([
                'name' => 'Manager Banyuwangi',
                'username' => '001_ANDI',
                'email' => 'manager.bwi@tohelp.com',
                'password' => Hash::make('password'),
                'cabang_id' => $defaultCabang?->id,
            ]);
        }
        $manager->syncRoles(['manager_cabang']);

        // 5. Helpman / Karyawan (helpman01@tohelp.com)
        $helpman = User::where('email', 'helpman01@tohelp.com')->first();
        if (! $helpman) {
            $helpman = User::create([
                'name' => 'Helpman Satu',
                'username' => 'andiganteng01',
                'email' => 'helpman01@tohelp.com',
                'password' => Hash::make('password'),
                'cabang_id' => $defaultCabang?->id,
            ]);
        } else {
            $helpman->update([
                'name' => 'Helpman Satu',
                'username' => 'andiganteng01',
                'email' => 'helpman01@tohelp.com',
                'password' => Hash::make('password'),
                'cabang_id' => $defaultCabang?->id,
            ]);
        }
        $helpman->syncRoles(['karyawan']);

        // 6. Customer (customer@tohelp.com)
        $customer = User::where('email', 'customer@tohelp.com')->first();
        if (! $customer) {
            $customer = User::create([
                'name' => 'Customer ToHelp',
                'username' => 'customer',
                'email' => 'customer@tohelp.com',
                'password' => Hash::make('password'),
                'cabang_id' => $defaultCabang?->id,
            ]);
        } else {
            $customer->update([
                'name' => 'Customer ToHelp',
                'username' => 'customer',
                'email' => 'customer@tohelp.com',
                'password' => Hash::make('password'),
                'cabang_id' => $defaultCabang?->id,
            ]);
        }
    }
}
