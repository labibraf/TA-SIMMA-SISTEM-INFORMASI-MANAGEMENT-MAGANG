<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Role;

echo "=== TEST LOGIN DEBUG ===\n\n";

// Cek semua user
$users = User::all();
echo "Total users: " . $users->count() . "\n\n";

echo "Daftar User:\n";
echo str_repeat("-", 80) . "\n";
printf("%-5s %-25s %-30s %-10s %-15s\n", "ID", "Nama", "Email", "Role ID", "Role Name");
echo str_repeat("-", 80) . "\n";

foreach ($users as $user) {
    $roleName = $user->role_id ? ($user->role ? $user->role->role_name : 'Role Tidak Ditemukan') : 'NULL';
    printf("%-5d %-25s %-30s %-10s %-15s\n", 
        $user->id, 
        substr($user->name, 0, 25), 
        substr($user->email, 0, 30), 
        $user->role_id ?? 'NULL',
        $roleName
    );
}

echo str_repeat("-", 80) . "\n\n";

// Cek Role Admin
$adminRole = Role::where('role_name', 'Admin')->first();
echo "Role Admin: ";
if ($adminRole) {
    echo "DITEMUKAN (ID: {$adminRole->id})\n";
} else {
    echo "TIDAK DITEMUKAN!\n";
}

// Statistik
echo "\nStatistik:\n";
echo "- User dengan role: " . User::whereNotNull('role_id')->count() . "\n";
echo "- User tanpa role: " . User::whereNull('role_id')->count() . "\n";
echo "- Total roles: " . Role::count() . "\n";

echo "\nDaftar Roles:\n";
$roles = Role::all();
foreach ($roles as $role) {
    echo "- ID: {$role->id}, Nama: {$role->role_name}\n";
}

echo "\n=== SELESAI ===\n";
