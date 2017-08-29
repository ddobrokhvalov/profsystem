<?php
/**
 * Модуль "БЛОГИ"
 *
 * Класс АВТОРИЗАЦИЯ БЛОГОВ
 * БЕЗ ИНТЕГРАЦИИ С КЛИЕНТОМ
 *
 * @author     Sergey A.Utkin / sautkin@informproduct.ru
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2008 RBC SOFT
 */

class blogs_auth {

/**
 * окончание URL-блога
 */
public $blog_site_postfix;

/**
 * название куки для id (без интеграции - blog_id, с интеграцией - client_id)
 */
protected $cookie_name_id = 'blog_id';
/**
 * название куки для random_number (без интеграции - blog_key, с интеграцией - client_key)
 */
protected $cookie_name_key = 'blog_key';

/**
 * BLOG_ID текущего аутентифицированного пользователя, извлекается из "кук"
 */
public $blog_id;
/**
 * Персональный ключ текущего аутентифицированного пользователя, извлекается из "кук"
 */
protected $blog_key;

/**
 * признак авторизованности клиента
 */
public $passed_client;
/**
 * признак авторизованности блога
 */
public $passed_blog;

/**
 * массив полей таблицы BLOG, которые инициализируются из сторонней таблицы (например, CLIENT)
 * они становятся не редактируемыми на формах редактирования профиля Блога
 */
public $fields_from_client = array();



/**
 * инициализация переменных класса
 */
public function blogs_auth(){
    $site = db::sql_select( 'select HOST from SITE order by SITE_ID' );
    $this->blog_site_postfix = ( count($site)>0 ) ? $site[0]['HOST'] : '';

    $this->blog_id = 0;
    $this->blog_key = '';

    $this->chechAuthorizationClient();
    $this->chechAuthorizationBlog();
}


/**
 * устанавливает "куки" на стороне аутентифицированного пользователя
 */
public function setCookie($blogID, $blogKey, $outComputer){
    $time = !$outComputer ? time() + 365 * 24 * 60 * 60 : 0;
    setcookie($this->cookie_name_id,  $blogID,  $time, '/', '.'.$this->blog_site_postfix);
    setcookie($this->cookie_name_key, $blogKey, $time, '/', '.'.$this->blog_site_postfix);
    $this->chechAuthorizationClient();
    $this->chechAuthorizationBlog();
}
/**
 * очищает "куки"
 */
public function delCookie(){
    setcookie($this->cookie_name_key, '', time()-3600, '/', '.'.$this->blog_site_postfix);
    setcookie($this->cookie_name_id,  '', time()-3600, '/', '.'.$this->blog_site_postfix);
    $this->chechAuthorizationClient();
    $this->chechAuthorizationBlog();
}
/**
 * проверяет на допустимость значения в клиентских "куках"
 * и пишет в переменные класса значения BLOG_ID и BLOG_KEY для работы с текущим Блогом
 */
protected function readCookie(){
    if ( !isset($_COOKIE[$this->cookie_name_key]) || !isset($_COOKIE[$this->cookie_name_id]) || intval($_COOKIE[$this->cookie_name_id])<=0 ){
        return false;
    }
    $this->blog_id = intval($_COOKIE[$this->cookie_name_id]);
    $this->blog_key = $_COOKIE[$this->cookie_name_key];
    return true;
}


/**
 * получение данных из Клиента (уже введённых при регистрации) для регистрации Блога
 * всегда array(), т.к. интеграции нет
 */
public function getRegistrationDataFromClient(){
    return array();
}


/**
 * кодирует информацию о пользователе
 */
public function getBlogKey($blogID, $blogEmail, $blogPasswordMD5){
    return md5($blogID.'|'.$blogEmail.'|'.$blogPasswordMD5);
}


/**
 * проверяет регистрацию пользователя модуля Клиент (если true, то 'куки' корректные)
 * переопределяем, чтобы всегда true давал, т.к. интеграции нет
 */
protected function chechAuthorizationClient(){
    $this->passed_client = true;
}
/**
 * проверяет регистрацию пользователя модуля Блог (если true, то 'куки' корректные)
 */
protected function chechAuthorizationBlog(){
    if ( !$this->readCookie() ){
        $this->passed_blog = false;
        return;
    }
    $blog = db::sql_select( 'select BLOG_ID,EMAIL,PASSWORD_MD5 from BLOG where BLOG_ID = :blog', array('blog'=>$this->blog_id) );
    $blogKey = '';
    if ( count($blog)>0 ){
        $blogKey = $this->getBlogKey($blog[0]['BLOG_ID'], $blog[0]['EMAIL'], $blog[0]['PASSWORD_MD5']);
    }
    $this->passed_blog = strlen($blogKey)>0 && $blogKey==$this->blog_key ? true : false;
}


/**
 * получение информации о произвольном пользователе
 */
public function getClientInfo($blogID, $blogInfo){
	return $blogInfo;
}


/**
 * добавление информации о новом пользователе
 * $fields = array('NAME_FIELD1'=>'VALUE_FIELD1',...,'NAME_FIELDN'=>'VALUE_FIELDN')
 */
public function setNewClientInfo($blogID, $fields){
    // пусто, т.к. интеграции нет
}
/**
 * сохранение информации о пользователе
 * $fields = array('NAME_FIELD1'=>'VALUE_FIELD1',...,'NAME_FIELDN'=>'VALUE_FIELDN')
 */
public function setClientInfo($blogID, $fields){
    // пусто, т.к. интеграции нет
}

}

// инициализация переменных класса
// $this->blogs_auth();

// устанавливает "куки" на стороне аутентифицированного пользователя
// $this->setCookie($blogID, $blogKey, $outComputer);

// очищает "куки"
// $this->delCookie();

// проверяет на допустимость значения в клиентских "куках"
// и пишет в переменные класса значения BLOG_ID и RANDOM_NUMBER для работы с текущим Блогом
// $this->readCookie();

// получение данных из Клиента (уже введённых при регистрации) для регистрации Блога
// $this->getRegistrationDateFromClient();

// кодирует информацию о пользователе
// $this->getBlogKey($blogID, $blogEmail, $blogPasswordMD5);

// проверяет регистрацию пользователя модуля Клиент (если true, то 'куки' корректные)
// $this->chechAuthorizationClient();

// проверяет регистрацию пользователя модуля Блог (если true, то 'куки' корректные)
// $this->chechAuthorizationBlog();

// получение информации о произвольном пользователе
// $this->getClientInfo($blogID, $blogInfo);

// добавление информации о новом пользователе
// $fields = array('NAME_FIELD1'=>'VALUE_FIELD1',...,'NAME_FIELDN'=>'VALUE_FIELDN')
// $this->setNewClientInfo($blogID, $fields);

// сохранение информации о пользователе
// $fields = array('NAME_FIELD1'=>'VALUE_FIELD1',...,'NAME_FIELDN'=>'VALUE_FIELDN')
// $this->setClientInfo($blogID, $fields);

?>