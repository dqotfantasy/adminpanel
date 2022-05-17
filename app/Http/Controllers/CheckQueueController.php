<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\FailedJob;
use App\Jobs\GetLineup;
use App\Jobs\GetScore;
use App\Jobs\CallContestCancel;
use App\Jobs\CalculateDynamicPrizeBreakup;
use Illuminate\Http\Response;
use App\Jobs\GetPoint;
use Illuminate\Support\Facades\Redis;
use App\Models\Fixture;
use Carbon\Carbon;


class CheckQueueController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index($fixtureId,Request $request)
    {
        $perPage = \request('per_page') ?? 15;
        //$fixtureId = \request('fixture_id');
        $Jobstatus = \request('status');
        $flag = \request('flag');
        $search = \request('search');
        $queueName = \request('queue_name');
        $auto_set = \request('auto_set');
        $time_duration = \request('time_duration')??1;
        $data=[];
        if(!empty($queueName)){
            if ($queueName == 'lineup') {
                GetLineup::dispatch($fixtureId,$auto_set,$time_duration)->delay(now()->addSeconds(2));
            }
            if ($queueName == 'point') {
                GetPoint::dispatch($fixtureId,$auto_set,$time_duration)->delay(now()->addSeconds(2));
            }
            if ($queueName == 'score') {
                GetScore::dispatch($fixtureId,$auto_set,$time_duration)->delay(now()->addSeconds(2));
            }
            if ($queueName == 'contest_cancel') {
                CallContestCancel::dispatch($fixtureId,$auto_set,$time_duration)->delay(now()->addSeconds(2));
            }
            if ($queueName == 'dynamic_price') {
                CalculateDynamicPrizeBreakup::dispatch($fixtureId,$auto_set,$time_duration)->delay(now()->addSeconds(2));

            }
            return apiResponse(true, 'Cron added successfully.');
        }else{
            if($Jobstatus=='failed'){
                $query=FailedJob::query();
                $query->where('payload','LIKE','%'.$fixtureId.'%');
                $query->orderBy('failed_at');
                $json   = Redis::get("scorecard:{$fixtureId}");
                $data['redisData']=$json;

            }elseif($Jobstatus=='remove' && $flag=='failjob'){
                $query=FailedJob::find($fixtureId);
                $query->delete();
            }elseif($Jobstatus=='remove' && $flag=='job'){
                $query=Job::find($fixtureId);
                $query->delete();
            }else{
                $query=Job::query();
                $query->where('payload','LIKE','%'.$fixtureId.'%');
                $query->orderBy('available_at');
                $fixture = Fixture::query()
                    ->where('id', $fixtureId)
                    ->first();
                $data['fixture_data']=$fixture;
            }
            if (!empty($search)) {
                $query->where('queue', 'LIKE', '%' . $search . '%');
            }

            $jobData = $query->paginate($perPage);
            if(!empty($jobData)){
                $data['jobData']=$jobData;
                $data['status']=true;
            }

            return apiResponse(true, null, $data);
        }
    }

    public function queyeFind(){
        $perPage = \request('per_page') ?? 15;
        $jobid = \request('jobid');
        $failjobid = \request('failjobid');
        $Jobstatus = \request('status');
        $search = \request('search');
        $queueName = \request('queue_name');
        $auto_set = \request('auto_set');
        $message='';

        if($Jobstatus=='failed'){
            $query=FailedJob::query();
            //$query->where('payload','LIKE','%'.$fixtureId.'%');
            $query->orderBy('failed_at');
        }elseif($Jobstatus=='jobremove'){
            $query=Job::find($jobid);
            $query->delete();
            $message="Cron was removed successfully remove.";
        }elseif($Jobstatus=='failremove'){
            $query=FailedJob::find($failjobid);
            $query->delete();
            $message="Cron was removed successfully remove.";
        }else{
            $query=Job::query();
            $query->whereIn('queue',['default','lineup','point']);
            //$query->orderBy('available_at');
            // $fixture = Fixture::query()
            //     ->where('id', $fixtureId)
            //     ->first();
            // $data['fixture_data']=$fixture;
        }
        if (!empty($search)) {
            $query->where('queue', 'LIKE', '%' . $search . '%');
        }
        $fixture = Fixture::query()
                    ->select(['id','teama','teamb', 'name','starting_at'])
                    ->get(['id', 'name']);
        $data['fixture_data']=$fixture;

        $jobData = $query->paginate($perPage);
        if(!empty($jobData)){
            $data['jobData']=$jobData;
            $data['status']=true;
        }

        return apiResponse(true, $message, $data);
    }
}
