<?php

namespace App\Exports;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ReferalExport implements FromCollection, WithHeadings
{
    private $search;

    function __construct($search)
    {
        $this->search = $search;
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        $query = User::query()
                ->from('users', 'u')
                ->leftJoin('bank_accounts as b','b.user_id','=','u.id')
                ->select(['u.*','b.account_number',DB::raw('DATE(u.created_at) AS join_at')]);
        if(!empty($this->search)){
            $getuserId=User::where('username', 'LIKE', '%' . $this->search . '%')->get('id');

            $query=User::query()
            ->from('users','u')
            ->select(['u.*',DB::raw('(SELECT username FROM users as us WHERE us.id=u.referral_id) as ref_username')]);
            $query->whereNotNull('u.referral_id');
            $query->whereIn('u.referral_id',$getuserId);
        }else{
            $query=User::query()
            ->from('users','u')
            ->select(['u.*',DB::raw('(SELECT username FROM users as us WHERE us.id=u.referral_id) as ref_username')]);
            $query->whereNotNull('u.referral_id');
        }

        $data = $query->get();
        //Log::info('urlllllllll'.json_encode($data));
        //return $data;
        $array = [];

        foreach ($data as $key => $val) {
            $array[$key]['Email'] = $val->email;
            $array[$key]['Phone Number'] = $val->phone;
            $array[$key]['Referal User'] = $val->username;
            $array[$key]['Referal By'] = $val->ref_username;
        }
        return new Collection($array);
    }

    public function headings(): array

    {
        return [
            'Email',
            'Phone Number',
            'Referal User',
            'Referal By'
        ];
    }
}
