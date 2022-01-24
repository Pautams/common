<?php

namespace Increment\Common\Rating\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Common\Rating\Models\Rating;

class RatingController extends APIController
{
    public $cacheController = 'Increment\Common\Cache\Http\CacheController';

    function __construct(){
      if($this->checkAuthenticatedUser() == false){
        return $this->response();
      }
      $this->model = new Rating();
      $this->notRequired = array(
        'comments'
      );
    }

    public function create(Request $request){
      $data = $request->all();
      $result = array();
      if($data['status'] == 'partial'){
        $result = Rating::where('account_id', '=', $data['account_id'])
      ->where('payload', '=', $data['payload'])->where('payload_value', '=', $data['payload_value'])->get();
      }else{
         $result = Rating::where('account_id', '=', $data['account_id'])
        ->where('payload', '=', $data['payload'])
        ->where('payload_value', '=', $data['payload_value'])
        ->where('payload_1', '=', $data['payload_1'])
        ->where('payload_value_1', '=', $data['payload_value_1'])->get();
      }
      
      if(sizeof($result) > 0){
        $this->model = new Rating();
        $this->response['error']['message'] = "You've submitted reviews already.";
        $this->response['error']['status'] = 100;
      }else{
        $this->insertDB($data);

        $key = 'ratings_'.$$data['payload'].'_'.$data['payload_value'];
        app($this->cacheController)->delete($key);
      }
      return $this->response();
    }

    public function retrieve(Request $request){
      $data = $request->all();
      $this->retrieveDB($data);
      $result = $this->response['data'];

      if(sizeof($result) > 0){
        $payload = $data['condition'][0]['value'];
        $payloadValue = $data['condition'][1]['value'];
        $avg = Rating::where('payload', '=', $payload)->where('payload_value', '=', $payloadValue)->avg('value');
        $total = Rating::where('payload', '=', $payload)->where('payload_value', '=', $payloadValue)->sum('value');
        $stars = round($avg);
        $this->response['total'] = $total;
        $this->response['avg'] = $avg;
        $this->response['stars'] = $stars;
      }
      $accountId = $data['account_id'];
      $payload = $data['condition'][0]['value'];
      $payloadValue = $data['condition'][1]['value'];
      $this->response['status'] = $this->checkAccountExist($accountId, $payload, $payloadValue);
      return $this->response();
    }

    public function retrieveById(Request $request){
      $data = $request->all();
      $this->retrieveDB($data);
      return $this->response();
    }

    public function checkAccountExist($accountId, $payload, $payloadValue){
      $result = Rating::where('account_id', '=', $accountId)
      ->where('payload', '=', $payload)
      ->where('payload_value', '=', $payloadValue)
      ->get();
      return (sizeof($result) > 0) ? true : false;
    }

    public function getByParams($accountId, $payload1, $payloadValue1){
      $rating = Rating::where('account_id', '=', $accountId)
                ->where('payload_1', '=', $payload1)
                ->where('payload_value_1', '=', $payloadValue1)->get();
      return sizeof($rating) > 0 ? $rating[0] : null;
    }

    public function getRatingByPayload($payload, $payloadValue){
      $key = 'ratings_'.$payload.'_'.$payloadValue;
      $result = app($this->cacheController)->retrieve($key, null, null);

      if(app($this->cacheController)->retrieveCondition($result, 0) == true){
        return $result;
      }else{
        $rating = Rating::where('payload', '=', $payload)->where('payload_value', '=', $payloadValue)->get();
        $avg = 0;
        $totalRating = 0;
        $size = sizeof($rating);
        
        if(sizeof($rating) > 0){
          $i = 0;
          foreach ($rating as $key) {
            $totalRating += intval($rating[$i]['value']);
            $i++;
          }
        }

        $avg = ($size > 0) ? floatval($totalRating / $size) : $totalRating;
        $result =  array(
          'total' => $totalRating,
          'size'  => $size,
          'avg'   => $avg,
          'stars' => round($avg)
        );

        app($this->cacheController)->insert($key, $result);
        return $result;
      }
    }

    public function getRatingByPayload2($accountId, $payload, $payloadValue, $payload1, $payloadValue1){
      $rating = Rating::where('account_id', '=', $accountId)
        ->where('payload', '=', $payload)
        ->where('payload_value', '=', $payloadValue)
        ->where('payload_1', '=', $payload1)
        ->where('payload_value_1', '=', $payloadValue1)
        ->get();
      return sizeof($rating) > 0 ? $rating[0]['value'] : null;
    }
    
    public function getRatingByParams($column, $value){
      $rating = Rating::where($column, '=', $value)->get();
      return sizeof($rating) > 0 ? $rating : [];
    }
}
