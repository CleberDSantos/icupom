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

if (!defined('_CAN_LOAD_FILES_') || !defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class blocksocial
 */
class blocksocial extends Module
{
    /**
     * blocksocial constructor.
     */
    public function __construct()
    {
        $this->name = 'blocksocial';
        $this->tab = 'front_office_features';
        $this->version = '2.2.3';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Block Social Networking');
        $this->description = $this->l('Allows you to add information about your brand\'s social networking accounts.');
        $this->tb_versions_compliancy = '>= 1.0.0';
        $this->tb_min_version = '1.0.0';
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6.99.99'];
    }

    /**
     * Install the module
     *
     * @return bool
     */
    public function install()
    {
        $return = parent::install();

        // Pre-fill some with thirty bees to show how it works.
        $return &= Configuration::updateValue(
            'BLOCKSOCIAL_FACEBOOK', 'https://www.facebook.com/thirtybees'
        );
        $return &= Configuration::updateValue(
            'BLOCKSOCIAL_TWITTER', 'https://twitter.com/thethirtybees'
        );
        $return &= Configuration::updateValue(
            'BLOCKSOCIAL_RSS', 'https://thirtybees.com/feed'
        );
        $return &= Configuration::updateValue(
            'BLOCKSOCIAL_GOOGLE_PLUS', 'https://plus.google.com/+thirtybees'
        );
        $return &= Configuration::updateValue('BLOCKSOCIAL_YOUTUBE', '');
        $return &= Configuration::updateValue('BLOCKSOCIAL_PINTEREST', '');
        $return &= Configuration::updateValue('BLOCKSOCIAL_VIMEO', '');
        $return &= Configuration::updateValue('BLOCKSOCIAL_INSTAGRAM', '');
        $return &= Configuration::updateValue('BLOCKSOCIAL_VK', '');
        $return &= Configuration::updateValue('BLOCKSOCIAL_LINKEDIN', '');
        $return &= Configuration::updateValue('BLOCKSOCIAL_WORDPRESS', '');
        $return &= Configuration::updateValue('BLOCKSOCIAL_AMAZON', '');
        $return &= Configuration::updateValue('BLOCKSOCIAL_TUMBLR', '');
        $return &= Configuration::updateValue('BLOCKSOCIAL_SNAPCHAT', '');
        $return &= Configuration::updateValue('BLOCKSOCIAL_REDDIT', '');
        $return &= Configuration::updateValue('BLOCKSOCIAL_YELP', '');
        $return &= Configuration::updateValue('BLOCKSOCIAL_MEDIUM', '');

        $return &= $this->registerHook('displayHeader');
        $return &= $this->registerHook('displayFooter');

        return $return;
    }

    /**
     * Uninstall the module
     *
     * @return bool
     */
    public function uninstall()
    {
        //Delete configuration
        return (Configuration::deleteByName('BLOCKSOCIAL_FACEBOOK')
            && Configuration::deleteByName('BLOCKSOCIAL_TWITTER')
            && Configuration::deleteByName('BLOCKSOCIAL_RSS')
            && Configuration::deleteByName('BLOCKSOCIAL_YOUTUBE')
            && Configuration::deleteByName('BLOCKSOCIAL_GOOGLE_PLUS')
            && Configuration::deleteByName('BLOCKSOCIAL_PINTEREST')
            && Configuration::deleteByName('BLOCKSOCIAL_VIMEO')
            && Configuration::deleteByName('BLOCKSOCIAL_INSTAGRAM')
            && Configuration::deleteByName('BLOCKSOCIAL_VK')
            && Configuration::deleteByName('BLOCKSOCIAL_LINKEDIN')
            && Configuration::deleteByName('BLOCKSOCIAL_WORDPRESS')
            && Configuration::deleteByName('BLOCKSOCIAL_AMAZON')
            && Configuration::deleteByName('BLOCKSOCIAL_TUMBLR')
            && Configuration::deleteByName('BLOCKSOCIAL_SNAPCHAT')
            && Configuration::deleteByName('BLOCKSOCIAL_REDDIT')
            && Configuration::deleteByName('BLOCKSOCIAL_YELP')
            && Configuration::deleteByName('BLOCKSOCIAL_MEDIUM')
            && parent::uninstall());
    }

    /**
     * Get the module's config page
     *
     * @return string
     */
    public function getContent()
    {
        // If we try to update the settings
        $output = '';
        if (Tools::isSubmit('submitModule')) {
            Configuration::updateValue('BLOCKSOCIAL_FACEBOOK', Tools::getValue('blocksocial_facebook', ''));
            Configuration::updateValue('BLOCKSOCIAL_TWITTER', Tools::getValue('blocksocial_twitter', ''));
            Configuration::updateValue('BLOCKSOCIAL_RSS', Tools::getValue('blocksocial_rss', ''));
            Configuration::updateValue('BLOCKSOCIAL_YOUTUBE', Tools::getValue('blocksocial_youtube', ''));
            Configuration::updateValue('BLOCKSOCIAL_GOOGLE_PLUS', Tools::getValue('blocksocial_google_plus', ''));
            Configuration::updateValue('BLOCKSOCIAL_PINTEREST', Tools::getValue('blocksocial_pinterest', ''));
            Configuration::updateValue('BLOCKSOCIAL_VIMEO', Tools::getValue('blocksocial_vimeo', ''));
            Configuration::updateValue('BLOCKSOCIAL_INSTAGRAM', Tools::getValue('blocksocial_instagram', ''));
            Configuration::updateValue('BLOCKSOCIAL_VK', Tools::getValue('blocksocial_vk', ''));
            Configuration::updateValue('BLOCKSOCIAL_LINKEDIN', Tools::getValue('blocksocial_linkedin', ''));
            Configuration::updateValue('BLOCKSOCIAL_WORDPRESS', Tools::getValue('blocksocial_wordpress', ''));
            Configuration::updateValue('BLOCKSOCIAL_AMAZON', Tools::getValue('blocksocial_amazon', ''));
            Configuration::updateValue('BLOCKSOCIAL_TUMBLR', Tools::getValue('blocksocial_tumblr', ''));
            Configuration::updateValue('BLOCKSOCIAL_SNAPCHAT', Tools::getValue('blocksocial_snapchat', ''));
            Configuration::updateValue('BLOCKSOCIAL_REDDIT', Tools::getValue('blocksocial_reddit', ''));
            Configuration::updateValue('BLOCKSOCIAL_YELP', Tools::getValue('blocksocial_yelp', ''));
            Configuration::updateValue('BLOCKSOCIAL_MEDIUM', Tools::getValue('blocksocial_medium', ''));
            $this->_clearCache('blocksocial.tpl');
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules').'&configure='.$this->name.'&tab_module='.$this->tab.'&conf=4&module_name='.$this->name);
        }

        return $output.$this->renderForm();
    }

    public function renderForm()
    {
        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ],
                'input'  => [
                    [
                        'type'  => 'text',
                        'label' => $this->l('Facebook URL'),
                        'name'  => 'blocksocial_facebook',
                        'desc'  => $this->l('Your Facebook fan page.'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Twitter URL'),
                        'name'  => 'blocksocial_twitter',
                        'desc'  => $this->l('Your official Twitter account.'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('RSS URL'),
                        'name'  => 'blocksocial_rss',
                        'desc'  => $this->l('The RSS feed of your choice (your blog, your store, etc.).'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('YouTube URL'),
                        'name'  => 'blocksocial_youtube',
                        'desc'  => $this->l('Your official YouTube account.'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Google+ URL:'),
                        'name'  => 'blocksocial_google_plus',
                        'desc'  => $this->l('Your official Google+ page.'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Pinterest URL:'),
                        'name'  => 'blocksocial_pinterest',
                        'desc'  => $this->l('Your official Pinterest account.'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Vimeo URL:'),
                        'name'  => 'blocksocial_vimeo',
                        'desc'  => $this->l('Your official Vimeo account.'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Instagram URL:'),
                        'name'  => 'blocksocial_instagram',
                        'desc'  => $this->l('Your official Instagram account.'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('VK URL:'),
                        'name'  => 'blocksocial_vk',
                        'desc'  => $this->l('Your official VK account.'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('LinkedIn URL:'),
                        'name'  => 'blocksocial_linkedin',
                        'desc'  => $this->l('Your official LinkedIn account.'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Wordpress URL:'),
                        'name'  => 'blocksocial_wordpress',
                        'desc'  => $this->l('Your official Wordpress Blog.'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Amazon Store URL:'),
                        'name'  => 'blocksocial_amazon',
                        'desc'  => $this->l('Your official Amazon store.'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Tumblr URL:'),
                        'name'  => 'blocksocial_tumblr',
                        'desc'  => $this->l('Your official Tumblr account.'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('SnapChat URL:'),
                        'name'  => 'blocksocial_snapchat',
                        'desc'  => $this->l('Your official SnapChat account.'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Reddit URL:'),
                        'name'  => 'blocksocial_reddit',
                        'desc'  => $this->l('Your official Reddit account or sub-reddit.'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Yelp URL:'),
                        'name'  => 'blocksocial_yelp',
                        'desc'  => $this->l('Your official Yelp page.'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Medium URL:'),
                        'name'  => 'blocksocial_medium',
                        'desc'  => $this->l('Your official Medium page.'),
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
        $helper->submit_action = 'submitModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$fieldsForm]);
    }

    public function getConfigFieldsValues()
    {
        return [
            'blocksocial_facebook'    => Tools::getValue('blocksocial_facebook', Configuration::get('BLOCKSOCIAL_FACEBOOK')),
            'blocksocial_twitter'     => Tools::getValue('blocksocial_twitter', Configuration::get('BLOCKSOCIAL_TWITTER')),
            'blocksocial_rss'         => Tools::getValue('blocksocial_rss', Configuration::get('BLOCKSOCIAL_RSS')),
            'blocksocial_youtube'     => Tools::getValue('blocksocial_youtube', Configuration::get('BLOCKSOCIAL_YOUTUBE')),
            'blocksocial_google_plus' => Tools::getValue('blocksocial_google_plus', Configuration::get('BLOCKSOCIAL_GOOGLE_PLUS')),
            'blocksocial_pinterest'   => Tools::getValue('blocksocial_pinterest', Configuration::get('BLOCKSOCIAL_PINTEREST')),
            'blocksocial_vimeo'       => Tools::getValue('blocksocial_vimeo', Configuration::get('BLOCKSOCIAL_VIMEO')),
            'blocksocial_instagram'   => Tools::getValue('blocksocial_instagram', Configuration::get('BLOCKSOCIAL_INSTAGRAM')),
            'blocksocial_vk'   => Tools::getValue('blocksocial_vk', Configuration::get('BLOCKSOCIAL_VK')),
            'blocksocial_linkedin'   => Tools::getValue('blocksocial_linkedin', Configuration::get('BLOCKSOCIAL_LINKEDIN')),
            'blocksocial_wordpress'   => Tools::getValue('blocksocial_wordpress', Configuration::get('BLOCKSOCIAL_WORDPRESS')),
            'blocksocial_amazon'   => Tools::getValue('blocksocial_amazon', Configuration::get('BLOCKSOCIAL_AMAZON')),
            'blocksocial_tumblr'   => Tools::getValue('blocksocial_tumblr', Configuration::get('BLOCKSOCIAL_TUMBLR')),
            'blocksocial_snapchat'   => Tools::getValue('blocksocial_snapchat', Configuration::get('BLOCKSOCIAL_SNAPCHAT')),
            'blocksocial_reddit'   => Tools::getValue('blocksocial_reddit', Configuration::get('BLOCKSOCIAL_REDDIT')),
            'blocksocial_yelp'   => Tools::getValue('blocksocial_yelp', Configuration::get('BLOCKSOCIAL_YELP')),
            'blocksocial_medium'   => Tools::getValue('blocksocial_medium', Configuration::get('BLOCKSOCIAL_MEDIUM')),
        ];
    }

    public function hookDisplayHeader()
    {
        Media::addJsDef(
            [
                'blocksocial_facebook_url'    => Configuration::get('BLOCKSOCIAL_FACEBOOK'),
                'blocksocial_twitter_url'     => Configuration::get('BLOCKSOCIAL_TWITTER'),
                'blocksocial_rss_url'         => Configuration::get('BLOCKSOCIAL_RSS'),
                'blocksocial_youtube_url'     => Configuration::get('BLOCKSOCIAL_YOUTUBE'),
                'blocksocial_google_plus_url' => Configuration::get('BLOCKSOCIAL_GOOGLE_PLUS'),
                'blocksocial_pinterest_url'   => Configuration::get('BLOCKSOCIAL_PINTEREST'),
                'blocksocial_vimeo_url'       => Configuration::get('BLOCKSOCIAL_VIMEO'),
                'blocksocial_instagram_url'   => Configuration::get('BLOCKSOCIAL_INSTAGRAM'),
                'blocksocial_vk_url'   => Configuration::get('BLOCKSOCIAL_VK'),
                'blocksocial_linkedin_url'   => Configuration::get('BLOCKSOCIAL_LINKEDIN'),
                'blocksocial_wordpress_url'   => Configuration::get('BLOCKSOCIAL_WORDPRESS'),
                'blocksocial_amazon_url'   => Configuration::get('BLOCKSOCIAL_AMAZON'),
                'blocksocial_tumblr_url'   => Configuration::get('BLOCKSOCIAL_TUMBLR'),
                'blocksocial_snapchat_url'   => Configuration::get('BLOCKSOCIAL_SNAPCHAT'),
                'blocksocial_reddit_url'   => Configuration::get('BLOCKSOCIAL_REDDIT'),
                'blocksocial_yelp_url'   => Configuration::get('BLOCKSOCIAL_YELP'),
                'blocksocial_medium_url'   => Configuration::get('BLOCKSOCIAL_MEDIUM'),
            ]
        );

        $this->context->controller->addJS(($this->_path).'views/js/blocksocial.js');
        $this->context->controller->addCSS(($this->_path).'blocksocial.css', 'all');
    }

    public function hookDisplayFooter()
    {
        if (!$this->isCached('blocksocial.tpl', $this->getCacheId())) {
            $this->smarty->assign(
                [
                    'facebook_url'    => Configuration::get('BLOCKSOCIAL_FACEBOOK'),
                    'twitter_url'     => Configuration::get('BLOCKSOCIAL_TWITTER'),
                    'rss_url'         => Configuration::get('BLOCKSOCIAL_RSS'),
                    'youtube_url'     => Configuration::get('BLOCKSOCIAL_YOUTUBE'),
                    'google_plus_url' => Configuration::get('BLOCKSOCIAL_GOOGLE_PLUS'),
                    'pinterest_url'   => Configuration::get('BLOCKSOCIAL_PINTEREST'),
                    'vimeo_url'       => Configuration::get('BLOCKSOCIAL_VIMEO'),
                    'instagram_url'   => Configuration::get('BLOCKSOCIAL_INSTAGRAM'),
                    'vk_url'   => Configuration::get('BLOCKSOCIAL_VK'),
                    'linkedin_url'   => Configuration::get('BLOCKSOCIAL_LINKEDIN'),
                    'wordpress_url'   => Configuration::get('BLOCKSOCIAL_WORDPRESS'),
                    'amazon_url'   => Configuration::get('BLOCKSOCIAL_AMAZON'),
                    'tumblr_url'   => Configuration::get('BLOCKSOCIAL_TUMBLR'),
                    'snapchat_url'   => Configuration::get('BLOCKSOCIAL_SNAPCHAT'),
                    'reddit_url'   => Configuration::get('BLOCKSOCIAL_REDDIT'),
                    'yelp_url'   => Configuration::get('BLOCKSOCIAL_YELP'),
                    'medium_url'   => Configuration::get('BLOCKSOCIAL_MEDIUM'),
                ]
            );
        }

        return $this->display(__FILE__, 'blocksocial.tpl', $this->getCacheId());
    }
}
