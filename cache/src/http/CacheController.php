<?php

namespace Increment\Common\Cache\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CacheController extends APIController
{

    public function retrieveCondition($result, $offset){
        if($result && sizeof($result) > 0 && $offset >= sizeof($result)){
            return true;
        }
        return false;
    }

    public function insert($key, $value){
        $previousData = Cache::get($key);

        if($previousData && sizeof($previousData) > 0){
            $previousData[] = $value;
            $res = Cache::put($key, $value);
        }else{
            $res = Cache::put($key, $value);
        }
        return $res;
    }

    public function insertToLast($key, $value){
        $data = [];
        $data = Cache::get($key);
        $data[] = $value;
        return $this->insert($key, $data);
    }

    public function insertToFirst($key, $value){
        $data = [];
        $data = Cache::get($key);
        if($data && sizeof($data) > 0){
            array_unshift($data, $value);
        }else{
            $data[] = $value;
        }
        return $this->insert($key, $data);
    }

    public function insertArray($keys, $value){
        $array = array();
        array_push($array, $value);
        $res = Cache::get($keys);
        if($res !== null){
            if(is_array($res)){
                $i=0;
                foreach ($res as $key) {
                    array_push($array, $key);
                    $i++;
                }
            }
        }
        $res = Cache::put($keys, $array);
        return $res;
    }

    public function retrieve($key, $offset = null, $limit = null){
        $data = Cache::get($key);
        if($offset && $limit && $data && sizeof($data) > 0){
            $offset = intval($offset);
            $limit = intval($limit);
            $size = sizeof($data);

            if($limit >= $size){
                return $data;
            }else{
                if($offset > $size){
                    return [];
                }else{
                    return array_slice($data, $offset, $limit);
                }
            }
        }else{
            return $data;
        }
        
    }

    public function retrieveFromRequest(Request $request){
        $data = $request->all();
        $limit = intval($data['limit']);
        $offset = intval($data['offset']);
        $data = Cache::get($data['key']);


        if($offset && $limit && $data && sizeof($data) > 0){
            $size = sizeof($data);

            if($limit >= $size){
                return $data;
            }else{
                if($offset > $size){
                    return [];
                }else{
                    return array_slice($data, $offset, $limit);
                }
            }
        }else{
            return $data;
        }
        
    }

    public function checkIfExist($key){
        return Cache::get($key);
    }

    public function delete($key){
        return Cache::forget($key);
    }
}
