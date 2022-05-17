<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\Payment;
use App\Models\User;
use App\Models\BankAccount;
use App\Models\PanCard;
use App\Models\UserContest;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::query()->where('role', 'user')->count();
        $todayDeposit = Payment::query()->where('TYPE', 'DEPOSIT')
            ->whereDate('created_at', '=', now())->sum('amount');
        $monthDeposit = Payment::query()->where('TYPE', 'DEPOSIT')
            ->whereMonth('created_at', '=', now()->month)->sum('amount');
        $monthWithdraw = Payment::query()->where('TYPE', 'WITHDRAW')
            ->whereMonth('updated_at', '=', now()->month)->where(['status'=>"SUCCESS"])->whereNotNull('reference_id')->sum('amount');
        $totalContests = Contest::query()->count();
        $activeContests = Contest::query()->whereIn('status', ['LIVE'])->count();
        $inActiveContests = Contest::query()->whereNotIn('status', ['LIVE'])->count();
        $todayJoinedContests = UserContest::query()->whereDate('created_at', '=', now())->count();
        $verifiedUsers = User::query()->where([['phone_verified', 1],['document_verified', 1],['email_verified', 1]])
                        ->count();

        $unverifiedBank = BankAccount::query()->where('status', 'PENDING')->count();
        $unverifiedpan = PanCard::query()->where('status', 'PENDING')->count();

        $unverifiedUsers = User::query()->join('bank_accounts as b','b.user_id','=','users.id')
                        ->join('pan_cards as pc','pc.user_id','=','users.id')
                        ->orWhere('b.status','PENDING')->orWhere('pc.status','PENDING')->count();

        $newUsers = User::query()->whereDate('created_at', '=', now())->count();
        $withdrawalRequests = Payment::query()->where('status', 'PENDING')
            ->where('type', 'WITHDRAW')->count();

        return apiResponse(true, null, [
            'total_users' => $totalUsers,
            'today_deposit' => $todayDeposit,
            'month_deposit' => $monthDeposit,
            'month_withdraw' => abs($monthWithdraw),
            'total_contests' => $totalContests,
            'active_contests' => $activeContests,
            'inactive_contests' => $inActiveContests,
            'today_joined_contests' => $todayJoinedContests,
            'verified_users' => $verifiedUsers,
            'unverified_users' => $unverifiedUsers,
            'new_users' => $newUsers,
            'withdrawal_requests' => $withdrawalRequests
        ]);
    }
}
