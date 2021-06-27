<?php

namespace App\model;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Model\Url;


class Url extends Model
{
    protected static $chars = "abcdfghjkmnpqrstvwxyz|ABCDFGHJKLMNPQRSTVWXYZ|0123456789";
    public  $table = "urls";
    protected static $checkUrlExists = false;
    protected static $codeLength = 7;

    public function urlToShortCode($url, $expiryDate){

        if(empty($url)){
            throw new Exception("No URL was supplied.");
        }
        if($this->validateUrlFormat($url) == false){
            throw new Exception("URL does not have a valid format.");
        }

        if(self::$checkUrlExists){
            if (!$this->verifyUrlExists($url)){
                throw new Exception("URL does not appear to exist.",201);
            }
        }
        $shortCode = $this->urlExistsInDB($url);
        if($shortCode == false){
            $shortCode = $this->createShortCode($url, $expiryDate);
        }
        return $shortCode;
    }

    protected function validateUrlFormat($url){
        return filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED);
    }

    public function getShortUrl($longURL, $customUrl, $expiryDate) {
        try{
            // Get short code of the URL
            $shortCode = $this->urlToShortCode($longURL, $expiryDate);
            $shortURL_Prefix = 'http://www.abc.com';
            if($customUrl){
                $shortURL_Prefix = $customUrl;
            }
           //'https://localhost/'; // with URL rewrite
            $shortURL = $shortURL_Prefix."/".$shortCode;
            $data['status_code'] = 200;
            $data['short_url'] =  $shortURL;
            $data['short_code'] =  $shortCode;
            return $data;
        }catch(Exception $e){
            $data['status_code'] = 400;
            $data['shortURL'] = $e->getMessage();
            return $data;
        }
    }

    public function getLongUrl($code) {
        try{
            $urlRow = $this->getUrlFromDB($code);
            $data['status_code'] = 200;
            $data['long_url'] =  $urlRow->long_url;
            $updateHits['hits']=$urlRow->hits + 1;
            DB::table('urls')->where('id', $urlRow->id)->update($updateHits);
            return $data;
        }catch(Exception $e){
            $data['status_code'] = 400;
            $data['shortURL'] = $e->getMessage();
            return $data;
        }
    }

    public function deleteShortUrl($id) {
        try{
            $deleteUrl['status']=0;
            $delete=DB::table('urls')->where('id', $id)->update($deleteUrl);
            if($delete){
                $data['status_code'] = 200;
                return $data;
            } else {
                $data['status_code'] = 400;
                $data['msg'] = 'Something went wrong.';
                return $data;
            }


        }catch(Exception $e){
            $data['status_code'] = 400;
            $data['shortURL'] = $e->getMessage();
            return $data;
        }
    }

    protected function verifyUrlExists($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch,  CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return (!empty($response) && $response != 404);
    }

    protected function urlExistsInDB($url){
        $result=DB::table('urls')->select('short_code')->where('long_url', $url)->first();
        return (empty($result)) ? false : $result->short_code;
    }

    protected function createShortCode($url, $expiryDate){
        $shortCode = $this->generateRandomString(self::$codeLength);
        $id = $this->insertUrlInDB($url, $shortCode, $expiryDate);
        return $shortCode;
    }

    protected function generateRandomString($length = 6){
        $sets = explode('|', self::$chars);
        $all = '';
        $randString = '';
        foreach($sets as $set){
            $randString .= $set[array_rand(str_split($set))];
            $all .= $set;
        }
        $all = str_split($all);
        for($i = 0; $i < $length - count($sets); $i++){
            $randString .= $all[array_rand($all)];
        }
        $randString = str_shuffle($randString);
        return $randString;
    }

    protected function insertUrlInDB($url, $code, $expiryDate){
        $timestamp = date("Y-m-d H:i:s");
        $expiry = date("Y-m-d H:i:s", strtotime($expiryDate));
        $values = array('long_url' => $url,'short_code' => $code,'expiry_date' => $expiry, 'created'=> $timestamp,'hits'=>0);
        $id=DB::table('urls')->insertGetId($values);
        return $id;
    }

    public function shortCodeToUrl($code, $increment = true){
        if(empty($code)) {
            throw new Exception("No short code was supplied.");
        }

        if($this->validateShortCode($code) == false){
            throw new Exception("Short code does not have a valid format.");
        }

        $urlRow = $this->getUrlFromDB($code);
        if(empty($urlRow)){
            throw new Exception("Short code does not appear to exist.");
        }
        return $urlRow["long_url"];
    }


    protected function validateShortCode($code){
        $rawChars = str_replace('|', '', self::$chars);
        return preg_match("|[".$rawChars."]+|", $code);
    }

    protected function getUrlFromDB($code){
        $result=DB::table('urls')->select('long_url','hits','id')->where('short_code', $code)->first();
        return (empty($result)) ? false : $result;
    }


    public function getAllUrl() {
        try{
            $result=DB::table('urls')->where('status',1);
            $urlData=$result->paginate(5);
            $bdata['total']=$urlData->total();
            $data['status_code'] = 200;
            $data['urlList'] =  $urlData;
            return $data;
        }catch(Exception $e){
            $data['status_code'] = 400;
            $data['msg'] = $e->getMessage();
            return $data;
        }
    }
}
