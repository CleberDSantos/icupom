<?php
/**
 * Copyright (C) 2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2018 thirty bees
 * @license   Open Software License (OSL 3.0)
 */

/**
 * Class PageCacheEntry
 *
 * @since 1.1.0
 */
class PageCacheEntryCore
{
    const CONSTANT = 'const';
    const OBJECT_MODEL = 'obj';
    const PRODUCT_PROPERTIES = 'prod';
    const SMARTY_OBJECT = 'smarty';
    const COOKIE_OBJECT = 'cookie';
    const CONTEXT_OBJECT = 'context';

    private $isNew = true;
    private $valid = true;

    private $hooks = [];
    private $content = null;

    /**
     * Initialize cache entry using serialized data from cache.
     *
     * @param $serialized json object representing this entry
     *
     * @since 1.1.0
     */
    public function setFromCache($serialized)
    {
        $this->valid = true;
        $this->isNew = is_null($serialized);
        if (! $this->isNew) {
            $data = json_decode($serialized, true);
            if ($data && isset($data['hooks']) && isset($data['content'])) {
                $this->hooks = $data['hooks'];
                $this->content = $data['content'];
            } else {
                $this->isNew = true;
            }
        }
    }


    /**
     * Serialize this cache entry to json object.
     *
     * @return string json object
     *
     * @since 1.1.0
     */
    public function serialize()
    {
        return json_encode([
            'hooks' => $this->hooks,
            'content' => $this->content
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Returns true, if this cache entry exists in cache.
     *
     * @return bool
     *
     * @since 1.1.0
     */
    public function exists()
    {
        return !$this->isNew;
    }

    /**
     * Returns true, if this cache entry is valid.
     *
     * If entry is not valid, we can't call getFreshContent, because there are
     * some hook parameters that can't be instantiated.
     *
     * @return bool
     *
     * @since 1.1.0
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * Set page html content.
     *
     * @param $content
     *
     * @since 1.1.0
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Returns page html content that was stored inside cache.
     *
     * use getFreshContent method if you want to get current content, with
     * dynamic hook sections replaced with current versions.
     *
     * @return string page html content
     *
     * @since 1.1.0
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * This method returns array describing dynamic hook sections.
     *
     * When we want to generate fresh version of page content, we have to
     * execute each hook in this array, and replace relevant section in old
     * content with hook return value.
     *
     * @return array of dynamic hooks
     *
     * @since 1.1.0
     */
    public function getHooks()
    {
        return $this->hooks;
    }

    /**
     * Registers new dynamic hook, and returns unique id of dynamic section.
     *
     * Returned unique id is used by Hook class to wrap hook content into html
     * comments that works as a section delimiters. These delimiters are later
     * used to replace hook content by fresh value.
     *
     *   <body>
     *      ....
     *      <--[hook:1:21:11]-->
     *        ...
     *        content generated by hook
     *      <--[hook:1:21:11]--
     *      ...
     *   </body>
     *
     * Some hooks needs input parameters. When we call Hook::exec from
     * getFreshContent() method, we need to pass these parameters.
     * Unfortunately, it's not possible to serialize parameters. Instead, we
     * will try to describe parameters, and use this description later to
     * instantiate the parameter objects on the fly.
     *
     * @param $moduleId module id
     * @param $hookId hook id
     * @param $hookName hook name
     * @param $hookParams hook params
     *
     * @return string section id
     *
     * @since 1.1.0
     */
    public function setHook($moduleId, $hookId, $hookName, $hookParams)
    {
        $cnt = count($this->hooks) + 1;
        $id = 'hook:' . $cnt;
        $params = [];
        foreach ($hookParams as $key => $param) {
            // describe parameter so we know how to instantiate it
            $params[$key] = $this->describeParam($param);
            if (! $params[$key]) {
                // hook parameter is some object we don't know how to recreate from scratch
                // that means it's not possible to serialize it, and thus this particular
                // page can't be cached
                trigger_error("PageCacheEntry: can't serialize parameter $key for hook $hookName", E_USER_NOTICE);
                $this->valid = false;
            }
        }
        $this->hooks[] = [
            'id' => $id,
            'hook' => $hookName,
            'moduleId' => $moduleId,
            'params' => $params
        ];
        return $id;
    }


    /**
     * This method returns fresh version of cached page. Every dynamic hook in
     * $hooks array will be executed, and its return value will be replaced
     * into relevant section in cached html content.
     *
     * @return string fresh version of cached page
     * @throws PrestaShopException
     *
     * @since 1.1.0
     */
    public function getFreshContent()
    {
        // old content version
        $content = $this->content;

        // call all dynamic hooks and replace fresh content
        foreach ($this->hooks as $hook) {
            $key = $hook['id'];
            $hookName = $hook['hook'];
            $moduleId = $hook['moduleId'];
            $params = $hook['params'] ? array_map([$this, 'instantiateParam'], $hook['params']) : [];
            $hookContent = Hook::execWithoutCache($hookName, $params, $moduleId, false, true, false, null);
            $hookContent = preg_replace('/\$(\d)/', '\\\$$1', $hookContent);
            $pattern = "/<!--\[$key\]-->.*?<!--\[$key\]-->/s";
            $count = 0;
            $pageContent = preg_replace($pattern, $hookContent, $content, 1, $count);
            if (preg_last_error() === PREG_NO_ERROR && $count > 0) {
                $content = $pageContent;
            }
        }

        // inject new security tokens into page
        if (Configuration::get('PS_TOKEN_ENABLE')) {
            $newToken = Tools::getToken(false);
            if (preg_match("/static_token[ ]?=[ ]?'([a-f0-9]{32})'/", $content, $matches)) {
                if (count($matches) > 1 && $matches[1] != '') {
                    $oldToken = $matches[1];
                    $content = preg_replace("/$oldToken/", $newToken, $content);
                }
            } else {
                $content = preg_replace('/name="token" value="[a-f0-9]{32}/', 'name="token" value="'.$newToken, $content);
                $content = preg_replace('/token=[a-f0-9]{32}"/', 'token='.$newToken.'"', $content);
                $content = preg_replace('/static_token[ ]?=[ ]?\'[a-f0-9]{32}/', 'static_token = \''.$newToken, $content);
            }
        }

        return $content;
    }

    /**
     * This method will use parameter $description to instantiate hook parameter object
     *
     * @param $description hook parameter description
     *
     * @return mixed
     * @throws PrestaShopException
     *
     * @since 1.1.0
     */
    private function instantiateParam($description)
    {
        $type = $description['type'];
        switch ($type) {
            case static::CONSTANT:
                return $description['value'];
            case static::SMARTY_OBJECT:
                return Context::getContext()->smarty;
            case static::CONTEXT_OBJECT:
                return Context::getContext();
            case static::COOKIE_OBJECT:
                return Context::getContext()->cookie;
            case static::OBJECT_MODEL:
                $class = $description['class'];
                $id = $description['id'];
                return new $class($id, Context::getContext()->language->id) ;
            case static::PRODUCT_PROPERTIES:
                return Product::getProductProperties(Context::getContext()->language->id, $description['row']);
            default:
                throw new PrestaShopException("Can't instantiate parameter, unknown type: $type");
        }
    }

    /**
     * This method describes object. This description can be used to re-create
     * the object from scratch.
     *
     * Method:
     *   - literal values (string, numbers,... )are simply serialized
     *   - objects - unfortunately, we can safely recreate only some objects
     *     (Smarty, Cookie, Context) and all subclasses of ObjectModel.
     *
     *     instantiate it
     *   - array - at the moment only one type of array can be serialized, and
     *     it's an array describing product
     *
     * If $param is not one of the above, it can't be described, because we
     * don't know how to safely instantiate it. In that case, this return will
     * return null, and the whole cache entry will be invalid (not possible to
     * save it into cache).
     *
     * @param $param
     *
     * @return array | null
     *
     * @since 1.1.0
     */
    private function describeParam($param)
    {
        $type = gettype($param);
        switch ($type) {
            case 'string':
            case 'integer':
            case 'boolean':
            case 'double':
            case 'NULL':
                return [
                    'type' => static::CONSTANT,
                    'value' => $param
                ];
            case 'object':
                return $this->describeObject($param);
            case 'array':
                return $this->describeArray($param);
            default:
                return null;
        }
    }

    /**
     * This method will try to describe array parameter.
     *
     * At the moment, only array returned by Product::getProductProperties()
     * method can be described. For other arrays we have no clue how to safely
     * recreate them. Some arrays could be serialized as a constant, but
     * generally it's not possible.
     *
     * @param $param array
     *
     * @return array | null
     *
     * @since 1.1.0
     */
    private function describeArray($param)
    {
        if (array_key_exists('id_product', $param) && array_key_exists('category', $param) && array_key_exists('link', $param)) {
            // array created/enhanced by Product::getProductProperties method
            // we will remove all keys that are dynamically added by Product::getProductProperties, and keep the rest
            $props = array_filter($param, function($key) {
                return ! in_array($key, [
                    'allow_oosp',
                    'category',
                    'link',
                    'attribute_price',
                    'price_tax_exc',
                    'price',
                    'price_without_reduction',
                    'reduction',
                    'specific_prices',
                    'quantity',
                    'quantity_all_versions',
                    'features',
                    'attachments',
                    'virtual',
                    'pack',
                    'packItems',
                    'nopackprice',
                    'customization_required',
                    'rate',
                    'tax_name',
                ]);
            }, ARRAY_FILTER_USE_KEY);
            if (isset($props['id_image'])) {
                $idImage = $props['id_image'];
                if (strpos($idImage, '-')) {
                    $idImage = (int)explode('-', $idImage)[1];
                    if ($idImage) {
                        $props['id_image'] = $idImage;
                    } else {
                        unset($props['id_image']);
                    }
                }
            }

            return [
                'type' => static::PRODUCT_PROPERTIES,
                'row' => $props
            ];
        }

        return null;
    }

    /**
     * This method will try to describe object parameter.
     *
     * We can safely recreate these objects
     *   - Smarty
     *   - Cookie
     *   - Context
     *   - all subclasses of ObjectModelCore
     *
     * If hook receive any other kind of object, we don't know how to recreate
     * it from scratch.
     *
     * @param $param object
     *
     * @return array | null
     *
     * @since 1.1.0
     */
    private function describeObject($param)
    {
        $classname = get_class($param);
        if ($param instanceof ObjectModelCore) {
            return [
                'type' => static::OBJECT_MODEL,
                'class' => $classname,
                'id' => $param->id
            ];
        }

        switch ($classname) {
            case 'Smarty_Custom_Template':
            case 'SmartyCustom':
            case 'SmartyCustomCore':
                return [ 'type' => static::SMARTY_OBJECT ];
            case 'Cookie':
            case 'CookieCore':
                return [ 'type' => static::COOKIE_OBJECT ];
            case 'Context':
            case 'ContextCore':
                return [ 'type' => static::CONTEXT_OBJECT ];
            default:
                return null;
        }
    }
}
