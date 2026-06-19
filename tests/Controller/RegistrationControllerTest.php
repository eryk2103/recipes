<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Tests\Support\IntegrationTestCase;

class RegistrationControllerTest extends IntegrationTestCase
{
    public function testRegisterPageRenders(): void
    {
        $this->client->request('GET', '/register');

        self::assertResponseIsSuccessful();
    }

    public function testRegisterWithValidDataCreatesUserAndLogsIn(): void
    {
        $this->client->request('GET', '/register');
        $this->client->submitForm('Register', [
            'registration_form[username]' => 'new_chef',
            'registration_form[plainPassword]' => 'super-secret',
            'registration_form[agreeTerms]' => true,
        ]);

        self::assertResponseRedirects();

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'new_chef']);
        self::assertNotNull($user);

        $this->client->followRedirect();
        self::assertSelectorExists('.avatar-dropdown');
    }

    public function testRegisterWithoutAgreeingToTermsFailsValidation(): void
    {
        $this->client->request('GET', '/register');
        $this->client->submitForm('Register', [
            'registration_form[username]' => 'new_chef',
            'registration_form[plainPassword]' => 'super-secret',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertNull($this->em->getRepository(User::class)->findOneBy(['username' => 'new_chef']));
    }

    public function testRegisterWithDuplicateUsernameFailsValidation(): void
    {
        $this->createUser('amelia');

        $this->client->request('GET', '/register');
        $this->client->submitForm('Register', [
            'registration_form[username]' => 'amelia',
            'registration_form[plainPassword]' => 'super-secret',
            'registration_form[agreeTerms]' => true,
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'There is already an account with this username');
    }
}
