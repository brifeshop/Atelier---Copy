<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FinancePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Daftar Permission khusus Finance
        $permissions = [
            'view finance dashboard',   // Sudah ada, tapi kita pastikan
            'manage payments',          // Sudah ada
            'view finance',             // BARU: Akses halaman AR & AP
            'create finance',           // BARU: Buat Payment In/Out
            'edit finance',             // BARU: Edit/Void Payment
            'delete finance',           // BARU: Hapus draft Payment
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName]);
        }

        // 2. Assign semua permission ke Role Finance
        $financeRole = Role::findByName('Finance');
        $financeRole->syncPermissions($permissions); // sync = timpa permission lama milik role ini

        // 3. Assign ke Owner (Owner harus punya semua akses)
        $ownerRole = Role::findByName('Owner');
        $ownerRole->givePermissionTo($permissions); // give = tambah, tidak menimpa
    }
}