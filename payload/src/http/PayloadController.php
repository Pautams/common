<?php

namespace Increment\Common\Payload\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Common\Payload\Models\Payload;
use Increment\Common\Image\Models\Image;
use Mail;
use Carbon\Carbon;
use App\Mail\PreVerifyEmail;
class PayloadController extends APIController
{
    public $merchantController = 'Increment\Imarket\Merchant\Http\MerchantController';
    function __construct(){
      if($this->checkAuthenticatedUser() == false){
        return $this->response();
      }
      $this->model = new Payload();
      $this->notRequired = array('category');
    }

    public function createByParams($data){
      $this->model = new Payload();
      $this->insertDB($data);
      return $this->response['data'];
    }

    public function createsIndustry(Request $request){
      $data = $request->all();
      $con = $this->checkValidIndustry($data['account_id']);
      if($con === false){
        $this->model = new Payload();
        $this->insertDB($data);
        $var = array('industry' => $data['payload_value']);
        app($this->merchantController)->updateByParams('account_id', $data['account_id'], array('addition_informations'=>$var));
        return $this->response();
      }else{
        $result = Payload::where('account_id', '=', $data['account_id'])->update(array(
          'payload_value' => $data['payload_value'],
          'updated_at' => Carbon::now()
       ));
        $var = array('industry' => $data['payload_value']);
        app($this->merchantController)->updateByParams('account_id', $data['account_id'], array('addition_informations'=>$var));
        $this->response['data'] = $result;
        return $this->response();
      }
    }

    public function checkValidIndustry($accountId){
      $payload = Payload::where('payload', '=', 'assigned_industry')
            ->where('account_id', '=', $accountId)
            ->get();
      if(sizeof($payload) > 0){
        return true;
      }else{
        return false;
      }
    }

    public function uploadValidId(Request $request){
      $data = $request->all();
      $payloadValue = array();
      $payloadValue['url'] = $data['file_url'];
      $payloadValue['status'] = 'PENDING';
      $data['payload'] = 'valid_id';
      $values = json_encode($payloadValue);
      $data['payload_value'] = $values;
      $this->model = new Payload();
      $this->insertDB($data);
      return $this->response();
    }
    
    public function checkValidId(Request $request){
      $data=$request->all();
      $id = Payload::where('account_id','=',$data['account_id'])
            ->where('payload','=', 'valid_id')
            ->get();
      $this->response['data'] = $id;
      return $this->response();
    }

    public function getRiderSchedule(Request $request){
      $data = $request->all();
      //rider schedule json
      //{startTime: string, endTime: string, days: 0123456 <- representing duty days, validity: <- date started using said schedule}
      $values = Payload::where('payload', '=', 'rider_schedule')
                ->where('account_id', '=', $data['account_id'])
                ->get();
      $this->response['data'] = $values;
      return $this->response();
    }

    public function addRiderSchedule(Request $request){
      $data = $request->all();
      $data['payload'] = 'rider_schedule';
      $values = array();
      $values['startTime'] = $request['startTime'];
      $values['endTime'] = $request['endTime'];
      $values['days'] = $request['days'];
      $values['validity'] = $request['validity'];
      $values = json_encode($values);
      $data['payload_values'] = $values;
      $this->model = new Payload();
      $this->insertDB($data);
      return $this->response();
    }

    public function createFaqs(Request $request){
      $data = $request->all();
      $data['payload'] = 'faqs';
      $values = array();
      $values['question'] = $data['question'];
      $values['answer'] = $data['answer'];
      $values = json_encode($values);
      $data['payload_value'] = $values;
      $this->model = new Payload();
      $this->insertDB($data);
      return $this->response();
    }

    public function getResource(Request $request){
      $data = $request->all();
      if (isset($data['id'])){
        $value = Payload::where('id', '=', $data['id'])->where('payload', '=', $data['payload'])->get();
      }else if (isset($data['account_id'])){
        $value = Payload::where('account_id', '=', $data['account_id'])->where('payload', '=', $data['payload'])->get();
      }
      $this->response['data'] = $value;
      return $this->response();
    }

    public function createCategory(Request $request){
      $data = $request->all();
      $data['payload'] = 'product_category';
      $data['payload_value'] = $data['product_category'];
      $this->model = new Payload();
      $this->insertDB($data);
      return $this->response();
      //payload values would look like this
      //american, burger, fries, fastfood
      //payload_id would be linked to category in product same with tags ??
    }

    public function createCurrency(Request $request){
      $data = $request->all();
      $data['payload'] = 'available_currency';
      $data['payload_value'] = $data['currency'];
      $data['category'] = NULL;
      $this->model = new Payload();
      $this->insertDB($data);
      return $this->response();
    }

