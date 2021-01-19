<?php

	include_once("../includes/paytm/PaytmChecksum.php");
	include_once("../includes/paytm/PaytmHelper.php");
	$is_admin_path = true;
	$root_folder_path = "../";
	include_once ($root_folder_path ."includes/common.php");
	include_once ($root_folder_path ."includes/order_items.php");
	include_once ($root_folder_path ."messages/".$language_code."/cart_messages.php");
	include_once ($root_folder_path ."includes/parameters.php");
	
	$paytmChecksum = "";
	$paramList = array();
	$isValidChecksum = "FALSE";



	$vc = get_session("session_vc");
	$order_id = get_session("session_order_id");
	
	$order_errors = check_order($order_id, $vc);
	if($order_errors) {
		echo $order_errors;
		exit;
	}

	$post_parameters = ""; 
	$payment_params = array(); 
	$pass_parameters = array(); 
	$pass_data = array(); 
	$variables = array();


	$paramList = $_POST;
	$paytmChecksum = isset($_POST["CHECKSUMHASH"]) ? $_POST["CHECKSUMHASH"] : ""; //Sent by Paytm pg

    get_payment_parameters($order_id, $payment_params, $pass_parameters, $post_parameters, $pass_data, $variables, "");

	$merchant_key = get_setting_value($payment_params, "merchant_key", "");
	if(get_setting_value($payment_params, "live_mode", "") == 'yes') {
	$paytm_env = 1;
	}else{
	$paytm_env = 0;
	}

	//$isValidChecksum = verifychecksum_e($paramList, get_setting_value($payment_params, "merchant_key", ""), $paytmChecksum); 
    $isValidChecksum = PaytmChecksum::verifySignature($paramList, $merchant_key, $paytmChecksum);

	if($isValidChecksum == "TRUE" || $isValidChecksum == "true" || $isValidChecksum == "1" || $isValidChecksum="01") 
	{

		if ($_POST["STATUS"] == "TXN_SUCCESS") 
		{
			// Create an array having all required parameters for status query.
			$requestParamList = array("MID" => $_POST['MID'] , "ORDERID" => $_POST['ORDERID']);
	
			$paytmParamsStatus = array();

            /* body parameters */
            $paytmParamsStatus["body"] = array(
                /* Find your MID in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys */
                "mid" => $requestParamList['MID'],
                /* Enter your order id which needs to be check status for */
                "orderId" => $requestParamList['ORDERID'],
            );
            $checksumStatus = PaytmChecksum::generateSignature(json_encode($paytmParamsStatus["body"], JSON_UNESCAPED_SLASHES), $merchant_key);
            /* head parameters */
            $paytmParamsStatus["head"] = array(
                /* put generated checksum value here */
                "signature" => $checksumStatus
            );
            $post_data_status = json_encode($paytmParamsStatus, JSON_UNESCAPED_SLASHES);
            $responseStatusArray = PaytmHelper::executecUrl(PaytmHelper::getPaytmURL(PaytmConstants::ORDER_STATUS_URL, 0), $post_data_status);


			if($responseStatusArray['body']['resultInfo']['resultStatus'] == 'TXN_SUCCESS' && $responseStatusArray['body']['txnAmount'] == $_POST['TXNAMOUNT'])
			{			
				$success_message = $_POST['RESPMSG'];
				// update order information
				
				$sql  = " UPDATE " . $table_prefix . "orders ";
				$sql .= " SET success_message=" . $db->tosql($_POST["STATUS"], TEXT);
				$sql .= ", pending_message='', error_message='' ";
				if ($_POST["TXNID"]) {
					$sql .= ", transaction_id=" . $db->tosql($_POST["TXNID"], TEXT);
				}
				$sql .= " WHERE order_id=" . $db->tosql($order_id, INTEGER);
				$db->query($sql);

				// update order status
				if ($_POST["STATUS"]) {
					update_order_status($order_id, $_POST["STATUS"], true, "", $status_error);
				}
			}
			else{
				$error_message = 'It seems some issue in server to server communication. Kindly connect with administrator.';
				$status = 'TXN_FAILURE';
				// update order information
							
				$sql  = " UPDATE " . $table_prefix . "orders ";
				$sql .= " SET success_message=" . $db->tosql($status, TEXT);;
				$sql .= ", pending_message='' ";
				$sql .= ", error_message=" . $db->tosql($status, TEXT);;
				if ($_POST["TXNID"]) {
					$sql .= ", transaction_id=" . $db->tosql($_POST["TXNID"], TEXT);
				}
				$sql .= " WHERE order_id=" . $db->tosql($order_id, INTEGER);
				$db->query($sql);
				
				// update order status
				if ($status) {
					update_order_status($order_id, $status, true, "", $status_error);
				}
			}
		}
		else 
		{		
			$error_message = $_POST['RESPMSG'];
			// update order information
						
			$sql  = " UPDATE " . $table_prefix . "orders ";
			$sql .= " SET success_message=" . $db->tosql($_POST["STATUS"], TEXT);
			$sql .= ", pending_message='' ";
			$sql .= ", error_message=" . $db->tosql($_POST["STATUS"], TEXT);
			if ($_POST["TXNID"]) {
				$sql .= ", transaction_id=" . $db->tosql($_POST["TXNID"], TEXT);
			}
			$sql .= " WHERE order_id=" . $db->tosql($order_id, INTEGER);
			$db->query($sql);
			
			// update order status
			if ($_POST["STATUS"]) {
				update_order_status($order_id, $_POST["STATUS"], true, "", $status_error);
			}
		}
	}
	else 
	{
		$error_message = $_POST['RESPMSG'];
		// update order information
					
		$sql  = " UPDATE " . $table_prefix . "orders ";
		$sql .= " SET success_message=" . $db->tosql($_POST["STATUS"], TEXT);
		$sql .= ", pending_message='' ";
		$sql .= ", error_message=" . $db->tosql($_POST["STATUS"], TEXT);
		if ($_POST["TXNID"]) {
			$sql .= ", transaction_id=" . $db->tosql($_POST["TXNID"], TEXT);
		}
		$sql .= " WHERE order_id=" . $db->tosql($order_id, INTEGER);
		$db->query($sql);
		// update order status
		if ($_POST["STATUS"]) {
			update_order_status($order_id, $_POST["STATUS"], true, "", $status_error);
		}			
		echo "<b>Checksum mismatched.</b>";
		//Process transaction as suspicious.
	}
