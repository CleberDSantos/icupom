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
 * Class BlockBanner
 *
 * @since 1.0.0
 */
class BlockBanner extends Module
{
    const IMAGE = 'BLOCKBANNER_IMG';
    const LINK = 'BLOCKBANNER_LINK';
    const DESCRIPTION = 'BLOCKBANNER_DESC';

    /**
     * BlockBanner constructor.
     */
    public function __construct()
    {
        $this->name = 'blockbanner';
        $this->tab = 'front_office_features';
        $this->version = '2.0.3';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Block Banner');
        $this->description = $this->l('Displays a banner at the top of the shop.');
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6.99.99'];
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $this->registerHook('displayBanner');
        $this->registerHook('displayHeader');
        $this->registerHook('actionObjectLanguageAddAfter');

        $this->installFixtures();

        return true;
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public function hookActionObjectLanguageAddAfter($params)
    {
        try {
            return $this->installFixture(
                (int) $params['object']->id,
                Configuration::get(static::IMAGE, (int) Configuration::get('PS_LANG_DEFAULT'))
            );
        } catch (PrestaShopException $e) {
            Logger::addLog("Blockbanner hook error: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Uninstall this module
     *
     * @return bool Indicates whether this module has been successfully uninstalled
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @since 1.0.0
     */
    public function uninstall()
    {
        foreach ([
            static::IMAGE,
            static::LINK,
            static::DESCRIPTION,
                 ] as $key) {
            try {
                Configuration::deleteByName($key);
            } catch (PrestaShopException $e) {
                Logger::addLog("Blockbanner module error: {$e->getMessage()}");
            }
        }

        return parent::uninstall();
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function hookDisplayBanner()
    {
        try {
            return $this->hookDisplayTop();
        } catch (Exception $e) {
            Logger::addLog("Blockbanner hook error: {$e->getMessage()}");

            return '';
        }
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function hookDisplayTop()
    {
        try {
            if (!$this->isCached('blockbanner.tpl', $this->getCacheId())) {
                $imgname = Configuration::get(static::IMAGE, $this->context->language->id);

                if ($imgname && file_exists(
                    _PS_MODULE_DIR_.$this->name.DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$imgname)
                ) {
                    $this->smarty->assign('banner_img', $this->context->link->protocol_content
                        .Tools::getMediaServer($imgname).$this->_path.'img/'.$imgname);
                }

                $this->smarty->assign(
                    [
                        'banner_link' => Configuration::get(static::LINK, $this->context->language->id),
                        'banner_desc' => Configuration::get(static::DESCRIPTION, $this->context->language->id),
                    ]
                );
            }

            return $this->display(__FILE__, 'blockbanner.tpl', $this->getCacheId());
        } catch (Exception $e) {
            Logger::addLog("Blockbanner hook error: {$e->getMessage()}");

            return '';
        }
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function hookDisplayFooter()
    {
        return $this->hookDisplayTop();
    }

    /**
     * @since 1.0.0
     */
    public function hookDisplayHeader()
    {
        $this->context->controller->addCSS($this->_path.'blockbanner.css', 'all');
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getContent()
    {
        try {
            return $this->postProcess().$this->renderForm();
        } catch (Exception $e) {
            $this->context->controller->errors[] = $e->getMessage();

            return '';
        }
    }

    /**
     * @return bool|string
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitStoreConf')) {
            $languages = Language::getLanguages(false);
            $values = [];
            $updateImagesValues = false;

            foreach ($languages as $lang) {
                if (isset($_FILES['BLOCKBANNER_IMG_'.$lang['id_lang']])
                    && isset($_FILES['BLOCKBANNER_IMG_'.$lang['id_lang']]['tmp_name'])
                    && !empty($_FILES['BLOCKBANNER_IMG_'.$lang['id_lang']]['tmp_name'])
                ) {
                    if ($error = ImageManager::validateUpload($_FILES['BLOCKBANNER_IMG_'.$lang['id_lang']], 4000000)) {
                        return $error;
                    } else {
                        $ext = substr(
                            $_FILES['BLOCKBANNER_IMG_'.$lang['id_lang']]['name'],
                            strrpos($_FILES['BLOCKBANNER_IMG_'.$lang['id_lang']]['name'], '.') + 1
                        );
                        $fileName = md5($_FILES['BLOCKBANNER_IMG_'.$lang['id_lang']]['name']).'.'.$ext;

                        if (!move_uploaded_file(
                            $_FILES['BLOCKBANNER_IMG_'.$lang['id_lang']]['tmp_name'],
                            dirname(__FILE__).DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$fileName)
                        ) {
                            return $this->displayError($this->l('An error occurred while attempting to upload the file.'));
                        } else {
                            if (Configuration::hasContext('BLOCKBANNER_IMG', $lang['id_lang'], Shop::getContext())
                                && Configuration::get('BLOCKBANNER_IMG', $lang['id_lang']) != $fileName
                            ) {
                                @unlink(dirname(__FILE__).DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR
                                    .Configuration::get('BLOCKBANNER_IMG', $lang['id_lang']));
                            }

                            $values['BLOCKBANNER_IMG'][$lang['id_lang']] = $fileName;
                        }
                    }

                    $updateImagesValues = true;
                }

                $values['BLOCKBANNER_LINK'][$lang['id_lang']] = Tools::getValue('BLOCKBANNER_LINK_'.$lang['id_lang']);
                $values['BLOCKBANNER_DESC'][$lang['id_lang']] = Tools::getValue('BLOCKBANNER_DESC_'.$lang['id_lang']);
            }

            if ($updateImagesValues) {
                Configuration::updateValue('BLOCKBANNER_IMG', $values['BLOCKBANNER_IMG']);
            }

            Configuration::updateValue('BLOCKBANNER_LINK', $values['BLOCKBANNER_LINK']);
            Configuration::updateValue('BLOCKBANNER_DESC', $values['BLOCKBANNER_DESC']);

            $this->_clearCache('blockbanner.tpl');

            return $this->displayConfirmation($this->l('The settings have been updated.'));
        }

        return '';
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
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ],
                'input'  => [
                    [
                        'type'  => 'file_lang',
                        'label' => $this->l('Top banner image'),
                        'name'  => 'BLOCKBANNER_IMG',
                        'desc'  => $this->l('Upload an image for your top banner. The recommended dimensions are 1170 x 65px if you are using the default theme.'),
                        'lang'  => true,
                    ],
                    [
                        'type'  => 'text',
                        'lang'  => true,
                        'label' => $this->l('Banner Link'),
                        'name'  => 'BLOCKBANNER_LINK',
                        'desc'  => $this->l('Enter the link associated to your banner. When clicking on the banner, the link opens in the same window. If no link is entered, it redirects to the homepage.'),
                    ],
                    [
                        'type'  => 'text',
                        'lang'  => true,
                        'label' => $this->l('Banner description'),
                        'name'  => 'BLOCKBANNER_DESC',
                        'desc'  => $this->l('Please enter a short but meaningful description for the banner.'),
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
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitStoreConf';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'uri'          => $this->getPathUri(),
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$formFields]);
    }

    /**
     * @return array
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    public function getConfigFieldsValues()
    {
        $languages = Language::getLanguages(false);
        $fields = [];

        foreach ($languages as $lang) {
            try {
                $fields[static::IMAGE][$lang['id_lang']] = Tools::getValue(
                    static::IMAGE.'_'.$lang['id_lang'],
                    Configuration::get(static::IMAGE, $lang['id_lang'])
                );
                $fields[static::LINK][$lang['id_lang']] = Tools::getValue(
                    static::LINK.'_'.$lang['id_lang'],
                    Configuration::get(static::LINK, $lang['id_lang'])
                );
                $fields[static::DESCRIPTION][$lang['id_lang']] = Tools::getValue(
                    static::DESCRIPTION.'_'.$lang['id_lang'],
                    Configuration::get(static::DESCRIPTION, $lang['id_lang'])
                );
            } catch (Exception $e) {
                Logger::addLog("Blockbanner hook error: {$e->getMessage()}");
                $fields[static::IMAGE][$lang['id_lang']] = '';
                $fields[static::LINK][$lang['id_lang']] = '';
                $fields[static::DESCRIPTION][$lang['id_lang']] = '';
            }
        }

        return $fields;
    }

    /**
     * @return bool
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    protected function installFixtures()
    {
        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            $this->installFixture((int) $lang['id_lang'], 'sale70.png');
        }

        return true;
    }

    /**
     * @param int         $idLang
     * @param string|null $image
     *
     * @since 1.0.0
     * @throws PrestaShopException
     */
    protected function installFixture($idLang, $image = null)
    {
        $values = [];
        $values[static::IMAGE][(int) $idLang] = $image;
        $values[static::LINK][(int) $idLang] = '';
        $values[static::DESCRIPTION][(int) $idLang] = '';
        Configuration::updateValue(static::IMAGE, $values[static::IMAGE]);
        Configuration::updateValue(static::LINK, $values[static::LINK]);
        Configuration::updateValue(static::DESCRIPTION, $values[static::DESCRIPTION]);
    }
}
