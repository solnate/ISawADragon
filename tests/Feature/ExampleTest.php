<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $json = '{
            "update_id": 778378486,
            "message": {
                "message_id": 17,
                "from": {
                    "id": 422317387,
                    "is_bot": false,
                    "first_name": "Denis",
                    "last_name": "Nikulin",
                    "username": "d1stantfuture"
                },
                "chat": {
                    "id": -1002470032008,
                    "title": "Bots",
                    "type": "supergroup"
                },
                "date": 1736014956,
                "text": "@DndBDNotifyBot /add",
                "entities": [
                    {
                        "offset": 0,
                        "length": 15,
                        "type": "mention"
                    },
                    {
                        "offset": 16,
                        "length": 4,
                        "type": "bot_command"
                    }
                ]
            }
        }';
        $response = $this->post('/webhook', json_decode($json, true));

        $response->assertStatus(200);
    }
}
