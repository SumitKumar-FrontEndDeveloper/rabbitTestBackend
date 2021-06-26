<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    //
    public function sendJsonResponse($response) {

        return \Illuminate\Support\Facades\Response::json($response, $response['status_code'])->header('Content-Type', "application/json");
    }
}
