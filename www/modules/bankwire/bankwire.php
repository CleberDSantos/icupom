<?php
/**
 * Copyright (C) 2017-2019 thirty bees
 * Copyright (C) 2007-2016 PrestaShop SA
 *
 * thirty bees is an extension to the PrestaShop software by PrestaShop SA.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2019 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark of PrestaShop SA.
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class BankWire
 */
class BankWire extends PaymentModule
{
    // @codingStandardsIgnoreStart
    /** @var string $details */
    public $details;
    /** @var string $owner */
    public $owner;
    /** @var string $address */
    public $address;
    /** @var array $extra_mail_vars */
    public $extra_mail_vars;
    /** @var string $moduleHtml */
    protected $moduleHtml = '';
    /** @var array $postErrors */
    protected $postErrors = [];
    // @codingStandarsdIgnoreEnd

    /**
     * BankWire constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'bankwire';
        $this->tab = 'payments_gateways';
        $this->version = '2.0.8';
        $this->author = 'thirty bees';
        $this->need_instance = 1;
        $this->controllers = ['payment', 'validation'];
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $config = Configuration::getMultiple(['BANK_WIRE_DETAILS', 'BANK_WIRE_OWNER', 'BANK_WIRE_ADDRESS']);
        if (!empty($config['BANK_WIRE_OWNER'])) {
            $this->owner = $config['BANK_WIRE_OWNER'];
        }
        if (!empty($config['BANK_WIRE_DETAILS'])) {
            $this->details = $config['BANK_WIRE_DETAILS'];
        }
        if (!empty($config['BANK_WIRE_ADDRESS'])) {
            $this->address = $config['BANK_WIRE_ADDRESS'];
        }

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Bankwire Module');
        $this->description = $this->l('Accept payments for your products via bank wire transfer.');
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';
        $this->confirmUninstall = $this->l('Are you sure about removing these details?');

        if (!isset($this->owner) || !isset($this->details) || !isset($this->address)) {
            $this->warning = $this->l('Account owner and account details must be configured before using this module.');
        }
        $paymentCurrencies = Currency::checkPaymentCurrencies($this->id);
        if (!is_array($paymentCurrencies) || !count($paymentCurrencies)) {
            $this->warning = $this->l('No currency has been set for this module.');
        }

        $this->extra_mail_vars = [
            '{bankwire_owner}'   => Configuration::get('BANK_WIRE_OWNER'),
            '{bankwire_details}' => nl2br(Configuration::get('BANK_WIRE_DETAILS')),
            '{bankwire_address}' => nl2br(Configuration::get('BANK_WIRE_ADDRESS')),
        ];
    }

    /**
     * @return bool
     * @throws PrestaShopException
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $this->registerHook('displayPayment');
        $this->registerHook('displayPaymentEU');
        $this->registerHook('paymentReturn');

        return true;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        if (!Configuration::deleteByName('BANK_WIRE_DETAILS')
            || !Configuration::deleteByName('BANK_WIRE_OWNER')
            || !Configuration::deleteByName('BANK_WIRE_ADDRESS')
            || !parent::uninstall()
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getContent()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $this->postValidation();
            if (!is_array($this->postErrors) || !count($this->postErrors)) {
                $this->postProcess();
            } else {
                foreach ($this->postErrors as $err) {
                    $this->moduleHtml .= $this->displayError($err);
                }
            }
        } else {
            $this->moduleHtml .= '<br />';
        }

        $this->moduleHtml .= $this->displayBankwire();
        $this->moduleHtml .= $this->renderForm();

        return $this->moduleHtml;
    }

    /**
     * @return string
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function displayBankwire()
    {
        return $this->display(__FILE__, 'infos.tpl');
    }

    /**
     * @return string
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        $formFields = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Contact details'),
                    'icon'  => 'icon-envelope',
                ],
                'input'  => [
                    [
                        'type'     => 'text',
                        'label'    => $this->l('Account owner'),
                        'name'     => 'BANK_WIRE_OWNER',
                        'required' => true,
                    ],
                    [
                        'type'     => 'textarea',
                        'label'    => $this->l('Details'),
                        'name'     => 'BANK_WIRE_DETAILS',
                        'desc'     => $this->l('Such as bank branch, IBAN number, BIC, etc.'),
                        'required' => true,
                    ],
                    [
                        'type'     => 'textarea',
                        'label'    => $this->l('Bank address'),
                        'name'     => 'BANK_WIRE_ADDRESS',
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$formFields]);
    }

    /**
     * Get the configuration field values
     *
     * @return array
     * @throws PrestaShopException
     */
    public function getConfigFieldsValues()
    {
        return [
            'BANK_WIRE_DETAILS' => Tools::getValue('BANK_WIRE_DETAILS', Configuration::get('BANK_WIRE_DETAILS')),
            'BANK_WIRE_OWNER'   => Tools::getValue('BANK_WIRE_OWNER', Configuration::get('BANK_WIRE_OWNER')),
            'BANK_WIRE_ADDRESS' => Tools::getValue('BANK_WIRE_ADDRESS', Configuration::get('BANK_WIRE_ADDRESS')),
        ];
    }

