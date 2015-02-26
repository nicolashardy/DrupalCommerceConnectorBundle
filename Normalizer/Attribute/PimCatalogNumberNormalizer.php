<?php

namespace Actualys\Bundle\DrupalCommerceConnectorBundle\Normalizer\Attribute;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Pim\Bundle\CatalogBundle\Entity\Attribute;

/**
 * Class PimCatalogNumber
 *
 * @package Actualys\Bundle\DrupalCommerceConnectorBundle\Normalizer
 */
class PimCatalogNumberNormalizer implements NormalizerInterface
{
    /**
     * @param  object                                                $attribute
     * @param  null                                                  $format
     * @param  array                                                 $context
     * @return array|\Symfony\Component\Serializer\Normalizer\scalar
     */
    public function normalize($attribute, $format = null, array $context = [])
    {
        /**@var Attribute $attribute * */
        $normalizedAttribute       = [
          'code'       => null,
          'type'       => null,
          'required'   => null,
          'labels'     => null,
          'parameters' => null,
        ];
        $availableLocales          = [];
        $attributeAvailableLocales = $attribute->getAvailableLocales();
        if (!is_null($attributeAvailableLocales)) {
            foreach ($attribute->getAvailableLocales() as $availableLocale) {
                $availableLocales [] = $availableLocale;
            }
        }

        $normalizedAttribute['required']   = $attribute->isRequired();
        $normalizedAttribute['type']       = $attribute->getAttributeType();
        $normalizedAttribute['code']       = $attribute->getCode();
        $normalizedAttribute['parameters'] = [
          'scope'             => $attribute->isScopable(),
          'localizable'       => $attribute->isLocalizable(),
          'unique_value'      => $attribute->isUnique(),
          'available_locales' => $availableLocales,
          'default_value'     => $attribute->getDefaultValue(),
          'negative_allowed'  => $attribute->isNegativeAllowed(),
          'decimale_allowed'  => $attribute->isDecimalsAllowed(),
          'number_min'        => (bool) $attribute->getNumberMin(),
          'number_max'        => (bool) $attribute->getNumberMax(),
        ];

        if ($attribute->getTranslations()) {
            foreach ($attribute->getTranslations() as $trans) {
                $normalizedAttribute['labels'][$trans->getLocale(
                )] = $trans->getLabel();
            }
        } else {
            $normalizedAttribute['labels'][LANGUAGE_NONE] = $attribute->getLabel(
            );
        }

        return $normalizedAttribute;
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
        return $data instanceof Attribute;
    }
}
