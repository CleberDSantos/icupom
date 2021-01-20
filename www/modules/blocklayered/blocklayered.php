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
 * Class BlockLayered
 */
class BlockLayered extends Module
{
    private $products;
    private $nbr_products;
    private $page = 1;

    /**
     * BlockLayered constructor.
     */
    public function __construct()
    {
        $this->name = 'blocklayered';
        $this->tab = 'front_office_features';
        $this->version = '3.0.5';
        $this->author = 'thirty bees';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Block Layered Navigation');
        $this->description = $this->l('Displays a block with layered navigation filters.');
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6.99.99'];

        if ((int) Tools::getValue('p')) {
            $this->page = (int) Tools::getValue('p');
        }
    }

    public static function pricesIndexProcess($cursor = 0, $ajax = false)
    {
        return self::indexPrices($cursor, false, $ajax);
    }

    private static function getPriceFilterSubQuery($filterValue, $ignore_join = false)
    {
        $idCurrency = (int) Context::getContext()->currency->id;

        if (isset($filterValue) && $filterValue) {
            $priceFilterQuery = '
			INNER JOIN `'._DB_PREFIX_.'layered_price_index` psi ON (psi.id_product = p.id_product AND psi.id_currency = '.(int) $idCurrency.'
			AND psi.price_min <= '.(int) $filterValue[1].' AND psi.price_max >= '.(int) $filterValue[0].' AND psi.id_shop='.(int) Context::getContext()->shop->id.') ';

            return ['join' => $priceFilterQuery];
        }

        return [];
    }

    private static function getWeightFilterSubQuery($filterValue, $ignoreJoin = false)
    {
        if (isset($filterValue) && $filterValue) {
            if ($filterValue[0] != 0 || $filterValue[1] != 0) {
                return ['where' => ' AND p.`weight` BETWEEN '.(float) ($filterValue[0] - 0.001).' AND '.(float) ($filterValue[1] + 0.001).' '];
            }
        }

        return [];
    }

    private static function getId_featureFilterSubQuery($filterValue, $ignoreJoin = false)
    {
        if (empty($filterValue)) {
            return [];
        }
        $queryFilters = ' AND EXISTS (SELECT * FROM '._DB_PREFIX_.'feature_product fp WHERE fp.id_product = p.id_product AND ';
        foreach ($filterValue as $filterVal) {
            $queryFilters .= 'fp.`id_feature_value` = '.(int) $filterVal.' OR ';
        }
        $queryFilters = rtrim($queryFilters, 'OR ').') ';

        return ['where' => $queryFilters];
    }

    private static function getId_attribute_groupFilterSubQuery($filterValue, $ignoreJoin = false)
    {
        if (empty($filterValue)) {
            return [];
        }
        $queryFilters = '
		AND EXISTS (SELECT *
		FROM `'._DB_PREFIX_.'product_attribute_combination` pac
		LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON (pa.`id_product_attribute` = pac.`id_product_attribute`)
		WHERE pa.id_product = p.id_product AND ';

        foreach ($filterValue as $filterVal) {
            $queryFilters .= 'pac.`id_attribute` = '.(int) $filterVal.' OR ';
        }
        $queryFilters = rtrim($queryFilters, 'OR ').') ';

        return ['where' => $queryFilters];
    }

    private static function getCategoryFilterSubQuery($filterValue, $ignoreJoin = false)
    {
        if (empty($filterValue)) {
            return [];
        }
        $queryFiltersWhere = ' AND EXISTS (SELECT * FROM '._DB_PREFIX_.'category_product cp WHERE id_product = p.id_product AND ';
        foreach ($filterValue as $idCategory) {
            $queryFiltersWhere .= 'cp.`id_category` = '.(int) $idCategory.' OR ';
        }
        $queryFiltersWhere = rtrim($queryFiltersWhere, 'OR ').') ';

        return ['where' => $queryFiltersWhere];
    }

    //ATTRIBUTES GROUP

    private static function getQuantityFilterSubQuery($filterValue, $ignoreJoin = false)
    {
        if (empty($filterValue) || count($filterValue) == 2) {
            return [];
        }

        $queryFilters = ' AND sav.quantity '.(!$filterValue[0] ? '<=' : '>').' 0 ';
        $queryFiltersJoin = 'LEFT JOIN `'._DB_PREFIX_.'stock_available` sav ON (sav.id_product = p.id_product AND sav.id_shop = '.(int) Context::getContext()->shop->id.') ';

        return ['where' => $queryFilters, 'join' => $queryFiltersJoin];
    }

    private static function getManufacturerFilterSubQuery($filterValue, $ignoreJoin = false)
    {
        if (empty($filterValue)) {
            $queryFilters = '';
        } else {
            array_walk($filterValue, create_function('&$id_manufacturer', '$id_manufacturer = (int)$id_manufacturer;'));
            $queryFilters = ' AND p.id_manufacturer IN ('.implode($filterValue, ',').')';
        }
        if ($ignoreJoin) {
            return ['where' => $queryFilters];
        } else {
            return ['where' => $queryFilters, 'join' => 'LEFT JOIN `'._DB_PREFIX_.'manufacturer` m ON (m.id_manufacturer = p.id_manufacturer) '];
        }
    }

    private static function getConditionFilterSubQuery($filterValue, $ignoreJoin = false)
    {
        if (empty($filterValue) || count($filterValue) == 3) {
            return [];
        }

        $queryFilters = ' AND p.condition IN (';

        foreach ($filterValue as $cond) {
            $queryFilters .= '\''.$cond.'\',';
        }
        $queryFilters = rtrim($queryFilters, ',').') ';

        return ['where' => $queryFilters];
    }

    public function install()
    {
        if (parent::install() && $this->registerHook('header') && $this->registerHook('leftColumn')
            && $this->registerHook('categoryAddition') && $this->registerHook('categoryUpdate') && $this->registerHook('attributeGroupForm')
            && $this->registerHook('afterSaveAttributeGroup') && $this->registerHook('afterDeleteAttributeGroup') && $this->registerHook('featureForm')
            && $this->registerHook('afterDeleteFeature') && $this->registerHook('afterSaveFeature') && $this->registerHook('categoryDeletion')
            && $this->registerHook('afterSaveProduct') && $this->registerHook('productListAssign') && $this->registerHook('postProcessAttributeGroup')
            && $this->registerHook('postProcessFeature') && $this->registerHook('featureValueForm') && $this->registerHook('postProcessFeatureValue')
            && $this->registerHook('afterDeleteFeatureValue') && $this->registerHook('afterSaveFeatureValue') && $this->registerHook('attributeForm')
            && $this->registerHook('postProcessAttribute') && $this->registerHook('afterDeleteAttribute') && $this->registerHook('afterSaveAttribute') && $this->registerHook('leftColumn')
        ) {
            Configuration::updateValue('PS_LAYERED_HIDE_0_VALUES', 1);
            Configuration::updateValue('PS_LAYERED_SHOW_QTIES', 1);
            Configuration::updateValue('PS_LAYERED_FULL_TREE', 1);
            Configuration::updateValue('PS_LAYERED_FILTER_PRICE_USETAX', 1);
            Configuration::updateValue('PS_LAYERED_FILTER_CATEGORY_DEPTH', 1);
            Configuration::updateValue('PS_LAYERED_FILTER_INDEX_QTY', 0);
            Configuration::updateValue('PS_LAYERED_FILTER_INDEX_CDT', 0);
            Configuration::updateValue('PS_LAYERED_FILTER_INDEX_MNF', 0);
            Configuration::updateValue('PS_LAYERED_FILTER_INDEX_CAT', 0);
            Configuration::updateValue('PS_ATTRIBUTE_ANCHOR_SEPARATOR', '-');
            Configuration::updateValue('PS_LAYERED_FILTER_PRICE_ROUNDING', 1);

            $this->rebuildLayeredStructure();
            $this->buildLayeredCategories();

            $productsCount = Db::getInstance()->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.'product`');

            if ($productsCount < 20000) {
                // Lock template filter creation if too many products
                $this->rebuildLayeredCache();
            }

            self::installPriceIndexTable();
            $this->installFriendlyUrlTable();
            $this->installIndexableAttributeTable();
            $this->installProductAttributeTable();

            if ($productsCount < 5000) {
                // Lock indexation if too many products
                self::fullPricesIndexProcess();
                $this->indexUrl();
                $this->indexAttribute();
            }

            return true;
        } else {
            // Installation failed (or hook registration) => uninstall the module
            $this->uninstall();

            return false;
        }
    }

    //ATTRIBUTES

