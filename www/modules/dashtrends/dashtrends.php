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
 * Class Dashtrends
 */
class Dashtrends extends Module
{
    protected $dashboard_data;
    protected $dashboard_data_compare;
    protected $dashboard_data_sum;
    protected $dashboard_data_sum_compare;
    protected $data_trends;

    /**
     * Dashtrends constructor.
     */
    public function __construct()
    {
        $this->name = 'dashtrends';
        $this->tab = 'dashboard';
        $this->version = '2.0.5';
        $this->author = 'thirty bees';
        $this->need_instance = 0;

        $this->push_filename = _PS_CACHE_DIR_.'push/trends';
        $this->allow_push = true;

        parent::__construct();
        $this->displayName = $this->l('Dashboard Trends');
        $this->description = $this->l('Adds a block with a graphical representation of the development of your store(s) based on selected key data.');
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->tb_min_version = '1.0.0';
    }

    /**
     * @return bool
     */
    public function install()
    {
        return (parent::install()
            && $this->registerHook('dashboardZoneTwo')
            && $this->registerHook('dashboardData')
            && $this->registerHook('actionAdminControllerSetMedia')
            && $this->registerHook('actionOrderStatusPostUpdate')
        );
    }

    /**
     * @return void
     */
    public function hookActionAdminControllerSetMedia()
    {
        if (get_class($this->context->controller) == 'AdminDashboardController') {
            $this->context->controller->addJs($this->_path.'views/js/'.$this->name.'.js');
        }
    }

