<?php
namespace app\models;

use yii\base\Model;
use yii\db\ActiveRecord;


class PhotoUpload extends Model {

    public $photo;

    public function rules()
    {
        return [
            [['photo'], 'image', 'extensions' => 'png, jpg, jpeg'],

        ];
    }
}