<?php

namespace App\Exports;

use App\Models\Fixture;
use Illuminate\Support\Collection;
use Kreait\Firebase\Util\JSON;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EarningManagerExport implements FromCollection, WithHeadings
{
    private $from;
    private $to;
    private $competition_id;
    private $user_type;

    function __construct($from, $to,$competition_id, $user_type)
    {
        $this->from = $from;
        $this->to = $to;
        $this->competition_id = $competition_id;
        $this->user_type = $user_type;
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {

        $query = Fixture::query();
        $query->withCount(['contests', 'user_teams']);

        if(!empty($this->from)){
            $query->whereDate('last_squad_update', '>=', $this->from);
        }
        if(!empty($this->to)){
            $query->whereDate('last_squad_update', '<=', $this->to);
        }
        if(!empty($this->competition_id)){
            $query->where('competition_id',$this->competition_id);
        }
        $flag=false;
        if(!empty($this->user_type) && $this->user_type=='normal_user'){
            $flag=true;
        }
        $status="COMPLETED,CANCELED";
        if (isset($status)) {

            $query->whereIn('status', explode(",", $status));
        }
        $query->orderBy('last_squad_update','desc');
        $data = $query->get();
        $array = [];

        foreach ($data as $key => $val) {
            $array[$key]['Match Name'] = $val->name;
            $array[$key]['Date'] = $val->last_squad_update;
            if($flag){
                $payment_data=json_decode($val->payment_data,true);
            }else{
                $payment_data=json_decode($val->payment_data_all,true);
            }
            $array[$key]['Amount Collected'] = !empty($payment_data['total_amount'])?$payment_data['total_amount']:0;
            $array[$key]['Bonus'] = !empty($payment_data['used_cash_bonus'])?$payment_data['used_cash_bonus']:0;
            $array[$key]['Winning'] = !empty($payment_data['total_amount'])?$payment_data['total_amount']:0;
            $array[$key]['Deposit'] = !empty($payment_data['used_deposited_balance'])?$payment_data['used_deposited_balance']:0;
            $array[$key]['Winning Distribution'] = !empty($payment_data['total_winning_distributed'])?$payment_data['total_winning_distributed']:0;
            $array[$key]['Total Earning'] = $this->calculateEarning($payment_data);

        }
        return new Collection($array);
    }

    public function calculateEarning($data){
        if($data){
            $total=$data['total_amount']-$data['used_cash_bonus']-$data['total_winning_distributed'];
            return $total;
        }else{
            return 0;
        }
    }

    public function headings(): array

    {
        return [
            'Amount Collected',
            'Date',
            'Amount Collected',
            'Bonus',
            'Winning',
            'Deposit',
            'Winning Distribution',
            'Total Earning'
        ];
    }
}
