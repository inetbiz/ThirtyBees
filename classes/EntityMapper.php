<?php
/**
 * 2007-2017 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 * @author    Thirty Bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2017 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class EntityMapperCore
 *
 * @since 1.1.0
 */
class EntityMapperCore
{
    /**
     * Load ObjectModel
     *
     * @param int         $id
     * @param int         $idLang
     * @param ObjectModel $entity
     * @param array       $entityDefs
     * @param int         $idShop
     * @param bool        $shouldCacheObjects
     *
     * @throws \PrestaShopDatabaseException
     */
    public function load($id, $idLang, $entity, $entityDefs, $idShop, $shouldCacheObjects)
    {
        // Load object from database if object id is present
        $cacheId = 'objectmodel_'.$entityDefs['classname'].'_'.(int) $id.'_'.(int) $idShop.'_'.(int) $idLang;
        if (!$shouldCacheObjects || !\Cache::isStored($cacheId)) {
            $sql = new \DbQuery();
            $sql->from($entityDefs['table'], 'a');
            $sql->where('a.`'.bqSQL($entityDefs['primary']).'` = '.(int) $id);
            // Get lang informations
            if ($idLang && isset($entityDefs['multilang']) && $entityDefs['multilang']) {
                $sql->leftJoin($entityDefs['table'].'_lang', 'b', 'a.`'.bqSQL($entityDefs['primary']).'` = b.`'.bqSQL($entityDefs['primary']).'` AND b.`id_lang` = '.(int) $idLang);
                if ($idShop && !empty($entityDefs['multilang_shop'])) {
                    $sql->where('b.`id_shop` = '.(int) $idShop);
                }
            }
            // Get shop informations
            if (\ShopCore::isTableAssociated($entityDefs['table'])) {
                $sql->leftJoin($entityDefs['table'].'_shop', 'c', 'a.`'.bqSQL($entityDefs['primary']).'` = c.`'.bqSQL($entityDefs['primary']).'` AND c.`id_shop` = '.(int) $idShop);
            }
            if ($objectDatas = \Db::getInstance()->getRow($sql)) {
                if (!$idLang && isset($entityDefs['multilang']) && $entityDefs['multilang']) {
                    $sql = 'SELECT *
							FROM `'.bqSQL(_DB_PREFIX_.$entityDefs['table']).'_lang`
							WHERE `'.bqSQL($entityDefs['primary']).'` = '.(int) $id.(($idShop && $entity->isLangMultishop()) ? ' AND `id_shop` = '.(int) $idShop : '');
                    if ($objectDatasLang = \Db::getInstance()->executeS($sql)) {
                        foreach ($objectDatasLang as $row) {
                            foreach ($row as $key => $value) {
                                if ($key != $entityDefs['primary'] && array_key_exists($key, $entity)) {
                                    if (!isset($objectDatas[$key]) || !is_array($objectDatas[$key])) {
                                        $objectDatas[$key] = array();
                                    }
                                    $objectDatas[$key][$row['id_lang']] = $value;
                                }
                            }
                        }
                    }
                }
                $entity->id = (int) $id;
                foreach ($objectDatas as $key => $value) {
                    if (array_key_exists($key, $entity)) {
                        $entity->{$key} = $value;
                    } else {
                        unset($objectDatas[$key]);
                    }
                }
                if ($shouldCacheObjects) {
                    \Cache::store($cacheId, $objectDatas);
                }
            }
        } else {
            $objectDatas = \Cache::retrieve($cacheId);
            if ($objectDatas) {
                $entity->id = (int) $id;
                foreach ($objectDatas as $key => $value) {
                    $entity->{$key} = $value;
                }
            }
        }
    }
}
