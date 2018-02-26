<?php
	include_once("./includes/encdec_paytm.php");
	$is_admin_path = true;
	$root_folder_path = "../";
	
	$paytmChecksum = "";
	$paramList = array();
	$isValidChecksum = "FALSE";

	$paramList = $_POST;
	$paytmChecksum = isset($_POST["CHECKSUMHASH"]) ? $_POST["CHECKSUMHASH"] : ""; //Sent by Paytm pg

	$isValidChecksum = verifychecksum_e($paramList, get_setting_value($payment_params, "merchant_key", ""), $paytmChecksum); 
	if($isValidChecksum == "TRUE") 
	{		
		if ($_POST["STATUS"] == "TXN_SUCCESS") 
		{
			// Create an array having all required parameters for status query.
			$requestParamList = array("MID" => get_setting_value($payment_params, "merchant_id", "") , "ORDERID" => $_POST['ORDERID']);
			
			$StatusCheckSum = getChecksumFromArray($requestParamList, get_setting_value($payment_params, "merchant_key", ""));
							
			$requestParamList['CHECKSUMHASH'] = $StatusCheckSum;
			
			// Call the PG's getTxnStatus() function for verifying the transaction status.
			/*	19751/17Jan2018	*/
				/*if(get_setting_value($payment_params, "live_mode", "") == 'yes') {
					$check_status_url = 'https://secure.paytm.in/oltp/HANDLER_INTERNAL/getTxnStatus';
				} else {
					$check_status_url = 'https://pguat.paytm.com/oltp/HANDLER_INTERNAL/getTxnStatus';
				}*/

				/*if(get_setting_value($payment_params, "live_mode", "") == 'yes') {
					$check_status_url = 'https://securegw.paytm.in/merchant-status/getTxnStatus';
				} else {
					$check_status_url = 'https://securegw-stage.paytm.in/merchant-status/getTxnStatus';
				}*/
				$check_status_url = get_setting_value($payment_params, "transaction_status_url", "");
			/*	19751/17Jan2018 end	*/
			$responseParamList = callNewAPI($check_status_url, $requestParamList);
			if($responseParamList['STATUS']=='TXN_SUCCESS' && $responseParamList['TXNAMOUNT']==$_POST["TXNAMOUNT"])
			{			
				$success_message = $_POST['RESPMSG'];
				// update order information
				
				$sql  = " UPDATE " . $table_prefix . "orders ";
				$sql .= " SET success_message=" . $db->tosql($_POST["STATUS"], TEXT);
				$sql .= ", pending_message='', error_message='' ";
				if ($transaction_id) {
					$sql .= ", transaction_id=" . $db->tosql($_POST["TXNID"], TEXT);
				}
				$sql .= " WHERE order_id=" . $db->tosql($_POST["ORDERID"], INTEGER);
				$db->query($sql);

				// update order status
				if ($_POST["STATUS"]) {
					update_order_status($_POST["ORDERID"], $_POST["STATUS"], true, "", $status_error);
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
				if ($transaction_id) {
					$sql .= ", transaction_id=" . $db->tosql($_POST["TXNID"], TEXT);
				}
				$sql .= " WHERE order_id=" . $db->tosql($_POST["ORDERID"], INTEGER);
				$db->query($sql);
				
				// update order status
				if ($status) {
					update_order_status($_POST["ORDERID"], $status, true, "", $status_error);
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
			if ($transaction_id) {
				$sql .= ", transaction_id=" . $db->tosql($_POST["TXNID"], TEXT);
			}
			$sql .= " WHERE order_id=" . $db->tosql($_POST["ORDERID"], INTEGER);
			$db->query($sql);
			
			// update order status
			if ($_POST["STATUS"]) {
				update_order_status($_POST["ORDERID"], $_POST["STATUS"], true, "", $status_error);
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
		if ($transaction_id) {
			$sql .= ", transaction_id=" . $db->tosql($_POST["TXNID"], TEXT);
		}
		$sql .= " WHERE order_id=" . $db->tosql($_POST["ORDERID"], INTEGER);
		$db->query($sql);
		// update order status
		if ($_POST["STATUS"]) {
			update_order_status($_POST["ORDERID"], $_POST["STATUS"], true, "", $status_error);
		}			
		echo "<b>Checksum mismatched.</b>";
		//Process transaction as suspicious.
	}
