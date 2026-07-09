<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [

            /*
            |--------------------------------------------------------------------------
            | Website Development
            |--------------------------------------------------------------------------
            */

            [
    'service_code' => 'SER-0001',
    'service_name' => 'Business Website',
    'short_name' => 'Business Website',
    'sku' => 'WEB-BASIC-001',

    'category' => 'Website Development',

    'description' => 'Professional business website with responsive design and CMS.',

    'standard_price' => 25000,
    'pricing_type' => 'fixed',

    'setup_fee' => 5000,
    'monthly_price' => 0,
    'recurring' => false,

    'gst_applicable' => true,
    'gst_percentage' => 18,

    'estimated_duration' => 15,

    'display_order' => 1,
    'is_featured' => true,

    'icon' => 'heroicon-o-globe-alt',
    'thumbnail' => null,

    'notes' => 'Ideal for startups and small businesses.',

    'is_active' => true,
],

            [
                'service_code' => 'SER-0002',
                'service_name' => 'Corporate Website',
                'category' => 'Website Development',
                'description' => 'Corporate website with multiple pages and enquiry forms.',
                'standard_price' => 45000,
                'gst_applicable' => true,
                'gst_percentage' => 18,
                'estimated_duration' => 25,
                'is_active' => true,
            ],

            [
                'service_code' => 'SER-0003',
                'service_name' => 'Ecommerce Website',
                'category' => 'Website Development',
                'description' => 'Complete ecommerce website with payment gateway integration.',
                'standard_price' => 75000,
                'gst_applicable' => true,
                'gst_percentage' => 18,
                'estimated_duration' => 35,
                'is_active' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | SEO
            |--------------------------------------------------------------------------
            */

            [
                'service_code' => 'SER-0004',
                'service_name' => 'Local SEO',
                'category' => 'SEO',
                'description' => 'Google Business Profile optimization and local ranking.',
                'standard_price' => 15000,
                'gst_applicable' => true,
                'gst_percentage' => 18,
                'estimated_duration' => 30,
                'is_active' => true,
            ],

            [
                'service_code' => 'SER-0005',
                'service_name' => 'Technical SEO',
                'category' => 'SEO',
                'description' => 'Website audit, schema, indexing and technical optimization.',
                'standard_price' => 25000,
                'gst_applicable' => true,
                'gst_percentage' => 18,
                'estimated_duration' => 30,
                'is_active' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | Google Ads
            |--------------------------------------------------------------------------
            */

            [
                'service_code' => 'SER-0006',
                'service_name' => 'Google Ads Management',
                'category' => 'Google Ads',
                'description' => 'Search, Display and Performance Max campaign management.',
                'standard_price' => 20000,
                'gst_applicable' => true,
                'gst_percentage' => 18,
                'estimated_duration' => 30,
                'is_active' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | Meta Ads
            |--------------------------------------------------------------------------
            */

            [
                'service_code' => 'SER-0007',
                'service_name' => 'Meta Ads Management',
                'category' => 'Meta Ads',
                'description' => 'Facebook and Instagram advertising campaigns.',
                'standard_price' => 18000,
                'gst_applicable' => true,
                'gst_percentage' => 18,
                'estimated_duration' => 30,
                'is_active' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | Google Business Profile
            |--------------------------------------------------------------------------
            */

            [
                'service_code' => 'SER-0008',
                'service_name' => 'Google Business Profile Setup',
                'category' => 'Google Business Profile',
                'description' => 'Complete Google Business Profile setup and verification.',
                'standard_price' => 8000,
                'gst_applicable' => true,
                'gst_percentage' => 18,
                'estimated_duration' => 7,
                'is_active' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | Branding
            |--------------------------------------------------------------------------
            */

            [
                'service_code' => 'SER-0009',
                'service_name' => 'Logo Design',
                'category' => 'Branding',
                'description' => 'Professional logo with multiple concepts and revisions.',
                'standard_price' => 12000,
                'gst_applicable' => true,
                'gst_percentage' => 18,
                'estimated_duration' => 7,
                'is_active' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | Photography
            |--------------------------------------------------------------------------
            */

            [
                'service_code' => 'SER-0010',
                'service_name' => 'Professional Product Photography',
                'category' => 'Photography',
                'description' => 'High-quality studio product photography.',
                'standard_price' => 18000,
                'gst_applicable' => true,
                'gst_percentage' => 18,
                'estimated_duration' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['service_code' => $service['service_code']],
                $service
            );
        }
    }
}