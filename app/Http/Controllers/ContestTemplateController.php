<?php

namespace App\Http\Controllers;

use App\Models\ContestTemplate;
use App\Models\ContestCategory;
use App\Models\Contest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redis;

class ContestTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $perPage = \request('per_page') ?? 15;
        $fixtureId = \request('fixtureId');
        $def_inning = \request('def_inning');
        $category_temp = \request('category_temp');
        if(!empty($fixtureId)){
            if(empty($category_temp)){
                $query = ContestTemplate::query()
                        ->from('contest_templates', 'ct')
                        ->leftJoin('contest_categories as cc', 'cc.id', '=', 'ct.contest_category_id')
                        ->orderBy('cc.id')
                        ->select(['ct.*','cc.name as ct_name'])->get();
                $mainData=[];
                foreach($query as $key=>$qvalue){
                    $qvalue['isSelected']=false;
                    $mainData[]=$qvalue;
                }
                //$query['isSelected']=false;
                $contQuery = Contest::query()
                        ->where([
                        'fixture_id' => $fixtureId,
                        'inning_number' => $def_inning
                    ])->select(['contest_template_id','contest_template_id'])->get();
                return apiResponse(true, null, ['contest_templates' => $mainData,'selected_data'=>$contQuery]);

            }else{
                $contQuery = Contest::query()
                        ->where([
                        'fixture_id' => $fixtureId,
                        'inning_number' => $def_inning
                    ])->select(['contest_template_id','contest_template_id'])->get();
                return apiResponse(true, null, ['selected_data'=>$contQuery]);
            }
        }else{
            $query = ContestTemplate::query();
            $contestTemplates = $query->paginate($perPage);
            return apiResponse(true, null, ['contest_templates' => $contestTemplates]);
        }

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $fixtureId = \request('fixtureId');
        if(!empty($fixtureId)){
            $inningData = \request('inningData');
            $contData = \request('contData');
            $inmessage=$contmessage=false;

            if(isset($inningData['select_inning'])){
                $inmessage=true;
                $inn_num=$inningData['select_inning'];
                if(!empty($contData)){
                    foreach($contData as $ckey=>$cValue){
                        if($cValue){
                            $contmessage=true;
                            $contestTemp = ContestTemplate::find($ckey);

                            $contestData = [
                                'fixture_id' => $fixtureId,
                                'inning_number' => $inn_num,
                                'contest_template_id' => $ckey,
                                'invite_code' => generateRandomString(),
                                'status' => CONTEST_STATUS[1],
                                'contest_category_id' => $contestTemp->contest_category_id,
                                'commission' => $contestTemp->commission,
                                'total_teams' => $contestTemp->total_teams,
                                'entry_fee' => $contestTemp->entry_fee,
                                'max_team' => $contestTemp->max_team,
                                'prize' => $contestTemp->prize,
                                'winner_percentage' => $contestTemp->winner_percentage,
                                'is_confirmed' => $contestTemp->is_confirmed,
                                'prize_breakup' => $contestTemp->prize_breakup,
                                'auto_create_on_full' => $contestTemp->auto_create_on_full,
                                'type' => $contestTemp->type,
                                'discount' => $contestTemp->discount,
                                'bonus' => $contestTemp->bonus,
                                'is_mega_contest' => $contestTemp->is_mega_contest,
                                'is_dynamic' => $contestTemp->is_dynamic,
                                'dynamic_min_team' => $contestTemp->dynamic_min_team,

                            ];
                            //return $contestData;
                            $query = Contest::create($contestData);
                            Redis::set("contestSpace:$query->id", $query->total_teams);
                        }
                    }
                }
            }


            if(!$inmessage){
                return apiResponse(false, 'Please Select a Inning');

            }
            if(!$contmessage){
                return apiResponse(false, 'Please Select a Contest');

            }

            if($contmessage && $inmessage){
                return apiResponse(true, 'Contest added successfully.');
            }
            // foreach($main as $key=>$fValue){
            //     if(is_int($key) && $fValue){
            //         $contestTemp = ContestTemplate::find($key);
            //         return $contestTemp->entry_fee;
            //         //return print_r($query);
            //     }
            // }
            return $inningData;
        }else{
            if ($request->type == 'PRACTICE') {
                $data = $request->validate([
                    //'rank_category_id' => 'required|integer',
                    'contest_category_id' => 'bail|required|exists:contest_categories,id',
                    'name' => 'required|min:1|unique:contest_templates',
                    'description' => 'nullable',
                    'total_teams' => 'required|integer',
                    //'entry_fee' => 'required|integer',
                    'max_team' => 'required|integer|min:1',
                    //'prize' => 'integer',
                    'is_confirmed' => 'required|boolean',
                    'auto_add' => 'required|boolean',
                    'auto_create_on_full' => 'required|boolean',
                    //'commission' => 'required|integer',
                    'type' => 'bail|required',
                    'discount' => 'required_if:type,DISCOUNT|integer',
                    'is_mega_contest' => 'bail|required|boolean',
                    'is_dynamic' => 'bail|integer',
                    'dynamic_min_team' => 'bail|integer|min:0',

                ]);
            }else{
                $data = $request->validate([
                    //'rank_category_id' => 'required|integer',
                    'contest_category_id' => 'bail|required|exists:contest_categories,id',
                    'name' => 'required|min:1|unique:contest_templates',
                    'description' => 'nullable',
                    'total_teams' => 'required|integer',
                    'entry_fee' => 'required|integer',
                    'max_team' => 'required|integer|min:1',
                    'prize' => 'integer',
                    'is_confirmed' => 'required|boolean',
                    'auto_add' => 'required|boolean',
                    'auto_create_on_full' => 'required|boolean',
                    'commission' => 'required|integer',
                    'type' => 'bail|required',
                    'discount' => 'required_if:type,DISCOUNT|integer',
                    'is_mega_contest' => 'bail|required|boolean',
                    'prize_breakup' => 'bail|required|array',
                    'prize_breakup.*.from' => 'required|gt:0',
                    'prize_breakup.*.to' => 'required|gt:0|lte:total_teams',
                    'prize_breakup.*.prize' => 'required|gt:0',
                    'is_dynamic' => 'bail|integer',
                    'dynamic_min_team' => 'bail|integer|min:0',

                ]);
            }
            $entryFee = $request->entry_fee;
                $totalTeams = $request->total_teams;
                $total = $entryFee * $totalTeams;
                $prize = $request->prize;
            if ($request->type !== 'FREE') {
                if ($prize > $total) {
                    return apiResponse(false, 'Invalid prize value.'.$prize.'---'.$total);
                }
            }

            $rankPrize = 0;
            $lastWinner = 0;
            if ($request->type !== 'PRACTICE') {
                foreach ($request->prize_breakup as $breakup) {
                    if ($breakup['from'] > $breakup['to']) {
                        return apiResponse(false, 'The to field must be greater than or equal to from field.');
                    }

                    $rankPrize += (($breakup['to'] - $breakup['from']) + 1) * $breakup['prize'];

                    if ($rankPrize > $prize) {
                        return apiResponse(false, 'Invalid prize value.');
                    }
                    $lastWinner = $breakup['to'];
                }
                $data['winner_percentage'] = (100 * $lastWinner) / $totalTeams;
            }else{
                $data['winner_percentage'] = 0;
                $data['prize_breakup'] = [];
                $data['entry_fee'] = 0;
                $data['prize'] = 0;
                $data['commission'] = 0;
            }

            if($data['is_dynamic']==0){
                $data['dynamic_min_team']=0;
            }
            ContestTemplate::query()->create($data);
            return apiResponse(true, 'Contest template added.');
        }

    }

    /**
     * Display the specified resource.
     *
     * @param ContestTemplate $contestTemplate
     * @return Response
     */
    public function show(ContestTemplate $contestTemplate)
    {
        return apiResponse(true, null, ['contest_template' => $contestTemplate]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param ContestTemplate $contestTemplate
     * @return Response
     */
    public function update(Request $request, ContestTemplate $contestTemplate)
    {
        if ($request->type == 'PRACTICE') {
            $data = $request->validate([
                //'rank_category_id' => 'required|integer',
                'contest_category_id' => 'bail|required|exists:contest_categories,id',
                'name' => 'bail|required|min:1|unique:contest_templates,id,' . $contestTemplate->id,
                'description' => 'nullable',
                'total_teams' => 'bail|required|integer',
                //'entry_fee' => 'bail|required|integer',
                'max_team' => 'bail|required|integer|min:1',
                //'prize' => 'bail|integer',
                'is_confirmed' => 'bail|required|boolean',
                'auto_add' => 'bail|required|boolean',
                'auto_create_on_full' => 'bail|required|boolean',
                //'commission' => 'required|integer',
                'type' => 'bail|required',
                'discount' => 'required_if:type,DISCOUNT|integer',
                'is_mega_contest' => 'bail|required|boolean',
                'is_dynamic' => 'bail|integer',
                'dynamic_min_team' => 'bail|integer|min:0',
            ]);
        }else{
            $data = $request->validate([
                //'rank_category_id' => 'required|integer',
                'contest_category_id' => 'bail|required|exists:contest_categories,id',
                'name' => 'bail|required|min:1|unique:contest_templates,name,' . $contestTemplate->id,
                'description' => 'nullable',
                'total_teams' => 'bail|required|integer',
                'entry_fee' => 'bail|required|integer',
                'max_team' => 'bail|required|integer|min:1',
                'prize' => 'bail|integer',
                'is_confirmed' => 'bail|required|boolean',
                'auto_add' => 'bail|required|boolean',
                'auto_create_on_full' => 'bail|required|boolean',
                'commission' => 'required|integer',
                'type' => 'bail|required',
                'discount' => 'required_if:type,DISCOUNT|integer',
                'is_mega_contest' => 'bail|required|boolean',
                'prize_breakup' => 'bail|required|array',
                'prize_breakup.*.from' => 'required|gt:0',
                'prize_breakup.*.to' => 'required|gt:0|lte:total_teams',
                'prize_breakup.*.prize' => 'required|gt:0',
                'is_dynamic' => 'bail|integer',
                'dynamic_min_team' => 'bail|integer|min:0',
            ]);
        }

        $entryFee = $request->entry_fee;
        $totalTeams = $request->total_teams;
        $total = $entryFee * $totalTeams;
        $prize = $request->prize;
        if ($request->type !== 'FREE') {
            if ($prize > $total) {
                return apiResponse(false, 'Invalid prize value.');
            }
        }

        $rankPrize = 0;
        $lastWinner = 0;
        if ($request->type !== 'PRACTICE') {
            foreach ($request->prize_breakup as $breakup) {
                if ($breakup['from'] > $breakup['to']) {
                    return apiResponse(false, 'The to field must be greater than or equal to from field.');
                }

                $rankPrize += (($breakup['to'] - $breakup['from']) + 1) * $breakup['prize'];

                if ($rankPrize > $prize) {
                    return apiResponse(false, 'Invalid prize value.');
                }
                $lastWinner = $breakup['to'];
            }
            $data['winner_percentage'] = (100 * $lastWinner) / $totalTeams;
            $data['bonus'] = $request->bonus;

        }else{
            $data['winner_percentage'] = 0;
            $data['prize_breakup'] = [];
            $data['commission'] = 0;
            $data['entry_fee'] = 0;
            $data['bonus'] = $request->bonus;
            $data['prize'] = 0;
        }
        if($data['is_dynamic']==0){
            $data['dynamic_min_team']=0;
        }

        $contestTemplate->update($data);

        return apiResponse(true, 'Contest template updated.'.$request->bonus);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ContestTemplate $contestTemplate
     * @return Response
     */
    public function destroy(ContestTemplate $contestTemplate)
    {
        $contestTemplate->delete();

        return apiResponse(true, 'Contest template removed.');
    }
}
