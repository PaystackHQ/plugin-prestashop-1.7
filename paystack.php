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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Paystack extends PaymentModule
{
    private $_html = '';
    private $_postErrors = array();

    public $details;
    public $owner;
    public $address;
    public $extra_mail_vars;
    
    public function __construct()
    {
        $this->name = 'paystack';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->author = 'Douglas Kendyson';
        $this->controllers = array('payment', 'validation');
        $this->is_eu_compatible = 0;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $config = Configuration::getMultiple(array('PAYSTACK_TEST_SECRETKEY','PAYSTACK_TEST_PUBLICKEY','PAYSTACK_LIVE_SECRETKEY','PAYSTACK_LIVE_PUBLICKEY','PAYSTACK_MODE','PAYSTACK_STYLE'));
        // if (!empty($config['PAYSTACK_USERNAME'])) {
        //     $this->owner = $config['PAYSTACK_USERNAME'];
        // }
        // if (!empty($config['PAYSTACK_DETAILS'])) {
        //     $this->details = $config['PAYSTACK_DETAILS'];
        // }
        // if (!empty($config['PAYSTACK_PASSWORD'])) {
        //     $this->address = $config['PAYSTACK_PASSWORD'];
        // }

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Paystack', array(), 'Modules.Paystack.Admin');
        $this->description = $this->trans('Accept payments for your products via Paystack transfer.', array(), 'Modules.Paystack.Admin');
        $this->confirmUninstall = $this->trans('Are you sure about removing these details?', array(), 'Modules.Paystack.Admin');

        if (!isset($this->owner) || !isset($this->details) || !isset($this->address)) {
            $this->warning = $this->trans('Account owner and account details must be configured before using this module.', array(), 'Modules.Paystack.Admin');
        }
        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->trans('No currency has been set for this module.', array(), 'Modules.Paystack.Admin');
        }

        $this->extra_mail_vars = array(
            '{paystack_owner}' => Configuration::get('PAYSTACK_USERNAME'),
            '{paystack_details}' => nl2br(Configuration::get('PAYSTACK_DETAILS')),
            //'{paystack_address}' => nl2br(Configuration::get('PAYSTACK_ADDRESS'))
        );
    }

    public function install()
    {
		if (!parent::install() || !$this->registerHook('paymentReturn') || !$this->registerHook('paymentOptions') || !$this->registerHook('header')) {
            return false;
        }

        // TODO : Cek insert new state, Custom CSS
        $newState = new OrderState();
        
        $newState->send_email = true;
        $newState->module_name = $this->name;
        $newState->invoice = false;
        $newState->color = "#002F95";
        $newState->unremovable = false;
        $newState->logable = false;
        $newState->delivery = false;
        $newState->hidden = false;
        $newState->shipped = false;
        $newState->paid = false;
        $newState->delete = false;

        $languages = Language::getLanguages(true);
        foreach ($languages as $lang) {
            if ($lang['iso_code'] == 'id') {
                $newState->name[(int)$lang['id_lang']] = 'Menunggu pembayaran via Paystack';
            } else {
                $newState->name[(int)$lang['id_lang']] = 'Awaiting Paystack Payment';
            }
            $newState->template = "paystack";
        }

        if ($newState->add()) {
            Configuration::updateValue('PS_OS_EFTSECURE', $newState->id);
            copy(dirname(__FILE__).'/logo.png', _PS_IMG_DIR_.'tmp/order_state_mini_'.(int)$newState->id.'_1.png');
        } else {
            return false;
        }

        return true;
    }

    public function uninstall()
    {

        if (!Configuration::deleteByName('PAYSTACK_TEST_SECRETKEY')
                || !Configuration::deleteByName('PAYSTACK_TEST_PUBLICKEY')
                || !Configuration::deleteByName('PAYSTACK_LIVE_PUBLICKEY')
                || !Configuration::deleteByName('PAYSTACK_LIVE_SECRETKEY')
                || !parent::uninstall()) {
            return false;
        }
        return true;
    }

   //  protected function _postValidation()
   //  {
   //      if (Tools::isSubmit('btnSubmit')) {
   //          if (!Tools::getValue('PAYSTACK_USERNAME')) {
   //              $this->_postErrors[] = $this->trans('API username is required.', array(), 'Modules.Paystack.Admin');
   //          } elseif (!Tools::getValue('PAYSTACK_PASSWORD')) {
   //              $this->_postErrors[] = $this->trans('API password is required.', array(), "Modules.Paystack.Admin");
   //          } else {
			// 	$paystack_username = Tools::getValue('PAYSTACK_USERNAME');
			// 	$paystack_password = Tools::getValue('PAYSTACK_PASSWORD');
			// 	$response_data = $this->chkAuthorization($paystack_username, $paystack_password);
			// 	if(!isset($response_data->token)){
			// 		$this->_postErrors[] = $this->trans($response_data->message, array(), "Modules.Paystack.Admin");
			// 	}
			// }
   //      }
   //  }

    protected function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('PAYSTACK_TEST_SECRETKEY', Tools::getValue('PAYSTACK_TEST_SECRETKEY'));
            Configuration::updateValue('PAYSTACK_TEST_PUBLICKEY', Tools::getValue('PAYSTACK_TEST_PUBLICKEY'));
            Configuration::updateValue('PAYSTACK_LIVE_SECRETKEY', Tools::getValue('PAYSTACK_LIVE_SECRETKEY'));
            Configuration::updateValue('PAYSTACK_LIVE_PUBLICKEY', Tools::getValue('PAYSTACK_LIVE_PUBLICKEY'));
            Configuration::updateValue('PAYSTACK_MODE', Tools::getValue('PAYSTACK_MODE'));
            Configuration::updateValue('PAYSTACK_STYLE', Tools::getValue('PAYSTACK_STYLE'));
        }
        $this->_html .= $this->displayConfirmation($this->trans('Settings updated', array(), 'Admin.Global'));
    }

    private function _displayPaystack()
    {
        return $this->display(__FILE__, 'infos.tpl');
    }
	
	public function chkAuthorization($paystack_username, $paystack_password)
    {
		$curl = curl_init('https://services.callpay.com/api/v1/token');
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_USERPWD, $paystack_username . ":" . $paystack_password);

		$response = curl_exec($curl);
		curl_close($curl);
		$response_data = json_decode($response);
		return $response_data;
	}
	
	public function hookHeader($params)
    {
        if ((int)Tools::getValue('eft_iframe') == 1) {
            $this->addJsRC(__PS_BASE_URI__.'modules/paystack/views/js/jquery.blockUI.min.js');
			$this->addJsRC(__PS_BASE_URI__.'modules/paystack/views/js/paystack_checkout.js');
        }
    }
	
	public function addJsRC($js_uri)
    {
        $this->context->controller->addJS($js_uri);
    }

    public function getContent()
    {
        if (Tools::isSubmit('btnSubmit')) {
            // $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        } else {
            $this->_html .= '<br />';
        }

        $this->_html .= $this->_displayPaystack();
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    public function hookPaymentOptions($params)
    {
		$paystack_username = Configuration::get('PAYSTACK_USERNAME');
		$paystack_password = Configuration::get('PAYSTACK_PASSWORD');
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
		
		if (!$this->checkCurrencyzar($params['cart'])) {
            return;
        }
		
		if($paystack_username == '' AND $paystack_password == ''){
			return;
		}
		
		$eft_iframe = 0;
        if ((int)Tools::getValue('eft_iframe') == 1) {
            $eft_iframe = 1;
			$response_data = $this->chkAuthorization($paystack_username, $paystack_password);
			
			if(isset($response_data->token)){
				$token = $response_data->token;
				$organisation_id = $response_data->organisation_id;
			} else {
				$token = '';
				$organisation_id = '';
			}
			
			$cart = $this->context->cart;
			$amount = $cart->getOrderTotal(true, Cart::BOTH);
			
			$params = array(
				"reference" 		=> 'order_'.$params['cart']->id,
				"organisation_id" 	=> $organisation_id,
				"token" 			=> $token,
				"amount" 			=> number_format($amount, 2),
				"pcolor" 			=> '',
				"scolor" 			=> '',
			);
			
            $this->context->smarty->assign(array(
                'eft_iframe' => 1,
                'params'	 => $params,
                'form_url' 		=> $this->context->link->getModuleLink($this->name, 'eftsuccess', array(), true),
            ));
        }

        $this->context->smarty->assign(
            $this->getTemplateVarInfos()
        );

        $newOption = new PaymentOption();
        $newOption->setCallToActionText($this->trans('Pay by Paystack', array(), 'Modules.Paystack.Shop'))
                      ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
                      ->setAdditionalInformation($this->context->smarty->fetch('module:paystack/views/templates/hook/intro.tpl'))
					  ->setInputs(array(
						'wcst_iframe' => array(
							'name' =>'wcst_iframe',
							'type' =>'hidden',
							'value' =>'1',
						)
					));
		if ($eft_iframe == 1) {
            $newOption->setAdditionalInformation(
                $this->context->smarty->fetch('module:paystack/views/templates/front/embedded.tpl')
            );
        }
		
        $payment_options = [
            $newOption,
        ];

        return $payment_options;
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        $state = $params['order']->getCurrentState();
        if (in_array(
            $state,
            array(
                Configuration::get('PS_OS_EFTSECURE'),
                Configuration::get('PS_OS_OUTOFSTOCK'),
                Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'),
            )
        )) {
            $paystackOwner = $this->owner;
            if (!$paystackOwner) {
                $paystackOwner = '___________';
            }

            $paystackDetails = Tools::nl2br($this->details);
            if (!$paystackDetails) {
                $paystackDetails = '___________';
            }

            $paystackAddress = Tools::nl2br($this->address);
            if (!$paystackAddress) {
                $paystackAddress = '___________';
            }

            $this->smarty->assign(array(
                'shop_name' => $this->context->shop->name,
                'total' => Tools::displayPrice(
                    $params['order']->getOrdersTotalPaid(),
                    new Currency($params['order']->id_currency),
                    false
                ),
                'paystackDetails' => $paystackDetails,
                'paystackAddress' => $paystackAddress,
                'paystackOwner' => $paystackOwner,
                'status' => 'ok',
                'reference' => $params['order']->reference,
                'contact_url' => $this->context->link->getPageLink('contact', true)
            ));
        } else {
            $this->smarty->assign(
                array(
                    'status' => 'failed',
                    'contact_url' => $this->context->link->getPageLink('contact', true),
                )
            );
        }

        return $this->fetch('module:paystack/views/templates/hook/payment_return.tpl');
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }
	
	public function checkCurrencyzar($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        if ($currency_order->iso_code == 'NGN') {
			return true;
        }
        return false;
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->trans('User details', array(), 'Modules.Paystack.Admin'),
                    'icon' => 'icon-user'
                ),
        
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->trans('Test Mode', array(), 'Modules.Paystack.Admin'),
                        'name' => 'PAYSTACK_MODE',
                        'is_bool' => true,
                        'required' => true,
                         'values' =>array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Test', array(), 'Modules.Paystack.Admin')
                            ),array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('False', array(), 'Modules.Paystack.Admin')
                            )
                        ),
                    ),
					array(
                        'type' => 'text',
                        'label' => $this->trans('Test Secret key', array(), 'Modules.Paystack.Admin'),
                        'name' => 'PAYSTACK_TEST_SECRETKEY',
                       
                    ),
                      array(
                        'type' => 'text',
                        'label' => $this->trans('Test Public key', array(), 'Modules.Paystack.Admin'),
                        'name' => 'PAYSTACK_TEST_PUBLICKEY',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Live Secret key', array(), 'Modules.Paystack.Admin'),
                        'name' => 'PAYSTACK_LIVE_SECRETKEY',
                       
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Live Public key', array(), 'Modules.Paystack.Admin'),
                        'name' => 'PAYSTACK_LIVE_PUBLICKEY',
                    ),    
                    array(
                        'type' => 'switch',
                        'label' => $this->trans('Lazy Inline style', array(), 'Modules.Paystack.Admin'),
                        'name' => 'PAYSTACK_STYLE',
                        'is_bool' => true,
                        'required' => true,
                         'values' =>array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Lazy Inline', array(), 'Modules.Paystack.Admin')
                            ),array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('Inline', array(), 'Modules.Paystack.Admin')
                            )
                        ),
                    ),   
                ),
                'submit' => array(
                    'title' => $this->trans('Save', array(), 'Admin.Actions'),
                )
            ),
        );
        $fields_form_customization = array();

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? : 0;
        $this->fields_form = array();
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='
            .$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form, $fields_form_customization));
    }

    public function getConfigFieldsValues()
    {
        return array(
            'PAYSTACK_TEST_SECRETKEY' => Tools::getValue('PAYSTACK_TEST_SECRETKEY', Configuration::get('PAYSTACK_TEST_SECRETKEY')),
            'PAYSTACK_TEST_PUBLICKEY' => Tools::getValue('PAYSTACK_TEST_PUBLICKEY', Configuration::get('PAYSTACK_TEST_PUBLICKEY')),
            'PAYSTACK_LIVE_SECRETKEY' => Tools::getValue('PAYSTACK_LIVE_SECRETKEY', Configuration::get('PAYSTACK_LIVE_SECRETKEY')),
            'PAYSTACK_LIVE_PUBLICKEY' => Tools::getValue('PAYSTACK_LIVE_PUBLICKEY', Configuration::get('PAYSTACK_LIVE_PUBLICKEY')),
            'PAYSTACK_MODE' => Tools::getValue('PAYSTACK_MODE', Configuration::get('PAYSTACK_MODE')),
            'PAYSTACK_STYLE' => Tools::getValue('PAYSTACK_STYLE', Configuration::get('PAYSTACK_STYLE')),
        );
    }

    public function getTemplateVarInfos()
    {
        $cart = $this->context->cart;
        $total = sprintf(
            $this->trans('%1$s (tax incl.)', array(), 'Modules.Paystack.Shop'),
            Tools::displayPrice($cart->getOrderTotal(true, Cart::BOTH))
        );

         $paystackOwner = $this->owner;
        if (!$paystackOwner) {
            $paystackOwner = '___________';
        }

        $paystackDetails = Tools::nl2br($this->details);
        if (!$paystackDetails) {
            $paystackDetails = '___________';
        }

        return array(
            'total' => $total,
            'paystackDetails' => $paystackDetails,
            'paystackAddress' => $paystackAddress,
            'paystackOwner' => $paystackOwner,
        );
    }
}
