<?php

namespace Pim\Bundle\EnrichBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Register mass edit operations
 *
 * @author    Gildas Quemener <gildas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class RegisterMassEditOperationsPass implements CompilerPassInterface
{
    /** @staticvar */
    const OPERATION_REGISTRY = 'pim_enrich.mass_edit_action.operation.registry';

    /** @staticvar */
    const OPERATION_TAG = 'pim_enrich.mass_edit_action';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $registry = $container->getDefinition(self::OPERATION_REGISTRY);
        $operations = $container->findTaggedServiceIds(self::OPERATION_TAG);

        foreach ($operations as $operationsId => $operation) {
            $config = $operation[0];

            $alias    = $config['alias'];
            $acl      = isset($config['acl']) ? $config['acl'] : null;
            $gridName = isset($config['datagrid']) ? $config['datagrid'] : null;

            $registry->addMethodCall('register', [
                new Reference($operationsId),
                $alias,
                $acl,
                $gridName
            ]);
        }
    }
}
