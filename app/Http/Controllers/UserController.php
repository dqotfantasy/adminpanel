<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\State;
use App\Exports\UserExport;
use App\Exports\JoinUserExport;
use App\Models\ContestCategory;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use App\Models\Fixture;
use App\Models\PrivateContest;


class UserController extends Controller
{
    public function index()
    {
        $query = User::query();
        ////$query->with(['bank', 'pan']);
        $search = \request('search');
        $find = \request('find');
        $verified = \request('verified');
        $perPage = \request('per_page') ?? 15;
        if (isset($search)) {
            $query->where('name', 'LIKE', '%' . $search . '%');
            foreach (['username', 'email', 'phone'] as $field) {
                $query->orWhere($field, 'LIKE', '%' . $search . '%');
            }
        }
        if (isset($find) && $find == 'VERIFIED') {
            $query->where([['phone_verified', 1],['document_verified', 1],['email_verified', 1]]);
        }

        // if (isset($find) && $find == 'UNVERIFIED') {
        //     $query->where('document_verified',0);
        // }

        if (isset($find) && $find == 'UNVERIFIED') {
            $query->join('bank_accounts as b','b.user_id','=','users.id')
                    ->join('pan_cards as pc','pc.user_id','=','users.id');
             $query->orWhere('b.status','PENDING')->orWhere('pc.status','PENDING');
        }else{
            $query->with(['bank', 'pan']);
        }

        if (isset($find) && $find == 'newUsers') {
            $query->whereDate('created_at', '=', now());
        }
        $query->orderBy('users.created_at','DESC');
        $query->where('role', 'user')->select('users.*');

        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->makeVisible(['is_locked']);
        $types = [];
        foreach (NOTIFICATION_TYPE as $item) {
            $types[] = ['id' => $item, 'name' => $item];
        }

        $data = [
            'users' => $paginator->items(),
            'type'  => $types,
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
        ];

        return apiResponse(true, null, $data);
    }

