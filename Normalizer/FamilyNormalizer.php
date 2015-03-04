<?php

namespace Actualys\Bundle\DrupalCommerceConnectorBundle\Normalizer;

use Pim\Bundle\CatalogBundle\Entity\Family;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Pim\Bundle\CatalogBundle\Entity\Repository\AssociationTypeRepository;

class FamilyNormalizer implements NormalizerInterface
{
    /**
     * @var AttributeNormalizer
     */
    protected $attributeNormalizer;

    /**
     * @param AttributeNormalizer $attributeNormalizer The entity manager
     */
    public function __construct(
      AttributeNormalizer $attributeNormalizer,
      AssociationTypeRepository $associationTypeRepository
)
    {
        $this->attributeNormalizer = $attributeNormalizer;
        $this->associationTypeRepository = $associationTypeRepository;
    }

    /**
     * @param  object                                                $family
     * @param  null                                                  $format
     * @param  array                                                 $context
     * @return array|\Symfony\Component\Serializer\Normalizer\scalar
     * @throws Exception\NormalizeException
     */
    public function normalize($family, $format = null, array $context = [])
    {
        /**@var Family $family * */
        $normalizedFamily = [
          'code'             => $family->getCode(),
          'labels'           => [],
          'attribute_groups' => [],
        ];
        foreach ($family->getTranslations() as $trans) {
            $normalizedFamily['labels'][$trans->getLocale()] = $trans->getLabel(
            );
        }

        $associations = $this->associationTypeRepository->findAll();
        $normalizedFamily['associations'] = array();
        foreach($associations as $association) {
            $normalizedFamily['associations'][$association->getCode()] = array();
            foreach ($association->getTranslations() as $assocTrans) {
                $normalizedFamily['associations'][$association->getCode()]['labels'][$assocTrans->getLocale(
                )] = $assocTrans->getLabel();
            }
        }

        foreach ($family->getAttributes() as $attr) {
            $normalizedFamily['attribute_groups'][$attr->getGroup()->getCode(
            )]['code'] = $attr->getGroup()->getCode();
            foreach ($attr->getGroup()->getTranslations() as $groupTrans) {
                $normalizedFamily['attribute_groups'][$attr->getGroup()
                  ->getCode()]['labels'][$groupTrans->getLocale(
                )] = $groupTrans->getLabel();
            }
            $normalizedFamily['attribute_groups'][$attr->getGroup()->getCode(
            )]['attributes'][$attr->getCode(
            )] = $this->attributeNormalizer->normalize($attr, null, $context);
        }

        return $normalizedFamily;
    }

    /**
     * Checks whether the given class is supported for normalization by this normalizer
     *
     * @param mixed  $data   Data to normalize.
     * @param string $format The format being (de-)serialized from or into.
     *
     * @return boolean
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Family;
    }
}