    public function rebuildLayeredStructure()
    {
        @set_time_limit(0);

        /* Set memory limit to 128M only if current is lower */
        $memoryLimit = @ini_get('memory_limit');
        if (substr($memoryLimit, -1) != 'G' && ((substr($memoryLimit, -1) == 'M' && substr($memoryLimit, 0, -1) < 128) || is_numeric($memoryLimit) && (intval($memoryLimit) < 131072))) {
            @ini_set('memory_limit', '128M');
        }

        /* Delete and re-create the layered categories table */
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'layered_category');
        Db::getInstance()->execute(
            '
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'layered_category` (
		`id_layered_category` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
		`id_shop` INT(11) UNSIGNED NOT NULL,
		`id_category` INT(10) UNSIGNED NOT NULL,
		`id_value` INT(10) UNSIGNED NULL DEFAULT \'0\',
		`type` ENUM(\'category\',\'id_feature\',\'id_attribute_group\',\'quantity\',\'condition\',\'manufacturer\',\'weight\',\'price\') NOT NULL,
		`position` INT(10) UNSIGNED NOT NULL,
		`filter_type` INT(10) UNSIGNED NOT NULL DEFAULT 0,
		`filter_show_limit` INT(10) UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (`id_layered_category`),
		KEY `id_category` (`id_category`,`type`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;'
        ); /* MyISAM + latin1 = Smaller/faster */

        Db::getInstance()->execute(
            '
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'layered_filter` (
		`id_layered_filter` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
		`name` VARCHAR(64) NOT NULL,
		`filters` TEXT NULL,
		`n_categories` INT(10) UNSIGNED NOT NULL,
		`date_add` DATETIME NOT NULL
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'
        );

        Db::getInstance()->execute(
            '
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'layered_filter_shop` (
		`id_layered_filter` INT(10) UNSIGNED NOT NULL,
		`id_shop` INT(11) UNSIGNED NOT NULL,
		PRIMARY KEY (`id_layered_filter`, `id_shop`),
		KEY `id_shop` (`id_shop`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'
        );
    }

    public function buildLayeredCategories()
    {
        // Get all filter template
        $res = Db::getInstance()->executeS('SELECT * FROM '._DB_PREFIX_.'layered_filter ORDER BY date_add DESC');
        $categories = [];
        // Remove all from layered_category
        Db::getInstance()->execute('TRUNCATE '._DB_PREFIX_.'layered_category');

        if (!count($res))
        {
            // No filters templates defined, nothing else to do
            return true;
        }

        $sqlToInsert = 'INSERT INTO '._DB_PREFIX_.'layered_category (id_category, id_shop, id_value, type, position, filter_show_limit, filter_type) VALUES ';
        $values = false;

        foreach ($res as $filterTemplate) {
            $data = json_decode($filterTemplate['filters'], true);
            // Retrocompatibility for module versions <= 3.0.3, which used to
            // store data not JSON encoded, but serialized. Can get removed a
            // couple of releases later.
            if ( ! $data) {
                $data = Tools::unSerialize($filterTemplate['filters']);
            }

            foreach ($data['shop_list'] as $idShop) {
                if (!isset($categories[$idShop])) {
                    $categories[$idShop] = [];
                }

                foreach ($data['categories'] as $idCategory) {
                    $n = 0;
                    if (!in_array($idCategory, $categories[$idShop]))
                    {
                        // Last definition, erase preivious categories defined
                        $categories[$idShop][] = $idCategory;

                        foreach ($data as $key => $value) {
                            if (substr($key, 0, 17) == 'layered_selection') {
                                $values = true;
                                $type = $value['filter_type'];
                                $limit = $value['filter_show_limit'];
                                $n++;

                                if ($key == 'layered_selection_stock') {
                                    $sqlToInsert .= '('.(int) $idCategory.', '.(int) $idShop.', NULL,\'quantity\','.(int) $n.', '.(int) $limit.', '.(int) $type.'),';
                                } else {
                                    if ($key == 'layered_selection_subcategories') {
                                        $sqlToInsert .= '('.(int) $idCategory.', '.(int) $idShop.', NULL,\'category\','.(int) $n.', '.(int) $limit.', '.(int) $type.'),';
                                    } else {
                                        if ($key == 'layered_selection_condition') {
                                            $sqlToInsert .= '('.(int) $idCategory.', '.(int) $idShop.', NULL,\'condition\','.(int) $n.', '.(int) $limit.', '.(int) $type.'),';
                                        } else {
                                            if ($key == 'layered_selection_weight_slider') {
                                                $sqlToInsert .= '('.(int) $idCategory.', '.(int) $idShop.', NULL,\'weight\','.(int) $n.', '.(int) $limit.', '.(int) $type.'),';
                                            } else {
                                                if ($key == 'layered_selection_price_slider') {
                                                    $sqlToInsert .= '('.(int) $idCategory.', '.(int) $idShop.', NULL,\'price\','.(int) $n.', '.(int) $limit.', '.(int) $type.'),';
                                                } else {
                                                    if ($key == 'layered_selection_manufacturer') {
                                                        $sqlToInsert .= '('.(int) $idCategory.', '.(int) $idShop.', NULL,\'manufacturer\','.(int) $n.', '.(int) $limit.', '.(int) $type.'),';
                                                    } else {
                                                        if (substr($key, 0, 21) == 'layered_selection_ag_') {
                                                            $sqlToInsert .= '('.(int) $idCategory.', '.(int) $idShop.', '.(int) str_replace('layered_selection_ag_', '', $key).',
										\'id_attribute_group\','.(int) $n.', '.(int) $limit.', '.(int) $type.'),';
                                                        } else {
                                                            if (substr($key, 0, 23) == 'layered_selection_feat_') {
                                                                $sqlToInsert .= '('.(int) $idCategory.', '.(int) $idShop.', '.(int) str_replace('layered_selection_feat_', '', $key).',
										\'id_feature\','.(int) $n.', '.(int) $limit.', '.(int) $type.'),';
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($values) {
            Db::getInstance()->execute(rtrim($sqlToInsert, ','));
        }
    }

    public function rebuildLayeredCache($productsIds = [], $categoriesIds = [])
    {
        @set_time_limit(0);

        $filterData = ['categories' => []];

        /* Set memory limit to 128M only if current is lower */
        $memoryLimit = @ini_get('memory_limit');
        if (substr($memoryLimit, -1) != 'G' && ((substr($memoryLimit, -1) == 'M' && substr($memoryLimit, 0, -1) < 128) || is_numeric($memoryLimit) && (intval($memoryLimit) < 131072))) {
            @ini_set('memory_limit', '128M');
        }

        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $nCategories = [];
        $doneCategories = [];

        $alias = 'product_shop';
        $joinProduct = Shop::addSqlAssociation('product', 'p');
        $joinProductAttribute = Shop::addSqlAssociation('product_attribute', 'pa');

        $attributeGroups = self::query(
            '
		SELECT a.id_attribute, a.id_attribute_group
		FROM '._DB_PREFIX_.'attribute a
		LEFT JOIN '._DB_PREFIX_.'product_attribute_combination pac ON (pac.id_attribute = a.id_attribute)
		LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON (pa.id_product_attribute = pac.id_product_attribute)
		LEFT JOIN '._DB_PREFIX_.'product p ON (p.id_product = pa.id_product)
		'.$joinProduct.$joinProductAttribute.'
		LEFT JOIN '._DB_PREFIX_.'category_product cp ON (cp.id_product = p.id_product)
		LEFT JOIN '._DB_PREFIX_.'category c ON (c.id_category = cp.id_category)
		WHERE c.active = 1'.
            (count($categoriesIds) ? ' AND cp.id_category IN ('.implode(',', $categoriesIds).')' : '').'
		AND '.$alias.'.active = 1 AND '.$alias.'.`visibility` IN ("both", "catalog")
		'.(count($productsIds) ? 'AND p.id_product IN ('.implode(',', $productsIds).')' : '')
        );

        $attributeGroupsById = [];
        while ($row = $db->nextRow($attributeGroups)) {
            $attributeGroupsById[(int) $row['id_attribute']] = (int) $row['id_attribute_group'];
        }

        $features = self::query(
            '
		SELECT fv.id_feature_value, fv.id_feature
		FROM '._DB_PREFIX_.'feature_value fv
		LEFT JOIN '._DB_PREFIX_.'feature_product fp ON (fp.id_feature_value = fv.id_feature_value)
		LEFT JOIN '._DB_PREFIX_.'product p ON (p.id_product = fp.id_product)
		'.$joinProduct.'
		LEFT JOIN '._DB_PREFIX_.'category_product cp ON (cp.id_product = p.id_product)
		LEFT JOIN '._DB_PREFIX_.'category c ON (c.id_category = cp.id_category)
		WHERE (fv.custom IS NULL OR fv.custom = 0) AND c.active = 1'.(count($categoriesIds) ? ' AND cp.id_category IN ('.implode(',', $categoriesIds).')' : '').'
		AND '.$alias.'.active = 1 AND '.$alias.'.`visibility` IN ("both", "catalog") '.(count($productsIds) ? 'AND p.id_product IN ('.implode(',', $productsIds).')' : '')
        );

        $featuresById = [];
        while ($row = $db->nextRow($features)) {
            $featuresById[(int) $row['id_feature_value']] = (int) $row['id_feature'];
        }

        $result = self::query(
            '
		SELECT p.id_product,
		GROUP_CONCAT(DISTINCT fv.id_feature_value) features,
		GROUP_CONCAT(DISTINCT cp.id_category) categories,
		GROUP_CONCAT(DISTINCT pac.id_attribute) attributes
		FROM '._DB_PREFIX_.'product p
		LEFT JOIN '._DB_PREFIX_.'category_product cp ON (cp.id_product = p.id_product)
		LEFT JOIN '._DB_PREFIX_.'category c ON (c.id_category = cp.id_category)
		LEFT JOIN '._DB_PREFIX_.'feature_product fp ON (fp.id_product = p.id_product)
		LEFT JOIN '._DB_PREFIX_.'feature_value fv ON (fv.id_feature_value = fp.id_feature_value)
		LEFT JOIN '._DB_PREFIX_.'product_attribute pa ON (pa.id_product = p.id_product)
		'.$joinProduct.$joinProductAttribute.'
		LEFT JOIN '._DB_PREFIX_.'product_attribute_combination pac ON (pac.id_product_attribute = pa.id_product_attribute)
		WHERE c.active = 1'.(count($categoriesIds) ? ' AND cp.id_category IN ('.implode(',', $categoriesIds).')' : '').'
		AND '.$alias.'.active = 1 AND '.$alias.'.`visibility` IN ("both", "catalog")
		'.(count($productsIds) ? 'AND p.id_product IN ('.implode(',', $productsIds).')' : '').
            ' AND (fv.custom IS NULL OR fv.custom = 0)
		GROUP BY p.id_product'
        );

        $shopList = Shop::getShops(false, null, true);

        $toInsert = false;
        while ($product = $db->nextRow($result)) {
            $a = $c = $f = [];
            if (!empty($product['attributes'])) {
                $a = array_flip(explode(',', $product['attributes']));
            }
            if (!empty($product['categories'])) {
                $c = array_flip(explode(',', $product['categories']));
            }
            if (!empty($product['features'])) {
                $f = array_flip(explode(',', $product['features']));
            }

            $filterData['shop_list'] = $shopList;

            foreach ($c as $idCategory => $category) {
                if (!in_array($idCategory, $filterData['categories'])) {
                    $filterData['categories'][] = $idCategory;
                }

                if (!isset($nCategories[(int) $idCategory])) {
                    $nCategories[(int) $idCategory] = 1;
                }
                if (!isset($doneCategories[(int) $idCategory]['cat'])) {
                    $filterData['layered_selection_subcategories'] = ['filter_type' => 0, 'filter_show_limit' => 0];
                    $doneCategories[(int) $idCategory]['cat'] = true;
                    $toInsert = true;
                }
                if (is_array($attributeGroupsById) && count($attributeGroupsById) > 0) {
                    foreach ($a as $kAttribute => $attribute) {
                        if (!isset($doneCategories[(int) $idCategory]['a'.(int) $attributeGroupsById[(int) $kAttribute]])) {
                            $filterData['layered_selection_ag_'.(int) $attributeGroupsById[(int) $kAttribute]] = ['filter_type' => 0, 'filter_show_limit' => 0];
                            $doneCategories[(int) $idCategory]['a'.(int) $attributeGroupsById[(int) $kAttribute]] = true;
                            $toInsert = true;
                        }
                    }
                }
                if (is_array($attributeGroupsById) && count($attributeGroupsById) > 0) {
                    foreach ($f as $kFeature => $feature) {
                        if (!isset($doneCategories[(int) $idCategory]['f'.(int) $featuresById[(int) $kFeature]])) {
                            $filterData['layered_selection_feat_'.(int) $featuresById[(int) $kFeature]] = ['filter_type' => 0, 'filter_show_limit' => 0];
                            $doneCategories[(int) $idCategory]['f'.(int) $featuresById[(int) $kFeature]] = true;
                            $toInsert = true;
                        }
                    }
                }
                if (!isset($doneCategories[(int) $idCategory]['q'])) {
                    $filterData['layered_selection_stock'] = ['filter_type' => 0, 'filter_show_limit' => 0];
                    $doneCategories[(int) $idCategory]['q'] = true;
                    $toInsert = true;
                }
                if (!isset($doneCategories[(int) $idCategory]['m'])) {
                    $filterData['layered_selection_manufacturer'] = ['filter_type' => 0, 'filter_show_limit' => 0];
                    $doneCategories[(int) $idCategory]['m'] = true;
                    $toInsert = true;
                }
                if (!isset($doneCategories[(int) $idCategory]['c'])) {
                    $filterData['layered_selection_condition'] = ['filter_type' => 0, 'filter_show_limit' => 0];
                    $doneCategories[(int) $idCategory]['c'] = true;
                    $toInsert = true;
                }
                if (!isset($doneCategories[(int) $idCategory]['w'])) {
                    $filterData['layered_selection_weight_slider'] = ['filter_type' => 0, 'filter_show_limit' => 0];
                    $doneCategories[(int) $idCategory]['w'] = true;
                    $toInsert = true;
                }
                if (!isset($doneCategories[(int) $idCategory]['p'])) {
                    $filterData['layered_selection_price_slider'] = ['filter_type' => 0, 'filter_show_limit' => 0];
                    $doneCategories[(int) $idCategory]['p'] = true;
                    $toInsert = true;
                }
            }
        }
        if ($toInsert) {
            Db::getInstance()->execute(
                'INSERT INTO '._DB_PREFIX_.'layered_filter(name, filters, n_categories, date_add)
				VALUES (\''.sprintf($this->l('My template %s'), date('Y-m-d')).'\', \''.pSQL(json_encode($filterData)).'\', '.count($filterData['categories']).', NOW())'
            );

            $lastId = Db::getInstance()->Insert_ID();
            Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'layered_filter_shop WHERE `id_layered_filter` = '.$lastId);
            foreach ($shopList as $idShop) {
                Db::getInstance()->execute(
                    'INSERT INTO '._DB_PREFIX_.'layered_filter_shop (`id_layered_filter`, `id_shop`)
					VALUES('.$lastId.', '.(int) $idShop.')'
                );
            }

            $this->buildLayeredCategories();
        }
    }

    private static function query($sqlQuery)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->query($sqlQuery);
    }

    //FEATURES

    private static function installPriceIndexTable()
    {
        Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'layered_price_index`');

        Db::getInstance()->execute(
            '
		CREATE TABLE `'._DB_PREFIX_.'layered_price_index` (
			`id_product` INT  NOT NULL,
			`id_currency` INT NOT NULL,
			`id_shop` INT NOT NULL,
			`price_min` INT NOT NULL,
			`price_max` INT NOT NULL,
		PRIMARY KEY (`id_product`, `id_currency`, `id_shop`),
		INDEX `id_currency` (`id_currency`),
		INDEX `price_min` (`price_min`), INDEX `price_max` (`price_max`)
		)  ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'
        );
    }

    private function installFriendlyUrlTable()
    {
        Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'layered_friendly_url`');
        Db::getInstance()->execute(
            '
		CREATE TABLE `'._DB_PREFIX_.'layered_friendly_url` (
		`id_layered_friendly_url` INT NOT NULL AUTO_INCREMENT,
		`url_key` VARCHAR(32) NOT NULL,
		`data` VARCHAR(200) NOT NULL,
		`id_lang` INT NOT NULL,
		PRIMARY KEY (`id_layered_friendly_url`),
		INDEX `id_lang` (`id_lang`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'
        );

        Db::getInstance()->execute('CREATE INDEX `url_key` ON `'._DB_PREFIX_.'layered_friendly_url`(url_key(5))');
    }

    private function installIndexableAttributeTable()
    {
        // Attributes Groups
        Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'layered_indexable_attribute_group`');
        Db::getInstance()->execute(
            '
		CREATE TABLE `'._DB_PREFIX_.'layered_indexable_attribute_group` (
		`id_attribute_group` INT NOT NULL,
		`indexable` BOOL NOT NULL DEFAULT 0,
		PRIMARY KEY (`id_attribute_group`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'
        );
        Db::getInstance()->execute(
            '
		INSERT INTO `'._DB_PREFIX_.'layered_indexable_attribute_group`
		SELECT id_attribute_group, 1 FROM `'._DB_PREFIX_.'attribute_group`'
        );

        Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'layered_indexable_attribute_group_lang_value`');
        Db::getInstance()->execute(
            '
		CREATE TABLE `'._DB_PREFIX_.'layered_indexable_attribute_group_lang_value` (
		`id_attribute_group` INT NOT NULL,
		`id_lang` INT NOT NULL,
		`url_name` VARCHAR(128),
		`meta_title` VARCHAR(128),
		PRIMARY KEY (`id_attribute_group`, `id_lang`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'
        );

        // Attributes
        Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'layered_indexable_attribute_lang_value`');
        Db::getInstance()->execute(
            '
		CREATE TABLE `'._DB_PREFIX_.'layered_indexable_attribute_lang_value` (
		`id_attribute` INT NOT NULL,
		`id_lang` INT NOT NULL,
		`url_name` VARCHAR(128),
		`meta_title` VARCHAR(128),
		PRIMARY KEY (`id_attribute`, `id_lang`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'
        );

        // Features
        Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'layered_indexable_feature`');
        Db::getInstance()->execute(
            '
		CREATE TABLE `'._DB_PREFIX_.'layered_indexable_feature` (
		`id_feature` INT NOT NULL,
		`indexable` BOOL NOT NULL DEFAULT 0,
		PRIMARY KEY (`id_feature`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'
        );

        Db::getInstance()->execute(
            '
		INSERT INTO `'._DB_PREFIX_.'layered_indexable_feature`
		SELECT id_feature, 1 FROM `'._DB_PREFIX_.'feature`'
        );

        Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'layered_indexable_feature_lang_value`');
        Db::getInstance()->execute(
            '
		CREATE TABLE `'._DB_PREFIX_.'layered_indexable_feature_lang_value` (
		`id_feature` INT NOT NULL,
		`id_lang` INT NOT NULL,
		`url_name` VARCHAR(128) NOT NULL,
		`meta_title` VARCHAR(128),
		PRIMARY KEY (`id_feature`, `id_lang`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'
        );

        // Features values
        Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'layered_indexable_feature_value_lang_value`');
        Db::getInstance()->execute(
            '
		CREATE TABLE `'._DB_PREFIX_.'layered_indexable_feature_value_lang_value` (
		`id_feature_value` INT NOT NULL,
		`id_lang` INT NOT NULL,
		`url_name` VARCHAR(128),
		`meta_title` VARCHAR(128),
		PRIMARY KEY (`id_feature_value`, `id_lang`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'
        );
    }

    /**
     *
     * create table product attribute
     */
    public function installProductAttributeTable()
    {
        Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'layered_product_attribute`');
        Db::getInstance()->execute(
            '
		CREATE TABLE `'._DB_PREFIX_.'layered_product_attribute` (
		`id_attribute` INT(10) UNSIGNED NOT NULL,
		`id_product` INT(10) UNSIGNED NOT NULL,
		`id_attribute_group` INT(10) UNSIGNED NOT NULL DEFAULT "0",
		`id_shop` INT(10) UNSIGNED NOT NULL DEFAULT "1",
		PRIMARY KEY (`id_attribute`, `id_product`, `id_shop`),
		UNIQUE KEY `id_attribute_group` (`id_attribute_group`,`id_attribute`,`id_product`, `id_shop`)
		) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;'
        );
    }

    //FEATURES VALUE

    public static function fullPricesIndexProcess($cursor = 0, $ajax = false, $smart = false)
    {
        if ($cursor == 0 && !$smart) {
            self::installPriceIndexTable();
        }

        return self::indexPrices($cursor, true, $ajax, $smart);
    }

    private static function indexPrices($cursor = null, $full = false, $ajax = false, $smart = false)
    {
        if ($full) {
            $nb_products = (int) Db::getInstance()->getValue(
                '
				SELECT count(DISTINCT p.`id_product`)
				FROM '._DB_PREFIX_.'product p
				INNER JOIN `'._DB_PREFIX_.'product_shop` ps
					ON (ps.`id_product` = p.`id_product` AND ps.`active` = 1 AND ps.`visibility` IN ("both", "catalog"))'
            );
        } else {
            $nb_products = (int) Db::getInstance()->getValue(
                '
				SELECT COUNT(DISTINCT p.`id_product`) FROM `'._DB_PREFIX_.'product` p
				INNER JOIN `'._DB_PREFIX_.'product_shop` ps
					ON (ps.`id_product` = p.`id_product` AND ps.`active` = 1 AND ps.`visibility` IN ("both", "catalog"))
				LEFT JOIN  `'._DB_PREFIX_.'layered_price_index` psi ON (psi.id_product = p.id_product)
				WHERE psi.id_product IS NULL'
            );
        }

        $max_executiontime = @ini_get('max_execution_time');
        if ($max_executiontime > 5 || $max_executiontime <= 0) {
            $max_executiontime = 5;
        }

        $start_time = microtime(true);

        if (function_exists('memory_get_peak_usage')) {
            do {
                $cursor = (int) self::indexPricesUnbreakable((int) $cursor, $full, $smart);
                $time_elapsed = microtime(true) - $start_time;
            } while ($cursor < $nb_products && Tools::getMemoryLimit() > memory_get_peak_usage() && $time_elapsed < $max_executiontime);
        } else {
            do {
                $cursor = (int) self::indexPricesUnbreakable((int) $cursor, $full, $smart);
                $time_elapsed = microtime(true) - $start_time;
            } while ($cursor < $nb_products && $time_elapsed < $max_executiontime);
        }

        if (($nb_products > 0 && !$full || $cursor < $nb_products && $full) && !$ajax) {
            $token = substr(Tools::encrypt('blocklayered/index'), 0, 10);
            if (Tools::usingSecureMode()) {
                $domain = Tools::getShopDomainSsl(true);
            } else {
                $domain = Tools::getShopDomain(true);
            }

            if (!Tools::file_get_contents($domain.__PS_BASE_URI__.'modules/blocklayered/blocklayered-price-indexer.php?token='.$token.'&cursor='.(int) $cursor.'&full='.(int) $full)) {
                self::indexPrices((int) $cursor, (int) $full);
            }

            return $cursor;
        }
        if ($ajax && $nb_products > 0 && $cursor < $nb_products && $full) {
            return '{"cursor": '.$cursor.', "count": '.($nb_products - $cursor).'}';
        } else {
            if ($ajax && $nb_products > 0 && !$full) {
                return '{"cursor": '.$cursor.', "count": '.($nb_products).'}';
            } else {
                Configuration::updateGlobalValue('PS_LAYERED_INDEXED', 1);

                if ($ajax) {
                    return '{"result": "ok"}';
                } else {
                    return -1;
                }
            }
        }
    }

    private static function indexPricesUnbreakable($cursor, $full = false, $smart = false)
    {
        static $length = 100; // Nb of products to index

        if (is_null($cursor)) {
            $cursor = 0;
        }

        if ($full) {
            $query = '
				SELECT p.`id_product`
				FROM `'._DB_PREFIX_.'product` p
				INNER JOIN `'._DB_PREFIX_.'product_shop` ps
					ON (ps.`id_product` = p.`id_product` AND ps.`active` = 1 AND ps.`visibility` IN ("both", "catalog"))
				GROUP BY p.`id_product`
				ORDER BY p.`id_product` LIMIT '.(int) $cursor.','.(int) $length;
        } else {
            $query = '
				SELECT p.`id_product`
				FROM `'._DB_PREFIX_.'product` p
				INNER JOIN `'._DB_PREFIX_.'product_shop` ps
					ON (ps.`id_product` = p.`id_product` AND ps.`active` = 1 AND ps.`visibility` IN ("both", "catalog"))
				LEFT JOIN  `'._DB_PREFIX_.'layered_price_index` psi ON (psi.id_product = p.id_product)
				WHERE psi.id_product IS NULL
				GROUP BY p.`id_product`
				ORDER BY p.`id_product` LIMIT 0,'.(int) $length;
        }

        foreach (Db::getInstance()->executeS($query) as $product) {
            self::indexProductPrices((int) $product['id_product'], ($smart && $full));
        }

        return (int) ($cursor + $length);
    }

    public static function indexProductPrices($idProduct, $smart = true)
    {
        static $groups = null;

        if (is_null($groups)) {
            $groups = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT id_group FROM `'._DB_PREFIX_.'group_reduction`');
            if (!$groups) {
                $groups = [];
            }
        }

        $shopList = Shop::getShops(false, null, true);

        foreach ($shopList as $idShop) {
            static $currencyList = null;

            if (is_null($currencyList)) {
                $currencyList = Currency::getCurrencies(false, 1, new Shop($idShop));
            }

            $minPrice = [];
            $maxPrice = [];

            if ($smart) {
                Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'layered_price_index` WHERE `id_product` = '.(int) $idProduct.' AND `id_shop` = '.(int) $idShop);
            }

            if (Configuration::get('PS_LAYERED_FILTER_PRICE_USETAX')) {
                $maxTaxRate = Db::getInstance()->getValue(
                    '
					SELECT max(t.rate) max_rate
					FROM `'._DB_PREFIX_.'product_shop` p
					LEFT JOIN `'._DB_PREFIX_.'tax_rules_group` trg ON (trg.id_tax_rules_group = p.id_tax_rules_group AND p.id_shop = '.(int) $idShop.')
					LEFT JOIN `'._DB_PREFIX_.'tax_rule` tr ON (tr.id_tax_rules_group = trg.id_tax_rules_group)
					LEFT JOIN `'._DB_PREFIX_.'tax` t ON (t.id_tax = tr.id_tax AND t.active = 1)
					WHERE id_product = '.(int) $idProduct.'
					GROUP BY id_product'
                );
            } else {
                $maxTaxRate = 0;
            }

            $productMinPrices = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                '
			SELECT id_shop, id_currency, id_country, id_group, from_quantity
			FROM `'._DB_PREFIX_.'specific_price`
			WHERE id_product = '.(int) $idProduct
            );

            // Get min price
            foreach ($currencyList as $currency) {
                $price = Product::priceCalculation(
                    $idShop,
                    (int) $idProduct,
                    null,
                    null,
                    null,
                    null,
                    $currency['id_currency'],
                    null,
                    null,
                    false,
                    _TB_PRICE_DATABASE_PRECISION_,
                    false,
                    true,
                    true,
                    $specificPriceOutput,
                    true
                );

                if (!isset($maxPrice[$currency['id_currency']])) {
                    $maxPrice[$currency['id_currency']] = 0;
                }
                if (!isset($minPrice[$currency['id_currency']])) {
                    $minPrice[$currency['id_currency']] = null;
                }
                if ($price > $maxPrice[$currency['id_currency']]) {
                    $maxPrice[$currency['id_currency']] = $price;
                }
                if ($price == 0) {
                    continue;
                }
                if (is_null($minPrice[$currency['id_currency']]) || $price < $minPrice[$currency['id_currency']]) {
                    $minPrice[$currency['id_currency']] = $price;
                }
            }

            foreach ($productMinPrices as $specificPrice) {
                foreach ($currencyList as $currency) {
                    if ($specificPrice['id_currency'] && $specificPrice['id_currency'] != $currency['id_currency']) {
                        continue;
                    }
                    $price = Product::priceCalculation(
                        (($specificPrice['id_shop'] == 0) ?
                            null :
                            (int) $specificPrice['id_shop']),
                        (int) $idProduct,
                        null,
                        (($specificPrice['id_country'] == 0) ?
                            null :
                            $specificPrice['id_country']),
                        null,
                        null,
                        $currency['id_currency'],
                        (($specificPrice['id_group'] == 0) ?
                            null :
                            $specificPrice['id_group']),
                        $specificPrice['from_quantity'],
                        false,
                        _TB_PRICE_DATABASE_PRECISION_,
                        false,
                        true,
                        true,
                        $specificPriceOutput,
                        true
                    );

                    if (!isset($maxPrice[$currency['id_currency']])) {
                        $maxPrice[$currency['id_currency']] = 0;
                    }
                    if (!isset($minPrice[$currency['id_currency']])) {
                        $minPrice[$currency['id_currency']] = null;
                    }
                    if ($price > $maxPrice[$currency['id_currency']]) {
                        $maxPrice[$currency['id_currency']] = $price;
                    }
                    if ($price == 0) {
                        continue;
                    }
                    if (is_null($minPrice[$currency['id_currency']]) || $price < $minPrice[$currency['id_currency']]) {
                        $minPrice[$currency['id_currency']] = $price;
                    }
                }
            }

            foreach ($groups as $group) {
                foreach ($currencyList as $currency) {
                    $price = Product::priceCalculation(
                        null,
                        (int) $idProduct,
                        null,
                        null,
                        null,
                        null,
                        (int) $currency['id_currency'],
                        (int) $group['id_group'],
                        null,
                        false,
                        _TB_PRICE_DATABASE_PRECISION_,
                        false,
                        true,
                        true,
                        $specificPriceOutput,
                        true
                    );

                    if (!isset($maxPrice[$currency['id_currency']])) {
                        $maxPrice[$currency['id_currency']] = 0;
                    }
                    if (!isset($minPrice[$currency['id_currency']])) {
                        $minPrice[$currency['id_currency']] = null;
                    }
                    if ($price > $maxPrice[$currency['id_currency']]) {
                        $maxPrice[$currency['id_currency']] = $price;
                    }
                    if ($price == 0) {
                        continue;
                    }
                    if (is_null($minPrice[$currency['id_currency']]) || $price < $minPrice[$currency['id_currency']]) {
                        $minPrice[$currency['id_currency']] = $price;
                    }
                }
            }

            $values = [];
            foreach ($currencyList as $currency) {
                $values[] = '('.(int) $idProduct.',
					'.(int) $currency['id_currency'].',
					'.$idShop.',
					'.(int) $minPrice[$currency['id_currency']].',
					'.(int) Tools::ps_round($maxPrice[$currency['id_currency']] * (100 + $maxTaxRate) / 100, 0).')';
            }

            Db::getInstance()->execute(
                '
				INSERT INTO `'._DB_PREFIX_.'layered_price_index` (id_product, id_currency, id_shop, price_min, price_max)
				VALUES '.implode(',', $values).'
				ON DUPLICATE KEY UPDATE id_product = id_product # avoid duplicate keys'
            );
        }
    }

    public function indexUrl($ajax = false, $truncate = true)
    {
        if ($truncate) {
            Db::getInstance()->execute('TRUNCATE '._DB_PREFIX_.'layered_friendly_url');
        }

        $attributeValuesByLang = [];
        $filters = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
			SELECT lc.*, id_lang, name, link_rewrite, cl.id_category
			FROM '._DB_PREFIX_.'layered_category lc
			INNER JOIN '._DB_PREFIX_.'category_lang cl ON (cl.id_category = lc.id_category AND lc.id_category <> 1 )
			GROUP BY type, id_value, id_lang'
        );

        if (!$filters) {
            return;
        }

        foreach ($filters as $filter)
            switch ($filter['type']) {
                case 'id_attribute_group':
                    $attributes = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                        '
						SELECT agl.public_name name, a.id_attribute_group id_name, al.name value, a.id_attribute id_value, al.id_lang,
						liagl.url_name name_url_name, lial.url_name value_url_name
						FROM '._DB_PREFIX_.'attribute_group ag
						INNER JOIN '._DB_PREFIX_.'attribute_group_lang agl ON (agl.id_attribute_group = ag.id_attribute_group)
						INNER JOIN '._DB_PREFIX_.'attribute a ON (a.id_attribute_group = ag.id_attribute_group)
						INNER JOIN '._DB_PREFIX_.'attribute_lang al ON (al.id_attribute = a.id_attribute)
						LEFT JOIN '._DB_PREFIX_.'layered_indexable_attribute_group liag ON (liag.id_attribute_group = a.id_attribute_group)
						LEFT JOIN '._DB_PREFIX_.'layered_indexable_attribute_group_lang_value liagl
						ON (liagl.id_attribute_group = ag.id_attribute_group AND liagl.id_lang = '.(int) $filter['id_lang'].')
						LEFT JOIN '._DB_PREFIX_.'layered_indexable_attribute_lang_value lial
						ON (lial.id_attribute = a.id_attribute  AND lial.id_lang = '.(int) $filter['id_lang'].')
						WHERE a.id_attribute_group = '.(int) $filter['id_value'].' AND agl.id_lang = al.id_lang AND agl.id_lang = '.(int) $filter['id_lang']
                    );

                    foreach ($attributes as $attribute) {
                        if (!isset($attributeValuesByLang[$attribute['id_lang']])) {
                            $attributeValuesByLang[$attribute['id_lang']] = [];
                        }
                        if (!isset($attributeValuesByLang[$attribute['id_lang']]['c'.$attribute['id_name']])) {
                            $attributeValuesByLang[$attribute['id_lang']]['c'.$attribute['id_name']] = [];
                        }
                        $attributeValuesByLang[$attribute['id_lang']]['c'.$attribute['id_name']][] = [
                            'name'          => (!empty($attribute['name_url_name']) ? $attribute['name_url_name'] : $attribute['name']),
                            'id_name'       => 'c'.$attribute['id_name'],
                            'value'         => (!empty($attribute['value_url_name']) ? $attribute['value_url_name'] : $attribute['value']),
                            'id_value'      => $attribute['id_name'].'_'.$attribute['id_value'],
                            'id_id_value'   => $attribute['id_value'],
                            'category_name' => $filter['link_rewrite'],
                            'type'          => $filter['type'],
                        ];
                    }
                    break;

                case 'id_feature':
                    $features = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                        '
						SELECT fl.name name, fl.id_feature id_name, fvl.id_feature_value id_value, fvl.value value, fl.id_lang, fl.id_lang,
						lifl.url_name name_url_name, lifvl.url_name value_url_name
						FROM '._DB_PREFIX_.'feature_lang fl
						LEFT JOIN '._DB_PREFIX_.'layered_indexable_feature lif ON (lif.id_feature = fl.id_feature)
						INNER JOIN '._DB_PREFIX_.'feature_value fv ON (fv.id_feature = fl.id_feature)
						INNER JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fvl.id_feature_value = fv.id_feature_value)
						LEFT JOIN '._DB_PREFIX_.'layered_indexable_feature_lang_value lifl
						ON (lifl.id_feature = fl.id_feature AND lifl.id_lang = '.(int) $filter['id_lang'].')
						LEFT JOIN '._DB_PREFIX_.'layered_indexable_feature_value_lang_value lifvl
						ON (lifvl.id_feature_value = fvl.id_feature_value AND lifvl.id_lang = '.(int) $filter['id_lang'].')
						WHERE fl.id_feature = '.(int) $filter['id_value'].' AND fvl.id_lang = fl.id_lang AND fvl.id_lang = '.(int) $filter['id_lang']
                    );

                    foreach ($features as $feature) {
                        if (!isset($attributeValuesByLang[$feature['id_lang']])) {
                            $attributeValuesByLang[$feature['id_lang']] = [];
                        }
                        if (!isset($attributeValuesByLang[$feature['id_lang']]['f'.$feature['id_name']])) {
                            $attributeValuesByLang[$feature['id_lang']]['f'.$feature['id_name']] = [];
                        }
                        $attributeValuesByLang[$feature['id_lang']]['f'.$feature['id_name']][] = [
                            'name'          => (!empty($feature['name_url_name']) ? $feature['name_url_name'] : $feature['name']),
                            'id_name'       => 'f'.$feature['id_name'],
                            'value'         => (!empty($feature['value_url_name']) ? $feature['value_url_name'] : $feature['value']),
                            'id_value'      => $feature['id_name'].'_'.$feature['id_value'],
                            'id_id_value'   => $feature['id_value'],
                            'category_name' => $filter['link_rewrite'],
                            'type'          => $filter['type'],
                        ];
                    }
                    break;

                case 'category':
                    $categories = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                        '
						SELECT cl.name, cl.id_lang, c.id_category
						FROM '._DB_PREFIX_.'category c
						INNER JOIN '._DB_PREFIX_.'category_lang cl ON (c.id_category = cl.id_category)
						WHERE cl.id_lang = '.(int) $filter['id_lang']
                    );

                    foreach ($categories as $category) {
                        if (!isset($attributeValuesByLang[$category['id_lang']])) {
                            $attributeValuesByLang[$category['id_lang']] = [];
                        }
                        if (!isset($attributeValuesByLang[$category['id_lang']]['category'])) {
                            $attributeValuesByLang[$category['id_lang']]['category'] = [];
                        }
                        $attributeValuesByLang[$category['id_lang']]['category'][] = [
                            'name'          => $this->translateWord('Categories', $category['id_lang']),
                            'id_name'       => null, 'value' => $category['name'], 'id_value' => $category['id_category'],
                            'category_name' => $filter['link_rewrite'], 'type' => $filter['type'],
                        ];
                    }
                    break;

                case 'manufacturer':
                    $manufacturers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                        '
						SELECT m.name AS name,l.id_lang AS id_lang,  id_manufacturer
						FROM '._DB_PREFIX_.'manufacturer m , '._DB_PREFIX_.'lang l
						WHERE l.id_lang = '.(int) $filter['id_lang']
                    );

                    foreach ($manufacturers as $manufacturer) {
                        if (!isset($attributeValuesByLang[$manufacturer['id_lang']])) {
                            $attributeValuesByLang[$manufacturer['id_lang']] = [];
                        }
                        if (!isset($attributeValuesByLang[$manufacturer['id_lang']]['manufacturer'])) {
                            $attributeValuesByLang[$manufacturer['id_lang']]['manufacturer'] = [];
                        }
                        $attributeValuesByLang[$manufacturer['id_lang']]['manufacturer'][] = [
                            'name'          => $this->translateWord('Manufacturer', $manufacturer['id_lang']),
                            'id_name'       => null, 'value' => $manufacturer['name'], 'id_value' => $manufacturer['id_manufacturer'],
                            'category_name' => $filter['link_rewrite'], 'type' => $filter['type'],
                        ];
                    }
                    break;

                case 'quantity':
                    $avaibilityList = [
                        $this->translateWord('Not available', (int) $filter['id_lang']),
                        $this->translateWord('In stock', (int) $filter['id_lang']),
                    ];
                    foreach ($avaibilityList as $key => $quantity) {
                        $attributeValuesByLang[$filter['id_lang']]['quantity'][] = [
                            'name'          => $this->translateWord('Availability', (int) $filter['id_lang']),
                            'id_name'       => null, 'value' => $quantity, 'id_value' => $key, 'id_id_value' => 0,
                            'category_name' => $filter['link_rewrite'], 'type' => $filter['type'],
                        ];
                    }
                    break;

                case 'condition':
                    $conditionList = [
                        'new'         => $this->translateWord('New', (int) $filter['id_lang']),
                        'used'        => $this->translateWord('Used', (int) $filter['id_lang']),
                        'refurbished' => $this->translateWord('Refurbished', (int) $filter['id_lang']),
                    ];
                    foreach ($conditionList as $key => $condition) {
                        $attributeValuesByLang[$filter['id_lang']]['condition'][] = [
                            'name'          => $this->translateWord('Condition', (int) $filter['id_lang']),
                            'id_name'       => null, 'value' => $condition, 'id_value' => $key,
                            'category_name' => $filter['link_rewrite'], 'type' => $filter['type'],
                        ];
                    }
                    break;
            }

        // Foreach langs
        foreach ($attributeValuesByLang as $idLang => $attributeValues) {
            // Foreach attributes generate a couple "/<attribute_name>_<atttribute_value>". For example: color_blue
            foreach ($attributeValues as $attribute) {
                foreach ($attribute as $param) {
                    $selectedFilters = [];
                    $link = '/'.str_replace($this->getAnchor(), '_', Tools::link_rewrite($param['name'])).$this->getAnchor().str_replace($this->getAnchor(), '_', Tools::link_rewrite($param['value']));
                    $selectedFilters[$param['type']] = [];

                    if (!isset($param['id_id_value'])) {
                        $param['id_id_value'] = $param['id_value'];
                    }

                    $selectedFilters[$param['type']][$param['id_id_value']] = $param['id_value'];
                    $urlKey = md5($link);
                    $idLayeredFriendlyUrl = Db::getInstance()->getValue(
                        '
						SELECT id_layered_friendly_url
						FROM `'._DB_PREFIX_.'layered_friendly_url` WHERE `id_lang` = '.$idLang.' AND `url_key` = \''.$urlKey.'\''
                    );

                    if ($idLayeredFriendlyUrl == false) {
                        Db::getInstance()->insert('layered_friendly_url', ['url_key' => $urlKey, 'data' => json_encode($selectedFilters), 'id_lang' => (int) $idLang]);
                        $idLayeredFriendlyUrl = Db::getInstance()->Insert_ID();
                    }
                }
            }
        }

        if ($ajax) {
            return '{"result": 1}';
        } else {
            return 1;
        }
    }

    public function translateWord($string, $idLang)
    {
        static $_MODULES = [];
        global $_MODULE;

        $file = _PS_MODULE_DIR_.$this->name.'/translations/'.Language::getIsoById($idLang).'.php';

        if (!array_key_exists($idLang, $_MODULES)) {
            if (file_exists($file1 = _PS_MODULE_DIR_.$this->name.'/translations/'.Language::getIsoById($idLang).'.php')) {
                include($file1);
                $_MODULES[$idLang] = $_MODULE;
            } elseif (file_exists($file2 = _PS_MODULE_DIR_.$this->name.'/'.Language::getIsoById($idLang).'.php')) {
                include($file2);
                $_MODULES[$idLang] = $_MODULE;
            } else {
                return $string;
            }
        }

        $string = str_replace('\'', '\\\'', $string);

        // set array key to lowercase for 1.3 compatibility
        $_MODULES[$idLang] = array_change_key_case($_MODULES[$idLang]);
        $currentKey = '<{'.strtolower($this->name).'}'.strtolower(_THEME_NAME_).'>'.strtolower($this->name).'_'.md5($string);
        $defaultKey = '<{'.strtolower($this->name).'}prestashop>'.strtolower($this->name).'_'.md5($string);

        if (isset($_MODULES[$idLang][$currentKey])) {
            $ret = stripslashes($_MODULES[$idLang][$currentKey]);
        } else {
            if (isset($_MODULES[$idLang][Tools::strtolower($currentKey)])) {
                $ret = stripslashes($_MODULES[$idLang][Tools::strtolower($currentKey)]);
            } else {
                if (isset($_MODULES[$idLang][$defaultKey])) {
                    $ret = stripslashes($_MODULES[$idLang][$defaultKey]);
                } else {
                    if (isset($_MODULES[$idLang][Tools::strtolower($defaultKey)])) {
                        $ret = stripslashes($_MODULES[$idLang][Tools::strtolower($defaultKey)]);
                    } else {
                        $ret = stripslashes($string);
                    }
                }
            }
        }

        return str_replace('"', '&quot;', $ret);
    }

    protected function getAnchor()
    {
        static $anchor = null;
        if ($anchor === null) {
            if (!$anchor = Configuration::get('PS_ATTRIBUTE_ANCHOR_SEPARATOR')) {
                $anchor = '-';
            }
        }

        return $anchor;
    }

    public function indexAttribute($idProduct = null)
    {
        if (is_null($idProduct)) {
            Db::getInstance()->execute('TRUNCATE '._DB_PREFIX_.'layered_product_attribute');
        } else {
            Db::getInstance()->execute(
                '
				DELETE FROM '._DB_PREFIX_.'layered_product_attribute
				WHERE id_product = '.(int) $idProduct
            );
        }

        Db::getInstance()->execute(
            '
			INSERT INTO `'._DB_PREFIX_.'layered_product_attribute` (`id_attribute`, `id_product`, `id_attribute_group`, `id_shop`)
			SELECT pac.id_attribute, pa.id_product, ag.id_attribute_group, product_attribute_shop.`id_shop`
			FROM '._DB_PREFIX_.'product_attribute pa'.
            Shop::addSqlAssociation('product_attribute', 'pa').'
			INNER JOIN '._DB_PREFIX_.'product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
			INNER JOIN '._DB_PREFIX_.'attribute a ON (a.id_attribute = pac.id_attribute)
			INNER JOIN '._DB_PREFIX_.'attribute_group ag ON ag.id_attribute_group = a.id_attribute_group
			'.(is_null($idProduct) ? '' : 'AND pa.id_product = '.(int) $idProduct).'
			GROUP BY a.id_attribute, pa.id_product , product_attribute_shop.`id_shop`'
        );

        return 1;
    }

    public function uninstall()
    {
        /* Delete all configurations */
        Configuration::deleteByName('PS_LAYERED_HIDE_0_VALUES');
        Configuration::deleteByName('PS_LAYERED_SHOW_QTIES');
        Configuration::deleteByName('PS_LAYERED_FULL_TREE');
        Configuration::deleteByName('PS_LAYERED_INDEXED');
        Configuration::deleteByName('PS_LAYERED_FILTER_PRICE_USETAX');
        Configuration::deleteByName('PS_LAYERED_FILTER_CATEGORY_DEPTH');
        Configuration::deleteByName('PS_LAYERED_FILTER_INDEX_QTY');
        Configuration::deleteByName('PS_LAYERED_FILTER_INDEX_CDT');
        Configuration::deleteByName('PS_LAYERED_FILTER_INDEX_MNF');
        Configuration::deleteByName('PS_LAYERED_FILTER_INDEX_CAT');
        Configuration::deleteByName('PS_LAYERED_FILTER_PRICE_ROUNDING');

        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'layered_price_index');
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'layered_friendly_url');
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'layered_indexable_attribute_group');
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'layered_indexable_feature');
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'layered_indexable_attribute_lang_value');
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'layered_indexable_attribute_group_lang_value');
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'layered_indexable_feature_lang_value');
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'layered_indexable_feature_value_lang_value');
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'layered_category');
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'layered_filter');
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'layered_filter_shop');
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'layered_product_attribute');

