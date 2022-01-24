<?php

namespace Increment\Common\Ticket\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Common\Ticket\Models\Ticket;
use Carbon\Carbon;

class TicketController extends APIController
{
    //
    function __construct(){
        if($this->checkAuthenticatedUser() == false){
          return $this->response();
        }
        $this->model = new Ticket();
        $this->notRequired = array('assigned_to', 'images');
      }
    
    public function generateCode(){
      $code = 'tic_'.substr(str_shuffle($this->codeSource), 0, 60);
      $codeExist = Ticket::where('id', '=', $code)->get();
      if(sizeof($codeExist) > 0){
        $this->generateCode();
      }else{
        return $code;
      }
    }

    public function retrieve(Request $request){
      $data = $request->all();
      $this->model = new Ticket();
      $this->retrieveDB($data);
      $result = $this->response['data'];

      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          // $result[$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y h:i A');
          $result[$i]['created_at_human'] = $result[$i]['created_at'];
          $result[$i]['name'] = $this->retrieveNameOnly($key['account_id']);
          $result[$i]['assignTo'] = $this->retrieveAccountDetails($result[$i]['assigned_to']);
          $i++;
        }
      }
      
      $this->response['size'] = Ticket::where('deleted_at', '=', null)->count();
      $this->response['data'] = $result;

      return $this->response();
    }

    public function create(Request $request){
      $data = $request->all();
      $data['code'] = $this->generateCode();
      $data['status'] = 'PENDING';
      $this->model = new Ticket();
      $this->insertDB($data);
      return $this->response();
    }

    public function resolveTicket(Request $request){
      //check authenticated user
      //TODO: add authentication here
      $data = $request->all();
      $result = Ticket::where('code', '=', $data['code'])->update(array('status' => 'CLOSED'));
      $this->response['data'] = $result ? true : false;
      return $this->response();
    }

    public function updateAssign(Request $request){
      $data = $request->all();
      $result = Ticket::where('id', '=', $data['ticket_id'])
        ->update(
            array(
              'assigned_to' => $data['assigned_to'],
              'status' => $data['status']
            ));
      $this->response['data'] = $result ? true : false;
      return $this->response();
    }
}
