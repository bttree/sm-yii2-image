<?php
namespace bttree\smyimage\behaviors;

use bttree\smyimage\components\Image;
use amylabs\upload\FileUploadBehavior;
use Imagine\Image\ImageInterface;
use Yii;

/**
 * Class ImageUploadBehavior
 * @package bttree\smyimage\behaviors
 */
class ImageUploadBehavior extends FileUploadBehavior
{
    /**
     * @var bool
     */
    public $resizeOnUpload = false;
    /**
     * @var array
     */
    public $resizeOptions = [];

    /**
     * @var Thumbnailer
     */
    protected $thumbnailer;
    /**
     *
     */
    public function init()
    {
        parent::init();

        $this->thumbnailer = Yii::$app->thumbnailer;

        if ($this->resizeOnUpload) {
            $this->resizeOptions = array_merge(
                [
                    'width' => 950,
                    'height' => 950,
                    'quality' => ['jpeg_quality' => 100, 'png_compression_level' => 9],
                ],
                $this->resizeOptions
            );
        }
    }

    /**
     *
     */
    protected function saveFile()
    {
        parent::saveFile();

        $imagine = Image::getImagine();
        $image = $imagine->open($this->getFilePath());

        if ($this->resizeOnUpload) {
            $this->resize(
                $image,
                $this->resizeOptions['width'],
                $this->resizeOptions['height']
            );

            $image->save(
                $this->getFilePath(),
                $this->resizeOptions['quality']
            );
        }
    }

    /**
     * @param ImageInterface $image
     * @param $width
     * @param $height
     */
    protected function resize(ImageInterface $image, $width, $height)
    {
        $realWidth = $image->getSize()->getWidth();
        $realHeight = $image->getSize()->getHeight();

        if ($realWidth > $width || $realHeight > $height) {
            $ratio = $realWidth / $realHeight;

            if ($ratio > 1) {
                $height = $width / $ratio;
            } else {
                $width = $height * $ratio;
            }

            $image->resize(new \Imagine\Image\Box($width, $height));
        }
    }

    /**
     * @param int $width
     * @param int $height
     * @param bool|false $crop
     * @param bool|false $stretch
     * @return mixed
     */
    public function getImageUrl($width = 0, $height = 0, $crop = false, $stretch = false)
    {
        $file = null;

        if ($this->owner->{$this->attribute}) {
            $file = $this->getFilePath();
        }

        return $this->thumbnailer->thumbnail(
            $file,
            $width,
            $height,
            $crop,
            $stretch
        );
    }

    /**
     *
     */
    public function removeFile()
    {
        $this->thumbnailer->removeThumbs($this->uploadManager->getFilePath($this->currentFile));
        parent::removeFile();
    }
}
