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
    protected $pdo;

    public function urlToShortCode($url){

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
            $shortCode = $this->createShortCode($url);
        }
        return $shortCode;
    }

    protected function validateUrlFormat($url){
        return filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED);
    }

    public function getShortUrl($longURL) {
        try{
            // Get short code of the URL
            $shortCode = $this->urlToShortCode($longURL);
            $shortURL_Prefix = 'https://localhost/'; // with URL rewrite
            $shortURL = $shortURL_Prefix.$shortCode;
            $data['status_code'] = 200;
            $data['short_url'] =  $shortURL;
            return $data;
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

    protected function createShortCode($url){
        $shortCode = $this->generateRandomString(self::$codeLength);
        $id = $this->insertUrlInDB($url, $shortCode);
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

    protected function insertUrlInDB($url, $code){
        $timestamp = date("Y-m-d H:i:s");
        $values = array('long_url' => $url,'short_code' => $code,'created'=> $timestamp,'hits'=>0);
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

        if($increment == true){
            $this->incrementCounter($urlRow["id"]);
        }

        return $urlRow["long_url"];
    }

    protected function validateShortCode($code){
        $rawChars = str_replace('|', '', self::$chars);
        return preg_match("|[".$rawChars."]+|", $code);
    }

    protected function getUrlFromDB($code){
        $query = "SELECT id, long_url FROM ".self::$table." WHERE short_code = :short_code LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $params=array(
            "short_code" => $code
        );
        $stmt->execute($params);

        $result = $stmt->fetch();
        return (empty($result)) ? false : $result;
    }

    protected function incrementCounter($id){
        $query = "UPDATE ".self::$table." SET hits = hits + 1 WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $params = array(
            "id" => $id
        );
        $stmt->execute($params);
    }

}