    /**
     * @param $params
     *
     * @return string
     */
    public function hookDashboardZoneTwo($params)
    {
        $this->context->smarty->assign([
            'currency'                     => $this->context->currency,
        ]);

        return $this->display(__FILE__, 'dashboard_zone_two.tpl');
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array
     */
    protected function getData($dateFrom, $dateTo)
    {
        // We need the following figures to calculate our stats
        $tmpData = [
            'visits'              => [],
            'orders'              => [],
            'total_paid_tax_excl' => [],
            'total_purchases'     => [],
            'total_expenses'      => [],
        ];

        if (Configuration::get('PS_DASHBOARD_SIMULATION')) {
            $from = strtotime($dateFrom.' 00:00:00');
            $to = min(time(), strtotime($dateTo.' 23:59:59'));
            for ($date = $from; $date <= $to; $date = strtotime('+1 day', $date)) {
                $tmpData['visits'][$date] = round(rand(2000, 20000));
                $tmpData['conversion_rate'][$date] = rand(80, 250) / 100;
                $tmpData['average_cart_value'][$date] = round(rand(60, 200), 2);
                $tmpData['orders'][$date] = round($tmpData['visits'][$date] * $tmpData['conversion_rate'][$date] / 100);
                $tmpData['total_paid_tax_excl'][$date] = $tmpData['orders'][$date] * $tmpData['average_cart_value'][$date];
                $tmpData['total_purchases'][$date] = $tmpData['total_paid_tax_excl'][$date] * rand(50, 70) / 100;
                $tmpData['total_expenses'][$date] = $tmpData['total_paid_tax_excl'][$date] * rand(0, 10) / 100;
            }
        } else {
            $tmpData['visits'] = AdminStatsController::getVisits(false, $dateFrom, $dateTo, 'day');
            $tmpData['orders'] = AdminStatsController::getOrders($dateFrom, $dateTo, 'day');
            $tmpData['total_paid_tax_excl'] = AdminStatsController::getTotalSales($dateFrom, $dateTo, 'day');
            $tmpData['total_purchases'] = AdminStatsController::getPurchases($dateFrom, $dateTo, 'day');
            $tmpData['total_expenses'] = AdminStatsController::getExpenses($dateFrom, $dateTo, 'day');
        }

        return $tmpData;
    }

    /**
     * @param $dateFrom
     * @param $dateTo
     * @param $grossData
     *
     * @return array
     */
    protected function refineData($dateFrom, $dateTo, $grossData)
    {
        $refinedData = [
            'sales'              => [],
            'orders'             => [],
            'average_cart_value' => [],
            'visits'             => [],
            'conversion_rate'    => [],
            'net_profits'        => [],
        ];

        $from = strtotime($dateFrom.' 00:00:00');
        $to = min(time(), strtotime($dateTo.' 23:59:59'));
        for ($date = $from; $date <= $to; $date = strtotime('+1 day', $date)) {
            $refinedData['sales'][$date] = 0;
            if (isset($grossData['total_paid_tax_excl'][$date])) {
                $refinedData['sales'][$date] += $grossData['total_paid_tax_excl'][$date];
            }

            $refinedData['orders'][$date] = isset($grossData['orders'][$date]) ? $grossData['orders'][$date] : 0;

            $refinedData['average_cart_value'][$date] = $refinedData['orders'][$date] ? $refinedData['sales'][$date] / $refinedData['orders'][$date] : 0;

            $refinedData['visits'][$date] = isset($grossData['visits'][$date]) ? $grossData['visits'][$date] : 0;

            $refinedData['conversion_rate'][$date] = $refinedData['visits'][$date] ? $refinedData['orders'][$date] / $refinedData['visits'][$date] : 0;

            $refinedData['net_profits'][$date] = 0;
            if (isset($grossData['total_paid_tax_excl'][$date])) {
                $refinedData['net_profits'][$date] += $grossData['total_paid_tax_excl'][$date];
            }
            if (isset($grossData['total_purchases'][$date])) {
                $refinedData['net_profits'][$date] -= $grossData['total_purchases'][$date];
            }
            if (isset($grossData['total_expenses'][$date])) {
                $refinedData['net_profits'][$date] -= $grossData['total_expenses'][$date];
            }
        }

        return $refinedData;
    }

    /**
     * @param mixed $data
     *
     * @return array
     */
    protected function addupData($data)
    {
        $summing = [
            'sales'              => 0,
            'orders'             => 0,
            'average_cart_value' => 0,
            'visits'             => 0,
            'conversion_rate'    => 0,
            'net_profits'        => 0,
        ];

        $summing['sales'] = array_sum($data['sales']);
        $summing['orders'] = array_sum($data['orders']);
        $summing['average_cart_value'] = $summing['sales'] ? $summing['sales'] / $summing['orders'] : 0;
        $summing['visits'] = array_sum($data['visits']);
        $summing['conversion_rate'] = $summing['visits'] ? $summing['orders'] / $summing['visits'] : 0;
        $summing['net_profits'] = array_sum($data['net_profits']);

        return $summing;
    }

    /**
     * @param mixed $data1
     * @param mixed $data2
     *
     * @return array
     */
    protected function compareData($data1, $data2)
    {
        return [
            'sales_score_trends'           => [
                'way'   => ($data1['sales'] == $data2['sales'] ? 'right' : ($data1['sales'] > $data2['sales'] ? 'up' : 'down')),
                'value' => ($data1['sales'] > $data2['sales'] ? '+' : '').($data2['sales'] ? round(100 * $data1['sales'] / $data2['sales'] - 100, 2).'%' : '&infin;'),
            ],
            'orders_score_trends'          => [
                'way'   => ($data1['orders'] == $data2['orders'] ? 'right' : ($data1['orders'] > $data2['orders'] ? 'up' : 'down')),
                'value' => ($data1['orders'] > $data2['orders'] ? '+' : '').($data2['orders'] ? round(100 * $data1['orders'] / $data2['orders'] - 100, 2).'%' : '&infin;'),
            ],
            'cart_value_score_trends'      => [
                'way'   => ($data1['average_cart_value'] == $data2['average_cart_value'] ? 'right' : ($data1['average_cart_value'] > $data2['average_cart_value'] ? 'up' : 'down')),
                'value' => ($data1['average_cart_value'] > $data2['average_cart_value'] ? '+' : '').($data2['average_cart_value'] ? round(100 * $data1['average_cart_value'] / $data2['average_cart_value'] - 100, 2).'%' : '&infin;'),
            ],
            'visits_score_trends'          => [
                'way'   => ($data1['visits'] == $data2['visits'] ? 'right' : ($data1['visits'] > $data2['visits'] ? 'up' : 'down')),
                'value' => ($data1['visits'] > $data2['visits'] ? '+' : '').($data2['visits'] ? round(100 * $data1['visits'] / $data2['visits'] - 100, 2).'%' : '&infin;'),
            ],
            'conversion_rate_score_trends' => [
                'way'   => ($data1['conversion_rate'] == $data2['conversion_rate'] ? 'right' : ($data1['conversion_rate'] > $data2['conversion_rate'] ? 'up' : 'down')),
                'value' => ($data1['conversion_rate'] > $data2['conversion_rate'] ? '+' : '').($data2['conversion_rate'] ? sprintf($this->l('%s points'), round(100 * ($data1['conversion_rate'] - $data2['conversion_rate']), 2)) : '&infin;'),
            ],
            'net_profits_score_trends'     => [
                'way'   => ($data1['net_profits'] == $data2['net_profits'] ? 'right' : ($data1['net_profits'] > $data2['net_profits'] ? 'up' : 'down')),
                'value' => ($data1['net_profits'] > $data2['net_profits'] ? '+' : '').($data2['net_profits'] ? round(100 * $data1['net_profits'] / $data2['net_profits'] - 100, 2).'%' : '&infin;'),
            ],
        ];
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function hookDashboardData($params)
    {
        $this->currency = clone $this->context->currency;

        // Retrieve, refine and add up data for the selected period
        $tmpData = $this->getData($params['date_from'], $params['date_to']);
        $this->dashboard_data = $this->refineData($params['date_from'], $params['date_to'], $tmpData);
        $this->dashboard_data_sum = $this->addupData($this->dashboard_data);

        if ($params['compare_from'] && $params['compare_from'] != '0000-00-00') {
            // Retrieve, refine and add up data for the comparison period
            $tmpDataCompare = $this->getData($params['compare_from'], $params['compare_to']);
            $this->dashboard_data_compare = $this->refineData($params['compare_from'], $params['compare_to'], $tmpDataCompare);
            $this->dashboard_data_sum_compare = $this->addupData($this->dashboard_data_compare);

            $this->data_trends = $this->compareData($this->dashboard_data_sum, $this->dashboard_data_sum_compare);
            $this->dashboard_data_compare = $this->translateCompareData($this->dashboard_data, $this->dashboard_data_compare);
        }

        $salesScore = Tools::displayPrice($this->dashboard_data_sum['sales'], $this->currency).$this->addTaxSuffix();

        $cartValueScore = Tools::displayPrice($this->dashboard_data_sum['average_cart_value'], $this->currency).$this->addTaxSuffix();

        $netProfitScore = Tools::displayPrice($this->dashboard_data_sum['net_profits'], $this->currency).$this->addTaxSuffix();

        return [
            'data_value'  => [
                'sales_score'           => $salesScore,
                'orders_score'          => Tools::displayNumber($this->dashboard_data_sum['orders'], $this->currency),
                'cart_value_score'      => $cartValueScore,
                'visits_score'          => Tools::displayNumber($this->dashboard_data_sum['visits'], $this->currency),
                'conversion_rate_score' => round(100 * $this->dashboard_data_sum['conversion_rate'], 2).'%',
                'net_profits_score'     => $netProfitScore,
            ],
            'data_trends' => $this->data_trends,
            'data_chart'  => ['dash_trends_chart1' => $this->getChartTrends()],
        ];
    }

    /**
     * @return string
     */
    protected function addTaxSuffix()
    {
        return ' <small>'.$this->l('tax excl.').'</small>';
    }

    /**
     * @param mixed $normal
     * @param mixed $compare
     *
     * @return array
     */
    protected function translateCompareData($normal, $compare)
    {
        $translatedArray = [];
        foreach ($compare as $key => $dateArray) {
            $normalMin = key($normal[$key]);
            end($normal[$key]); // move the internal pointer to the end of the array
            $normalMax = key($normal[$key]);
            reset($normal[$key]);
            $normalSize = $normalMax - $normalMin;

            $compareMin = key($compare[$key]);
            end($compare[$key]); // move the internal pointer to the end of the array
            $compareMax = key($compare[$key]);
            reset($compare[$key]);
            $compareSize = $compareMax - $compareMin;

            $translatedArray[$key] = array();
            foreach ($dateArray as $compareDate => $value) {
                $translation = $normalMin + ($compareDate - $compareMin) * ($normalSize / $compareSize);
                $translatedArray[$key][number_format($translation, 0, '', '')] = $value;
            }
        }

        return $translatedArray;
    }

    /**
     * @return array
     */
    public function getChartTrends()
    {
        $chartData = [];
        $chartDataCompare = [];
        foreach (array_keys($this->dashboard_data) as $chartKey) {
            $chartData[$chartKey] = $chartDataCompare[$chartKey] = [];

            if (!$count = count($this->dashboard_data[$chartKey])) {
                continue;
            }

            // We calibrate 100% to the mean
            $calibration = array_sum($this->dashboard_data[$chartKey]) / $count;

            foreach ($this->dashboard_data[$chartKey] as $key => $value) {
                $chartData[$chartKey][] = [$key, $value];
            }
            // min(10) is there to limit the growth to 1000%, beyond this limit it becomes unreadable
            //$chart_data[$chart_key][] = array(1000 * $key, $calibration ? min(10, $value / $calibration) : 0);

            if ($this->dashboard_data_compare) {
                foreach ($this->dashboard_data_compare[$chartKey] as $key => $value) {
                    $chartDataCompare[$chartKey][] = [$key, $value];
                }
            }
            // min(10) is there to limit the growth to 1000%, beyond this limit it becomes unreadable
            /*$chart_data_compare[$chart_key][] = array(
                1000 * $key,
                $calibration ? min(10, $value / $calibration) : 0
            );*/
        }

        $charts = [
            'sales'              => $this->l('Sales'),
            'orders'             => $this->l('Orders'),
            'average_cart_value' => $this->l('Average Cart Value'),
            'visits'             => $this->l('Visits'),
            'conversion_rate'    => $this->l('Conversion Rate'),
            'net_profits'        => $this->l('Net Profit'),
        ];

        $gfxColor = ['#1777B6', '#2CA121', '#E61409', '#FF7F00', '#6B399C', '#B3591F'];
        $gfxColorCompare = ['#A5CEE4', '#B1E086', '#FD9997', '#FFC068', '#CAB1D7', '#D2A689'];

        $i = 0;
        $data = ['chart_type' => 'line_chart_trends', 'date_format' => $this->context->language->date_format_lite, 'data' => []];
        foreach ($charts as $key => $title) {
            $data['data'][] = [
                'id'       => $key,
                'key'      => $title,
                'color'    => $gfxColor[$i],
                'values'   => $chartData[$key],
                'disabled' => ($key == 'sales' ? false : true),
            ];
            if ($this->dashboard_data_compare) {
                $data['data'][] = [
                    'id'       => $key.'_compare',
                    'color'    => $gfxColorCompare[$i],
                    'key'      => sprintf($this->l('%s (previous period)'), $title),
                    'values'   => $chartDataCompare[$key],
                    'disabled' => ($key == 'sales' ? false : true),
                ];
            }
            $i++;
        }

        return $data;
    }

    /**
     * @param array $params
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
        Tools::changeFileMTime($this->push_filename);
    }
}
