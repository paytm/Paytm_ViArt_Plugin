Introduction

This is the readme file for Paytm Payment Gateway Plugin Integration for ViArt Php based e-Commerce Websites. 
The provided Package helps store merchants to redirect customers to the Paytm Payment Gateway when they choose PAYTM as their payment method. 
After the customer has finished the transaction they are redirected back to an appropriate page on the merchant site depending on the status of the transaction.
The aim of this document is to explain the procedure of installation and configuration of the Package on the merchant website.


Installation

- Unzip the files and copy paste the two folder "includes" and "payments" in viart root folder


Configuration

- Log in to the ViArt Admin panel
- In top Menu, from "Settings" click on "Payment Systems"
- Click on "New Payments System"
- Check "Is Active" option
- Enter "Payment System Name"
- In "Payment Url" fill "./payments/paytm.php"
- Choose "Form Submit Method" to "Post"
- In Parameter List Section Add following Parameter with their values

1)  invoice = order_id
2)  item_name = basket
3)  amount = order_total
4)  email = email
5)  channel =  (Provided by Paytm)
6)  callback_url = {site_url}order_final.php
7)  industry =  (Provided by Paytm)
8)  merchant_id =  (Provided by Paytm)
9)  merchant_key =  (Provided by Paytm)
10) merchant_website =  (Provided by Paytm)
11) live_mode = no (For Testing Purpose)

- Click on Save
- Now from the payment gateway list, locate "Paytm" and click on "Final Checkout Page" Link
- In Final Checkout Page link under validation parameters
- Check "Additional Validation" checkbox
- In "Validation Script" enter "./payments/paytm_validate.php"
- Set Order Success Status to "New Order"
- Set Pending Status to "Pending"
- Set Failure Status to "Failed"
- In Final Messages Section , set the messages as per you need on thankyou page.
- Click on Save.

# Paytm PG URL Details
	staging	
		Transaction URL             => https://securegw-stage.paytm.in/theia/processTransaction
		Transaction Status Url      => https://securegw-stage.paytm.in/merchant-status/getTxnStatus

	Production
		Transaction URL             => https://securegw.paytm.in/theia/processTransaction
		Transaction Status Url      => https://securegw.paytm.in/merchant-status/getTxnStatus
