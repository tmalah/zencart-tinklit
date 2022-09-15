<?php

//require_once 'tinklit_options.php';

function tinklitCurl($url, $client_id, $token, $post = false) {
	global $tinklitOptions;	
		
	$curl = curl_init($url);
	$length = 0;
	if ($post)
	{	
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
		$length = strlen($post);
	}
            
    $header = array ("Content-Type: application/json", "X-CLIENT-ID: {$client_id}", "X-AUTH-TOKEN: {$token}");

	curl_setopt($curl, CURLOPT_PORT, 443);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1); // verify certificate
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // check existence of CN and verify that it matches hostname
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_FORBID_REUSE, 1);
	curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
		
	$responseString = curl_exec($curl);
	
	if($responseString == false) {
		$response = curl_error($curl);
	} else {
		$response = json_decode($responseString, true);
	}
	curl_close($curl);
	return $response;
}

// Call from your notification handler to convert $_POST data to an object containing invoice data
function tinklitVerifyNotification($guid) {


}

// $options can include ('apiKey')
function bpGetInvoice($invoiceId, $apiKey=false) {
	
}

if (!function_exists('zen_remove_order')) { 
    function zen_remove_order($order_id, $restock = false) {
    global $db;
    if ($restock == 'on') {
      $order = $db->Execute("select products_id, products_quantity
                             from " . TABLE_ORDERS_PRODUCTS . "
                             where orders_id = '" . (int)$order_id . "'");

      while (!$order->EOF) {
        $db->Execute("update " . TABLE_PRODUCTS . "
                      set products_quantity = products_quantity + " . $order->fields['products_quantity'] . ", products_ordered = products_ordered - " . $order->fields['products_quantity'] . " where products_id = '" . (int)$order->fields['products_id'] . "'");
        $order->MoveNext();
      }
    }

    $db->Execute("delete from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id . "'");
    $db->Execute("delete from " . TABLE_ORDERS_PRODUCTS . "
                  where orders_id = '" . (int)$order_id . "'");

    $db->Execute("delete from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . "
                  where orders_id = '" . (int)$order_id . "'");

    $db->Execute("delete from " . TABLE_ORDERS_PRODUCTS_DOWNLOAD . "
                  where orders_id = '" . (int)$order_id . "'");

    $db->Execute("delete from " . TABLE_ORDERS_STATUS_HISTORY . "
                  where orders_id = '" . (int)$order_id . "'");

    $db->Execute("delete from " . TABLE_ORDERS_TOTAL . "
                  where orders_id = '" . (int)$order_id . "'");

    $db->Execute("delete from " . TABLE_COUPON_GV_QUEUE . "
                  where order_id = '" . (int)$order_id . "' and release_flag = 'N'");
  } 
}

?>
