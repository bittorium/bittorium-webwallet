<?php
//echo "Entering lib/daemon.php<br>";
function daemonrpc_get($path) {
  global $daemonHost, $daemonPort;
  $curl = curl_init();
  curl_setopt_array($curl, array(
    CURLOPT_URL => "http://" . $daemonHost . ":" . $daemonPort . $path,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
      "cache-control: no-cache"
    )
  ));

  $response = curl_exec($curl);
  $err = curl_error($curl);
  curl_close($curl);
  $response = json_decode($response);
  return $response;
}

function walletrpc_post($method, $params = NULL) {
  global $walletHost, $walletPort, $walletPassword;
  if (is_null($params)) {
    $params = (Object) Array();
  }
  $curl = curl_init();
  $fields = array("jsonrpc" => "2.0",
                  "method" => $method,
                  "password" => $walletPassword,
                  "params" => $params,
                  "id" => "1");
  $fields = json_encode((object) $fields);
  curl_setopt_array($curl, array(
    CURLOPT_URL => "http://" . $walletHost . ":" . $walletPort . "/json_rpc",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => $fields,
    CURLOPT_HTTPHEADER => array(
      "cache-control: no-cache"
    )
  ));

  $response = curl_exec($curl);
  $err = curl_error($curl);

  curl_close($curl);
  $response = json_decode($response);
  if (array_key_exists('result', $response)) {
    return $response->result;
  } else {
    return $response;
  }
}
//echo "Leaving lib/daemon.php<br>";
?>
