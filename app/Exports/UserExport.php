<?php

namespace App\Exports;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class UserExport implements FromCollection, WithHeadings
{
    private $user_type;
    private $document_verified;

    function __construct($user_type, $document_verified)
    {
        $this->user_type = $user_type;
        $this->document_verified = $document_verified;
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
        if($this->user_type==1){
            $query->where('u.is_sys_user',1);
        }elseif($this->user_type==0){
            $query->where('u.is_sys_user',0);
        }
        if($this->document_verified==1){
            $query->where('u.document_verified',1);
        }elseif($this->document_verified==0){
            $query->where('u.document_verified',0);
        }
        $data = $query->get();
        //Log::info('urlllllllll'.json_encode($data));
        //return $data;
        $array = [];

        foreach ($data as $key => $val) {
            $array[$key]['Name'] = $val->name;
            $array[$key]['Username'] = $val->username;
            $array[$key]['Email'] = $val->email;
            $array[$key]['Phone'] = $val->phone;
            $array[$key]['Status'] = (($val->is_locked==0)?"Active":"Deactivee");
            $array[$key]['Date of Birth'] = $val->date_of_birth;
            $array[$key]['Winning Balance'] = $val->winning_amount;
            $array[$key]['Deposite Balance'] = $val->deposited_balance;
            $array[$key]['Cash Balance'] = $val->cash_bonus;
            $array[$key]['Account Number'] = $val->account_number;
            $array[$key]['Join at'] = $val->join_at;
        }
        return new Collection($array);
    }

    public function headings(): array

    {
        return [
            'Name',
            'Username',
            'Email',
            'Phone',
            'Status',
            'Date of Birth',
            'Winning Amount',
            'Deposite Balance',
            'Cash Balance',
            'Account Number',
            'Join at'
        ];
    }
}
