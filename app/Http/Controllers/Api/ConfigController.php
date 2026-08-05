<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Country;


class ConfigController extends Controller
{
    public function index()
    {
        return response()->json(Country::where('is_active', true)->orderBy('name')->get(), 200);
    }
}
