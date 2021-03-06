<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Parameter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * BasketElementRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BasketElementRepository extends EntityRepository
{
    public function findUserElement($element_id, User $user)
    {
        $dql = 'SELECT e
            FROM Phraseanet:BasketElement e
            JOIN e.basket b
            LEFT JOIN e.validation_datas vd
            LEFT JOIN b.validation s
            LEFT JOIN s.participants p
            WHERE (b.user = :usr_id OR p.user = :same_usr_id)
              AND e.id = :element_id';

        $params = [
            'usr_id'      => $user->getId(),
            'same_usr_id' => $user->getId(),
            'element_id'  => $element_id
        ];

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        $element = $query->getOneOrNullResult();

        /* @var $element BasketElement */
        if (null === $element) {
            throw new NotFoundHttpException('Element is not found');
        }

        return $element;
    }

    /**
     * @param \record_adapter $record
     * @return BasketElement[]
     */
    public function findElementsByRecord(\record_adapter $record)
    {
        $dql = 'SELECT e
            FROM Phraseanet:BasketElement e
            JOIN e.basket b
            LEFT JOIN b.validation s
            LEFT JOIN s.participants p
            WHERE e.record_id = :record_id
            AND e.sbas_id = :sbas_id';

        $params = [
            'sbas_id'   => $record->getDataboxId(),
            'record_id' => $record->getRecordId()
        ];

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        return $query->getResult();
    }

    public function findElementsByDatabox(\databox $databox)
    {
        $dql = 'SELECT e
            FROM Phraseanet:BasketElement e
            JOIN e.basket b
            LEFT JOIN b.validation s
            LEFT JOIN s.participants p
            WHERE e.sbas_id = :sbas_id';

        $params = [
            'sbas_id'   => $databox->get_sbas_id(),
        ];

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        return $query->getResult();
    }

    /**
     *
     * @param  \record_adapter                              $record
     * @param  User                                         $user
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findReceivedElementsByRecord(\record_adapter $record, User $user)
    {
        $dql = 'SELECT e
            FROM Phraseanet:BasketElement e
            JOIN e.basket b
            LEFT JOIN b.validation s
            LEFT JOIN s.participants p
            WHERE b.user = :usr_id
            AND b.pusher IS NOT NULL
            AND e.record_id = :record_id
            AND e.sbas_id = :sbas_id';

        $params = [
            'sbas_id'   => $record->getDataboxId(),
            'record_id' => $record->getRecordId(),
            'usr_id'    => $user->getId()
        ];

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        return $query->getResult();
    }

    public function findReceivedValidationElementsByRecord(\record_adapter $record, User $user)
    {
        $dql = 'SELECT e
            FROM Phraseanet:BasketElement e
            JOIN e.basket b
            JOIN b.validation v
            JOIN v.participants p
            WHERE p.user = :usr_id
            AND e.record_id = :record_id
            AND e.sbas_id = :sbas_id';

        $params = [
            'sbas_id'   => $record->getDataboxId(),
            'record_id' => $record->getRecordId(),
            'usr_id'    => $user->getId()
        ];

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        return $query->getResult();
    }

    /**
     * @param array $records Each record is an array which MUST have a databox_id AND record_id key
     * @param null|int   $basketId
     * @return \Alchemy\Phrasea\Model\Entities\BasketElement[]
     */
    public function findByRecords(array $records, $basketId = null)
    {
        $perDataboxLookup = [];
        foreach ($records as $record) {
            if (!isset($record['databox_id']) || !isset($record['record_id'])) {
                throw new \LogicException('Each record should have a databox_id AND record_id key');
            }

            $databoxId = $record['databox_id'];
            $recordId = $record['record_id'];

            if (!isset($perDataboxLookup[$databoxId])) {
                $perDataboxLookup[$databoxId] = [];
            }
            $perDataboxLookup[$databoxId][] = $recordId;
        }

        if (empty($perDataboxLookup)) {
            return [];
        }

        $builder = $this->createQueryBuilder('e');

        $parameters = new ArrayCollection();

        if ($basketId) {
            $builder->where('e.basket_id = :basket_id');
            $parameters->add(new Parameter('basket_id', $basketId));
        }

        $parameterGroup = 1;
        $expr = $builder->expr()->orX();
        foreach ($perDataboxLookup as $databoxId => $recordsIds) {
            $databoxIdParameter = sprintf('databoxId%d', $parameterGroup);
            $recordIdsParameter = sprintf('recordIds%d', $parameterGroup);

            $expr->add($builder->expr()->andX(
                sprintf('e.sbas_id = :%s', $databoxIdParameter),
                sprintf('e.record_id IN (:%s)', $recordIdsParameter)
            ));

            $parameters->add(new Parameter($databoxIdParameter, $databoxId, \PDO::PARAM_INT));
            $parameters->add(new Parameter($recordIdsParameter, $recordsIds, Connection::PARAM_INT_ARRAY));

            ++$parameterGroup;
        }

        $builder->andWhere($expr);
        $builder->setParameters($parameters);

        return $builder->getQuery()->getResult();
    }
}
