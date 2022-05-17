<?php

namespace App\Exports;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SystemUserExport implements FromCollection, WithHeadings
{
    private $from;
    private $to;
    private $user_id;
    private $typecontest;
    private $status;

    function __construct($from, $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        $query = User::query()
                ->from('users', 'u')
                ->select('u.*');
        $query->where('u.is_sys_user',1);
        if(!empty($this->from)){
            $query->whereDate('created_at', '>=', $this->from);
        }
        if(!empty($this->to)){
            $query->whereDate('created_at', '<=', $this->to);
        }
        $data = $query->get();
        $array = [];

        foreach ($data as $key => $val) {
            $array[$key]['Id'] = $val->id;
            $array[$key]['Username'] = $val->username;
            $array[$key]['Email'] = $val->email;
        }
        return new Collection($array);
    }

    public function headings(): array

    {
        return [
            'Id',
            'Username',
            'Email'
        ];
    }
}
