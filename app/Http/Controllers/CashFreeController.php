<?php

namespace App\Http\Controllers;

use FuncInfo;
use Illuminate\Http\Request;
use App\Models\User;

class CashFreeController extends Controller
{
    public function index(){
        include_once("cfpayout.inc.php");

    }
}
