<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EarningManagerExport;



class EarningManagerController extends Controller
{
    public function index(){
        $perPage = \request('per_page') ?? 15;
        $fromDate = \request('from_date');
        $toDate = \request('to_date');
        $status = \request('status');
        $user_type = \request('user_type');
        $competition_id = \request('competition_id');

        $query = Fixture::query();
        //$query->withCount(['contests', 'user_teams']);
        // if(!$user_type=='all_user'){
        //     $query->select(['payment_data_all as payment_data']);
        // }
        $search = \request('search');

        //$competition_name = \request('competitionStatus');
        $perPage = \request('per_page') ?? 15;
        if (isset($search)) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        }

        if (isset($status)) {

            $query->whereIn('status', explode(",", $status));
        }

        // if (isset($competition_name)) {

        //     $query->where('competition_name',$competition_name);
        // }
        if(!empty($competition_id)){
            $query->where('competition_id',$competition_id);
        }
        // return $competition_name."ttt";die;
        // return apiResponse(true, null, $competition_name);


        if (isset($fromDate) && isset($toDate)) {
            \request()->validate([
                'from_date' => 'before_or_equal:to_date'
            ]);

            $query->whereDate('starting_at', '>=', $fromDate);
            $query->whereDate('starting_at', '<=', $toDate);
        } elseif (isset($fromDate)) {
            \request()->validate([
                'from_date' => 'date'
            ]);

            $query->whereDate('starting_at', '>=', $fromDate);
        } elseif (isset($toDate)) {
            \request()->validate([
                'to_date' => 'date'
            ]);

            $query->whereDate('starting_at', '<=', $toDate);
        }
        $query->orderBy('last_squad_update','desc');
        $fixtures = $query->paginate($perPage);
        $data['fixtures'] = $fixtures;

        $statuses = [];
        foreach (FIXTURE_STATUS as $item) {
            $statuses[] = ['id' => $item, 'name' => $item];
        }
        $data['statuses'] = $statuses;

        $types = [];
        foreach (CRICKET_TYPES as $item) {
            $types[] = ['id' => $item, 'name' => $item];
        }
        $data['types'] = $types;

        return apiResponse(true, null, $data);
    }

    public function getExport(Request $request)
    {
        $competition_id = date($request->competition_id);
        $user_type = date($request->user_type);
        $from = date($request->from_date);
        $to = date($request->to_date);

        if ($request->type !== 'XLS') {
            return Excel::download(new EarningManagerExport($from, $to,$competition_id, $user_type), 'earning-details.csv', \Maatwebsite\Excel\Excel::CSV, [
                'Content-Type' => 'text/csv',
            ]);
        } else {
            return Excel::download(new EarningManagerExport($from, $to,$competition_id, $user_type), 'earning-details.xlsx');
        }
    }
}
