<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_a_jwt_and_protected_routes_require_it(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk()->assertJsonStructure(['token', 'token_type', 'expires_in']);

    }

    public function test_protected_routes_reject_requests_without_a_token(): void
    {
        $this->getJson('/api/tickets')->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
        $this->get('/api/tickets')->assertUnauthorized()->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_ticket_crud_validates_fields_and_returns_expected_statuses(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);
        $headers = ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'];

        $this->postJson('/api/tickets', [], $headers)->assertStatus(400)->assertJsonValidationErrors(['title', 'description']);

        $created = $this->postJson('/api/tickets', [
            'title' => 'Account issue',
            'description' => 'Cannot sign in',
            'status' => 'open',
        ], $headers)->assertCreated()->assertJsonPath('status', 'open');

        $id = $created->json('id');
        $this->getJson('/api/tickets', $headers)->assertOk()->assertJsonCount(1);
        $this->getJson("/api/tickets/{$id}", $headers)->assertOk()->assertJsonPath('title', 'Account issue');
        $agent = User::factory()->create(['role' => 'agent']);
        $agentHeaders = ['Authorization' => 'Bearer '.auth('api')->login($agent)];
        Auth::forgetGuards();
        $this->putJson("/api/tickets/{$id}", ['status' => 'closed'], $agentHeaders)->assertOk()->assertJsonPath('status', 'closed');
        Auth::forgetGuards();
        $this->putJson("/api/tickets/{$id}", ['status' => 'invalid'], $agentHeaders)->assertStatus(400)->assertJsonValidationErrors('status');
        Auth::forgetGuards();
        $this->deleteJson("/api/tickets/{$id}", [], $agentHeaders)->assertOk();
        $this->getJson("/api/tickets/{$id}", $headers)->assertNotFound();
    }

    public function test_users_cannot_access_another_users_tickets(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = Ticket::create([
            'title' => 'Private ticket',
            'description' => 'Only the owner can see this',
            'status' => 'open',
            'user_id' => $owner->id,
        ]);
        $token = auth('api')->login($otherUser);

        $this->getJson("/api/tickets/{$ticket->id}", ['Authorization' => "Bearer {$token}"])->assertNotFound();
    }

    public function test_agents_can_reply_and_ticket_responses_include_reply_history(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $agent = User::factory()->create(['role' => 'agent']);
        $ticket = Ticket::create([
            'title' => 'Account issue',
            'description' => 'Cannot sign in',
            'status' => 'open',
            'user_id' => $customer->id,
        ]);
        $agentHeaders = ['Authorization' => 'Bearer '.auth('api')->login($agent)];
        $this->postJson("/api/tickets/{$ticket->id}/reply", ['message' => 'Please try again.'], $agentHeaders)
            ->assertCreated()
            ->assertJsonPath('message', 'Please try again.')
            ->assertJsonPath('user.role', 'agent');

        Auth::forgetGuards();
        $this->getJson('/api/tickets', $agentHeaders)
            ->assertOk()
            ->assertJsonPath('0.replies.0.message', 'Please try again.');

        Auth::forgetGuards();
        $this->postJson("/api/tickets/{$ticket->id}/reply", ['message' => '  '], $agentHeaders)
            ->assertStatus(400)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_customers_cannot_reply_to_tickets(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $ticket = Ticket::create([
            'title' => 'Account issue',
            'description' => 'Cannot sign in',
            'status' => 'open',
            'user_id' => $customer->id,
        ]);
        $customerHeaders = ['Authorization' => 'Bearer '.auth('api')->login($customer)];

        $this->postJson("/api/tickets/{$ticket->id}/reply", ['message' => 'Please try again.'], $customerHeaders)
            ->assertForbidden();
    }
}
