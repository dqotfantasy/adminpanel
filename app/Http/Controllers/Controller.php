<?php

namespace App\Http\Controllers;

use App\Jobs\AutoWithdraw;
use App\Jobs\GenerateCommission;
use App\Mail\ForgotPassword;
use App\Mail\UserRegistered;
use App\Mail\VerifyEmail;
use App\Models\Contest;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Redis;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param $storage
     * @param $filename
     * @return \Illuminate\Http\Response
     */
    function image($storage, $filename)
    {
        $path = storage_path('app/' . $storage . '/' . $filename);

        if (!File::exists($path)) {
            abort(404);
        }

        $file = File::get($path);
        $type = File::mimeType($path);

        $response = Response::make($file);
        $response->header("Content-Type", $type);

        return $response;
    }

    public function handleEvent(Request $request)
    {

        if (!$request->has(['type', 'data', 'secret'])) {
            return \response([
                'status' => false
            ]);
        }

        if (!$request->secret == env('EVENT_SECRET')) {
            return \response([
                'status' => false
            ]);
        }

        $type = $request->type;
        $data = $request->data;

        if (method_exists(self::class, $type)) {
            $this->$type($data);
        }

        return \response([
            'status' => true
        ]);
    }

    private function sendEmail($data)
    {
        if (isset($data['user_id']) && isset($data['type'])) {
            $user = User::query()->find($data['user_id']);
            if ($user) {
                $type = $data['type'];
                if ($type === 'USER_REGISTERED') {
                    Mail::to($user)
                        ->queue(new UserRegistered($user));
                } else if ($type === 'FORGOT_PASSWORD') {
                    Mail::to($user)
                        ->queue(new ForgotPassword($user));
                } else if ($type === 'VERIFY_EMAIL') {
                    Mail::to($user)
                        ->queue(new VerifyEmail($user));
                }
            }
        }
    }

    // When contest full
    private function autoCreateContest($data)
    {

        if (isset($data['contest_id'])) {
            $contest = Contest::query()->where('auto_create_on_full',1)->find($data['contest_id']);

            if ($contest) {
                $newContest = $contest->replicate()->fill([
                    'invite_code' => generateRandomString(),
                    'status' => CONTEST_STATUS[1],
                ]);
                $newContest->save();
                Redis::set("contestSpace:$newContest->id", $newContest->total_teams);
            }
        }

    }

    private function autoWithdraw($data)
    {
        if (isset($data['payment_id'])) {
            AutoWithdraw::dispatch($data['payment_id']);
        }

    }

    private function generateCommission($data)
    {
        if (isset($data['payment_id'])) {
            GenerateCommission::dispatch($data['payment_id']);
        }

    }

}
