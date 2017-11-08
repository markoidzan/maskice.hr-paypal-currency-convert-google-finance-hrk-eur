<?php
/*
Plugin Name: maskice.hr PayPal Fix
Plugin URI: https://github.com/markoidzan/
Description: PayPal Fix for Getting Paid in Croatian Kuna eventrough PayPal don't accept it by default. Uses Google Conversion rates
Version: 1.0
Author: Marko Idžan
Author URI: https://idzan.eu
*/
/**
 * this code will add an unsupported currency to Woocommerce (tested up to v3.1.2 on WP 4.8.2) 
 * it will convert any amounts including a cart discount, tax or shipping into a supported currency (in this case EUR)
 * paypal HTML variables can be found at https://developer.paypal.com/webapps/developer/docs/classic/paypal-payments-standard/integration-guide/Appx_websitestandard_htmlvariables/#id08A6HI00JQU
 * payment processing can then be done via Paypal
 * note - any orders placed this way are automatically placed on-hold by Woocommerce due to an amount & currency mismatch and MUST be manually updated to processing or complete, esp with virtual or downloadable orders.
 * stock or inventory management does NOT work with this workaround - there is an additional step needed please see https://vinefruit.net/woocommerce-currency-tweak/ 
 * 
 */
 
/*Step 1 Code to use HRK currency to display Dirhams in WooCommerce:*/
add_filter( 'woocommerce_currencies', 'add_aed_currency' );  
function add_aed_currency( $currencies ) {  
	$currencies['HRK'] = __( 'Croatian Kuna', 'woocommerce' );  
	return $currencies;  
}
/*Step 2 Code to add HRK currency symbol in WooCommerce:*/
add_filter('woocommerce_currency_symbol', 'add_aed_currency_symbol', 10, 2);  
function add_aed_currency_symbol( $currency_symbol, $currency ) {  
	switch( $currency ) {  
		case 'HRK': $currency_symbol = 'HRK'; break;  
	}  
	return $currency_symbol;  
}  
add_filter( 'woocommerce_paypal_supported_currencies', 'add_aed_paypal_valid_currency' );       
function add_aed_paypal_valid_currency( $currencies ) {    
	array_push ( $currencies , 'HRK' );  
	return $currencies;    
}   
/*Step 3 – Code to change 'HRK' currency to ‘EUR’ before checking out with Paypal through WooCommerce:*/
add_filter('woocommerce_paypal_args', 'convert_aed_to_usd', 11 );  
function get_currency($from_Currency='EUR', $to_Currency='HRK') {
	$url = "https://finance.google.com/finance/converter?a=1&from=$from_Currency&to=$to_Currency";
	$ch = curl_init();
    $timeout = 0;
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_USERAGENT,
                 "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    $rawdata = curl_exec($ch);
    curl_close($ch);
    $data = explode('bld>', $rawdata);
    $data = explode($to_Currency, $data[1]);
    return round($data[0], 2);
}
function convert_aed_to_usd($paypal_args){
	if ( $paypal_args['currency_code'] == 'HRK'){  
		$convert_rate = get_currency(); //Set converting rate
		$paypal_args['currency_code'] = 'EUR'; //change HRK to EUR  
		$i = 1;  
        
		while (isset($paypal_args['amount_' . $i])) {  
			$paypal_args['amount_' . $i] = round( $paypal_args['amount_' . $i] / $convert_rate, 2);
			++$i;  
        }  
		if ( $paypal_args['shipping_1'] > 0 ) {
			$paypal_args['shipping_1'] = round( $paypal_args['shipping_1'] / $convert_rate, 2);
		}
		
		if ( $paypal_args['discount_amount_cart'] > 0 ) {
			$paypal_args['discount_amount_cart'] = round( $paypal_args['discount_amount_cart'] / $convert_rate, 2);
		}
		if ( $paypal_args['tax_cart'] > 0 ) {
			$paypal_args['tax_cart'] = round( $paypal_args['tax_cart'] / $convert_rate, 2);
		}
	}
return $paypal_args;  
}   