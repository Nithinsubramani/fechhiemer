<?php

namespace I95Dev\ConfigurableImage\Block\Product\View\Type;

use Zend\Log\Logger;
use Zend\Log\Writer\Stream;

/**
 * Class Configurable
 * I95Dev\ConfigurableImage\Block\Product\View\Type
 */
class Configurable extends \Magento\Swatches\Block\Product\Renderer\Configurable
{
    /**
     * @return array
     */
    protected function getOptionImages()
    {
        $images = [];
        $configurableImages = $this->helper->getGalleryImages($this->getProduct()) ?: [];
        foreach ($this->getAllowProducts() as $product) {

            $productImages = $this->helper->getGalleryImages($product) ?: [];
            $imagePosition = [];
            $maxPosition = 0;

            foreach ($productImages as $image) {
                $images[$product->getId()][] =
                    [
                        'thumb' => $image->getData('small_image_url'),
                        'img' => $image->getData('medium_image_url'),
                        'full' => $image->getData('large_image_url'),
                        'caption' => $image->getLabel(),
                        'position' => $image->getPosition(),
                        'isMain' => $image->getFile() == $product->getImage(),
                        'type' => str_replace('external-', '', $image->getMediaType()),
                        'videoUrl' => $image->getVideoUrl(),
                    ];
                $imagePosition[] = $image->getPosition();
            }

            if (!empty($imagePosition) && count($imagePosition) > 0) {
                $maxPosition = max($imagePosition);
            } else {
                $maxPosition = 0;
            }

            foreach ($configurableImages as $image) {
                if (!($this->getProduct()->getImage() == $image->getFile())) {

                    $images[$product->getId()][] =
                        [
                            'thumb' => $image->getData('small_image_url'),
                            'img' => $image->getData('medium_image_url'),
                            'full' => $image->getData('large_image_url'),
                            'caption' => ($image->getLabel() ?: $this->getProduct()->getName()),
                            'position' => $maxPosition + $image->getData('position'),
                            'isMain' => false,
                            'type' => str_replace('external-', '', $image->getMediaType()),
                            'videoUrl' => $image->getVideoUrl(),
                        ];

                }
            }
        }
        return $images;
    }
}
