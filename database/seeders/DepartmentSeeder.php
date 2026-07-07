<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [

            [
                'name' => 'Management',
                'code' => 'MGMT',
                'description' => 'Executive management team',
            ],

            [
                'name' => 'Sales',
                'code' => 'SALE',
                'description' => 'Sales and business development',
            ],

            [
                'name' => 'Marketing',
                'code' => 'MKT',
                'description' => 'Marketing and branding',
            ],

            [
                'name' => 'Customer Support',
                'code' => 'SUP',
                'description' => 'Customer success and support',
            ],

            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'description' => 'Human resource management',
            ],

            [
                'name' => 'Accounts',
                'code' => 'ACC',
                'description' => 'Finance and accounting',
            ],

            [
                'name' => 'Operations',
                'code' => 'OPS',
                'description' => 'Business operations',
            ],

            [
                'name' => 'Information Technology',
                'code' => 'IT',
                'description' => 'Software and infrastructure',
            ],

        ];

        foreach ($departments as $department) {

            Department::updateOrCreate(

                [
                    'code' => $department['code'],
                ],

                [
                    'name' => $department['name'],
                    'description' => $department['description'],
                    'is_active' => true,
                ]

            );
        }
    }
}