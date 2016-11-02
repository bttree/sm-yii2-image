<?php
namespace bttree\smyimage\behaviors;

use Yii;
use yii\base\Behavior;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;

class GalleryUploadBehavior extends Behavior
{
    public $attribute = 'images';

    /**
     * @var string Класс моделей для галереии
     */
    public $modelClass;

    public $modelFileNameAttribute = 'name';

    public $files;

    /**
     * @var callable
     */
    public $afterNewImageCreate;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (!$this->modelClass) {
            throw new Exception('Необходимо указать modelClass - класс моделей для галереи');
        }

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
        ];
    }

    public function beforeValidate($event)
    {
        $this->files = UploadedFile::getInstances($this->owner, $this->attribute);
    }

    public function afterSave($event)
    {
        foreach ((array)$this->files as $index => $file) {
            $model = new $this->modelClass;
            $model->setUploadedFile($file);

            if (is_callable($this->afterNewImageCreate)) {
                call_user_func($this->afterNewImageCreate, $model, $index);
            }

            $this->owner->link($this->attribute, $model);
        }
        $model = new $this->modelClass;
        $images = $this->owner->{$this->attribute};

        $model::loadMultiple($images, \Yii::$app->request->post());
        foreach ($images as $i => $image) {

            $file = UploadedFile::getInstance($image, "[$i]" . $this->modelFileNameAttribute);
            if ($file) {
                $image->setUploadedFile($file);
            }

            if (!$image->save()) {

            };
        }
    }
}
