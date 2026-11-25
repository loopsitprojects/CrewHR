<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Modules\Employee\Models\Department;
use Modules\Employee\Models\Designation;
use Modules\Employee\Models\Employee;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Department::firstOrCreate(['name' => 'IT'], ['code' => 'IT', 'description' => 'IT Dept']);
        Designation::firstOrCreate(['name' => 'Developer']);
    }

    public function test_login_page_renders_google_sso_button(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('Sign in with Google');
        $response->assertSee('Work Email');
        $response->assertSee(route('auth.google'));
    }

    public function test_redirect_to_google_fails_gracefully_when_credentials_not_configured(): void
    {
        config(['services.google.client_id' => '']);
        config(['services.google.client_secret' => '']);

        $response = $this->get(route('auth.google'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    public function test_redirect_to_google_redirects_when_configured(): void
    {
        config(['services.google.client_id' => 'mock-client-id']);
        config(['services.google.client_secret' => 'mock-client-secret']);
        config(['services.google.allowed_domains' => 'loopshr.com']);

        $response = $this->get(route('auth.google'));

        $this->assertTrue($response->isRedirection());
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
    }

    public function test_google_callback_rejects_unauthorized_non_work_domain(): void
    {
        config(['services.google.allowed_domains' => 'loopshr.com,loopshr.lk']);

        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-12345');
        $socialiteUser->shouldReceive('getEmail')->andReturn('intruder@gmail.com');
        $socialiteUser->shouldReceive('getName')->andReturn('Personal User');
        $socialiteUser->shouldReceive('getAvatar')->andReturn(null);

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

        $response = $this->get(route('auth.google.callback'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    public function test_google_callback_authenticates_existing_user_with_work_email(): void
    {
        config(['services.google.allowed_domains' => 'loopshr.com,loopshr.lk']);

        $existingUser = User::create([
            'name' => 'Shimal Test',
            'username' => 'shimal_test',
            'email' => 'shimal@loopshr.com',
            'password' => bcrypt('secret123'),
        ]);

        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-99999');
        $socialiteUser->shouldReceive('getEmail')->andReturn('shimal@loopshr.com');
        $socialiteUser->shouldReceive('getName')->andReturn('Shimal Test');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/avatar.jpg');

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($existingUser);
        $response->assertRedirect(route('dashboard'));

        $existingUser->refresh();
        $this->assertEquals('google-99999', $existingUser->google_id);
    }

    public function test_google_callback_auto_registers_user_with_authorized_work_email(): void
    {
        config(['services.google.allowed_domains' => 'loopshr.com,loopshr.lk']);
        config(['services.google.auto_register' => true]);

        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google-new-777');
        $socialiteUser->shouldReceive('getEmail')->andReturn('newemployee@loopshr.com');
        $socialiteUser->shouldReceive('getName')->andReturn('New Employee');
        $socialiteUser->shouldReceive('getAvatar')->andReturn(null);

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

        $response = $this->get(route('auth.google.callback'));

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));

        $createdUser = User::where('email', 'newemployee@loopshr.com')->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals('google-new-777', $createdUser->google_id);
        $this->assertNotNull($createdUser->employee);
    }
}
