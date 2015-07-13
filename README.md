<h3>First Data<br>
Payment Module for osCommerce 2.3</h3>
Released under the GNU General Public License

=============================================================

<h3>General Notes</h3><br>
<br>
First Data Payment Module is developed by Endri Prifti.<br>
This document with instructions is written by Endri Prifti.<br>

=============================================================

First, make sure:<br>
• Install a CLEAN copy of osCommerce 2.x, (Version 2.3 recommended)<br>
• Delete other currencies.<br>
• You should have an account with a Bank that uses First Data ECOMM system.<br>
• Update the links on FirstData ECOMM panel.<br>
For returnOkUrl use, for example: <pre>http://localhost:9999/catalog/ext/modules/payment/firstdata/firstdata_callback.php</pre><br>
For returnFailUrl use, for example: <pre>http://localhost:9999/catalog/ext/modules/payment/firstdata/firstdata_callback_fail.php</pre>

=============================================================

Notes:<br>
• Every end of day (at 23:59, but not necessary), the operator (administrator) of osCommerce 2.x should perform the Bussiness Day Closing in order to receive the money in his account at his Bank which operates with First Data Latvia.

=============================================================

Copy the content of the "FirstData" folder to "catalog" folder of osCommerce installation.

=============================================================

Modify the following files:

1. ..\catalog\admin\orders.php<br>
around line 70<br>
find <pre>"case 'deleteconfirm':</pre>"<br>
substitute all the case with this:<br>
<pre>
      case 'deleteconfirm':
    	require_once(DIR_FS_CATALOG . 'ext/modules/payment/firstdata/Merchant.php');

        $oID = tep_db_prepare_input($HTTP_GET_VARS['oID']);

		$result = tep_db_query("SELECT trans_id, amount FROM firstdata_transaction WHERE firstdata_order_id=" . $oID . " AND result='OK'");

		if(tep_db_num_rows($result) >= 1) {
			$result		= tep_db_fetch_array($result);

			$trans_id	= urlencode($result['trans_id']);
			$amount		= $result['amount'] * 100;

			$merchant	= new Merchant(	MODULE_PAYMENT_FIRSTDATA_URL_SERVER_HANDLER, 
										MODULE_PAYMENT_FIRSTDATA_KEYSTORE,
										MODULE_PAYMENT_FIRSTDATA_PASSPHRASE,
										1);
			$resp		= $merchant->reverse($trans_id, $amount);

			if(substr($resp,8,2) == "OK" OR substr($resp,8,8) == "REVERSED") {
				tep_remove_order($oID, $HTTP_POST_VARS['restock']);

				$result_token = explode(' ', $resp);

				tep_db_query("UPDATE firstdata_transaction SET reversal_amount='" . ($amount/100) . "', response='" . addslashes($resp) . "', result_code='" . $result_token[2] . "', result='REVERSED' WHERE trans_id='" . urldecode($trans_id) . "'");
				
				$messageStack->add_session(SUCCESS_ORDER_DELETED_AMOUNT_REVERSED, 'success');
			}
			else $messageStack->add_session(ERROR_ORDER_CANT_BE_DELETED_AMOUNT_CANT_BE_REVERSED, 'error');
		}
		else tep_remove_order($oID, $HTTP_POST_VARS['restock']);

		tep_redirect(tep_href_link(FILENAME_ORDERS, tep_get_all_get_params(array('oID', 'action'))));
        break;
</pre>
----------------------------------------------------------------------------------------

2. ..\catalog\admin\includes\languages\english.php<br>
	around line 85, add: <pre>define('BOX_CUSTOMERS_BUSINESS_DAY_CLOSING', 'Business Day Closing');</pre><br>
	around line 197, add: <pre>define('IMAGE_CLOSE_DAY', 'Close Day');</pre>

----------------------------------------------------------------------------------------

3. ..\catalog\admin\includes\filenames.php<br>
	around line 19, add: <pre>define('FILENAME_BUSINESS_DAY_CLOSING', 'business_day_closing.php');</pre>

----------------------------------------------------------------------------------------

4. ..\catalog\admin\includes\languages\english\orders.php<br>
around line 80, add:<br><pre>
	define('ERROR_ORDER_CANT_BE_DELETED_AMOUNT_CANT_BE_REVERSED', 'Error: Can NOT delete order because payment can NOT be reverted.');
	define('SUCCESS_ORDER_DELETED_AMOUNT_REVERSED', 'Success: Order has been successfully deleted and payment reverted.');</pre>

----------------------------------------------------------------------------------------

5. ..\catalog\admin\includes\boxes\customers.php<br>
around line 25, add:<br>
<pre>
      ,array(
        'code' => FILENAME_BUSINESS_DAY_CLOSING,
        'title' => BOX_CUSTOMERS_BUSINESS_DAY_CLOSING,
        'link' => tep_href_link(FILENAME_BUSINESS_DAY_CLOSING)
      )</pre>

=============================================================

Enter osCommerce Administrator's Panel, go to Modules > Payment.<br>
Install, if it isn't already installed.<br>
Enable First Data Payment Module, if it isn't already.<br>
Edit First Data Payment Module, and put the required information.
<br>
Put the path where Keystore is located, (just use single slashes, as in the example)<br>
e.g.: <pre>C:/xampp/htdocs/eshop/ext/modules/payment/firstdata/keystore.pem</pre>
<br>
Put the keystore Passphrase.
<br>
For the test environment:<br>
Put on Handler Server URL: <pre>https://secureshop-test.firstdata.lv:8443/ecomm/MerchantHandler</pre><br>
Put on Handler Client URL: <pre>https://secureshop-test.firstdata.lv/ecomm/ClientHandler</pre>
<br>
For the production environment:<br>
Put on Handler Server URL: <pre>https://secureshop.firstdata.lv:8443/ecomm/MerchantHandler</pre><br>
Put on Handler Client URL: <pre>https://secureshop.firstdata.lv/ecomm/ClientHandler</pre>
<br>
For the other fields, don't change anything.
