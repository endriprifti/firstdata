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

	$merchant	= new Merchant(	MODULE_PAYMENT_FIRSTDATA_URL_SERVER_HANDLER,
								MODULE_PAYMENT_FIRSTDATA_KEYSTORE,
								MODULE_PAYMENT_FIRSTDATA_PASSPHRASE,
								1);

	$resp		= $merchant -> getTransResult(urlencode($REQUEST['trans_id']), $REQUEST['ip']);

	tep_db_query("INSERT INTO firstdata_error VALUES (NULL, now(), 'ReturnFailURL', 'error:" . $REQUEST['error'] . ";response:" . $resp. "')");

	tep_redirect(
				tep_href_link(
								FILENAME_CHECKOUT_PAYMENT, 
								'payment_error=firstdata&error=' . urlencode(MODULE_PAYMENT_FIRSTDATA_ERROR_CALLBACK_FAIL), 
								'SSL'
				)
	);

?>
