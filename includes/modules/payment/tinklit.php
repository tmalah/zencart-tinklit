<?php

/**
 * @package paymentMethod
 */
class tinklit {
  var $code, $title, $description, $enabled, $payment;
  
  function log($contents){
    error_log($contents);
  }
  
  // class constructor
  function tinklit() {
    global $order;
    $this->code = 'tinklit';
    $this->title = MODULE_PAYMENT_TINKLIT_TEXT_TITLE;
    $this->description = MODULE_PAYMENT_TINKLIT_TEXT_DESCRIPTION;
    $this->sort_order = MODULE_PAYMENT_TINKLIT_SORT_ORDER;
    $this->enabled = ((MODULE_PAYMENT_TINKLIT_STATUS == 'True') ? true : false);

    if ((int)MODULE_PAYMENT_TINKLIT_ORDER_STATUS_ID > 0) {
        $this->order_status = MODULE_PAYMENT_TINKLIT_ORDER_STATUS_ID;
    } else {
        $this->order_status = DEFAULT_ORDERS_STATUS_ID;
    }

    if (is_object($order)) $this->update_status();
    
  }

  // class methods
  function update_status() { 
    global $db;
    global $order;

    // check zone
    if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_TINKLIT_ZONE > 0) ) {
      $check_flag = false;
      $check = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . intval(MODULE_PAYMENT_TINKLIT_ZONE) . "' and zone_country_id = '" . intval($order->billing['country']['id']) . "' order by zone_id");
      while (!$check->EOF) {
        if ($check->fields['zone_id'] < 1) {
          $check_flag = true;
          break;
        } elseif ($check->fields['zone_id'] == $order->billing['zone_id']) {
          $check_flag = true;
          break;
        }
        $check->MoveNext();
      }

      if ($check_flag == false) { 
        $this->enabled = false;
      }
    }

  }

  function javascript_validation() {
    return false;
  }

  function selection() { 
    return array('id' => $this->code,
                 'module' => $this->title);
  }

  function pre_confirmation_check() {
    return false;
  }

  // called upon requesting step 3
  function confirmation() {
    return false;
  }
  
  // called upon requesting step 3 (after confirmation above)
  function process_button() {    
    return false;
  }

  // called upon clicking confirm
  function before_process() {
    return false; 
  }

  // called upon clicking confirm (after before_process and after the order is created)
  function after_process() {
    global $insert_id, $order, $db, $currencies;
    require_once 'tinklit/tinklit_lib.php';          
        
    // change order status to value selected by merchant
    $db->Execute("update ". TABLE_ORDERS. " set orders_status = " . $this->order_status . " where orders_id = ". intval($insert_id));
        
    
    $options = array(
        'price' => $currencies->rateAdjusted($order->info['total']), //$order->info['total'],
        'currency' => 'EUR', 
        'order_id' => $insert_id,
        'notification_url' => HTTP_SERVER.DIR_WS_CATALOG.'tinklit_callback.php',
        'redirect_url' => zen_href_link(FILENAME_ACCOUNT, 'tinklit=update_status'));
    //echo '<pre>'; print_r($options); echo '</pre>'; exit();
    $post = json_encode($options);
	
    //  create invoice
	//$invoice = tinklitCurl('https://api-staging.tinkl.it/v1/invoices', MODULE_PAYMENT_TINKLIT_CLIENTID, MODULE_PAYMENT_TINKLIT_TOKEN, $post);
    $invoice = tinklitCurl('https://api.tinkl.it/v1/invoices', MODULE_PAYMENT_TINKLIT_CLIENTID, MODULE_PAYMENT_TINKLIT_TOKEN, $post);
      //echo '<pre>'; print_r($invoice); echo '</pre>'; exit();
    if (!is_array($invoice) or array_key_exists('error', $invoice)) {
      $this->log('createInvoice error '.var_export($invoice['error'], true));
      zen_remove_order($insert_id, $restock = true);
      // unfortunately, there's not a good way of telling the customer that it's hosed.  Their cart is still full so they can try again w/ a different payment option.
    } else {
        
        $insert_sql = "INSERT INTO ".TABLE_TINKLIT."
                        (order_id, guid, status, btc_price, invoice_time, payment_confidence, time_created)
                        VALUES ('".$invoice['order_id']."', '".$invoice['guid']."', '".$invoice['status']."', '".$invoice['btc_price']."', '".$invoice['invoice_time']."', '".$invoice['payment_confidence']."', now())";
        $db->execute($insert_sql);
        
      $_SESSION['cart']->reset(true);
      zen_redirect($invoice['url']);
    }

    return false;
  }

  function get_error() {
    return false;
  }

  function check() {
    global $db;

    if (!isset($this->_check)) {
      $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_TINKLIT_STATUS'");
      $this->_check = $check_query->RecordCount();
    }

    return $this->_check;
  }

  function install() {
      global $db, $messageStack;
      if (defined('MODULE_PAYMENT_TINKLIT_STATUS')) {
        $messageStack->add_session('TINKLIT module already installed.', 'error');
        zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=TINKLIT', 'NONSSL'));
        return 'failed';
      }
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Check/Money Order Module', 'MODULE_PAYMENT_TINKLIT_STATUS', 'True', 'Do you want to accept TINKLIT payments?', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now());");
      
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Client ID', 'MODULE_PAYMENT_TINKLIT_CLIENTID', 'CLIENTID', '', '6', '0', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Token', 'MODULE_PAYMENT_TINKLIT_TOKEN', 'TOKEN', '', '6', '0', now())");
      //$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Password', 'MODULE_PAYMENT_TINKLIT_PASSWORD', 'PASSWORD', '', '6', '0', now())");
      //$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Mode', 'MODULE_PAYMENT_TINKLIT_MODE', 'test', '(test/live)', '6', '1', 'zen_cfg_select_option(array(\'test\', \'live\'), ', now());");

      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_TINKLIT_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_TINKLIT_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
      $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_TINKLIT_ORDER_STATUS_ID', '0', 'Set the status of orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
      
    }

    function remove() {
      global $db;
      $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys() {
      return array('MODULE_PAYMENT_TINKLIT_STATUS', 'MODULE_PAYMENT_TINKLIT_ZONE', 'MODULE_PAYMENT_TINKLIT_ORDER_STATUS_ID', 'MODULE_PAYMENT_TINKLIT_SORT_ORDER', 'MODULE_PAYMENT_TINKLIT_CLIENTID', 'MODULE_PAYMENT_TINKLIT_TOKEN'/*, 'MODULE_PAYMENT_TINKLIT_PASSWORD', 'MODULE_PAYMENT_TINKLIT_MODE'*/);
    }
}
