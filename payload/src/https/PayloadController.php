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
          $this->response['error'] = 'Email address is already existed.';
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

    public function retrievePayloads($payload, $payloadValue, $payload1, $payloadValue1) {
      $res = Payload::where('deleted_at', '=', null)
      ->where($payload, '=', $payloadValue)
      ->where($payload1, '=', $payloadValue1)
      ->get();

      $this->response['data'] = sizeof($res) > 0 ? $res : [];
      return $this->response['data'];
    }

    public function createsIndustry(Request $request){
      $data = $request->all();
      $con = $this->checkValidIndustry($data['account_id']);
      $merchantController = 'Increment\Imarket\Merchant\Http\MerchantController';
      if($con === false){
        $this->model = new Payload();
        $this->insertDB($data);
        $var = array('industry' => $data['payload_value']);
        app($merchantController)->updateByParams('account_id', $data['account_id'], array('addition_informations'=>$var));
        return $this->response();
      }else{
        $result = Payload::where('account_id', '=', $data['account_id'])->update(array(
          'payload_value' => $data['payload_value'],
          'updated_at' => Carbon::now()
       ));
        $var = array('industry' => $data['payload_value']);
        app($merchantController)->updateByParams('account_id', $data['account_id'], array('addition_informations'=>$var));
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
    
}