    /**
     * @return string
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookPayment()
    {
        if (!$this->active) {
            return '';
        }

        $this->smarty->assign(
            [
                'this_path'     => $this->_path,
                'this_path_bw'  => $this->_path,
                'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
            ]
        );

        return $this->display(__FILE__, 'payment.tpl');
    }

    /**
     * @return array|string
     * @throws PrestaShopException
     */
    public function hookDisplayPaymentEU()
    {
        if (!$this->active) {
            return '';
        }

        return [
            'cta_text' => $this->l('Pay by Bank Wire'),
            'logo'     => Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/bankwire.jpg'),
            'action'   => $this->context->link->getModuleLink($this->name, 'validation', [], true),
        ];
    }

    /**
     * @param array $params
     *
     * @return string
     * @throws Exception
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookPaymentReturn($params)
    {
        if (!isset($params) || !isset($params['objOrder']) || !$params['objOrder'] instanceof Order || !$this->active) {
            return '';
        }

        try {
            $state = $params['objOrder']->getCurrentState();
            if (in_array($state, [Configuration::get('PS_OS_BANKWIRE'), Configuration::get('PS_OS_OUTOFSTOCK'), Configuration::get('PS_OS_OUTOFSTOCK_UNPAID')])) {
                $this->smarty->assign(
                    [
                        'total_to_pay'    => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
                        'bankwireDetails' => nl2br($this->details),
                        'bankwireAddress' => nl2br($this->address),
                        'bankwireOwner'   => $this->owner,
                        'status'          => 'ok',
                        'id_order'        => $params['objOrder']->id,
                    ]
                );
                if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference)) {
                    $this->smarty->assign('reference', $params['objOrder']->reference);
                }
            } else {
                $this->smarty->assign('status', 'failed');
            }
        } catch (PrestaShopException $e) {
            Logger::addLog("Bankwire module error: {$e->getMessage()}");

            return '';
        }

        return $this->display(__FILE__, 'payment_return.tpl');
    }

    /**
     * Post process
     *
     * @throws PrestaShopException
     */
    protected function postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('BANK_WIRE_DETAILS', Tools::getValue('BANK_WIRE_DETAILS'));
            Configuration::updateValue('BANK_WIRE_OWNER', Tools::getValue('BANK_WIRE_OWNER'));
            Configuration::updateValue('BANK_WIRE_ADDRESS', Tools::getValue('BANK_WIRE_ADDRESS'));
        }
        $this->moduleHtml .= $this->displayConfirmation($this->l('Settings updated'));
    }

    /**
     * Post validation
     */
    protected function postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (!Tools::getValue('BANK_WIRE_DETAILS')) {
                $this->postErrors[] = $this->l('Account details are required.');
            } elseif (!Tools::getValue('BANK_WIRE_OWNER')) {
                $this->postErrors[] = $this->l('Account owner is required.');
            }
        }
    }
}
