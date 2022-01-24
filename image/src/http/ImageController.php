<?php

namespace Increment\Common\Image\Http;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Increment\Common\Image\Models\Image;
use App\Http\Controllers\APIController;
use Illuminate\Support\Facades\Storage;
use File;
use Response;
class ImageController extends APIController
{
    function __construct(){
      $this->notRequired = array(
        'category', 'payload', 'payload_value',
      );
      $this->model = new Image();
    }

    public function retrieve(Request $request){
      $data = $request->all();
      $this->model = new Image();
      $this->retrieveDB($data);
      if(sizeof($this->response['data']) > 0){
        $i = 0;
        foreach ($this->response['data'] as $key) {
          $this->response['data'][$i]['active'] = false;
          $i++;
        }
      }
      return $this->response();
    }

    public function retrieveWithCategory(Request $request){
      $data = $request->all();
      $result = Image::where('category', '=', $data['category'])->where('deleted_at', '=', null)->get();
      $this->response['data'] = $result;
      if(sizeof($this->response['data']) > 0){
        $i = 0;
        foreach ($this->response['data'] as $key) {
          $this->response['data'][$i]['active'] = false;
          $i++;
        }
      }
      return $this->response();
    }

    public function upload(Request $request){
      $data = $request->all();
      if(isset($data['file_url'])){
        $date = Carbon::now()->toDateString();
        $time = str_replace(':', '_',Carbon::now()->toTimeString());
        $ext = $request->file('file')->extension();
        // $fileUrl = str_replace(' ', '_', $data['file_url']);
        // $fileUrl = str_replace('%20', '_', $fileUrl);
        $filename = $data['account_id'].'_'.$date.'_'.$time.'_'.$data['file_url'];
        $result = $request->file('file')->storeAs('images', $filename);
        $url = '/storage/image/'.$filename;
        $this->model = new Image();
        $insertData = array(
          'account_id'    => $data['account_id'],
          'url'           => $url,
          'category'      => isset($data['category']) ? $data['category'] : null
        );
        $this->insertDB($insertData);
        $this->response['data'] = $url;
        return $this->response();
      }
      return response()->json(array(
        'data'  => null,
        'error' => null,
        'timestamps' => Carbon::now()
      ));
    }

    public function uploadFile(Request $request){
      $data = $request->all();
      if(isset($data['file_url'])){
        $date = Carbon::now()->toDateString();
        $time = str_replace(':', '_',Carbon::now()->toTimeString());
        $ext = $request->file('file')->extension();
        // $fileUrl = str_replace(' ', '_', $data['file_url']);
        // $fileUrl = str_replace('%20', '_', $fileUrl);
        $filename = $data['account_id'].'_'.$date.'_'.$time.'_'.$data['file_url'];
        $result = $request->file('file')->storeAs('files', $filename);
        $url = '/storage/file/'.$filename;
        $this->model = new Image();
        $insertData = array(
          'account_id'    => $data['account_id'],
          'url'           => $url,
          'category'      => $data['category']
        );
        $this->insertDB($insertData);
        $this->response['data'] = $url;
        return $this->response();
      }
      return response()->json(array(
        'data'  => null,
        'error' => null,
        'timestamps' => Carbon::now()
      ));
    }

    public function retrieveFile(Request $request){
      $data = $request->all();
      $result = Image::where('account_id', '=', $data['account_id'])->get();

      $this->response['data'] = $result;
      return $this->response();
    }

    public function uploadUnLink(Request $request){
      $data = $request->all();
      if(isset($data['file_url'])){
        $date = Carbon::now()->toDateString();
        $time = str_replace(':', '_',Carbon::now()->toTimeString());
        $ext = $request->file('file')->extension();
        $filename = $data['account_id'].'_'.$date.'_'.$time.'_'.$data['file_url'];
        $result = $request->file('file')->storeAs('images', $filename);
        $url = '/storage/image/'.$filename;
        $this->response['data'] = $url;
        return $this->response();
      }
      return response()->json(array(
        'data'  => null,
        'error' => null,
        'timestamps' => Carbon::now()
      ));
    }

    public function uploadBase64(Request $request){
      $data = $request->all();
      if(isset($data['file_base64'])){
        $date = Carbon::now()->toDateString();
        $time = str_replace(':', '_',Carbon::now()->toTimeString());
        $filename = $data['account_id'].'_'.$date.'_'.$data['file_url'];
        $image = base64_decode($data['file_base64']);
        Storage::disk('local')->put('images/'.$filename, $image);
        $url = '/storage/image/'.$filename;
        $this->response['data'] = $url;
        return $this->response();
      }
      return response()->json(array(
        'data'  => null,
        'error' => null,
        'timestamps' => Carbon::now()
      ));
    }

    public function uploadBase64Array(Request $request){
      $data = $request->all();
      $date = Carbon::now()->toDateString();
      $time = str_replace(':', '_',Carbon::now()->toTimeString());
      $images = [];
      foreach ($data['images'] as $key) {
        $filename = $data['account_id'].'_'.$date.'_'.$key['file_url'];
        $image = base64_decode($key['file_base64']);
        Storage::disk('local')->put('images/'.$filename, $image);
        $url = '/storage/image/'.$filename;
        $params = array(
          'account_id' => $data['account_id'],
          'payload' => $data['payload'],
          'payload_value' =>$data['payload_value'],
          'category' => $url
        );
        $res = app('Increment\Common\Payload\Http\PayloadController')->createByParams($params);
        $params['id'] = $res['data'];
        array_push($images, $params);
      }
      $this->response['data'] = $images;
      return $this->response();
    }

    public function retrieveFeaturedPhotos($payload, $payload_value, $payload1, $payload_value1){
      $result = Image::where($payload, '=', $payload_value)->where($payload1, '=', $payload_value1)->where('deleted_at', '=', null)->get();
      if(sizeof($result) > 0) {
        return $result;
      } else {
        return [];
      }
    }

    public function getImage($filename){
      $path = storage_path('/app/images/' . $filename);

      if (!File::exists($path)) {
          abort(404);
      }

      $file = File::get($path);
      $type = File::mimeType($path);

      $response = Response::make($file, 200);
      $response->header("Content-Type", $type);

      return $response;
    }

    public function getFile($filename){
      $path = storage_path('/app/files/' . $filename);

      if (!File::exists($path)) {
          abort(404);
      }

      $file = File::get($path);
      $type = File::mimeType($path);

      $response = Response::make($file, 200);
      $response->header("Content-Type", $type);

      return $response;  
    }

    // public function removeImages($)
}