    public function getCurrency(Request $request){
      $data=$request->all();
      $id = Payload::where('account_id', '=', $data['account_id'])
            ->where('payload','=', 'available_currency')
            ->get();
      $this->response['data'] = $id;
      return $this->response();
    }

    public function getCurrencyParams($accountId){
      $id = Payload::where('account_id', '=', $accountId)
            ->where('payload','=', 'available_currency')
            ->get();
      return $id;

    }

    public function getCategory(Request $request){
      $data = $request->all();
      if (isset($data['account_id'])){
        $value = Payload::where('payload', '=', 'product_category')->where('account_id','=', $data['account_id'])->get();
      }else{
        $value = Payload::where('payload', '=', 'product_category')->get();
      }
      $this->response['data'] = $value;
      return $this->response();
    }

    public function getByParams($arrayCondition){
      $result = Payload::where($arrayCondition)->get();
      return sizeof($result) > 0 ? $result[0] : null;
    }
    
    public function createWithImages(Request $request){
      $data = $request->all();
      $exist = Payload::whereRaw("BINARY `payload_value` = ?", [$data['payload_value']])->get();
      // dd($exist);
      if(sizeof($exist) > 0 && $data['status'] === 'create'){
        $this->response['error'] = 'Already Existed';
        $this->response['data'] = null;
        return $this->response();
      }else{
        $payload = array(
          'account_id'    => $data['account_id'],
          'payload' => $data['payload'],
          'category' => $data['category'],
          'payload_value' => $data['payload_value'],
          'details' => isset($data['details']) ? $data['details'] : null
        );
        if($data['status'] === 'create'){
          $res = Payload::create($payload);
        }else if($data['status'] === 'update'){
          $payload['updated_at'] = Carbon::now();
          $res = Payload::where('id', '=', $data['id'])->update($payload);
        }
        if(isset($data['images'])){
          if(sizeof($data['images']) > 0){
            for ($i=0; $i <= sizeof($data['images'])-1 ; $i++) { 
              $item = $data['images'][$i];
              $params = array(
                'room_id' => $data['status'] === 'create' ? $res['id'] : $data['id'],
                'url' => $item['url'],
                'status' => 'room_type'
              );
              app('Increment\Hotel\Room\Http\ProductImageController')->addImage($params);
            }
          }
        }
        $this->response['data'] = $res;
        return $this->response();
      }
    }

    public function retrieveWithImage(Request $request){
      $data = $request->all();
      $con = $data['condition'];
      $res = Payload::where($con[0]['column'], $con[0]['clause'], $con[0]['value'])
        ->where('deleted_at', '=', null)
        ->where('payload', '=', $data['payload'])
        ->offset($data['offset'])->limit($data['limit'])
        ->orderBy(array_keys($data['sort'])[0], array_values($data['sort'])[0])
        ->get();
      
      $size = Payload::where($con[0]['column'], $con[0]['clause'], $con[0]['value'])
        ->where('deleted_at', '=', null)
        ->where('payload', '=', $data['payload'])
        ->get();

      if(sizeof($res) > 0){
        for ($i=0; $i <= sizeof($res)-1; $i++) {
          $item = $res[$i];
          $res[$i]['image'] = app('Increment\Hotel\Room\Http\ProductImageController')->getImage($item['id']);
        }
      }
      $this->response['data'] = $res;
      $this->response['size'] = sizeOf($size);
      return $this->response();
    }
    
    public function retrieveById(Request $request){
      $data = $request->all();
      $res = Payload::where('id', $data['id'])->first();
      $res['images'] = app('Increment\Hotel\Room\Http\ProductImageController')->getImages($res['id']);
      $this->response['data'] = $res;
      return $this->response();
    }

    public function removeWithImage(Request $request){
      $data = $request->all();
      $res = Payload::where('id', '=', $data['id'])->update(array(
        'deleted_at' => Carbon::now()
      ));
      app('Increment\Hotel\Room\Http\ProductImageController')->removeImages($data['id']);

      $this->response['data'] = $res;
      return $this->response();
    }

    public function retrieveAll(){
      return Payload::where('deleted_at', '=', null)->where('payload', '=', 'room_type')->get(['payload_value', 'id']);
    }

    public function retrieveCategory(Request $request){
      $data = $request->all();
      $result = Payload::where('deleted_at', '=', null)->where('payload', '=', 'room_type')->get(['payload_value', 'id']);
      $this->response['data'] = $result;
      return $this->response();
    }
    
    public function retrieveByParams($roomTypeId){
      return Payload::where('id', '=', $roomTypeId)->where('deleted_at', '=', null)->select('payload_value', 'id')->first();
    }

