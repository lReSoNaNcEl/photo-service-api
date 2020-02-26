<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "share".
 *
 * @property int $id
 * @property int $photo_id
 * @property int $user_id
 */
class Share extends \yii\db\ActiveRecord {

    public static function tableName()
    {
        return 'share';
    }

    public function rules()
    {
        return [
            [['photo_id', 'user_id'], 'required'],
            [['photo_id', 'user_id'], 'integer']
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'photo_id' => 'Photo ID',
            'user_id' => 'User ID',
        ];
    }

}
