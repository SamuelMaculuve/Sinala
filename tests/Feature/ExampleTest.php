<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_guest_can_see_login_and_registration_pages(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Bem-vindo de volta')->assertSee('Palavra-passe')->assertSee('brand/sinala-logo-transparent.png');
        $this->get(route('register'))->assertOk()->assertSee('Crie a sua organização')->assertSee('brand/sinala-logo-transparent.png');
    }

    public function test_public_seo_and_ai_discovery_endpoints_are_available(): void
    {
        $landing = $this->get('/');
        $landing->assertOk()->assertSee('rel="canonical"',false)->assertSee('application/ld+json',false)->assertSee('og-sinala.png')->assertSee('brand/sinala-logo-transparent.png');

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $landing->getContent(), $matches);
        $this->assertNotEmpty($matches[1] ?? null);
        $this->assertIsArray(json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR));

        $this->get('/robots.txt')->assertOk()->assertHeader('Content-Type','text/plain; charset=UTF-8')->assertSee('OAI-SearchBot')->assertSee('Sitemap:');
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type','application/xml; charset=UTF-8')->assertSee('<urlset',false);
        $this->get('/llms.txt')->assertOk()->assertSee('Plataforma SaaS moçambicana')->assertSee('até 10 eventos');
    }
}
