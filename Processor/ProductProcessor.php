<?php

namespace Actualys\Bundle\DrupalCommerceConnectorBundle\Processor;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Akeneo\Bundle\BatchBundle\Item\ItemProcessorInterface;
use Pim\Bundle\CatalogBundle\Manager\ProductManager;
use Pim\Bundle\CatalogBundle\Manager\ChannelManager;
use Pim\Bundle\CatalogBundle\Entity\Channel;
use Pim\Bundle\CatalogBundle\Model\ProductInterface;
use Actualys\Bundle\DrupalCommerceConnectorBundle\Normalizer\Exception\NormalizeException;
use Actualys\Bundle\DrupalCommerceConnectorBundle\Normalizer\ProductNormalizer;
use Actualys\Bundle\DrupalCommerceConnectorBundle\Item\DrupalItemStep;

class ProductProcessor extends DrupalItemStep implements ItemProcessorInterface
{
    /** @var StepExecution */
    protected $stepExecution;

    /** @var ProductManager */
    protected $productManager;

    /** @var  ChannelManager */
    protected $channelManager;

    /** @var  array */
    protected $globalContext;

    /** @var  ProductNormalizer */
    protected $productNormalizer;

    /** @var  Channel $channel */
    protected $channel;

    protected $mergeImages;

    /**
     * @param ProductManager    $productManager
     * @param ChannelManager    $channelManager
     * @param ProductNormalizer $productNormalizer
     */
    public function __construct(
      ProductManager $productManager,
      ChannelManager $channelManager,
      ProductNormalizer $productNormalizer,
      $status
    ) {
        $this->productManager    = $productManager;
        $this->channelManager    = $channelManager;
        $this->productNormalizer = $productNormalizer;
        $this->status            = $status;
    }

    /**
     * @param  mixed                $product
     * @return array|mixed
     * @throws InvalidItemException
     */
    public function process($product)
    {
        /** @var Channel $channel */
        $channel = $this->channelManager->getChannelByCode($this->channel);

        if($this->status == "delete") {
            return $this->normalizeProduct($product, $channel, true);
        }

        return $this->normalizeProduct($product, $channel);
    }

    /**
     * Normalize the given product
     *
     * @param ProductInterface $product [description]
     * @param Channel          $channel
     * @param boolean          $isDeleted
     *
     * @throws InvalidItemException If a normalization error occurs
     *
     * @return array processed item
     */
    protected function normalizeProduct(
      $product,
      Channel $channel,
      $isDeleted = false
    ) {

        if(get_class($product) === "Chronopost\Bundle\CatalogBundle\Entity\DeleteHistory") {
            $context = $product->getContext();
            $sku = $context['sku'];
            $family = $context['family_code'];

            return array(
                'sku'               => $sku,
                'family'            => $family,
                'history_id'        => $product->getId(),
                'values'            => array(
                                        'is_deleted' => true
                                       )
            );
        }

        try {
            $processedItem = $this->productNormalizer->normalize(
              $product,
              null,
              [
                'channel'       => $channel,
                'configuration' => $this->getConfiguration(),
                'is_deleted' => $isDeleted
              ]
            );
        } catch (NormalizeException $e) {
            throw new InvalidItemException(
              $e->getMessage(),
              [
                'id'                                                 => $product->getId(
                ),
                $product->getIdentifier()->getAttribute()->getCode(
                )                                                    => $product->getIdentifier(
                )->getData(),
                'label'                                              => $product->getLabel(
                ),
                'family'                                             => $product->getFamily(
                )->getCode()
              ]
            );
        }

        return $processedItem;
    }

    /**
     * Get fields for the twig
     *
     * @return array
     */
    public function getConfigurationFields()
    {
        return array_merge(
          parent::getConfigurationFields(),
          [
            'mergeImages' => [
              'type'    => 'checkbox',
              'options' => [
                'help'  => 'actualys_drupal_commerce_connector.export.mergeImages.help',
                'label' => 'actualys_drupal_commerce_connector.export.mergeImages.label',
              ],
            ],
            'channel'     => [
              'type'    => 'choice',
              'options' => [
                'choices'  => $this->channelManager->getChannelChoices(),
                'required' => true,
                'help'     => 'actualys_drupal_commerce_connector.export.channel.help',
                'label'    => 'actualys_drupal_commerce_connector.export.channel.label',
              ],
            ],
          ]
        );
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
     * @return Channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param Channel $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }
}
