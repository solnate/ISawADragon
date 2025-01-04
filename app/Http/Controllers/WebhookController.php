<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Telegram\Bot\BotsManager;

class WebhookController extends Controller
{
    protected BotsManager $botsManager;
    public function __construct(BotsManager $botsManager)
    {
        $this->botsManager = $botsManager;
    }
    public function __invoke(Request $request): Response
    {
        $this->botsManager->bot('DndBDNotifyBot')->commandsHandler(true);
        return response(null ,200);
    }
}
