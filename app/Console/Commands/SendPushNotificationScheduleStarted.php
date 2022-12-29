<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Http\Response;

class SendPushNotificationScheduleStarted extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push-notification:send-schedule-started';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia as notificações push informando o início de um agendamento';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $schedules = Schedule::query()
            ->where("start_push_notification_sent", "=", false)
            ->where("started", "=", true)
            ->where('start_date', "<", now())
            ->whereNull("deleted_at")
            ->get();
        if (!$schedules) {
            return;
        }

        $client = new Client();

        foreach ($schedules as $schedule) {
            /* @var Schedule $schedule */
            $token = $schedule->user()->push_token;
            if (empty($token))
                continue;

            $response = $client->request("POST", "https://exp.host/--/api/v2/push/send", [
                "headers" => [
                    "Accept" => "application/json",
                    "Accept-encoding" => "gzip, deflate",
                    "Content-Type" => "application/json",
                ],
                "json" => [
                    "to" => $token,
                    "sound" => "default",
                    "title" => "Começou!!!",
                    "body" => "Seu agendamento iniciou e está em andamento!"
                ]
            ]);

            if ($response->getStatusCode() === Response::HTTP_OK) {
                $schedule->start_push_notification_sent = true;
                $schedule->save();
            }
        }
    }
}
