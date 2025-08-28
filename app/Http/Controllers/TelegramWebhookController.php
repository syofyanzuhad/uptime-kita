<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use NotificationChannels\Telegram\TelegramMessage;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Mengambil update dari request yang dikirim Telegram
        $updates = $request->all();
        // info('request', $request->all());

        // --- BAGIAN YANG DIPERBAIKI TOTAL ---
        // Periksa apakah update berisi 'message' dan di dalamnya ada 'text'
        if (isset($updates['message']) && isset($updates['message']['text'])) {
            $message = $updates['message'];

            // Ambil data langsung dari properti array
            $chatId = $message['chat']['id'];
            $firstName = $message['from']['first_name'];

            // Periksa secara manual apakah teksnya adalah '/start'
            if ($message['text'] === '/start') {
                $responseText = "Halo, {$firstName}!\n\n"
                              .'Terima kasih telah memulai bot. '
                              ."Gunakan Chat ID berikut untuk menerima notifikasi dari Uptime Monitor:\n\n`{$chatId}`";

                // Mengirim balasan menggunakan TelegramMessage
                TelegramMessage::create($responseText)
                    ->to($chatId)
                    ->options(['parse_mode' => 'Markdown'])
                    ->send();
                info('send message success');
            }
        }
        // --- AKHIR BAGIAN YANG DIPERBAIKI ---

        return response()->json(['status' => 'ok']);
    }
}
