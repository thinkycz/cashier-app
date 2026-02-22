<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'company_name' => 'Acme s.r.o.',
                'company_id' => 'ACME-001',
                'vat_id' => 'CZ12345678',
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'phone_number' => '+420123456789',
                'street' => 'Main 1',
                'city' => 'Prague',
                'zip' => '11000',
                'country_code' => 'CZ',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Acme s.r.o.', $user->company_name);
        $this->assertSame('ACME-001', $user->company_id);
        $this->assertSame('CZ12345678', $user->vat_id);
        $this->assertSame('Test', $user->first_name);
        $this->assertSame('User', $user->last_name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('+420123456789', $user->phone_number);
        $this->assertSame('Main 1', $user->street);
        $this->assertSame('Prague', $user->city);
        $this->assertSame('11000', $user->zip);
        $this->assertSame('CZ', $user->country_code);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_company_id_must_be_unique_when_provided(): void
    {
        User::factory()->create(['company_id' => 'ACME-001']);
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'company_id' => 'ACME-001',
            ]);

        $response
            ->assertSessionHasErrors('company_id')
            ->assertRedirect('/profile');
    }

    public function test_current_user_can_keep_their_existing_company_id(): void
    {
        $user = User::factory()->create(['company_id' => 'ACME-001']);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'company_id' => 'ACME-001',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');
    }

    public function test_optional_profile_fields_can_be_empty(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'company_name' => '',
                'company_id' => '',
                'vat_id' => '',
                'first_name' => '',
                'last_name' => '',
                'email' => $user->email,
                'phone_number' => '',
                'street' => '',
                'city' => '',
                'zip' => '',
                'country_code' => '',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertNull($user->company_name);
        $this->assertNull($user->company_id);
        $this->assertNull($user->vat_id);
        $this->assertNull($user->first_name);
        $this->assertNull($user->last_name);
        $this->assertNull($user->phone_number);
        $this->assertNull($user->street);
        $this->assertNull($user->city);
        $this->assertNull($user->zip);
        $this->assertNull($user->country_code);
    }

    public function test_country_code_must_have_exactly_two_characters_when_provided(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'country_code' => 'CZE',
            ]);

        $response
            ->assertSessionHasErrors('country_code')
            ->assertRedirect('/profile');
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
