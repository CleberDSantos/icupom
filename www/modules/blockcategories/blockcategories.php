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
 * Class BlockCategories
 *
 * @since 1.0.0
 */
class BlockCategories extends Module
{
    /**
     * BlockCategories constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->name = 'blockcategories';
        $this->tab = 'front_office_features';
        $this->version = '3.0.2';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Block Categories');
        $this->description = $this->l('Adds a block featuring product categories.');
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';
    }

    /**
     * Install this module
     *
     * @return bool Indicates whether this module has been successfully installed
     *
     * @since 1.0.0
     */
    public function install()
    {
        // Prepare tab
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminBlockCategories';
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'BlockCategories';
        }
        $tab->id_parent = -1;
        $tab->module = $this->name;

        if (!parent::install()) {
            return false;
        }

        $tab->add();
        $this->registerHook('footer');
        $this->registerHook('header');
        $this->registerHook('leftColumn');
        $this->registerHook('categoryAddition');
        $this->registerHook('categoryUpdate');
        $this->registerHook('categoryDeletion');
        $this->registerHook('actionAdminMetaControllerUpdate_optionsBefore');
        $this->registerHook('actionAdminLanguagesControllerStatusBefore');
        $this->registerHook('displayBackOfficeCategory');
        Configuration::updateValue('BLOCK_CATEG_MAX_DEPTH', 4);
        Configuration::updateValue('BLOCK_CATEG_DHTML', 1);
        Configuration::updateValue('BLOCK_CATEG_ROOT_CATEGORY', 1);

