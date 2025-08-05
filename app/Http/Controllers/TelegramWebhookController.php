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

        // Ambil objek pesan dari update
        $message = $updates->getMessage();

        // --- BAGIAN YANG DIPERBAIKI ---
        // Pastikan ada objek pesan dan pesan tersebut berisi teks
        if ($message && $message->has('text')) {

            $text = $message->getText();
            $chatId = $message->getChat()->getId();
            $firstName = $message->getFrom()->getFirstName();

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