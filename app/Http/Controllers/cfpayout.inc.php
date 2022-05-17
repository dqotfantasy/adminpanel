<?php
class CfPayout {

    protected $token;
    protected $baseUrl;

    public function __construct($authParams) {
        if(!empty($authParams)) {
            $clientId = $authParams["clientId"];
            $clientSecret = $authParams["clientSecret"];
            $stage = $authParams["stage"];
            if ($stage == "PROD") {
                $this->baseUrl = "https://payout-api.cashfree.com/payout/v1";
            } else {
              $this->baseUrl = "https://payout-gamma.cashfree.com/payout/v1";
            }
            $headers = [
             "X-Client-Id: $clientId",
             "X-Client-Secret: $clientSecret"
            ];
            $endpoint = $this->baseUrl."/authorize";
            $curlResponse = $this->postCurl($endpoint, $headers);
            if ($curlResponse) {
               if ($curlResponse["status"] == "SUCCESS") {
                 $this->token = $curlResponse["data"]["token"];
               } else {
                  throw new Exception("Authorization failed. Reason : ". $curlResponse["message"]);
               }
            }
         }
    }

    public function addBeneficiary ($beneficiary) {

      $response =["status" => "FAILED", "message" => "Authorization failed"];
      if ($this->token) {
        $endpoint = $this->baseUrl."/addBeneficiary";
        $authToken = $this->token;
        $headers = [
            "Authorization: Bearer $authToken"
            ];
        $curlResponse = $this->postCurl($endpoint, $headers, $beneficiary);
        return $curlResponse;
      }
      return $response;
    }

    public function getBeneficiary ($beneficiary) {

        $response =["status" => "FAILED", "message" => "Authorization failed"];
        if ($this->token) {
          $endpoint = $this->baseUrl."/getBeneficiary/".$beneficiary;

          $authToken = $this->token;
          $headers = [
              "Authorization: Bearer $authToken"
              ];
          $curlResponse = $this->getCurl($endpoint, $headers, $beneficiary);
          return $curlResponse;
        }
        return $response;
      }

    public function removeBeneficiary ($beneId) {
      $response =["status" => "FAILED", "message" => "Authorization failed"];
      if($this->token) {
        $params = [];
        $params["beneId"] = $beneId;

        $endpoint = $this->baseUrl."/removeBeneficiary";
        $authToken = $this->token;
        $headers = [
            "Authorization: Bearer $authToken"
            ];
        $curlResponse = $this->postCurl($endpoint, $headers, $params);
        return $curlResponse;
      }
      return $response;
    }
    public function requestTransfer ($transfer) {
      $response =["status" => "FAILED", "message" => "Authorization failed"];
      if ($this->token) {
        $endpoint = $this->baseUrl."/requestTransfer";
        $authToken = $this->token;
        $headers = [
             "Authorization: Bearer $authToken"
              ];
        $curlResponse = $this->postCurl($endpoint, $headers, $transfer);
        return $curlResponse;
      }
      return $response;
    }
    public function getBalance () {
      $balance =["ledger" => -1, "available" => -1];
      if ($this->token) {
        $endpoint = $this->baseUrl."/getBalance";
        $authToken = $this->token;
        $headers = [
             "Authorization: Bearer $authToken"
              ];
        $curlResponse = $this->getCurl($endpoint, $headers);
        if ($curlResponse["status"] == "SUCCESS") {
          $balance["ledger"] = $curlResponse["data"]["balance"];
          $balance["available"] = $curlResponse["data"]["availableBalance"];
        }
      }
      return $balance;
    }


    protected function postCurl ($endpoint, $headers, $params = []) {
      $postFields = json_encode($params);
      array_push($headers,
         'Content-Type: application/json',
         'Content-Length: ' . strlen($postFields));
      $endpoint = $endpoint."?";
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $endpoint);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $returnData = curl_exec($ch);
      curl_close($ch);
      if ($returnData != "") {
        return json_decode($returnData, true);
      }
      return NULL;
    }
    protected function getCurl ($endpoint, $headers) {
       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $endpoint);
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
       $returnData = curl_exec($ch);
       curl_close($ch);
       if ($returnData != "") {
        return json_decode($returnData, true);
       }
       return NULL;
    }
    function __destruct()
    {
        $this->token = NULL;
    }

    public function getTransferRequest ($checktran) {
        $request_string = "";
          foreach($checktran as $key=>$value) {
            $request_string .= $key.'='.rawurlencode($value).'&';
          }
        $response =["subCode" => 0, "status" => "FAILED", "message" => "Authorization failed"];
        if ($this->token) {
          $endpoint = $this->baseUrl."/getTransferStatus?".$request_string;
          $authToken = $this->token;
          $headers = [
               "Authorization: Bearer $authToken"
                ];
          $curlResponse = $this->getCurl($endpoint, $headers);
          return $curlResponse;
        }
        return $response;
      }
}
?>
