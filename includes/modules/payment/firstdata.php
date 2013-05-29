<?php
/*
  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License


  First Data Bank Payment Module for osCommerce 2.3
  Developed by Endri Prifti, March 31, 2013
*/

  class FirstData {
    var $code, $title, $description, $enabled;

    function FirstData() {
      global $order;

      $this->signature = 'firstdata|sms|1.0';
      $this->api_version = '1.0';

      $this->code        = 'firstdata';
      $this->title       = MODULE_PAYMENT_FIRSTDATA_TEXT_TITLE;
      $this->description = MODULE_PAYMENT_FIRSTDATA_TEXT_DESCRIPTION;
      $this->sort_order  = MODULE_PAYMENT_FIRSTDATA_SORT_ORDER;
      $this->enabled     = ((MODULE_PAYMENT_FIRSTDATA_STATUS == 'True') ? true : false);

      if ((int)MODULE_PAYMENT_FIRSTDATA_PREPARE_ORDER_STATUS_ID > 0) {
        $this->order_status = MODULE_PAYMENT_FIRSTDATA_PREPARE_ORDER_STATUS_ID;
      }

      if (is_object($order)) $this->update_status();

      $this->form_action_url = MODULE_PAYMENT_FIRSTDATA_URL_CLIENT_HANDLER;
    }

    function update_status() {
      global $order;

      if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_FIRSTDATA_ZONE > 0) ) {
        $check_flag = false;
        $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_FIRSTDATA_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
        while ($check = tep_db_fetch_array($check_query)) {
          if ($check['zone_id'] < 1) {
            $check_flag = true;
            break;
          } elseif ($check['zone_id'] == $order->billing['zone_id']) {
            $check_flag = true;
            break;
          }
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
      global $cart_Firstdata_ID;

      if (tep_session_is_registered('cart_Firstdata_ID')) {
        $order_id = substr($cart_Firstdata_ID, strpos($cart_Firstdata_ID, '-')+1);

        $check_query = tep_db_query('select orders_id from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '" limit 1');

        if (tep_db_num_rows($check_query) < 1) {
          tep_db_query('delete from ' . TABLE_ORDERS  				 . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_TOTAL				 . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_STATUS_HISTORY		 . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS			 . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' where orders_id = "' . (int)$order_id . '"');
          tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD	 . ' where orders_id = "' . (int)$order_id . '"');

          tep_session_unregister('cart_Firstdata_ID');
        }
      }

      return array(	'id'		=> $this->code, 
	  				'module'	=> $this->title, 
					'fields'	=> array(array('title' => '', 'field' => MODULE_PAYMENT_FIRSTDATA_TEXT_PUBLIC_HTML)));
    }

    function pre_confirmation_check() {
      global $cartID, $cart;

      if (empty($cart->cartID)) {
        $cartID = $cart->cartID = $cart->generate_cart_id();
      }

      if (!tep_session_is_registered('cartID')) {
        tep_session_register('cartID');
      }
    }

    function confirmation() {
      global $cartID, $cart_Firstdata_ID, $customer_id, $languages_id, $order, $order_total_modules;

      if (tep_session_is_registered('cartID')) {
        $insert_order = false;

        if (tep_session_is_registered('cart_Firstdata_ID')) {
          $order_id = substr($cart_Firstdata_ID, strpos($cart_Firstdata_ID, '-')+1);

          $curr_check = tep_db_query("select currency from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id . "'");
          $curr = tep_db_fetch_array($curr_check);

          if ( ($curr['currency'] != $order->info['currency']) || ($cartID != substr($cart_Firstdata_ID, 0, strlen($cartID))) ) {
            $check_query = tep_db_query('select orders_id from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '" limit 1');

            if (tep_db_num_rows($check_query) < 1) {
              tep_db_query('delete from ' . TABLE_ORDERS                     . ' where orders_id = "' . (int)$order_id . '"');
              tep_db_query('delete from ' . TABLE_ORDERS_TOTAL               . ' where orders_id = "' . (int)$order_id . '"');
              tep_db_query('delete from ' . TABLE_ORDERS_STATUS_HISTORY      . ' where orders_id = "' . (int)$order_id . '"');
              tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS            . ' where orders_id = "' . (int)$order_id . '"');
              tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' where orders_id = "' . (int)$order_id . '"');
              tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD   . ' where orders_id = "' . (int)$order_id . '"');
            }

            $insert_order = true;
          }
        } else $insert_order = true;

        if ($insert_order == true) {
          $order_totals = array();
          if (is_array($order_total_modules->modules)) {
            reset($order_total_modules->modules);
            while (list(, $value) = each($order_total_modules->modules)) {
              $class = substr($value, 0, strrpos($value, '.'));
              if ($GLOBALS[$class]->enabled) {
                for ($i=0, $n=sizeof($GLOBALS[$class]->output); $i<$n; $i++) {
                  if (tep_not_null($GLOBALS[$class]->output[$i]['title']) && tep_not_null($GLOBALS[$class]->output[$i]['text'])) {
                    $order_totals[] = array('code'			=> $GLOBALS[$class]->code,
                                            'title'			=> $GLOBALS[$class]->output[$i]['title'],
                                            'text'			=> $GLOBALS[$class]->output[$i]['text'],
                                            'value'			=> $GLOBALS[$class]->output[$i]['value'],
                                            'sort_order'	=> $GLOBALS[$class]->sort_order);
                  }
                }
              }
            }
          }

          $sql_data_array = array('customers_id'				=> $customer_id,
                                  'customers_name'				=> $order->customer['firstname'] . ' ' . $order->customer['lastname'],
                                  'customers_company'			=> $order->customer['company'],
                                  'customers_street_address'	=> $order->customer['street_address'],
                                  'customers_suburb'			=> $order->customer['suburb'],
                                  'customers_city'				=> $order->customer['city'],
                                  'customers_postcode'			=> $order->customer['postcode'],
                                  'customers_state'				=> $order->customer['state'],
                                  'customers_country'			=> $order->customer['country']['title'],
                                  'customers_telephone'			=> $order->customer['telephone'],
                                  'customers_email_address'		=> $order->customer['email_address'],
                                  'customers_address_format_id'	=> $order->customer['format_id'],
                                  'delivery_name'				=> $order->delivery['firstname'] . ' ' . $order->delivery['lastname'],
                                  'delivery_company'			=> $order->delivery['company'],
                                  'delivery_street_address'		=> $order->delivery['street_address'],
                                  'delivery_suburb'				=> $order->delivery['suburb'],
                                  'delivery_city'				=> $order->delivery['city'],
                                  'delivery_postcode'			=> $order->delivery['postcode'],
                                  'delivery_state'				=> $order->delivery['state'],
                                  'delivery_country'			=> $order->delivery['country']['title'],
                                  'delivery_address_format_id'	=> $order->delivery['format_id'],
                                  'billing_name'				=> $order->billing['firstname'] . ' ' . $order->billing['lastname'],
                                  'billing_company'				=> $order->billing['company'],
                                  'billing_street_address'		=> $order->billing['street_address'],
                                  'billing_suburb'				=> $order->billing['suburb'],
                                  'billing_city'				=> $order->billing['city'],
                                  'billing_postcode'			=> $order->billing['postcode'],
                                  'billing_state'				=> $order->billing['state'],
                                  'billing_country'				=> $order->billing['country']['title'],
                                  'billing_address_format_id'	=> $order->billing['format_id'],
                                  'payment_method'				=> $order->info['payment_method'],
                                  'cc_type'						=> $order->info['cc_type'],
                                  'cc_owner'					=> $order->info['cc_owner'],
                                  'cc_number'					=> $order->info['cc_number'],
                                  'cc_expires'					=> $order->info['cc_expires'],
                                  'date_purchased'				=> 'now()',
                                  'orders_status'				=> $order->info['order_status'],
                                  'currency'					=> $order->info['currency'],
                                  'currency_value'				=> $order->info['currency_value']);

          tep_db_perform(TABLE_ORDERS, $sql_data_array);

          $insert_id = tep_db_insert_id();

          for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
            $sql_data_array = array('orders_id'			=> $insert_id,
                                    'title'				=> $order_totals[$i]['title'],
                                    'text'				=> $order_totals[$i]['text'],
                                    'value'				=> $order_totals[$i]['value'],
                                    'class'				=> $order_totals[$i]['code'],
                                    'sort_order'		=> $order_totals[$i]['sort_order']);

            tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
          }

          for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
            $sql_data_array = array('orders_id'			=> $insert_id,
                                    'products_id'		=> tep_get_prid($order->products[$i]['id']),
                                    'products_model'	=> $order->products[$i]['model'],
                                    'products_name'		=> $order->products[$i]['name'],
                                    'products_price'	=> $order->products[$i]['price'],
                                    'final_price'		=> $order->products[$i]['final_price'],
                                    'products_tax'		=> $order->products[$i]['tax'],
                                    'products_quantity'	=> $order->products[$i]['qty']);

            tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);

            $order_products_id = tep_db_insert_id();

            $attributes_exist = '0';
            if (isset($order->products[$i]['attributes'])) {
              $attributes_exist = '1';
              for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
                if (DOWNLOAD_ENABLED == 'true') {
                  $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                                       from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                       left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                       on pa.products_attributes_id=pad.products_attributes_id
                                       where pa.products_id = '" . $order->products[$i]['id'] . "'
                                       and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                       and pa.options_id = popt.products_options_id
                                       and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                       and pa.options_values_id = poval.products_options_values_id
                                       and popt.language_id = '" . $languages_id . "'
                                       and poval.language_id = '" . $languages_id . "'";
                  $attributes = tep_db_query($attributes_query);
                } else {
                  $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
                }
                $attributes_values = tep_db_fetch_array($attributes);

                $sql_data_array = array('orders_id'						=> $insert_id,
                                        'orders_products_id'			=> $order_products_id,
                                        'products_options'				=> $attributes_values['products_options_name'],
                                        'products_options_values'		=> $attributes_values['products_options_values_name'],
                                        'options_values_price'			=> $attributes_values['options_values_price'],
                                        'price_prefix'					=> $attributes_values['price_prefix']);

                tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

                if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && tep_not_null($attributes_values['products_attributes_filename'])) {
                  $sql_data_array = array('orders_id'					=> $insert_id,
                                          'orders_products_id'			=> $order_products_id,
                                          'orders_products_filename'	=> $attributes_values['products_attributes_filename'],
                                          'download_maxdays'			=> $attributes_values['products_attributes_maxdays'],
                                          'download_count'				=> $attributes_values['products_attributes_maxcount']);

                  tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
                }
              }
            }
          }

          $cart_Firstdata_ID = $cartID . '-' . $insert_id;
          tep_session_register('cart_Firstdata_ID');
        }
      }

      return false;
    }

    function process_button() {
      global $order, $currencies, $customer_id, $languages_id, $cart_Firstdata_ID, $trans_id, $ip;

		$order_id = substr($cart_Firstdata_ID, strpos($cart_Firstdata_ID, '-')+1);

		$amount			= number_format($order->info['total'], 2, '.', '');
      	$language		= tep_db_fetch_array(tep_db_query("SELECT code FROM " . TABLE_LANGUAGES . " WHERE languages_id = " . (int)$languages_id));
		$language		= $language['code'];
		$ip				= tep_get_ip_address();
		$description	=	'customer_id:'			. $customer_id							. ';' .
							'firstname:'			. $order->customer['firstname']			. ';' .
							'lastname:'				. $order->customer['lastname']			. ';' .
							'order_id:'				. $order_id								. ';' .
							'amount:'				. $amount;

		$trans_id = $this->get_transaction_id($amount, $description, $language, $ip, $order_id, $customer_id, $order->customer['firstname'], $order->customer['lastname']);

return	tep_draw_hidden_field('hash',			md5(MODULE_PAYMENT_FIRSTDATA_PASSPHRASE	.
													tep_session_id()					.
													$trans_id							.
													$customer_id						.
													$order_id							.
													$amount								.
													$ip	)								)
																	.
		tep_draw_hidden_field('session_id',		tep_session_id())	.
		tep_draw_hidden_field('trans_id',		$trans_id)			.
		tep_draw_hidden_field('customer_id',	$customer_id)		.
		tep_draw_hidden_field('order_id',		$order_id)			.

		tep_draw_hidden_field('amount',			$amount)			.
		tep_draw_hidden_field('ip',				$ip)				.
		tep_draw_hidden_field('language',		$language)			.
		tep_draw_hidden_field('description',	$description)		.
		tep_draw_hidden_field(tep_session_name(),	tep_session_id());

    }

    function before_process() {
      global $customer_id, $language, $order, $order_totals, $sendto, $billto, $languages_id, $payment, $currencies, 
	  		 $cart, $cart_Firstdata_ID, $is_transaction_verified;
      global $$payment;

      $order_id = substr($cart_Firstdata_ID, strpos($cart_Firstdata_ID, '-')+1);

      $sql_data_array = array('orders_id'			=> $order_id,
                              'orders_status_id'	=> (($is_transaction_verified===true) ? 
								  			(int)MODULE_PAYMENT_FIRSTDATA_WAITING_STATUS_ID : (int)MODULE_PAYMENT_FIRSTDATA_FAILED_STATUS_ID),
                              'date_added'			=> 'now()',
                              'customer_notified'	=> (SEND_EMAILS == 'true') ? '1' : '0',
                              'comments'			=> $order->info['comments']);

      tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

      $products_ordered = '';
      $subtotal = 0;
      $total_tax = 0;

      for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
        if (STOCK_LIMITED == 'true') {
          if (DOWNLOAD_ENABLED == 'true') {
            $stock_query_raw = "SELECT products_quantity, pad.products_attributes_filename
                                FROM " . TABLE_PRODUCTS . " p
                                LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                ON p.products_id=pa.products_id
                                LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                ON pa.products_attributes_id=pad.products_attributes_id
                                WHERE p.products_id = '" . tep_get_prid($order->products[$i]['id']) . "'";
            $products_attributes = $order->products[$i]['attributes'];
            if (is_array($products_attributes)) {
              $stock_query_raw .= " AND pa.options_id = '" . $products_attributes[0]['option_id'] . "' AND pa.options_values_id = '" . $products_attributes[0]['value_id'] . "'";
            }
            $stock_query = tep_db_query($stock_query_raw);
          } else {
            $stock_query = tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
          }
          if (tep_db_num_rows($stock_query) > 0 && $is_transaction_verified===true) {
            $stock_values = tep_db_fetch_array($stock_query);
            if ((DOWNLOAD_ENABLED != 'true') || (!$stock_values['products_attributes_filename'])) {
              $stock_left = $stock_values['products_quantity'] - $order->products[$i]['qty'];
            } else {
              $stock_left = $stock_values['products_quantity'];
            }
            tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . $stock_left . "' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
            if ( ($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false') ) {
              tep_db_query("update " . TABLE_PRODUCTS . " set products_status = '0' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
            }
          }
        }

        if($is_transaction_verified===true) tep_db_query("update " . TABLE_PRODUCTS . " set products_ordered = products_ordered + " . sprintf('%d', $order->products[$i]['qty']) . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");

        $attributes_exist = '0';
        $products_ordered_attributes = '';
        if (isset($order->products[$i]['attributes'])) {
          $attributes_exist = '1';
          for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
            if (DOWNLOAD_ENABLED == 'true') {
              $attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                                   from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                   left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                   on pa.products_attributes_id=pad.products_attributes_id
                                   where pa.products_id = '" . $order->products[$i]['id'] . "'
                                   and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                   and pa.options_id = popt.products_options_id
                                   and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                   and pa.options_values_id = poval.products_options_values_id
                                   and popt.language_id = '" . $languages_id . "'
                                   and poval.language_id = '" . $languages_id . "'";
              $attributes = tep_db_query($attributes_query);
            } else {
              $attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
            }
            $attributes_values = tep_db_fetch_array($attributes);

            $products_ordered_attributes .= "\n\t" . $attributes_values['products_options_name'] . ' ' . $attributes_values['products_options_values_name'];
          }
        }

        $total_weight	+= ($order->products[$i]['qty'] * $order->products[$i]['weight']);
        $total_tax		+= tep_calculate_tax($total_products_price, $products_tax) * $order->products[$i]['qty'];
        $total_cost		+= $total_products_price;

        $products_ordered .= $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . $products_ordered_attributes . "\n";
      }

      $email_order = STORE_NAME . "\n" .
                     EMAIL_SEPARATOR . "\n" .
                     EMAIL_TEXT_ORDER_NUMBER . ' ' . $order_id . "\n" .
                     EMAIL_TEXT_INVOICE_URL . ' ' . tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $order_id, 'SSL', false) . "\n" .
                     EMAIL_TEXT_DATE_ORDERED . ' ' . strftime(DATE_FORMAT_LONG) . "\n\n";
      if ($order->info['comments']) {
        $email_order .= tep_db_output($order->info['comments']) . "\n\n";
      }
      $email_order .= EMAIL_TEXT_PRODUCTS . "\n" .
                      EMAIL_SEPARATOR . "\n" .
                      $products_ordered .
                      EMAIL_SEPARATOR . "\n";

      for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
        $email_order .= strip_tags($order_totals[$i]['title']) . ' ' . strip_tags($order_totals[$i]['text']) . "\n";
      }

      if ($order->content_type != 'virtual') {
        $email_order .= "\n" . EMAIL_TEXT_DELIVERY_ADDRESS . "\n" .
                        EMAIL_SEPARATOR . "\n" .
                        tep_address_label($customer_id, $sendto, 0, '', "\n") . "\n";
      }

      $email_order .= "\n" . EMAIL_TEXT_BILLING_ADDRESS . "\n" .
                      EMAIL_SEPARATOR . "\n" .
                      tep_address_label($customer_id, $billto, 0, '', "\n") . "\n\n";

      if (is_object($$payment)) {
        $email_order .= EMAIL_TEXT_PAYMENT_METHOD . "\n" .
                        EMAIL_SEPARATOR . "\n";
        $payment_class = $$payment;
        $email_order .= $payment_class->title . "\n\n";
        if ($payment_class->email_footer) {
          $email_order .= $payment_class->email_footer . "\n\n";
        }
      }

      tep_mail($order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

      if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
        tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
      }

      $this->after_process();

      $cart->reset(true);

		tep_session_unregister('is_transaction_verified');

      tep_session_unregister('sendto');
      tep_session_unregister('billto');
      tep_session_unregister('shipping');
      tep_session_unregister('payment');
      tep_session_unregister('comments');

      tep_session_unregister('cart_Firstdata_ID');

      if($is_transaction_verified===true) tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
	  else tep_redirect(
					tep_href_link(	
									FILENAME_CHECKOUT_PAYMENT, 
									'payment_error=firstdata&error=' . urlencode(MODULE_PAYMENT_FIRSTDATA_NO_PAYMENT), 
									'SSL'
					)
		);
    }

    function after_process() {
      return false;
    }

    function get_error() {
      global $HTTP_GET_VARS;

      $error = array('title' => MODULE_PAYMENT_FIRSTDATA_ERROR_HEADING,
                     'error' => ((isset($HTTP_GET_VARS['error']) && tep_not_null($HTTP_GET_VARS['error'])) ? stripslashes(urldecode($HTTP_GET_VARS['error'])) : MODULE_PAYMENT_FIRSTDATA_ERROR_MESSAGE));

      return $error;
    }

    function check() {
      if(!isset($this->_check)) {
        $check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = '" . $this->keys(0) . "'");
        $this->_check = tep_db_num_rows($check_query);
      }
      return $this->_check;
    }

    function install() {
		$status_id				= $this->set_firstdata_order_statues("[FDL] Preparing Transaction");
		$status_delivered_id	= $this->set_firstdata_order_statues("[FDL] Delivered");
		$status_failed_id		= $this->set_firstdata_order_statues("[FDL] Failed Transaction");
		$status_waiting_id		= $this->set_firstdata_order_statues("[FDL] Paid, waiting delivery");

      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable First Data Module', '" . $this->keys(0) . "', 'True', 'Do you want to accept First Data payments?', '6', '3', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Keystore path', '" . $this->keys(1) . "', '" .  DIR_FS_CATALOG . "ext/modules/payment/firstdata/" . "', 'The keystore file is needed to connect the osCommerce from the merchant server with the payment gateway.<br />You need to give the full path.<br />Do put a single slash.', '6', '4', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Passphrase', '" . $this->keys(2) . "', '', 'Passphrase is a password that protects the Keystore file, also needed for the hash in each transaction.<br />Keep it secret.', '6', '4', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Handler Server URL', '" . $this->keys(3) . "', '', 'The  url that produces the transaction id.', '6', '4', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Handler Client URL', '" . $this->keys(4) . "', '', 'The url to be redirected to handle the credit & debit card details and the transaction finalisation.', '6', '4', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Currency ISO Code', '" . $this->keys(5) . "', '', 'The currency of the merchant\'s account.', '6', '4', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', '" . $this->keys(6) . "', '0', 'Sort order of display. Lowest is displayed first.', '6', '0', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', '" . $this->keys(7) . "', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
      tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', '" . $this->keys(8) . "', '', 'Set the status of orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
	  tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Preparing Order Status', '" . $this->keys(9) . "', '" . (int)$status_id . "', 'Set the status of prepared orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
	   tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Delivered Order Status', '" . $this->keys(10) . "', '" . (int)$status_delivered_id . "', 'Set the status of delivered orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
	   tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Failed Order Status', '" . $this->keys(11) . "', '" . (int)$status_failed_id . "', 'Set the status of failed orders made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
	   tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Waiting to be Delivered Order Status', '" . $this->keys(12) . "', '" . (int)$status_waiting_id . "', 'Set the status of orders that are waiting to be delivered made with this payment module to this value', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
	   tep_db_query(
		  "CREATE TABLE IF NOT EXISTS `firstdata_closing_business_day` (
			`close_date`			timestamp		NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			`result` 				text,
			`result_code` 			varchar(3)		DEFAULT NULL,
			`count_reversal`		bigint(10)		unsigned DEFAULT NULL,
			`count_transaction`		bigint(10)		unsigned DEFAULT NULL,
			`amount_reversal`		decimal(15,2)	DEFAULT NULL,
			`amount_transaction`	decimal(15,2)	DEFAULT NULL,
			`response`				mediumtext,
			PRIMARY KEY (`close_date`)
		  ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;");
	   tep_db_query(
		  "CREATE TABLE IF NOT EXISTS `firstdata_error` (
		  `id`						int(10)			NOT NULL AUTO_INCREMENT,
		  `error_time`				varchar(20) 	DEFAULT NULL,
		  `action`					varchar(20) 	DEFAULT NULL,
		  `response`				text,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;");
	   tep_db_query(
		  "CREATE TABLE IF NOT EXISTS `firstdata_transaction` (
		  `id`							int(10) 		NOT NULL AUTO_INCREMENT,
		  `trans_id`					varchar(50) 	DEFAULT NULL,
		  `amount`						decimal(15,2) 	DEFAULT NULL,
		  `currency`					char(3) 		DEFAULT NULL,
		  `client_ip_addr`				varchar(50) 	DEFAULT NULL,
		  `description`					longtext,
		  `firstdata_order_id`			int(11) 		NOT NULL,
		  `firstdata_customer_id` 		int(11) 		NOT NULL,
		  `firstdata_customer_name`		varchar(255)	NOT NULL,
		  `firstdata_customer_surname`	varchar(255)	NOT NULL,
		  `firstdata_timestamp` 			timestamp 		NOT NULL
														DEFAULT CURRENT_TIMESTAMP 
														ON UPDATE CURRENT_TIMESTAMP,
		  `language`					varchar(50) DEFAULT NULL,
		  `dms_ok`						varchar(50) DEFAULT NULL,
		  `result`						varchar(50) DEFAULT NULL,
		  `result_code`					varchar(50) DEFAULT NULL,
		  `result_3dsecure`				varchar(50) DEFAULT NULL,
		  `card_number`					varchar(50) DEFAULT NULL,
		  `t_date` 						varchar(20)	DEFAULT NULL,
		  `response`					text,
		  `reversal_amount`				decimal(15,2) 	DEFAULT NULL,
		  `makeDMS_amount`				int(10) 	DEFAULT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0;");
    }

	function set_firstdata_order_statues($order_status_name) {
      $check_query = tep_db_query("select orders_status_id from " . TABLE_ORDERS_STATUS . " where orders_status_name = '" . $order_status_name . "' limit 1");

      if (tep_db_num_rows($check_query) < 1) {
        $status_query = tep_db_query("select max(orders_status_id) as status_id from " . TABLE_ORDERS_STATUS);
        $status = tep_db_fetch_array($status_query);

        $status_id = $status['status_id']+1;

        $languages = tep_get_languages();

        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
          tep_db_query("insert into " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) values ('" . $status_id . "', '" . $languages[$i]['id'] . "', '" . $order_status_name . "')");
        }

        $flags_query = tep_db_query("describe " . TABLE_ORDERS_STATUS . " public_flag");
        if (tep_db_num_rows($flags_query) == 1) {
          tep_db_query("update " . TABLE_ORDERS_STATUS . " set public_flag = 0 and downloads_flag = 0 where orders_status_id = '" . $status_id . "'");
        }
      } else {
        $check = tep_db_fetch_array($check_query);

        $status_id = $check['orders_status_id'];
      }
	  return $status_id;
	}

    function remove() {
      tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    function keys($index = -1) {
		$array_of_keys = array(
			'MODULE_PAYMENT_FIRSTDATA_STATUS',			//0

			'MODULE_PAYMENT_FIRSTDATA_KEYSTORE',			//1
			'MODULE_PAYMENT_FIRSTDATA_PASSPHRASE',		//2
			'MODULE_PAYMENT_FIRSTDATA_URL_SERVER_HANDLER',//3
			'MODULE_PAYMENT_FIRSTDATA_URL_CLIENT_HANDLER',//4
			'MODULE_PAYMENT_FIRSTDATA_CURRENCY_ISO_CODE',	//5

			'MODULE_PAYMENT_FIRSTDATA_SORT_ORDER',		//6
			'MODULE_PAYMENT_FIRSTDATA_ZONE',				//7
			'MODULE_PAYMENT_FIRSTDATA_ORDER_STATUS_ID',	//8

			'MODULE_PAYMENT_FIRSTDATA_PREPARE_ORDER_STATUS_ID',	//9
			'MODULE_PAYMENT_FIRSTDATA_DELIVERED_STATUS_ID',		//10
			'MODULE_PAYMENT_FIRSTDATA_FAILED_STATUS_ID',			//11
			'MODULE_PAYMENT_FIRSTDATA_WAITING_STATUS_ID'			//12
		);

		if($index < 0) return $array_of_keys;
		else return $array_of_keys[$index];
    }

	private function get_config_value($index) {
		$query = "SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = '" . $this->keys($index) . "'";
		$result = tep_db_fetch_array(tep_db_query($query));
		return $result['configuration_value'];
	}

	private function get_transaction_id($amount, 
										$description, 
										$language, 
										$ip, 
										$firstdata_order_id, 
										$firstdata_customer_id, 
										$firstdata_customer_name,
										$firstdata_customer_surname) {

		require_once(DIR_FS_CATALOG . 'ext/modules/payment/firstdata/Merchant.php');

		$merchant	= new Merchant(	MODULE_PAYMENT_FIRSTDATA_URL_SERVER_HANDLER,
									MODULE_PAYMENT_FIRSTDATA_KEYSTORE,
									MODULE_PAYMENT_FIRSTDATA_PASSPHRASE,
									1);
		$resp		= $merchant->startSMSTrans($amount * 100, MODULE_PAYMENT_FIRSTDATA_CURRENCY_ISO_CODE, $ip, $description, $language);

		if(substr($resp,0,14) == "TRANSACTION_ID") {
			$trans_id	= substr($resp,16,28);

			tep_db_query("INSERT INTO firstdata_transaction 
						  SET trans_id					='" . $trans_id . "', 
						      amount					='" . $amount . "', 
							  currency					='" . MODULE_PAYMENT_FIRSTDATA_CURRENCY_ISO_CODE . "', 
							  client_ip_addr			='" . $ip . "', 
							  description				='" . $description . "', 
							  firstdata_order_id			='" . $firstdata_order_id . "', 
							  firstdata_customer_id		='" . $firstdata_customer_id . "', 
							  firstdata_customer_name		='" . $firstdata_customer_name . "', 
							  firstdata_customer_surname	='" . $firstdata_customer_surname . "', 
							  language					='" . $language . "', 
							  dms_ok					='---', 
							  result					='???', 
							  result_code				='???', 
							  result_3dsecure			='???', 
							  card_number				='???', 
							  t_date					=now(), 
							  response					='" . $resp . "',
							  reversal_amount			=DEFAULT,
							  makeDMS_amount			=DEFAULT");
		}
		else tep_db_query("INSERT INTO firstdata_error VALUES (NULL, now(), 'startsmstrans', '" . htmlentities($resp, ENT_QUOTES) . "')");

		return $trans_id;
	}
  }
?>
