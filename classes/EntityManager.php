<?php
/**
 * 2007-2017 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2017 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

/**
 * Class EntityManagerCore
 *
 * @since 1.1.0
 */
class EntityManagerCore
{
    protected $db;

    protected $entityMetaData = array();

    /**
     * EntityManagerCore constructor.
     */
    public function __construct()
    {
        $this->db = Db::getInstance();
    }

    /**
     * Return current database object used
     *
     * @return Db
     */
    public function getDatabase()
    {
        return Db::getInstance();
    }

    /**
     * Return current repository used
     *
     * @param $className
     *
     * @return mixed
     */
    public function getRepository($className)
    {
        if (is_callable(array($className, 'getRepositoryClassName'))) {
            $repositoryClass = call_user_func(array($className, 'getRepositoryClassName'));
        } else {
            $repositoryClass = null;
        }

        if (!$repositoryClass) {
            $repositoryClass = 'EntityRepository';
        }

        $repository = new $repositoryClass(
            $this,
            _DB_PREFIX_,
            $this->getEntityMetaData($className)
        );

        return $repository;
    }

    /**
     * Return entity's meta data
     *
     * @param string $className
     *
     * @return mixed
     * @throws PrestaShopException
     *
     * @since 1.1.0
     */
    public function getEntityMetaData($className)
    {
        if (!array_key_exists($className, $this->entityMetaData)) {
            $metaDataRetriever = new EntityMetaDataRetriever();
            $this->entityMetaData[$className] = $metaDataRetriever->getEntityMetaData($className);
        }

        return $this->entityMetaData[$className];
    }

    /**
     * Flush entity to DB
     * @param ObjectModel $entity
     * @return $this
     */
    public function save(ObjectModel $entity)
    {
        $entity->save();

        return $this;
    }

    /**
     * DElete entity from DB
     * @param ObjectModel $entity
     * @return $this
     */
    public function delete(ObjectModel $entity)
    {
        $entity->delete();

        return $this;
    }
}
