<?php

namespace bttree\smyimage\widgets;

use bttree\smyupload\FileUploadBehavior;
use kartik\file\FileInput;
use yii\helpers\Html;
use yii\widgets\InputWidget;

class ImageInput extends InputWidget
{
    public $previewImage = null;

    public function run()
    {
        echo Html::hiddenInput((new FileUploadBehavior())->deleteFileParam . '[]', null, ['id' => 'delete-file-' . $this->id]);
        echo FileInput::widget([
            'id' => $this->id,
            'model' => $this->model,
            'attribute' => $this->attribute,
            'options' => [
                'accept' => 'image/*',
                'multiple' => false,
                'data-file-field' => true,
                'data-delete-flag' => '#delete-file-' . $this->id,
                'data-delete-flag-value' => $this->model->{$this->attribute},
            ],
            'pluginOptions' => [
                'showUpload' => false,
                'initialPreview' => $this->previewImage !== null ? $this->previewImage : ($this->model->{$this->attribute} ? Html::img($this->model->getImageUrl(null, 160)) : false),
            ],
        ]);

        $this->registerJs();
    }

    public function registerJs()
    {
        $js = <<<'JS'
$('[data-file-field]').on('fileclear', function(){
    var $this = $(this);
    $($this.data('delete-flag')).val($this.data('delete-flag-value'));
})
JS;
        $this->getView()->registerJs($js);
    }
}
