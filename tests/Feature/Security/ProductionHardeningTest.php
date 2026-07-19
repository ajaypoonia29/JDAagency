<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_can_be_disabled(): void
    {
        config()->set('agencyos.registration_enabled', false);

        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertGuest();
    }

    public function test_registration_remains_available_when_explicitly_enabled(): void
    {
        config()->set('agencyos.registration_enabled', true);

        $this->get('/register')->assertOk();
    }

    public function test_user_without_dashboard_permission_cannot_access_admin_panel(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'production');

        $user = User::factory()->create();

        $this->assertFalse(
            $user->canAccessPanel(Panel::make()->id('admin')),
        );
    }

    public function test_user_with_dashboard_permission_can_access_admin_panel(): void
    {
        $this->app->detectEnvironment(static fn (): string => 'production');

        $permission = Permission::create([
            'name' => 'dashboard.view',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        $this->assertTrue(
            $user->canAccessPanel(Panel::make()->id('admin')),
        );
    }

    public function test_security_headers_are_added_to_web_responses(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader(
                'Permissions-Policy',
                'camera=(), microphone=(), geolocation=()',
            );
    }
}
