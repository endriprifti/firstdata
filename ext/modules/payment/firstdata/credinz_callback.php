<?php
/*
  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License


  First Data Payment Module for osCommerce 2.3
  Developed by Endri Prifti, March 31, 2013
*/

  require('Merchant.php');
  chdir('../../../../');
  require('includes/application_top.php');
  include(DIR_WS_LANGUAGES . $language . '/modules/payment/firstdata.php');

  if (isset($HTTP_POST_VARS['trans_id']) && !empty($HTTP_POST_VARS['trans_id'])) {

  $merchant	= new Merchant(	MODULE_PAYMENT_FIRSTDATA_URL_SERVER_HANDLER,
								MODULE_PAYMENT_FIRSTDATA_KEYSTORE,
								MODULE_PAYMENT_FIRSTDATA_PASSPHRASE,
								1);

	$resp		= $merchant -> getTransResult(urlencode($HTTP_POST_VARS['trans_id']), $HTTP_POST_VARS['ip']);

	$resp = preg_split("/\s/", $resp);


	$is_transaction_verified = false;


	if($resp[1] !== 'OK') {
		$sql_data_array = array('result'			=> $resp[1],
								'result_code'		=> $resp[3],
								'result_3dsecure'	=> $resp[5],
								'card_number'		=> $resp[11],
								'response'			=> implode(' ', $resp));
	
		tep_db_perform('firstdata_transaction', $sql_data_array, 'update', 'trans_id="' . $HTTP_POST_VARS['trans_id'] . '"');

		tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . MODULE_PAYMENT_FIRSTDATA_FAILED_STATUS_ID . "', last_modified = now() where orders_id = '" . (int)$HTTP_POST_VARS['order_id'] . "'");

		$sql_data_array = array('orders_id'			=> $HTTP_POST_VARS['order_id'],
								'orders_status_id'	=> MODULE_PAYMENT_FIRSTDATA_FAILED_STATUS_ID,
								'date_added'		=> 'now()',
								'customer_notified'	=> '0',
								'comments'			=> 'First Data: Transaction NOT Valid');

		tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);


		tep_session_register('is_transaction_verified');


		tep_redirect(
					tep_href_link(	
									FILENAME_CHECKOUT_PAYMENT, 
									'payment_error=firstdata&error=' . urlencode(MODULE_PAYMENT_FIRSTDATA_ERROR_TRANS_NOT_SUCCEEDED), 
									'SSL'
					)
		);
		exit();
	}


	if (isset($HTTP_POST_VARS['hash'])	&& 
		!empty($HTTP_POST_VARS['hash'])	&& 
		($HTTP_POST_VARS['hash'] 		== md5(	MODULE_PAYMENT_FIRSTDATA_PASSPHRASE	.
												$HTTP_POST_VARS['session_id']		.
												$HTTP_POST_VARS['trans_id']			.
												$HTTP_POST_VARS['customer_id']		.
												$HTTP_POST_VARS['order_id']			.
												$HTTP_POST_VARS['amount']			.
												$HTTP_POST_VARS['ip']				))) {

		$sql_data_array = array('result'			=> $resp[1],
								'result_code'		=> $resp[3],
								'result_3dsecure'	=> $resp[5],
								'card_number'		=> $resp[11],
								'response'			=> implode(' ', $resp));
	
		tep_db_perform('firstdata_transaction', $sql_data_array, 'update', 'trans_id="' . $HTTP_POST_VARS['trans_id'] . '"');


        $order_query = tep_db_query("select orders_status, currency, currency_value from " . TABLE_ORDERS . " where orders_id = '" . (int)$HTTP_POST_VARS['order_id'] . "' and customers_id = '" . (int)$HTTP_POST_VARS['customer_id'] . "'");
        if (tep_db_num_rows($order_query) > 0) {
          $order = tep_db_fetch_array($order_query);

          if ($order['orders_status'] == MODULE_PAYMENT_FIRSTDATA_PREPARE_ORDER_STATUS_ID) {
            tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . MODULE_PAYMENT_FIRSTDATA_WAITING_STATUS_ID . "', last_modified = now() where orders_id = '" . (int)$HTTP_POST_VARS['order_id'] . "'");

            $sql_data_array = array('orders_id'			=> $HTTP_POST_VARS['order_id'],
                                    'orders_status_id'	=> MODULE_PAYMENT_FIRSTDATA_PREPARE_ORDER_STATUS_ID,
                                    'date_added'		=> 'now()',
                                    'customer_notified'	=> '0',
                                    'comments'			=> '');

            tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

            $sql_data_array = array('orders_id'			=> $HTTP_POST_VARS['order_id'],
                                    'orders_status_id'	=> MODULE_PAYMENT_FIRSTDATA_WAITING_STATUS_ID,
                                    'date_added'		=> 'now()',
                                    'customer_notified'	=> '0',
                                    'comments'			=> 'First Data: Transaction Verified');

            tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);


			$is_transaction_verified = true;
			tep_session_register('is_transaction_verified');


			tep_redirect(
						tep_href_link(
										FILENAME_CHECKOUT_PROCESS,
										tep_session_name() . '=' . $HTTP_POST_VARS['session_id'],
										'SSL',
										false
									  )
						);
          }
        }
      }
	  else {
		$sql_data_array = array('result'			=> $resp[1],
								'result_code'		=> $resp[3],
								'result_3dsecure'	=> $resp[5],
								'card_number'		=> $resp[11],
								'response'			=> "INVALID HASH");

		tep_db_perform('firstdata_transaction', $sql_data_array, 'update', 'trans_id="' . $HTTP_POST_VARS['trans_id'] . '"');

		tep_redirect(
					tep_href_link(	
									FILENAME_CHECKOUT_PAYMENT, 
									'payment_error=firstdata&error=' . urlencode(MODULE_PAYMENT_FIRSTDATA_HACK_ATTEMPTED), 
									'SSL'
					)
		);

	  }
    }
	else {
		tep_redirect(
					tep_href_link(	
									FILENAME_CHECKOUT_PAYMENT, 
									'payment_error=firstdata&error=' . urlencode(MODULE_PAYMENT_FIRSTDATA_NO_TRANS_ID),
									'SSL'
					)
		);
	}
?>
