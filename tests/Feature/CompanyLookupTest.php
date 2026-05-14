<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_be_found_by_inn_in_sandbox(): void
    {
        config(['services.dadata.sandbox' => true]);

        $user = User::factory()->create(['role' => User::ROLE_OWNER]);

        $this->actingAs($user)
            ->postJson(route('clients.lookup-company'), [
                'inn' => '7700000000',
            ])
            ->assertOk()
            ->assertJsonPath('data.type', 'legal_entity')
            ->assertJsonPath('data.inn', '7700000000')
            ->assertJsonPath('data.source', 'sandbox');
    }

    public function test_company_lookup_rejects_invalid_inn(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);

        $this->actingAs($user)
            ->postJson(route('clients.lookup-company'), [
                'inn' => '123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('inn');
    }

    public function test_company_lookup_requires_authentication(): void
    {
        $this->postJson(route('clients.lookup-company'), [
            'inn' => '7700000000',
        ])->assertUnauthorized();
    }
}
