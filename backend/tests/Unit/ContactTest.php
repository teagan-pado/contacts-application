<?php

namespace Tests\Unit;

use App\Jobs\CreateContactJob;
use App\Models\Contact;
use App\Models\ContactBook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_contact()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $contactBook = ContactBook::factory()->create();
        $contactBook->users()->attach($user->id);

        // Mock the job dispatch
        Queue::fake();

        $response = $this->postJson(route('contacts.store', $contactBook->id), [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'phone' => '1234567890',
        ]);

        $response->assertStatus(202)
            ->assertJson([
                'message' => 'Contact creation task has been created',
            ]);

        // Assert that the job was dispatched with correct parameters
        Queue::assertPushed(CreateContactJob::class);
    }

    /** @test */
    public function it_can_view_a_contact()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $contactBook = ContactBook::factory()->create();
        $contactBook->users()->attach($user->id);

        $contact = Contact::factory()->create([
            'contact_book_id' => $contactBook->id,
            'name' => 'Jane Doe',
        ]);

        $response = $this->getJson(route('contacts.show', [$contactBook->id, $contact->id]));

        $response->assertStatus(200)
            ->assertJson([
                'id' => $contact->id,
                'name' => 'Jane Doe',
            ]);
    }

    /** @test */
    public function it_can_update_a_contact()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $contactBook = ContactBook::factory()->create();
        $contactBook->users()->attach($user->id);

        $contact = Contact::factory()->create([
            'contact_book_id' => $contactBook->id,
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'phone' => '1234567890',
        ]);

        $response = $this->putJson(route('contacts.update', [$contactBook->id, $contact->id]), [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'phone' => '0987654321',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $contact->id,
                'name' => 'New Name',
                'email' => 'new@example.com',
                'phone' => '0987654321',
            ]);

        $this->assertDatabaseHas('contacts', ['email' => 'new@example.com']);
    }

    /** @test */
    public function it_can_delete_a_contact()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $contactBook = ContactBook::factory()->create();
        $contactBook->users()->attach($user->id);

        $contact = Contact::factory()->create([
            'contact_book_id' => $contactBook->id,
            'name' => 'Temporary Contact',
        ]);

        $response = $this->deleteJson(route('contacts.destroy', [$contactBook->id, $contact->id]));

        $response->assertStatus(204);
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }
}
