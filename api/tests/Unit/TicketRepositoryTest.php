<?php

namespace Tests\Unit;

use App\Models\Ticket;
use App\Repositories\TicketRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TicketRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected $ticketRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ticketRepository = new TicketRepository(new Ticket());
    }

    public function test_it_can_create_a_ticket()
    {
        // Create dependencies
        $user = \App\Models\User::factory()->create();
        $branch = \App\Models\Branch::factory()->create();
        $category = \App\Models\TicketCategory::factory()->create();

        $data = [
            'title' => 'Test Ticket',
            'description' => 'Test Description',
            'priority' => 'medium',
            'category_id' => $category->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'status' => 'open'
        ];

        // Use real repository implementation (integration test)
        // Ensure dependent services are handled or mocked if necessary,
        // but for TicketRepository, we want to test the DB insertion logic.
        // If WhatsAppService is triggered, we should mock IT, not the Ticket model.

        $whatsappMock = Mockery::mock(\App\Services\WhatsAppNotificationService::class);
        $whatsappMock->shouldReceive('sendNewTicketNotification')->andReturn(['status' => 'success']);
        $this->app->instance(\App\Services\WhatsAppNotificationService::class, $whatsappMock);

        $repository = new TicketRepository(new Ticket());
        $ticket = $repository->create($data);

        $this->assertDatabaseHas('tickets', [
            'title' => 'Test Ticket',
            'description' => 'Test Description',
            'user_id' => $user->id,
        ]);

        $this->assertEquals($data['title'], $ticket->title);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
