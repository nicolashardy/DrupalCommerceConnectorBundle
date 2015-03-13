<?php

namespace Actualys\Bundle\DrupalCommerceConnectorBundle\Reader;
use Pim\Bundle\BaseConnectorBundle\Reader\ORM\EntityReader;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\AbstractConfigurableStepElement;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Pim\Bundle\BaseConnectorBundle\Reader\ProductReaderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Pim\Bundle\TransformBundle\Converter\MetricConverter;
use Pim\Bundle\BaseConnectorBundle\Validator\Constraints\Channel as ChannelConstraint;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Manager\CompletenessManager;
use Pim\Bundle\CatalogBundle\Repository\ProductRepositoryInterface;

/**
 * Reads products one by one
 *
 * @author    Olivier Soulet <olivier.soulet@akeneo.com>
 * @copyright 2014 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductsReader extends EntityReader
{
    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        if (!$this->query) {
            $this->query = $this->em
                ->getRepository($this->className)
                ->createQueryBuilder('p')
                ->where('p.validationDate IS NOT NULL')
                ->addOrderBy('p.validationDate')
                ->getQuery();
        }

        return $this->query;
    }
}

