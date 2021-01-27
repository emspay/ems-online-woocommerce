# EMS Online plugin for Wordpress WooCommerce

## About
This is the offical EMS Online plugin.

EMS helps entrepreneurs with the best, smartest and most efficient payment systems. Both 
in your physical store and online in your webshop. With a wide range of payment methods 
you can serve every customer.

Why EMS?

Via the EMS website you can create a free test account online 24/7 and try out the online 
payment solution. EMS's online solution also offers the option of sending payment links and 
accepting QR payments.

The ideal online payment page for your webshop:
- Free test account - available online 24/7
- Wide range of payment methods
- Easy integration via a plug-in or API
- Free shopping cart plug-ins
- Payment page in the look & feel of your webshop
- Reports in the formats CAMT.053, MT940S, MT940 & CODA
- One clear dashboard for all your payment, turnover data and administration functions

Promotion promotion extended!

Choose the EMS Online Payment Solution now
and pay no subscription costs at € 9.95 until June 2021!

Start immediately with your test account
Request it https://portal.emspay.eu/create-test-account?language=NL_NL 

Satisfied after testing?
Click on the yellow button [Begin→]
 in the test portal and
simply request your live account.

## Version number
Version 1.1.3

## Pre-requisites to install the plug-ins 
* PHP v5.4 and above
* MySQL v5.4 and above

## Installation
Manual installation of the EMS WooCommerce plugin using (s)FTP

1. Upload the folder 'ems-online' in the ZIP file into the 'wp-content/plugins' folder of your WordPress installation.
You can use an sFTP or SCP program, for example, to upload the files. There are various sFTP clients that you can download free of charge from the internet, such as WinSCP or Filezilla.
2. Activate the EMS Online plugin in ‘Plugins’ > Installed Plugins.
3. Select ‘WooCommerce’ > ‘Settings’ > Payments and click on EMS Online (Enabled).
4. Configure the EMS Online module ('Manage' button)
- Copy the API key
- Are you offering Klarna on your pay page? In that case enter the following fields:
	- Test API key field. Copy the API Key of your test webshop in the Test API key field.
	When your Klarna application is approved an extra test webshop was created for you to use in your test with Klarna. The name of this webshop starts with ‘TEST Klarna’.
	- Klarna IP
	For the payment method Klarna you can choose to offer it only to a limited set of whitelisted IP addresses. You can use this for instance when you are in the testing phase and want to make sure that Klarna is not available yet for your customers.
	If you do not offer Klarna you can leave the Test API key and Klarna debug IP fields empty.
- Are you offering Afterpay on your pay page? 
	- To do this click on the “Manage” button of EMS Online: AfterPay in the payment method overview.
	- Next, see the instructions for Klarna
- Select your preferred Failed payment page. This setting determines the page to which your customer is redirected after a payment attempt has failed. You can choose between the Checkout page (the page where you can choose a payment method) or the Shopping cart page (the page before checkout where the content of the shopping cart is displayed).
- Enable the cURL CA bundle option.
This fixes a cURL SSL Certificate issue that appears in some web-hosting environments where you do not have access to the PHP.ini file and therefore are not able to update server certificates.

- Only for AfterPay payment: To allow AfterPay to be used for any other country just add its country code (in ISO 2 standard) to the "Countries available for AfterPay" field. Example: BE, NL, FR

5. Configure each payment method you would like to offer in your webshop.
Enable only those payment methods that you applied for and for which you have received a confirmation from us.

- To configure iDEAL do the following:
	- Go to ‘WooCommerce’ > ‘Settings’ > Payments > ‘EMS Online: iDEAL’.
	- Select Enable iDEAL Payment to include the payment method in your pay page.

- Follow the same procedure for all other payment methods you have enabled.

Manual installation by uploading ZIP file from WordPress administration environment

1. Go to your WordPress admin environment. Upload the ZIP file to your WordPress installation by clicking on ‘Plugins’ > ‘Add New’. No files are overwritten.
2. Select ´Upload plugin´.
3. Select the ems-online.zip file.
4. Continue with step 3 of Installation using (s)FTP.

Compatibility: WordPress 5.2.2

## WooCommerce & qTranslate-X compatibility
In order for the webhook URL to be accessible by the EMS Online API you need to go to: Settings -> Languages -> General
Under "URL Modification Mode" check the `"Hide URL language information for default language."` box.
