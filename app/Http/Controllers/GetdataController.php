<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Fixture;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class GetdataController extends Controller
{
    public function show(){
        //echo "aayaa";die;
        $fixture = Fixture::query()->get();
        echo "<pre>";print_r($fixture);echo "</pre>";die;
    }
}