        return true;
    }

    /**
     * Uninstall this module
     *
     * @return bool Indicates whether this module has been successfully uninstalled
     *
     * @since 1.0.0
     */
    public function uninstall()
    {
        $idTab = (int) Tab::getIdFromClassName('AdminBlockCategories');

        if ($idTab) {
            $tab = new Tab($idTab);
            $tab->delete();
        }

        if (!parent::uninstall() ||
            !Configuration::deleteByName('BLOCK_CATEG_MAX_DEPTH') ||
            !Configuration::deleteByName('BLOCK_CATEG_DHTML') ||
            !Configuration::deleteByName('BLOCK_CATEG_ROOT_CATEGORY')
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitBlockCategories')) {
            $maxDepth = (int) (Tools::getValue('BLOCK_CATEG_MAX_DEPTH'));
            $dhtml = Tools::getValue('BLOCK_CATEG_DHTML');
            $nbrColumns = Tools::getValue('BLOCK_CATEG_NBR_COLUMN_FOOTER', 4);
            if ($maxDepth < 0) {
                $output .= $this->displayError($this->l('Maximum depth: Invalid number.'));
            } elseif ($dhtml != 0 && $dhtml != 1) {
                $output .= $this->displayError($this->l('Dynamic HTML: Invalid choice.'));
            } else {
                Configuration::updateValue('BLOCK_CATEG_MAX_DEPTH', (int) $maxDepth);
                Configuration::updateValue('BLOCK_CATEG_DHTML', (int) $dhtml);
                Configuration::updateValue('BLOCK_CATEG_NBR_COLUMN_FOOTER', (int) $nbrColumns);
                Configuration::updateValue('BLOCK_CATEG_SORT_WAY', Tools::getValue('BLOCK_CATEG_SORT_WAY'));
                Configuration::updateValue('BLOCK_CATEG_SORT', Tools::getValue('BLOCK_CATEG_SORT'));
                Configuration::updateValue('BLOCK_CATEG_ROOT_CATEGORY', Tools::getValue('BLOCK_CATEG_ROOT_CATEGORY'));

                $this->clearBlockcategoriesCache();

                Tools::redirectAdmin(AdminController::$currentIndex.'&configure='.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules').'&conf=6');
            }
        }

        return $output.$this->renderForm();
    }

    /**
     *
     *
     * @since 1.0.0
     */
    protected function clearBlockcategoriesCache()
    {
        $this->_clearCache('blockcategories.tpl');
        $this->_clearCache('blockcategories_footer.tpl');
    }

    /**
     * @return string
     *
     * @since 1.0.0
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
                        'type'   => 'radio',
                        'label'  => $this->l('Category root'),
                        'name'   => 'BLOCK_CATEG_ROOT_CATEGORY',
                        'hint'   => $this->l('Select which category is displayed in the block. The current category is the one the visitor is currently browsing.'),
                        'values' => [
                            [
                                'id'    => 'home',
                                'value' => 0,
                                'label' => $this->l('Home category'),
                            ],
                            [
                                'id'    => 'current',
                                'value' => 1,
                                'label' => $this->l('Current category'),
                            ],
                            [
                                'id'    => 'parent',
                                'value' => 2,
                                'label' => $this->l('Parent category'),
                            ],
                            [
                                'id'    => 'current_parent',
                                'value' => 3,
                                'label' => $this->l('Current category, unless it has no subcategories, in which case the parent category of the current category is used'),
                            ],
                        ],
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('Maximum depth'),
                        'name'  => 'BLOCK_CATEG_MAX_DEPTH',
                        'desc'  => $this->l('Set the maximum depth of category sublevels displayed in this block (0 = infinite).'),
                    ],
                    [
                        'type'   => 'switch',
                        'label'  => $this->l('Dynamic'),
                        'name'   => 'BLOCK_CATEG_DHTML',
                        'desc'   => $this->l('Activate dynamic (animated) mode for category sublevels.'),
                        'values' => [
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
                        'type'   => 'radio',
                        'label'  => $this->l('Sort'),
                        'name'   => 'BLOCK_CATEG_SORT',
                        'values' => [
                            [
                                'id'    => 'name',
                                'value' => 1,
                                'label' => $this->l('By name'),
                            ],
                            [
                                'id'    => 'position',
                                'value' => 0,
                                'label' => $this->l('By position'),
                            ],
                        ],
                    ],
                    [
                        'type'   => 'radio',
                        'label'  => $this->l('Sort order'),
                        'name'   => 'BLOCK_CATEG_SORT_WAY',
                        'values' => [
                            [
                                'id'    => 'name',
                                'value' => 1,
                                'label' => $this->l('Descending'),
                            ],
                            [
                                'id'    => 'position',
                                'value' => 0,
                                'label' => $this->l('Ascending'),
                            ],
                        ],
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->l('How many footer columns would you like?'),
                        'name'  => 'BLOCK_CATEG_NBR_COLUMN_FOOTER',
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
        $helper->submit_action = 'submitBlockCategories';
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
     * @return array
     *
     * @since 1.0.0
     */
    public function getConfigFieldsValues()
    {
        return [
            'BLOCK_CATEG_MAX_DEPTH'         => Tools::getValue('BLOCK_CATEG_MAX_DEPTH', Configuration::get('BLOCK_CATEG_MAX_DEPTH')),
            'BLOCK_CATEG_DHTML'             => Tools::getValue('BLOCK_CATEG_DHTML', Configuration::get('BLOCK_CATEG_DHTML')),
            'BLOCK_CATEG_NBR_COLUMN_FOOTER' => Tools::getValue('BLOCK_CATEG_NBR_COLUMN_FOOTER', Configuration::get('BLOCK_CATEG_NBR_COLUMN_FOOTER')),
            'BLOCK_CATEG_SORT_WAY'          => Tools::getValue('BLOCK_CATEG_SORT_WAY', Configuration::get('BLOCK_CATEG_SORT_WAY')),
            'BLOCK_CATEG_SORT'              => Tools::getValue('BLOCK_CATEG_SORT', Configuration::get('BLOCK_CATEG_SORT')),
            'BLOCK_CATEG_ROOT_CATEGORY'     => Tools::getValue('BLOCK_CATEG_ROOT_CATEGORY', Configuration::get('BLOCK_CATEG_ROOT_CATEGORY')),
        ];
    }

    /**
     * @param array $params
     *
     * @return string|null
     *
     * @since 1.0.0
     */
    public function hookDisplayBackOfficeCategory($params)
    {
        $category = new Category((int) Tools::getValue('id_category'));
        $files = [];

        if ($category->level_depth < 1) {
            return null;
        }

        for ($i = 0; $i < 3; $i++) {
            if (file_exists(_PS_CAT_IMG_DIR_.(int) $category->id.'-'.$i.'_thumb.jpg')) {
                $files[$i]['type'] = HelperImageUploader::TYPE_IMAGE;
                $files[$i]['image'] = ImageManager::thumbnail(_PS_CAT_IMG_DIR_.(int) $category->id.'-'.$i.'_thumb.jpg', $this->context->controller->table.'_'.(int) $category->id.'-'.$i.'_thumb.jpg', 100, 'jpg', true, true);
                $files[$i]['delete_url'] = Context::getContext()->link->getAdminLink('AdminBlockCategories').'&deleteThumb='.$i.'&id_category='.(int) $category->id;
            }
        }

        $imagesTypes = ImageType::getImagesTypes('categories');
        $formatedMedium = ImageType::getFormatedName('medium');
        foreach ($imagesTypes as $k => $imageType) {
            if ($formatedMedium == $imageType['name']) {
                $this->smarty->assign('format', $imageType);
            }
        }

        $helper = new HelperImageUploader();
        $helper->setMultiple(true)->setUseAjax(true)->setName('thumbnail')->setFiles($files)->setMaxFiles(3)->setUrl(Context::getContext()->link->getAdminLink('AdminBlockCategories').'&ajax=1&id_category='.$category->id.'&action=uploadThumbnailImages');
        $this->smarty->assign('helper', $helper->render());

        return $this->display(__FILE__, 'views/blockcategories_admin.tpl');
    }

    /**
     * @param array $params
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function hookFooter($params)
    {
        $this->setLastVisitedCategory();
        if (!$this->isCached('blockcategories_footer.tpl', $this->getCacheId())) {
            $maxdepth = Configuration::get('BLOCK_CATEG_MAX_DEPTH');
            // Get all groups for this customer and concatenate them as a string: "1,2,3..."
            $groups = implode(', ', Customer::getGroupsStatic((int) $this->context->customer->id));
            if (!$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                '
				SELECT DISTINCT c.id_parent, c.id_category, cl.name, cl.description, cl.link_rewrite
				FROM `'._DB_PREFIX_.'category` c
				'.Shop::addSqlAssociation('category', 'c').'
				LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (c.`id_category` = cl.`id_category` AND cl.`id_lang` = '.(int) $this->context->language->id.Shop::addSqlRestrictionOnLang('cl').')
				LEFT JOIN `'._DB_PREFIX_.'category_group` cg ON (cg.`id_category` = c.`id_category`)
				WHERE (c.`active` = 1 OR c.`id_category` = '.(int) Configuration::get('PS_ROOT_CATEGORY').')
				'.((int) ($maxdepth) != 0 ? ' AND `level_depth` <= '.(int) ($maxdepth) : '').'
				AND cg.`id_group` IN ('.pSQL($groups).')
				ORDER BY `level_depth` ASC, '.(Configuration::get('BLOCK_CATEG_SORT') ? 'cl.`name`' : 'category_shop.`position`').' '.(Configuration::get('BLOCK_CATEG_SORT_WAY') ? 'DESC' : 'ASC')
            )
            ) {
                return null;
            }
            $resultParents = [];
            $resultIds = [];

            foreach ($result as &$row) {
                $resultParents[$row['id_parent']][] = &$row;
                $resultIds[$row['id_category']] = &$row;
            }
            //$nbrColumns = Configuration::get('BLOCK_CATEG_NBR_COLUMNS_FOOTER');
            $nbrColumns = (int) Configuration::get('BLOCK_CATEG_NBR_COLUMN_FOOTER');
            if (!$nbrColumns or empty($nbrColumns)) {
                $nbrColumns = 3;
            }
            $numberColumn = abs(count($result) / $nbrColumns);
            $widthColumn = floor(100 / $nbrColumns);
            $this->smarty->assign('numberColumn', $numberColumn);
            $this->smarty->assign('widthColumn', $widthColumn);

            $blockCategTree = $this->getTree($resultParents, $resultIds, Configuration::get('BLOCK_CATEG_MAX_DEPTH'));
            unset($resultParents, $resultIds);

            $isDhtml = (Configuration::get('BLOCK_CATEG_DHTML') == 1 ? true : false);

            $this->smarty->assign('blockCategTree', $blockCategTree);

            if (file_exists(_PS_THEME_DIR_.'modules/blockcategories/blockcategories_footer.tpl')) {
                $this->smarty->assign('branche_tpl_path', _PS_THEME_DIR_.'modules/blockcategories/category-tree-branch.tpl');
            } else {
                $this->smarty->assign('branche_tpl_path', _PS_MODULE_DIR_.'blockcategories/category-tree-branch.tpl');
            }
            $this->smarty->assign('isDhtml', $isDhtml);
        }
        $display = $this->display(__FILE__, 'blockcategories_footer.tpl', $this->getCacheId());

        return $display;
    }

    /**
     * @return null
     *
     * @since 1.0.0
     */
    public function setLastVisitedCategory()
    {
        $cacheId = 'blockcategories::setLastVisitedCategory';
        if (!Cache::isStored($cacheId)) {
            if (method_exists($this->context->controller, 'getCategory') && ($category = $this->context->controller->getCategory())) {
                $this->context->cookie->last_visited_category = $category->id;
            } elseif (method_exists($this->context->controller, 'getProduct') && ($product = $this->context->controller->getProduct())) {
                if (!isset($this->context->cookie->last_visited_category)
                    || !Product::idIsOnCategoryId($product->id, [['id_category' => $this->context->cookie->last_visited_category]])
                    || !Category::inShopStatic($this->context->cookie->last_visited_category, $this->context->shop)
                ) {
                    $this->context->cookie->last_visited_category = (int) $product->id_category_default;
                }
            }
            Cache::store($cacheId, $this->context->cookie->last_visited_category);
        }

        return Cache::retrieve($cacheId);
    }

    /**
     * @param string|null $name
     *
     * @return string
     *
     * @since 1.0.0
     */
    protected function getCacheId($name = null)
    {
        $cacheId = parent::getCacheId();

        if ($name !== null) {
            $cacheId .= '|'.$name;
        }

        if ((Tools::getValue('id_product') || Tools::getValue('id_category')) && isset($this->context->cookie->last_visited_category) && $this->context->cookie->last_visited_category) {
            $cacheId .= '|'.(int) $this->context->cookie->last_visited_category;
        }

        return $cacheId.'|'.implode('-', Customer::getGroupsStatic($this->context->customer->id));
    }

    /**
     * @param array $resultParents
     * @param array $resultIds
     * @param int   $maxDepth
     * @param null  $idCategory
     * @param int   $currentDepth
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getTree($resultParents, $resultIds, $maxDepth, $idCategory = null, $currentDepth = 0)
    {
        if (is_null($idCategory)) {
            $idCategory = $this->context->shop->getCategory();
        }
        $children = [];
        if (isset($resultParents[$idCategory]) && count($resultParents[$idCategory]) && ($maxDepth == 0 || $currentDepth < $maxDepth)) {
            foreach ($resultParents[$idCategory] as $subcat) {
                $children[] = $this->getTree($resultParents, $resultIds, $maxDepth, $subcat['id_category'], $currentDepth + 1);
            }
        }
        if (isset($resultIds[$idCategory])) {
            $link = $this->context->link->getCategoryLink($idCategory, $resultIds[$idCategory]['link_rewrite']);
            $name = $resultIds[$idCategory]['name'];
            $desc = $resultIds[$idCategory]['description'];
        } else {
            $link = $name = $desc = '';
        }

        $return = [
            'id'       => $idCategory,
            'link'     => $link,
            'name'     => $name,
            'desc'     => $desc,
            'children' => $children,
        ];

        return $return;
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function hookRightColumn($params)
    {
        return $this->hookLeftColumn($params);
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function hookLeftColumn($params)
    {
        $this->setLastVisitedCategory();
        $phpself = $this->context->controller->php_self;
        $currentAllowedControllers = ['category'];

        if ($phpself != null && in_array($phpself, $currentAllowedControllers) && Configuration::get('BLOCK_CATEG_ROOT_CATEGORY') && isset($this->context->cookie->last_visited_category) && $this->context->cookie->last_visited_category) {
            $category = new Category($this->context->cookie->last_visited_category, $this->context->language->id);
            if (Configuration::get('BLOCK_CATEG_ROOT_CATEGORY') == 2 && !$category->is_root_category && $category->id_parent) {
                $category = new Category($category->id_parent, $this->context->language->id);
            } elseif (Configuration::get('BLOCK_CATEG_ROOT_CATEGORY') == 3 && !$category->is_root_category && !$category->getSubCategories($category->id, true)) {
                $category = new Category($category->id_parent, $this->context->language->id);
            }
        } else {
            $category = new Category((int) Configuration::get('PS_HOME_CATEGORY'), $this->context->language->id);
        }

        $cacheId = $this->getCacheId($category ? $category->id : null);

        if (!$this->isCached('blockcategories.tpl', $cacheId)) {
            $range = '';
            $maxdepth = Configuration::get('BLOCK_CATEG_MAX_DEPTH');
            if (Validate::isLoadedObject($category)) {
                if ($maxdepth > 0) {
                    $maxdepth += $category->level_depth;
                }
                $range = 'AND nleft >= '.(int) $category->nleft.' AND nright <= '.(int) $category->nright;
            }

            $resultIds = [];
            $resultParents = [];
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                '
			SELECT c.id_parent, c.id_category, cl.name, cl.description, cl.link_rewrite
			FROM `'._DB_PREFIX_.'category` c
			INNER JOIN `'._DB_PREFIX_.'category_lang` cl ON (c.`id_category` = cl.`id_category` AND cl.`id_lang` = '.(int) $this->context->language->id.Shop::addSqlRestrictionOnLang('cl').')
			INNER JOIN `'._DB_PREFIX_.'category_shop` cs ON (cs.`id_category` = c.`id_category` AND cs.`id_shop` = '.(int) $this->context->shop->id.')
			WHERE (c.`active` = 1 OR c.`id_category` = '.(int) Configuration::get('PS_HOME_CATEGORY').')
			AND c.`id_category` != '.(int) Configuration::get('PS_ROOT_CATEGORY').'
			'.((int) $maxdepth != 0 ? ' AND `level_depth` <= '.(int) $maxdepth : '').'
			'.$range.'
			AND c.id_category IN (
				SELECT id_category
				FROM `'._DB_PREFIX_.'category_group`
				WHERE `id_group` IN ('.pSQL(implode(', ', Customer::getGroupsStatic((int) $this->context->customer->id))).')
			)
			ORDER BY `level_depth` ASC, '.(Configuration::get('BLOCK_CATEG_SORT') ? 'cl.`name`' : 'cs.`position`').' '.(Configuration::get('BLOCK_CATEG_SORT_WAY') ? 'DESC' : 'ASC')
            );
            foreach ($result as &$row) {
                $resultParents[$row['id_parent']][] = &$row;
                $resultIds[$row['id_category']] = &$row;
            }

            $blockCategTree = $this->getTree($resultParents, $resultIds, $maxdepth, ($category ? $category->id : null));
            $this->smarty->assign('blockCategTree', $blockCategTree);

            if ((Tools::getValue('id_product') || Tools::getValue('id_category')) && isset($this->context->cookie->last_visited_category) && $this->context->cookie->last_visited_category) {
                $category = new Category($this->context->cookie->last_visited_category, $this->context->language->id);
                if (Validate::isLoadedObject($category)) {
                    $this->smarty->assign(['currentCategory' => $category, 'currentCategoryId' => $category->id]);
                }
            }

            $this->smarty->assign('isDhtml', Configuration::get('BLOCK_CATEG_DHTML'));
            if (file_exists(_PS_THEME_DIR_.'modules/blockcategories/blockcategories.tpl')) {
                $this->smarty->assign('branche_tpl_path', _PS_THEME_DIR_.'modules/blockcategories/category-tree-branch.tpl');
            } else {
                $this->smarty->assign('branche_tpl_path', _PS_MODULE_DIR_.'blockcategories/category-tree-branch.tpl');
            }
        }

        return $this->display(__FILE__, 'blockcategories.tpl', $cacheId);
    }

    /**
     * @since 1.0.0
     */
    public function hookHeader()
    {
        $this->context->controller->addJS(_THEME_JS_DIR_.'tools/treeManagement.js');
        $this->context->controller->addCSS(($this->_path).'blockcategories.css', 'all');
    }

    /**
     * @param array $params
     *
     * @since 1.0.0
     */
    public function hookCategoryAddition($params)
    {
        $this->clearBlockcategoriesCache();
    }

    /**
     * @param $params
     *
     * @since 1.0.0
     */
    public function hookCategoryUpdate($params)
    {
        $this->clearBlockcategoriesCache();
    }

    /**
     * @param $params
     *
     * @since 1.0.0
     */
    public function hookCategoryDeletion($params)
    {
        $this->clearBlockcategoriesCache();
    }

    /**
     * @param $params
     *
     * @since 1.0.0
     */
    public function hookActionAdminMetaControllerUpdate_optionsBefore($params)
    {
        $this->clearBlockcategoriesCache();
    }
}
