<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController;

use Illuminate\Http\Request;
use App\Model\Url;
class UrlController extends BaseController
{
    //
    public function changeUrl(Request $request){
        if(!$request){
            $sData['status_code']=201;
            $sData['msg']='Please Enter short url';
            return $this->sendJsonResponse($sData);exit;
        } else {
            $url = new Url();
            $sData=$url->getShortUrl($request->url);
            return $this->sendJsonResponse($sData);exit;
        }
     }

}
