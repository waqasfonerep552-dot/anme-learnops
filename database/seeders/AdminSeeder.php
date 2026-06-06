<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $instructorRole = Role::firstOrCreate(['name' => 'Instructor']);
        $studentRole = Role::firstOrCreate(['name' => 'Student']);

        // Create permissions
        $permissions = [
            'view_orders', 'create_orders', 'edit_orders', 'delete_orders', 'approve_payment', 'reject_payment',
            'view_students', 'manage_students', 'suspend_student',
            'view_courses', 'create_courses', 'edit_courses', 'publish_courses', 'sync_courses',
            'manage_settings', 'view_diagnostics', 'view_audit_logs',
            'send_notifications', 'manage_notifications',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdminRole->syncPermissions(Permission::all());

        $adminRole->syncPermissions([
            'view_orders', 'create_orders', 'edit_orders', 'approve_payment', 'reject_payment',
            'view_students', 'manage_students',
            'view_courses', 'edit_courses', 'publish_courses', 'sync_courses',
            'manage_settings', 'view_diagnostics', 'view_audit_logs',
            'send_notifications', 'manage_notifications',
        ]);

        $instructorRole->syncPermissions(['view_courses', 'view_students']);
        $studentRole->syncPermissions(['view_orders']);

        // Create super admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('Password123!'),
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole($superAdminRole);

        echo "✅ Admin setup complete!\n";
    }
}
