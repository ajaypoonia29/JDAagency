<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [

            // Dashboard
            'dashboard.view',

            // Company
            'company.view',
            'company.edit',

            // Employees
            'employees.view',
            'employees.create',
            'employees.edit',
            'employees.delete',

            // Customers
            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',

            // Leads
            'leads.view',
            'leads.create',
            'leads.edit',
            'leads.delete',
            'leads.assign',
            'leads.convert',

            // Services
            'services.view',
            'services.create',
            'services.edit',
            'services.delete',

            // Packages
            'packages.view',
            'packages.create',
            'packages.edit',
            'packages.delete',

            // Add-ons
            'addons.view',
            'addons.create',
            'addons.edit',
            'addons.delete',

            // Quotations
            'quotations.view',
            'quotations.create',
            'quotations.edit',
            'quotations.delete',
            'quotations.send',
            'quotations.approve',

            // Payments
            'payments.view',
            'payments.create',
            'payments.verify',

            // Invoices
            'invoices.view',
            'invoices.create',
            'invoices.download',
            'invoices.share',

            // Receipts
            'receipts.view',
            'receipts.create',
            'receipts.download',

            // Projects
            'projects.view',
            'projects.create',
            'projects.edit',

            // Tasks
            'tasks.view',
            'tasks.create',
            'tasks.edit',

            // Documents
            'documents.view',
            'documents.upload',
            'documents.delete',

            // Reports
            'reports.view',
            'reports.export',

            // Settings
            'settings.view',
            'settings.edit',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        $admin = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'Sales Manager',
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'Developer',
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'SEO Specialist',
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'Digital Marketing Specialist',
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'Accountant',
            'guard_name' => 'web',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Admin gets every permission
        |--------------------------------------------------------------------------
        */

        $admin->syncPermissions(Permission::all());
    }
}