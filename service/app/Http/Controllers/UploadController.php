<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Excel;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
class UploadController extends Controller {
  public $GlobalData = array();
  public $globalvar = array();

  public function upload(Request $req) {
    // Get Paypal Token Code
    $client = new Client();
    $apiClientId = "AUB4XIf3huW9TH6203ZM-14B45GsGLiQupCAt0wGBpxlfcADs7BxdcUfrWpQe92hNQPS2yZqKesd8X-u";
    $apiClientSecret = "EAKCLdZx6cymwjate1oKgKc3jctG8f1E_tBtLB6fP07O__qmqTlw3PcYyIXiTQDd0WO9_vvUt9nBNWwE";
    $getToken = $client->request("POST","https://api.paypal.com/v1/oauth2/token",
    [
      "headers"=> [
        'Accept' => 'application/json',
        'Accept-Language' => 'en_US',
        'Content-Type' => 'application/x-www-form-urlencoded'
      ],
      'auth' =>  [$apiClientId, $apiClientSecret, 'basic'],
      'form_params' => ['grant_type' => 'client_credentials']
    ]);
    $this->GlobalData['paypal'] = json_decode($getToken->getbody(),true);
    // End Get Paypal Token Code

    if ($req->hasFile('file')) {
      $req->file->store('public');
      $path =  storage_path('app/public/'.$req->file->hashName());

      // Read Exel Data
      Excel::load($path, function($reader) {
        $tokenCode = $this->GlobalData['paypal']['access_token'];
        $exelData = $reader->get();
        $client = new Client();

        $user = '10cd4275238f571cdf4308bdb8c9caa6';
        $pass = "e1fa0949ba9687a8c9a630500adeb89f";

        for ($i=0; $i < count($exelData); $i++) {
          $id = $exelData[$i]['order_id'];
          $this->globalvar[$i]['order_id'] = $id;
          if (!empty($id)) {
            // Get Shopify Order Detail
            $getOrder = $client->request("GET",
            "https://{$user}:{$pass}@gadgetsmarket.myshopify.com/admin/orders.json",
              ['query' => [
                'name' => $id,
                'fields'=> "fulfillment_status,id,line_items,fulfillment_status",
                'status'=>'any'
              ]]);
            $orderData = json_decode($getOrder->getbody(),true);
            // End Get Shopify Order Detail

            if (!empty($orderData['orders']) && empty($orderData['orders'][0]['fulfillment_status']) && $getOrder->getStatusCode() == 200) {
              // fulfillment Shopify
              $fulfill = array(
                "fulfillment" => [
                  "tracking_number" => preg_replace('/[^0-9]/', '', $exelData[$i]['tracking_number']),
                  "tracking_company" => strtoupper($exelData[$i]['tracking_company']),
                  "tracking_urls" => [],
                  "line_items" =>[]
                  ]
                );

                if (!empty($exelData[$i]['tracking_url'])) {
                  $fulfill['fulfillment']['tracking_urls'][] = $exelData[$i]['tracking_url'];
                }
                elseif (empty($exelData[$i]['tracking_url'])) {
                  unset($fulfill['fulfillment']['tracking_urls']);
                }

                for ($j=0; $j < count($orderData['orders'][0]['line_items']); $j++) {
                  $fulfill['fulfillment']['line_items'][] = array('id' => $orderData['orders'][0]['line_items'][$j]['id']);
                }

                $Fulfillstatus = '';
                try {
                  $orderFulfill = $client->request(
                    "POST",
                    "https://{$user}:{$pass}@gadgetsmarket.myshopify.com/admin/orders/{$orderData['orders'][0]['id']}/fulfillments.json",
                    [
                      "headers"=> [
                        "Content-Type" => "application/json"
                      ],
                      "body" => json_encode($fulfill)
                    ]);
                  $this->globalvar[$i]['fulfillment_status'] = $orderFulfill->getStatusCode();
                } catch (ClientException $e) {
                  $this->globalvar[$i]['fulfillment_status'] = $e->getResponse()->getStatusCode();
                }
                // End fulfillment Shopify
              }
              elseif (!empty($orderData['orders']) && $orderData['orders'][0]['fulfillment_status'] == 'fulfilled' && $getOrder->getStatusCode() == 200) {
                  $this->globalvar[$i]['fulfillment_status'] = 422;
              }


              // Get Paypal TransactionId from Shopify
              $getTransaction = $client->request("GET",
              "https://{$user}:{$pass}@gadgetsmarket.myshopify.com/admin/orders/{$orderData['orders'][0]['id']}/transactions.json",
              ['query' => [
                  'fields'=> "authorization,status"
                ]
              ]);
              $transactionData = json_decode($getTransaction->getbody(),true);
              $this->globalvar[$i]['transaction_id'] = $transactionData['transactions'][0]['authorization'];
              // Get Paypal TransactionId from Shopify


              $tracker = array(
                "trackers" => [[
                  "transaction_id" => $this->globalvar[$i]['transaction_id'],
                  "tracking_number" => preg_replace('/[^0-9]/', '', $exelData[$i]['tracking_number']),
                  "status" => "SHIPPED",
                  "shipment_date" => !empty($exelData[$i]['shipment_date']) ? date("Y-m-d", strtotime($exelData[$i]['shipment_date'])) : date('Y-m-d'),
                  "carrier" => "OTHER",
                  "carrier_name_other" => strtoupper($exelData[$i]['tracking_company'])
                  ]]
                );

              try {
                $paypalAddTracking = $client->request(
                  "POST",
                  "https://api.paypal.com/v1/shipping/trackers",
                  [
                    "headers"=> [
                      "Content-Type" => "application/json",
                      "Authorization" => "Bearer {$tokenCode}"
                    ],
                    "body" => json_encode($tracker)
                  ]);
                $this->globalvar[$i]['paypalAddTracking_status'] = $paypalAddTracking->getStatusCode();
              } catch (ClientException $e) {
                $this->globalvar[$i]['paypalAddTracking_status'] = $e->getResponse()->getStatusCode();
              }

            }
          }
        });
        // End of Reqad Excel Data

        // echo "<pre>";
        // print_r($this->globalvar);
        Storage::delete('public/'.$req->file->hashName());
      }
      return view("result", ["data"=>$this->globalvar]);
    }
  }
