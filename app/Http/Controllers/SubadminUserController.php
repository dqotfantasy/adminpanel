<?php

namespace App\Http\Controllers;
use App\Models\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class SubadminUserController extends Controller
{
    public function index(){
        $search = \request('search');
        $deleteid = \request('deleteid');
        $per_page = \request('per_page');
        $query = Admin::query()
                ->select(['id','name', 'email','pin','role_id']);
        $message='';
        //$query->where('role_id',2);
        if($deleteid){
            $blog = Admin::find($deleteid);
            if($blog->delete()){
                $message="Remove record successfully.";
            }
        }

        $userData=$query->paginate($per_page);
        $data=[];
        $data['userdata']=$userData;
        $data['role_id']=roleId();
        return apiResponse(true, $message, $data);
    }

    public function store(Request $request){
        $request->validate([
            'email' => 'required|unique:users|email',
            'password' => 'required',
            'pin' => 'required'
        ]);

        $name = \request('name');
        $email = \request('email');
        $pin = \request('pin');
        $password = \request('password');

        $admin_data = new Admin;
        $admin_data->name=$name;
        $admin_data->email=$email;
        $admin_data->role_id=2;
        $admin_data->password=Hash::make($password);;
        $admin_data->pin=$pin;
        if($admin_data->save()){
            return apiResponse(true, 'SubAdmin added Successfully');
        }else{
            return apiResponse(false, 'SubAdmin not added');
        }
        //return $name.'------'.$email.'-------'.$pin;
    }

    public function destroy($id)
    {
        return "finee".$id;
    }
}
