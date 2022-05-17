<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\CustomNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\UserContest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class SendNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $notification;

    public $timeout = 6000;
    private $userId;
    private $unplay_day;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($notification,$userId=null,$unplay_day=null)
    {
        //
        $this->notification = $notification;
        $this->userId = $userId;
        $this->unplay_day = $unplay_day;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        if(!empty($this->userId)){
            $user=User::query()
                ->whereNotNull('fcm_token')
                ->where('id',$this->userId)
                ->groupBy('fcm_token')
                ->chunkById(100, function ($users) {
                    foreach ($users as $user) {
                        try {
                            $user->notify(new CustomNotification($this->notification));
                        } catch (\Exception $exception) {
                        }
                    }
                });
        }elseif(!empty($unplay_day) && $unplay_day!=0){
            $date = Carbon::now()->subDays($unplay_day);
            User::query()
                ->whereNotNull('fcm_token')
                ->whereNotIn('id',
                UserContest::select('user_id')->distinct()->where('created_at', '>=', $date)->get())
                ->orderBy('id')
                ->groupBy('fcm_token')
                ->chunkById(100, function ($users) {
                    foreach ($users as $user) {
                        try {
                            $user->notify(new CustomNotification($this->notification));
                        } catch (\Exception $exception) {
                        }
                    }
                });
        }else{
            User::query()
                ->whereNotNull('fcm_token')
                ->orderBy('id')
                ->groupBy('fcm_token')
                ->chunkById(100, function ($users) {
                    foreach ($users as $user) {
                        try {
                            $user->notify(new CustomNotification($this->notification));
                        } catch (\Exception $exception) {
                        }
                    }
                });
        }
    }
}
