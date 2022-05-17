<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     *
     * @param Setting $setting
     * @return Response
     */
    public function show(Setting $setting)
    {
        if ((is_null(json_decode($setting->value, TRUE))) ? FALSE : TRUE) {
            $setting['value'] = json_decode($setting->value, TRUE);
        }

        return apiResponse(true, null, [$setting['key'] => $setting['value']]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Setting $setting
     * @return Response
     */
    public function update(Request $request, Setting $setting)
    {
        //echo "<pre>";print_r($request->all());die;
        if ($setting->key === 'teams') {
            $validator = Validator::make($request->all(), [
                'min_players' => 'bail|required|min:1|numeric',
                'max_players' => 'bail|required|min:1|numeric',
                'max_players_per_team' => 'bail|required|min:1|numeric',
                'min_wicket_keepers' => 'bail|required|min:1|numeric',
                'max_wicket_keepers' => 'bail|required|min:1|numeric',
                'min_batsmen' => 'bail|required|min:1|numeric',
                'max_batsmen' => 'bail|required|min:1|numeric',
                'min_all_rounders' => 'bail|required|min:1|numeric',
                'max_all_rounders' => 'bail|required|min:1|numeric',
                'min_bowlers' => 'bail|required|min:1|numeric',
                'max_bowlers' => 'bail|required|min:1|numeric',
            ]);

            if ($validator->fails()) {
                return apiResponse(false, $validator->errors()->first());
            }

            $setting->update(['value' => json_encode($validator->validated(),JSON_NUMERIC_CHECK)]);


            return apiResponse(true, 'Setting saved.');
        } elseif ($setting->key === 'withdraw') {
            $validator = Validator::make($request->all(), [
                'min_amount' => 'bail|required|min:1|numeric',
                'max_amount' => 'bail|required|min:1|numeric',
            ]);

            if ($validator->fails()) {
                return apiResponse(false, $validator->errors()->first());
            }

            $setting->update(['value' => json_encode($validator->validated(),JSON_NUMERIC_CHECK)]);

            return apiResponse(true, 'Setting saved.');
        } elseif ($setting->key === 'version') {
            $validator = Validator::make($request->all(), [
                'version' => 'bail|required|min:1|numeric',
            ]);

            if ($validator->fails()) {
                return apiResponse(false, $validator->errors()->first());
            }

            $setting->update(['value' => $request->version]);

            return apiResponse(true, 'Setting saved.');
        } elseif ($setting->key === 'referral_price') {
            $validator = Validator::make($request->all(), [
                'referral_price' => 'bail|required|min:1|numeric',
            ]);

            if ($validator->fails()) {
                return apiResponse(false, $validator->errors()->first());
            }

            $setting->update(['value' => $request->referral_price]);

            return apiResponse(true, 'Setting saved.');
        } elseif ($setting->key === 'tds_deduction') {
            $validator = Validator::make($request->all(), [
                'tds_deduction' => 'bail|required|min:1|numeric',
            ]);

            if ($validator->fails()) {
                return apiResponse(false, $validator->errors()->first());
            }

            $setting->update(['value' => $request->tds_deduction]);

            return apiResponse(true, 'Setting saved.');
        } elseif ($setting->key === 'level_limit') {
            $validator = Validator::make($request->all(), [
                'limit' => 'bail|required|min:1|numeric',
                'bonus' => 'bail|required|min:1|numeric',
            ]);

            if ($validator->fails()) {
                return apiResponse(false, $validator->errors()->first());
            }

            $setting->update(['value' => json_encode($validator->validated(),JSON_NUMERIC_CHECK)]);

            return apiResponse(true, 'Setting saved.');
        } elseif ($setting->key === 'personal_contest_commission') {
            $validator = Validator::make($request->all(), [
                'personal_contest_commission' => 'bail|required|min:1|numeric',
            ]);

            if ($validator->fails()) {
                return apiResponse(false, $validator->errors()->first());
            }

            $setting->update(['value' => $request->personal_contest_commission]);

            return apiResponse(true, 'Setting saved.');
        } elseif ($setting->key === 'entity_sport') {
            // $request->validate([
            //     'token' => 'bail|required',
            // ]);

            $validator = Validator::make($request->all(), [
                'token' => 'bail|required',
            ]);

            if ($validator->fails()) {
                return apiResponse(false, $validator->errors()->first());
            }
            Redis::set($setting->key, json_encode($validator->validated()));
            return apiResponse(true, 'Setting saved.');
        } elseif ($setting->key === 'private_contest') {
            $validator = Validator::make($request->all(), [
                'min_contest_size' => 'bail|required|numeric',
                'max_contest_size' => 'bail|required|numeric',
                'min_entry_fee' => 'bail|required|numeric',
                'max_entry_fee' => 'bail|required|numeric',
                'min_allow_multi' => 'bail|required|numeric',
                'max_allow_multi' => 'bail|required|numeric',
                'commission_value' => 'bail|required|numeric',
                'commission_on_fee' => 'bail|required|numeric',
            ]);

            if ($validator->fails()) {
                return apiResponse(false, $validator->errors()->first());
            }

            $setting->update(['value' => json_encode($validator->validated(),JSON_NUMERIC_CHECK)]);

            return apiResponse(true, 'Setting saved.');
        } elseif ($setting->key === 'signup_bonus') {
            $validator = Validator::make($request->all(), [
                'signup_bonus' => 'bail|required|min:1|numeric',
            ]);

            if ($validator->fails()) {
                return apiResponse(false, $validator->errors()->first());
            }

            $setting->update(['value' => $request->signup_bonus]);

            return apiResponse(true, 'Setting saved.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Setting $setting
     * @return Response
     */
    public function destroy(Setting $setting)
    {
        //
    }

    public function get(Request $request)
    {

        $typeV = Validator::make($request->all(), [
            'type' => 'in:entity_sport,razorpay,version',
        ]);

        if ($typeV->fails()) {
            return apiResponse(false, $typeV->errors()->first());
        }

        $type = $request->type;
        $data = Redis::get($type);

        return apiResponse(true, null, json_decode($data));
    }

    public function set(Request $request)
    {
        $typeV = Validator::make($request->all(), [
            'type' => 'in:entity_sport,razorpay,version',
        ]);

        if ($typeV->fails()) {
            return apiResponse(false, $typeV->errors()->first());
        }

        $type = $request->type;

        if ($type == 'entity_sport') {
            // $request->validate([
            //     'token' => 'bail|required',
            // ]);
            $validator = Validator::make($request->all(), [
                'token' => 'bail|required',
            ]);

            if ($validator->fails()) {
                return apiResponse(false, $validator->errors()->first());
            }
            Redis::set($type, json_encode($validator->validated()));
        } else if ($type == 'razorpay') {

            $validator = Validator::make($request->all(), [
                'access_key' => 'bail|required',
                'secret_key' => 'bail|required',
                'account_number' => 'bail|required',
                'webhook_secret' => 'bail|required',
            ]);

            if ($validator->fails()) {
                return apiResponse(false, $validator->errors()->first());
            }

            Redis::set($type, json_encode($validator->validated()));
            Setting::query()->where('key', 'razorpay')->update(['value' => $validator->validated()]);
        } else if ($type == 'version') {
            $data = $request->validate([
                'name' => 'bail|required',
                'code' => 'bail|required',
                'force_update' => 'bail|required|boolean',
                'description' => 'bail|required',
                'file' => 'bail|file',
            ]);

            $name = $data['name'] . '.apk';
            $path = $request->file('file')->storePubliclyAs('apk', $name, 's3');

            $imageFile = Storage::disk('s3')->url($path);
            $data['url'] = $imageFile;

            unset($data['file']);

            Redis::set($type, json_encode($data));
            Setting::query()->where('key', 'version')->update(['value' => $data]);
        }

        return apiResponse(true, 'Setting saved.');
    }
}
