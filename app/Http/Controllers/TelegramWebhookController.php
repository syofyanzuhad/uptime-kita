<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use NotificationChannels\Telegram\TelegramMessage;
use NotificationChannels\Telegram\TelegramUpdates;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Mengambil update dari request yang dikirim Telegram
        $updates = TelegramUpdates::create($request->all());
        info($updates);

        // --- BAGIAN YANG DIPERBAIKI TOTAL ---
        // Periksa apakah update berisi 'message' dan di dalamnya ada 'text'
        if (isset($updates->message['text'])) {

            // Ambil data langsung dari properti array
            $text = $updates->message['text'];
            $chatId = $updates->message['chat']['id'];
            $firstName = $updates->message['from']['first_name'];

            // Periksa secara manual apakah teksnya adalah '/start'
            if ($text === '/start') {
                $responseText = "Halo, {$firstName}!\n\n"
                              . "Terima kasih telah memulai bot. "
                              . "Gunakan Chat ID berikut untuk menerima notifikasi dari Uptime Monitor:\n\n`{$chatId}`";

                // Mengirim balasan menggunakan TelegramMessage
                TelegramMessage::create($responseText)
                    ->to($chatId)
                    ->options(['parse_mode' => 'Markdown'])
                    ->send();
            }
        }
        // --- AKHIR BAGIAN YANG DIPERBAIKI ---

        return response()->json(['status' => 'ok']);
    }
}