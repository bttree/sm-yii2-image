<?php
namespace bttree\smyimage\components;

use bttree\smyimage\components\Image;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Yii;
use yii\base\Component;
use yii\helpers\FileHelper;
use yii\web\HttpException;

class Thumbnailer extends Component
{
    /**
     * @var string - Путь к корневой директории для загрузки
     */
    public $uploadPath = '';

    /**
     * @var string - Url к корневой папке загрузок
     */
    public $uploadUrl = '';

    /**
     * @var null|string Полный путь к изображению по умолчанию
     */
    public $defaultImage = '@bttree/smyimage/assets/img/default.png';

    public $applyWatermarkImage = false;

    public $watermarkImageOptions = [
        'imageFile' => '',
    ];

    public $minWidthForWatermark = 300;
    public $minHeightForWatermark = 300;

    public $maxWidth = 2000;
    public $maxHeight = 2000;

    public function thumbnail(
        $file,
        $width,
        $height,
        $crop = false,
        $stretch = false,
        $options = ['jpeg_quality' => 100, 'png_compression_level' => 9, 'animated' => true],
        $destinationPath = false
    ) {
        $isRealImage = (bool)$file;
        $file = $file ?: Yii::getAlias($this->defaultImage);

        $mode = $crop ? ImageInterface::THUMBNAIL_OUTBOUND : ImageInterface::THUMBNAIL_INSET;

        $newBaseName = $this->generateBaseName($file);
        $name = dirname($newBaseName) . '/' . $width . 'x' . $height . '_' . $mode . '_' . ($stretch ? 'stretch' : 'normal') . ($this->applyWatermarkImage ? '_wm' : '') . '_' . pathinfo($newBaseName, PATHINFO_BASENAME);
        if($destinationPath)
        {
            $thumbFile = $destinationPath;
        }
        else
        {
            $thumbFile = $this->getFilePath($name);
        }

        $dir = dirname($thumbFile);

        if (!FileHelper::createDirectory($dir)) {
            throw new HttpException(500, sprintf('Не удалось создать папку «%s»', $dir));
        }

        if (!file_exists($thumbFile) && file_exists($file)) {
            $image = Image::getImagine()->open($file);
            $size = $image->getSize();
            $originalWidth = $size->getWidth();
            $originalHeight = $size->getHeight();

            if ($originalHeight > $this->maxHeight) {
                $originalHeight = $this->maxHeight;
            }
            if ($originalWidth > $this->maxWidth) {
                $originalWidth = $this->maxWidth;
            }

            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
            if ($height) {
                if ($originalHeight > $height || $stretch) {
                    $newHeight = $height;
                }
            }
            if ($width) {
                if ($originalWidth > $width || $stretch) {
                    $newWidth = $width;
                }
            }

            $newSize = new Box($newWidth, $newHeight);

            $filter = Image::getImagine() instanceof \Imagine\Gd\Imagine
                ? ImageInterface::FILTER_UNDEFINED
                : ImageInterface::FILTER_LANCZOS;

            $newImage = $image->thumbnail($newSize, $mode, $filter);

            if ($isRealImage && $newWidth >= $this->minWidthForWatermark && $newHeight >= $this->minHeightForWatermark) {
                $newImage = $this->applyWatermark($newImage);
            }
            $newImage->save($thumbFile, $options);
        }

        $url = rtrim(Yii::getAlias($this->uploadUrl), '\\/') . '/' . ltrim($name, '\\/');

        return $url;
    }

    private function generateBaseName($file)
    {
        $uid = md5($file);

        return substr($uid, 0, 2) . '/' . substr($uid, 2, 2) . '/' . substr($uid, 4) . '.' . pathinfo($file, PATHINFO_EXTENSION);
    }

    public function getFilePath($name)
    {
        return \Yii::getAlias($this->uploadPath) . DIRECTORY_SEPARATOR . ltrim($name, '\\/');
    }

    public function applyWatermark(ImageInterface $image)
    {
        $imagine = Image::getImagine();

        $width = $image->getSize()->getWidth();
        $height = $image->getSize()->getHeight();

        if ($this->applyWatermarkImage) {
            $logo = $imagine
                ->open(Yii::getAlias($this->watermarkImageOptions['imageFile']))
                ->resize(new Box(200, 80));

            $x = $width - $logo->getSize()->getWidth() - 20;
            $y = $height - $logo->getSize()->getHeight() - 20;
            if ($x < 0) {
                $x = 0;
            }
            if ($y < 0) {
                $y = 0;
            }
            $image->paste($logo, new Point($x, $y));
        }

        return $image;
    }

    public function removeThumbs($file)
    {
        $filename = $this->generateBaseName($file);

        $iterator = new \GlobIterator(\Yii::getAlias($this->uploadPath) . '/' . dirname($filename) . '/' . '*_' . pathinfo($filename, PATHINFO_BASENAME));

        foreach ($iterator as $file) {
            @unlink($file->getRealPath());
        }
    }
}
