<?php

namespace app\models;

use Yii;

class Login extends \yii\db\ActiveRecord {

    public static function tableName()
    {
        return 'user';
    }

    public function rules()
    {
        return [
            [['phone', 'password'], 'required'],
            [['phone'], 'string', 'max' => 11],
            [['password'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'first_name' => 'First Name',
            'surname' => 'Surname',
            'phone' => 'Phone',
            'password' => 'Password',
            'token' => 'Token',
        ];
    }

}
