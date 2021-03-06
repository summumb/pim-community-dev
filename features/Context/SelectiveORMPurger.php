<?php

namespace Context;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Purger\PurgerInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\CommitOrderCalculator;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * ORM Purger to purge tables selectively
 *
 * @author    Filips Alpe <filips@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class SelectiveORMPurger extends ORMPurger implements PurgerInterface
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var array Tables to be excluded from the purge
     */
    protected $excludedTables;

    /**
     * @var int
     */
    protected $purgeMode = self::PURGE_MODE_DELETE;

    /**
     * Construct new purger instance.
     *
     * @param EntityManager $em
     * @param array         $excludedTables
     */
    public function __construct(EntityManager $em = null, array $excludedTables = array())
    {
        $this->em             = $em;
        $this->excludedTables = $excludedTables;
    }

    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        $classes = array();
        $metadatas = $this->em->getMetadataFactory()->getAllMetadata();

        foreach ($metadatas as $metadata) {
            if (! $metadata->isMappedSuperclass) {
                $classes[] = $metadata;
            }
        }

        $commitOrder = $this->getCommitOrder($this->em, $classes);

        // Get platform parameters
        $platform = $this->em->getConnection()->getDatabasePlatform();

        // Drop association tables first
        $orderedTables = $this->getAssociationTables($commitOrder, $platform);

        // Get platform parameters
        $platform = $this->em->getConnection()->getDatabasePlatform();

        // Drop tables in reverse commit order
        for ($i = count($commitOrder) - 1; $i >= 0; --$i) {
            $class = $commitOrder[$i];

            if (($class->isInheritanceTypeSingleTable() && $class->name != $class->rootEntityName)
                || $class->isMappedSuperclass) {
                continue;
            }

            $orderedTables[] = $class->getQuotedTableName($platform);
        }

        $orderedTables = array_diff($orderedTables, $this->excludedTables);

        foreach ($orderedTables as $tbl) {
            if ($this->purgeMode === self::PURGE_MODE_DELETE) {
                $this->em->getConnection()->executeUpdate("DELETE FROM " . $tbl);
            } else {
                $this->em->getConnection()->executeUpdate($platform->getTruncateTableSQL($tbl, true));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommitOrder(EntityManagerInterface $em, array $classes)
    {
        $calc = new CommitOrderCalculator();

        foreach ($classes as $class) {
            $calc->addClass($class);

            // $class before its parents
            foreach ($class->parentClasses as $parentClass) {
                $parentClass = $em->getClassMetadata($parentClass);

                if (!$calc->hasClass($parentClass->name)) {
                    $calc->addClass($parentClass);
                }

                $calc->addDependency($class, $parentClass);
            }

            foreach ($class->associationMappings as $assoc) {
                if ($assoc['isOwningSide']) {
                    $targetClass = $em->getClassMetadata($assoc['targetEntity']);

                    if (!$calc->hasClass($targetClass->name)) {
                        $calc->addClass($targetClass);
                    }

                    // add dependency ($targetClass before $class)
                    $calc->addDependency($targetClass, $class);

                    // parents of $targetClass before $class, too
                    foreach ($targetClass->parentClasses as $parentClass) {
                        $parentClass = $em->getClassMetadata($parentClass);

                        if (!$calc->hasClass($parentClass->name)) {
                            $calc->addClass($parentClass);
                        }

                        $calc->addDependency($parentClass, $class);
                    }
                }
            }
        }

        return $calc->getCommitOrder();
    }

    /**
     * {@inheritdoc}
     */
    protected function getAssociationTables(array $classes, AbstractPlatform $platform)
    {
        $associationTables = array();

        foreach ($classes as $class) {
            foreach ($class->associationMappings as $assoc) {
                if ($assoc['isOwningSide'] && $assoc['type'] == ClassMetadata::MANY_TO_MANY) {
                    $associationTables[] = $assoc['joinTable']['name'];
                }
            }
        }

        return $associationTables;
    }
}
