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
class PaystackEftsuccessModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function initContent()
    {
        $cart = $this->context->cart;
		$gateway_reference = Tools::getValue('paystack_transaction_id');
		
		$paystack_username = Configuration::get('PAYSTACK_USERNAME');
		$paystack_password = Configuration::get('PAYSTACK_PASSWORD');
		
		$curl = curl_init('https://services.callpay.com/api/v1/token');
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_USERPWD, $paystack_username . ":" . $paystack_password);

		$response = curl_exec($curl);
		curl_close($curl);
		$response_data = json_decode($response);
		
		if(isset($response_data->token)){
			$token = $response_data->token;
			$organisation_id = $response_data->organisation_id;
		} else {
			$token = '';
			$organisation_id = '';
		}
		
		$headers = array(
			'X-Token: '.$token,
		);
		$curl = curl_init('https://services.callpay.com/api/v1/gateway-transaction/'.$gateway_reference);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$response = curl_exec($curl);
		curl_close($curl);
			
		$response_data = json_decode($response);
		
		if($response_data->id == $gateway_reference && $response_data->successful == 1) {
			$extra_vars = array(
				'transaction_id' => $gateway_reference
            );
			$total = (float)$cart->getOrderTotal(true, Cart::BOTH);
			$customer = new Customer($cart->id_customer);
			$this->module->validateOrder(
				$cart->id,
				Configuration::get('PS_OS_EFTSECURE'),
				$total,
				$this->module->displayName,
				false,
				$extra_vars,
				(int)$cart->id_currency,
				false,
				$customer->secure_key
            );
			Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
		} else {
			Tools::redirect('404');
		}
    }
}
