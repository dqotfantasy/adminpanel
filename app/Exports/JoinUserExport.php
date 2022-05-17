<?php

namespace App\Exports;

use App\Models\Contest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Log;


class JoinUserExport implements FromCollection, WithHeadings
{
    private $contes_categorie;
    private $user_type;
    private $fixture_id;

    function __construct($contes_categorie, $user_type,$fixture_id)
    {
        $this->contes_categorie = $contes_categorie;
        $this->user_type = $user_type;
        $this->fixture_id=$fixture_id;
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        $query = Contest::query()
                    ->from('contests','c')
                    ->join('contest_categories as cc','cc.id','=','c.contest_category_id')
                    ->Join('user_contests as uc','uc.contest_id','=','c.id')
                    ->Join('user_teams as ut','ut.id','=','uc.user_team_id')
                    ->Join('users','users.id','=','uc.user_id')
                    ->select(['users.is_sys_user','users.email','users.username','users.is_sys_user','uc.rank','ut.*','cc.name as contest_category_name','c.entry_fee','c.prize','c.total_teams']);

        if($this->user_type==1){
            $query->where('users.is_sys_user',1);
        }elseif($this->user_type==0){
            $query->where('users.is_sys_user',0);
        }
        if($this->fixture_id){
            $query->where('c.fixture_id', $this->fixture_id);
        }
        if($this->contes_categorie){
            $query->where('cc.id', $this->contes_categorie);
        }
        $data = $query->get();
        $array = [];

        foreach ($data as $key => $val) {
            $array[$key]['Rank'] = $val->rank;
            $array[$key]['Email'] = $val->email;
            $array[$key]['Username'] = $val->username;
            $array[$key]['Entry Fee'] = $val->entry_fee;
            $array[$key]['Total teams'] = $val->total_teams;
            $array[$key]['Total Points'] = $val->total_points;
            $array[$key]['Name'] = $val->name;
            $array[$key]['Contest Category'] = $val->contest_category_name;
        }
        return new Collection($array);
    }

    public function headings(): array

    {
        return [
            'Rank',
            'Email',
            'Username',
            'Entry Fee',
            'Total teams',
            'Total Points',
            'Name',
            'Contest Category'
        ];
    }
}
