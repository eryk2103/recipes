<?php

namespace App\Tests\Controller;

use App\Tests\Support\IntegrationTestCase;

class SecurityControllerTest extends IntegrationTestCase
{
    public function testLoginPageRenders(): void
    {
        $this->client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Sign in');
    }

    public function testLoginWithValidCredentialsAuthenticatesUser(): void
    {
        $this->createUser('amelia', 'password123');

        $this->client->request('GET', '/login');
        $this->client->submitForm('Sign in', [
            '_username' => 'amelia',
            '_password' => 'password123',
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorExists('.avatar-dropdown');
    }

    public function testLoginWithInvalidCredentialsShowsError(): void
    {
        $this->createUser('amelia', 'password123');

        $this->client->request('GET', '/login');
        $this->client->submitForm('Sign in', [
            '_username' => 'amelia',
            '_password' => 'wrong-password',
        ]);

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertSelectorExists('.alert');
    }

    public function testLogoutClearsSession(): void
    {
        $user = $this->createUser('amelia', 'password123');
        $this->client->loginUser($user);

        $this->client->request('GET', '/recipes');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/logout');
        self::assertResponseRedirects();

        $this->client->request('GET', '/recipes');
        self::assertResponseRedirects('/login');
    }
}
