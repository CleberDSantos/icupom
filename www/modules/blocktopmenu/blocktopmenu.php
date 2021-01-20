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

if ( ! defined('_TB_VERSION_')) {
    exit;
}

require __DIR__.'/menutoplinks.class.php';

/**
 * Class Blocktopmenu
 */
class Blocktopmenu extends Module
{
    protected $_menu = '';
    protected $_html = '';
    protected $user_groups;
    protected $autogenerateImages = null;

    /*
     * Pattern for matching config values
     */
    protected $pattern = '/^([A-Z_]*)[0-9]+/';

    /*
     * Name of the controller
     * Used to set item selected or not in top menu
     */
    protected $page_name = '';

    /*
     * Spaces per depth in BO
     */
    protected $spacer_size = '5';

    /**
     * Blocktopmenu constructor.
     */
    public function __construct()
    {
        $this->name = 'blocktopmenu';
        $this->tab = 'front_office_features';
        $this->version = '3.0.4';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Block Top Menu');
        $this->description = $this->l('Adds a new horizontal menu to the top of your e-commerce website.');
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';
    }

    /**
     * @param bool $deleteParams
     *
     * @return bool
     */
    public function install($deleteParams = true)
    {
        if (!parent::install()) {
            return false;
        }

        foreach ([
                     'header',
                     'displayTop',
                     'actionObjectCategoryUpdateAfter',
                     'actionObjectCategoryDeleteAfter',
                     'actionObjectCategoryAddAfter',
                     'actionObjectCmsUpdateAfter',
                     'actionObjectCmsDeleteAfter',
                     'actionObjectCmsAddAfter',
                     'actionObjectSupplierUpdateAfter',
                     'actionObjectSupplierDeleteAfter',
                     'actionObjectSupplierAddAfter',
                     'actionObjectManufacturerUpdateAfter',
                     'actionObjectManufacturerDeleteAfter',
                     'actionObjectManufacturerAddAfter',
                     'actionObjectProductUpdateAfter',
                     'actionObjectProductDeleteAfter',
                     'actionObjectProductAddAfter',
                     'categoryUpdate',
                     'actionShopDataDuplication',
                 ] as $hook) {
            try {
                $this->registerHook($hook);
            } catch (PrestaShopException $e) {
            }
        }

        $this->clearMenuCache();

        if ($deleteParams) {
            if (!$this->installDb()
                || !Configuration::updateGlobalValue('MOD_BLOCKTOPMENU_ITEMS', 'CAT3,CAT26')
                || !Configuration::updateGlobalValue('MOD_BLOCKTOPMENU_SEARCH', '1')
                || !Configuration::updateGlobalValue('MOD_BLOCKTOPMENU_MAXLEVELDEPTH', '2')
                || !Configuration::updateGlobalValue('MOD_BLOCKTOPMENU_SHOWIMAGES', '0')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function installDb()
    {
        try {
            return (Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'linksmenutop` (
                `id_linksmenutop` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `id_shop` INT(11) UNSIGNED NOT NULL,
                `new_window` TINYINT( 1 ) NOT NULL,
                INDEX (`id_shop`)
            ) ENGINE = '._MYSQL_ENGINE_.' CHARACTER SET utf8 COLLATE utf8_general_ci;') &&
                Db::getInstance()->execute('
                 CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'linksmenutop_lang` (
                `id_linksmenutop` INT(11) UNSIGNED NOT NULL,
                `id_lang` INT(11) UNSIGNED NOT NULL,
                `id_shop` INT(11) UNSIGNED NOT NULL,
                `label` VARCHAR( 128 ) NOT NULL ,
                `link` VARCHAR( 128 ) NOT NULL ,
                INDEX ( `id_linksmenutop` , `id_lang`, `id_shop`)
            ) ENGINE = '._MYSQL_ENGINE_.' CHARACTER SET utf8 COLLATE utf8_general_ci;'));
        } catch (PrestaShopException $e) {
            return false;
        }
    }

    /**
     * @param bool $deleteParams
     *
     * @return bool
     */
    public function uninstall($deleteParams = true)
    {
        if (!parent::uninstall()) {
            return false;
        }

        $this->clearMenuCache();

        if ($deleteParams) {
            if (!$this->uninstallDB() || !Configuration::deleteByName('MOD_BLOCKTOPMENU_ITEMS') || !Configuration::deleteByName('MOD_BLOCKTOPMENU_SEARCH') || !Configuration::deleteByName('MOD_BLOCKTOPMENU_MAXLEVELDEPTH') || !Configuration::deleteByName('MOD_BLOCKTOPMENU_SHOWIMAGES')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function uninstallDb()
    {
        try {
            Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'linksmenutop`');
        } catch (PrestaShopException $e) {
        }
        try {
            Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'linksmenutop_lang`');
        } catch (PrestaShopException $e) {
        }

        return true;
    }

    /**
     * @return bool
     */
    public function reset()
    {
        if (!$this->uninstall(false)) {
            return false;
        }
        if (!$this->install(false)) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        $this->context->controller->addjQueryPlugin('hoverIntent');

        $idLang = (int) Context::getContext()->language->id;
        $languages = $this->context->controller->getLanguages();
        $defaultLanguage = (int) Configuration::get('PS_LANG_DEFAULT');

        $labels = Tools::getValue('label') ? array_filter(Tools::getValue('label'), 'strlen') : [];
        $linksLabel = Tools::getValue('link') ? array_filter(Tools::getValue('link'), 'strlen') : [];

        $updateCache = false;

        if (Tools::isSubmit('submitBlocktopmenu')) {
            $errorsUpdateShops = [];
            $items = Tools::getValue('items');
            $shops = Shop::getContextListShopID();


            foreach ($shops as $idShop) {
                $idShopGroup = Shop::getGroupFromShop($idShop);
                $updated = true;

                if (count($shops) == 1) {
                    if (is_array($items) && count($items)) {
                        $updated = Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', (string) implode(',', $items), false, (int) $idShopGroup, (int) $idShop);
                    } else {
                        $updated = Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', '', false, (int) $idShopGroup, (int) $idShop);
                    }
                }

                $updated &= Configuration::updateValue('MOD_BLOCKTOPMENU_SEARCH', (bool) Tools::getValue('search'), false, (int) $idShopGroup, (int) $idShop);

                $updated &= Configuration::updateValue('MOD_BLOCKTOPMENU_MAXLEVELDEPTH', (int) Tools::getValue('maxleveldepth'), false, (int) $idShopGroup, (int) $idShop);

                $updated &= Configuration::updateValue('MOD_BLOCKTOPMENU_SHOWIMAGES', (int) Tools::getValue('showimages'), false, (int) $idShopGroup, (int) $idShop);

                if (!$updated) {
                    $shop = new Shop($idShop);
                    $errorsUpdateShops[] = $shop->name;
                }
            }

            if (!count($errorsUpdateShops)) {
                $this->_html .= $this->displayConfirmation($this->l('The settings have been updated.'));
            } else {
                $this->_html .= $this->displayError(sprintf($this->l('Unable to update settings for the following shop(s): %s'), implode(', ', $errorsUpdateShops)));
            }

            $updateCache = true;
        } else {
            if (Tools::isSubmit('submitBlocktopmenuLinks')) {
                $errorsAddLink = [];

                foreach ($languages as $key => $val) {
                    $linksLabel[$val['id_lang']] = Tools::getValue('link_'.(int) $val['id_lang']);
                    $labels[$val['id_lang']] = Tools::getValue('label_'.(int) $val['id_lang']);
                }

                $countLinksLabel = count($linksLabel);
                $countLabel = count($labels);

                if ($countLinksLabel || $countLabel) {
                    if (!$countLinksLabel) {
                        $this->_html .= $this->displayError($this->l('Please complete the "Link" field.'));
                    } elseif (!$countLabel) {
                        $this->_html .= $this->displayError($this->l('Please add a label.'));
                    } elseif (!isset($labels[$defaultLanguage])) {
                        $this->_html .= $this->displayError($this->l('Please add a label for your default language.'));
                    } else {
                        $shops = Shop::getContextListShopID();

                        foreach ($shops as $idShop) {
                            $added = MenuTopLinks::add($linksLabel, $labels, Tools::getValue('new_window', 0), (int) $idShop);

                            if (!$added) {
                                $shop = new Shop($idShop);
                                $errorsAddLink[] = $shop->name;
                            }
                        }

                        if (!count($errorsAddLink)) {
                            $this->_html .= $this->displayConfirmation($this->l('The link has been added.'));
                        } else {
                            $this->_html .= $this->displayError(sprintf($this->l('Unable to add link for the following shop(s): %s'), implode(', ', $errorsAddLink)));
                        }
                    }
                }
                $updateCache = true;
            } elseif (Tools::isSubmit('deletelinksmenutop')) {
                $errorsDeleteLink = [];
                $idLinksmenutop = Tools::getValue('id_linksmenutop', 0);
                $shops = Shop::getContextListShopID();

                foreach ($shops as $idShop) {
                    $deleted = MenuTopLinks::remove($idLinksmenutop, (int) $idShop);
                    Configuration::updateValue('MOD_BLOCKTOPMENU_ITEMS', str_replace(['LNK'.$idLinksmenutop.',', 'LNK'.$idLinksmenutop], '', Configuration::get('MOD_BLOCKTOPMENU_ITEMS')));

                    if (!$deleted) {
                        $shop = new Shop($idShop);
                        $errorsDeleteLink[] = $shop->name;
                    }
                }

                if (!count($errorsDeleteLink)) {
                    $this->_html .= $this->displayConfirmation($this->l('The link has been removed.'));
                } else {
                    $this->_html .= $this->displayError(sprintf($this->l('Unable to remove link for the following shop(s): %s'), implode(', ', $errorsDeleteLink)));
                }

                $updateCache = true;
            } elseif (Tools::isSubmit('updatelinksmenutop')) {
                $idLinksmenutop = (int) Tools::getValue('id_linksmenutop', 0);
                $idShop = (int) Shop::getContextShopID();

                if (Tools::isSubmit('updatelink')) {
                    $link = [];
                    $label = [];
                    $newWindow = (int) Tools::getValue('new_window', 0);

                    foreach (Language::getLanguages(false) as $lang) {
                        $link[$lang['id_lang']] = Tools::getValue('link_'.(int) $lang['id_lang']);
                        $label[$lang['id_lang']] = Tools::getValue('label_'.(int) $lang['id_lang']);
                    }

                    MenuTopLinks::update($link, $label, $newWindow, (int) $idShop, (int) $idLinksmenutop);
                    $this->_html .= $this->displayConfirmation($this->l('The link has been edited.'));
                }
                $updateCache = true;
            }
        }

        if ($updateCache) {
            $this->clearMenuCache();
        }


        $shops = Shop::getContextListShopID();
        $links = [];

        if (count($shops) > 1) {
            $this->_html .= $this->getWarningMultishopHtml();
        }

        if (Shop::isFeatureActive()) {
            $this->_html .= $this->getCurrentShopInfoMsg();
        }

        $this->_html .= $this->renderForm().$this->renderAddForm();

        foreach ($shops as $idShop) {
            $links = array_merge($links, MenuTopLinks::gets((int) $idLang, null, (int) $idShop));
        }

        if (!count($links)) {
            return $this->_html;
        }

        $this->_html .= $this->renderList();

        return $this->_html;
    }

    /**
     * @return string
     */
    protected function getWarningMultishopHtml()
    {
        return '<p class="alert alert-warning">'.$this->l('You cannot manage top menu items from a "All Shops" or a "Group Shop" context, select directly the shop you want to edit').'</p>';
    }

    /**
     * @return string
     */
    protected function getCurrentShopInfoMsg()
    {
        $shopInfo = null;

        if (Shop::getContext() == Shop::CONTEXT_SHOP) {
            $shopInfo = sprintf($this->l('The modifications will be applied to shop: %s'), $this->context->shop->name);
        } else {
            if (Shop::getContext() == Shop::CONTEXT_GROUP) {
                $shopInfo = sprintf($this->l('The modifications will be applied to this group: %s'), Shop::getContextShopGroup()->name);
            } else {
                $shopInfo = $this->l('The modifications will be applied to all shops');
            }
        }

        return '<div class="alert alert-info">'.$shopInfo.'</div>';
    }

    protected function getMenuItems()
    {
        $items = Tools::getValue('items');
        if (is_array($items) && count($items)) {
            return $items;
        } else {
            $shops = Shop::getContextListShopID();
            $conf = null;

            if (count($shops) > 1) {
                foreach ($shops as $key => $idShop) {
                    $idShopGroup = Shop::getGroupFromShop($idShop);
                    $conf .= (string) ($key > 1 ? ',' : '').Configuration::get('MOD_BLOCKTOPMENU_ITEMS', null, $idShopGroup, $idShop);
                }
            } else {
                $idShop = (int) $shops[0];
                $idShopGroup = Shop::getGroupFromShop($idShop);
                $conf = Configuration::get('MOD_BLOCKTOPMENU_ITEMS', null, $idShopGroup, $idShop);
            }

            if (strlen($conf)) {
                return explode(',', $conf);
            } else {
                return [];
            }
        }
    }

    /**
     * @return string
     */
    protected function makeMenuOption()
    {
        $idShop = (int) Shop::getContextShopID();

        $menuItem = $this->getMenuItems();
        $idLang = (int) $this->context->language->id;

        $html = '<select multiple="multiple" name="items[]" id="items" style="width: 300px; height: 160px;">';
        foreach ($menuItem as $item) {
            if (!$item) {
                continue;
            }

            preg_match($this->pattern, $item, $values);
            $id = (int) substr($item, strlen($values[1]), strlen($item));

            switch (substr($item, 0, strlen($values[1]))) {
                case 'CAT':
                    $category = new Category((int) $id, (int) $idLang);
                    if (Validate::isLoadedObject($category)) {
                        $html .= '<option selected="selected" value="CAT'.$id.'">'.$category->name.'</option>'.PHP_EOL;
                    }
                    break;

                case 'PRD':
                    $product = new Product((int) $id, true, (int) $idLang);
                    if (Validate::isLoadedObject($product)) {
                        $html .= '<option selected="selected" value="PRD'.$id.'">'.$product->name.'</option>'.PHP_EOL;
                    }
                    break;

                case 'CMS':
                    $cms = new CMS((int) $id, (int) $idLang);
                    if (Validate::isLoadedObject($cms)) {
                        $html .= '<option selected="selected" value="CMS'.$id.'">'.$cms->meta_title.'</option>'.PHP_EOL;
                    }
                    break;

                case 'CMS_CAT':
                    $category = new CMSCategory((int) $id, (int) $idLang);
                    if (Validate::isLoadedObject($category)) {
                        $html .= '<option selected="selected" value="CMS_CAT'.$id.'">'.$category->name.'</option>'.PHP_EOL;
                    }
                    break;

                // Case to handle the option to show all Manufacturers
                case 'ALLMAN':
                    $html .= '<option selected="selected" value="ALLMAN0">'.$this->l('All manufacturers').'</option>'.PHP_EOL;
                    break;

                case 'MAN':
                    $manufacturer = new Manufacturer((int) $id, (int) $idLang);
                    if (Validate::isLoadedObject($manufacturer)) {
                        $html .= '<option selected="selected" value="MAN'.$id.'">'.$manufacturer->name.'</option>'.PHP_EOL;
                    }
                    break;

                // Case to handle the option to show all Suppliers
                case 'ALLSUP':
                    $html .= '<option selected="selected" value="ALLSUP0">'.$this->l('All suppliers').'</option>'.PHP_EOL;
                    break;

                case 'SUP':
                    $supplier = new Supplier((int) $id, (int) $idLang);
                    if (Validate::isLoadedObject($supplier)) {
                        $html .= '<option selected="selected" value="SUP'.$id.'">'.$supplier->name.'</option>'.PHP_EOL;
                    }
                    break;

                case 'LNK':
                    $link = MenuTopLinks::get((int) $id, (int) $idLang, (int) $idShop);
                    if (count($link)) {
                        if (!isset($link[0]['label']) || ($link[0]['label'] == '')) {
                            $defaultLanguage = Configuration::get('PS_LANG_DEFAULT');
                            $link = MenuTopLinks::get($link[0]['id_linksmenutop'], (int) $defaultLanguage, (int) Shop::getContextShopID());
                        }
                        $html .= '<option selected="selected" value="LNK'.(int) $link[0]['id_linksmenutop'].'">'.Tools::safeOutput($link[0]['label']).'</option>';
                    }
                    break;

                case 'SHOP':
                    $shop = new Shop((int) $id);
                    if (Validate::isLoadedObject($shop)) {
                        $html .= '<option selected="selected" value="SHOP'.(int) $id.'">'.$shop->name.'</option>'.PHP_EOL;
                    }
                    break;
            }
        }

        return $html.'</select>';
    }

    /**
     * Main method to generate menu items
     *
     * @throws Adapter_Exception
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function makeMenu()
    {
        $menuItems = $this->getMenuItems();
        $idLang = (int) $this->context->language->id;
        $idShop = (int) Shop::getContextShopID();

        foreach ($menuItems as $item) {
            if (!$item) {
                continue;
            }

            preg_match($this->pattern, $item, $value);
            $id = (int) substr($item, strlen($value[1]), strlen($item));

            switch (substr($item, 0, strlen($value[1]))) {
                case 'CAT':
                    $this->_menu .= $this->generateCategoriesMenu(Category::getNestedCategories($id, $idLang, false, $this->user_groups));
                    break;

                case 'PRD':
                    $selected = ($this->page_name == 'product' && (Tools::getValue('id_product') == $id)) ? ' class="sfHover"' : '';
                    $product = new Product((int) $id, true, (int) $idLang);
                    if (!is_null($product->id)) {
                        $this->_menu .= '<li'.$selected.'><a href="'.Tools::HtmlEntitiesUTF8($product->getLink()).'" title="'.$product->name.'">'.$product->name.'</a></li>'.PHP_EOL;
                    }
                    break;

                case 'CMS':
                    $selected = ($this->page_name == 'cms' && (Tools::getValue('id_cms') == $id)) ? ' class="sfHover"' : '';
                    $cms = CMS::getLinks((int) $idLang, [$id]);
                    if (count($cms)) {
                        $this->_menu .= '<li'.$selected.'><a href="'.Tools::HtmlEntitiesUTF8($cms[0]['link']).'" title="'.Tools::safeOutput($cms[0]['meta_title']).'">'.Tools::safeOutput($cms[0]['meta_title']).'</a></li>'.PHP_EOL;
                    }
                    break;

                case 'CMS_CAT':
                    $category = new CMSCategory((int) $id, (int) $idLang);
                    if (Validate::isLoadedObject($category)) {
                        $this->_menu .= '<li><a href="'.Tools::HtmlEntitiesUTF8($category->getLink()).'" title="'.$category->name.'">'.$category->name.'</a>';
                        $this->getCMSMenuItems($category->id);
                        $this->_menu .= '</li>'.PHP_EOL;
                    }
                    break;

                // Case to handle the option to show all Manufacturers
                case 'ALLMAN':
                    $link = new Link();
                    $this->_menu .= '<li><a href="'.$link->getPageLink('manufacturer').'" title="'.$this->l('All manufacturers').'">'.$this->l('All manufacturers').'</a><ul>'.PHP_EOL;
                    $manufacturers = Manufacturer::getManufacturers();
                    foreach ($manufacturers as $key => $manufacturer) {
                        $this->_menu .= '<li><a href="'.$link->getManufacturerLink((int) $manufacturer['id_manufacturer'], $manufacturer['link_rewrite']).'" title="'.Tools::safeOutput($manufacturer['name']).'">'.Tools::safeOutput($manufacturer['name']).'</a></li>'.PHP_EOL;
                    }
                    $this->_menu .= '</ul>';
                    break;

                case 'MAN':
                    $selected = ($this->page_name == 'manufacturer' && (Tools::getValue('id_manufacturer') == $id)) ? ' class="sfHover"' : '';
                    $manufacturer = new Manufacturer((int) $id, (int) $idLang);
                    if (!is_null($manufacturer->id)) {
                        if (intval(Configuration::get('PS_REWRITING_SETTINGS'))) {
                            $manufacturer->link_rewrite = Tools::link_rewrite($manufacturer->name);
                        } else {
                            $manufacturer->link_rewrite = 0;
                        }
                        $link = new Link();
                        $this->_menu .= '<li'.$selected.'><a href="'.Tools::HtmlEntitiesUTF8($link->getManufacturerLink((int) $id, $manufacturer->link_rewrite)).'" title="'.Tools::safeOutput($manufacturer->name).'">'.Tools::safeOutput($manufacturer->name).'</a></li>'.PHP_EOL;
                    }
                    break;

                // Case to handle the option to show all Suppliers
                case 'ALLSUP':
                    $link = new Link();
                    $this->_menu .= '<li><a href="'.$link->getPageLink('supplier').'" title="'.$this->l('All suppliers').'">'.$this->l('All suppliers').'</a><ul>'.PHP_EOL;
                    $suppliers = Supplier::getSuppliers();
                    foreach ($suppliers as $key => $supplier) {
                        $this->_menu .= '<li><a href="'.$link->getSupplierLink((int) $supplier['id_supplier'], $supplier['link_rewrite']).'" title="'.Tools::safeOutput($supplier['name']).'">'.Tools::safeOutput($supplier['name']).'</a></li>'.PHP_EOL;
                    }
                    $this->_menu .= '</ul>';
                    break;

                case 'SUP':
                    $selected = ($this->page_name == 'supplier' && (Tools::getValue('id_supplier') == $id)) ? ' class="sfHover"' : '';
                    $supplier = new Supplier((int) $id, (int) $idLang);
                    if (!is_null($supplier->id)) {
                        $link = new Link();
                        $this->_menu .= '<li'.$selected.'><a href="'.Tools::HtmlEntitiesUTF8($link->getSupplierLink((int) $id, $supplier->link_rewrite)).'" title="'.$supplier->name.'">'.$supplier->name.'</a></li>'.PHP_EOL;
                    }
                    break;

                case 'SHOP':
                    $selected = ($this->page_name == 'index' && ($this->context->shop->id == $id)) ? ' class="sfHover"' : '';
                    $shop = new Shop((int) $id);
                    if (Validate::isLoadedObject($shop)) {
                        $this->_menu .= '<li'.$selected.'><a href="'.Tools::HtmlEntitiesUTF8($shop->getBaseURL()).'" title="'.$shop->name.'">'.$shop->name.'</a></li>'.PHP_EOL;
                    }
                    break;
                case 'LNK':
                    $link = MenuTopLinks::get((int) $id, (int) $idLang, (int) $idShop);
                    if (count($link)) {
                        if (!isset($link[0]['label']) || ($link[0]['label'] == '')) {
                            $defaultLanguage = Configuration::get('PS_LANG_DEFAULT');
                            $link = MenuTopLinks::get($link[0]['id_linksmenutop'], $defaultLanguage, (int) Shop::getContextShopID());
                        }
                        $this->_menu .= '<li><a href="'.Tools::HtmlEntitiesUTF8($link[0]['link']).'"'.(($link[0]['new_window']) ? ' onclick="return !window.open(this.href);"' : '').' title="'.Tools::safeOutput($link[0]['label']).'">'.Tools::safeOutput($link[0]['label']).'</a></li>'.PHP_EOL;
                    }
                    break;
            }
        }
    }

    /**
     * @param array      $categories
     * @param array|null $itemsToSkip
     *
     * @return string
     */
    protected function generateCategoriesOption($categories, $itemsToSkip = null)
    {
        $html = '';

        foreach ($categories as $key => $category) {
            if (isset($itemsToSkip) /*&& !in_array('CAT'.(int)$category['id_category'], $items_to_skip)*/) {
                $shop = (object) Shop::getShop((int) $category['id_shop']);
                $html .= '<option value="CAT'.(int) $category['id_category'].'">'.str_repeat('&nbsp;', $this->spacer_size * (int) $category['level_depth']).$category['name'].' ('.$shop->name.')</option>';
            }

            if (isset($category['children']) && !empty($category['children'])) {
                $html .= $this->generateCategoriesOption($category['children'], $itemsToSkip);
            }
        }

        return $html;
    }

    /**
     * @param array $categories list of categories to render
     * @param int $depth
     *
     * @return string
     * @throws PrestaShopException
     */
    protected function generateCategoriesMenu($categories, $depth = 0)
    {
        if (! $categories) {
            return '';
        }

        $maxLvlDepth = (int) Configuration::get('MOD_BLOCKTOPMENU_MAXLEVELDEPTH');
        if ($maxLvlDepth && $depth >= $maxLvlDepth) {
            return '';
        }

        $html = '';

        foreach ($categories as $key => $category) {
            if ($category['level_depth'] > 1) {
                $link = $this->context->link->getCategoryLink($category['id_category']);
            } else {
                $link = $this->context->link->getPageLink('index');
            }

            /* Whenever a category is not active we shouldnt display it to customer */
            if ((bool) $category['active'] === false) {
                continue;
            }

            $html .= '<li'.(($this->page_name == 'category'
                    && (int) Tools::getValue('id_category') == (int) $category['id_category']) ? ' class="sfHoverForce"' : '').'>';

            $html .= '<a href="'.$link.'" title="'.$category['name'].'">'.$category['name'].'</a>';

            $hasChildren = isset($category['children']) && !!$category['children'];
            $reachedMaxDepth = $maxLvlDepth ? ($depth+1 >= $maxLvlDepth) : true;
            $images = $depth > 0 ? [] : $this->getCategoryImages((int)$category['id_category']);

            if (!!$images || ($hasChildren && !$reachedMaxDepth)) {
                $html .= '<ul>';

                if ($hasChildren && !$reachedMaxDepth) {
                    $html .= $this->generateCategoriesMenu($category['children'], $depth + 1);
                }

                if ($images) {
                    $html .= '<li class="category-thumbnail">';

                    foreach ($images as $image) {
                        $html .= '<div>';
                        $html .= '<img src="'.$image.'" alt="'.Tools::SafeOutput($category['name']).'" title="'.Tools::SafeOutput($category['name']).'" class="imgm" />';
                        $html .= '</div>';
                    }
                    $html .= '</li>';
                }
                $html .= '</ul>';
            }

            $html .= '</li>';
        }

        return $html;
    }

    /**
     * Return list of urls to category images
     *
     * @param $categoryId
     * @return string[]
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function getCategoryImages($categoryId)
    {
        $categoryId = (int)$categoryId;
        $images = [];

        // first, look if any menu category thumbnails exists for this category
        if (file_exists(_PS_CAT_IMG_DIR_)) {
            $files = scandir(_PS_CAT_IMG_DIR_);

            if (count(preg_grep('/^' . $categoryId . '-([0-9])?_thumb.jpg/i', $files)) > 0) {
                foreach ($files as $file) {
                    if (preg_match('/^' . $categoryId . '-([0-9])?_thumb.jpg/i', $file) === 1) {
                        $images[] = $this->context->link->getMediaLink(_THEME_CAT_DIR_ . $file);
                    }
                }
            }
        }

        if ($images) {
            return $images;
        }

        // if no images were found, we can use product image from category
        if ($this->autoGenerateImages()) {
            $images = $this->generateCategoryImages($categoryId);
        }

        return $images;
    }

    /**
     * Returns images of 3 most priced products in category
     *
     * @param $categoryId
     * @return string[]
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function generateCategoryImages($categoryId)
    {
        if ($categoryId)
        {
            $images = [];

            $sql = (new DbQuery)
                ->select('DISTINCT pl.link_rewrite, i.id_image, p.price')
                ->from('product', 'p')
                ->innerJoin('category_product', 'cp', 'cp.id_product = p.id_product')
                ->innerJoin('image', 'i', 'i.id_product = p.id_product AND i.cover')
                ->innerJoin('product_lang', 'pl', 'pl.id_product = p.id_product AND pl.id_lang = ' . (int)$this->context->language->id .' AND pl.id_shop = '. (int)$this->context->shop->id)
                ->where('p.active')
                ->where('cp.id_category = '.$categoryId.' OR cp.id_category IN (SELECT id_category FROM '._DB_PREFIX_.'category WHERE id_parent = '. $categoryId .' AND active = 1)')
                ->orderBy('p.price DESC')
                ->limit(3) ;

            $results = Db::getInstance()->executeS($sql);
            foreach ($results as $row) {
                $rewrite = $row['link_rewrite'];
                $imageId = (int)$row['id_image'];
                $images[] = $this->context->link->getImageLink($rewrite, $imageId, ImageType::getFormatedName('home'));
            }

            return $images;
        }
        return [];
    }

    private function autoGenerateImages()
    {
        if ($this->autogenerateImages == null)
        {
            $this->autogenerateImages = Configuration::get('MOD_BLOCKTOPMENU_SHOWIMAGES', false, Shop::getGroupFromShop($this->context->shop->id), $this->context->shop->id);
        }
        return (bool) $this->autogenerateImages;
    }

    /**
     * @param      $parent
     * @param int  $depth
     * @param bool $idLang
     */
    protected function getCMSMenuItems($parent, $depth = 1, $idLang = false)
    {
        $idLang = $idLang ? (int) $idLang : (int) Context::getContext()->language->id;

        if ($depth > 3) {
            return;
        }

        $categories = $this->getCMSCategories(false, (int) $parent, (int) $idLang);
        $pages = $this->getCMSPages((int) $parent);

        if (count($categories) || count($pages)) {
            $this->_menu .= '<ul>';

            foreach ($categories as $category) {
                $cat = new CMSCategory((int) $category['id_cms_category'], (int) $idLang);

                $this->_menu .= '<li>';
                $this->_menu .= '<a href="'.Tools::HtmlEntitiesUTF8($cat->getLink()).'">'.$category['name'].'</a>';
                $this->getCMSMenuItems($category['id_cms_category'], (int) $depth + 1);
                $this->_menu .= '</li>';
            }

            foreach ($pages as $page) {
                $cms = new CMS($page['id_cms'], (int) $idLang);
                $links = $cms->getLinks((int) $idLang, [(int) $cms->id]);

                $selected = ($this->page_name == 'cms' && ((int) Tools::getValue('id_cms') == $page['id_cms'])) ? ' class="sfHoverForce"' : '';
                $this->_menu .= '<li '.$selected.'>';
                $this->_menu .= '<a href="'.$links[0]['link'].'">'.$cms->meta_title.'</a>';
                $this->_menu .= '</li>';
            }

            $this->_menu .= '</ul>';
        }
    }

    /**
     * @param int  $parent
     * @param int  $depth
     * @param bool $idLang
     * @param null $itemsToSkip
     * @param bool $idShop
     *
     * @return string
     */
    protected function getCMSOptions($parent = 0, $depth = 1, $idLang = false, $itemsToSkip = null, $idShop = false)
    {
        $html = '';
        $idLang = $idLang ? (int) $idLang : (int) Context::getContext()->language->id;
        $idShop = ($idShop !== false) ? $idShop : Context::getContext()->shop->id;
        $categories = $this->getCMSCategories(false, (int) $parent, (int) $idLang, (int) $idShop);
        $pages = $this->getCMSPages((int) $parent, (int) $idShop, (int) $idLang);

        $spacer = str_repeat('&nbsp;', $this->spacer_size * (int) $depth);

        foreach ($categories as $category) {
            if (isset($itemsToSkip) && !in_array('CMS_CAT'.$category['id_cms_category'], $itemsToSkip)) {
                $html .= '<option value="CMS_CAT'.$category['id_cms_category'].'" style="font-weight: bold;">'.$spacer.$category['name'].'</option>';
            }
            $html .= $this->getCMSOptions($category['id_cms_category'], (int) $depth + 1, (int) $idLang, $itemsToSkip);
        }

        foreach ($pages as $page) {
            if (isset($itemsToSkip) && !in_array('CMS'.$page['id_cms'], $itemsToSkip)) {
                $html .= '<option value="CMS'.$page['id_cms'].'">'.$spacer.$page['meta_title'].'</option>';
            }
        }

        return $html;
    }

    /**
     * @param string|null $name
     *
     * @return string
     */
    protected function getCacheId($name = null)
    {
        $pageName = in_array($this->page_name, ['category', 'supplier', 'manufacturer', 'cms', 'product']) ? $this->page_name : 'index';

        return parent::getCacheId().'|'.$pageName.($pageName != 'index' ? '|'.(int) Tools::getValue('id_'.$pageName) : '');
    }

    /**
     * @return void
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'js/hoverIntent.js');
        $this->context->controller->addJS($this->_path.'js/superfish-modified.js');
        $this->context->controller->addJS($this->_path.'js/blocktopmenu.js');
        $this->context->controller->addCSS($this->_path.'css/blocktopmenu.css');
        $this->context->controller->addCSS($this->_path.'css/superfish-modified.css');
    }

    /**
     * @return string
     */
    public function hookDisplayTop()
    {
        $this->user_groups = ($this->context->customer->isLogged() ?
            $this->context->customer->getGroups() : [Configuration::get('PS_UNIDENTIFIED_GROUP')]);
        $this->page_name = Dispatcher::getInstance()->getController();
        if (!$this->isCached('blocktopmenu.tpl', $this->getCacheId())) {
            if (Tools::isEmpty($this->_menu)) {
                $this->makeMenu();
            }

            $idShop = (int) $this->context->shop->id;
            $idShopGroup = Shop::getGroupFromShop($idShop);

            $this->smarty->assign('MENU_SEARCH', Configuration::get('MOD_BLOCKTOPMENU_SEARCH', null, $idShopGroup, $idShop));
            $this->smarty->assign('MENU', $this->_menu);
            $this->smarty->assign('this_path', $this->_path);
        }

        $html = $this->display(__FILE__, 'blocktopmenu.tpl', $this->getCacheId());

        return $html;
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayNav($params)
    {
        return $this->hookDisplayTop($params);
    }

    /**
     * @param bool $recursive
     * @param int  $parent
     * @param bool $idLang
     * @param bool $idShop
     *
     * @return array|bool|false|null|PDOStatement
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getCMSCategories($recursive = false, $parent = 1, $idLang = false, $idShop = false)
    {
        $idLang = $idLang ? (int) $idLang : (int) Context::getContext()->language->id;
        $idShop = ($idShop !== false) ? $idShop : Context::getContext()->shop->id;

        $joinShop = ' INNER JOIN `'._DB_PREFIX_.'cms_category_shop` cs
            ON (bcp.`id_cms_category` = cs.`id_cms_category`)';
        $whereShop = ' AND cs.`id_shop` = '.(int) $idShop.' AND cl.`id_shop` = '.(int) $idShop;

        if ($recursive === false) {
            $sql = 'SELECT bcp.`id_cms_category`, bcp.`id_parent`, bcp.`level_depth`, bcp.`active`, bcp.`position`, cl.`name`, cl.`link_rewrite`
                FROM `'._DB_PREFIX_.'cms_category` bcp'.
                $joinShop.'
                INNER JOIN `'._DB_PREFIX_.'cms_category_lang` cl
                ON (bcp.`id_cms_category` = cl.`id_cms_category`)
                WHERE cl.`id_lang` = '.(int) $idLang.'
                AND bcp.`id_parent` = '.(int) $parent.
                $whereShop;

            return Db::getInstance()->executeS($sql);
        } else {
            $sql = 'SELECT bcp.`id_cms_category`, bcp.`id_parent`, bcp.`level_depth`, bcp.`active`, bcp.`position`, cl.`name`, cl.`link_rewrite`
                FROM `'._DB_PREFIX_.'cms_category` bcp'.
                $joinShop.'
                INNER JOIN `'._DB_PREFIX_.'cms_category_lang` cl
                ON (bcp.`id_cms_category` = cl.`id_cms_category`)
                WHERE cl.`id_lang` = '.(int) $idLang.'
                AND bcp.`id_parent` = '.(int) $parent.
                $whereShop;

            $results = Db::getInstance()->executeS($sql);
            foreach ($results as $result) {
                $subCategories = $this->getCMSCategories(true, $result['id_cms_category'], (int) $idLang);
                if ($subCategories && count($subCategories) > 0) {
                    $result['sub_categories'] = $subCategories;
                }
                $categories[] = $result;
            }

            return isset($categories) ? $categories : false;
        }
    }

    /**
     * @param int      $idCmsCategory
     * @param int|bool $idShop
     * @param int|bool $idLang
     *
     * @return array|false|null|PDOStatement
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getCMSPages($idCmsCategory, $idShop = false, $idLang = false)
    {
        $idShop = ($idShop !== false) ? (int) $idShop : (int) Context::getContext()->shop->id;
        $idLang = $idLang ? (int) $idLang : (int) Context::getContext()->language->id;

        $sql = 'SELECT c.`id_cms`, cl.`meta_title`, cl.`link_rewrite`
            FROM `'._DB_PREFIX_.'cms` c
            INNER JOIN `'._DB_PREFIX_.'cms_shop` cs
            ON (c.`id_cms` = cs.`id_cms`)
            INNER JOIN `'._DB_PREFIX_.'cms_lang` cl
            ON (c.`id_cms` = cl.`id_cms`)
            WHERE c.`id_cms_category` = '.(int) $idCmsCategory.'
            AND cs.`id_shop` = '.(int) $idShop.'
            AND cl.`id_lang` = '.(int) $idLang.'
            AND cl.`id_shop` = '.(int) $idShop.'
            AND c.`active` = 1
            ORDER BY `position`';

        return Db::getInstance()->executeS($sql);
    }

    public function hookActionObjectCategoryAddAfter($params)
    {
        $this->clearMenuCache();
    }

    public function hookActionObjectCategoryUpdateAfter($params)
    {
        $this->clearMenuCache();
    }

    public function hookActionObjectCategoryDeleteAfter($params)
    {
        $this->clearMenuCache();
    }

    public function hookActionObjectCmsUpdateAfter($params)
    {
        $this->clearMenuCache();
    }

    public function hookActionObjectCmsDeleteAfter($params)
    {
        $this->clearMenuCache();
    }

    public function hookActionObjectCmsAddAfter($params)
    {
        $this->clearMenuCache();
    }

    public function hookActionObjectSupplierUpdateAfter($params)
    {
        $this->clearMenuCache();
    }

    public function hookActionObjectSupplierDeleteAfter($params)
    {
        $this->clearMenuCache();
    }

    public function hookActionObjectSupplierAddAfter($params)
    {
        $this->clearMenuCache();
    }

    public function hookActionObjectManufacturerUpdateAfter($params)
    {
        $this->clearMenuCache();
    }

    public function hookActionObjectManufacturerDeleteAfter($params)
    {
        $this->clearMenuCache();
    }

    public function hookActionObjectManufacturerAddAfter($params)
    {
        $this->clearMenuCache();
    }

    public function hookActionObjectProductUpdateAfter($params)
    {
        $this->clearMenuCache();
    }

    public function hookActionObjectProductDeleteAfter($params)
    {
        $this->clearMenuCache();
    }

    public function hookActionObjectProductAddAfter($params)
    {
        $this->clearMenuCache();
    }

    public function hookCategoryUpdate($params)
    {
        $this->clearMenuCache();
    }

    protected function clearMenuCache()
    {
        $this->_clearCache('blocktopmenu.tpl');
    }

    public function hookActionShopDataDuplication($params)
    {
        $linksmenutop = Db::getInstance()->executeS('
            SELECT *
            FROM '._DB_PREFIX_.'linksmenutop
            WHERE id_shop = '.(int) $params['old_id_shop']
        );

        foreach ($linksmenutop as $id => $link) {
            Db::getInstance()->execute('
                INSERT IGNORE INTO '._DB_PREFIX_.'linksmenutop (id_linksmenutop, id_shop, new_window)
                VALUES (NULL, '.(int) $params['new_id_shop'].', '.(int) $link['new_window'].')');

            $linksmenutop[$id]['new_id_linksmenutop'] = Db::getInstance()->Insert_ID();
        }

        foreach ($linksmenutop as $id => $link) {
            $lang = Db::getInstance()->executeS('
                    SELECT id_lang, '.(int) $params['new_id_shop'].', label, link
                    FROM '._DB_PREFIX_.'linksmenutop_lang
                    WHERE id_linksmenutop = '.(int) $link['id_linksmenutop'].' AND id_shop = '.(int) $params['old_id_shop']);

            foreach ($lang as $l) {
                Db::getInstance()->execute('
                    INSERT IGNORE INTO '._DB_PREFIX_.'linksmenutop_lang (id_linksmenutop, id_lang, id_shop, label, link)
                    VALUES ('.(int) $link['new_id_linksmenutop'].', '.(int) $l['id_lang'].', '.(int) $params['new_id_shop'].', '.(int) $l['label'].', '.(int) $l['link'].' )');
            }
        }
    }

    public function renderForm()
    {
        $shops = Shop::getContextListShopID();

        if (count($shops) == 1) {
            $fieldsForm = [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Menu Top Link'),
                        'icon'  => 'icon-link',
                    ],
                    'input'  => [
                        [
                            'type'  => 'link_choice',
                            'label' => '',
                            'name'  => 'link',
                            'lang'  => true,
                        ],
                        [
                            'type'  => 'text',
                            'label' => $this->l('Maximum level depth'),
                            'name'  => 'maxleveldepth',
                        ],
                        [
                            'type'    => 'switch',
                            'label'   => $this->l('Automatically select images for subcategories?'),
                            'name'    => 'showimages',
                            'is_bool' => true,
                            'values'  => [
                                [
                                    'id'    => 'active_on',
                                    'value' => 1,
                                    'label' => $this->l('Enabled'),
                                ],
                                [
                                    'id'    => 'active_off',
                                    'value' => 0,
                                    'label' => $this->l('Disabled'),
                                ],
                            ],
                        ],
                        [
                            'type'    => 'switch',
                            'label'   => $this->l('Search bar'),
                            'name'    => 'search',
                            'is_bool' => true,
                            'values'  => [
                                [
                                    'id'    => 'active_on',
                                    'value' => 1,
                                    'label' => $this->l('Enabled'),
                                ],
                                [
                                    'id'    => 'active_off',
                                    'value' => 0,
                                    'label' => $this->l('Disabled'),
                                ],
                            ],
                        ],
                    ],
                    'submit' => [
                        'name'  => 'submitBlocktopmenu',
                        'title' => $this->l('Save'),
                    ],
                ],
            ];
        } else {
            $fieldsForm = [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Menu Top Link'),
                        'icon'  => 'icon-link',
                    ],
                    'info'   => '<div class="alert alert-warning">'.
                        $this->l('All active products combinations quantities will be changed').'</div>',
                    'input'  => [
                        [
                            'type'  => 'text',
                            'label' => $this->l('Maximum level depth'),
                            'name'  => 'maxleveldepth',
                        ],
                        [
                            'type'    => 'switch',
                            'label'   => $this->l('Automatically select images for subcategories?'),
                            'name'    => 'showimages',
                            'is_bool' => true,
                            'values'  => [
                                [
                                    'id'    => 'active_on',
                                    'value' => 1,
                                    'label' => $this->l('Enabled'),
                                ],
                                [
                                    'id'    => 'active_off',
                                    'value' => 0,
                                    'label' => $this->l('Disabled'),
                                ],
                            ],
                        ],
                        [
                            'type'    => 'switch',
                            'label'   => $this->l('Search bar'),
                            'name'    => 'search',
                            'is_bool' => true,
                            'values'  => [
                                [
                                    'id'    => 'active_on',
                                    'value' => 1,
                                    'label' => $this->l('Enabled'),
                                ],
                                [
                                    'id'    => 'active_off',
                                    'value' => 0,
                                    'label' => $this->l('Disabled'),
                                ],
                            ],
                        ],
                    ],
                    'submit' => [
                        'name'  => 'submitBlocktopmenu',
                        'title' => $this->l('Save'),
                    ],
                ],
            ];
        }

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = [];
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).
            '&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value'   => $this->getConfigFieldsValues(),
            'languages'      => $this->context->controller->getLanguages(),
            'id_language'    => $this->context->language->id,
            'choices'        => $this->renderChoicesSelect(),
            'selected_links' => $this->makeMenuOption(),
        ];
        return $helper->generateForm([$fieldsForm]);
    }

    public function renderAddForm()
    {
        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => (Tools::getIsset('updatelinksmenutop') && !Tools::getValue('updatelinksmenutop')) ?
                        $this->l('Update link') : $this->l('Add a new link'),
                    'icon'  => 'icon-link',
                ],
                'input'  => [
                    [
                        'type'  => 'text',
                        'label' => $this->l('Label'),
                        'name'  => 'label',
                        'lang'  => true,
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Link'),
                        'name'  => 'link',
                        'lang'  => true,
                    ],
                    [
                        'type'    => 'switch',
                        'label'   => $this->l('New window'),
                        'name'    => 'new_window',
                        'is_bool' => true,
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'name'  => 'submitBlocktopmenuLinks',
                    'title' => $this->l('Add'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = [];
        $helper->identifier = $this->identifier;
        $helper->fields_value = $this->getAddLinkFieldsValues();

        if (Tools::getIsset('updatelinksmenutop') && !Tools::getValue('updatelinksmenutop')) {
            $fieldsForm['form']['submit'] = [
                'name'  => 'updatelinksmenutop',
                'title' => $this->l('Update'),
            ];
        }

        if (Tools::isSubmit('updatelinksmenutop')) {
            $fieldsForm['form']['input'][] = ['type' => 'hidden', 'name' => 'updatelink'];
            $fieldsForm['form']['input'][] = ['type' => 'hidden', 'name' => 'id_linksmenutop'];
            $helper->fields_value['updatelink'] = '';
        }

        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).
            '&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->languages = $this->context->controller->getLanguages();
        $helper->default_form_language = (int) $this->context->language->id;

        return $helper->generateForm([$fieldsForm]);
    }

    public function renderChoicesSelect()
    {
        $spacer = str_repeat('&nbsp;', $this->spacer_size);
        $items = $this->getMenuItems();

        $html = '<select multiple="multiple" id="availableItems" style="width: 300px; height: 160px;">';
        $html .= '<optgroup label="'.$this->l('CMS').'">';
        $html .= $this->getCMSOptions(0, 1, $this->context->language->id, $items);
        $html .= '</optgroup>';

        // BEGIN SUPPLIER
        $html .= '<optgroup label="'.$this->l('Supplier').'">';
        // Option to show all Suppliers
        $html .= '<option value="ALLSUP0">'.$this->l('All suppliers').'</option>';
        $suppliers = Supplier::getSuppliers(false, $this->context->language->id);
        foreach ($suppliers as $supplier) {
            if (!in_array('SUP'.$supplier['id_supplier'], $items)) {
                $html .= '<option value="SUP'.$supplier['id_supplier'].'">'.$spacer.$supplier['name'].'</option>';
            }
        }
        $html .= '</optgroup>';

        // BEGIN Manufacturer
        $html .= '<optgroup label="'.$this->l('Manufacturer').'">';
        // Option to show all Manufacturers
        $html .= '<option value="ALLMAN0">'.$this->l('All manufacturers').'</option>';
        $manufacturers = Manufacturer::getManufacturers(false, $this->context->language->id);
        foreach ($manufacturers as $manufacturer) {
            if (!in_array('MAN'.$manufacturer['id_manufacturer'], $items)) {
                $html .= '<option value="MAN'.$manufacturer['id_manufacturer'].'">'.$spacer.$manufacturer['name'].'</option>';
            }
        }
        $html .= '</optgroup>';

        // BEGIN Categories
        $shop = new Shop((int) Shop::getContextShopID());
        $html .= '<optgroup label="'.$this->l('Categories').'">';

        $shopsToGet = Shop::getContextListShopID();

        foreach ($shopsToGet as $idShop) {
            $html .= $this->generateCategoriesOption($this->customGetNestedCategories($idShop, null, (int) $this->context->language->id, false), $items);
        }
        $html .= '</optgroup>';

        // BEGIN Shops
        if (Shop::isFeatureActive()) {
            $html .= '<optgroup label="'.$this->l('Shops').'">';
            $shops = Shop::getShopsCollection();
            foreach ($shops as $shop) {
                if (!$shop->setUrl() && !$shop->getBaseURL()) {
                    continue;
                }

                if (!in_array('SHOP'.(int) $shop->id, $items)) {
                    $html .= '<option value="SHOP'.(int) $shop->id.'">'.$spacer.$shop->name.'</option>';
                }
            }
            $html .= '</optgroup>';
        }

        // BEGIN Products
        $html .= '<optgroup label="'.$this->l('Products').'">';
        $html .= '<option value="PRODUCT" style="font-style:italic">'.$spacer.$this->l('Choose product ID').'</option>';
        $html .= '</optgroup>';

        // BEGIN Menu Top Links
        $html .= '<optgroup label="'.$this->l('Menu Top Links').'">';
        $links = MenuTopLinks::gets($this->context->language->id, null, (int) Shop::getContextShopID());
        foreach ($links as $link) {
            if ($link['label'] == '') {
                $defaultLanguage = Configuration::get('PS_LANG_DEFAULT');
                $link = MenuTopLinks::get($link['id_linksmenutop'], $defaultLanguage, (int) Shop::getContextShopID());
                if (!in_array('LNK'.(int) $link[0]['id_linksmenutop'], $items)) {
                    $html .= '<option value="LNK'.(int) $link[0]['id_linksmenutop'].'">'.$spacer.Tools::safeOutput($link[0]['label']).'</option>';
                }
            } elseif (!in_array('LNK'.(int) $link['id_linksmenutop'], $items)) {
                $html .= '<option value="LNK'.(int) $link['id_linksmenutop'].'">'.$spacer.Tools::safeOutput($link['label']).'</option>';
            }
        }
        $html .= '</optgroup>';
        $html .= '</select>';

        return $html;
    }


    public function customGetNestedCategories(
        $idShop,
        $rootCategory = null,
        $idLang = false,
        $active = false,
        $groups = null,
        $useShopRestriction = true,
        $sqlFilter = '',
        $sqlSort = '',
        $sqlLimit = ''
    ){
        if (isset($rootCategory) && !Validate::isInt($rootCategory)) {
            die(Tools::displayError());
        }

        if (!Validate::isBool($active)) {
            die(Tools::displayError());
        }

        if (isset($groups) && Group::isFeatureActive() && !is_array($groups)) {
            $groups = (array) $groups;
        }

        $cacheId = 'Category::getNestedCategories_'.md5((int) $idShop.(int) $rootCategory.(int) $idLang.(int) $active.(int) $active
                .(isset($groups) && Group::isFeatureActive() ? implode('', $groups) : ''));

        if (!Cache::isStored($cacheId)) {
            $result = Db::getInstance()->executeS('
                            SELECT c.*, cl.*
                FROM `'._DB_PREFIX_.'category` c
                INNER JOIN `'._DB_PREFIX_.'category_shop` category_shop ON (category_shop.`id_category` = c.`id_category` AND category_shop.`id_shop` = "'.(int) $idShop.'")
                LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (c.`id_category` = cl.`id_category` AND cl.`id_shop` = "'.(int) $idShop.'")
                WHERE 1 '.$sqlFilter.' '.($idLang ? 'AND cl.`id_lang` = '.(int) $idLang : '').'
                '.($active ? ' AND (c.`active` = 1 OR c.`is_root_category` = 1)' : '').'
                '.(isset($groups) && Group::isFeatureActive() ? ' AND cg.`id_group` IN ('.implode(',', $groups).')' : '').'
                '.(!$idLang || (isset($groups) && Group::isFeatureActive()) ? ' GROUP BY c.`id_category`' : '').'
                '.($sqlSort != '' ? $sqlSort : ' ORDER BY c.`level_depth` ASC').'
                '.($sqlSort == '' && $useShopRestriction ? ', category_shop.`position` ASC' : '').'
                '.($sqlLimit != '' ? $sqlLimit : '')
            );

            $categories = [];
            $buff = [];

            foreach ($result as $row) {
                $current = &$buff[$row['id_category']];
                $current = $row;

                if ($row['id_parent'] == 0) {
                    $categories[$row['id_category']] = &$current;
                } else {
                    $buff[$row['id_parent']]['children'][$row['id_category']] = &$current;
                }
            }

            Cache::store($cacheId, $categories);
        }

        return Cache::retrieve($cacheId);
    }

    public function getConfigFieldsValues()
    {
        $shops = Shop::getContextListShopID();
        $isSearchOn = true;
        $maxLvlDepth = 0;

        foreach ($shops as $idShop) {
            $idShopGroup = Shop::getGroupFromShop($idShop);
            $isSearchOn &= (bool) Configuration::get('MOD_BLOCKTOPMENU_SEARCH', null, $idShopGroup, $idShop);
            $maxLvlDepth = (int) Configuration::get('MOD_BLOCKTOPMENU_MAXLEVELDEPTH', 0, $idShopGroup, $idShop);
            $showImages = (bool) Configuration::get('MOD_BLOCKTOPMENU_SHOWIMAGES', 0, $idShopGroup, $idShop);
        }

        return [
            'search' => (int) $isSearchOn,
            'maxleveldepth' => (int) $maxLvlDepth,
            'showimages' => (int) $showImages,
        ];
    }

    public function getAddLinkFieldsValues()
    {
        $linksLabelEdit = '';
        $labelsEdit = '';
        $newWindowEdit = '';

        if (Tools::isSubmit('updatelinksmenutop')) {
            $link = MenuTopLinks::getLinkLang(Tools::getValue('id_linksmenutop'), (int) Shop::getContextShopID());

            foreach ($link['link'] as $key => $label) {
                $link['link'][$key] = Tools::htmlentitiesDecodeUTF8($label);
            }

            $linksLabelEdit = $link['link'];
            $labelsEdit = $link['label'];
            $newWindowEdit = $link['new_window'];
        }

        $fieldsValues = [
            'new_window'      => Tools::getValue('new_window', $newWindowEdit),
            'id_linksmenutop' => Tools::getValue('id_linksmenutop'),
        ];

        if (Tools::getValue('submitAddmodule')) {
            foreach (Language::getLanguages(false) as $lang) {
                $fieldsValues['label'][$lang['id_lang']] = '';
                $fieldsValues['link'][$lang['id_lang']] = '';
            }
        } else {
            foreach (Language::getLanguages(false) as $lang) {
                $fieldsValues['label'][$lang['id_lang']] = Tools::getValue('label_'.(int) $lang['id_lang'], isset($labelsEdit[$lang['id_lang']]) ? $labelsEdit[$lang['id_lang']] : '');
                $fieldsValues['link'][$lang['id_lang']] = Tools::getValue('link_'.(int) $lang['id_lang'], isset($linksLabelEdit[$lang['id_lang']]) ? $linksLabelEdit[$lang['id_lang']] : '');
            }
        }

        return $fieldsValues;
    }

    public function renderList()
    {
        $shops = Shop::getContextListShopID();
        $links = [];

        foreach ($shops as $idShop) {
            $links = array_merge($links, MenuTopLinks::gets((int) $this->context->language->id, null, (int) $idShop));
        }

        $fieldsList = [
            'id_linksmenutop' => [
                'title' => $this->l('Link ID'),
                'type'  => 'text',
            ],
            'name'            => [
                'title' => $this->l('Shop name'),
                'type'  => 'text',
            ],
            'label'           => [
                'title' => $this->l('Label'),
                'type'  => 'text',
            ],
            'link'            => [
                'title' => $this->l('Link'),
                'type'  => 'link',
            ],
            'new_window'      => [
                'title'  => $this->l('New window'),
                'type'   => 'bool',
                'align'  => 'center',
                'active' => 'status',
            ],
        ];

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->identifier = 'id_linksmenutop';
        $helper->table = 'linksmenutop';
        $helper->actions = ['edit', 'delete'];
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->title = $this->l('Link list');
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        return $helper->generateList($links, $fieldsList);
    }
}
