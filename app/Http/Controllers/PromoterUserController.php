<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\ReferalDepositDetails;
use Illuminate\Support\Facades\DB;


class PromoterUserController extends Controller
{
    public function index(){
        $search = \request('search');
        $perPage = \request('per_page') ?? 15;
        $query=Payment::query()
                ->from('payments','p')
                ->join('users as u','u.id','=','p.user_id')
                //->select(['p.extra',DB::raw('(SELECT extra FROM payments WHERE extra = "") as ext_user_id')])->get();
                ->select(['u.referral_code','u.username','u.email','u.phone','p.extra','p.created_at','p.extra as promoter_data']);

        if (isset($search)) {
                //$query->orWhere('u.referral_code', 'LIKE', '%' . $search . '%');
                //$query->orWhere('p.extra', 'LIKE', '%' . $search . '%');
                $query->where(function ($query) {
                    $search = request('search');
                    foreach(['p.extra','u.username','u.email','u.phone','u.referral_code'] as $field){
                        $query->orWhere($field, 'LIKE', '%' . $search . '%');
                    }
                });
        }
        $query->where([['p.extra','!=',''],['p.type','=','PROMOTER ADD']]);


        $paginator = $query->paginate($perPage);

            $data = [
                'promoter_data' => $paginator->items(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl()
            ];
            return apiResponse(true, null, $data);
    }

    public function promoterInfo(){
        $search = \request('search');
        $perPage = \request('per_page') ?? 15;
        DB::enableQueryLog();
        $query=ReferalDepositDetails::query();
        if (isset($search)) {
            //return $query->find();
            $query->with(['user'=>function($query){
                    $query->where(function ($query) {
                        $search = request('search');
                        foreach(['referral_code','email'] as $field){
                            $query->orWhere($field, 'LIKE', '%' . $search . '%');
                        }
                    });
                },
                // 'earnUser'=>function($query){
                //     $query->where(function ($query) {
                //         $search = request('search');
                //         foreach(['referral_code','email'] as $field){
                //             $query->orWhere($field, 'LIKE', '%' . $search . '%');
                //         }
                //     });
                // },
                'payment'=>function($query){
                    $search = request('search');
                    foreach(['transaction_id'] as $field){
                        $query->where($field, 'LIKE', '%' . $search . '%');
                    }
                }
            ]);

        }else{
            $query->with(['user','earnUser','payment']);
        }



        $paginator = $query->paginate($perPage);

            $data = [
                'promoter_data' => $paginator->items(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl()
            ];
            return apiResponse(true, null, $data);
    }
}
