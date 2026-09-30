<?php

namespace Tests\Feature;

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Guest behaviour that does not need the database: redirects and the public pages.
 */
class GuestAccessTest extends TestCase
{
    public function test_root_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_login_page_renders(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Vraman Adesh Generator')
            ->assertSee('css/auth/login.css', false)
            ->assertSee(route('login.attempt'), false);
    }

    public function test_login_requires_username_and_password(): void
    {
        $this->from(route('login'))
            ->post(route('login.attempt'), [])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['username', 'password']);
    }

    public function test_every_protected_route_redirects_guests_to_login(): void
    {
        $protected = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (LaravelRoute $route) => in_array('auth', $route->gatherMiddleware(), true));

        $this->assertNotEmpty($protected, 'No routes with the auth middleware were found.');

        foreach ($protected as $route) {
            $uri = '/'.ltrim(preg_replace('/\{[^}]+\??\}/', 'x', $route->uri()), '/');
            $method = in_array('GET', $route->methods(), true) ? 'GET' : $route->methods()[0];

            $this->call($method, $uri)
                ->assertRedirect(route('login'), "{$method} {$uri} ({$route->getName()}) should redirect guests to login.");
        }
    }

    public function test_change_password_form_needs_a_pending_password_change(): void
    {
        $this->get(route('password.change'))->assertRedirect(route('login'));
    }

    public function test_change_password_form_renders_for_a_user_with_a_plain_text_password(): void
    {
        $this->withSession(['password_change_username' => 'someone'])
            ->get(route('password.change'))
            ->assertOk()
            ->assertSee('css/auth/change_password.css', false);
    }
}
