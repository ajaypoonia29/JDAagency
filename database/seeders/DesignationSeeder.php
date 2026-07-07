<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $designations = [

            [
                'name' => 'Chief Executive Officer',
                'code' => 'CEO',
                'description' => 'Organization head',
            ],

            [
                'name' => 'Director',
                'code' => 'DIR',
                'description' => 'Department director',
            ],

            [
                'name' => 'General Manager',
                'code' => 'GM',
                'description' => 'General manager',
            ],

            [
                'name' => 'Manager',
                'code' => 'MGR',
                'description' => 'Department manager',
            ],

            [
                'name' => 'Team Lead',
                'code' => 'TL',
                'description' => 'Team leader',
            ],

            [
                'name' => 'Sales Executive',
                'code' => 'SE',
                'description' => 'Sales executive',
            ],

            [
                'name' => 'Marketing Executive',
                'code' => 'ME',
                'description' => 'Marketing executive',
            ],

            [
                'name' => 'Software Developer',
                'code' => 'DEV',
                'description' => 'Software developer',
            ],

            [
                'name' => 'UI/UX Designer',
                'code' => 'UIUX',
                'description' => 'UI and UX designer',
            ],

            [
                'name' => 'HR Executive',
                'code' => 'HRE',
                'description' => 'Human resources executive',
            ],

            [
                'name' => 'Accountant',
                'code' => 'ACC',
                'description' => 'Accounts executive',
            ],

            [
                'name' => 'Customer Support Executive',
                'code' => 'CSE',
                'description' => 'Customer support executive',
            ],

        ];

        foreach ($designations as $designation) {

            Designation::updateOrCreate(

                [
                    'code' => $designation['code'],
                ],

                [
                    'name' => $designation['name'],
                    'description' => $designation['description'],
                    'is_active' => true,
                ]

            );
        }
    }
}