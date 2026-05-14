<?php

namespace Tests\Feature;

use App\Models\Payee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayeeCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_payee_can_be_created_updated_and_archived(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OWNER]);

        $this->actingAs($user)
            ->post(route('payees.store'), [
                'type' => Payee::TYPE_CONTRACTOR,
                'name' => 'Иван Подрядчик',
                'requisites' => 'Счёт 40702810000000000000',
                'phone' => '+7 900 000-00-00',
                'comment' => 'SEO подрядчик',
                'status' => Payee::STATUS_ACTIVE,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('payees.index'));

        $payee = Payee::query()->firstOrFail();

        $this->actingAs($user)
            ->put(route('payees.update', $payee), [
                'type' => Payee::TYPE_CONTRACTOR,
                'name' => 'Иван SEO',
                'requisites' => 'Счёт 40702810000000000001',
                'phone' => '+7 900 000-00-01',
                'comment' => 'Обновлено',
                'status' => Payee::STATUS_ACTIVE,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('payees.index'));

        $this->assertDatabaseHas('payees', [
            'id' => $payee->id,
            'name' => 'Иван SEO',
        ]);

        $this->actingAs($user)
            ->delete(route('payees.destroy', $payee))
            ->assertRedirect(route('payees.index'));

        $this->assertSame(Payee::STATUS_ARCHIVED, $payee->fresh()->status);
    }
}
