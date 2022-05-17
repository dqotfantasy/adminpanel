<?php

use Illuminate\Support\Facades\Redis;
use App\Models\Setting;

const BANK_DETAIL_STATUS = ['PENDING', 'VERIFIED', 'UNLINKED', 'REJECTED'];
const CRICKET_TYPES = ['T20', 'T10', 'ODI', 'TEST'];
const POSITIONS = ['WK', 'BAT', 'AR', 'BOWL'];
const ROLES = ['admin', 'user'];
const CRICKET_INNING_BREAK = ['T20'=>'10', 'T10'=>'5', 'ODI'=>'15', 'TEST'=>'15']; // In Minutes

const FIXTURE_STATUS = ['NOT STARTED', 'LIVE', 'IN REVIEW', 'COMPLETED', 'CANCELED'];
const CONTEST_STATUS = ['NOT STARTED', 'LIVE', 'IN REVIEW', 'COMPLETED', 'CANCELED'];
const COMPETITION_STATUS = ['LIVE', 'FIXTURE'];
const PAYMENT_TYPES = ['DEPOSIT', 'WITHDRAW', 'CONTEST JOIN', 'CONTEST WON', 'REFUND','ADMIN ADDED','ADMIN DEDUCT','PROMOTER ADD','PROMOTER UPDATE','COUPON','PROMOTER COMMISSION','LEVEL BONUS'];
const CONTEST_TYPE = ['PRACTICE', 'PAID', 'FREE', 'DISCOUNT'];
const NOTIFICATION_TYPE = ['TRANSACTIONAL', 'PROMOTIONAL', 'GAMEPLAY', 'PROFILE', 'SOCIAL'];

// DEMO
// const PROD_CLIENT_ID='CF127080C7T6938SN97UHOLPCKR0';
// const PROD_CLIENT_SECRET='3edf3009ff2f11512c95dda3dd589319d139f205';

function SettingData($key){
    $tdsSetting = Setting::query()->firstWhere('key', $key);
    return $tdsSetting->value;
}
// live Cash free payout
const PROD_CLIENT_ID='CF110979C8U28P7GPCB67J9FRU8G';
const PROD_CLIENT_SECRET='c7f8724ab6decab34c2eb8454ba39c3efce3eea4';

function roleId(){
    return auth()->user()->role_id;
}

function apiResponse($status, $message, $data = null)
{
    return response([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
}

function generateRandomString($length = 7)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return 'M' . $randomString;
}

function uuid($data = null) {
    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
    $data = $data ?? random_bytes(16);
    assert(strlen($data) == 16);

    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Output the 36 character UUID.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function rankedInList($name, $members): array
{
    if (count($members) === 0) {
        return [];
    }

    $ranksForMembers = [];
    $transaction = Redis::multi();

    foreach ($members as $member) {
        $transaction->zrevrank($name, $member);
        $transaction->zscore($name, $member);
    }

    $replies = $transaction->exec();
    foreach ($members as $index => $m) {
        $data = [];

        $data['member'] = $m;

        if ($replies[($index * 2) + 1]) {
            $data['score'] = floatval($replies[($index * 2) + 1]);
        } else {
            $data['score'] = null;
            $data['rank'] = null;
        }

        $count = Redis::zcount($name, "(${data['score']}", '+inf');
        $data['rank'] = $count + 1;

        $ranksForMembers[] = $data;
    }

    return $ranksForMembers;
}

function get_dummy_inning_json() {
    $data = [
        'playing11_point' => 0,
        'runs' => 0,
        'runs_point' => 0.0,
        'fours' => 0,
        'fours_point' => 0.0,
        'sixes' => 0,
        'sixes_point' => 0.0,
        'century_half_century'=>0,
        'century_half_century_point' => 0.0,
        'strike_rate' => 0.0,
        'strike_rate_point'=> 0.0,
        'duck' => 0,
        'duck_point' => 0.0,
        'wicket' => 0,
        'wicket_point' => 0.0,
        'maiden_over' => 0,
        'maiden_over_point' => 0.0,
        'economy_rate' => 0.0,
        'economy_rate_point' => 0.0,
        'catch' => 0,
        'catch_point' => 0.0,
        'runoutstumping' => 0,
        'runoutstumping_point' => 0.0,
        'balls_faced' => 0,
        'overs_bowled' => 0,
        'lbw_bowled_bonus' => 0.0,
        'wicket_bonus' => 0,
        'catch_bonus' => 0.0,
        'bonus_point' => 0.0,
        'total_points' => 0.0
    ];
    return json_encode($data);
}

function getCricketMatchType($status_str)
{
    $status_str = strtoupper($status_str);
    if( $status_str == 'ODI' || $status_str == 'WOMEN ODI' || $status_str == 'WOMAN ODI' || $status_str == 'YOUTH ODI' || $status_str == 'LIST A' ){
        return 'ODI';
    } else if( $status_str == 'T20I' || $status_str == 'T20' || $status_str == 'WOMEN T20' || $status_str == 'WOMAN T20' || $status_str == 'YOUTH T20' ){
        return 'T20';
    } else if( $status_str == 'TEST' || $status_str == 'FIRST CLASS' ){
        return 'TEST';
    } else if( $status_str == 'T10' ){
        return 'T10';
    } else {
        return 'ODI';
    }
}
