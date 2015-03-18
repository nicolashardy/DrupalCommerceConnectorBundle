<?php

namespace Actualys\Bundle\DrupalCommerceConnectorBundle\Writer;

use Actualys\Bundle\DrupalCommerceConnectorBundle\Item\DrupalItemStep;
use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;
use Akeneo\Bundle\BatchBundle\Event\InvalidItemEvent;
use Akeneo\Bundle\BatchBundle\Job\ExitStatus;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Akeneo\Bundle\BatchBundle\Event\EventInterface;

/**
 * Class ProductWriter
 *
 * @package Actualys\Bundle\DrupalCommerceConnectorBundle\Writer
 */
class ProductWriter extends DrupalItemStep implements ItemWriterInterface
{

    public function __construct(EventDispatcher $eventDispatcher, $productRepository, $entityManager, $historyRepository)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->productandler   = $eventDispatcher;
        $this->productRepository = $productRepository;
        $this->entityManager = $entityManager;
        $this->historyRepository = $historyRepository;
    }

    protected $mergeImages;

    /**x
     *
     * @return array
     */
    public function getConfigurationFields()
    {
        return parent::getConfigurationFields();
    }

    /**
     * @param array $items
     */
    public function write(array $items)
    {
        foreach ($items as $item) {
            try {
              $this->webservice->sendProduct($item);
              $this->setProductExportedToDrupal($item);
            } catch (\Exception $e) {
                $event = new InvalidItemEvent(
                  __CLASS__,
                  $e->getMessage(),
                  array(),
                  ['sku' => array_key_exists('sku', $item) ? $item['sku'] : 'NULL']
                );
                // Logging file
                $this->eventDispatcher->dispatch(
                  EventInterface::INVALID_ITEM,
                  $event
                );


                // Loggin Interface
                $this->stepExecution->addWarning(
                  __CLASS__,
                  $e->getMessage(),
                  array(),
                  ['sku' => array_key_exists('sku', $item) ? $item['sku'] : 'NULL']
                );

                /** @var ClientErrorResponseException  $e */
                if ($e->getResponse()->getStatusCode() <= 404) {
                    $e = new \Exception($e->getResponse()->getReasonPhrase());
                    $this->stepExecution->addFailureException($e);
                    $exitStatus = new ExitStatus(ExitStatus::FAILED);
                    $this->stepExecution->setExitStatus($exitStatus);
                }
                // Handle next element.
            }
            $this->stepExecution->incrementSummaryInfo('write');
        }
    }

    /**
     * @return boolean
     */
    public function getMergeImages()
    {
        return $this->mergeImages;
    }

    /**
     * @param boolean $mergeImages
     */
    public function setMergeImages($mergeImages)
    {
        $this->mergeImages = $mergeImages;
    }

    /**
     * @param array $item
     */
    public function setProductExportedToDrupal($item)
    {
        if(isset($item['akeneo_product_id']) && intval($item['akeneo_product_id'])) {
            $product = $this->productRepository->findOneById($item['akeneo_product_id']);
            $product->setExportedDrupalAt(new \DateTime('now'));
            $this->entityManager->persist($product);
            $this->entityManager->flush();

        } elseif(isset($item['history_id']) && intval($item['history_id'])) {
            $historyDeleted = $this->historyRepository->findOneById($item['history_id']);
            $historyDeleted->setDrupalExported(new \DateTime('now'));
            $this->entityManager->persist($historyDeleted);
            $this->entityManager->flush();
        }
    }
}
