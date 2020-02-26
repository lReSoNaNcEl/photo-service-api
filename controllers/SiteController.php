<?php


namespace app\controllers;
use app\models\Share;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\Url;
use yii\web\Controller;
use app\models\Login;
use app\models\PhotoUpload;
use app\models\User;
use app\models\Photo;
use yii\web\UploadedFile;

class Bearer extends HttpBearerAuth {
    public function handleFailure($response) {
        Yii::$app->response->setStatusCode(403);
        return Yii::$app->response->data = [
            'message' => 'You need authorization'
        ];
    }
}

class SiteController extends Controller {

    public function behaviors() {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => Bearer::className(),
            'except' => ['login', 'signup']
        ];

        return $behaviors;
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    //Регистрация
    public function actionSignup() {
        $model = new User();

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post(), '');

            if ($model->validate()) {
                Yii::$app->response->setStatusCode(201);
                $model->save();
                $user = $model::findOne(['phone' => $model->phone]);
                return ['id' => $user->id];
            }
            else {
                Yii::$app->response->setStatusCode(422);
                return $model->getErrors();
            }
        }
    }

    //Авторизация
    public function actionLogin() {
        $model = new Login();

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post(), '');
            $user = $model::findOne(['phone' => $model->phone]);

            if (!$model->validate()) {
                Yii::$app->response->setStatusCode(422);
                return $model->getErrors();
            }

            if (!empty($user && $user->password === $model->password)) {
                Yii::$app->response->setStatusCode(200);
                $token = substr(Yii::$app->getRequest()->getCsrfToken(), 1, 20);
                $user->token = $token;
                $user->save(false);

                return ['token' => $token];
            } else {
                Yii::$app->response->setStatusCode(404);
                return 'Incorrect login or password';
            }

        }
    }
    //Разлогин
    public function actionLogout() {
        if (Yii::$app->request->isPost) {
            $token = User::getToken();
            $user = User::findOne(['token' => $token]);
            $user->token = '';
            $user->save(false);
            Yii::$app->response->setStatusCode(200);
        }

    }

    //Поиск пользователей
    public function actionUser() {
        $model = new User();

        if (Yii::$app->request->isGet) {
            $search = explode(' ',Yii::$app->request->get('search'));
            $usersList = [];

            if (!empty($search[0]))
                $first_name = $search[0];
            else
                $first_name = '';
            if (!empty($search[1]))
                $surname = $search[1];
            else
                $surname = '';
            if (!empty($search[2]))
                $phone = $search[2];
            else $phone = '';

            if (!empty($first_name) && !empty($surname) && !empty($phone)) {
                $users = User::find()->filterWhere(['like', 'first_name', $first_name])
                    ->andFilterWhere(['like', 'surname', $surname])
                    ->andFilterWhere(['like', 'phone', $phone])
                    ->all();

                foreach ($users as $user) {
                    $userList[] = [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'surname' => $user->surname,
                        'phone' => $user->phone
                    ];
                }
                if (empty($userList))
                    Yii::$app->response->setStatusCode(404);

                return $userList;
            }
            else
                Yii::$app->response->setStatusCode(400, 'Mistake in request');

        }
    }

    public function actionPhoto() {
        $uploader = new PhotoUpload();
        $modelPhoto = new Photo();
        $user = User::findOne(['token' => User::getToken()]);

        //Загрузка картинки
        if (Yii::$app->request->isPost) {
            $uploader->photo = $_FILES['photo'];
            $photo = UploadedFile::getInstanceByName('photo');

            $name = "{$photo->getBaseName()}.{$photo->getExtension()}";
            $url = Url::home(true) . "images/${name}";

            $photo->saveAs(Yii::getAlias('@app/api/'). 'images/' . $name);

            $modelPhoto->name = $name;
            $modelPhoto->url = $url;
            $modelPhoto->owner_id = $user->id;

            if ($modelPhoto->validate()) {
                $modelPhoto->save();
                $newPhoto = $modelPhoto::findOne(['name' => $name]);
                return [
                    'id' => $newPhoto->id,
                    'name' => $newPhoto->name,
                    'url' => $newPhoto->url
                ];
            }
            else {
                Yii::$app->response->setStatusCode(422);
                return $modelPhoto->getErrors();
            }
        }

        //Просмотр всех картинок
        if (Yii::$app->request->isGet) {
            return Photo::find()->all();;
        }

    }

    public function actionOnephoto($id) {
        $user = User::findOne(['token' => User::getToken()]);

        //Обновление картинки
        if (Yii::$app->request->isPatch) {
            $photo = Photo::findOne(['id' => $id]);
            if ($user->id === $photo->owner_id) {

                if (!empty($_POST['name'])) {
                    $namePhoto = $_POST['name'].'.png';
                    $url = Url::home(true)."images/{$namePhoto}";
                    $openFile = fopen(Photo::getPath().$namePhoto, 'wb');

                    //если base64 текста на изменение нет...
                    if (empty($_POST['photo'])) {
                        $lastNamePhoto = $photo->name;
                        function getBase64($fileName, $fileType) {
                            $img = fread(fopen($fileName, "r"), filesize($fileName));
                            return "data:image/{$fileType};base64," . base64_encode($img);
                        }
                        $photoBase64 = getBase64(Photo::getPath().$lastNamePhoto, 'png');

                        unlink(Photo::getPath().$lastNamePhoto);
                        $photoBase64 = base64_decode(explode(';base64,', $photoBase64)[1]);
                        fwrite($openFile, $photoBase64);
                        fclose($openFile);
                    }
                    else {
                        unlink(Photo::getPath().$photo->name);
                        $photoBase64 = base64_decode(explode(';base64,', $_POST['photo'])[1]);
                        fwrite($openFile, $photoBase64);
                        fclose($openFile);
                    }

                    $photo->name = $namePhoto;
                    $photo->url = $url;
                    $photo->save();

                    return [
                        'id' => $photo->id,
                        'name' => $namePhoto,
                        'url' => $url
                    ];
                }
            }
            else
                Yii::$app->response->setStatusCode(403, 'Forbidden'); //когда пытаемся изменить не свою фотографию
        }
        else
            Yii::$app->response->setStatusCode(422, 'Unprocessable entity'); //траблы в валидацией _method: patch

        //Просмотр картинки по id
        if (Yii::$app->request->isGet) {
            $photo = Photo::findOne(['id' => $id]);
            if ($photo !== null)
                return $photo;
            else
                Yii::$app->response->setStatusCode(404);
        }

        //Удаление картинки
        if (Yii::$app->request->isDelete) {
            $photo = Photo::findOne(['id' => $id]);
            if ($photo !== null) {
                if ($photo->owner_id === $user->id) {  //если id авторизованного пользователя совпадает с id владельца фотографии
                    $photo->delete();
                    unlink(Photo::getPath().$photo->name);
                    Yii::$app->response->setStatusCode(204);
                }
                else
                    Yii::$app->response->setStatusCode(403);
            } else
                Yii::$app->response->setStatusCode(404);
        }

    }

    //шаринг фотографий другим пользователям
    public function actionShare($id) {
        //получаем массив id для шаринга
        $photoForSharing = json_decode($_POST['photos']);
        //экземляр авторизованного пользователя
        $user = User::findOne(['token' => User::getToken()]);
        //экземляр пользователя, которому будет отправлен шаринг
        $sharingUser = User::findOne(['id' => $id]);

        if ($sharingUser !== null) {
            //массив картинок, владелец которых - текущий авторизованный пользователь
            $usersPhoto = [];
            //id картинок, которые уже были расшарены
            $existing_photos = [];
            //массив с объектами расшаренных картинок
            $photos = [];

            //проверяем права текущего юзера, сравнивая его id с id владельца картинки
            foreach (Photo::find()->all() as $photo) {
                if ($photo->owner_id === $user->id)
                    $usersPhoto[] = $photo->id;
            }

            //определяем, какие фото были ранее расшарены
            foreach ($usersPhoto as $id) {
                $photo = Photo::findOne(['id' => $id]);
                $sharedPhoto = Share::findOne([
                    'user_id' => $sharingUser->id,
                    'photo_id' => $photo->id
                ]);

                //если в отправленном POST запросом массиве содержатся id картинок, которые имеет право изменять текущий юзер...
                if (in_array($id, $photoForSharing)) {
                    if ($sharedPhoto === null) {
                        Yii::$app->response->setStatusCode(201);
                        $share = new Share();
                        $share->photo_id = $photo->id;
                        $share->user_id = $sharingUser->id;
                        $share->save(false);
                    }
                    else {
                        if ($sharedPhoto->photo_id === $photo->id && $sharedPhoto->user_id === $sharingUser->id)
                            $photos[] = $sharedPhoto;
                    }
                }
            }

            foreach ($photos as $photo) {$existing_photos[] = $photo->photo_id;}
            return ['existing_photos' => $existing_photos];
        }
    }

    //тестовый экшен для получения текста base64
    public function actionTest() {
        function getBase64($filename, $filetype) {
                $imgbinary = fread(fopen($filename, "r"), filesize($filename));
                return 'data:image/' . $filetype . ';base64,' . base64_encode($imgbinary);
        }
        return getBase64(Yii::getAlias('@app/api/') . 'images/image.png', 'png');
    }

    public function beforeAction($action) {
        Yii::$app->response->format = \Yii\web\Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

}
