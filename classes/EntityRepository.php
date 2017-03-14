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
 * Class EntityRepository
 *
 * @since 1.1.0
 */
class EntityRepositoryCore
{
    protected $entityManager;
    protected $db;
    protected $tablesPrefix;
    protected $entityMetaData;
    protected $queryBuilder;

    /**
     * EntityRepository constructor.
     *
     * @param EntityManager  $entityManager
     * @param                $tablesPrefix
     * @param EntityMetaData $entityMetaData
     *
     * @since 1.1.0
     */
    public function __construct(
        EntityManager $entityManager,
        $tablesPrefix,
        EntityMetaData $entityMetaData
    ) {
        $this->entityManager = $entityManager;
        $this->db = $this->entityManager->getDatabase();
        $this->tablesPrefix = $tablesPrefix;
        $this->entityMetaData = $entityMetaData;
        $this->queryBuilder = new QueryBuilder($this->db);
    }

    /**
     * @param string $method
     * @param array  $arguments
     *
     * @return array|mixed|null
     * @throws PrestaShopException
     *
     * @since 1.1.0
     */
    public function __call($method, $arguments)
    {
        if (0 === strpos($method, 'findOneBy')) {
            $one = true;
            $by = substr($method, 9);
        } elseif (0 === strpos($method, 'findBy')) {
            $one = false;
            $by = substr($method, 6);
        } else {
            throw new PrestaShopException(sprintf('Undefind method %s.', $method));
        }

        if (count($arguments) !== 1) {
            throw new PrestaShopException(sprintf('Method %s takes exactly one argument.', $method));
        }

        if (!$by) {
            $where = $arguments[0];
        } else {
            $where = array();
            $by = $this->convertToDbFieldName($by);
            $where[$by] = $arguments[0];
        }

        return $this->doFind($one, $where);
    }

    /**
     * Convert a camelCase field name to a snakeCase one
     * e.g.: findAllByIdCMS => id_cms
     *
     * @param $camelCaseFieldName
     *
     * @return string
     *
     * @since 1.1.0
     */
    private function convertToDbFieldName($camelCaseFieldName)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $camelCaseFieldName));
    }

    /**
     * Constructs and performs 'SELECT' in DB
     *
     * @param       $one
     * @param array $cumulativeConditions
     *
     * @return array|mixed|null
     * @throws Exception
     *
     * @since 1.1.0
     */
    private function doFind($one, array $cumulativeConditions)
    {
        $whereClause = $this->queryBuilder->buildWhereConditions('AND', $cumulativeConditions);

        $sql = 'SELECT * FROM '.$this->getTableNameWithPrefix().' WHERE '.$whereClause;

        $rows = $this->db->select($sql);

        if ($one) {
            return $this->hydrateOne($rows);
        } else {
            return $this->hydrateMany($rows);
        }
    }

    /**
     * Returns escaped+prefixed current table name
     *
     * @return mixed
     *
     * @since 1.1.0
     */
    protected function getTableNameWithPrefix()
    {
        return $this->db->escape($this->tablesPrefix.$this->entityMetaData->getTableName());
    }

    /**
     * This function takes an array of database rows as input
     * and returns an hydrated entity if there is one row only.
     *
     * Null is returned when there are no rows, and an exception is thrown
     * if there are too many rows.
     *
     * @param array $rows Database rows
     *
     * @return mixed|null
     * @throws PrestaShopException
     *
     * @since 1.1.0
     */
    protected function hydrateOne(array $rows)
    {
        if (count($rows) === 0) {
            return null;
        } elseif (count($rows) > 1) {
            throw new PrestaShopException('Too many rows returned.');
        } else {
            $data = $rows[0];
            $entity = $this->getNewEntity();
            $entity->hydrate($data);

            return $entity;
        }
    }

    /**
     * Return a new empty Entity depending on current Repository selected
     *
     * @return mixed
     *
     * @since 1.1.0
     */
    public function getNewEntity()
    {
        $entityClassName = $this->entityMetaData->getEntityClassName();

        return new $entityClassName;
    }

    /**
     * @param array $rows
     *
     * @return array
     *
     * @since 1.1.0
     */
    protected function hydrateMany(array $rows)
    {
        $entities = array();
        foreach ($rows as $row) {
            $entity = $this->getNewEntity();
            $entity->hydrate($row);
            $entities[] = $entity;
        }

        return $entities;
    }

    /**
     * Find one entity in DB
     *
     * @param int $id
     *
     * @return array|mixed|null
     * @throws PrestaShopException
     *
     * @since 1.1.0
     */
    public function findOne($id)
    {
        $conditions = array();
        $conditions[$this->getIdFieldName()] = $id;

        return $this->doFind(true, $conditions);
    }

    /**
     * Return ID field name
     *
     * @return mixed
     * @throws PrestaShopException
     *
     * @since 1.1.0
     */
    protected function getIdFieldName()
    {
        $primary = $this->entityMetaData->getPrimaryKeyFieldnames();

        if (count($primary) === 0) {
            throw new PrestaShopException(
                sprintf(
                    'No primary key defined in entity `%s`.',
                    $this->entityMetaData->getEntityClassName()
                )
            );
        } elseif (count($primary) > 1) {
            throw new PrestaShopException(
                sprintf(
                    'Entity `%s` has a composite primary key, which is not supported by entity repositories.',
                    $this->entityMetaData->getEntityClassName()
                )
            );
        }

        return $primary[0];
    }

    /**
     * Find all entities in DB
     *
     * @return array
     *
     * @since 1.1.0
     */
    public function findAll()
    {
        $sql = 'SELECT * FROM '.$this->getTableNameWithPrefix();

        return $this->hydrateMany($this->db->select($sql));
    }

    /**
     * Returns escaped DB table prefix
     *
     * @return mixed
     *
     * @since 1.1.0
     */
    protected function getPrefix()
    {
        return $this->db->escape($this->tablesPrefix);
    }
}
