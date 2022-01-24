<?php

namespace Increment\Common\Comment\Http;

use Illuminate\Http\Request;
use Increment\Common\Comment\Models\Comment;
use Increment\Common\Comment\Models\CommentReply;
use App\Http\Controllers\APIController;
use Carbon\Carbon;
use App\Jobs\Notifications;
class CommentController extends APIController
{
    function __construct(){
      $this->model = new Comment();
    }

    public function createWithNotification(Request $request){
      $data = $request->all();
      $this->model = new Comment();
      $this->insertDB($data);
      if($this->response['data']){
        $data['id'] = $this->response['data'];
        $data['title'] = 'New comment added to your ticket';
        $data['message'] = $data['text'];
        $data['from'] = $data['account_id'];
        $data['created_at'] = Carbon::now();
        app('Increment\Common\Notification\Http\NotificationController')->createByParams($data);
        if(isset($data['to'])){
          $data['to'] = 'ticket-comment-'.$data['to'];
          $data['topic'] = 'ticket-comment';
          $data['account'] = $this->retrieveAccountDetailsOnRequests($data['account_id']);
          $data['created_at_human'] = Carbon::now()->copy()->diffForHumans();
          Notifications::dispatch('ticket-comment', $data);
        }
      }
      return $this->response();
    }

    public function retrieve(Request $request){
      $data = $request->all();
      $this->retrieveDB($data);

      $result = $this->response['data'];
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $this->response['data'][$i]['account'] = $this->retrieveAccountDetails($result[$i]['account_id']);
          $this->response['data'][$i]['comment_replies'] = $this->getReplies($result[$i]['id']);
          $this->response['data'][$i]['created_at_human'] = Carbon::createFromFormat($this->dateTimeFormat, $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->diffForHumans();
          $this->response['data'][$i]['new_reply_flag'] = false;
          $i++;
        }
      }
      return $this->response();
    }

    public function retrieveCommentsWithImages(Request $request){
      $data = $request->all();
      $this->retrieveDB($data);

      $result = $this->response['data'];
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $this->response['data'][$i]['account'] = $this->retrieveAccountDetails($result[$i]['account_id']);
          $this->response['data'][$i]['comment_replies'] = $this->getReplies($result[$i]['id']);
          $this->response['data'][$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->diffForHumans();
          $this->response['data'][$i]['new_reply_flag'] = false;
          $this->response['data'][$i]['images'] = app('Increment\Common\Payload\Http\PayloadController')->retrievePayloads('payload', 'comment_id', 'payload_value', $result[$i]['id']);
          $this->response['data'][$i]['amen'] = app('App\Http\Controllers\ReactionController')->retrieveWithPayload('comment_id', '=', $result[$i]['id'], 'reaction', '=', 'amen');
          $this->response['data'][$i]['love'] = app('App\Http\Controllers\ReactionController')->retrieveWithPayload('comment_id', '=', $result[$i]['id'], 'reaction', '=', 'love');
          $i++;
        }
      }
      return $this->response();
    }

    public function retrieveComments(Request $request){
      $data = $request->all();
      $this->retrieveDB($data);

      $result = $this->response['data'];
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $this->response['data'][$i]['account'] = $this->retrieveAccountDetails($result[$i]['account_id']);
          $this->response['data'][$i]['comment_replies'] = $this->getReplies($result[$i]['id']);
          $this->response['data'][$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->diffForHumans();
          $this->response['data'][$i]['new_reply_flag'] = false;
          $this->response['data'][$i]['members'] = app('App\Http\Controllers\CommentMemberController')->retrieveMemberWithInfo($result[$i]['id']);
          $i++;
        }
      }
      return $this->response();
    }

    public function getReplies($commentId){
      $this->localization();
      $result = CommentReply::where('comment_id', '=', $commentId)->orderBy('created_at', 'ASC')->get();
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $result[$i]['account'] = $this->retrieveAccountDetails($result[$i]['account_id']);
          // $result[$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y h:i A');
          $i++;
        }
        return $result;
      }else{
        return null;
      }
    }

    public function getComments($payload, $payloadValue){
      $result = Comment::where('payload', '=', $payload)->where('payload_value', '=', $payloadValue)->get();
      return sizeof($result);
    }
}
