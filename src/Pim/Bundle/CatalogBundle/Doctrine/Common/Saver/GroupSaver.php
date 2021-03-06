<?php

namespace Pim\Bundle\CatalogBundle\Doctrine\Common\Saver;

use Akeneo\Component\StorageUtils\Saver\BulkSaverInterface;
use Akeneo\Component\StorageUtils\Saver\SaverInterface;
use Akeneo\Component\StorageUtils\Saver\SavingOptionsResolverInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Pim\Bundle\CatalogBundle\Manager\ProductTemplateApplierInterface;
use Pim\Bundle\CatalogBundle\Manager\ProductTemplateMediaManager;
use Pim\Bundle\CatalogBundle\Model\GroupInterface;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Pim\Bundle\VersioningBundle\Manager\VersionContext;

/**
 * Group saver, contains custom logic for variant group products saving
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class GroupSaver implements SaverInterface
{
    /** @var ObjectManager */
    protected $objectManager;

    /** @var BulkSaverInterface */
    protected $productSaver;

    /** @var ProductTemplateMediaManager */
    protected $templateMediaManager;

    /** @var ProductTemplateApplierInterface */
    protected $productTplApplier;

    /** @var VersionContext */
    protected $versionContext;

    /** @var SavingOptionsResolverInterface */
    protected $optionsResolver;

    /** @var string */
    protected $productClassName;

    /**
     * @param ObjectManager                   $objectManager
     * @param BulkSaverInterface              $productSaver
     * @param ProductTemplateMediaManager     $templateMediaManager
     * @param ProductTemplateApplierInterface $productTplApplier
     * @param VersionContext                  $versionContext
     * @param SavingOptionsResolverInterface  $optionsResolver
     * @param string                          $productClassName
     */
    public function __construct(
        ObjectManager $objectManager,
        BulkSaverInterface $productSaver,
        ProductTemplateMediaManager $templateMediaManager,
        ProductTemplateApplierInterface $productTplApplier,
        VersionContext $versionContext,
        SavingOptionsResolverInterface $optionsResolver,
        $productClassName
    ) {
        $this->objectManager          = $objectManager;
        $this->productSaver           = $productSaver;
        $this->templateMediaManager   = $templateMediaManager;
        $this->productTplApplier      = $productTplApplier;
        $this->versionContext         = $versionContext;
        $this->optionsResolver        = $optionsResolver;
        $this->productClassName       = $productClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function save($group, array $options = [])
    {
        /* @var GroupInterface */
        if (!$group instanceof GroupInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expects a "Pim\Bundle\CatalogBundle\Model\GroupInterface", "%s" provided.',
                    ClassUtils::getClass($group)
                )
            );
        }

        $options = $this->optionsResolver->resolveSaveOptions($options);

        $this->versionContext->addContextInfo(
            sprintf('Comes from variant group %s', $group->getCode()),
            $this->productClassName
        );
        $this->objectManager->persist($group);
        if (true === $options['flush']) {
            $this->objectManager->flush();
        }

        if (count($options['add_products']) > 0) {
            $this->addProducts($options['add_products']);
        }

        if (count($options['remove_products']) > 0) {
            $this->removeProducts($options['remove_products']);
        }

        if ($group->getType()->isVariant()) {
            $template = $group->getProductTemplate();
            if (null !== $template) {
                $this->templateMediaManager->handleProductTemplateMedia($template);
            }
        }

        if ($group->getType()->isVariant() && true === $options['copy_values_to_products']) {
            $this->copyVariantGroupValues($group);
        }
    }

    /**
     * @param ProductInterface[] $products
     */
    protected function addProducts(array $products)
    {
        $this->productSaver->saveAll($products, ['recalculate' => false, 'schedule' => false]);
    }

    /**
     * @param ProductInterface[] $products
     */
    protected function removeProducts(array $products)
    {
        $this->productSaver->saveAll($products, ['recalculate' => false, 'schedule' => false]);
    }

    /**
     * Copy the variant group values on any products belonging in the variant group
     *
     * @param GroupInterface $group
     */
    protected function copyVariantGroupValues(GroupInterface $group)
    {
        $template = $group->getProductTemplate();
        $products = $group->getProducts()->toArray();
        $this->productTplApplier->apply($template, $products);
    }
}
