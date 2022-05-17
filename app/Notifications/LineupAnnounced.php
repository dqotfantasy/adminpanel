<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\AndroidConfig;
use NotificationChannels\Fcm\Resources\AndroidFcmOptions;
use NotificationChannels\Fcm\Resources\AndroidNotification;
use NotificationChannels\Fcm\Resources\ApnsConfig;
use NotificationChannels\Fcm\Resources\ApnsFcmOptions;

class LineupAnnounced extends Notification implements ShouldQueue
{
    use Queueable;

    private $fixture;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($fixture)
    {
        $this->fixture = $fixture;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable): array
    {
        return [FcmChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return FcmMessage
     */
    public function toFcm($notifiable): FcmMessage
    {
        $title = $this->fixture->teama_short_name . " vs " . $this->fixture->teamb_short_name . ' | Lineups Announced';
        $body = 'Match starts at ' . Carbon::parse($this->fixture->starting_at)->format('h:m A') . "! Create your team now!";
        return FcmMessage::create()
            ->setData(['id' => (string)$this->fixture->id, 'type' => 'LINEUP', 'click_action' => 'FLUTTER_NOTIFICATION_CLICK'])
            ->setNotification(\NotificationChannels\Fcm\Resources\Notification::create()
                ->setTitle($title)
                ->setBody($body)
                ->setImage('https://via.placeholder.com/150')
            )
            ->setAndroid(
                AndroidConfig::create()
                    ->setFcmOptions(AndroidFcmOptions::create()->setAnalyticsLabel('analytics'))
                    ->setNotification(AndroidNotification::create()->setColor('#0A0A0A'))
            )->setApns(
                ApnsConfig::create()
                    ->setFcmOptions(ApnsFcmOptions::create()->setAnalyticsLabel('analytics_ios')));
    }

    public function fcmProject($notifiable, $message)
    {
        return 'app'; // name of the firebase project to use
    }
}
