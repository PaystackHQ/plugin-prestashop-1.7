<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @since 1.5.0
 */

 include_once dirname(__FILE__) . "/class-paystack-plugin-tracker.php";
class PaystackPaystacksuccessModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function verify_txn($code){
      $test_secretkey = Configuration::get('PAYSTACK_TEST_SECRETKEY');
      $live_secretkey = Configuration::get('PAYSTACK_LIVE_SECRETKEY');
      $mode = Configuration::get('PAYSTACK_MODE');

      if ($mode == '1') {
        $key = $test_secretkey;
      }else{
        $key = $live_secretkey;
      }
      $key = str_replace(' ', '', $key);

      $contextOptions = array(
          'http'=>array(
     		    'method'=>"GET",
            'header'=> array("Authorization: Bearer ".$key."\r\n")
     		  )
      );

      $context = stream_context_create($contextOptions);
      $url = 'https://api.paystack.co/transaction/verify/'.$code;
      $request = Tools::file_get_contents($url, false, $context);
      $result = Tools::jsonDecode($request);
      return $result;
    }
    public function initContent()
    {
		$cart = $this->context->cart;
    	$txn_code = Tools::getValue('reference');
        if(Tools::getValue('reference') == ""){
          $txn_code = $_POST['reference'];
        }
        $amount = Tools::getValue('amount');
        $email = Tools::getValue('email');
        $verification = $this->verify_txn($txn_code);
       
        if(($verification->status===false) || (!property_exists($verification, 'data')) || ($verification->data->status !== 'success')){
          $date = date("Y-m-d h:i:sa");
          $email = $email;
          $total = $amount;
          $status = 'failed';
          	Tools::redirect('404');
        } else {

          //PSTK - Logger
          $mode = Configuration::get('PAYSTACK_MODE');
          $test_pk = Configuration::get('PAYSTACK_TEST_PUBLICKEY');
          $live_pk = Configuration::get('PAYSTACK_LIVE_PUBLICKEY');
          if ($mode == '1') {
            $key = $test_pk;
          }else{
            $key = $live_pk;
          }
          $key = str_replace(' ', '', $key);
          $pstk_logger = new presta_1_7_paystack_plugin_tracker('presta-1.7', $key );
          $pstk_logger->log_transaction_success($txn_code);
          // PSTK Logger done -----------------



         $email = $verification->data->customer->email;
          $date = $verification->data->transaction_date;
          $total = $verification->data->amount/100;
          $status = 'approved';
          $currency_order = new Currency($cart->id_currency);
        
          $extra_vars = array(
				    'transaction_id' => $txn_code,
			       'id' => 1,
            'payment_method' => 'Paystack',
            'status' => 'Paid',
            'currency' => $currency_order->iso_code,
            'intent' => '$intent'
            );

			$customer = new Customer($cart->id_customer);
			
			$this->module->validateOrder(
				$cart->id,
				Configuration::get('PS_OS_PAYSTACK'),
				$total,
				$this->module->displayName,
				'Paystack Reference: '.$txn_code,
				$extra_vars,
				(int)$cart->id_currency,
				false,
				$customer->secure_key
        );

			Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key.'&reference='.$txn_code);
        }
    }
}
