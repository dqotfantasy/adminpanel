<?php

namespace App\Http\Controllers;
use App\Exports\SystemUserExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Models\UserTeam;
use App\Models\Squad;
use App\Models\Fixture;
use App\Jobs\GetPoint;
use App\Models\Contest;
use App\Models\UserContest;
use Illuminate\Foundation\Auth\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class SystemUserData extends Controller
{
    public function index(){
        $search = \request('search');
        $inning_number = \request('inning_number');
        $entry_fee = \request('entry_fee');
        $per_page = \request('per_page');
        $fixtureId = \request('fixtureId');

        if(!empty($fixtureId)){
            // $query = UserTeam::query()
            //     ->from('user_teams')
            //     //->where('fixture_id',$fixtureId)
            //     ->leftJoin('users as u', 'u.id', '=', 'user_teams.user_id')
            //     ->select(['user_teams.*', 'u.username as username','u.email','u.phone']);
            // $query->where([['u.is_sys_user',1],['fixture_id',$fixtureId]]);

            $query = Contest::query()
                ->from('contests','c')
                ->join('contest_categories as cc','cc.id','=','c.contest_category_id')
                ->Join('user_contests as uc','uc.contest_id','=','c.id')
                ->Join('user_teams as ut','ut.id','=','uc.user_team_id')
                ->leftJoin('users as u','u.id','=','uc.user_id')
                ->select(['ut.*','u.username as username','u.email','u.phone','cc.name as contest_category_name','uc.rank as team_rank','c.entry_fee','c.prize','c.total_teams','c.id as contest_idd']);
            $query->where([['u.is_sys_user',1],['c.fixture_id',$fixtureId]]);
            if(isset($inning_number)){
                $query->where('ut.inning_number',$inning_number);
            }
            if(isset($entry_fee)){
                $query->where('c.entry_fee',$entry_fee);
            }

        }else{

            $query = User::query()
                    ->from('users','u');
            $query->where('u.is_sys_user',1);
        }

        if (isset($search)) {
            $query->where(function ($query) {
                $search = request('search');
                foreach (['u.name', 'u.email', 'u.phone','u.username'] as $field) {
                    $query->orWhere($field, 'LIKE', '%' . $search . '%');
                }
            });
            //$query->where('u.email', 'LIKE', '%' . $search . '%');
            // foreach (['u.phone', 'u.email'] as $field) {
            //     $query->Where($field, 'LIKE', '%' . $search . '%');
            // }
        }

        $userTeams=$query->paginate($per_page);
        $data=[];
        $data['user_data']=$userTeams;

        return apiResponse(true, null, $data);
    }

    public function userteamlist(){
        $fixtureId = \request('fixtureId');
        $search = \request('search');
        $user_teams_id = \request('user_teams_id');

        $userTeams = UserTeam::query()
                ->from('user_teams')
                ->where([['fixture_id',$fixtureId],['id',$user_teams_id]])
                ->select(['players','captain_id','vice_captain_id','master_player_id'])
                ->first();

        $squads=Squad::query()
                ->where('fixture_id', $fixtureId)
                ->leftJoin('players as p', 'p.id', '=', 'squads.player_id')
                ->leftJoin('fixtures as fi','fi.id','=','squads.fixture_id')
                ->where('role',$search)
                ->select(['role', 'fantasy_player_rating', 'total_points', 'p.image', 'p.name', 'squads.player_id as id', 'player_id','fantasy_player_rating','fi.teama_short_name','fi.teamb_short_name','squads.team_id as steam_id','fi.teama_id as fteama_id'])
                ->orderBy('role')
                ->get();

        $newdata=[];
        $selectedCount=0;
        $totalPlayerAteam=[];
        $captData=array('captain_id'=>$userTeams['captain_id'],'vice_captain_id'=>$userTeams['vice_captain_id'],'master_player_id'=>$userTeams['master_player_id']);
        $total_points=$player_rating=0;
        foreach($squads as $key=>$pValue){
            if($pValue['fteama_id']==$pValue['steam_id']){
                $pValue['team_name']=$pValue['teama_short_name'];
            }else{
                $pValue['team_name']=$pValue['teamb_short_name'];
            }
            $pValue['cap_selected']=$pValue['vic_selected']=$pValue['mast_selected']=0;
            if(in_array($pValue['player_id'],$userTeams['players'])){
                $total_points+=$pValue['total_points'];
                $player_rating+=$pValue['fantasy_player_rating'];
                if($pValue['player_id']==$userTeams['captain_id']){
                    $pValue['cap_selected']=1;
                }
                if($pValue['player_id']==$userTeams['vice_captain_id']){
                    $pValue['vic_selected']=1;
                }
                if($pValue['player_id']==$userTeams['master_player_id']){
                    $pValue['mast_selected']=1;
                }
                $pValue['user_selected']=1;
                $selectedCount+=1;
                if(!empty($totalPlayerAteam[$pValue['team_name']])){
                    $totalPlayerAteam[$pValue['team_name']]+=1;
                }else{
                    $totalPlayerAteam[$pValue['team_name']]=1;
                }

            }else{
                $pValue['user_selected']=0;
            }
            $pValue['isDisabled']=0;
            $newdata[]=$pValue;
        }
        if($search=='WK'){
            $fixture = Fixture::find($fixtureId);
            return apiResponse(true, null, ['selectedCount'=>$selectedCount,'totalPlayerAteam'=>$totalPlayerAteam,'userdata'=>$newdata,'fixtures' => $fixture,'captain_data'=>$captData,'allplayer_rating'=>$player_rating,'total_points' => $total_points]);
        }
        return apiResponse(true, null, ['selectedCount'=>$selectedCount,'totalPlayerAteam'=>$totalPlayerAteam,'userdata'=>$newdata,'allplayer_rating'=>$player_rating,'total_points' => $total_points]);
    }

    public function checkDuplicate($uuid){

        $checkExists = User::find($uuid);
        if(!empty($checkExists)){
            $uuid=uuid();
            $this->checkDuplicate($uuid);
        }
        return $uuid;
    }

    public function systemUserSave(Request $request){
        $request->validate([
            'data' => 'required'
        ]);
        $data=json_decode($request->data,true);

        foreach($data as $key=>$value){
            $uuid=uuid();
            $uuid=$this->checkDuplicate($uuid);

            $user = new User();
            $user->id=$uuid;
            $user->name=$value['name'];
            $user->referral_code=generateRandomString();
            $user->app_version=0;
            $user->username=$value['name'].rand(pow(10, 4), pow(10, 3)-1);
            $user->email=str_replace(" ","",strtolower($value['name'])).rand(pow(10, 3), pow(10, 2)-1)."@system.com";
            $user->is_sys_user=1;
            $user->password=Hash::make('Aktu@123');
            $user->phone=$this->getdummyphone();
            $user->save();
        }
        return apiResponse(true,"Data export Successfully");
    }

    public function editTeam(){
        $user_teams_id = \request('user_teams_id');
        $contest_id = \request('contest_id');
        $capt_data = \request('capt_data');
        $player_data = \request('player_data');
        $players=array_keys(json_decode($player_data,true));
        $capt_ar=json_decode($capt_data,true);
        if(!empty($user_teams_id)){
            $userTeams = UserTeam::find($user_teams_id);

            $userContest = UserContest::where(['contest_id'=>$contest_id,'user_id'=>$userTeams->user_id])->get();
            foreach($userContest as $value){
                $getuserTeams = UserTeam::find($value['user_team_id']);

                if($getuserTeams->captain_id==$capt_ar['captain'] && $getuserTeams->vice_captain_id==$capt_ar['vice_captain'] && $getuserTeams->master_player_id==$capt_ar['master_player'] && !array_diff($getuserTeams->players,$players)){
                    return apiResponse(false,"Duplicate team can't selected in a contest.");
                }
                //$team_id.="/".$value->user_team_id;
            }
            //return apiResponse(false,"Team edit successfully.");

            $fixtureId=$userTeams->fixture_id;
            $userTeams->players=$players;
            $userTeams->captain_id=$capt_ar['captain'];
            $userTeams->vice_captain_id=$capt_ar['vice_captain'];
            $userTeams->master_player_id=$capt_ar['master_player'];
            if($userTeams->save()){
                GetPoint::dispatch($fixtureId,false,1)->delay(now()->addSeconds(2));
                return apiResponse(true,"Team edit successfully.");
            }
        }
    }

    public function getExport(Request $request)
    {
        // request()->validate([
        //     'from_date' => 'required|date|before_or_equal:to_date',
        //     'to_date' => 'required|date|before:tomorrow',
        // ]);

        $from = date($request->from_date);
        $to = date($request->to_date);
        // $user_id = !empty($request->user_id)?$request->user_id:'';
        //return $from.'====='.$to.'======'.$user_id.'========'.$status.'====='.$typecontest;
        if ($request->type !== 'XLS') {
            return Excel::download(new SystemUserExport($from, $to), 'system_user.csv', \Maatwebsite\Excel\Excel::CSV, [
                'Content-Type' => 'text/csv',
            ]);
        } else {
            return Excel::download(new SystemUserExport($from, $to), 'system_user.xlsx');
        }
    }

    private function getdummyphone(){
        $rphone =  '123'.rand(1000000,9999999);
        $isExists = User::where('phone', $rphone)->exists();
        if($isExists){
            $this->getdummyphone();
        }
        return $rphone;
    }



}
