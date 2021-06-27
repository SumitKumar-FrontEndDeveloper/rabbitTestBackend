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
            $sData=$url->getShortUrl($request->url, $request->customUrl, $request->expiryDate);
            return $this->sendJsonResponse($sData);exit;
        }
     }

     public function getLongUrl(Request $request){
        if(!$request){
            $sData['status_code']=201;
            $sData['msg']='Please send short code';
            return $this->sendJsonResponse($sData);exit;
        } else {
            $url = new Url();
            $sData=$url->getLongUrl($request->short_code);
            return $this->sendJsonResponse($sData);exit;
        }
     }

     public function getUrlList(Request $request){
        if(!$request){
            $sData['status_code']=201;
            $sData['msg']='Please send short code';
            return $this->sendJsonResponse($sData);exit;
        } else {
            $url = new Url();
            $sData=$url->getAllUrl();
            return $this->sendJsonResponse($sData);exit;
        }
     }

     public function deleteUrl(Request $request){
        if(!$request){
            $sData['status_code']=201;
            $sData['msg']='Please sent short code';
            return $this->sendJsonResponse($sData);exit;
        } else {
            $url = new Url();
            $sData=$url->deleteShortUrl($request->id);
            return $this->sendJsonResponse($sData);exit;
        }
     }

}
