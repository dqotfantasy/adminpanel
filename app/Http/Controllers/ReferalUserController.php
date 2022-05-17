<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use phpDocumentor\Reflection\Types\Null_;
use Illuminate\Support\Facades\DB;
use App\Exports\ReferalExport;
use Maatwebsite\Excel\Facades\Excel;


class ReferalUserController extends Controller
{
    public function index(){
        $search = \request('search');
        $perPage = \request('per_page') ?? 15;

        //$query->where('u.referral_id','u.id');
        if (isset($search)) {
            $getuserId=User::where('username', 'LIKE', '%' . $search . '%')->get('id');

            $query=User::query()
            ->from('users','u')
            ->select(['u.*',DB::raw('(SELECT username FROM users as us WHERE us.id=u.referral_id) as ref_username')]);
            $query->whereNotNull('u.referral_id');
            $query->whereIn('u.referral_id',$getuserId);
            // $query->where('u.username', 'LIKE', '%' . $search . '%');
            // foreach (['u.username', 'u.email'] as $field) {
            //     $query->orWhere($field, 'LIKE', '%' . $search . '%');
            // }
        }else{
            $query=User::query()
            ->from('users','u')
            ->select(['u.*',DB::raw('(SELECT username FROM users as us WHERE us.id=u.referral_id) as ref_username')]);
            $query->whereNotNull('u.referral_id');
        }
        $paginator = $query->paginate($perPage);
        $data = [
            'user_data' => $paginator->items(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl()
        ];
        return apiResponse(true, null, $data);
    }

    public function getExport(Request $request)
    {

        $search = !empty($request->search)?$request->search:'';
        if ($request->type !== 'XLS') {
            return Excel::download(new ReferalExport($search), 'user_data.csv', \Maatwebsite\Excel\Excel::CSV, [
                'Content-Type' => 'text/csv',
            ]);
        } else {
            return Excel::download(new ReferalExport($search), 'user_data.xlsx');
        }
    }
}
