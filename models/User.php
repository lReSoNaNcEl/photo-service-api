<?php

namespace app\models;

use Yii;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property string $first_name
 * @property string $surname
 * @property string $phone
 * @property string $password
 * @property string $token
 */
class User extends \yii\db\ActiveRecord implements IdentityInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['first_name', 'surname', 'phone', 'password'], 'required'],
            [['first_name', 'surname'], 'string', 'max' => 80],
            [['phone'], 'string', 'max' => 11],
            [['phone'], 'unique'],
            [['token'], 'string', 'max' => 20],
            [['password'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
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

    public static function getToken() {
        return substr(Yii::$app->request->headers->get('Authorization'), 7);
    }

    public static function findIdentity($id)
    {

    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return User::findOne(['token' => $token]);
    }

    public function getId()
    {
    }

    public function getAuthKey()
    {

    }

    public function validateAuthKey($authKey)
    {

    }
}
