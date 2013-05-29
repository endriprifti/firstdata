<?php
/*
  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  First Data Payment Module for osCommerce 2.3
  Developed by Endri Prifti, March 31, 2013
*/

  require('includes/application_top.php');
	require(DIR_WS_INCLUDES . 'template_top.php');
	require_once(DIR_FS_CATALOG . 'ext/modules/payment/firstdata/Merchant.php');

	$action = (isset($HTTP_GET_VARS['action']) ? $HTTP_GET_VARS['action'] : '');

 	if(tep_not_null($action)) {
		switch($action) {
			case 'closebday':
				$merchant	= new Merchant(	MODULE_PAYMENT_FIRSTDATA_URL_SERVER_HANDLER,
											MODULE_PAYMENT_FIRSTDATA_KEYSTORE,
											MODULE_PAYMENT_FIRSTDATA_PASSPHRASE,
											1);
				$resp		= $merchant->closeDay(); 

				$response_array = preg_split('/[\s,]+/', $resp);

				tep_db_query("INSERT INTO `firstdata_closing_business_day`(`result`, `result_code`, `count_reversal`, `count_transaction`, `amount_reversal`, `amount_transaction`, `response`) VALUES ('" . $response_array[1] . "','" . $response_array[3] . "'," . $response_array[7] . "," . $response_array[9] . "," . ($response_array[15]/100) . "," . ($response_array[17]/100) . ",'" . $resp . "')");
				
				break;
		}
	}
?>

    <table border="0" width="100%" cellspacing="0" cellpadding="2">
      <tr>
        <td width="100%"><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE; ?></td>
            <td class="pageHeading" align="right"><?php echo tep_draw_separator('pixel_trans.gif', 1, HEADING_IMAGE_HEIGHT); ?></td>
            <td class="smallText" align="right"><?php 
	echo tep_draw_button(IMAGE_CLOSE_DAY, 'document', tep_href_link(FILENAME_BUSINESS_DAY_CLOSING, 'action=closebday'));
		  ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
          <tr>
            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent" align="left"><?php echo CLOSE_DATE; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo RESULT; ?></td>
                <td class="dataTableHeadingContent" align="center"><?php echo RESULT_CODE; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo COUNT_REVERSAL; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo COUNT_TRANSACTION; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo AMOUNT_REVERSAL; ?></td>
                <td class="dataTableHeadingContent" align="right"><?php echo AMOUNT_TRANSACTION; ?></td>
              </tr>
<?php
	$orders_query_raw = "select * from firstdata_closing_business_day order by close_date desc";
    $orders_split = new splitPageResults($HTTP_GET_VARS['page'], MAX_DISPLAY_SEARCH_RESULTS, $orders_query_raw, $orders_query_numrows);
	$orders_query = tep_db_query($orders_query_raw);

    while ($orders = tep_db_fetch_array($orders_query)) {
?>
		<tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">
			<td class="dataTableContent" align="left"><?php echo $orders['close_date']; ?></td>
			<td class="dataTableContent" align="center"><?php echo $orders['result']; ?></td>
			<td class="dataTableContent" align="center"><?php echo $orders['result_code']; ?></td>
			<td class="dataTableContent" align="right"><?php echo $orders['count_reversal']; ?></td>
			<td class="dataTableContent" align="right"><?php echo $orders['count_transaction']; ?></td>
			<td class="dataTableContent" align="right"><?php echo number_format($orders['amount_reversal'], 2); ?></td>
			<td class="dataTableContent" align="right"><?php echo number_format($orders['amount_transaction'], 2); ?></td>
		</tr>
<?php
	}
	
?>
        <tr>
            <td colspan="5"><table border="0" width="100%" cellspacing="0" cellpadding="2">
            <tr>
            <td class="smallText" valign="top"><?php echo $orders_split->display_count($orders_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $HTTP_GET_VARS['page'], TEXT_DISPLAY_NUMBER_OF_ORDERS); ?></td>
            <td class="smallText" align="right"><?php echo $orders_split->display_links($orders_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $HTTP_GET_VARS['page'], tep_get_all_get_params(array('page', 'oID', 'action'))); ?></td>
            </tr>
            </table></td>
            </tr>
            </table></td>
        </tr>
	</table>
<?php
	require(DIR_WS_INCLUDES . 'template_bottom.php');
 	require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