        return parent::uninstall();
    }

    public function hookAfterSaveAttributeGroup($params)
    {
        if (!$params['id_attribute_group'] || Tools::getValue('layered_indexable') === false) {
            return;
        }

        Db::getInstance()->execute(
            'DELETE FROM '._DB_PREFIX_.'layered_indexable_attribute_group
			WHERE `id_attribute_group` = '.(int) $params['id_attribute_group']
        );
        Db::getInstance()->execute(
            'DELETE FROM '._DB_PREFIX_.'layered_indexable_attribute_group_lang_value
			WHERE `id_attribute_group` = '.(int) $params['id_attribute_group']
        );

        Db::getInstance()->execute(
            'INSERT INTO '._DB_PREFIX_.'layered_indexable_attribute_group (`id_attribute_group`, `indexable`)
			VALUES ('.(int) $params['id_attribute_group'].', '.(int) Tools::getValue('layered_indexable').')'
        );

        foreach (Language::getLanguages(false) as $language) {
            $seo_url = Tools::getValue('url_name_'.(int) $language['id_lang']);

            if (empty($seo_url)) {
                $seo_url = Tools::getValue('name_'.(int) $language['id_lang']);
            }

            Db::getInstance()->execute(
                'INSERT INTO '._DB_PREFIX_.'layered_indexable_attribute_group_lang_value
				(`id_attribute_group`, `id_lang`, `url_name`, `meta_title`)
				VALUES (
					'.(int) $params['id_attribute_group'].', '.(int) $language['id_lang'].',
					\''.pSQL(Tools::link_rewrite($seo_url)).'\',
					\''.pSQL(Tools::getValue('meta_title_'.(int) $language['id_lang']), true).'\'
				)'
            );
        }
    }

    public function hookAfterDeleteAttributeGroup($params)
    {
        if (!isset($params['id_attribute_group']) || !$params['id_attribute_group']) {
            return;
        }

        Db::getInstance()->execute(
            'DELETE FROM '._DB_PREFIX_.'layered_indexable_attribute_group
			WHERE `id_attribute_group` = '.(int) $params['id_attribute_group']
        );
        Db::getInstance()->execute(
            'DELETE FROM '._DB_PREFIX_.'layered_indexable_attribute_group_lang_value
			WHERE `id_attribute_group` = '.(int) $params['id_attribute_group']
        );
    }

    public function hookPostProcessAttributeGroup($params)
    {
        $errors = [];

        foreach (Language::getLanguages(false) as $language) {
            $id_lang = $language['id_lang'];

            if (Tools::getValue('url_name_'.$id_lang)) {
                if (Tools::link_rewrite(Tools::getValue('url_name_'.$id_lang)) != strtolower(Tools::getValue('url_name_'.$id_lang))) {
                    $params['errors'][] = Tools::displayError(
                        sprintf(
                            $this->l('"%s" is not a valid url'),
                            Tools::getValue('url_name_'.$id_lang)
                        )
                    );
                }
            }
        }
    }

    public function hookAttributeGroupForm($params)
    {
        if (!isset($params['id_attribute_group']) || !$params['id_attribute_group']) {
            return '';
        }

        $values = [];
        $is_indexable = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT `indexable`
			FROM '._DB_PREFIX_.'layered_indexable_attribute_group
			WHERE `id_attribute_group` = '.(int) $params['id_attribute_group']
        );

        if ($is_indexable === false) {
            $is_indexable = true;
        }

        if ($result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT `url_name`, `meta_title`, `id_lang` FROM '._DB_PREFIX_.'layered_indexable_attribute_group_lang_value
			WHERE `id_attribute_group` = '.(int) $params['id_attribute_group']
        )
        ) {
            foreach ($result as $data) {
                $values[$data['id_lang']] = ['url_name' => $data['url_name'], 'meta_title' => $data['meta_title']];
            }
        }

        $this->context->smarty->assign(
            [
                'languages'             => Language::getLanguages(false),
                'default_form_language' => (int) $this->context->controller->default_form_language,
                'values'                => $values,
                'is_indexable'          => (bool) $is_indexable,
            ]
        );

        return $this->display(__FILE__, 'attribute_group_form.tpl');
    }

    /*
     * Generate data product attribute
     */

    public function hookAfterSaveAttribute($params)
    {
        if (!isset($params['id_attribute']) || !$params['id_attribute']) {
            return;
        }

        Db::getInstance()->execute(
            'DELETE FROM '._DB_PREFIX_.'layered_indexable_attribute_lang_value
			WHERE `id_attribute` = '.(int) $params['id_attribute']
        );

        foreach (Language::getLanguages(false) as $language) {
            $seo_url = Tools::getValue('url_name_'.(int) $language['id_lang']);

            if (empty($seo_url)) {
                $seo_url = Tools::getValue('name_'.(int) $language['id_lang']);
            }

            Db::getInstance()->execute(
                'INSERT INTO '._DB_PREFIX_.'layered_indexable_attribute_lang_value
				(`id_attribute`, `id_lang`, `url_name`, `meta_title`)
				VALUES (
					'.(int) $params['id_attribute'].', '.(int) $language['id_lang'].',
					\''.pSQL(Tools::link_rewrite($seo_url)).'\',
					\''.pSQL(Tools::getValue('meta_title_'.(int) $language['id_lang']), true).'\'
				)'
            );
        }
    }

    /*
     * Url indexation
     */

    public function hookAfterDeleteAttribute($params)
    {
        if (!isset($params['id_attribute']) || !$params['id_attribute']) {
            return;
        }

        Db::getInstance()->execute(
            'DELETE FROM '._DB_PREFIX_.'layered_indexable_attribute_lang_value
			WHERE `id_attribute` = '.(int) $params['id_attribute']
        );
    }

    /*
     * $cursor $cursor in order to restart indexing from the last state
     */

    public function hookPostProcessAttribute($params)
    {
        $errors = [];

        foreach (Language::getLanguages(false) as $language) {
            $id_lang = $language['id_lang'];

            if (Tools::getValue('url_name_'.$id_lang)) {
                if (Tools::link_rewrite(Tools::getValue('url_name_'.$id_lang)) != strtolower(Tools::getValue('url_name_'.$id_lang))) {
                    $params['errors'][] = Tools::displayError(
                        sprintf(
                            $this->l('"%s" is not a valid url'),
                            Tools::getValue('url_name_'.$id_lang)
                        )
                    );
                }
            }
        }
    }

    /*
     * $cursor $cursor in order to restart indexing from the last state
     */

    public function hookAttributeForm($params)
    {
        if (!isset($params['id_attribute']) || !$params['id_attribute']) {
            return '';
        }

        $values = [];

        if ($result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT `url_name`, `meta_title`, `id_lang`
			FROM '._DB_PREFIX_.'layered_indexable_attribute_lang_value
			WHERE `id_attribute` = '.(int) $params['id_attribute']
        )
        ) {
            foreach ($result as $data) {
                $values[$data['id_lang']] = ['url_name' => $data['url_name'], 'meta_title' => $data['meta_title']];
            }
        }

        $this->context->smarty->assign(
            [
                'languages'             => Language::getLanguages(false),
                'default_form_language' => (int) $this->context->controller->default_form_language,
                'values'                => $values,
            ]
        );

        return $this->display(__FILE__, 'attribute_form.tpl');
    }

    public function hookAfterSaveFeature($params)
    {
        if (!$params['id_feature'] || Tools::getValue('layered_indexable') === false) {
            return;
        }

        Db::getInstance()->execute(
            'DELETE FROM '._DB_PREFIX_.'layered_indexable_feature
			WHERE `id_feature` = '.(int) $params['id_feature']
        );
        Db::getInstance()->execute(
            'DELETE FROM '._DB_PREFIX_.'layered_indexable_feature_lang_value
			WHERE `id_feature` = '.(int) $params['id_feature']
        );

        Db::getInstance()->execute(
            'INSERT INTO '._DB_PREFIX_.'layered_indexable_feature
			(`id_feature`, `indexable`)
			VALUES ('.(int) $params['id_feature'].', '.(int) Tools::getValue('layered_indexable').')'
        );

        foreach (Language::getLanguages(false) as $language) {
            $seo_url = Tools::getValue('url_name_'.(int) $language['id_lang']);

            if (empty($seo_url)) {
                $seo_url = Tools::getValue('name_'.(int) $language['id_lang']);
            }

            Db::getInstance()->execute(
                'INSERT INTO '._DB_PREFIX_.'layered_indexable_feature_lang_value
				(`id_feature`, `id_lang`, `url_name`, `meta_title`)
				VALUES (
					'.(int) $params['id_feature'].', '.(int) $language['id_lang'].',
					\''.pSQL(Tools::link_rewrite($seo_url)).'\',
					\''.pSQL(Tools::getValue('meta_title_'.(int) $language['id_lang']), true).'\'
				)'
            );
        }
    }

    /*
     * $cursor $cursor in order to restart indexing from the last state
     */

    public function hookAfterDeleteFeature($params)
    {
        if (!$params['id_feature']) {
            return;
        }

        Db::getInstance()->execute(
            'DELETE FROM '._DB_PREFIX_.'layered_indexable_feature
			WHERE `id_feature` = '.(int) $params['id_feature']
        );
    }

    public function hookPostProcessFeature($params)
    {
        $errors = [];

        foreach (Language::getLanguages(false) as $language) {
            $id_lang = $language['id_lang'];

            if (Tools::getValue('url_name_'.$id_lang)) {
                if (Tools::link_rewrite(Tools::getValue('url_name_'.$id_lang)) != strtolower(Tools::getValue('url_name_'.$id_lang))) {
                    $params['errors'][] = Tools::displayError(
                        sprintf(
                            $this->l('"%s" is not a valid url'),
                            Tools::getValue('url_name_'.$id_lang)
                        )
                    );
                }
            }
        }
    }

    public function hookFeatureForm($params)
    {
        if (!isset($params['id_feature']) || !$params['id_feature']) {
            return '';
        }

        $values = [];
        $is_indexable = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT `indexable`
			FROM '._DB_PREFIX_.'layered_indexable_feature
			WHERE `id_feature` = '.(int) $params['id_feature']
        );

        if ($is_indexable === false) {
            $is_indexable = true;
        }

        if ($result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT `url_name`, `meta_title`, `id_lang` FROM '._DB_PREFIX_.'layered_indexable_feature_lang_value
			WHERE `id_feature` = '.(int) $params['id_feature']
        )
        ) {
            foreach ($result as $data) {
                $values[$data['id_lang']] = ['url_name' => $data['url_name'], 'meta_title' => $data['meta_title']];
            }
        }

        $this->context->smarty->assign(
            [
                'languages'             => Language::getLanguages(false),
                'default_form_language' => (int) $this->context->controller->default_form_language,
                'values'                => $values,
                'is_indexable'          => (bool) $is_indexable,
            ]
        );

        return $this->display(__FILE__, 'feature_form.tpl');
    }

    public function hookAfterSaveFeatureValue($params)
    {
        if (!$params['id_feature_value']) {
            return;
        }

        //Removing all indexed language data for this attribute value id
        Db::getInstance()->execute(
            'DELETE FROM '._DB_PREFIX_.'layered_indexable_feature_value_lang_value
			WHERE `id_feature_value` = '.(int) $params['id_feature_value']
        );

        foreach (Language::getLanguages(false) as $language) {
            $seo_url = Tools::getValue('url_name_'.(int) $language['id_lang']);

            if (empty($seo_url)) {
                $seo_url = Tools::getValue('name_'.(int) $language['id_lang']);
            }

            Db::getInstance()->execute(
                'INSERT INTO '._DB_PREFIX_.'layered_indexable_feature_value_lang_value
				(`id_feature_value`, `id_lang`, `url_name`, `meta_title`)
				VALUES (
					'.(int) $params['id_feature_value'].', '.(int) $language['id_lang'].',
					\''.pSQL(Tools::link_rewrite($seo_url)).'\',
					\''.pSQL(Tools::getValue('meta_title_'.(int) $language['id_lang']), true).'\'
				)'
            );
        }
    }

    public function hookAfterDeleteFeatureValue($params)
    {
        if (!$params['id_feature_value']) {
            return;
        }

        Db::getInstance()->execute(
            'DELETE FROM '._DB_PREFIX_.'layered_indexable_feature_value_lang_value
			WHERE `id_feature_value` = '.(int) $params['id_feature_value']
        );
    }

    public function hookPostProcessFeatureValue($params)
    {
        $errors = [];

        foreach (Language::getLanguages(false) as $language) {
            $id_lang = $language['id_lang'];

            if (Tools::getValue('url_name_'.$id_lang)) {
                if (Tools::link_rewrite(Tools::getValue('url_name_'.$id_lang)) != strtolower(Tools::getValue('url_name_'.$id_lang))) {
                    $params['errors'][] = Tools::displayError(
                        sprintf(
                            $this->l('"%s" is not a valid url'),
                            Tools::getValue('url_name_'.$id_lang)
                        )
                    );
                }
            }
        }
    }

    public function hookFeatureValueForm($params)
    {
        if (!isset($params['id_feature_value'])) {
            return '';
        }

        $values = [];

        if ($result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT `url_name`, `meta_title`, `id_lang`
			FROM '._DB_PREFIX_.'layered_indexable_feature_value_lang_value
			WHERE `id_feature_value` = '.(int) $params['id_feature_value']
        )
        ) {
            foreach ($result as $data) {
                $values[$data['id_lang']] = ['url_name' => $data['url_name'], 'meta_title' => $data['meta_title']];
            }
        }

        $this->context->smarty->assign(
            [
                'languages'             => Language::getLanguages(false),
                'default_form_language' => (int) $this->context->controller->default_form_language,
                'values'                => $values,
            ]
        );

        return $this->display(__FILE__, 'feature_value_form.tpl');
    }

    public function hookProductListAssign($params)
    {
        if ((isset($this->context->controller->display_column_left) && !$this->context->controller->display_column_left)
            && (isset($this->context->controller->display_column_right) && !$this->context->controller->display_column_right)
        ) {
            return false;
        }

        global $smarty;
        if (!Configuration::getGlobalValue('PS_LAYERED_INDEXED')) {
            return;
        }

        $categories_count = Db::getInstance()->getValue(
            '
			SELECT COUNT(*)
			FROM '._DB_PREFIX_.'layered_category
			WHERE id_category = '.(int) Tools::getValue('id_category', Tools::getValue('id_category_layered', Configuration::get('PS_HOME_CATEGORY'))).'
			AND id_shop = '.(int) Context::getContext()->shop->id
        );

        if ($categories_count == 0) {
            return;
        }

        // Inform the hook was executed
        $params['hookExecuted'] = true;
        // List of product to overrride categoryController
        $params['catProducts'] = [];
        $selected_filters = $this->getSelectedFilters();
        $filter_block = $this->getFilterBlock($selected_filters);
        $title = '';

        if (is_array($filter_block['title_values'])) {
            foreach ($filter_block['title_values'] as $key => $val) {
                $title .= ' > '.$key.' '.implode('/', $val);
            }
        }

        $smarty->assign('categoryNameComplement', $title);
        $this->getProducts($selected_filters, $params['catProducts'], $params['nbProducts'], $p, $n, $pages_nb, $start, $stop, $range);
        // Need a nofollow on the pagination links?
        $smarty->assign('no_follow', $filter_block['no_follow']);
    }

    private function getSelectedFilters()
    {
        $home_category = Configuration::get('PS_HOME_CATEGORY');
        $id_parent = (int) Tools::getValue('id_category', Tools::getValue('id_category_layered', $home_category));
        if ($id_parent == $home_category) {
            return;
        }

        // Force attributes selection (by url '.../2-mycategory/color-blue' or by get parameter 'selected_filters')
        if (strpos($_SERVER['SCRIPT_FILENAME'], 'blocklayered-ajax.php') === false || Tools::getValue('selected_filters') !== false) {
            if (Tools::getValue('selected_filters')) {
                $url = Tools::getValue('selected_filters');
            } else {
                $url = preg_replace('/\/(?:\w*)\/(?:[0-9]+[-\w]*)([^\?]*)\??.*/', '$1', Tools::safeOutput($_SERVER['REQUEST_URI'], true));
            }

            $url_attributes = explode('/', ltrim($url, '/'));
            $selected_filters = ['category' => []];
            if (!empty($url_attributes)) {
                foreach ($url_attributes as $url_attribute) {
                    /* Pagination uses - as separator, can be different from $this->getAnchor()*/
                    if (strpos($url_attribute, 'page-') === 0) {
                        $url_attribute = str_replace('-', $this->getAnchor(), $url_attribute);
                    }
                    $url_parameters = explode($this->getAnchor(), $url_attribute);
                    $attribute_name = array_shift($url_parameters);
                    if ($attribute_name == 'page') {
                        $this->page = (int) $url_parameters[0];
                    } else {
                        if (in_array($attribute_name, ['price', 'weight'])) {
                            $selected_filters[$attribute_name] = [Tools::purifyHTML($url_parameters[0]), Tools::purifyHTML($url_parameters[1])];
                        } else {
                            foreach ($url_parameters as $url_parameter) {
                                $result = Db::getInstance()->getValue('SELECT data FROM `'._DB_PREFIX_.'layered_friendly_url` WHERE `url_key` = \''.md5('/'.$attribute_name.$this->getAnchor().$url_parameter).'\'');
                                if ($result) {
                                    $data = json_decode($result, true);
                                    // Retrocompatibility for module versions
                                    // <= 3.0.3, which used to store data not
                                    // JSON encoded, but serialized. Can get
                                    // removed a couple of releases later.
                                    if ( ! $data) {
                                        $data = Tools::unSerialize($result);
                                    }

                                    foreach ($data as $key_params => $params) {
                                        if (!isset($selected_filters[$key_params])) {
                                            $selected_filters[$key_params] = [];
                                        }
                                        foreach ($params as $key_param => $param) {
                                            if (!isset($selected_filters[$key_params][$key_param])) {
                                                $selected_filters[$key_params][$key_param] = [];
                                            }
                                            $selected_filters[$key_params][$key_param] = Tools::purifyHTML($param);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                return $selected_filters;
            }
        }

        /* Analyze all the filters selected by the user and store them into a tab */
        $selected_filters = ['category' => [], 'manufacturer' => [], 'quantity' => [], 'condition' => []];
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 8) == 'layered_') {
                preg_match('/^(.*)_([0-9]+|new|used|refurbished|slider)$/', substr($key, 8, strlen($key) - 8), $res);
                if (isset($res[1])) {
                    $tmp_tab = explode('_', Tools::purifyHTML($value));
                    $value = Tools::purifyHTML($tmp_tab[0]);
                    $id_key = false;
                    if (isset($tmp_tab[1])) {
                        $id_key = $tmp_tab[1];
                    }
                    if ($res[1] == 'condition' && in_array($value, ['new', 'used', 'refurbished'])) {
                        $selected_filters['condition'][] = $value;
                    } else {
                        if ($res[1] == 'quantity' && (!$value || $value == 1)) {
                            $selected_filters['quantity'][] = $value;
                        } else {
                            if (in_array($res[1], ['category', 'manufacturer'])) {
                                if (!isset($selected_filters[$res[1].($id_key ? '_'.$id_key : '')])) {
                                    $selected_filters[$res[1].($id_key ? '_'.$id_key : '')] = [];
                                }
                                $selected_filters[$res[1].($id_key ? '_'.$id_key : '')][] = (int) $value;
                            } else {
                                if (in_array($res[1], ['id_attribute_group', 'id_feature'])) {
                                    if (!isset($selected_filters[$res[1]])) {
                                        $selected_filters[$res[1]] = [];
                                    }
                                    $selected_filters[$res[1]][(int) $value] = $id_key.'_'.(int) $value;
                                } else {
                                    if ($res[1] == 'weight') {
                                        $selected_filters[$res[1]] = $tmp_tab;
                                    } else {
                                        if ($res[1] == 'price') {
                                            $selected_filters[$res[1]] = $tmp_tab;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $selected_filters;
    }

    public function getFilterBlock($selected_filters = [])
    {
        global $cookie;
        static $cache = null;

        $context = Context::getContext();

        $id_lang = $context->language->id;
        $currency = $context->currency;
        $id_shop = (int) $context->shop->id;
        $alias = 'product_shop';

        if (is_array($cache)) {
            return $cache;
        }

        $home_category = Configuration::get('PS_HOME_CATEGORY');
        $id_parent = (int) Tools::getValue('id_category', Tools::getValue('id_category_layered', $home_category));
        if ($id_parent == $home_category) {
            return;
        }

        $parent = new Category((int) $id_parent, $id_lang);

        /* Get the filters for the current category */
        $filters = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
			SELECT type, id_value, filter_show_limit, filter_type FROM '._DB_PREFIX_.'layered_category
			WHERE id_category = '.(int) $id_parent.'
				AND id_shop = '.$id_shop.'
			GROUP BY `type`, id_value ORDER BY position ASC'
        );

        /* Create the table which contains all the id_product in a cat or a tree */

        Db::getInstance()->execute('DROP TEMPORARY TABLE IF EXISTS '._DB_PREFIX_.'cat_restriction', false);
        Db::getInstance()->execute(
            'CREATE TEMPORARY TABLE '._DB_PREFIX_.'cat_restriction ENGINE=MEMORY
													SELECT DISTINCT cp.id_product, p.id_manufacturer, product_shop.condition, p.weight FROM '._DB_PREFIX_.'category c
													STRAIGHT_JOIN '._DB_PREFIX_.'category_product cp ON (c.id_category = cp.id_category AND
													'.(Configuration::get('PS_LAYERED_FULL_TREE') ? 'c.nleft >= '.(int) $parent->nleft.'
													AND c.nright <= '.(int) $parent->nright : 'c.id_category = '.(int) $id_parent).'
													AND c.active = 1)
													STRAIGHT_JOIN '._DB_PREFIX_.'product_shop product_shop ON (product_shop.id_product = cp.id_product
													AND product_shop.id_shop = '.(int) $context->shop->id.')
													STRAIGHT_JOIN '._DB_PREFIX_.'product p ON (p.id_product=cp.id_product)
													WHERE product_shop.`active` = 1 AND product_shop.`visibility` IN ("both", "catalog")', false
        );

        Db::getInstance()->execute(
            'ALTER TABLE '._DB_PREFIX_.'cat_restriction ADD PRIMARY KEY (id_product),
													ADD KEY `id_manufacturer` (`id_manufacturer`,`id_product`) USING BTREE,
													ADD KEY `condition` (`condition`,`id_product`) USING BTREE,
													ADD KEY `weight` (`weight`,`id_product`) USING BTREE', false
        );

        // Remove all empty selected filters
        foreach ($selected_filters as $key => $value)
            switch ($key) {
                case 'price':
                case 'weight':
                    if ($value[0] === '' && $value[1] === '') {
                        unset($selected_filters[$key]);
                    }
                    break;
                default:
                    if ($value == '') {
                        unset($selected_filters[$key]);
                    }
                    break;
            }

        $filter_blocks = [];
        foreach ($filters as $filter) {
            $sql_query = ['select' => '', 'from' => '', 'join' => '', 'where' => '', 'group' => '', 'second_query' => ''];
            switch ($filter['type']) {
                case 'price':
                    $sql_query['select'] = 'SELECT p.`id_product`, psi.price_min, psi.price_max ';
                    // price slider is not filter dependent
                    $sql_query['from'] = '
					FROM '._DB_PREFIX_.'cat_restriction p';
                    $sql_query['join'] = 'INNER JOIN `'._DB_PREFIX_.'layered_price_index` psi
								ON (psi.id_product = p.id_product AND psi.id_currency = '.(int) $context->currency->id.' AND psi.id_shop='.(int) $context->shop->id.')';
                    $sql_query['where'] = 'WHERE 1';
                    break;
                case 'weight':
                    $sql_query['select'] = 'SELECT p.`id_product`, p.`weight` ';
                    // price slider is not filter dependent
                    $sql_query['from'] = '
					FROM '._DB_PREFIX_.'cat_restriction p';
                    $sql_query['where'] = 'WHERE 1';
                    break;
                case 'condition':
                    $sql_query['select'] = 'SELECT p.`id_product`, product_shop.`condition` ';
                    $sql_query['from'] = '
					FROM '._DB_PREFIX_.'cat_restriction p';
                    $sql_query['where'] = 'WHERE 1';
                    $sql_query['from'] .= Shop::addSqlAssociation('product', 'p');
                    break;
                case 'quantity':
                    $sql_query['select'] = 'SELECT p.`id_product`, sa.`quantity`, sa.`out_of_stock` ';

                    $sql_query['from'] = '
					FROM '._DB_PREFIX_.'cat_restriction p';

                    $sql_query['join'] .= 'LEFT JOIN `'._DB_PREFIX_.'stock_available` sa
						ON (sa.id_product = p.id_product AND sa.id_product_attribute=0 '.StockAvailable::addSqlShopRestriction(null, null, 'sa').') ';
                    $sql_query['where'] = 'WHERE 1';
                    break;

                case 'manufacturer':
                    $sql_query['select'] = 'SELECT COUNT(DISTINCT p.id_product) nbr, m.id_manufacturer, m.name ';
                    $sql_query['from'] = '
					FROM '._DB_PREFIX_.'cat_restriction p
					INNER JOIN '._DB_PREFIX_.'manufacturer m ON (m.id_manufacturer = p.id_manufacturer) ';
                    $sql_query['where'] = 'WHERE 1';
                    $sql_query['group'] = ' GROUP BY p.id_manufacturer ORDER BY m.name';

                    if (!Configuration::get('PS_LAYERED_HIDE_0_VALUES')) {
                        $sql_query['second_query'] = '
							SELECT m.name, 0 nbr, m.id_manufacturer

							FROM '._DB_PREFIX_.'cat_restriction p
							INNER JOIN '._DB_PREFIX_.'manufacturer m ON (m.id_manufacturer = p.id_manufacturer)
							WHERE 1
							GROUP BY p.id_manufacturer ORDER BY m.name';
                    }

                    break;
                case 'id_attribute_group':// attribute group
                    $sql_query['select'] = '
					SELECT COUNT(DISTINCT lpa.id_product) nbr, lpa.id_attribute_group,
					a.color, al.name attribute_name, agl.public_name attribute_group_name , lpa.id_attribute, ag.is_color_group,
					liagl.url_name name_url_name, liagl.meta_title name_meta_title, lial.url_name value_url_name, lial.meta_title value_meta_title';
                    $sql_query['from'] = '
					FROM '._DB_PREFIX_.'layered_product_attribute lpa
					INNER JOIN '._DB_PREFIX_.'attribute a
					ON a.id_attribute = lpa.id_attribute
					INNER JOIN '._DB_PREFIX_.'attribute_lang al
					ON al.id_attribute = a.id_attribute
					AND al.id_lang = '.(int) $id_lang.'
					INNER JOIN '._DB_PREFIX_.'cat_restriction p
					ON p.id_product = lpa.id_product
					INNER JOIN '._DB_PREFIX_.'attribute_group ag
					ON ag.id_attribute_group = lpa.id_attribute_group
					INNER JOIN '._DB_PREFIX_.'attribute_group_lang agl
					ON agl.id_attribute_group = lpa.id_attribute_group
					AND agl.id_lang = '.(int) $id_lang.'
					LEFT JOIN '._DB_PREFIX_.'layered_indexable_attribute_group_lang_value liagl
					ON (liagl.id_attribute_group = lpa.id_attribute_group AND liagl.id_lang = '.(int) $id_lang.')
					LEFT JOIN '._DB_PREFIX_.'layered_indexable_attribute_lang_value lial
					ON (lial.id_attribute = lpa.id_attribute AND lial.id_lang = '.(int) $id_lang.') ';

                    $sql_query['where'] = 'WHERE lpa.id_attribute_group = '.(int) $filter['id_value'];
                    $sql_query['where'] .= ' AND lpa.`id_shop` = '.(int) $context->shop->id;
                    $sql_query['group'] = '
					GROUP BY lpa.id_attribute
					ORDER BY ag.`position` ASC, a.`position` ASC';

                    if (!Configuration::get('PS_LAYERED_HIDE_0_VALUES')) {
                        $sql_query['second_query'] = '
							SELECT 0 nbr, lpa.id_attribute_group,
								a.color, al.name attribute_name, agl.public_name attribute_group_name , lpa.id_attribute, ag.is_color_group,
								liagl.url_name name_url_name, liagl.meta_title name_meta_title, lial.url_name value_url_name, lial.meta_title value_meta_title
							FROM '._DB_PREFIX_.'layered_product_attribute lpa'.
                            Shop::addSqlAssociation('product', 'lpa').'
							INNER JOIN '._DB_PREFIX_.'attribute a
								ON a.id_attribute = lpa.id_attribute
							INNER JOIN '._DB_PREFIX_.'attribute_lang al
								ON al.id_attribute = a.id_attribute AND al.id_lang = '.(int) $id_lang.'
							INNER JOIN '._DB_PREFIX_.'product AS p
								ON p.id_product = lpa.id_product
							INNER JOIN '._DB_PREFIX_.'attribute_group ag
								ON ag.id_attribute_group = lpa.id_attribute_group
							INNER JOIN '._DB_PREFIX_.'attribute_group_lang agl
								ON agl.id_attribute_group = lpa.id_attribute_group
							AND agl.id_lang = '.(int) $id_lang.'
							LEFT JOIN '._DB_PREFIX_.'layered_indexable_attribute_group_lang_value liagl
								ON (liagl.id_attribute_group = lpa.id_attribute_group AND liagl.id_lang = '.(int) $id_lang.')
							LEFT JOIN '._DB_PREFIX_.'layered_indexable_attribute_lang_value lial
								ON (lial.id_attribute = lpa.id_attribute AND lial.id_lang = '.(int) $id_lang.')
							WHERE lpa.id_attribute_group = '.(int) $filter['id_value'].'
							AND lpa.`id_shop` = '.(int) $context->shop->id.'
							GROUP BY lpa.id_attribute
							ORDER BY id_attribute_group, id_attribute';
                    }
                    break;

                case 'id_feature':
                    $sql_query['select'] = 'SELECT fl.name feature_name, fp.id_feature, fv.id_feature_value, fvl.value,
					COUNT(DISTINCT p.id_product) nbr,
					lifl.url_name name_url_name, lifl.meta_title name_meta_title, lifvl.url_name value_url_name, lifvl.meta_title value_meta_title ';
                    $sql_query['from'] = '
					FROM '._DB_PREFIX_.'feature_product fp
					INNER JOIN '._DB_PREFIX_.'cat_restriction p
					ON p.id_product = fp.id_product
					LEFT JOIN '._DB_PREFIX_.'feature_lang fl ON (fl.id_feature = fp.id_feature AND fl.id_lang = '.$id_lang.')
					INNER JOIN '._DB_PREFIX_.'feature_value fv ON (fv.id_feature_value = fp.id_feature_value AND (fv.custom IS NULL OR fv.custom = 0))
					LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fvl.id_feature_value = fp.id_feature_value AND fvl.id_lang = '.$id_lang.')
					LEFT JOIN '._DB_PREFIX_.'layered_indexable_feature_lang_value lifl
					ON (lifl.id_feature = fp.id_feature AND lifl.id_lang = '.$id_lang.')
					LEFT JOIN '._DB_PREFIX_.'layered_indexable_feature_value_lang_value lifvl
					ON (lifvl.id_feature_value = fp.id_feature_value AND lifvl.id_lang = '.$id_lang.') ';
                    $sql_query['where'] = 'WHERE fp.id_feature = '.(int) $filter['id_value'];
                    $sql_query['group'] = 'GROUP BY fv.id_feature_value ';

                    if (!Configuration::get('PS_LAYERED_HIDE_0_VALUES')) {
                        $sql_query['second_query'] = '
							SELECT fl.name feature_name, fp.id_feature, fv.id_feature_value, fvl.value,
							0 nbr,
							lifl.url_name name_url_name, lifl.meta_title name_meta_title, lifvl.url_name value_url_name, lifvl.meta_title value_meta_title

							FROM '._DB_PREFIX_.'feature_product fp'.
                            Shop::addSqlAssociation('product', 'fp').'
							INNER JOIN '._DB_PREFIX_.'product p ON (p.id_product = fp.id_product)
							LEFT JOIN '._DB_PREFIX_.'feature_lang fl ON (fl.id_feature = fp.id_feature AND fl.id_lang = '.(int) $id_lang.')
							INNER JOIN '._DB_PREFIX_.'feature_value fv ON (fv.id_feature_value = fp.id_feature_value AND (fv.custom IS NULL OR fv.custom = 0))
							LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fvl.id_feature_value = fp.id_feature_value AND fvl.id_lang = '.(int) $id_lang.')
							LEFT JOIN '._DB_PREFIX_.'layered_indexable_feature_lang_value lifl
								ON (lifl.id_feature = fp.id_feature AND lifl.id_lang = '.(int) $id_lang.')
							LEFT JOIN '._DB_PREFIX_.'layered_indexable_feature_value_lang_value lifvl
								ON (lifvl.id_feature_value = fp.id_feature_value AND lifvl.id_lang = '.(int) $id_lang.')
							WHERE fp.id_feature = '.(int) $filter['id_value'].'
							GROUP BY fv.id_feature_value';
                    }

                    break;

                case 'category':
                    if (Group::isFeatureActive()) {
                        $this->user_groups = ($this->context->customer->isLogged() ? $this->context->customer->getGroups() : [Configuration::get('PS_UNIDENTIFIED_GROUP')]);
                    }

                    $depth = Configuration::get('PS_LAYERED_FILTER_CATEGORY_DEPTH');
                    if ($depth === false) {
                        $depth = 1;
                    }

                    $sql_query['select'] = '
					SELECT c.id_category, c.id_parent, cl.name, (SELECT count(DISTINCT p.id_product) # ';
                    $sql_query['from'] = '
					FROM '._DB_PREFIX_.'category_product cp
					LEFT JOIN '._DB_PREFIX_.'product p ON (p.id_product = cp.id_product) ';
                    $sql_query['where'] = '
					WHERE cp.id_category = c.id_category
					AND '.$alias.'.active = 1 AND '.$alias.'.`visibility` IN ("both", "catalog")';
                    $sql_query['group'] = ') count_products
					FROM '._DB_PREFIX_.'category c
					LEFT JOIN '._DB_PREFIX_.'category_lang cl ON (cl.id_category = c.id_category AND cl.`id_shop` = '.(int) Context::getContext()->shop->id.' and cl.id_lang = '.(int) $id_lang.') ';

                    if (Group::isFeatureActive()) {
                        $sql_query['group'] .= 'RIGHT JOIN '._DB_PREFIX_.'category_group cg ON (cg.id_category = c.id_category AND cg.`id_group` IN ('.implode(', ', $this->user_groups).')) ';
                    }

                    $sql_query['group'] .= 'WHERE c.nleft > '.(int) $parent->nleft.'
					AND c.nright < '.(int) $parent->nright.'
					'.($depth ? 'AND c.level_depth <= '.($parent->level_depth + (int) $depth) : '').'
					AND c.active = 1
					GROUP BY c.id_category ORDER BY c.nleft, c.position';

                    $sql_query['from'] .= Shop::addSqlAssociation('product', 'p');
            }

            foreach ($filters as $filter_tmp) {
                $method_name = 'get'.ucfirst($filter_tmp['type']).'FilterSubQuery';
                if (method_exists('BlockLayered', $method_name) &&
                    ($filter['type'] != 'price' && $filter['type'] != 'weight' && $filter['type'] != $filter_tmp['type'] || $filter['type'] == $filter_tmp['type'])
                ) {
                    if ($filter['type'] == $filter_tmp['type'] && $filter['id_value'] == $filter_tmp['id_value']) {
                        $sub_query_filter = self::$method_name([], true);
                    } else {
                        if (!is_null($filter_tmp['id_value'])) {
                            $selected_filters_cleaned = $this->cleanFilterByIdValue(@$selected_filters[$filter_tmp['type']], $filter_tmp['id_value']);
                        } else {
                            $selected_filters_cleaned = @$selected_filters[$filter_tmp['type']];
                        }
                        $sub_query_filter = self::$method_name($selected_filters_cleaned, $filter['type'] == $filter_tmp['type']);
                    }
                    foreach ($sub_query_filter as $key => $value) {
                        $sql_query[$key] .= $value;
                    }
                }
            }

            $products = false;
            if (!empty($sql_query['from'])) {
                $products = Db::getInstance()->executeS($sql_query['select']."\n".$sql_query['from']."\n".$sql_query['join']."\n".$sql_query['where']."\n".$sql_query['group'], true, false);
            }

            // price & weight have slidebar, so it's ok to not complete recompute the product list
            if (!empty($selected_filters['price']) && $filter['type'] != 'price' && $filter['type'] != 'weight') {
                $products = self::filterProductsByPrice(@$selected_filters['price'], $products);
            }

            if (!empty($sql_query['second_query'])) {
                $res = Db::getInstance()->executeS($sql_query['second_query']);
                if ($res) {
                    $products = array_merge($products, $res);
                }
            }

            switch ($filter['type']) {
                case 'price':
                    if ($this->showPriceFilter()) {
                        $price_array = [
                            'type_lite'         => 'price',
                            'type'              => 'price',
                            'id_key'            => 0,
                            'name'              => $this->l('Price'),
                            'slider'            => true,
                            'max'               => '0',
                            'min'               => null,
                            'values'            => ['1' => 0],
                            'unit'              => $currency->sign,
                            'format'            => $currency->format,
                            'filter_show_limit' => $filter['filter_show_limit'],
                            'filter_type'       => $filter['filter_type'],
                        ];
                        if (isset($products) && $products) {
                            foreach ($products as $product) {
                                if (is_null($price_array['min'])) {
                                    $price_array['min'] = $product['price_min'];
                                    $price_array['values'][0] = $product['price_min'];
                                } else {
                                    if ($price_array['min'] > $product['price_min']) {
                                        $price_array['min'] = $product['price_min'];
                                        $price_array['values'][0] = $product['price_min'];
                                    }
                                }

                                if ($price_array['max'] < $product['price_max']) {
                                    $price_array['max'] = $product['price_max'];
                                    $price_array['values'][1] = $product['price_max'];
                                }
                            }
                        }

                        if ($price_array['max'] != $price_array['min'] && $price_array['min'] != null) {
                            if ($filter['filter_type'] == 2) {
                                $price_array['list_of_values'] = [];
                                $nbr_of_value = $filter['filter_show_limit'];
                                if ($nbr_of_value < 2) {
                                    $nbr_of_value = 4;
                                }
                                $delta = ($price_array['max'] - $price_array['min']) / $nbr_of_value;
                                $current_step = $price_array['min'];
                                for ($i = 0; $i < $nbr_of_value; $i++) {
                                    $price_array['list_of_values'][] = [
                                        (int) ($price_array['min'] + $i * $delta),
                                        (int) ($price_array['min'] + ($i + 1) * $delta),
                                    ];
                                }
                            }
                            if (isset($selected_filters['price']) && isset($selected_filters['price'][0])
                                && isset($selected_filters['price'][1])
                            ) {
                                $price_array['values'][0] = $selected_filters['price'][0];
                                $price_array['values'][1] = $selected_filters['price'][1];
                            }
                            $filter_blocks[] = $price_array;
                        }
                    }
                    break;

                case 'weight':
                    $weight_array = [
                        'type_lite'         => 'weight',
                        'type'              => 'weight',
                        'id_key'            => 0,
                        'name'              => $this->l('Weight'),
                        'slider'            => true,
                        'max'               => '0',
                        'min'               => null,
                        'values'            => ['1' => 0],
                        'unit'              => Configuration::get('PS_WEIGHT_UNIT'),
                        'format'            => 5, // Ex: xxxxx kg
                        'filter_show_limit' => $filter['filter_show_limit'],
                        'filter_type'       => $filter['filter_type'],
                    ];
                    if (isset($products) && $products) {
                        foreach ($products as $product) {
                            if (is_null($weight_array['min'])) {
                                $weight_array['min'] = $product['weight'];
                                $weight_array['values'][0] = $product['weight'];
                            } else {
                                if ($weight_array['min'] > $product['weight']) {
                                    $weight_array['min'] = $product['weight'];
                                    $weight_array['values'][0] = $product['weight'];
                                }
                            }

                            if ($weight_array['max'] < $product['weight']) {
                                $weight_array['max'] = $product['weight'];
                                $weight_array['values'][1] = $product['weight'];
                            }
                        }
                    }
                    if ($weight_array['max'] != $weight_array['min'] && $weight_array['min'] != null) {
                        if (isset($selected_filters['weight']) && isset($selected_filters['weight'][0])
                            && isset($selected_filters['weight'][1])
                        ) {
                            $weight_array['values'][0] = $selected_filters['weight'][0];
                            $weight_array['values'][1] = $selected_filters['weight'][1];
                        }
                        $filter_blocks[] = $weight_array;
                    }
                    break;

                case 'condition':
                    $condition_array = [
                        'new'         => ['name' => $this->l('New'), 'nbr' => 0],
                        'used'        => ['name' => $this->l('Used'), 'nbr' => 0],
                        'refurbished' => [
                            'name' => $this->l('Refurbished'),
                            'nbr'  => 0,
                        ],
                    ];
                    if (isset($products) && $products) {
                        foreach ($products as $product) {
                            if (isset($selected_filters['condition']) && in_array($product['condition'], $selected_filters['condition'])) {
                                $condition_array[$product['condition']]['checked'] = true;
                            }
                        }
                    }
                    foreach ($condition_array as $key => $condition) {
                        if (isset($selected_filters['condition']) && in_array($key, $selected_filters['condition'])) {
                            $condition_array[$key]['checked'] = true;
                        }
                    }
                    if (isset($products) && $products) {
                        foreach ($products as $product) {
                            if (isset($condition_array[$product['condition']])) {
                                $condition_array[$product['condition']]['nbr']++;
                            }
                        }
                    }
                    $filter_blocks[] = [
                        'type_lite'         => 'condition',
                        'type'              => 'condition',
                        'id_key'            => 0,
                        'name'              => $this->l('Condition'),
                        'values'            => $condition_array,
                        'filter_show_limit' => $filter['filter_show_limit'],
                        'filter_type'       => $filter['filter_type'],
                    ];
                    break;

                case 'quantity':
                    $quantity_array = [
                        0 => ['name' => $this->l('Not available'), 'nbr' => 0],
                        1 => ['name' => $this->l('In stock'), 'nbr' => 0],
                    ];
                    foreach ($quantity_array as $key => $quantity) {
                        if (isset($selected_filters['quantity']) && in_array($key, $selected_filters['quantity'])) {
                            $quantity_array[$key]['checked'] = true;
                        }
                    }
                    if (isset($products) && $products) {
                        foreach ($products as $product) {
                            //If oosp move all not available quantity to available quantity
                            if ((int) $product['quantity'] > 0 || Product::isAvailableWhenOutOfStock($product['out_of_stock'])) {
                                $quantity_array[1]['nbr']++;
                            } else {
                                $quantity_array[0]['nbr']++;
                            }
                        }
                    }

                    $filter_blocks[] = [
                        'type_lite'         => 'quantity',
                        'type'              => 'quantity',
                        'id_key'            => 0,
                        'name'              => $this->l('Availability'),
                        'values'            => $quantity_array,
                        'filter_show_limit' => $filter['filter_show_limit'],
                        'filter_type'       => $filter['filter_type'],
                    ];

                    break;

                case 'manufacturer':
                    if (isset($products) && $products) {
                        $manufaturers_array = [];
                        foreach ($products as $manufacturer) {
                            if (!isset($manufaturers_array[$manufacturer['id_manufacturer']])) {
                                $manufaturers_array[$manufacturer['id_manufacturer']] = ['name' => $manufacturer['name'], 'nbr' => $manufacturer['nbr']];
                            }
                            if (isset($selected_filters['manufacturer']) && in_array((int) $manufacturer['id_manufacturer'], $selected_filters['manufacturer'])) {
                                $manufaturers_array[$manufacturer['id_manufacturer']]['checked'] = true;
                            }
                        }
                        $filter_blocks[] = [
                            'type_lite'         => 'manufacturer',
                            'type'              => 'manufacturer',
                            'id_key'            => 0,
                            'name'              => $this->l('Manufacturer'),
                            'values'            => $manufaturers_array,
                            'filter_show_limit' => $filter['filter_show_limit'],
                            'filter_type'       => $filter['filter_type'],
                        ];
                    }
                    break;

                case 'id_attribute_group':
                    $attributes_array = [];
                    if (isset($products) && $products) {
                        foreach ($products as $attributes) {
                            if (!isset($attributes_array[$attributes['id_attribute_group']])) {
                                $attributes_array[$attributes['id_attribute_group']] = [
                                    'type_lite'         => 'id_attribute_group',
                                    'type'              => 'id_attribute_group',
                                    'id_key'            => (int) $attributes['id_attribute_group'],
                                    'name'              => $attributes['attribute_group_name'],
                                    'is_color_group'    => (bool) $attributes['is_color_group'],
                                    'values'            => [],
                                    'url_name'          => $attributes['name_url_name'],
                                    'meta_title'        => $attributes['name_meta_title'],
                                    'filter_show_limit' => $filter['filter_show_limit'],
                                    'filter_type'       => $filter['filter_type'],
                                ];
                            }

                            if (!isset($attributes_array[$attributes['id_attribute_group']]['values'][$attributes['id_attribute']])) {
                                $attributes_array[$attributes['id_attribute_group']]['values'][$attributes['id_attribute']] = [
                                    'color'      => $attributes['color'],
                                    'name'       => $attributes['attribute_name'],
                                    'nbr'        => (int) $attributes['nbr'],
                                    'url_name'   => $attributes['value_url_name'],
                                    'meta_title' => $attributes['value_meta_title'],
                                ];
                            }

                            if (isset($selected_filters['id_attribute_group'][$attributes['id_attribute']])) {
                                $attributes_array[$attributes['id_attribute_group']]['values'][$attributes['id_attribute']]['checked'] = true;
                            }
                        }

                        $filter_blocks = array_merge($filter_blocks, $attributes_array);
                    }
                    break;
                case 'id_feature':
                    $feature_array = [];
                    if (isset($products) && $products) {
                        foreach ($products as $feature) {
                            if (!isset($feature_array[$feature['id_feature']])) {
                                $feature_array[$feature['id_feature']] = [
                                    'type_lite'         => 'id_feature',
                                    'type'              => 'id_feature',
                                    'id_key'            => (int) $feature['id_feature'],
                                    'values'            => [],
                                    'name'              => $feature['feature_name'],
                                    'url_name'          => $feature['name_url_name'],
                                    'meta_title'        => $feature['name_meta_title'],
                                    'filter_show_limit' => $filter['filter_show_limit'],
                                    'filter_type'       => $filter['filter_type'],
                                ];
                            }

                            if (!isset($feature_array[$feature['id_feature']]['values'][$feature['id_feature_value']])) {
                                $feature_array[$feature['id_feature']]['values'][$feature['id_feature_value']] = [
                                    'nbr'        => (int) $feature['nbr'],
                                    'name'       => $feature['value'],
                                    'url_name'   => $feature['value_url_name'],
                                    'meta_title' => $feature['value_meta_title'],
                                ];
                            }

                            if (isset($selected_filters['id_feature'][$feature['id_feature_value']])) {
                                $feature_array[$feature['id_feature']]['values'][$feature['id_feature_value']]['checked'] = true;
                            }
                        }

                        //Natural sort
                        foreach ($feature_array as $key => $value) {
                            $temp = [];
                            foreach ($feature_array[$key]['values'] as $keyint => $valueint) {
                                $temp[$keyint] = $valueint['name'];
                            }

                            natcasesort($temp);
                            $temp2 = [];

                            foreach ($temp as $keytemp => $valuetemp) {
                                $temp2[$keytemp] = $feature_array[$key]['values'][$keytemp];
                            }

                            $feature_array[$key]['values'] = $temp2;
                        }

                        $filter_blocks = array_merge($filter_blocks, $feature_array);
                    }
                    break;

                case 'category':
                    $tmp_array = [];
                    if (isset($products) && $products) {
                        $categories_with_products_count = 0;
                        foreach ($products as $category) {
                            $tmp_array[$category['id_category']] = [
                                'name' => $category['name'],
                                'nbr'  => (int) $category['count_products'],
                            ];

                            if ((int) $category['count_products']) {
                                $categories_with_products_count++;
                            }

                            if (isset($selected_filters['category']) && in_array($category['id_category'], $selected_filters['category'])) {
                                $tmp_array[$category['id_category']]['checked'] = true;
                            }
                        }
                        if ($categories_with_products_count || !Configuration::get('PS_LAYERED_HIDE_0_VALUES')) {
                            $filter_blocks[] = [
                                'type_lite'         => 'category',
                                'type'              => 'category',
                                'id_key'            => 0, 'name' => $this->l('Categories'),
                                'values'            => $tmp_array,
                                'filter_show_limit' => $filter['filter_show_limit'],
                                'filter_type'       => $filter['filter_type'],
                            ];
                        }
                    }
                    break;
            }
        }

        // All non indexable attribute and feature
        $non_indexable = [];

        // Get all non indexable attribute groups
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
		SELECT public_name
		FROM `'._DB_PREFIX_.'attribute_group_lang` agl
		LEFT JOIN `'._DB_PREFIX_.'layered_indexable_attribute_group` liag
		ON liag.id_attribute_group = agl.id_attribute_group
		WHERE indexable IS NULL OR indexable = 0
		AND id_lang = '.(int) $id_lang
        ) as $attribute) {
            $non_indexable[] = Tools::link_rewrite($attribute['public_name']);
        }

        // Get all non indexable features
        foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
		SELECT name
		FROM `'._DB_PREFIX_.'feature_lang` fl
		LEFT JOIN  `'._DB_PREFIX_.'layered_indexable_feature` lif
		ON lif.id_feature = fl.id_feature
		WHERE indexable IS NULL OR indexable = 0
		AND id_lang = '.(int) $id_lang
        ) as $attribute) {
            $non_indexable[] = Tools::link_rewrite($attribute['name']);
        }

        //generate SEO link
        $param_selected = '';
        $param_product_url = '';
        $option_checked_array = [];
        $param_group_selected_array = [];
        $title_values = [];
        $meta_values = [];

        //get filters checked by group

        foreach ($filter_blocks as $type_filter) {
            $filter_name = (!empty($type_filter['url_name']) ? $type_filter['url_name'] : $type_filter['name']);
            $filter_meta = (!empty($type_filter['meta_title']) ? $type_filter['meta_title'] : $type_filter['name']);
            $attr_key = $type_filter['type'].'_'.$type_filter['id_key'];

            $param_group_selected = '';
            $lower_filter = strtolower($type_filter['type']);
            $filter_name_rewritten = Tools::link_rewrite($filter_name);

            if (($lower_filter == 'price' || $lower_filter == 'weight')
                && (float) $type_filter['values'][0] > (float) $type_filter['min']
                && (float) $type_filter['values'][1] > (float) $type_filter['max']
            ) {
                $param_group_selected .= $this->getAnchor().str_replace($this->getAnchor(), '_', $type_filter['values'][0])
                    .$this->getAnchor().str_replace($this->getAnchor(), '_', $type_filter['values'][1]);
                $param_group_selected_array[$filter_name_rewritten][] = $filter_name_rewritten;

                if (!isset($title_values[$filter_meta])) {
                    $title_values[$filter_meta] = [];
                }
                $title_values[$filter_meta][] = $filter_meta;
                if (!isset($meta_values[$attr_key])) {
                    $meta_values[$attr_key] = ['title' => $filter_meta, 'values' => []];
                }
                $meta_values[$attr_key]['values'][] = $filter_meta;
            } else {
                foreach ($type_filter['values'] as $key => $value) {
                    if (is_array($value) && array_key_exists('checked', $value)) {
                        $value_name = !empty($value['url_name']) ? $value['url_name'] : $value['name'];
                        $value_meta = !empty($value['meta_title']) ? $value['meta_title'] : $value['name'];
                        $param_group_selected .= $this->getAnchor().str_replace($this->getAnchor(), '_', Tools::link_rewrite($value_name));
                        $param_group_selected_array[$filter_name_rewritten][] = Tools::link_rewrite($value_name);

                        if (!isset($title_values[$filter_meta])) {
                            $title_values[$filter_meta] = [];
                        }
                        $title_values[$filter_meta][] = $value_name;
                        if (!isset($meta_values[$attr_key])) {
                            $meta_values[$attr_key] = ['title' => $filter_meta, 'values' => []];
                        }
                        $meta_values[$attr_key]['values'][] = $value_meta;
                    } else {
                        $param_group_selected_array[$filter_name_rewritten][] = [];
                    }
                }
            }

            if (!empty($param_group_selected)) {
                $param_selected .= '/'.str_replace($this->getAnchor(), '_', $filter_name_rewritten).$param_group_selected;
                $option_checked_array[$filter_name_rewritten] = $param_group_selected;
            }
            // select only attribute and group attribute to display an unique product combination link
            if (!empty($param_group_selected) && $type_filter['type'] == 'id_attribute_group') {
                $param_product_url .= '/'.str_replace($this->getAnchor(), '_', $filter_name_rewritten).$param_group_selected;
            }

        }

        if ($this->page > 1) {
            $param_selected .= '/page-'.$this->page;
        }

        $blacklist = ['weight', 'price'];

        if (!Configuration::get('PS_LAYERED_FILTER_INDEX_CDT')) {
            $blacklist[] = 'condition';
        }
        if (!Configuration::get('PS_LAYERED_FILTER_INDEX_QTY')) {
            $blacklist[] = 'quantity';
        }
        if (!Configuration::get('PS_LAYERED_FILTER_INDEX_MNF')) {
            $blacklist[] = 'manufacturer';
        }
        if (!Configuration::get('PS_LAYERED_FILTER_INDEX_CAT')) {
            $blacklist[] = 'category';
        }

        $global_nofollow = false;
        $categorie_link = Context::getContext()->link->getCategoryLink($parent, null, null);

        foreach ($filter_blocks as &$type_filter) {
            $filter_name = (!empty($type_filter['url_name']) ? $type_filter['url_name'] : $type_filter['name']);
            $filter_link_rewrite = Tools::link_rewrite($filter_name);

            if (count($type_filter) > 0 && !isset($type_filter['slider'])) {
                foreach ($type_filter['values'] as $key => $values) {
                    $nofollow = false;
                    if (!empty($values['checked']) && in_array($type_filter['type'], $blacklist)) {
                        $global_nofollow = true;
                    }

                    $option_checked_clone_array = $option_checked_array;

                    // If not filters checked, add parameter
                    $value_name = !empty($values['url_name']) ? $values['url_name'] : $values['name'];

                    if (!in_array(Tools::link_rewrite($value_name), $param_group_selected_array[$filter_link_rewrite])) {
                        // Update parameter filter checked before
                        if (array_key_exists($filter_link_rewrite, $option_checked_array)) {
                            $option_checked_clone_array[$filter_link_rewrite] = $option_checked_clone_array[$filter_link_rewrite].$this->getAnchor().str_replace($this->getAnchor(), '_', Tools::link_rewrite($value_name));

                            if (in_array($type_filter['type'], $blacklist)) {
                                $nofollow = true;
                            }
                        } else {
                            $option_checked_clone_array[$filter_link_rewrite] = $this->getAnchor().str_replace($this->getAnchor(), '_', Tools::link_rewrite($value_name));
                        }
                    } else {
                        // Remove selected parameters
                        $option_checked_clone_array[$filter_link_rewrite] = str_replace($this->getAnchor().str_replace($this->getAnchor(), '_', Tools::link_rewrite($value_name)), '', $option_checked_clone_array[$filter_link_rewrite]);
                        if (empty($option_checked_clone_array[$filter_link_rewrite])) {
                            unset($option_checked_clone_array[$filter_link_rewrite]);
                        }
                    }
                    $parameters = '';
                    ksort($option_checked_clone_array); // Order parameters
                    foreach ($option_checked_clone_array as $key_group => $value_group) {
                        $parameters .= '/'.str_replace($this->getAnchor(), '_', $key_group).$value_group;
                    }

                    // Add nofollow if any blacklisted filters ins in parameters
                    foreach ($filter_blocks as $filter) {
                        $name = Tools::link_rewrite((!empty($filter['url_name']) ? $filter['url_name'] : $filter['name']));
                        if (in_array($filter['type'], $blacklist) && strpos($parameters, $name.'-') !== false) {
                            $nofollow = true;
                        }
                    }

                    // Check if there is an non indexable attribute or feature in the url
                    foreach ($non_indexable as $value) {
                        if (strpos($parameters, '/'.$value) !== false) {
                            $nofollow = true;
                        }
                    }

                    $type_filter['values'][$key]['link'] = $categorie_link.'#'.ltrim($parameters, '/');
                    $type_filter['values'][$key]['rel'] = ($nofollow) ? 'nofollow' : '';
                }
            }
        }

        $n_filters = 0;

        if (isset($selected_filters['price'])) {
            if ($price_array['min'] == $selected_filters['price'][0] && $price_array['max'] == $selected_filters['price'][1]) {
                unset($selected_filters['price']);
            }
        }
        if (isset($selected_filters['weight'])) {
            if ($weight_array['min'] == $selected_filters['weight'][0] && $weight_array['max'] == $selected_filters['weight'][1]) {
                unset($selected_filters['weight']);
            }
        }

        foreach ($selected_filters as $filters) {
            $n_filters += count($filters);
        }

        $cache = [
            'layered_show_qties'   => (int) Configuration::get('PS_LAYERED_SHOW_QTIES'),
            'id_category_layered'  => (int) $id_parent,
            'selected_filters'     => $selected_filters,
            'n_filters'            => (int) $n_filters,
            'nbr_filterBlocks'     => count($filter_blocks),
            'filters'              => $filter_blocks,
            'title_values'         => $title_values,
            'meta_values'          => $meta_values,
            'current_friendly_url' => $param_selected,
            'param_product_url'    => $param_product_url,
            'no_follow'            => (!empty($param_selected) || $global_nofollow),
        ];

        return $cache;
    }

    public function cleanFilterByIdValue($attributes, $id_value)
    {
        $selected_filters = [];
        if (is_array($attributes)) {
            foreach ($attributes as $attribute) {
                $attribute_data = explode('_', $attribute);
                if ($attribute_data[0] == $id_value) {
                    $selected_filters[] = $attribute_data[1];
                }
            }
        }

        return $selected_filters;
    }

    private static function filterProductsByPrice($filter_value, $product_collection)
    {
        static $ps_layered_filter_price_usetax = null;
        static $ps_layered_filter_price_rounding = null;

        if (empty($filter_value)) {
            return $product_collection;
        }

        if ($ps_layered_filter_price_usetax === null) {
            $ps_layered_filter_price_usetax = Configuration::get('PS_LAYERED_FILTER_PRICE_USETAX');
        }

        if ($ps_layered_filter_price_rounding === null) {
            $ps_layered_filter_price_rounding = Configuration::get('PS_LAYERED_FILTER_PRICE_ROUNDING');
        }

        foreach ($product_collection as $key => $product) {
            if (isset($filter_value) && $filter_value && isset($product['price_min']) && isset($product['id_product'])
                && (($product['price_min'] < (int) $filter_value[0] && $product['price_max'] > (int) $filter_value[0])
                    || ($product['price_max'] > (int) $filter_value[1] && $product['price_min'] < (int) $filter_value[1]))
            ) {
                $price = Product::getPriceStatic($product['id_product'], $ps_layered_filter_price_usetax);
                if ($ps_layered_filter_price_rounding) {
                    $price = (int) $price;
                }
                if ($price < $filter_value[0] || $price > $filter_value[1]) {
                    unset($product_collection[$key]);
                }
            }
        }

        return $product_collection;
    }

    protected function showPriceFilter()
    {
        return Group::getCurrent()->show_prices;
    }

    public function getProducts($selected_filters, &$products, &$nb_products, &$p, &$n, &$pages_nb, &$start, &$stop, &$range)
    {
        global $cookie;

        $products = $this->getProductByFilters($selected_filters);
        $products = Product::getProductsProperties((int) $cookie->id_lang, $products);
        $nb_products = $this->nbr_products;
        $range = 2; /* how many pages around page selected */

        $product_per_page = isset($this->context->cookie->nb_item_per_page) ? (int) $this->context->cookie->nb_item_per_page : Configuration::get('PS_PRODUCTS_PER_PAGE');
        $n = (int) Tools::getValue('n', Configuration::get('PS_PRODUCTS_PER_PAGE'));

        if ($n <= 0) {
            $n = 1;
        }

        $p = $this->page;

        if ($p < 0) {
            $p = 0;
        }

        if ($p > ($nb_products / $n)) {
            $p = ceil($nb_products / $n);
        }
        $pages_nb = ceil($nb_products / (int) ($n));

        $start = (int) ($p - $range);
        if ($start < 1) {
            $start = 1;
        }

        $stop = (int) ($p + $range);
        if ($stop > $pages_nb) {
            $stop = (int) ($pages_nb);
        }

        foreach ($products as &$product) {
            if ($product['id_product_attribute'] && isset($product['product_attribute_minimal_quantity'])) {
                $product['minimal_quantity'] = $product['product_attribute_minimal_quantity'];
            }
        }
    }

    public function getProductByFilters($selected_filters = [])
    {
        global $cookie;

        if (!empty($this->products)) {
            return $this->products;
        }

        $home_category = Configuration::get('PS_HOME_CATEGORY');
        /* If the current category isn't defined or if it's homepage, we have nothing to display */
        $id_parent = (int) Tools::getValue('id_category', Tools::getValue('id_category_layered', $home_category));
        if ($id_parent == $home_category) {
            return false;
        }

        $query_filters_where = ' AND product_shop.`active` = 1 AND product_shop.`visibility` IN ("both", "catalog")';
        $query_filters_from = '';

        $parent = new Category((int) $id_parent);

        foreach ($selected_filters as $key => $filter_values) {
            if (!count($filter_values)) {
                continue;
            }

            preg_match('/^(.*[^_0-9])/', $key, $res);
            $key = $res[1];

            switch ($key) {
                case 'id_feature':
                    $sub_queries = [];
                    foreach ($filter_values as $filter_value) {
                        $filter_value_array = explode('_', $filter_value);
                        if (!isset($sub_queries[$filter_value_array[0]])) {
                            $sub_queries[$filter_value_array[0]] = [];
                        }
                        $sub_queries[$filter_value_array[0]][] = 'fp.`id_feature_value` = '.(int) $filter_value_array[1];
                    }
                    foreach ($sub_queries as $sub_query) {
                        $query_filters_where .= ' AND p.id_product IN (SELECT `id_product` FROM `'._DB_PREFIX_.'feature_product` fp WHERE ';
                        $query_filters_where .= implode(' OR ', $sub_query).') ';
                    }
                    break;

                case 'id_attribute_group':
                    $sub_queries = [];

                    foreach ($filter_values as $filter_value) {
                        $filter_value_array = explode('_', $filter_value);
                        if (!isset($sub_queries[$filter_value_array[0]])) {
                            $sub_queries[$filter_value_array[0]] = [];
                        }
                        $sub_queries[$filter_value_array[0]][] = 'pac.`id_attribute` = '.(int) $filter_value_array[1];
                    }
                    foreach ($sub_queries as $sub_query) {
                        $query_filters_where .= ' AND p.id_product IN (SELECT pa.`id_product`
						FROM `'._DB_PREFIX_.'product_attribute_combination` pac
						LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
						ON (pa.`id_product_attribute` = pac.`id_product_attribute`)'.
                            Shop::addSqlAssociation('product_attribute', 'pa').'
						WHERE '.implode(' OR ', $sub_query).') ';
                    }
                    break;

                case 'category':
                    $query_filters_where .= ' AND p.id_product IN (SELECT id_product FROM '._DB_PREFIX_.'category_product cp WHERE ';
                    foreach ($selected_filters['category'] as $id_category) {
                        $query_filters_where .= 'cp.`id_category` = '.(int) $id_category.' OR ';
                    }
                    $query_filters_where = rtrim($query_filters_where, 'OR ').')';
                    break;

                case 'quantity':
                    if (count($selected_filters['quantity']) == 2) {
                        break;
                    }

                    $query_filters_where .= ' AND sa.quantity '.(!$selected_filters['quantity'][0] ? '<=' : '>').' 0 ';
                    $query_filters_from .= 'LEFT JOIN `'._DB_PREFIX_.'stock_available` sa ON (sa.id_product = p.id_product '.StockAvailable::addSqlShopRestriction(null, null, 'sa').') ';
                    break;

                case 'manufacturer':
                    $query_filters_where .= ' AND p.id_manufacturer IN ('.implode($selected_filters['manufacturer'], ',').')';
                    break;

                case 'condition':
                    if (count($selected_filters['condition']) == 3) {
                        break;
                    }
                    $query_filters_where .= ' AND product_shop.condition IN (';
                    foreach ($selected_filters['condition'] as $cond) {
                        $query_filters_where .= '\''.pSQL($cond).'\',';
                    }
                    $query_filters_where = rtrim($query_filters_where, ',').')';
                    break;

                case 'weight':
                    if ($selected_filters['weight'][0] != 0 || $selected_filters['weight'][1] != 0) {
                        $query_filters_where .= ' AND p.`weight` BETWEEN '.(float) ($selected_filters['weight'][0] - 0.001).' AND '.(float) ($selected_filters['weight'][1] + 0.001);
                    }
                    break;

                case 'price':
                    if (isset($selected_filters['price'])) {
                        if ($selected_filters['price'][0] !== '' || $selected_filters['price'][1] !== '') {
                            $price_filter = [];
                            $price_filter['min'] = (float) ($selected_filters['price'][0]);
                            $price_filter['max'] = (float) ($selected_filters['price'][1]);
                        }
                    } else {
                        $price_filter = false;
                    }
                    break;
            }
        }

        $context = Context::getContext();
        $id_currency = (int) $context->currency->id;

        $price_filter_query_in = ''; // All products with price range between price filters limits
        $price_filter_query_out = ''; // All products with a price filters limit on it price range
        if (isset($price_filter) && $price_filter) {
            $price_filter_query_in = 'INNER JOIN `'._DB_PREFIX_.'layered_price_index` psi
			ON
			(
				psi.price_min <= '.(int) $price_filter['max'].'
				AND psi.price_max >= '.(int) $price_filter['min'].'
				AND psi.`id_product` = p.`id_product`
				AND psi.`id_shop` = '.(int) $context->shop->id.'
				AND psi.`id_currency` = '.$id_currency.'
			)';

            $price_filter_query_out = 'INNER JOIN `'._DB_PREFIX_.'layered_price_index` psi
			ON
				((psi.price_min < '.(int) $price_filter['min'].' AND psi.price_max > '.(int) $price_filter['min'].')
				OR
				(psi.price_max > '.(int) $price_filter['max'].' AND psi.price_min < '.(int) $price_filter['max'].'))
				AND psi.`id_product` = p.`id_product`
				AND psi.`id_shop` = '.(int) $context->shop->id.'
				AND psi.`id_currency` = '.$id_currency;
        }

        $query_filters_from .= Shop::addSqlAssociation('product', 'p');

        Db::getInstance()->execute('DROP TEMPORARY TABLE IF EXISTS '._DB_PREFIX_.'cat_filter_restriction', false);
        if (empty($selected_filters['category'])) {
            /* Create the table which contains all the id_product in a cat or a tree */
            Db::getInstance()->execute(
                'CREATE TEMPORARY TABLE '._DB_PREFIX_.'cat_filter_restriction ENGINE=MEMORY
														SELECT cp.id_product, MIN(cp.position) position FROM '._DB_PREFIX_.'category c
														STRAIGHT_JOIN '._DB_PREFIX_.'category_product cp ON (c.id_category = cp.id_category AND
														'.(Configuration::get('PS_LAYERED_FULL_TREE') ? 'c.nleft >= '.(int) $parent->nleft.'
														AND c.nright <= '.(int) $parent->nright : 'c.id_category = '.(int) $id_parent).'
														AND c.active = 1)
														STRAIGHT_JOIN `'._DB_PREFIX_.'product` p ON (p.id_product=cp.id_product)
														'.$price_filter_query_in.'
														'.$query_filters_from.'
														WHERE 1 '.$query_filters_where.'
														GROUP BY cp.id_product ORDER BY position, id_product', false
            );
        } else {
            $categories = array_map('intval', $selected_filters['category']);

            Db::getInstance()->execute(
                'CREATE TEMPORARY TABLE '._DB_PREFIX_.'cat_filter_restriction ENGINE=MEMORY
														SELECT cp.id_product, MIN(cp.position) position FROM '._DB_PREFIX_.'category_product cp
														STRAIGHT_JOIN `'._DB_PREFIX_.'product` p ON (p.id_product=cp.id_product)
														'.$price_filter_query_in.'
														'.$query_filters_from.'
														WHERE cp.`id_category` IN ('.implode(',', $categories).') '.$query_filters_where.'
														GROUP BY cp.id_product ORDER BY position, id_product', false
            );
        }
        Db::getInstance()->execute('ALTER TABLE '._DB_PREFIX_.'cat_filter_restriction ADD PRIMARY KEY (id_product), ADD KEY (position, id_product) USING BTREE', false);

        if (isset($price_filter) && $price_filter) {
            static $ps_layered_filter_price_usetax = null;
            static $ps_layered_filter_price_rounding = null;

            if ($ps_layered_filter_price_usetax === null) {
                $ps_layered_filter_price_usetax = Configuration::get('PS_LAYERED_FILTER_PRICE_USETAX');
            }

            if ($ps_layered_filter_price_rounding === null) {
                $ps_layered_filter_price_rounding = Configuration::get('PS_LAYERED_FILTER_PRICE_ROUNDING');
            }

            if (empty($selected_filters['category'])) {
                $all_products_out = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    '
				SELECT p.`id_product` id_product
				FROM `'._DB_PREFIX_.'product` p JOIN '._DB_PREFIX_.'category_product cp USING (id_product)
				INNER JOIN '._DB_PREFIX_.'category c ON (c.id_category = cp.id_category AND
					'.(Configuration::get('PS_LAYERED_FULL_TREE') ? 'c.nleft >= '.(int) $parent->nleft.'
					AND c.nright <= '.(int) $parent->nright : 'c.id_category = '.(int) $id_parent).'
					AND c.active = 1)
				'.$price_filter_query_out.'
				'.$query_filters_from.'
				WHERE 1 '.$query_filters_where.' GROUP BY cp.id_product'
                );
            } else {
                $all_products_out = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    '
				SELECT p.`id_product` id_product
				FROM `'._DB_PREFIX_.'product` p JOIN '._DB_PREFIX_.'category_product cp USING (id_product)
				'.$price_filter_query_out.'
				'.$query_filters_from.'
				WHERE cp.`id_category` IN ('.implode(',', $categories).') '.$query_filters_where.' GROUP BY cp.id_product'
                );
            }

            /* for this case, price could be out of range, so we need to compute the real price */
            foreach ($all_products_out as $product) {
                $price = Product::getPriceStatic($product['id_product'], $ps_layered_filter_price_usetax);
                if ($ps_layered_filter_price_rounding) {
                    $price = (int) $price;
                }
                if ($price < $price_filter['min'] || $price > $price_filter['max']) {
                    // out of range price, exclude the product
                    $product_id_delete_list[] = (int) $product['id_product'];
                }
            }
            if (!empty($product_id_delete_list)) {
                Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'cat_filter_restriction WHERE id_product IN ('.implode(',', $product_id_delete_list).')', false);
            }
        }
        $this->nbr_products = Db::getInstance()->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'cat_filter_restriction', false);

        if ($this->nbr_products == 0) {
            $this->products = [];
        } else {
            $product_per_page = isset($this->context->cookie->nb_item_per_page) ? (int) $this->context->cookie->nb_item_per_page : Configuration::get('PS_PRODUCTS_PER_PAGE');
            $default_products_per_page = max(1, (int) Configuration::get('PS_PRODUCTS_PER_PAGE'));
            $n = $default_products_per_page;
            if (isset($this->context->cookie->nb_item_per_page)) {
                $n = (int) $this->context->cookie->nb_item_per_page;
            }
            if ((int) Tools::getValue('n')) {
                $n = (int) Tools::getValue('n');
            }
            $nb_day_new_product = (Validate::isUnsignedInt(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20);

            $this->products = Db::getInstance()->executeS('
				SELECT
					p.*,
					product_shop.*,
					product_shop.id_category_default,
					pl.*,
					image_shop.`id_image` id_image,
					il.legend,
					m.name manufacturer_name,
					'.(Combination::isFeatureActive() ? 'product_attribute_shop.id_product_attribute id_product_attribute,' : '').'
					DATEDIFF(product_shop.`date_add`, DATE_SUB("'.date('Y-m-d').' 00:00:00", INTERVAL '.(int) $nb_day_new_product.' DAY)) > 0 AS new,
					stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity'.(Combination::isFeatureActive() ? ', product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity' : '').'
				FROM '._DB_PREFIX_.'cat_filter_restriction cp
				LEFT JOIN `'._DB_PREFIX_.'product` p ON p.`id_product` = cp.`id_product`
				'.Shop::addSqlAssociation('product', 'p').
                    (Combination::isFeatureActive() ?
                        ' LEFT JOIN `'._DB_PREFIX_.'product_attribute_shop` product_attribute_shop
					ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop='.(int) $context->shop->id.')' : '').'
				LEFT JOIN '._DB_PREFIX_.'product_lang pl ON (pl.id_product = p.id_product'.Shop::addSqlRestrictionOnLang('pl').' AND pl.id_lang = '.(int) $cookie->id_lang.')
				LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop
					ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop='.(int) $context->shop->id.')
				LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = '.(int) $cookie->id_lang.')
				LEFT JOIN '._DB_PREFIX_.'manufacturer m ON (m.id_manufacturer = p.id_manufacturer)
				'.Product::sqlStock('p', 0).'
				WHERE product_shop.`active` = 1 AND product_shop.`visibility` IN ("both", "catalog")
				ORDER BY '.Tools::getProductsOrder('by', Tools::getValue('orderby'), true).' '.Tools::getProductsOrder('way', Tools::getValue('orderway')).' , cp.id_product'.
                    ' LIMIT '.(((int) $this->page - 1) * $n.','.$n), true, false
            );
        }

        if (Tools::getProductsOrder('by', Tools::getValue('orderby'), true) == 'p.price') {
            Tools::orderbyPrice($this->products, Tools::getProductsOrder('way', Tools::getValue('orderway')));
        }

        return $this->products;
    }

    public function hookAfterSaveProduct($params)
    {
        if (!$params['id_product']) {
            return;
        }

        self::indexProductPrices((int) $params['id_product']);
        $this->indexAttribute((int) $params['id_product']);
    }

    public function hookRightColumn($params)
    {
        return $this->hookLeftColumn($params);
    }

    public function hookLeftColumn($params)
    {
        return $this->generateFiltersBlock($this->getSelectedFilters());
    }

    public function generateFiltersBlock($selected_filters)
    {
        global $smarty;
        if ($filter_block = $this->getFilterBlock($selected_filters)) {
            if ($filter_block['nbr_filterBlocks'] == 0) {
                return false;
            }

            $translate = [];
            $translate['price'] = $this->l('price');
            $translate['weight'] = $this->l('weight');

            $smarty->assign($filter_block);
            $smarty->assign(
                [
                    'hide_0_values'          => Configuration::get('PS_LAYERED_HIDE_0_VALUES'),
                    'blocklayeredSliderName' => $translate,
                    'col_img_dir'            => _PS_COL_IMG_DIR_,
                ]
            );

            return $this->display(__FILE__, 'blocklayered.tpl');
        } else {
            return false;
        }
    }

    public function hookHeader($params)
    {
        if ((isset($this->context->controller->display_column_left) && !$this->context->controller->display_column_left)
            && (isset($this->context->controller->display_column_right) && !$this->context->controller->display_column_right)
        ) {
            return false;
        }

        global $smarty, $cookie;

        // No filters => module disable
        if ($filter_block = $this->getFilterBlock($this->getSelectedFilters())) {
            if ($filter_block['nbr_filterBlocks'] == 0) {
                return false;
            }
        }

        if (Tools::getValue('id_category', Tools::getValue('id_category_layered', Configuration::get('PS_HOME_CATEGORY'))) == Configuration::get('PS_HOME_CATEGORY')) {
            return;
        }

        $id_lang = (int) $cookie->id_lang;
        $category = new Category((int) Tools::getValue('id_category'));

        // Generate meta title and meta description
        $category_title = (empty($category->meta_title[$id_lang]) ? $category->name[$id_lang] : $category->meta_title[$id_lang]);
        $category_metas = Meta::getMetaTags($id_lang, 'category');
        $title = '';
        $keywords = '';

        if (is_array($filter_block['title_values'])) {
            foreach ($filter_block['title_values'] as $key => $val) {
                $title .= ' > '.$key.' '.implode('/', $val);
                $keywords .= $key.' '.implode('/', $val).', ';
            }
        }

        $title = $category_title.$title;

        if (!empty($title)) {
            $smarty->assign('meta_title', $title.' - '.Configuration::get('PS_SHOP_NAME'));
        } else {
            $smarty->assign('meta_title', $category_metas['meta_title']);
        }

        $smarty->assign('meta_description', $category_metas['meta_description']);

        $keywords = substr(strtolower($keywords), 0, 1000);
        if (!empty($keywords)) {
            $smarty->assign('meta_keywords', rtrim($category_title.', '.$keywords.', '.$category_metas['meta_keywords'], ', '));
        }

        $this->context->controller->addJS(($this->_path).'blocklayered.js');
        $this->context->controller->addJS(_PS_JS_DIR_.'jquery/jquery-ui-1.8.10.custom.min.js');
        $this->context->controller->addJQueryUI('ui.slider');
        $this->context->controller->addCSS(_PS_CSS_DIR_.'jquery-ui-1.8.10.custom.css');
        $this->context->controller->addCSS(($this->_path).'blocklayered.css', 'all');
        $this->context->controller->addJQueryPlugin('scrollTo');

        $filters = $this->getSelectedFilters();

        // Get non indexable attributes
        $attribute_group_list = Db::getInstance()->executeS('SELECT id_attribute_group FROM '._DB_PREFIX_.'layered_indexable_attribute_group WHERE indexable = 0');
        // Get non indexable features
        $feature_list = Db::getInstance()->executeS('SELECT id_feature FROM '._DB_PREFIX_.'layered_indexable_feature WHERE indexable = 0');

        $attributes = [];
        $features = [];

        $blacklist = ['weight', 'price'];
        if (!Configuration::get('PS_LAYERED_FILTER_INDEX_CDT')) {
            $blacklist[] = 'condition';
        }
        if (!Configuration::get('PS_LAYERED_FILTER_INDEX_QTY')) {
            $blacklist[] = 'quantity';
        }
        if (!Configuration::get('PS_LAYERED_FILTER_INDEX_MNF')) {
            $blacklist[] = 'manufacturer';
        }
        if (!Configuration::get('PS_LAYERED_FILTER_INDEX_CAT')) {
            $blacklist[] = 'category';
        }

        foreach ($filters as $type => $val) {
            switch ($type) {
                case 'id_attribute_group':
                    foreach ($val as $attr) {
                        $attr_id = preg_replace('/_\d+$/', '', $attr);
                        if (in_array($attr_id, $attributes) || in_array(['id_attribute_group' => $attr_id], $attribute_group_list)) {
                            $smarty->assign('nobots', true);
                            $smarty->assign('nofollow', true);

                            return;
                        }
                        $attributes[] = $attr_id;
                    }
                    break;
                case 'id_feature':
                    foreach ($val as $feat) {
                        $feat_id = preg_replace('/_\d+$/', '', $feat);
                        if (in_array($feat_id, $features) || in_array(['id_feature' => $feat_id], $feature_list)) {
                            $smarty->assign('nobots', true);
                            $smarty->assign('nofollow', true);

                            return;
                        }
                        $features[] = $feat_id;
                    }
                    break;
                default:
                    if (in_array($type, $blacklist)) {
                        if (count($val)) {
                            $smarty->assign('nobots', true);
                            $smarty->assign('nofollow', true);

                            return;
                        }
                    } elseif (count($val) > 1) {
                        $smarty->assign('nobots', true);
                        $smarty->assign('nofollow', true);

                        return;
                    }
                    break;
            }
        }
    }

    public function hookFooter($params)
    {
        if ((isset($this->context->controller->display_column_left) && !$this->context->controller->display_column_left)
            && (isset($this->context->controller->display_column_right) && !$this->context->controller->display_column_right)
        ) {
            return false;
        }

        // No filters => module disable
        if ($filter_block = $this->getFilterBlock($this->getSelectedFilters())) {
            if ($filter_block['nbr_filterBlocks'] == 0) {
                return false;
            }
        }

        if (Dispatcher::getInstance()->getController() == 'category') {
            $this->context->controller->addJS($this->_path.'blocklayered-footer.js');
        }
    }

    public function hookCategoryAddition($params)
    {
        $this->rebuildLayeredCache([], [(int) $params['category']->id]);
    }

    public function hookCategoryUpdate($params)
    {
        /* The category status might (active, inactive) have changed, we have to update the layered cache table structure */
        if (isset($params['category']) && !$params['category']->active) {
            $this->hookCategoryDeletion($params);
        }
    }

    public function hookCategoryDeletion($params)
    {
        $layered_filter_list = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            'SELECT * FROM '._DB_PREFIX_.'layered_filter'
        );

        foreach ($layered_filter_list as $layered_filter) {
            $data = json_decode($layered_filter['filters'], true);
            // Retrocompatibility for module versions <= 3.0.3, which used to
            // store data not JSON encoded, but serialized. Can get removed a
            // couple of releases later.
            if ( ! $data) {
                $data = Tools::unSerialize($layered_filter['filters']);
            }

            if (in_array((int) $params['category']->id, $data['categories'])) {
                unset($data['categories'][array_search((int) $params['category']->id, $data['categories'])]);
                Db::getInstance()->execute(
                    'UPDATE `'._DB_PREFIX_.'layered_filter`
					SET `filters` = \''.pSQL(json_encode($data)).'\'
					WHERE `id_layered_filter` = '.(int) $layered_filter['id_layered_filter']
                );
            }
        }

        $this->buildLayeredCategories();
    }

    public function getContent()
    {
        global $cookie;
        $message = '';

        if (Tools::isSubmit('SubmitFilter')) {
            if (!Tools::getValue('layered_tpl_name')) {
                $message = $this->displayError($this->l('Filter template name required (cannot be empty)'));
            } elseif (!Tools::getValue('categoryBox')) {
                $message = $this->displayError($this->l('You must select at least one category.'));
            } else {
                if (Tools::getValue('id_layered_filter')) {
                    Db::getInstance()->execute(
                        '
						DELETE FROM '._DB_PREFIX_.'layered_filter
						WHERE id_layered_filter = '.(int) Tools::getValue('id_layered_filter')
                    );
                    $this->buildLayeredCategories();
                }

                if (Tools::getValue('scope') == 1) {
                    Db::getInstance()->execute('TRUNCATE TABLE '._DB_PREFIX_.'layered_filter');
                    $categories = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                        '
						SELECT id_category
						FROM '._DB_PREFIX_.'category'
                    );

                    foreach ($categories as $category) {
                        $_POST['categoryBox'][] = (int) $category['id_category'];
                    }
                }

                $id_layered_filter = (int) Tools::getValue('id_layered_filter');

                if (!$id_layered_filter) {
                    $id_layered_filter = (int) Db::getInstance()->Insert_ID();
                }

                $shop_list = [];

                if (isset($_POST['checkBoxShopAsso_layered_filter'])) {
                    foreach ($_POST['checkBoxShopAsso_layered_filter'] as $id_shop => $row) {
                        $assos[] = ['id_object' => (int) $id_layered_filter, 'id_shop' => (int) $id_shop];
                        $shop_list[] = (int) $id_shop;
                    }
                } else {
                    $shop_list = [Context::getContext()->shop->id];
                }

                Db::getInstance()->execute(
                    '
					DELETE FROM '._DB_PREFIX_.'layered_filter_shop
					WHERE `id_layered_filter` = '.(int) $id_layered_filter
                );

                if (count($_POST['categoryBox'])) {
                    /* Clean categoryBox before use */
                    if (isset($_POST['categoryBox']) && is_array($_POST['categoryBox'])) {
                        foreach ($_POST['categoryBox'] as &$category_box_tmp) {
                            $category_box_tmp = (int) $category_box_tmp;
                        }
                    }

                    $filter_values = [];

                    foreach ($_POST['categoryBox'] as $idc) {
                        $filter_values['categories'][] = (int) $idc;
                    }

                    $filter_values['shop_list'] = $shop_list;
                    $values = false;

                    foreach ($_POST['categoryBox'] as $id_category_layered) {
                        foreach ($_POST as $key => $value) {
                            if (substr($key, 0, 17) == 'layered_selection' && $value == 'on') {
                                $values = true;
                                $type = 0;
                                $limit = 0;

                                if (Tools::getValue($key.'_filter_type')) {
                                    $type = Tools::getValue($key.'_filter_type');
                                }
                                if (Tools::getValue($key.'_filter_show_limit')) {
                                    $limit = Tools::getValue($key.'_filter_show_limit');
                                }

                                $filter_values[$key] = [
                                    'filter_type'       => (int) $type,
                                    'filter_show_limit' => (int) $limit,
                                ];
                            }
                        }
                    }

                    $values_to_insert = [
                        'name'         => pSQL(Tools::getValue('layered_tpl_name')),
                        'filters'      => pSQL(json_encode($filter_values)),
                        'n_categories' => (int) count($filter_values['categories']),
                        'date_add'     => date('Y-m-d H:i:s'),
                    ];

                    if (isset($_POST['id_layered_filter']) && $_POST['id_layered_filter']) {
                        $values_to_insert['id_layered_filter'] = (int) Tools::getValue('id_layered_filter');
                    }

                    Db::getInstance()->autoExecute(_DB_PREFIX_.'layered_filter', $values_to_insert, 'INSERT');
                    $id_layered_filter = (int) Db::getInstance()->Insert_ID();

                    if (isset($assos)) {
                        foreach ($assos as $asso) {
                            Db::getInstance()->execute(
                                '
							INSERT INTO '._DB_PREFIX_.'layered_filter_shop (`id_layered_filter`, `id_shop`)
							VALUES('.$id_layered_filter.', '.(int) $asso['id_shop'].')'
                            );
                        }
                    }

                    $this->buildLayeredCategories();
                    $message = $this->displayConfirmation(
                        $this->l('Your filter').' "'.Tools::safeOutput(Tools::getValue('layered_tpl_name')).'" '.
                        ((isset($_POST['id_layered_filter']) && $_POST['id_layered_filter']) ? $this->l('was updated successfully.') : $this->l('was added successfully.'))
                    );
                }
            }
        } else {
            if (Tools::isSubmit('submitLayeredSettings')) {
                Configuration::updateValue('PS_LAYERED_HIDE_0_VALUES', (int) Tools::getValue('ps_layered_hide_0_values'));
                Configuration::updateValue('PS_LAYERED_SHOW_QTIES', (int) Tools::getValue('ps_layered_show_qties'));
                Configuration::updateValue('PS_LAYERED_FULL_TREE', (int) Tools::getValue('ps_layered_full_tree'));
                Configuration::updateValue('PS_LAYERED_FILTER_PRICE_USETAX', (int) Tools::getValue('ps_layered_filter_price_usetax'));
                Configuration::updateValue('PS_LAYERED_FILTER_CATEGORY_DEPTH', (int) Tools::getValue('ps_layered_filter_category_depth'));
                Configuration::updateValue('PS_LAYERED_FILTER_INDEX_QTY', (int) Tools::getValue('ps_layered_filter_index_availability'));
                Configuration::updateValue('PS_LAYERED_FILTER_INDEX_CDT', (int) Tools::getValue('ps_layered_filter_index_condition'));
                Configuration::updateValue('PS_LAYERED_FILTER_INDEX_MNF', (int) Tools::getValue('ps_layered_filter_index_manufacturer'));
                Configuration::updateValue('PS_LAYERED_FILTER_INDEX_CAT', (int) Tools::getValue('ps_layered_filter_index_category'));
                Configuration::updateValue('PS_LAYERED_FILTER_PRICE_ROUNDING', (int) Tools::getValue('ps_layered_filter_price_rounding'));

                $message = '<div class="alert alert-success">'.$this->l('Settings saved successfully').'</div>';
            } else {
                if (Tools::getValue('deleteFilterTemplate')) {
                    $layered_values = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
                        '
				SELECT filters
				FROM '._DB_PREFIX_.'layered_filter
				WHERE id_layered_filter = '.(int) Tools::getValue('id_layered_filter')
                    );

                    if ($layered_values) {
                        Db::getInstance()->execute(
                            '
					DELETE FROM '._DB_PREFIX_.'layered_filter
					WHERE id_layered_filter = '.(int) Tools::getValue('id_layered_filter').' LIMIT 1'
                        );
                        $this->buildLayeredCategories();
                        $message = $this->displayConfirmation($this->l('Filter template deleted, categories updated (reverted to default Filter template).'));
                    } else {
                        $message = $this->displayError($this->l('Filter template not found'));
                    }
                }
            }
        }

        $category_box = [];
        $attribute_groups = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
			SELECT ag.id_attribute_group, ag.is_color_group, agl.name, COUNT(DISTINCT(a.id_attribute)) n
			FROM '._DB_PREFIX_.'attribute_group ag
			LEFT JOIN '._DB_PREFIX_.'attribute_group_lang agl ON (agl.id_attribute_group = ag.id_attribute_group)
			LEFT JOIN '._DB_PREFIX_.'attribute a ON (a.id_attribute_group = ag.id_attribute_group)
			WHERE agl.id_lang = '.(int) $cookie->id_lang.'
			GROUP BY ag.id_attribute_group'
        );

        $features = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
			SELECT fl.id_feature, fl.name, COUNT(DISTINCT(fv.id_feature_value)) n
			FROM '._DB_PREFIX_.'feature_lang fl
			LEFT JOIN '._DB_PREFIX_.'feature_value fv ON (fv.id_feature = fl.id_feature)
			WHERE (fv.custom IS NULL OR fv.custom = 0) AND fl.id_lang = '.(int) $cookie->id_lang.'
			GROUP BY fl.id_feature'
        );

        if (Shop::isFeatureActive() && count(Shop::getShops(true, null, true)) > 1) {
            $helper = new HelperForm();
            $helper->id = Tools::getValue('id_layered_filter', null);
            $helper->table = 'layered_filter';
            $helper->identifier = 'id_layered_filter';
            $this->context->smarty->assign('asso_shops', $helper->renderAssoShop());
        }

        $tree_categories_helper = new HelperTreeCategories('categories-treeview');
        $tree_categories_helper->setRootCategory((Shop::getContext() == Shop::CONTEXT_SHOP ? Category::getRootCategory()->id_category : 0))
            ->setUseCheckBox(true);

        $module_url = Tools::getProtocol(Tools::usingSecureMode()).$_SERVER['HTTP_HOST'].$this->getPathUri();

        if (method_exists($this->context->controller, 'addJquery')) {
            $this->context->controller->addJS($this->_path.'js/blocklayered_admin.js');
            $this->context->controller->addjqueryPlugin('sortable');
        }

        $this->context->controller->addCSS($this->_path.'css/blocklayered_admin.css');

        if (Tools::getValue('add_new_filters_template')) {
            $this->context->smarty->assign(
                [
                    'current_url'       => $this->context->link->getAdminLink('AdminModules').'&configure=blocklayered&tab_module=front_office_features&module_name=blocklayered',
                    'uri'               => $this->getPathUri(),
                    'id_layered_filter' => 0,
                    'template_name'     => sprintf($this->l('My template - %s'), date('Y-m-d')),
                    'attribute_groups'  => $attribute_groups,
                    'features'          => $features,
                    'total_filters'     => 6 + count($attribute_groups) + count($features),
                ]
            );

            $this->context->smarty->assign('categories_tree', $tree_categories_helper->render());

            return $this->display(__FILE__, 'views/templates/admin/add.tpl');
        } else {
            if (Tools::getValue('edit_filters_template')) {
                $template = Db::getInstance()->getRow(
                    '
				SELECT *
				FROM `'._DB_PREFIX_.'layered_filter`
				WHERE id_layered_filter = '.(int) Tools::getValue('id_layered_filter')
                );

                $filters = json_decode($template['filters'], true);
                // Retrocompatibility for module versions <= 3.0.3, which used to
                // store data not JSON encoded, but serialized. Can get removed a
                // couple of releases later.
                if ( ! $filters) {
                        $filters = Tools::unSerialize($template['filters']);
                }

                $tree_categories_helper->setSelectedCategories($filters['categories']);
                $this->context->smarty->assign('categories_tree', $tree_categories_helper->render());

                $select_shops = $filters['shop_list'];
                unset($filters['categories']);
                unset($filters['shop_list']);

                $this->context->smarty->assign(
                    [
                        'current_url'       => $this->context->link->getAdminLink('AdminModules').'&configure=blocklayered&tab_module=front_office_features&module_name=blocklayered',
                        'uri'               => $this->getPathUri(),
                        'id_layered_filter' => (int) Tools::getValue('id_layered_filter'),
                        'template_name'     => $template['name'],
                        'attribute_groups'  => $attribute_groups,
                        'features'          => $features,
                        'filters'           => Tools::jsonEncode($filters),
                        'total_filters'     => 6 + count($attribute_groups) + count($features),
                    ]
                );

                return $this->display(__FILE__, 'views/templates/admin/add.tpl');
            } else {
                $this->context->smarty->assign(
                    [
                        'message'                => $message,
                        'uri'                    => $this->getPathUri(),
                        'PS_LAYERED_INDEXED'     => Configuration::getGlobalValue('PS_LAYERED_INDEXED'),
                        'current_url'            => Tools::safeOutput(preg_replace('/&deleteFilterTemplate=[0-9]*&id_layered_filter=[0-9]*/', '', $_SERVER['REQUEST_URI'])),
                        'id_lang'                => Context::getContext()->cookie->id_lang,
                        'token'                  => substr(Tools::encrypt('blocklayered/index'), 0, 10),
                        'base_folder'            => urlencode(_PS_ADMIN_DIR_),
                        'price_indexer_url'      => $module_url.'blocklayered-price-indexer.php'.'?token='.substr(Tools::encrypt('blocklayered/index'), 0, 10),
                        'full_price_indexer_url' => $module_url.'blocklayered-price-indexer.php'.'?token='.substr(Tools::encrypt('blocklayered/index'), 0, 10).'&full=1',
                        'attribute_indexer_url'  => $module_url.'blocklayered-attribute-indexer.php'.'?token='.substr(Tools::encrypt('blocklayered/index'), 0, 10),
                        'url_indexer_url'        => $module_url.'blocklayered-url-indexer.php'.'?token='.substr(Tools::encrypt('blocklayered/index'), 0, 10).'&truncate=1',
                        'filters_templates'      => Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT * FROM '._DB_PREFIX_.'layered_filter ORDER BY date_add DESC'),
                        'hide_values'            => Configuration::get('PS_LAYERED_HIDE_0_VALUES'),
                        'show_quantities'        => Configuration::get('PS_LAYERED_SHOW_QTIES'),
                        'full_tree'              => Configuration::get('PS_LAYERED_FULL_TREE'),
                        'category_depth'         => Configuration::get('PS_LAYERED_FILTER_CATEGORY_DEPTH'),
                        'price_use_tax'          => (bool) Configuration::get('PS_LAYERED_FILTER_PRICE_USETAX'),
                        'index_cdt'              => Configuration::get('PS_LAYERED_FILTER_INDEX_CDT'),
                        'index_qty'              => Configuration::get('PS_LAYERED_FILTER_INDEX_QTY'),
                        'index_mnf'              => Configuration::get('PS_LAYERED_FILTER_INDEX_MNF'),
                        'index_cat'              => Configuration::get('PS_LAYERED_FILTER_INDEX_CAT'),
                        'limit_warning'          => $this->displayLimitPostWarning(21 + count($attribute_groups) * 3 + count($features) * 3),
                        'price_use_rounding'     => (bool) Configuration::get('PS_LAYERED_FILTER_PRICE_ROUNDING'),
                    ]
                );

                return $this->display(__FILE__, 'views/templates/admin/view.tpl');
            }
        }
    }

    public function displayLimitPostWarning($count)
    {
        $return = [];
        if ((ini_get('suhosin.post.max_vars') && ini_get('suhosin.post.max_vars') < $count) || (ini_get('suhosin.request.max_vars') && ini_get('suhosin.request.max_vars') < $count)) {
            $return['error_type'] = 'suhosin';
            $return['post.max_vars'] = ini_get('suhosin.post.max_vars');
            $return['request.max_vars'] = ini_get('suhosin.request.max_vars');
            $return['needed_limit'] = $count + 100;
        } elseif (ini_get('max_input_vars') && ini_get('max_input_vars') < $count) {
            $return['error_type'] = 'conf';
            $return['max_input_vars'] = ini_get('max_input_vars');
            $return['needed_limit'] = $count + 100;
        }

        return $return;
    }

    public function ajaxCall()
    {
        global $smarty, $cookie;

        $selected_filters = $this->getSelectedFilters();
        $filter_block = $this->getFilterBlock($selected_filters);
        $this->getProducts($selected_filters, $products, $nb_products, $p, $n, $pages_nb, $start, $stop, $range);

        // Add pagination variable
        $nArray = (int) Configuration::get('PS_PRODUCTS_PER_PAGE') != 10 ? [(int) Configuration::get('PS_PRODUCTS_PER_PAGE'), 10, 20, 50] : [10, 20, 50];
        // Clean duplicate values
        $nArray = array_unique($nArray);
        asort($nArray);

        Hook::exec(
            'actionProductListModifier',
            [
                'nb_products'  => &$nb_products,
                'cat_products' => &$products,
            ]
        );

        $this->context->controller->addColorsToProductList($products);

        $category = new Category(Tools::getValue('id_category_layered', Configuration::get('PS_HOME_CATEGORY')), (int) $cookie->id_lang);

        // Generate meta title and meta description
        $category_title = (empty($category->meta_title) ? $category->name : $category->meta_title);
        $category_metas = Meta::getMetaTags((int) $cookie->id_lang, 'category');
        $title = '';
        $keywords = '';

        if (is_array($filter_block['title_values'])) {
            foreach ($filter_block['title_values'] as $key => $val) {
                $title .= ' > '.$key.' '.implode('/', $val);
                $keywords .= $key.' '.implode('/', $val).', ';
            }
        }

        $title = $category_title.$title;

        if (!empty($title)) {
            $meta_title = $title;
        } else {
            $meta_title = $category_metas['meta_title'];
        }

        $meta_description = $category_metas['meta_description'];

        $keywords = Tools::substr(Tools::strtolower($keywords), 0, 1000);
        if (!empty($keywords)) {
            $meta_keywords = rtrim($category_title.', '.$keywords.', '.$category_metas['meta_keywords'], ', ');
        }

        $smarty->assign(
            [
                'homeSize'            => Image::getSize(ImageType::getFormatedName('home')),
                'nb_products'         => $nb_products,
                'category'            => $category,
                'pages_nb'            => (int) $pages_nb,
                'p'                   => (int) $p,
                'n'                   => (int) $n,
                'range'               => (int) $range,
                'start'               => (int) $start,
                'stop'                => (int) $stop,
                'n_array'             => ((int) Configuration::get('PS_PRODUCTS_PER_PAGE') != 10) ? [(int) Configuration::get('PS_PRODUCTS_PER_PAGE'), 10, 20, 50] : [10, 20, 50],
                'comparator_max_item' => (int) (Configuration::get('PS_COMPARATOR_MAX_ITEM')),
                'products'            => $products,
                'products_per_page'   => (int) Configuration::get('PS_PRODUCTS_PER_PAGE'),
                'static_token'        => Tools::getToken(false),
                'page_name'           => 'category',
                'nArray'              => $nArray,
                'compareProducts'     => CompareProduct::getCompareProducts((int) $this->context->cookie->id_compare),
            ]
        );

        // Prevent bug with old template where category.tpl contain the title of the category and category-count.tpl do not exists
        if (file_exists(_PS_THEME_DIR_.'category-count.tpl')) {
            $category_count = $smarty->fetch(_PS_THEME_DIR_.'category-count.tpl');
        } else {
            $category_count = '';
        }

        if ($nb_products == 0) {
            $product_list = $this->display(__FILE__, 'blocklayered-no-products.tpl');
        } else {
            $product_list = $smarty->fetch(_PS_THEME_DIR_.'product-list.tpl');
        }

        $vars = [
            'filtersBlock'         => utf8_encode($this->generateFiltersBlock($selected_filters)),
            'productList'          => utf8_encode($product_list),
            'pagination'           => $smarty->fetch(_PS_THEME_DIR_.'pagination.tpl'),
            'categoryCount'        => $category_count,
            'meta_title'           => $meta_title.' - '.Configuration::get('PS_SHOP_NAME'),
            'heading'              => $meta_title,
            'meta_keywords'        => isset($meta_keywords) ? $meta_keywords : null,
            'meta_description'     => $meta_description,
            'current_friendly_url' => ((int) $n == (int) $nb_products) ? '#/show-all' : '#'.$filter_block['current_friendly_url'],
            'filters'              => $filter_block['filters'],
            'nbRenderedProducts'   => (int) $nb_products,
            'nbAskedProducts'      => (int) $n,
            'pagination_bottom'    => $smarty->assign('paginationId', 'bottom')
                                             ->fetch(_PS_THEME_DIR_.'pagination.tpl'),
        ];

        /* We are sending an array in jSon to the .js controller, it will update both the filters and the products zones */

        return json_encode($vars);
    }
}
