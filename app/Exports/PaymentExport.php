<?php

namespace App\Exports;

use App\Models\Payment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentExport implements FromCollection, WithHeadings
{
    private $from;
    private $to;
    private $user_id;
    private $typecontest;
    private $status;

    function __construct($from, $to,$user_id,$typecontest,$status)
    {
        $this->from = $from;
        $this->to = $to;
        $this->user_id = $user_id;
        $this->typecontest = $typecontest;
        $this->status = $status;
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        $query = Payment::query()
                ->from('payments', 'p')
                // ->leftJoin('contests as c','c.id','=','p.contest_id')
                // ->leftJoin('contest_categories as cc','cc.id','=','c.contest_category_id')
                // ->leftJoin('fixtures as f','f.id','=','c.fixture_id')
                ->select(['p.*']);

        $query->whereDate('p.created_at', '>=', $this->from);
        $query->whereDate('p.created_at', '<=', $this->to);
        if(!empty($this->user_id)){
            $query->where('p.user_id', '=', $this->user_id);
        }
        if(!empty($this->typecontest)){
            $query->where('p.type', '=', $this->typecontest);
        }
        if(!empty($this->status)){
            $query->where('p.status', '=', $this->status);
        }
        $query->with(['user']);
        $data = $query->get();
        $array = [];

        foreach ($data as $key => $val) {
            $extraArr= json_decode($val->extra,true);
            $array[$key]['User'] = $val->user->name;
            $array[$key]['Phone'] = $val->user->phone;
            $array[$key]['Description'] = $val->description;
            $array[$key]['Amount'] = $val->amount;
            $array[$key]['Created At'] = $val->created_at;
            $array[$key]['Contest Category'] = !empty($extraArr['category_name'])?$extraArr['category_name']:'';
            $array[$key]['Fixture Name'] = !empty($extraArr['fixture_name'])?$extraArr['fixture_name']:'';
            $array[$key]['Fixture id & Contest id'] = $val->fid."&".$val->contid;
            //$array[$key]['contest_id'] = $val->contests->contest_category_id;

        }
        return new Collection($array);
    }

    public function headings(): array

    {
        return [
            'User',
            'Phone',
            'Description',
            'Amount',
            'Created At',
            'contest_category_name',
            'Fixture Name',
            'Fixture id & Contest id'
        ];
    }
}
