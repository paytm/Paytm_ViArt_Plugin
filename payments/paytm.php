<?php
    header("Pragma: no-cache");
    header("Cache-Control: no-cache");
    header("Expires: 0");
	include_once("../includes/paytm/PaytmChecksum.php");
	include_once("../includes/paytm/PaytmHelper.php");
	
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
	//$callback_url = get_setting_value($payment_params, "callback_url", "");
	$callback_url = 'http://127.0.0.1/viart/viart56/payments/paytm_validate.php';
	$merchant_key = get_setting_value($payment_params, "merchant_key", "");
	
	$ORDER_ID = PaytmHelper::getPaytmOrderId($_POST["invoice"]);
	$CUST_ID = trim($_POST["email"]);
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




	    $paytmParams["body"] = array(
            "requestType" => "Payment",
            "mid" => $paramList["MID"],
            "websiteName" => $paramList["WEBSITE"],
            "orderId" => $paramList["ORDER_ID"],
            "callbackUrl" => $paramList["CALLBACK_URL"],
            "txnAmount" => array(
                "value" => $paramList["TXN_AMOUNT"],
                "currency" => "INR",
            ),
            "userInfo" => array(
                "custId" => $paramList["CUST_ID"],
            ),
        );

        $generateSignature = PaytmChecksum::generateSignature(json_encode($paytmParams['body'], JSON_UNESCAPED_SLASHES), $merchant_key);
        $paytmParams["head"] = array(
            "signature" => $generateSignature
        );
	

//


//print_r($paytmParams);  exit;
   
	//$checkSum = getChecksumFromArray($paramList, get_setting_value($payment_params, "merchant_key", ""));
	/*	19751/17Jan2018	*/
		/*if(get_setting_value($payment_params, "live_mode", "") == 'yes') {
			$post_url = "https://secure.paytm.in/oltp-web/processTransaction";
		} else {
			$post_url = "https://pguat.paytm.com/oltp-web/processTransaction";
		}*/

		if(get_setting_value($payment_params, "live_mode", "") == 'yes') {
			$paytm_env = 1;
		} else {
			$paytm_env = 0;
		}


		$apiURL = PaytmHelper::getPaytmURL(PaytmConstants::TRANSACTION_INIT_URL,$paytm_env).$paramList['MID'].'&ORDER_ID='.$paramList['ORDER_ID'];

		        $post_data_string = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);
        $response_array = PaytmHelper::executecUrl($apiURL, $post_data_string);

        if(!empty($response_array['body']['txnToken'])){
        $txnToken = $response_array['body']['txnToken'];
        $paytm_msg = PaytmConstants::TNX_TOKEN_GENERATED;
        }else{
         $txnToken = '';
         $paytm_msg = PaytmConstants::RESPONSE_ERROR;

        }

 //print_r($response_array); exit;
		//$post_url = get_setting_value($payment_params, "transaction_url", "");
	/*	19751/17Jan2018 end	*/

	//echo $post_url; 
	//print_r($paramList); 
	?>
	<html>
		<head>
			<title>Merchant Check Out Page</title>
		</head>
		<body class="">





        <div id="paytm-pg-spinner" class="paytm-pg-loader ">
            <div class="bounce1"></div>
            <div class="bounce2"></div>
            <div class="bounce3"></div>
            <div class="bounce4"></div>
            <div class="bounce5"></div>
        </div>
        <div class="paytm-overlay"></div>

        <script type="application/javascript" crossorigin="anonymous" src="<?php echo PaytmConstants::STAGING_HOST; ?>merchantpgpui/checkoutjs/merchants/<?php echo $paramList['MID']; ?>.js"></script>
        
        <div class="loader"></div>
        <style type="text/css">
            #paytm-pg-spinner {margin: 20% auto 0;width: 72px;text-align: center;z-index: 999999;position: relative;display: block;}

            #paytm-pg-spinner > div {width: 10px;height: 10px;background-color: #012b71;border-radius: 100%;display: inline-block;-webkit-animation: sk-bouncedelay 1.4s infinite ease-in-out both;animation: sk-bouncedelay 1.4s infinite ease-in-out both;}

            #paytm-pg-spinner .bounce1 {-webkit-animation-delay: -0.64s;animation-delay: -0.64s;}

            #paytm-pg-spinner .bounce2 {-webkit-animation-delay: -0.48s;animation-delay: -0.48s;}
            #paytm-pg-spinner .bounce3 {-webkit-animation-delay: -0.32s;animation-delay: -0.32s;}

            #paytm-pg-spinner .bounce4 {-webkit-animation-delay: -0.16s;animation-delay: -0.16s;}
            #paytm-pg-spinner .bounce4, #paytm-pg-spinner .bounce5{background-color: #48baf5;} 
            @-webkit-keyframes sk-bouncedelay {0%, 80%, 100% { -webkit-transform: scale(0) }40% { -webkit-transform: scale(1.0) }}

            @keyframes sk-bouncedelay { 0%, 80%, 100% { -webkit-transform: scale(0);transform: scale(0); } 40% { 
                                            -webkit-transform: scale(1.0); transform: scale(1.0);}}
            .paytm-overlay{width: 100%;position: fixed;top: 0px;opacity: .4;height: 100%;background: #000;display: block;}
            #errorDivPaytm {
                color: red !important;
            }

        </style>

        <script type="text/javascript">
         

                //$( '<div class="paytm-overlay paytm-pg-loader"></div>' ).insertBefore("body");
                function openBlinkCheckoutPopup(orderId, txnToken, amount)
                {
                    var config = {
                        "root": "",
                        "flow": "DEFAULT",
                        "data": {
                            "orderId": orderId,
                            "token": txnToken,
                            "tokenType": "TXN_TOKEN",
                            "amount": amount
                        },
                        "integration": {
                            "platform": "ViArt",
                            "version": "<?php echo va_version() ?>|<?php echo PaytmConstants::PLUGIN_VERSION ?>"
                        },
                        "merchant": {
                            "redirect": true
                        },
                        "handler": {
                    
                            "notifyMerchant": function (eventName, data) {
                                
                               if(eventName == 'SESSION_EXPIRED'){
                                location.reload(); 
                               }
                            }
                        }
                    };
                    if (window.Paytm && window.Paytm.CheckoutJS) {
                        // initialze configuration using init method 
                        window.Paytm.CheckoutJS.init(config).then(function onSuccess() {
                            // after successfully updating configuration, invoke checkoutjs
                            window.Paytm.CheckoutJS.invoke();

                            //  $('.paytm-pg-loader').css("display", "none");

                        }).catch(function onError(error) {
                          //  console.log("error => ", error);
                        });
                    }
                }

                setTimeout(function(){ 

                	 openBlinkCheckoutPopup('<?php echo $paramList['ORDER_ID']; ?>','<?php echo $txnToken; ?>','<?php echo $paramList['TXN_AMOUNT']; ?>'); 


                }, 3000);
                 

 

        </script>



		</body>
	</html>
	