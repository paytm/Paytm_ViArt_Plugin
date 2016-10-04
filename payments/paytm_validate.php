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