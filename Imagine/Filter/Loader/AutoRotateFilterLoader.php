<?php

namespace Liip\ImagineBundle\Imagine\Filter\Loader;

use Imagine\Image\ImageInterface;

/**
 * AutoRotateFilterLoader - rotates an Image based on its EXIF Data.
 *
 * @author Robert Schönthal <robert.schoenthal@gmail.com>
 */
class AutoRotateFilterLoader implements LoaderInterface
{
    protected $orientationKeys = [
        'exif.Orientation',
        'ifd0.Orientation',
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ImageInterface $image, array $options = [])
    {
        if ($orientation = $this->getOrientation($image)) {
            // Rotates if necessary.
            $degree = $this->calculateRotation($orientation);
            if ($degree !== 0) {
                $image->rotate($degree);
            }

            // Flips if necessary.
            if ($this->isFlipped($orientation)) {
                $image->flipHorizontally();
            }
        }

        return $image;
    }

    /**
     * calculates to rotation degree from the EXIF Orientation.
     *
     * @param int $orientation
     *
     * @return int
     */
    private function calculateRotation($orientation)
    {
        switch ($orientation) {
            case 1:
            case 2:
                return 0;
            case 3:
            case 4:
                return 180;
            case 5:
            case 6:
                return 90;
            case 7:
            case 8:
                return -90;
            default:
                throw new Exception('Unhandled orientation');
        }
    }

    /**
     * @param ImageInterface $image
     *
     * @return int
     */
    private function getOrientation(ImageInterface $image)
    {
        //>0.6 imagine meta data interface
        if (method_exists($image, 'metadata')) {
            foreach ($this->orientationKeys as $orientationKey) {
                $orientation = $image->metadata()->offsetGet($orientationKey);

                if ($orientation) {
                    $image->metadata()->offsetSet($orientationKey, '1');

                    return intval($orientation);
                }
            }
        } else {
            $data = exif_read_data('data://image/jpeg;base64,'.base64_encode($image->get('jpg')));

            return isset($data['Orientation']) ? $data['Orientation'] : null;
        }

        return;
    }

    /**
     * Returns true if the image is flipped, false otherwise.
     *
     * @param int $orientation
     *
     * @return bool
     */
    private function isFlipped($orientation)
    {
        switch ($orientation) {
            case 1:
            case 3:
            case 6:
            case 8:
                return false;

            case 2:
            case 4:
            case 5:
            case 7:
                return true;

            default:
                throw new Exception('Unhandled orientation');
        }
    }
}
