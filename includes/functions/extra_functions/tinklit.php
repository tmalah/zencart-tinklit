<?php

function tinklit_update_status() { 

    global $db;
    
    require_once 'tinklit/tinklit_lib.php';

    $tinkl_info_sql = "SELECT configuration_value
                            FROM ".TABLE_CONFIGURATION."
                            WHERE configuration_key = 'MODULE_PAYMENT_TINKLIT_CLIENTID'";
    $tinkl_info = $db->execute($tinkl_info_sql);
    $clientid = $tinkl_info->fields['configuration_value'];
        
    $tinkl_info_sql = "SELECT configuration_value
                            FROM ".TABLE_CONFIGURATION."
                            WHERE configuration_key = 'MODULE_PAYMENT_TINKLIT_TOKEN'";
    $tinkl_info = $db->execute($tinkl_info_sql);
    $tokenid = $tinkl_info->fields['configuration_value'];
        
    $tinkl_orders_sql = "SELECT guid, order_id FROM ".TABLE_TINKLIT."
                            WHERE status = 'pending'
                            AND time_created > '".date('Y-m-d h:i:s', (date('U')-15*60))."'";
    //echo $tinkl_orders_sql; exit();
    $tinkl_orders = $db->execute($tinkl_orders_sql);
    
    while (!$tinkl_orders->EOF) {
        
        $guid = $tinkl_orders->fields['guid'];
        
        $options = array('guid' => $tinkl_info->fields['guid']);
    
        $post = json_encode($options);
        
        //  create invoice
    	//$invoice = tinklitCurl('https://api-staging.tinkl.it/v1/invoices', MODULE_PAYMENT_TINKLIT_CLIENTID, MODULE_PAYMENT_TINKLIT_TOKEN, $post);
        $invoice = tinklitCurl('https://api.tinkl.it/invoices/'.$guid, $clientid, $tokenid);
        
        //echo $guid;
        //echo '<pre>'; print_r($invoice); echo '</pre>'; //exit();
        
        if ($invoice['status'] == 'payed') {
            
            $get_status_sql = "SELECT orders_status FROM ".TABLE_ORDERS."
                                WHERE orders_id = '".$tinkl_orders->fields['order_id']."'";
            $get_status = $db->execute($get_status_sql);
            
            if ($get_status->fields['orders_status'] != '2') {
            
                $db->Execute("update " . TABLE_ORDERS . "
                                set orders_status = '2', last_modified = now()
                                where orders_id = '" . $tinkl_orders->fields['order_id'] . "'");
                                
                $db->Execute("insert into " . TABLE_ORDERS_STATUS_HISTORY . "
                              (orders_id, orders_status_id, date_added, customer_notified, comments)
                              values ('" . $tinkl_orders->fields['order_id'] . "',
                              '2',
                              now(),
                              '0',
                              'Order successfully payed')");
                              
                $db->Execute("update " . TABLE_TINKLIT . "
                                set status = 'payed'
                                where order_id = '" . $tinkl_orders->fields['order_id'] . "'");
            }
        }
        
        $tinkl_orders->MoveNext();
        
     }
}

?>
