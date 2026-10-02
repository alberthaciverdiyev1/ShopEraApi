<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_retrieve_contact_information(): void
    {
        $response = $this->getJson('/api/contact');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'status_code',
                'message',
                'data' => [
                    'address',
                    'email',
                    'phone',
                    'phones',
                    'whatsapp_number',
                    'google_map_url',
                    'instagram_url',
                    'tiktok_url',
                ],
            ]);
    }

    public function test_can_submit_contact_message(): void
    {
        $payload = [
            'first_name' => 'Elvin',
            'last_name' => 'Mammadov',
            'email' => 'elvin@example.com',
            'phone' => '+994501234567',
            'subject' => 'Order support',
            'message' => 'Salam, sifarişimlə bağlı sualım var.',
        ];

        $response = $this->postJson('/api/contact', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status_code' => 200,
            ])
            ->assertJsonStructure([
                'data' => ['id'],
            ]);

        $this->assertDatabaseHas('contact_messages', [
            'first_name' => 'Elvin',
            'last_name' => 'Mammadov',
            'email' => 'elvin@example.com',
            'subject' => 'Order support',
        ]);
    }

    public function test_contact_form_validation(): void
    {
        $response = $this->postJson('/api/contact', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'email', 'message']);
    }
}
