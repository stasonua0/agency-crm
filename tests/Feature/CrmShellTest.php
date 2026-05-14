<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_roles_are_supported(): void
    {
        $this->assertSame([
            'owner',
            'finance_manager',
            'viewer',
        ], User::ROLES);
    }

    public function test_authenticated_user_can_open_crm_sections(): void
    {
        $this->withoutVite();

        $user = User::factory()->create([
            'role' => User::ROLE_OWNER,
        ]);

        $this
            ->actingAs($user)
            ->get(route('clients.index'))
            ->assertOk();

        $this
            ->actingAs($user)
            ->get(route('audit.log.index'))
            ->assertOk();
    }
}
