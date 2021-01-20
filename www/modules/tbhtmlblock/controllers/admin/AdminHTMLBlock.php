<?php
/**
 * Copyright (C) 2019 thirty bees
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
 * @copyright 2019 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

if ( ! defined('_TB_VERSION_')) {
    exit;
}

class AdminHTMLBlockController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->show_toolbar = true;
        $this->identifier = 'id_block';
        $this->table = 'tbhtmlblock';

        parent::__construct();
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display) || $this->display =='list') {
            $this->page_header_toolbar_btn['new_block'] = [
                'href' => static::$currentIndex.'&addtbhtmlblock&token='.$this->token,
                'desc' => $this->l('Add new block', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    public function renderList()
    {
        $blocks = $this->module->getAllBlocks();
        $content = '';

        if ($blocks) {
            foreach ($blocks as $block) {
                $fieldsList = [
                    'id_block'  => [
                        'title'   => 'ID',
                        'align'   => 'center',
                        'class'   => 'fixed-width-xs',
                    ],
                    'name'      => [
                        'title'   => $this->l('Name'),
                    ],
                    'active'    => [
                        'title'   => $this->l('Status'),
                        'active'  => 'status',
                        'type'    => 'bool',
                    ],
                    'position'  => [
                        'title'     => $this->l('Position'),
                        'position'  => 'position',
                    ],
                ];

                $helper = new HelperList();
                $helper->shopLinkType = '';
                $helper->simple_header = true;
                $helper->actions = ["edit", "delete"];
                $helper->show_toolbar = false;
                $helper->module = $this;
                $helper->listTotal = count($blocks);
                $helper->identifier = 'id_block';
                $helper->position_identifier = 'position';
                $helper->title = $block['name'];
                $helper->orderBy = 'position';
                $helper->orderWay = 'ASC';
                $helper->table = $this->table;
                $helper->token = Tools::getAdminTokenLite('AdminHTMLBlock');
                $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

                $content .= $helper->generateList($block['blocks'], $fieldsList);
            }
        }

        return $content;
    }


    public function renderForm()
    {
        $inputs[] = [
            'type'  => 'text',
            'label' => $this->l('Block Name (For your eyes only)'),
            'name'  => 'name',
        ];
        $inputs[] = [
            'type'  => 'textarea',
            'label' => $this->l('Content'),
            'name'  => 'content',
            'lang'  => true,
            'autoload_rte' => true,
        ];
        $inputs[] = [
            'type'  => 'select',
            'label' => $this->l('Hook'),
            'name'  => 'hook_name',
            'options' => [
                    'query' => $this->module->getHooksWithNames(),
                    'id'    => 'name',
                    'name'  => 'title',
                ],
        ];
        $inputs[] = [
            'type'   => 'switch',
            'label'  => $this->l("Active"),
            'name'   => 'active',
            'values' => [
                [
                    'id'    => 'active_on',
                    'value' => 1,
                ],
                [
                    'id'    => 'active_off',
                    'value' => 0,
                ],
            ],
        ];

        if ($this->display == 'edit') {
            $inputs[] = [
                'type' => 'hidden',
                'name' => 'id_block',
            ];
            $title = $this->l('Edit Block');
            $action = 'submitEditBlock';
            $this->fields_value = $this->module->getSingleBlockData(Tools::getValue('id_block'));
        } else {
            $title = $this->l('Add new Entry');
            $action = 'submitAddBlock';
        }

        $this->fields_form = [
            'legend' => [
                'title' => $title,
                'icon'  => 'icon-cogs',
            ],
            'input' => $inputs,
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
                'name'  => $action,
            ],
        ];

        return parent::renderForm();
    }

    public function renderView()
    {
        $this->tpl_view_vars['object'] = $this->loadObject();

        return parent::renderView();
    }

    public function postProcess()
    {
        if ($this->ajax) {
            $action = Tools::getValue('action');
            if ( ! empty($action)
                && method_exists($this, 'ajaxProcess'.Tools::toCamelCase($action))
            ) {
                $return = $this->{'ajaxProcess'.Tools::toCamelCase($action)}();
            }
        } else {
            if (Tools::isSubmit('submitAddBlock')) {
                $this->processAdd();
            } elseif (Tools::isSubmit('submitEditBlock')) {
                $this->processUpdate();
            } elseif (Tools::isSubmit('status'.$this->table)) {
                $this->toggleStatus();
            } elseif (Tools::isSubmit('delete'.$this->table)
                && Tools::isSubmit('id_block')
            ) {
                $this->processDelete();
            }
        }
    }

    public function toggleStatus()
    {
        $idBlock = (int)Tools::getValue('id_block');
        Db::getInstance()->update($this->module->table_name, ['active' => !$this->module->getBlockStatus($idBlock)], 'id_block = '.$idBlock);

        if (empty($this->errors)) {
            $this->redirect_after = static::$currentIndex.'&conf=4&token='.$this->token;
        }
    }

    public function processAdd()
    {
        $blockName = Tools::getValue('name');

        if ( ! $blockName || ! Validate::isGenericName($blockName)) {
            $this->_errors[] = $this->l('Invalid name');
        } else {
            if ( ! Db::getInstance()->insert($this->module->table_name, ['name' => $blockName, 'active' => Tools::getValue('active')])) {
                $this->_errors[] = $this->l('Error while adding the new block, please retry');
            } else {
                $blockId = Db::getInstance()->Insert_ID();

                $hookName = Tools::getValue('hook_name');
                $maxP = Db::getInstance()->getValue('SELECT MAX(position) FROM ' . _DB_PREFIX_ . $this->module->table_name_hook . ' WHERE hook_name = "' . pSQL($hookName).'"');

                if ($maxP === false) {
                    $maxP = 0;
                } else {
                    $maxP++;
                }

                $hookData = [
                    'id_block'  => $blockId,
                    'hook_name' => pSQL($hookName),
                    'position'  => $maxP,
                ];

                if ( ! Db::getInstance()->insert($this->module->table_name_hook, $hookData)) {
                    Db::getInstance()->delete($this->module->table_name, 'id_block = ' . $blockId);
                    $this->_errors[] = $this->l('Error while adding the hook. ');
                } else {
                    foreach ($this->getLanguages() as $lang) {
                        $content = Tools::getValue('content_'.$lang['id_lang']);
                        if ( ! Db::getInstance()->insert($this->module->table_name_lang, ['id_block' => $blockId, 'id_lang' => $lang['id_lang'], 'content' => pSQL($content, TRUE)]))
                            $this->_errors[] = $this->l('Error while adding the block\'s content for language "'.$lang['id_lang'].'"');
                    }
                }
            }
        }

        if (empty($this->errors)) {
            $this->redirect_after = static::$currentIndex.'&conf=3&token='.$this->token;
        }
    }

    public function processUpdate()
    {
        $blockName = Tools::getValue('name');
        if ( ! $blockName || ! Validate::isGenericName($blockName))
            $this->_errors[] = $this->l('Invalid name');
        else {
            if (!Db::getInstance()->update($this->module->table_name, ['name' => $blockName, 'active' => Tools::getValue('active')], 'id_block = '. (int)Tools::getValue('id_block'))) {
                $this->_errors[] = $this->l('Error while updating the block ');
            } else {
                if (!Db::getInstance()->update($this->module->table_name_hook, ['hook_name' => pSQL(Tools::getValue('hook_name'))], 'id_block = '. (int)Tools::getValue('id_block'))) {
                    $this->_errors[] = $this->l('Error while updating the hook ');
                } else {
                    foreach ($this->getLanguages() as $lang) {
                        $content = Tools::getValue('content_'.$lang['id_lang']);

                        // add the language if not present
                        $isLangAdded = Db::getInstance()->getValue('SELECT id_block FROM '._DB_PREFIX_.$this->module->table_name_lang.' WHERE id_block = '.(int)Tools::getValue('id_block').' AND id_lang = ' . $lang['id_lang']);
                        if ( ! $isLangAdded) {
                            Db::getInstance()->insert(
                                $this->module->table_name_lang,
                                [
                                    'id_lang'   => $lang['id_lang'],
                                    'id_block'  => (int)Tools::getValue('id_block'),
                                    'content'   => '',
                                ]
                            );
                        }

                        if ( ! Db::getInstance()->update(
                            $this->module->table_name_lang,
                            ['content' => pSQL($content, true)],
                            'id_block = '.(int) Tools::getValue('id_block').' AND id_lang = ' . $lang['id_lang']
                        )) {
                            $this->_errors[] = $this->l('Error while updating the block\'s content for language "'.$lang['id_lang'].'"');
                        }
                    }
                }
            }
        }

        if (empty($this->errors)) {
            $this->redirect_after = static::$currentIndex.'&conf=4&token='.$this->token;
        }
    }

    public function processDelete()
    {
        $idBlock = Tools::getValue('id_block');
        Db::getInstance()->delete($this->module->table_name, 'id_block = ' . $idBlock);
        Db::getInstance()->delete($this->module->table_name_hook, 'id_block = ' . $idBlock);
        Db::getInstance()->delete($this->module->table_name_lang, 'id_block = ' . $idBlock);

        $this->redirect_after = static::$currentIndex.'&conf=1&token='.$this->token;
    }

    public function ajaxProcessUpdatePositions()
    {
        $positions = Tools::getValue('block');

        foreach ($positions as $position => $value) {
            $pos = explode('_', $value);
            Db::getInstance()->update($this->module->table_name_hook, ['position' => $position], 'id_block =' . (int)$pos[2]);
        }
    }
}
