<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\Telegram\TelegramMessage;
use NotificationChannels\Telegram\TelegramUpdates;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Mengambil update dari request yang dikirim Telegram
        $updates = TelegramUpdates::create($request->all());

        // Periksa apakah ada pesan teks
        if ($updates->isCommand() && $updates->getCommand() === 'start') {
            $chatId = $updates->getChat()->getId();
            $firstName = $updates->getFrom()->getFirstName();

            $responseText = "Halo, {$firstName}!\n\n"
                          . "Terima kasih telah memulai bot. "
                          . "Gunakan Chat ID berikut untuk menerima notifikasi dari Uptime Monitor:\n\n`{$chatId}`";

            // Mengirim balasan menggunakan TelegramMessage
            TelegramMessage::create($responseText)
                ->to($chatId)
                ->options(['parse_mode' => 'Markdown'])
                ->send();
        }

        return response()->json(['status' => 'ok']);
    }
}