    /**
     * Display the specified resource.
     *
     * @param User $user
     * @return Response
     */
    public function show(User $user)
    {
        $user->makeVisible(['is_locked']);
        $query=User::where('referral_id',$user->id)->get();
        $refrel_total=$query->count();
        $states = State::where('is_active', true)->orderBy('name')->get();
        return apiResponse(true, null, ["user" => $user, 'role_id' => roleId(), 'states' => $states,'refrel_total'=>$refrel_total]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param User $user
     * @return Response
     */
    public function update(Request $request, User $user)
    {
        if ($request->is_sys_user == true) {
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|min:2',
                'username' => 'required',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'referral_code' => 'required',
                'phone' => 'nullable|numeric|digits:10',
                'date_of_birth' => 'nullable',
                'gender' => 'nullable|in:m,f',
                'city' => 'nullable',
                'state_id' => 'nullable|exists:states,id',
                'address' => 'nullable',
                'document_verified' => 'boolean',
                'phone_verified' => 'boolean',
                'email_verified' => 'boolean',
                'is_locked' => 'boolean',
                'is_sys_user' => 'boolean',
                'promoter_type' => 'bail|integer'
            ]);
        }else{
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|min:2',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'referral_code' => 'required',
                'phone' => 'nullable|numeric|digits:10',
                'date_of_birth' => 'nullable',
                'gender' => 'nullable|in:m,f',
                'city' => 'nullable',
                'state_id' => 'nullable|exists:states,id',
                'address' => 'nullable',
                'document_verified' => 'boolean',
                'phone_verified' => 'boolean',
                'email_verified' => 'boolean',
                'is_locked' => 'boolean',
                'is_sys_user' => 'boolean',
                'promoter_type' => 'bail|integer'
            ]);
        }
        if ($validator->fails()) {
            return apiResponse(false, $validator->errors()->first());
        }

        if (isset($user->bank) && isset($user->pan) && $user->bank->status === 'VERIFIED' && $user->pan->status === 'VERIFIED' && $request->email_verified == 1 && $request->phone_verified == 1) {
            $user->document_verified = 1;
        } else {
            $user->document_verified = 0;
        }
        $arrayConvert=$validator->validated();
        if($user->update($validator->validated())){
            // $token=Redis::get("userToken:{$user->id}");
            // $arrayConvert['token']=$token;
            // Redis::set("auth:{$token}", json_encode($arrayConvert));
        }

        return apiResponse(true, 'User details updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @return Response
     */
    public function destroy(User $user)
    {
        //
    }

    public function bankAccounts()
    {
        $bankAccounts = auth()->user()->bankAccounts;

        return apiResponse(true, null, ['bank_accounts' => $bankAccounts]);
    }

    public function joinedMatch()
    {
        $search = \request('search');
        $user_type = \request('user_type');
        $contes_categorie = \request('contes_categorie');
        $fixtureId = \request('fixtureId');
        $perPage = \request('per_page') ?? 15;

        $query = Contest::query()
                    ->from('contests','c')
                    ->join('contest_categories as cc','cc.id','=','c.contest_category_id')
                    ->Join('user_contests as uc','uc.contest_id','=','c.id')
                    ->Join('user_teams as ut','ut.id','=','uc.user_team_id')
                    ->Join('users','users.id','=','uc.user_id')
                    ->select(['users.username','users.is_sys_user','uc.rank','ut.*','cc.name as contest_category_name','c.entry_fee','c.prize','c.total_teams']);
        $query->where('c.fixture_id', $fixtureId);

        if (isset($search)) {
            $query->where('users.username', 'LIKE', '%' . $search . '%');
        }
        if (isset($user_type)) {
            $query->where('users.is_sys_user', $user_type);
        }

        if(isset($contes_categorie)){
            $query->where('cc.id', $contes_categorie);
        }
        $query->orderBy('uc.rank');
        $paginator = $query->paginate($perPage);

        $contestCategories = ContestCategory::all(['id', 'name']);

        //return $paginator;

        $data = [
            'user_data' => $paginator->items(),
            'contestCategories' =>$contestCategories,
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
        // request()->validate([
        //     'from_date' => 'required|date|before_or_equal:to_date',
        //     'to_date' => 'required|date|before:tomorrow',
        // ]);

        $user_type = !empty($request->user_type)?$request->user_type:'';
        $document_verified = !empty($request->document_verified)?$request->document_verified:'';
        //return $from.'====='.$to.'======'.$user_id.'========'.$status.'====='.$typecontest;
        if ($request->type !== 'XLS') {
            return Excel::download(new UserExport($user_type, $document_verified), 'user_data.csv', \Maatwebsite\Excel\Excel::CSV, [
                'Content-Type' => 'text/csv',
            ]);
        } else {
            return Excel::download(new UserExport($user_type, $document_verified), 'user_data.xlsx');
        }
    }

    public function getExportJoinUser(Request $request)
    {
        // request()->validate([
        //     'from_date' => 'required|date|before_or_equal:to_date',
        //     'to_date' => 'required|date|before:tomorrow',
        // ]);

        // $from = date($request->from_date);
        // $to = date($request->to_date);
        $contes_categorie = !empty($request->contes_categorie)?$request->contes_categorie:'';
        $user_type = !empty($request->user_type)?$request->user_type:'';
        $fixture_id = !empty($request->fixture_id)?$request->fixture_id:'';
        //return $from.'====='.$to.'======'.$user_id.'========'.$status.'====='.$typecontest;
        if ($request->type !== 'XLS') {
            return Excel::download(new JoinUserExport($contes_categorie, $user_type,$fixture_id), 'user_join_fixture.csv', \Maatwebsite\Excel\Excel::CSV, [
                'Content-Type' => 'text/csv',
            ]);
        } else {
            return Excel::download(new JoinUserExport($contes_categorie, $user_type,$fixture_id), 'user_join_fixture.xlsx');
        }
    }
}
