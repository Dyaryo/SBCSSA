<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_regular_event_has_a_title_and_no_entries(): void
    {
        $this->seed();

        $this->actingAs(User::where('username', 'gm.north')->firstOrFail())
            ->post('/gm/events', [
                'type' => 'regular',
                'name' => 'Friday Fight Night',
                'venue' => 'Arena A',
                'starts_at' => '2026-11-11',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $event = Event::where('venue', 'Arena A')->firstOrFail();

        $this->assertStringStartsWith('REG-', $event->code);

        $this->assertSame(Event::TYPE_REGULAR, $event->type);
        $this->assertSame('Friday Fight Night', $event->name);
        $this->assertSame('Friday Fight Night', $event->getRawOriginal('name'));
        $this->assertSame([], $event->entryNames());
    }

    public function test_an_event_without_a_title_is_rejected(): void
    {
        $this->seed();

        $this->actingAs(User::where('username', 'gm.north')->firstOrFail())
            ->from('/gm/events')
            ->post('/gm/events', ['type' => 'regular', 'venue' => 'No Title Arena'])
            ->assertRedirect('/gm/events')
            ->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('events', ['venue' => 'No Title Arena']);
    }

    public function test_a_derby_requires_a_title_and_entry_names(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $this->actingAs($gm)
            ->from('/gm/events')
            ->post('/gm/events', ['type' => 'derby'])
            ->assertRedirect('/gm/events')
            ->assertSessionHasErrors(['name', 'entries']);

        $this->actingAs($gm)
            ->post('/gm/events', [
                'type' => 'derby',
                'name' => 'Sunday Grand Derby',
                'entries' => "Sunrise Ace\nNight Fury\nGolden Spur",
                'starts_at' => '2026-11-12 09:00:00',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $event = Event::where('name', 'Sunday Grand Derby')->firstOrFail();

        $this->assertStringStartsWith('DRB-', $event->code);

        $this->assertTrue($event->isDerby());
        $this->assertSame('Sunday Grand Derby', $event->name);
        $this->assertSame(['Sunrise Ace', 'Night Fury', 'Golden Spur'], $event->entryNames());
        $this->assertSame('Derby', $event->typeLabel());
    }

    public function test_a_derby_with_a_single_entry_is_rejected(): void
    {
        $this->seed();

        $this->actingAs(User::where('username', 'gm.north')->firstOrFail())
            ->from('/gm/events')
            ->post('/gm/events', [
                'type' => 'derby',
                'name' => 'Lone Entry Derby',
                'entries' => 'Only One',
            ])
            ->assertRedirect('/gm/events')
            ->assertSessionHasErrors('entries');

        $this->assertDatabaseMissing('events', ['name' => 'Lone Entry Derby']);
    }

    public function test_the_create_form_uses_radios_and_asks_for_no_event_code(): void
    {
        $this->seed();

        $this->actingAs(User::where('username', 'gm.north')->firstOrFail())
            ->get('/gm/events')
            ->assertOk()
            ->assertSee('type="radio" name="type" value="regular"', false)
            ->assertSee('type="radio" name="type" value="derby"', false)
            ->assertDontSee('name="code"', false)
            ->assertSee('type="date" name="starts_at"', false)
            ->assertSee('Event title')
            ->assertSee('name="name"', false)
            ->assertDontSee('Derby name')
            ->assertSee('Event date');
    }

    public function test_the_events_page_lists_the_derby_entry_names(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $this->actingAs($gm)->post('/gm/events', [
            'type' => 'derby',
            'name' => 'Cockpit Classic',
            'entries' => "Sunrise Ace\nNight Fury",
        ])->assertRedirect();

        $this->actingAs($gm)
            ->get('/gm/events')
            ->assertOk()
            ->assertSee('Cockpit Classic')
            ->assertSee('Night Fury')
            ->assertSee('Derby');
    }
}