    public function preVerifyEmail(Request $request){
      $data = $request->all();

      if($data['email'] == null){
          $this->response['error'] = 'Empty email address';
          return $this->response();
      }

      $checkIfExist = app('Increment\Common\Payload\Http\PayloadController')->getByParams(array(
          array('payload_value', '=', $data['email']),
          array('payload', '=', 'pre_register')
      ));

      $checkIfAccountExist = app('Increment\Account\Http\AccountController')->retrieveByEmail($data['email']);

      if($checkIfExist == null && $checkIfAccountExist == null){
          // check new payload
          $code = $this->generatePayloadCode($data['email']);
          Payload::insert(array(
              'account_id' => 1,
              'payload' => 'pre_register',
              'payload_value' => $data['email'],
              'category' => $code,
              'created_at' => Carbon::now()
          ));
          if(env('EMAIL_STATUS') == false){
              $this->response['data'] = true;
          }else{
              Mail::to($data['email'])->send(new PreVerifyEmail($data['email'], $code, $this->response['timezone']));
          }
          $this->response['data'] = true;
      }else{
        if($checkIfAccountExist){
          $this->response['error'] = 'Email address is already existed.';
        }else{
          $code = $this->generatePayloadCode($data['email']);
          $update = Payload::where('payload_value', '=', $data['email'])
            ->where('payload', '=', 'pre_register')
            ->update(array(
              'category' => $code,
              'updated_at' => Carbon::now()
            ));
          if(env('EMAIL_STATUS') == false){
            $this->response['data'] = true;
          }else{
            Mail::to($data['email'])->send(new PreVerifyEmail($data['email'], $code, $this->response['timezone']));
          }
          $this->response['data'] = true;
        }
      }
      return $this->response();
    }

    public function generatePayloadCode($email){
      $code = substr(str_shuffle('0123456789'), 0, 6);
      $codeExist = Payload::where('category', '=', $code)->where('payload_value', '=', $email)->where('payload', '=', 'pre_register')->get();
      if (sizeof($codeExist) > 0) {
        $this->generatePayloadCode($email);
      } else {
        return $code;
      }
    }
    
    public function retrieveSubscriptions(Request $request){
      $data = $request->all();
      $con = $data['condition'];
      $limit = isset($data['limit']) ? $data['limit'] : null;
      $result = Payload::where($con[0]['column'], $con[0]['clause'], $con[0]['value'])->limit($limit)->get();
      if(sizeof($result) > 0){
        for ($i=0; $i <= sizeof($result)-1; $i++) { 
          $item = $result[$i];
          $result[$i]['payload_value'] = json_decode($item['payload_value']);
        }
        $this->response['data'] = $result;
      }
      return $this->response();
    }

    public function retrieveWithValidation(Request $request){
      $data = $request->all();
      $con = $data['condition'];
      $temp = Payload::where($con[0]['column'], $con[0]['clause'], $con[0]['value'])->where('deleted_at', '=', null)->get();
      $result = [];
      if(sizeof($temp) > 0){
        for ($i=0; $i <= sizeof($temp)-1 ; $i++) { 
          $item = $temp[$i];
          $addedCategoryPerRoom = app('Increment\Hotel\Room\Http\RoomController')->retrieveByCategory($item['id']);
          $limitPerCategory = app('Increment\Hotel\Room\Http\AvailabilityController')->retrieveByPayloadPayloadValue('room_type', $item['id']);
          if($limitPerCategory !== null){
            if(sizeof($addedCategoryPerRoom) <= (int)$limitPerCategory['limit']){
              array_push($result, $item);
            }
          }
        }
        $this->response['data'] = $result;
      }
      return $this->response();
    }
    
    public function retrievePayloads($payload, $payloadValue, $payload1, $payloadValue1) {
      $res = Payload::where('deleted_at', '=', null)
      ->where($payload, '=', $payloadValue)
      ->where($payload1, '=', $payloadValue1)
      ->get();

      $this->response['data'] = sizeof($res) > 0 ? $res : [];
      return $this->response['data'];
    }

    public function retrieveStorePayments(Request $request){
      $data = $request->all();
      $result = Payload::where('account_id', '=', $data['account_id'])->where('payload', '=', 'Branch')->get();

      if(sizeof($result) > 0){
        for($i=0; $i < sizeof($result); $i++){
          $item = $result[$i];
          $result[$i]['payload_value'] = json_decode($item['payload_value']);
        }
      }
      $this->response['data'] = $result;
      return $this->response();
    }

    // public function retrieveRecipients(Request $request) {
    //   $data = $request->all();
    //   $recipients = Payload::where('account_id', '=', '$data[account_id]')->get();
    //   if(sizeof($recipients > 0)){
    //     for($i=0; $i < sizeof($recipients); $i++){
    //       $item = $recipients[$i];
    //       $recipients[$i]['recipients'] = json_decode($item['recipients']);
    //     }
    //   }
    // }
}
