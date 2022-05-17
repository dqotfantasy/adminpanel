<?php

namespace App\Http\Controllers;

use App\Jobs\SendNotification;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Notifications\CustomNotification;
use Carbon\Carbon;
use App\Models\UserContest;

use Exception;


class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $type = \request('type');
        $query = Notification::query();
        $perPage = \request('per_page') ?? 15;
        if (request('type')) {
            $query->where('type', $type);
        }
        $query->orderBy('created_at', 'desc');
        $notifications = $query->paginate($perPage);

        $types = [];
        foreach (NOTIFICATION_TYPE as $item) {
            $types[] = ['id' => $item, 'name' => $item];
        }

        return apiResponse(true, null, ['notifications' => $notifications, 'type' => $types]);
    }

    public function sendAll(Request $request)
    {
        $data = $request->validate([
            'type' => 'required',
            'subject' => 'required',
            'message' => 'required',
            'image' => 'required',
            'day' => 'bail|integer|min:0',
        ]);
        $userId = \request('user_id');
        $unplay_day = $request->day;
        $played_user = '';
        if (!empty($unplay_day)) {
            $date = Carbon::now()->subDays($unplay_day);
            $played_user = User::query()
                ->whereNotNull('fcm_token')
                ->whereNotIn(
                    'id',
                    UserContest::select('user_id')->distinct()->where('created_at', '>=', $date)->get()
                )
                ->orderBy('id')
                ->pluck('id');
        }

        $notification = new Notification();

        if (empty($userId)) {
            $notification->type = $request->type;
            $notification->subject = $request->subject;
            $notification->message = $request->message;
            $notification->user_id = $played_user;

            if ($request->hasFile('image')) {
                $path = $request->file('image')->storePublicly('notifications', 's3');
                $imageFile = Storage::disk('s3')->url($path);
                $notification->image = $imageFile;
            }
        } else {
            $notification->type = $request->type;
            $notification->subject = $request->subject;
            $notification->message = $request->message;
            $notification->user_id = $userId;

            if ($request->hasFile('image')) {
                $path = $request->file('image')->storePublicly('notifications', 's3');
                $imageFile = Storage::disk('s3')->url($path);
                $notification->image = $imageFile;
            }

        }
        $notification->save();
        SendNotification::dispatch($notification, $userId, $unplay_day);

        return apiResponse(true, 'Notification send.');
    }
}
