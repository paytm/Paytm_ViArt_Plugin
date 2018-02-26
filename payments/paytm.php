<?php
	include_once("../includes/encdec_paytm.php");
	
	$is_admin_path = true;
	$root_folder_path = "../";

	include_once ($root_folder_path ."includes/common.php");
	include_once ($root_folder_path ."includes/order_items.php");
	include_once ($root_folder_path ."messages/".$language_code."/cart_messages.php");
	include_once ($root_folder_path ."includes/parameters.php");
	    
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
		
	get_payment_parameters($order_id, $payment_params, $pass_parameters, $post_parameters, $pass_data, $variables, "");

	$checkSum = "";
	$paramList = array();
	$callback_url = get_setting_value($payment_params, "callback_url", "");
	
	$ORDER_ID = $_POST["invoice"];
	$CUST_ID = $_POST["email"];
	$INDUSTRY_TYPE_ID = get_setting_value($payment_params, "industry", "");
	$CHANNEL_ID = get_setting_value($payment_params, "channel", "");	
	$email = $_POST["email"];
	$TXN_AMOUNT = $_POST["amount"];	
	
	$paramList["MID"] = get_setting_value($payment_params, "merchant_id", "");
	$paramList["ORDER_ID"] = $ORDER_ID;
	$paramList["CUST_ID"] = $CUST_ID;
	$paramList["INDUSTRY_TYPE_ID"] = $INDUSTRY_TYPE_ID;
	$paramList["CHANNEL_ID"] = $CHANNEL_ID;
	$paramList["TXN_AMOUNT"] = $TXN_AMOUNT;
	$paramList["WEBSITE"] = get_setting_value($payment_params, "merchant_website", "");
	$paramList["CALLBACK_URL"] = $callback_url;
	
	$checkSum = getChecksumFromArray($paramList, get_setting_value($payment_params, "merchant_key", ""));
	/*	19751/17Jan2018	*/
		/*if(get_setting_value($payment_params, "live_mode", "") == 'yes') {
			$post_url = "https://secure.paytm.in/oltp-web/processTransaction";
		} else {
			$post_url = "https://pguat.paytm.com/oltp-web/processTransaction";
		}*/

		/*if(get_setting_value($payment_params, "live_mode", "") == 'yes') {
			$post_url = "https://securegw.paytm.in/theia/processTransaction";
		} else {
			$post_url = "https://securegw-stage.paytm.in/theia/processTransaction";
		}*/
		$post_url = get_setting_value($payment_params, "transaction_url", "");
	/*	19751/17Jan2018 end	*/
	?>
	<html>
		<head>
			<title>Merchant Check Out Page</title>
		</head>
		<body>
			<center><h1>Please do not refresh this page...</h1></center>
			<form method="post" action="<?php echo $post_url; ?>" name="f1">
				<table border="1">
					<tbody>			
						<?php
						foreach($paramList as $name => $value) {
						echo '<input type="hidden" name="' . $name .'" value="' . $value . '">';
						}
						?>
						<input type="hidden" name="CHECKSUMHASH" value="<?php echo $checkSum ?>">
					</tbody>
				</table>
				<script type="text/javascript">
				document.f1.submit();
				</script>
			</form>
		</body>
	</html>
	