<?php
/**
 * Модуль "БЛОГИ"
 *
 * @author     Sergey A.Utkin / sautkin@informproduct.ru
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2008 RBC SOFT
 */

include_once(params::$params["common_data_server"]["value"].'module/blogs/blogs_auth.php');

/**
 * Каталог для хранения папок Блогов пользователей
 * Если не будет блогов как поддоменов (пример: nick.site.ru), а будут только как подпапки (пример: site.ru/blogs/nick, www.site.ru/blogs/nick),
 * то закомментировать строку define('BLOG_SITE_ROOT', ... );
 * или определить как пустую  define('BLOG_SITE_ROOT', '');
 *
 * По умолчанию, их нет, если будут, всё равно надо проверить папку для их хранения
 */
//define('BLOG_SITE_ROOT', $_SERVER['DOCUMENT_ROOT'] . '/virtual');


class m_blogs extends module
{

/**
 * Объект шаблонизатора модуля
 */
protected $tpl;

/**
 * Объект класса blogs_auth, обеспечивающий работу, связанную с авторизацией и получением данных из Коиента при интеграции
 */
protected $authorization;


/**
 * Формат даты отображения в блогах
 */
protected $date_format_in_blog = "d.m.Y H:i";

/**
 * Соответствие индесов и значений для поля "Количество записей на странице"
 */
protected $post_on_page_arr = array('1'=>20, '2'=>30, '3'=>40, '4'=>50);
//protected $post_on_page_arr = array('1'=>1, '2'=>2, '3'=>3, '4'=>4);

/**
 * Соответствие индесов и значений для поля "Разрешение на чтение"
 */
protected $access_arr = array(
    '1'=>'sysw_blog_readpermission_for_all',    //для всех
    '2'=>'sysw_blog_readpermission_for_friends',//для друзей
    '3'=>'sysw_blog_readpermission_repsonal',   //личное
    '4'=>'sysw_blog_readpermission_selectively' //выборочно
);

/**
 * Каталог для загрузки изображений пользователей
 */
protected $upload_dir_image = 'upload/blogs/userimage';


/**
 * Инициализация модуля
 */
protected function content_init() {
    // необходимо для работы каптчи
    session_start();

    // Инициализация шаблонизатора
    $this->tpl = new smarty_ee_module( $this );

    // Инициализация объкта авторизации
    $this->authorization = new blogs_auth();

    // Удаляются специальные символы из полей формы
    if ( $this->q_param ){
        $this->q_param = lib::array_htmlspecialchars( $this->q_param );
    }

    if ( !$this->authorization->passed_blog 
        && !in_array($this->view_param['view_mode'], array('registration','auth','blog','adv_blog','reminder')) )
    {
    	$this->view_param['view_mode'] = 'auth';
    }

    // Проверяем наличие шаблонов, объявляем основной шаблон и запускаем обработчика
    switch ( $this->view_param['view_mode'] ){
        case 'registration' :
            // Вариант использования "Форма регистрация"
            $template_file = 'registration.tpl';
            $this->modeRegistration();
            break;
        case 'registration_community' :
            // Вариант использования "Форма регистрация сообщества"
            $template_file = 'registrationCommunity.tpl';
            $this->modeRegistrationCommunity();
            break;
        case 'auth' :
            // Вариант использования "Форма аутентификации"
            $template_file = 'auth.tpl';
            $this->modeAuth();
            break;
        case 'reminder' :
            // Вариант использования "Напоминание забытого пароля"
            $template_file = 'reminder.tpl';
            $this->modeReminder();
            break;
        case 'blog' :
            // Вариант использования "Блог"
            switch ( $this->view_param['submode'] ){
                case 'profile' :
                    $template_file = 'blog_profile.tpl';
                    break;
                case 'photogallery' :
                    $template_file = 'blog_photogallery.tpl';
                    break;
                case 'blog' :
                case 'friendtape' :
                    $template_file = 'blog_blog.tpl';
                    break;
            }
            $this->modeBlog();
            break;
        case 'add_post' :
            // Вариант использования "Добавление записи"
            $template_file = 'add_post.tpl';
            $this->modeAddPost();
            break;
        case 'edit_profile' :
            // Вариант использования "Редактирование профиля"
            $template_file = 'edit_profile.tpl';
            $this->modeEditProfile();
            break;
        case 'community_manager' :
            // Вариант использования "Управление сообществами"
            $template_file = 'community_manager.tpl';
            $this->modeCommunityManager();
            break;
        case 'adv_blog' :
            // Вариант использования "Блог(расширенная форма)"
            $template_file = 'adv_blog.tpl';
            $this->modeAdvBlog();
            break;
    }

    $this->body = $this->tpl->fetch( $this->tpl_dir . $template_file );
}






/** 
 * Вариант использования "Управление сообществами" 
 */
protected function modeCommunityManager() {
    $user = $this->get_current_user_info();
    if ( !empty($user) ){
        // заголовок формы заполняем
        $this->tpl->assign('BLOG_USER', $user['TITLE']);
        $this->tpl->assign('BLOG_ID', $this->authorization->blog_id);

        if ( in_array($this->q_param['submode'], array('profile', 'images', 'party', 'tags')) ){
            if ( isset($this->q_param['community_id']) && $this->q_param['community_id']>0 ){
                $this->q_param['COMMUNITY_ID'] = $this->q_param['community_id'] = intval($this->q_param['community_id']);
            }else{
                return false;
            }
            $community = $this->get_user_info($this->q_param['COMMUNITY_ID']);
            if ( !empty($community) ){
                // заголовок формы заполняем
                $this->tpl->assign('TITLE', $community['TITLE']);
            }
            $this->tpl->assign('COMMUNITY_ID', $this->q_param['COMMUNITY_ID']);
            $this->tpl->assign('SUBMODE', $this->q_param['submode']);

            $sql_result_set = db::sql_select( '
                SELECT BLOG_ID FROM BLOG 
                WHERE IS_COMMUNITY = 1 AND BLOG_ID = :community AND BLOG_ID IN ( 
                    SELECT OWNER_ID FROM BLOG_FRIEND WHERE FRIEND_ID = :friend AND (IS_CREATOR = \'1\' OR IS_MODERATOR = \'1\') 
                )', 
               array('community'=>$this->q_param['COMMUNITY_ID'], 'friend'=>$this->authorization->blog_id) 
            );
            if ( !(count($sql_result_set)>0) ){
                Header( 'Location: '.$_SERVER['PHP_SELF'] );
                exit();
            }
        }

        switch ( $this->q_param['submode'] ){
            case 'profile' :
                $this->managerProfileCommunity();
                break;
            case 'images' :
                $this->managerImagesCommunity();
                break;
            case 'party' :
                $this->modeCommunityParty();
                break;
            case 'tags' :
                $this->managerTagsCommunity();
                break;
            default :
                $this->modeCommunityList();
        }
    }else{
        return false;
    }
}


/**
 * Вариант использования "Управление сообществами - Список сообществ пользователя"
 */
protected function modeCommunityList() {
    $this->tpl->assign('MODE_LIST', 1);
    if ( isset($this->q_param['action']) and $this->q_param['action']=='del' ){
        // удаление сообщества со всеми связями
        if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id && $this->q_param['COMMUNITY_ID']>0 ){
            // сначала удаляем папки, т.к. нужно имя блога/сообщества, потом чистим БД
            $this->removeBlogFolder($this->q_param['COMMUNITY_ID']);
            db::sql_query( '
                    DELETE FROM BLOG_COMMENT 
                    WHERE  BLOG_POST_ID IN ( SELECT BLOG_POST_ID FROM BLOG_POST WHERE BLOG_ID = :blog )', 
                    array('blog'=>$this->q_param['COMMUNITY_ID']) 
            );
            db::sql_query( '
                    DELETE FROM BLOG_FRIEND_SV_BLOG_FRIENDGROUP 
                    WHERE  BLOG_FRIENDGROUP_ID IN ( SELECT BLOG_FRIENDGROUP_ID FROM BLOG_FRIENDGROUP WHERE BLOG_ID = :blog )
                        OR  BLOG_FRIEND_ID IN ( SELECT BLOG_FRIEND_ID FROM BLOG_FRIEND WHERE OWNER_ID = :owner )',
                    array('blog'=>$this->q_param['COMMUNITY_ID'], 'owner'=>$this->q_param['COMMUNITY_ID']) 
            );
            db::sql_query( '
                    DELETE FROM BLOG_POST_SV_BLOG_FRIENDGROUP 
                    WHERE  BLOG_FRIENDGROUP_ID IN ( SELECT BLOG_FRIENDGROUP_ID FROM BLOG_FRIENDGROUP WHERE BLOG_ID = :blog )
                        OR  BLOG_POST_ID IN ( SELECT BLOG_POST_ID FROM BLOG_POST WHERE BLOG_ID = :blog_p )',
                    array('blog'=>$this->q_param['COMMUNITY_ID'], 'blog_p'=>$this->q_param['COMMUNITY_ID']) 
            );
            db::sql_query( '
                    DELETE FROM BLOG_POST_SV_BLOG_TAG 
                    WHERE  BLOG_TAG_ID IN ( SELECT BLOG_TAG_ID FROM BLOG_TAG WHERE BLOG_ID = :blog )
                        OR  BLOG_POST_ID IN ( SELECT BLOG_POST_ID FROM BLOG_POST WHERE BLOG_ID = :blog_p )',
                    array('blog'=>$this->q_param['COMMUNITY_ID'], 'blog_p'=>$this->q_param['COMMUNITY_ID']) 
            );
            db::delete_record( 'BLOG_FRIEND', array('OWNER_ID'=>$this->q_param['COMMUNITY_ID']) );
            db::delete_record( 'BLOG_FRIEND', array('FRIEND_ID'=>$this->q_param['COMMUNITY_ID']) );
            db::delete_record( 'BLOG_FRIENDGROUP', array('BLOG_ID'=>$this->q_param['COMMUNITY_ID']) );
            db::delete_record( 'BLOG_IMAGE', array('BLOG_ID'=>$this->q_param['COMMUNITY_ID']) );
            db::delete_record( 'BLOG_POST', array('BLOG_ID'=>$this->q_param['COMMUNITY_ID']) );
            db::delete_record( 'BLOG_SV_BLOG_FIELD', array('BLOG_ID'=>$this->q_param['COMMUNITY_ID']) );
            db::delete_record( 'BLOG_SV_BLOG_INTEREST', array('BLOG_ID'=>$this->q_param['COMMUNITY_ID']) );
            db::delete_record( 'BLOG_TAG', array('BLOG_ID'=>$this->q_param['COMMUNITY_ID']) );
            db::delete_record( 'BLOG', array('BLOG_ID'=>$this->q_param['COMMUNITY_ID']) );
        }
        Header('Location: '.$_SERVER['REQUEST_URI']);
        exit();
    }

    $sql_result_set = db::sql_select( '
        SELECT B.* 
        FROM BLOG B, BLOG_FRIEND BF
        WHERE B.IS_COMMUNITY = \'1\' AND B.BLOG_ID = BF.OWNER_ID AND BF.FRIEND_ID = :friend AND (BF.IS_CREATOR = \'1\' OR BF.IS_MODERATOR = \'1\')
        ORDER BY B.TITLE ASC',
        array('friend'=>$this->authorization->blog_id)
    );
    if ( count($sql_result_set)>0 ){
        $communities = array(); 
        while ( $current_row=current($sql_result_set) ){
            $current_row['BLOG_DATE'] = lib::unpack_date($current_row['BLOG_DATE'],'long');
            $current_row['PATH'] = $this->get_blog_path($current_row['TITLE']);
            $communities[] = $current_row;
            next($sql_result_set); 
        } 
        $this->tpl->assign('COMMUNITIES', $communities); 
    }
}


/**
 * Вариант использования "Управление сообществами - Участники"
 */
protected function modeCommunityParty() {
    $this->tpl->assign('MODE_PARTY', 1);
    // делаем доступными для шаблона ранее введённые значения
    if ( count($this->q_param)>0 ){
        foreach ( $this->q_param as $key => $value ){
            if ( $key=='ff_l' || $key=='ff_m' || $key=='ff_c' ){
                $this->tpl->assign($key.$value, 1);
            }else{
                $this->tpl->assign($key, $value);
            }
        }
    }
    if ( isset($this->q_param['action']) ){
        $is_error = 0;
        if ( $this->q_param['action'] == 'save' ){
            if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id && !empty($this->q_param['BLOG_FRIEND_ID']) && !empty($this->q_param['FRIEND_ID']) && !empty($this->q_param['COMMUNITY_ID']) ){
                if ( $this->q_param['BLOG_ID']==$this->q_param['FRIEND_ID'] ){
                    $this->tpl->assign('ERROR_4', 1); // свои данные нельзя менять
                    $is_error = 1;
                }else{
                    $BLOG_FRIEND_ID = intval($this->q_param['BLOG_FRIEND_ID']);
                    $CREATOR = intval($this->q_param['IS_CREATOR']);
                    $IS_CREATOR = ($CREATOR == $BLOG_FRIEND_ID) ? 1 : 0;
                    if ($IS_CREATOR){
                        db::update_record( 'BLOG_FRIEND', 
                            array( 'IS_CREATOR' => 0 ), '',
                            array( 'OWNER_ID' => $this->q_param['COMMUNITY_ID'] )
                        );
                    }
                    db::update_record( 'BLOG_FRIEND', 
                        array(
                            'LEVEL' => $this->q_param['LEVEL'.$BLOG_FRIEND_ID.'_'], 
                            'IS_CREATOR' => $IS_CREATOR,
                            'IS_MODERATOR' => (($this->q_param['IS_MODERATOR'.$BLOG_FRIEND_ID.'_']==1 || $IS_CREATOR) ? 1 : 0)
                        ), '',
                        array( 'BLOG_FRIEND_ID' => $this->q_param['BLOG_FRIEND_ID'] )
                    );
                    // надо отправить сообщение пользователю
                    $this->sendMsgWhenCommunityUser( $this->q_param['FRIEND_ID'], $this->q_param['COMMUNITY_ID'], 'change' );
                }
            }
        }
        if ( $this->q_param['action'] == 'del' ){
            if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id && !empty($this->q_param['BLOG_FRIEND_ID']) && !empty($this->q_param['FRIEND_ID']) && !empty($this->q_param['COMMUNITY_ID']) ) {
                if ( $this->q_param['BLOG_ID']==$this->q_param['FRIEND_ID'] ){
                    $this->tpl->assign('ERROR_4', 1); // удалить нельзя себя
                    $is_error = 1;
                }else{
                    $sql_result_set = db::sql_select( 'SELECT FRIEND_ID FROM BLOG_FRIEND WHERE BLOG_FRIEND_ID = :friend', array('friend'=>$this->q_param['BLOG_FRIEND_ID']) );
                    db::delete_record( 'BLOG_FRIEND', array('BLOG_FRIEND_ID'=>$this->q_param['BLOG_FRIEND_ID']) );
                    if ( count($sql_result_set)>0 ){
                        db::delete_record( 'BLOG_FRIEND', array('OWNER_ID'=>$sql_result_set[0]['FRIEND_ID'], 'FRIEND_ID'=>$this->q_param['COMMUNITY_ID']) );
                    }
                    // надо отправить сообщение пользователю, о том что его удалили
                    $this->sendMsgWhenCommunityUser( $this->q_param['FRIEND_ID'], $this->q_param['COMMUNITY_ID'], 'del' );
                }
            }
        }
        if ( $this->q_param['action'] == 'add' ){
            if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id && (trim($this->q_param['NICK']) != '') && !empty($this->q_param['COMMUNITY_ID']) ) {
                $this->tpl->assign('NICK', trim($this->q_param['NICK']));
                $user = $this->get_user_info_use_title($this->q_param['NICK']);
                if ( empty($user) ){
                    $this->tpl->assign('ERROR_1', 1); // пользователя с указанным ником не существует
                    $is_error = 1;
                }elseif ( $user['IS_COMMUNITY']==1 ){
                    $this->tpl->assign('ERROR_1', 1); // пользователя с указанным ником не существует (есть только сообщество)
                    $is_error = 1;
                }else{
                    $new_friend_id = $user['BLOG_ID'];
                    $sql_friend_set = db::sql_select( 'SELECT BLOG_FRIEND_ID,IS_INVITE FROM BLOG_FRIEND WHERE FRIEND_ID = :friend AND OWNER_ID = :owner', array('friend'=>$new_friend_id, 'owner'=>$this->q_param['COMMUNITY_ID']) );
                    if ( count($sql_friend_set)>0 ){
                        if ( $sql_friend_set[0]['IS_INVITE']!=1 ){
                            $this->tpl->assign('ERROR_2', 1); // пользователь с указанным ником уже состоит в сообществе
                        }else{
                            $this->tpl->assign('ERROR_3', 1); // пользователь с указанным ником уже приглашен в данное сообщество
                        }
                        $is_error = 1;
                    }else{
                        //всё ОК - добавляем пользователя $new_friend_id в друзья сообществу с флагом "приглашение"
                        $community_set = $this->get_user_info($this->q_param['COMMUNITY_ID']);
                        db::insert_record( 'BLOG_FRIEND', 
                            array( 
                                'ADDED_DATE' => lib::pack_date(date("d.m.Y H:i"),'long'), 
                                'FRIEND_ID'  => $new_friend_id, 
                                'OWNER_ID'   => $this->q_param['COMMUNITY_ID'],
                                'INVITER_ID' => $this->authorization->blog_id,
                                'LEVEL'      => (($community_set['POSTLEVEL']==1) ? 2 : 1),
                                'IS_CREATOR' => 0,
                                'IS_MODERATOR' => 0,
                                'IS_INVITE'  => 1,
                                'IS_INQUIRY' => 0,
                            )
                        );
                        //отосылаем письмо приглашённому 
                        $this->sendMsgWhenCommunityUser( $new_friend_id, $this->q_param['COMMUNITY_ID'], 'invite' );
                    }
                }
            }
        }
        if ( $this->q_param['action'] == 'del_inquiry' || $this->q_param['action'] == 'del_invite' ){
            if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id && !empty($this->q_param['BLOG_FRIEND_ID']) && !empty($this->q_param['FRIEND_ID']) && !empty($this->q_param['COMMUNITY_ID']) ) {
                db::delete_record( 'BLOG_FRIEND', array('BLOG_FRIEND_ID'=>$this->q_param['BLOG_FRIEND_ID']) );
            }
        }
        if ( $this->q_param['action'] == 'add_inquiry' ){
            if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id && !empty($this->q_param['BLOG_FRIEND_ID']) && !empty($this->q_param['FRIEND_ID']) && !empty($this->q_param['COMMUNITY_ID']) ) {
                db::update_record( 'BLOG_FRIEND', 
                    array( 'IS_INQUIRY' => 0 ), '',
                    array( 'BLOG_FRIEND_ID' => $this->q_param['BLOG_FRIEND_ID'] )
                );
                $community_set = $this->get_user_info($this->q_param['COMMUNITY_ID']);
                db::insert_record( 'BLOG_FRIEND', 
                    array( 
                        'ADDED_DATE' => lib::pack_date(date("d.m.Y H:i"),'long'), 
                        'FRIEND_ID'  => $this->q_param['COMMUNITY_ID'],
                        'OWNER_ID'   => $this->q_param['FRIEND_ID'],
                        'INVITER_ID' => $this->q_param['FRIEND_ID'],
                        'LEVEL'      => (($community_set['POSTLEVEL']==1) ? 2 : 1),
                        'IS_CREATOR' => 0,
                        'IS_MODERATOR' => 0,
                        'IS_INVITE'  => 0,
                        'IS_INQUIRY' => 0
                    )
                );
            }
        }
        if ( !$is_error ){
            Header('Location: '.$_SERVER['REQUEST_URI']);
            exit();
        }
     }

    $blog_id = $this->q_param['COMMUNITY_ID'];
    // собираем фильтр
    $_where = ' TRUE ';
    if ( trim($this->q_param['ff_ds'])!='') $_where .= ' AND F.ADDED_DATE >= \''.lib::pack_date(trim($this->q_param['ff_ds']),'short').'\' ';
    if ( trim($this->q_param['ff_de'])!='') $_where .= ' AND F.ADDED_DATE <= \''.lib::pack_date(trim($this->q_param['ff_de']),'short').'\' ';
    if ( trim($this->q_param['ff_n'])!='') $_where .= ' AND B.TITLE LIKE \'%'.trim($this->q_param['ff_n']).'%\' ';
    if ( $this->q_param['ff_l']!='') $_where .= ' AND F.LEVEL = \''.$this->q_param['ff_l'].'\' ';
    if ( $this->q_param['ff_m']!='') $_where .= ' AND F.IS_MODERATOR = \''.$this->q_param['ff_m'].'\' ';
    if ( $this->q_param['ff_c']!='') $_where .= ' AND F.IS_CREATOR = \''.$this->q_param['ff_c'].'\' ';
    // готовим список участников
    $this->createFriendList($blog_id, ' AND (F.IS_INVITE = \'0\' OR F.IS_INVITE IS NULL) AND (F.IS_INQUIRY = \'0\' OR F.IS_INQUIRY IS NULL) AND '.$_where.'', 'BLOG_FRIENDS');

    // собираем фильтр
    $_where = ' TRUE ';
    if ( trim($this->q_param['fq_ds'])!='') $_where .= ' AND F.ADDED_DATE >= \''.lib::pack_date(trim($this->q_param['fq_ds']),'short').'\' ';
    if ( trim($this->q_param['fq_de'])!='') $_where .= ' AND F.ADDED_DATE <= \''.lib::pack_date(trim($this->q_param['fq_de']),'short').'\' ';
    if ( trim($this->q_param['fq_n'])!='') $_where .= ' AND B.TITLE LIKE \'%'.trim($this->q_param['fq_n']).'%\' ';
    // готовим список запрашивающий вступление
    $this->createFriendList($blog_id, ' AND F.IS_INQUIRY = \'1\' AND '.$_where.'', 'BLOG_FRIENDS_INQUIRY');

    // собираем фильтр
    $_where = ' TRUE ';
    if ( trim($this->q_param['fi_ds'])!='') $_where .= ' AND F.ADDED_DATE >= \''.lib::pack_date(trim($this->q_param['fi_ds']),'short').'\' ';
    if ( trim($this->q_param['fi_de'])!='') $_where .= ' AND F.ADDED_DATE <= \''.lib::pack_date(trim($this->q_param['fi_de']),'short').'\' ';
    if ( trim($this->q_param['fi_n'])!='') $_where .= ' AND B.TITLE LIKE \'%'.trim($this->q_param['fi_n']).'%\' ';
    // готовим список приглашённых
    $this->createFriendList($blog_id, ' AND F.IS_INVITE = \'1\' AND '.$_where.'', 'BLOG_FRIENDS_INVITE');
}


/**
 * Выбор из БД списка друзей сообщества, с разнымим условиями $_where (приглащённые, участники, с запросом)
 */
protected function createFriendList($blog_id, $_where, $nameLoop){
    $sql_friends_sel = db::sql_select( '
        SELECT DISTINCT B.TITLE, F.*
        FROM BLOG_FRIEND F
            LEFT JOIN BLOG B ON (F.FRIEND_ID = B.BLOG_ID AND (B.IS_COMMUNITY = \'0\' OR B.IS_COMMUNITY IS NULL) AND B.IS_ACTIVE = \'1\')
        WHERE F.OWNER_ID = :owner '.$_where.'
        ORDER BY F.ADDED_DATE DESC, B.TITLE ASC',
        array('owner'=>$blog_id)
    );
    
    if ( count($sql_friends_sel)>0 ){
        $nameLoop_data = array();
        while ( $current_row=current($sql_friends_sel) ){
            $current_row['ADDED_DATE'] = lib::unpack_date($current_row['ADDED_DATE'], 'long');
            $current_row['PATH'] = $this->get_blog_path($current_row['TITLE']);
            $current_row['LEVEL'.$current_row['LEVEL']] = 1;
            $nameLoop_data[] = $current_row;
            next($sql_friends_sel); 
        } 
        $this->tpl->assign($nameLoop, $nameLoop_data);
    }
}





/**
 * Вариант использования "Форма регистрации Сообщества"
 */
protected function modeRegistrationCommunity() {
    // если не передан ни один параметр, значит надо отобразить форму для заполнения
    if ( !count($this->q_param) ) {
        return false;
    }
    // делаем доступными для шаблона ранее введённые значения
    if ( count($this->q_param)>0 ){
        foreach ( $this->q_param as $key => $value ){
            if ($key=='MEMBERSHIP' || $key=='POSTLEVEL' || $key=='MODERATION'){
                $this->tpl->assign($key.$value, 1);
            }else{
                $this->tpl->assign($key, $value);
            }
        }
    }
    // Проверяем обязательность заполнения полей
    if ( !$this->q_param['NICK'] || !$this->q_param['MEMBERSHIP'] || !$this->q_param['POSTLEVEL'] || !$this->q_param['MODERATION']){
        $this->tpl->assign( '_error1', 1);
        $is_error = true;
    }
    // Проверяется уникальность названия сообщества (ника)
    $user = $this->get_user_info_use_title($this->q_param['NICK']);
    $nick = strtolower($this->q_param['NICK']);
    if ( in_array($nick, array('adm','test','common','ru','en')) 
        || !empty($user) && ($this->view_param['view_mode']=='registration_community') ){
        $this->tpl->assign( '_error2', 1);
        $is_error = true;
    }
    // если ошибки - не идём на обработку    
    if ($is_error){
        $this->tpl->assign( '_is_error', 1);
        return false;
    }

    
    // в случае корректных данных - сохранение данных в БД
    $fields = array(
        'BLOG_DATE'     => lib::pack_date(date("d.m.Y H:i"),'long'),
        'TITLE'         => $this->q_param['NICK'],
        'NAME'          => $this->q_param['NAME'],
        'POSTS_ON_PAGE' => 1,
        'IS_ACTIVE'     => 1,
        'IS_COMMUNITY'  => 1,
        'MEMBERSHIP'    => $this->q_param['MEMBERSHIP'],
        'POSTLEVEL'     => $this->q_param['POSTLEVEL'],
        'MODERATION'    => $this->q_param['MODERATION']
    );
    $new_user = $this->set_newuser_info($fields);

    // добавляем пользователя, создавшего сообщество, в список друзей сообщества с флагом создателя
	db::insert_record( 'BLOG_FRIEND', 
        array(
            'ADDED_DATE' => lib::pack_date(date("d.m.Y H:i"),'long'),
            'FRIEND_ID'  => $this->authorization->blog_id,
            'OWNER_ID'   => $new_user['BLOG_ID'],
            'INVITER_ID' => $this->authorization->blog_id,
            'LEVEL'      => 2,
            'IS_CREATOR' => 1,
            'IS_MODERATOR' => 1,
            'IS_INVITE'  => 0,
            'IS_INQUIRY' => 0
        )
	);
    // добавляем сообщество в список друзей пользователя
	db::insert_record( 'BLOG_FRIEND', 
        array(
            'ADDED_DATE' => lib::pack_date(date("d.m.Y H:i"),'long'),
            'FRIEND_ID'  => $new_user['BLOG_ID'],
            'OWNER_ID'   => $this->authorization->blog_id,
            'INVITER_ID' => $this->authorization->blog_id,
            'LEVEL'      => 0,
            'IS_CREATOR' => 0,
            'IS_MODERATOR' => 0,
            'IS_INVITE'  => 0,
            'IS_INQUIRY' => 0
        )
	);
	// создание папок и index-файлов в папке virtual
	$this->createBlogFolder( $this->q_param['NICK'], $new_user['BLOG_ID'] );

    // ставим всем полям "признак отображения"
    $_fields_arr = array('BLOG_COUNTRY_ID', 'BLOG_CITY_ID', 'HOMEPAGE', 'ABOUT', 'NAME', 'INTEREST');
    foreach ( $_fields_arr as $_field ){
    	$_field_sel = db::sql_select( 'select BLOG_FIELD_ID from BLOG_FIELD where FIELD_NAME = :field', array('field'=>$_field) );
    	if ( count($_field_sel)>0 ){
            db::insert_record( 'BLOG_SV_BLOG_FIELD',
                array(
                    'BLOG_ID'       => $new_user['BLOG_ID'],
                    'BLOG_FIELD_ID' => $_field_sel[0]['BLOG_FIELD_ID']
                )
            );
        }
    }

    // рассылка сообщений
    $fileTPL = "registrationCommunityNotice.tpl";
    // формируется тело письма для менеджера
    if ( !file_exists($this->tpl_dir.$fileTPL) ) {
        $htmlBodyToManager = 'Шаблон отсутствует: '.$fileTPL.'';
    }else{
        // инициализация шаблонизатора
        $htmlBody_tpl = new smarty_ee_module($this);
		$htmlBody_tpl->assign($this->q_param);
		$htmlBody_tpl->assign('HTTP_HOST', $_SERVER['HTTP_HOST']);
		$htmlBodyToManager = $htmlBody_tpl->fetch($this->tpl_dir . $fileTPL);
        unset($htmlBody_tpl);
  	}

	$param = array(
        'toName'   	=> "Уважаемый администратор!",
        'from'		=> 'webmaster@'.$_SERVER['HTTP_HOST'],
        'fromName'	=> $this->lang['sysw_blog_registration_from'].' '.$_SERVER['HTTP_HOST'],
        'subject'	=> "Регистрация нового сообщества",
        'htmlBody'	=> $htmlBodyToManager
    );
	// отсылается уведомление менеджеру
	if ( $mailArr = preg_split("/[,; ]+/", $this->view_param['emailToNotice']) ){
 		for	( $i = 0; $i < count($mailArr); $i++ ){
 			if ( preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9_-]+(\.[_a-z0-9-]+)+$/i', $mailArr[$i]) ){
	 			$param['to'] = $mailArr[$i];
				$this->sendMail($param);
			}
		}
	}
	
    $this->tpl->assign('_is_registrated', 1);
    $_path = $this->getPathToVI( 'blogs', 'community_manager', array( array('name'=>'submode', 'value'=>'profile'), array('name'=>'community_id', 'value'=>$new_user['BLOG_ID']) ) );
    if ( $_path ){
		Header('Location: '.$_path.'');
        exit();
    }
}






/**
 * Установка пути к шаблону tpl_dir // необходима при создании блога из админки
 */
public function setTplDir( $tpl_dir ){
    $this->tpl_dir = $tpl_dir;
}

/**
 * Создание структуры папок для сайта-блога и заливка туда индексных файлов
 */
public function createBlogFolder( $nick, $blog_id ){
    $this->authorization = new blogs_auth();
    $nick = strtolower($nick);
    // шаблон индексного файла
    $fileTPLindex = 'index_tpl.tpl';
    if ( !file_exists($this->tpl_dir . $fileTPLindex) ){
        $this->body = 'Шаблон отсутствует: '.$fileTPLindex.'';
        return 0;
    }
    if ( defined('BLOG_SITE_ROOT') && strlen(BLOG_SITE_ROOT)>0 ){
        // создание структуры папок для сайта-блога
        mkdir( BLOG_SITE_ROOT.'/'.$nick.'.'.$this->authorization->blog_site_postfix );
        mkdir( BLOG_SITE_ROOT.'/'.$nick.'.'.$this->authorization->blog_site_postfix.'/htdocs/' );
        mkdir( BLOG_SITE_ROOT.'/'.$nick.'.'.$this->authorization->blog_site_postfix.'/cgi_bin/' );
        mkdir( BLOG_SITE_ROOT.'/'.$nick.'.'.$this->authorization->blog_site_postfix.'/htdocs/profile/' );
        mkdir( BLOG_SITE_ROOT.'/'.$nick.'.'.$this->authorization->blog_site_postfix.'/htdocs/friend/' );
        //индексные файлы
        $files = array(
            array( 'MODE'=>'adv_blog',  'SUBMODE'=>'blog',         'PATH_TO_BLOG_PARAMS'=>'../../',    'PATH_TO_INDEX_FILE'=>'/htdocs/index.php'),
            array( 'MODE'=>'adv_blog',  'SUBMODE'=>'profile',      'PATH_TO_BLOG_PARAMS'=>'../../../', 'PATH_TO_INDEX_FILE'=>'/htdocs/profile/index.php'),
            array( 'MODE'=>'blog',      'SUBMODE'=>'photogallery', 'PATH_TO_BLOG_PARAMS'=>'../../../', 'PATH_TO_INDEX_FILE'=>'/htdocs/profile/photogallery.php'),
            array( 'MODE'=>'adv_blog',  'SUBMODE'=>'friendtape',   'PATH_TO_BLOG_PARAMS'=>'../../../', 'PATH_TO_INDEX_FILE'=>'/htdocs/friend/index.php')
        );
        foreach ( $files as $item_file ){
            // инициализация шаблонизатора
            $index_tpl = new smarty_ee_module($this);
            $index_tpl->assign('PHP_START',            '<?php');
            $index_tpl->assign('PHP_END',              '?>');
            $index_tpl->assign('PATH_TO_BLOG_PARAMS',  $item_file['PATH_TO_BLOG_PARAMS'].'blog_params.php');
            $index_tpl->assign('VIEW_MODE',            $item_file['MODE']);
            $index_tpl->assign('SUBMODE',              $item_file['SUBMODE']);
            $index_tpl->assign('BLOG_ID',              intval($blog_id));

            $index_file = fopen( BLOG_SITE_ROOT.'/'.$nick.'.'.$this->authorization->blog_site_postfix.$item_file['PATH_TO_INDEX_FILE'], w );
            if ( $index_file ){
                fwrite($index_file, $index_tpl->fetch($this->tpl_dir . $fileTPLindex));
                fclose($index_file);
            }
            unset($index_tpl);
        }
    }

    $site_arr = db::sql_select( 'SELECT * FROM SITE ORDER BY SITE_ID' );
    $sities = array();
    if ( count($site_arr)>0 ){
        if ( !empty($site_arr[0]['PATH']) ){
            $sities[] = $site_arr[0]['PATH'];
        }
        if ( !empty($site_arr[0]['TEST_PATH']) ){
            $sities[] = $site_arr[0]['TEST_PATH'];
        }
    }
    if ( count($sities)>0 ){
        foreach ( $sities as $item_path ){
            // создание структуры папок для сайта-блога
            mkdir( $item_path.'blogs/'.$nick.'/' );
            mkdir( $item_path.'blogs/'.$nick.'/profile/' );
            mkdir( $item_path.'blogs/'.$nick.'/friend/' );
            //индексные файлы
            $files = array(
                array( 'MODE'=>'adv_blog',  'SUBMODE' => 'blog',         'PATH_TO_BLOG_PARAMS'=>'../',    'PATH_TO_INDEX_FILE' => '/index.php'),
                array( 'MODE'=>'adv_blog',  'SUBMODE' => 'profile',      'PATH_TO_BLOG_PARAMS'=>'../../', 'PATH_TO_INDEX_FILE' => '/profile/index.php'),
                array( 'MODE'=>'blog',      'SUBMODE' => 'photogallery', 'PATH_TO_BLOG_PARAMS'=>'../../', 'PATH_TO_INDEX_FILE' => '/profile/photogallery.php'),
                array( 'MODE'=>'adv_blog',  'SUBMODE' => 'friendtape',   'PATH_TO_BLOG_PARAMS'=>'../../', 'PATH_TO_INDEX_FILE' => '/friend/index.php')
            );
            foreach ( $files as $item_file ){
                // инициализация шаблонизатора
                $index_tpl = new smarty_ee_module($this);
                $index_tpl->assign('PHP_START',            '<?php');
                $index_tpl->assign('PHP_END',              '?>');
                $index_tpl->assign('PATH_TO_BLOG_PARAMS',  $item_file['PATH_TO_BLOG_PARAMS'].'blog_params.php');
                $index_tpl->assign('VIEW_MODE',            $item_file['MODE']);
                $index_tpl->assign('SUBMODE',              $item_file['SUBMODE']);
                $index_tpl->assign('BLOG_ID',              intval($blog_id));

                $index_file = fopen( $item_path.'blogs/'.$nick.$item_file['PATH_TO_INDEX_FILE'], w );
                if ( $index_file ){
                    fwrite($index_file, $index_tpl->fetch($this->tpl_dir . $fileTPLindex));
                    fclose($index_file);
                }
                unset($index_tpl);
            }
        }
    }
}

/**
 * Удаление структуры папок для пользователя с указанным ID
 */
public function removeBlogFolder( $blog_id ){
    $this->authorization = new blogs_auth();
    $user = $this->get_user_info($blog_id);
    if ( !empty($user) && !empty($user['TITLE']) ){
        $nick = strtolower($user['TITLE']);
        if ( defined('BLOG_SITE_ROOT') && strlen(BLOG_SITE_ROOT)>0 ){
            $this->delete_directory(BLOG_SITE_ROOT.'/'.$nick.'.'.$this->authorization->blog_site_postfix);
        }
        $site_arr = db::sql_select( 'SELECT * FROM SITE ORDER BY SITE_ID' );
        $sities = array();
        if ( count($site_arr)>0 ){
            if ( !empty($site_arr[0]['PATH']) ){
                $sities[] = $site_arr[0]['PATH'];
            }
            if ( !empty($site_arr[0]['TEST_PATH']) ){
                $sities[] = $site_arr[0]['TEST_PATH'];
            }
        }
        if ( count($sities)>0 ){
            foreach ( $sities as $item_path ){
                $this->delete_directory($item_path.'blogs/'.$nick.'');
            }
        }
    }
}

/**
 * Рекурсивное удаление каталога(со всеми подкаталогами и файлами)
 */
protected function delete_directory($dirname){
    if ( is_dir($dirname) ){
        $dir_handle = opendir($dirname);
    }
    if ( !$dir_handle ){
        return false;
    }
    while ( $file = readdir($dir_handle) ){
        if ($file != '.' && $file != '..'){
            if ( !is_dir($dirname.'/'.$file) ){
                unlink($dirname.'/'.$file);
            }else{
                $this->delete_directory($dirname.'/'.$file);
            }
        }
    }
    closedir($dir_handle);
    rmdir($dirname);
    return true;
}








/**
 * Вариант использования "Редактирование профиля"
 */
protected function modeEditProfile() {
    $user = $this->get_current_user_info();
    if ( !empty($user) ) {
        // заголовок формы заполняем
        $this->tpl->assign('BLOG_USER', $user['TITLE']);
        $this->tpl->assign('BLOG_ID', $this->authorization->blog_id);
        // считаем количество предложений вступить в сообщество (для оформления постраничных ссылок)
        $sql_friends_sel = db::sql_select( '
            SELECT count(BLOG_FRIEND_ID) AS COUNT_OFFERS 
            FROM BLOG_FRIEND F 
            WHERE FRIEND_ID = :friend AND F.IS_INVITE = \'1\'', 
            array('friend'=>$this->authorization->blog_id) 
        );
        if ( is_array($sql_friends_sel) && count($sql_friends_sel)>0 ){
            $this->tpl->assign( '_countOffer', $sql_friends_sel[0]['COUNT_OFFERS']);
        }

        if ( is_array($this->authorization->fields_from_client) && (count($this->authorization->fields_from_client)>0) ){
            foreach ( $this->authorization->fields_from_client as $item ){
                $this->tpl->assign($item.'_READONLY', 1);
            }
            if ( ($this->q_param['submode']=='pwd') && in_array('PASSWORD', $this->authorization->fields_from_client) ){
                $this->q_param['submode'] = '';
            }
        }

        switch ( $this->q_param['submode'] ){
            case 'pwd' :
                $this->modeProfilePwd();
                break;
            case 'images' :
                $this->managerImagesPerson();
                break;
            case 'friendgroups' :
                $this->modeProfileFriendgroups();
                break;
            case 'friends' :
                $this->modeProfileFriends();
                break;
            case 'tags' :
                $this->managerTagsPerson();
                break;
            case 'offers' :
                $this->modeProfileOffers();
                break;
            default :
                $this->managerProfilePerson();
        }
    }else{
        return false;
    }
}


/**
 * Вариант использования "Редактирование профиля - Изменение пароля"
 */
protected function modeProfilePwd() {
    $this->tpl->assign('MODE_PWD', 1);
    $_is_error = 0;
    if ( isset($this->q_param['save']) ){
        //сохранение изменений в БД
        if ( is_numeric($this->q_param['BLOG_ID']) && ($this->q_param['BLOG_ID']==$this->authorization->blog_id) ){
            if ( $this->q_param['PASSWORD']!=$this->q_param['PASSWORD2'] ){
                $this->tpl->assign('ERROR_DIFF_PASSWORD', 1);
                $_is_error = 1;
            }else{
                $user = $this->get_current_user_info();
                if ( empty($user) || ($user['PASSWORD_MD5']!=md5($this->q_param['OLD_PASSWORD'])) ){
                    $this->tpl->assign('ERROR_OLD_PASSWORD', 1);
                    $_is_error = 1;
                }else{
                    $fields = array( 'PASSWORD_MD5'=>md5($this->q_param['PASSWORD']) );
                    $this->set_user_info($this->q_param['BLOG_ID'], $fields);
                }
            }
        }
        if ( !$_is_error ){
            $blog = $this->get_current_user_info();
            $this->authorization->setCookie($blog['BLOG_ID'], $this->authorization->getBlogKey($blog['BLOG_ID'],$blog['EMAIL'],$blog['PASSWORD_MD5']), false);
            Header('Location: '.$_SERVER['REQUEST_URI']);
            exit();
        }
    }
}


/**
 * Вариант использования "Редактирование профиля - Группы друзей"
 */
protected function modeProfileFriendgroups() {
    $this->tpl->assign('MODE_FRIENDGROUPS', 1);
    if ( isset($this->q_param['action']) ){
        if ( $this->q_param['action'] == 'del' ){
            if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id && !empty($this->q_param['BLOG_FRIENDGROUP_ID']) ) {
                db::delete_record( 'BLOG_FRIEND_SV_BLOG_FRIENDGROUP', array('BLOG_FRIENDGROUP_ID'=>$this->q_param['BLOG_FRIENDGROUP_ID']) );
                db::delete_record( 'BLOG_FRIENDGROUP', array('BLOG_FRIENDGROUP_ID'=>$this->q_param['BLOG_FRIENDGROUP_ID']) );
                $BLOG_FRIENDGROUP_ID = 0;
            }
        }
        if ( $this->q_param['action'] == 'add' ){
            if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id && (trim($this->q_param['TITLE']) != '') ) {
                $sql_result_set = db::sql_select( '
                    SELECT * 
                    FROM BLOG_FRIENDGROUP 
                    WHERE BLOG_ID = :blog AND TITLE = :title',
                    array('blog'=>$this->authorization->blog_id, 'title'=>trim($this->q_param['TITLE']))
                );
                if ( !(count($sql_result_set)>0) ){
                    db::insert_record( 'BLOG_FRIENDGROUP', 
                        array(
                            'BLOG_ID'    => $this->authorization->blog_id, 
                            'TITLE'      => trim($this->q_param['TITLE']), 
                            'LIST_ORDER' => intval(trim($this->q_param['LIST_ORDER']))
                        )
                    );
                    $BLOG_FRIENDGROUP_ID = db::last_insert_id( 'BLOG_FRIENDGROUP_SEQ' );
                }
            }
        }
        if ( $this->q_param['action'] == 'save' ){
            if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id && !empty($this->q_param['BLOG_FRIENDGROUP_ID']) && (trim($this->q_param['TITLE']) != '') ) {
                db::update_record( 'BLOG_FRIENDGROUP', 
                    array(
                        'TITLE'      => trim($this->q_param['TITLE']), 
                        'LIST_ORDER' => intval(trim($this->q_param['LIST_ORDER']))
                    ), '',
                    array( 'BLOG_FRIENDGROUP_ID' => $this->q_param['BLOG_FRIENDGROUP_ID'] )
                );
                db::delete_record( 'BLOG_FRIEND_SV_BLOG_FRIENDGROUP', array('BLOG_FRIENDGROUP_ID'=>$this->q_param['BLOG_FRIENDGROUP_ID']) );
                if ( count($this->q_param['BLOG_FRIENDS_IN'])>0 ){
                    foreach ( $this->q_param['BLOG_FRIENDS_IN'] as $item){
                        db::insert_record( 'BLOG_FRIEND_SV_BLOG_FRIENDGROUP', 
                            array(
                                'BLOG_FRIENDGROUP_ID' => $this->q_param['BLOG_FRIENDGROUP_ID'], 
                                'BLOG_FRIEND_ID'      => intval($item) 
                            )
                        );
                    }
                }
            }
            $BLOG_FRIENDGROUP_ID = $this->q_param['BLOG_FRIENDGROUP_ID'];
        }
        if ( strpos($_SERVER['REQUEST_URI'], '&blog_friendgroup_id'.$this->env['area_id'].'=') !== false ){
            $go_to = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '&blog_friendgroup_id'.$this->env['area_id'].'='));
        }else{
            $go_to = $_SERVER['REQUEST_URI'];
        }
        if ($BLOG_FRIENDGROUP_ID > 0){
            $go_to .= '&blog_friendgroup_id'.$this->env['area_id'].'='.$BLOG_FRIENDGROUP_ID.'';
        }
        Header('Location: '.$go_to);
        exit();
    }

    $BLOG_FRIENDGROUP_ID = $this->q_param['blog_friendgroup_id'];
    if ( empty($BLOG_FRIENDGROUP_ID) ){
        $sql_result_set = db::sql_select( '
            SELECT BLOG_FRIENDGROUP_ID 
            FROM BLOG_FRIENDGROUP 
            WHERE BLOG_ID=:blog 
            ORDER BY LIST_ORDER ASC',
            array('blog'=>$this->authorization->blog_id)
        );
        if ( count($sql_result_set)>0 ){
            $BLOG_FRIENDGROUP_ID = $sql_result_set[0]['BLOG_FRIENDGROUP_ID'];
        }
    }

    // готовим список групп для селекта
    $sql_result_set = db::sql_select( '
        SELECT * 
        FROM BLOG_FRIENDGROUP 
        WHERE BLOG_ID=:blog 
        ORDER BY LIST_ORDER ASC',
        array('blog'=>$this->authorization->blog_id)
    );
    if ( count($sql_result_set)>0 ){
        $BLOG_FRIENDGROUP = array();
        while ( $current_row=current($sql_result_set) ){
            if ($current_row['BLOG_FRIENDGROUP_ID']==$BLOG_FRIENDGROUP_ID){
                $current_row['SELECTED'] = 1;
            }
            $BLOG_FRIENDGROUP[] = $current_row;
            next($sql_result_set); 
        } 
        $this->tpl->assign('BLOG_FRIENDGROUP', $BLOG_FRIENDGROUP);
    }

    // готовим данные о выбранной группе
    if ( !empty($BLOG_FRIENDGROUP_ID) ){
        $sql_result_set = db::sql_select( '
            SELECT * 
            FROM BLOG_FRIENDGROUP 
            WHERE BLOG_ID = :blog AND BLOG_FRIENDGROUP_ID = :friendgroup',
            array('blog'=>$this->authorization->blog_id, 'friendgroup'=>$BLOG_FRIENDGROUP_ID)
        );
        if ( count($sql_result_set)>0 ){
            $this->tpl->assign($sql_result_set[0]);
        }
        $sql_friends_sel = db::sql_select( '
            SELECT FF.BLOG_FRIENDGROUP_ID AS SELECTED, F.BLOG_FRIEND_ID, B.TITLE
            FROM BLOG_FRIEND F
                LEFT JOIN BLOG_FRIEND_SV_BLOG_FRIENDGROUP FF ON (F.BLOG_FRIEND_ID = FF.BLOG_FRIEND_ID AND FF.BLOG_FRIENDGROUP_ID = :friendgroup)
                LEFT JOIN BLOG B ON (F.FRIEND_ID = B.BLOG_ID AND (B.IS_COMMUNITY = \'0\' OR B.IS_COMMUNITY IS NULL) AND B.IS_ACTIVE = \'1\')
            WHERE F.OWNER_ID = :owner AND B.TITLE IS NOT NULL',
            array('friendgroup'=>$BLOG_FRIENDGROUP_ID, 'owner'=>$this->authorization->blog_id)
        );
        if ( count($sql_friends_sel)>0 ){
            $BLOG_FRIENDS = array(); 
            while ( $current_row=current($sql_friends_sel) ){
                $current_row['SELECTED'] = ($current_row['SELECTED']==$BLOG_FRIENDGROUP_ID) ? 1 : 0;
                $current_row['PATH'] = $this->get_blog_path($current_row['TITLE']);
                $BLOG_FRIENDS[] = $current_row;
                next($sql_friends_sel); 
            } 
            $this->tpl->assign('BLOG_FRIENDS', $BLOG_FRIENDS);
        }
    }
}

/**
 * Вариант использования "Редактирование профиля - Друзья"
 */
protected function modeProfileFriends() {
    $this->tpl->assign('MODE_FRIENDS', 1);
    if ( isset($this->q_param['action']) ){
        $is_error = 0;
        if ( $this->q_param['action'] == 'del' ){
            if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id && !empty($this->q_param['BLOG_FRIEND_ID']) ){
                db::delete_record( 'BLOG_FRIEND_SV_BLOG_FRIENDGROUP', array('BLOG_FRIEND_ID'=>$this->q_param['BLOG_FRIEND_ID']) );
                db::delete_record( 'BLOG_FRIEND', array('BLOG_FRIEND_ID'=>$this->q_param['BLOG_FRIEND_ID']) );
            }
        }
        if ( $this->q_param['action'] == 'add' ){
            if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id && (trim($this->q_param['NICK']) != '') ){
                $this->tpl->assign('NICK', trim($this->q_param['NICK']));
                $user = $this->get_user_info_use_title($this->q_param['NICK']);
                if ( empty($user) ){
                    $this->tpl->assign('ERROR_1', 1); // пользователя с указанным ником не существует
                    $is_error = 1;
                }else{
                    $new_friend_id = $user['BLOG_ID'];
                    if ( $new_friend_id==$this->authorization->blog_id ){
                        $this->tpl->assign('ERROR_2', 1); // указан собственный ник
                        $is_error = 1;
                    }else{
                        $sql_friend_set = db::sql_select( '
                            SELECT BLOG_FRIEND_ID 
                            FROM BLOG_FRIEND 
                            WHERE FRIEND_ID = :friend AND OWNER_ID = :owner',
                            array('friend'=>$new_friend_id, 'owner'=>$this->authorization->blog_id)
                        );
                        if ( count($sql_friend_set)>0 ){
                            if ( $user['IS_COMMUNITY']!=1 ){
                                $this->tpl->assign('ERROR_3', 1); // пользователь с указанным ником уже является другом
                            }else{
                                $this->tpl->assign('ERROR_4', 1); // сообщество с указанным ником уже является другом
                            }
                            $is_error = 1;
                        }else{
                            if ( $user['IS_COMMUNITY']!=1 ){
                                //всё ОК - добавляем пользователя $new_friend_id себе в друзья
                                db::insert_record( 'BLOG_FRIEND', 
                                    array( 
                                        'ADDED_DATE'  => lib::pack_date(date("d.m.Y H:i"),'long'), 
                                        'FRIEND_ID'   => $new_friend_id,
                                        'OWNER_ID'    => $this->authorization->blog_id,
                                        'INVITER_ID'  => $this->authorization->blog_id,
                                        'LEVEL'       => 0,
                                        'IS_CREATOR'  => 0,
                                        'IS_MODERATOR'=> 0,
                                        'IS_INVITE'   => 0,
                                        'IS_INQUIRY'  => 0
                                    )
                                );
                            }else{
                                //всё ОК - добавляем себя в список друзей сообщества $new_friend_id
                                $MEMBERSHIP = $user['MEMBERSHIP'];
                                $POSTLEVEL = $user['POSTLEVEL'];
                                db::insert_record( 'BLOG_FRIEND', 
                                    array( 
                                        'ADDED_DATE'  => lib::pack_date(date("d.m.Y H:i"),'long'), 
                                        'FRIEND_ID'   => $this->authorization->blog_id, 
                                        'OWNER_ID'    => $new_friend_id,
                                        'INVITER_ID'  => $this->authorization->blog_id,
                                        'LEVEL'       => (($POSTLEVEL==2) ? 1 : 2),
                                        'IS_CREATOR'  => 0,
                                        'IS_MODERATOR'=> 0,
                                        'IS_INVITE'   => 0,
                                        'IS_INQUIRY'  => (($MEMBERSHIP==2) ? 1 : 0)
                                    )
                                );
                                if ($MEMBERSHIP!=2){
                                    // добавляем сообщество $new_friend_id себе в друзья
                                    db::insert_record( 'BLOG_FRIEND', 
                                        array(
                                            'ADDED_DATE'  => lib::pack_date(date("d.m.Y H:i"),'long'), 
                                            'FRIEND_ID'   => $new_friend_id,
                                            'OWNER_ID'    => $this->authorization->blog_id,
                                            'INVITER_ID'  => $this->authorization->blog_id,
                                            'LEVEL'       => 0,
                                            'IS_CREATOR'  => 0,
                                            'IS_MODERATOR'=> 0,
                                            'IS_INVITE'   => 0,
                                            'IS_INQUIRY'  => 0
                                        )
                                    );
                                }
                                //(!!!!!!)надо ещё всем модераторам сообщества разослать сообщения
                            }
                        }
                    }
                }
            }
        }
        if ( !$is_error ){
            Header('Location: '.$_SERVER['REQUEST_URI']);
            exit();
        }
    }

    $BLOG_FRIENDGROUP_ID = $this->q_param['blog_friendgroup_id'];
    // готовим список групп для селекта
    $sql_result_set = db::sql_select( 'SELECT * FROM BLOG_FRIENDGROUP WHERE BLOG_ID = :blog ORDER BY LIST_ORDER ASC', array('blog'=>$this->authorization->blog_id) );
    if ( count($sql_result_set)>0 ){
        $BLOG_FRIENDGROUP = array();
        while ( $current_row=current($sql_result_set) ){
            if ($current_row['BLOG_FRIENDGROUP_ID']==$BLOG_FRIENDGROUP_ID){
                $current_row['SELECTED'] = 1;
            }
            $BLOG_FRIENDGROUP[] = $current_row;
            next($sql_result_set); 
        } 
        $this->tpl->assign('BLOG_FRIENDGROUP', $BLOG_FRIENDGROUP);
    }

    // готовим данные о выбранной группе (всех друзей из данной группы со списком их групп)
    $sql_friends_sel = db::sql_select( '
        SELECT DISTINCT F.BLOG_FRIEND_ID, B.TITLE
        FROM BLOG_FRIEND F
            LEFT JOIN BLOG_FRIEND_SV_BLOG_FRIENDGROUP FF ON (F.BLOG_FRIEND_ID = FF.BLOG_FRIEND_ID)
            LEFT JOIN BLOG B ON (F.FRIEND_ID=B.BLOG_ID AND (B.IS_COMMUNITY = \'0\' OR B.IS_COMMUNITY IS NULL) AND B.IS_ACTIVE = \'1\')
        WHERE F.OWNER_ID = :owner AND B.TITLE IS NOT NULL '.(empty($BLOG_FRIENDGROUP_ID) ? '' : ' AND FF.BLOG_FRIENDGROUP_ID = \''.$BLOG_FRIENDGROUP_ID.'\''),
        array('owner'=>$this->authorization->blog_id)
    );
    
    if ( count($sql_friends_sel)>0 ){
        $BLOG_FRIENDS = array();
        while ( $current_row=current($sql_friends_sel) ){
            $sql_groups_sel = db::sql_select( '
                SELECT FG.*, FF.* 
                FROM BLOG_FRIENDGROUP FG, BLOG_FRIEND_SV_BLOG_FRIENDGROUP FF 
                WHERE FG.BLOG_ID = :blog AND FG.BLOG_FRIENDGROUP_ID = FF.BLOG_FRIENDGROUP_ID AND FF.BLOG_FRIEND_ID = :friend',
                array('blog'=>$this->authorization->blog_id, 'friend'=>$current_row['BLOG_FRIEND_ID'])
            );
            $current_row['GROUPS'] = $sql_groups_sel;
            $current_row['PATH'] = $this->get_blog_path($current_row['TITLE']);
            $BLOG_FRIENDS[] = $current_row;
            next($sql_friends_sel); 
        }
        $this->tpl->assign('BLOG_FRIENDS', $BLOG_FRIENDS);
    }
}


/**
 * Вариант использования "Редактирование профиля - Приглашения"
 */
protected function modeProfileOffers() {
    $this->tpl->assign('MODE_OFFERS', 1);
    if ( isset($this->q_param['action']) ){
        if ( $this->q_param['action'] == 'del' ){
            if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id && !empty($this->q_param['BLOG_FRIEND_ID']) ){
                // отсылаем сообщение пригласившему
                $this->sendMsgWhenInviteList( $this->q_param['BLOG_FRIEND_ID'], 'del' );
                db::delete_record( 'BLOG_FRIEND', array('BLOG_FRIEND_ID'=>$this->q_param['BLOG_FRIEND_ID']) );
            }
        }
        if ( $this->q_param['action'] == 'add' ){
            if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id && !empty($this->q_param['BLOG_FRIEND_ID']) ){
                // снимаем флаг приглащения
                db::update_record( 'BLOG_FRIEND', 
                    array( 'IS_INVITE' => 0 ), '',
                    array( 'BLOG_FRIEND_ID' => $this->q_param['BLOG_FRIEND_ID'] )
                );
                // добавляем сообщество себе в друзья
                db::insert_record( 'BLOG_FRIEND', 
                    array( 
                        'ADDED_DATE'  => lib::pack_date(date("d.m.Y H:i"),'long'), 
                        'FRIEND_ID'   => $this->q_param['COMMUNITY_ID'], 
                        'OWNER_ID'    => $this->authorization->blog_id,
                        'INVITER_ID'  => $this->authorization->blog_id,
                        'LEVEL'       => 0,
                        'IS_CREATOR'  => 0,
                        'IS_MODERATOR'=> 0,
                        'IS_INVITE'   => 0,
                        'IS_INQUIRY'  => 0
                    )
                );
                // отсылаем сообщение все модераторам о расширении сообщества
                $this->sendMsgWhenInviteList( db::last_insert_id( 'BLOG_FRIEND_SEQ' ), 'add' );
            }
        }
        Header('Location: '.$_SERVER['REQUEST_URI']);
        exit();
    }

    // выбираем все приглашения
    $sql_friends_sel = db::sql_select( '
        SELECT DISTINCT F.BLOG_FRIEND_ID, F.ADDED_DATE, F.FRIEND_ID, F.OWNER_ID AS COMMUNITY_ID, F.INVITER_ID, B2.TITLE AS COMMUNITY_TITLE, B3.TITLE AS INVITER_TITLE
        FROM BLOG_FRIEND F
        	LEFT JOIN BLOG B2 ON (F.OWNER_ID = B2.BLOG_ID AND B2.IS_ACTIVE = \'1\')
        	LEFT JOIN BLOG B3 ON (F.INVITER_ID = B3.BLOG_ID AND B3.IS_ACTIVE = \'1\')
        WHERE FRIEND_ID = :friend AND F.IS_INVITE = \'1\'',
        array('friend'=>$this->authorization->blog_id)
    );
    
    if ( count($sql_friends_sel)>0 ){
        $BLOG_FRIENDS = array();
        while ( $current_row=current($sql_friends_sel) ){
            $current_row['ADDED_DATE'] = lib::unpack_date($current_row['ADDED_DATE'], 'long');
            $current_row['COMMUNITY_PATH'] = $this->get_blog_path($current_row['COMMUNITY_TITLE']).'/profile/';
            $current_row['INVITER_PATH'] = $this->get_blog_path($current_row['INVITER_TITLE']).'/profile/';
            $BLOG_FRIENDS[] = $current_row;
            next($sql_friends_sel); 
        }
        $this->tpl->assign('BLOG_FRIENDS', $BLOG_FRIENDS);
    }
}









/** 
 * Функция обработки Профиля пользователя и сообщества 
 * Для вариантов использования :
 *    Редактирование профиля - Личная информация
 *    Управление сообществами - Редактирование описания
 */
protected function managerProfileCommunity() {
    $this->managerProfile( true );
}
protected function managerProfilePerson() {
    $this->managerProfile( false );
}
protected function managerProfile( $is_community=false ) {
    $this->tpl->assign('MODE_PERSONDATA', 1);
    $blog_id = $is_community ? $this->q_param['COMMUNITY_ID'] : ( !empty($this->q_param['BLOG_ID']) ? $this->q_param['BLOG_ID'] : $this->authorization->blog_id );
    if ( isset($this->q_param['save']) ){
        //сохранение записи в БД
        if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id ){
            $fields = array(
                'NAME'       => $this->q_param['NAME'], 
                'EMAIL'      => $this->q_param['EMAIL'], 
                'FIO'        => $this->q_param['FIO'],
                'ICQ'        => $this->q_param['ICQ'],
                'SKYPE'      => $this->q_param['SKYPE'], 
                'BIRTHDATE'  => (empty($this->q_param['BIRTHDATE']) ? '' : lib::pack_date($this->q_param['BIRTHDATE'],'short')),
                'BIRTHDATE_FORMAT' => $this->q_param['BIRTHDATE_FORMAT'],
                'SEX'        => $this->q_param['SEX'],
                'BLOG_COUNTRY_ID' => $this->q_param['BLOG_COUNTRY_ID'], 
                'BLOG_CITY_ID' => $this->q_param['BLOG_CITY_ID'],
                'HOMEPAGE'   => $this->q_param['HOMEPAGE'],
                'ABOUT'      => $this->q_param['ABOUT'],
                'POSTS_ON_PAGE' => $this->q_param['POSTS_ON_PAGE'],
                'MEMBERSHIP' => ( $is_community ? $this->q_param['MEMBERSHIP'] : 0 ),
                'POSTLEVEL'  => ( $is_community ? $this->q_param['POSTLEVEL'] : 0 ),
                'MODERATION' => ( $is_community ? $this->q_param['MODERATION'] : 0 )
            );
            $this->set_user_info($blog_id, $fields);

            // список интересов обрабатываем (сначала все удаляем, смотрим появление новых, потом пишем новый расклад)
            $this->q_param['INTEREST'] = trim($this->q_param['INTEREST']);
            db::sql_query( '
                UPDATE BLOG_INTEREST 
                SET RATING = RATING-1 
                WHERE RATING > 0 AND BLOG_INTEREST_ID IN (SELECT BLOG_INTEREST_ID FROM BLOG_SV_BLOG_INTEREST WHERE BLOG_ID = :blog)',
                array('blog'=>$blog_id)
            );
            db::delete_record( 'BLOG_SV_BLOG_INTEREST', array('BLOG_ID'=>$blog_id) );
            if ( !empty($this->q_param['INTEREST']) ){
                $arr_interests = explode(',', $this->q_param['INTEREST']);
                $user_interests = array();
                foreach ( $arr_interests as $value ){
                    $value = trim($value);
                    if (!empty($value)){
                        $interests_sel = db::sql_select( '
                            SELECT BLOG_INTEREST_ID 
                            FROM BLOG_INTEREST 
                            WHERE TITLE = :title 
                            ORDER BY BLOG_INTEREST_ID DESC',
                            array('title'=>$value)
                        );
                        if ( count($interests_sel)>0 ){
                            $user_interests[] = $interests_sel[0]['BLOG_INTEREST_ID'];
                        }else{
                            db::insert_record( 'BLOG_INTEREST', 
                                array( 
                                    'TITLE'  => $value, 
                                    'RATING' => 0
                                )
                            );
                            $user_interests[] = db::last_insert_id( 'BLOG_INTEREST_SEQ' );
                        }
                    }
                }
                $user_interests = array_unique($user_interests);
                if ( is_array($user_interests) && count($user_interests)>0 ){
                    foreach ( $user_interests as $item ){
                        db::insert_record( 'BLOG_SV_BLOG_INTEREST', 
                            array( 
                                'BLOG_ID'          => $blog_id,
                                'BLOG_INTEREST_ID' => $item
                            )
                        );
                        db::sql_query( 'UPDATE BLOG_INTEREST SET RATING = RATING+1 WHERE BLOG_INTEREST_ID = :interest', array('interest'=>$item) );
                    }
                }
            }

            // флаги отображения полей обрабатываем (сначала все удаляем, потом пишем новый расклад)
            db::delete_record( 'BLOG_SV_BLOG_FIELD', array('BLOG_ID'=>$blog_id) );
            if ( is_array($this->q_param['SHOW_FIELDS']) && count($this->q_param['SHOW_FIELDS'])>0 ){
                foreach ( $this->q_param['SHOW_FIELDS'] as $item ){
                    db::insert_record( 'BLOG_SV_BLOG_FIELD', 
                        array(
                            'BLOG_ID'       => $blog_id,
                            'BLOG_FIELD_ID' => $item
                        )
                    );
                }
            }
        }
        $blog = $this->get_current_user_info();
        $this->authorization->setCookie($blog['BLOG_ID'], $this->authorization->getBlogKey($blog['BLOG_ID'],$blog['EMAIL'],$blog['PASSWORD_MD5']), false);
        Header('Location: '.$_SERVER['REQUEST_URI']);
        exit();
    }

    $blog = $this->get_user_info($blog_id);
    if ( empty($blog) ){
        return false;
    }

    $this->tpl->assign('BLOG_DATE', lib::unpack_date($blog['BLOG_DATE'], 'long'));
    $this->tpl->assign('BIRTHDATE', lib::unpack_date($blog['BIRTHDATE'], 'short'));
    foreach ( array('TITLE','NAME','EMAIL','FIO','ICQ','SKYPE','HOMEPAGE','ABOUT') as $item_field ){
        $this->tpl->assign($item_field, htmlspecialchars($blog[$item_field]));
    }

    if ($is_community){
        $this->tpl->assign('MEMBERSHIP'.$blog['MEMBERSHIP'], 1);
        $this->tpl->assign('POSTLEVEL'.$blog['POSTLEVEL'], 1);
        $this->tpl->assign('MODERATION'.$blog['MODERATION'], 1);
    }

    if (!$is_community){
        $BIRTHDATE_FORMAT_SELECT = array();
        array_push($BIRTHDATE_FORMAT_SELECT, ($blog["BIRTHDATE_FORMAT"] == '1') ? array("BIRTHDATE_FORMAT_VALUE"=>'1', "SELECTED"=>'selected', "BIRTHDATE_FORMAT_NAME"=>'ДД.ММ.ГГГГ') : array("BIRTHDATE_FORMAT_VALUE"=>'1', "BIRTHDATE_FORMAT_NAME"=>'ДД.ММ.ГГГГ'));
        array_push($BIRTHDATE_FORMAT_SELECT, ($blog["BIRTHDATE_FORMAT"] == '2') ? array("BIRTHDATE_FORMAT_VALUE"=>'2', "SELECTED"=>'selected', "BIRTHDATE_FORMAT_NAME"=>'ДД.ММ') : array("BIRTHDATE_FORMAT_VALUE"=>'2', "BIRTHDATE_FORMAT_NAME"=>'ДД.ММ'));
        array_push($BIRTHDATE_FORMAT_SELECT, ($blog["BIRTHDATE_FORMAT"] == '3') ? array("BIRTHDATE_FORMAT_VALUE"=>'3', "SELECTED"=>'selected', "BIRTHDATE_FORMAT_NAME"=>'ГГГГ') : array("BIRTHDATE_FORMAT_VALUE"=>'3', "BIRTHDATE_FORMAT_NAME"=>'ГГГГ'));
        $this->tpl->assign("BIRTHDATE_FORMAT", $BIRTHDATE_FORMAT_SELECT);

        $SEX_SELECT = array();
        array_push($SEX_SELECT, ($blog["SEX"] == '1') ? array("SEX_VALUE"=>'1', "SELECTED"=>'selected', "SEX_NAME"=>'не указан') : array("SEX_VALUE"=>'1', "SEX_NAME"=>'не указан'));
        array_push($SEX_SELECT, ($blog["SEX"] == '2') ? array("SEX_VALUE"=>'2', "SELECTED"=>'selected', "SEX_NAME"=>'мужской') : array("SEX_VALUE"=>'2', "SEX_NAME"=>'мужской'));
        array_push($SEX_SELECT, ($blog["SEX"] == '3') ? array("SEX_VALUE"=>'3', "SELECTED"=>'selected', "SEX_NAME"=>'женский') : array("SEX_VALUE"=>'3', "SEX_NAME"=>'женский'));
        $this->tpl->assign("SEX", $SEX_SELECT);
    }

    $sql_result_set_headings = db::sql_select( 'SELECT BLOG_COUNTRY_ID as BLOG_COUNTRY_ID, TITLE As BLOG_COUNTRY_NAME FROM BLOG_COUNTRY ORDER BY BLOG_COUNTRY_NAME' );
    if ( count($sql_result_set_headings) > 0 ){
        $BLOG_COUNTRY_BLOG_COUNTRY_ID = array();
        foreach($sql_result_set_headings as $current_row) {
            if ($current_row['BLOG_COUNTRY_ID']==$blog['BLOG_COUNTRY_ID']){
                $current_row['SELECTED'] = ' selected ';
            }
            $BLOG_COUNTRY_BLOG_COUNTRY_ID[] = $current_row;
        }
        $this->tpl->assign('BLOG_COUNTRY_BLOG_COUNTRY_ID', $BLOG_COUNTRY_BLOG_COUNTRY_ID);
    }

    $sql_result_set_headings = db::sql_select( 'SELECT BLOG_CITY_ID as BLOG_CITY_ID, TITLE As BLOG_CITY_NAME, BLOG_COUNTRY_ID FROM BLOG_CITY ORDER BY BLOG_CITY_NAME' );
    if (count($sql_result_set_headings) > 0) {
        $BLOG_CITY_BLOG_CITY_ID = array(); 
        foreach($sql_result_set_headings as $current_row) {
            if ($current_row['BLOG_COUNTRY_ID']==$blog['BLOG_COUNTRY_ID']){
                $current_row['SHOW'] = 1;
            }
            if ($current_row['BLOG_CITY_ID']==$blog['BLOG_CITY_ID']){
                $current_row['SELECTED'] = ' selected ';
            }
            $BLOG_CITY_BLOG_CITY_ID[] = $current_row;
        }
        $this->tpl->assign('BLOG_CITY_BLOG_CITY_ID', $BLOG_CITY_BLOG_CITY_ID);
    }

    $POSTS_ON_PAGE_SELECT = array();
    array_push($POSTS_ON_PAGE_SELECT, ($blog['POSTS_ON_PAGE'] == '1') ? array('POSTS_ON_PAGE_VALUE'=>'1', 'SELECTED'=>'selected', 'POSTS_ON_PAGE_NAME'=>'20') : array('POSTS_ON_PAGE_VALUE'=>'1', 'POSTS_ON_PAGE_NAME'=>'20'));
    array_push($POSTS_ON_PAGE_SELECT, ($blog['POSTS_ON_PAGE'] == '2') ? array('POSTS_ON_PAGE_VALUE'=>'2', 'SELECTED'=>'selected', 'POSTS_ON_PAGE_NAME'=>'30') : array('POSTS_ON_PAGE_VALUE'=>'2', 'POSTS_ON_PAGE_NAME'=>'30'));
    array_push($POSTS_ON_PAGE_SELECT, ($blog['POSTS_ON_PAGE'] == '3') ? array('POSTS_ON_PAGE_VALUE'=>'3', 'SELECTED'=>'selected', 'POSTS_ON_PAGE_NAME'=>'40') : array('POSTS_ON_PAGE_VALUE'=>'3', 'POSTS_ON_PAGE_NAME'=>'40'));
    array_push($POSTS_ON_PAGE_SELECT, ($blog['POSTS_ON_PAGE'] == '4') ? array('POSTS_ON_PAGE_VALUE'=>'4', 'SELECTED'=>'selected', 'POSTS_ON_PAGE_NAME'=>'50') : array('POSTS_ON_PAGE_VALUE'=>'4', 'POSTS_ON_PAGE_NAME'=>'50'));
    $this->tpl->assign('POSTS_ON_PAGE', $POSTS_ON_PAGE_SELECT);

    $sql_result_set_headings = db::sql_select( '
        SELECT DISTINCT I.BLOG_INTEREST_ID, I.TITLE As BLOG_INTEREST_NAME 
        FROM BLOG_INTEREST I 
            LEFT JOIN BLOG_SV_BLOG_INTEREST BI on (I.BLOG_INTEREST_ID = BI.BLOG_INTEREST_ID) 
        WHERE BI.BLOG_ID = :blog
        ORDER BY BLOG_INTEREST_NAME',
        array('blog'=>$blog_id)
    );
    $interests = array();
    if (count($sql_result_set_headings) > 0) {
        foreach ( $sql_result_set_headings as $current_row ){
            $interests[] = $current_row['BLOG_INTEREST_NAME'];
        }
    }
    $this->tpl->assign('INTEREST', implode(', ', $interests));
    
    // проверяем для всех полей возможность отображения и пишем им значение
    $fields_arr = db::sql_select( 'select * from BLOG_FIELD' );
    if ( count($fields_arr)>0 ){
        foreach ( $fields_arr as $field ){
            $this->tpl->assign('FIELD_'.$field['FIELD_NAME'].'_ID', $field['BLOG_FIELD_ID']);
        }
    }
    $fields_arr = db::sql_select( '
        SELECT BF.* 
        FROM BLOG_SV_BLOG_FIELD BBF, BLOG_FIELD BF 
        WHERE BF.BLOG_FIELD_ID = BBF.BLOG_FIELD_ID AND BBF.BLOG_ID = :blog',
        array('blog'=>$blog_id)
    );
    if ( count($fields_arr)>0 ){
        foreach ( $fields_arr as $field ){
            $this->tpl->assign('FIELD_'.$field['FIELD_NAME'].'_CHECKED', 1);
        }
    }
}





/**
 * Функция обработки Изображений пользователя и сообщества 
 * Для вариантов использования :
 *    Редактирование профиля - Изображения
 *    Управление сообществами - Изображения
 */
protected function managerImagesCommunity() {
    $this->managerImages( true );
}
protected function managerImagesPerson() {
    $this->managerImages( false );
}
protected function managerImages( $is_community=false ) {
    $this->tpl->assign('MODE_IMAGES', 1);
    $blog_id = $is_community ? $this->q_param['COMMUNITY_ID'] : ( !empty($this->q_param['BLOG_ID']) ? $this->q_param['BLOG_ID'] : $this->authorization->blog_id );
    if ( isset($this->q_param['action']) ){
        //сохранение изменений в БД
        if ( $this->q_param['action']=='save' ){
            if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id && $this->q_param['BLOG_IMAGE_ID']>0 ){
                $update_param = array();
                $file_name = 'IMG'.$this->q_param['BLOG_IMAGE_ID'].'IMG_'.$this->env['area_id'].'_file';
                if ( isset($_FILES[$file_name]['name']) && strlen($_FILES[$file_name]['name'])>0 ){
                    if ( $this->isCorrectImage($file_name) ) {
                        $upload_file = upload::upload_file( $_FILES['IMG'.$this->q_param['BLOG_IMAGE_ID'].'IMG_'.$this->env['area_id'].'_file'], params::$params['common_htdocs_server']['value'].$this->upload_dir_image, true, false );
                        $upload_file = str_replace( params::$params['common_htdocs_server']['value'], params::$params['common_htdocs_http']['value'], $upload_file);
                        $update_param['IMG'] = $upload_file;
                    }else{
                        $is_error = 1;
                    }
                }
                if ( !$is_error ){
                    $is_default = ($this->q_param['IS_DEFAULT']==$this->q_param['BLOG_IMAGE_ID']) ? 1 : 0;
                    if ( $is_default ){
                        db::update_record( 'BLOG_IMAGE', 
                            array( 'IS_DEFAULT' => 0 ), '',
                            array( 'BLOG_ID'=>$blog_id, 'IS_DEFAULT'=>1 )
                        );
                    }
                    $update_param['TITLE'] = $this->q_param['TITLE'.$this->q_param['BLOG_IMAGE_ID'].'TITLE'];
                    $update_param['IS_DEFAULT'] = $is_default;
                    db::update_record( 'BLOG_IMAGE',
                        $update_param, '',
                        array( 'BLOG_IMAGE_ID'=>$this->q_param['BLOG_IMAGE_ID'] )
                    );
                }
            }
        }elseif ( $this->q_param['action']=='add' ){
            if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id ){
                $file_name = 'IMG_'.$this->env['area_id'].'_file';
                if ( isset($_FILES[$file_name]['name']) && strlen($_FILES[$file_name]['name'])>0 ){
                    if ( $this->isCorrectImage($file_name) ){
                        $is_default = ($this->q_param['IS_DEFAULT']==0) ? 1 : 0;
                        if ($is_default){
                            db::update_record( 'BLOG_IMAGE', 
                                array( 'IS_DEFAULT' => 0 ), '',
                                array( 'BLOG_ID' => $blog_id, 'IS_DEFAULT' => 1 )
                            );
                        }
                        $upload_file = upload::upload_file( $_FILES['IMG_'.$this->env['area_id'].'_file'], params::$params['common_htdocs_server']['value'].$this->upload_dir_image, true, false );
                        $upload_file = str_replace( params::$params['common_htdocs_server']['value'], params::$params['common_htdocs_http']['value'], $upload_file);
                        db::insert_record( 'BLOG_IMAGE', 
                            array(
                                'BLOG_ID'    => $blog_id,
                                'IMAGE_DATE' => lib::pack_date(date("d.m.Y H:i"),'long'),
                                'TITLE'      => $this->q_param['TITLE'],
                                'IMG'        => $upload_file,
                                'IS_DEFAULT' => $is_default
                            )
                        );
                    }else{
                        $is_error = 1;
                    }
                }else{
                    $this->tpl->assign('ERROR_IMAGE_EMPTY', 1); //файл не указан
                    $is_error = 1;
                }
            }
        }elseif ( $this->q_param['action']=='del' ){
            if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id && $this->q_param['BLOG_IMAGE_ID']>0 ) {
                db::delete_record( 'BLOG_IMAGE', array('BLOG_IMAGE_ID'=>$this->q_param['BLOG_IMAGE_ID']) );
            }
        }
        if ( !$is_error ){
            Header('Location: '.$_SERVER['REQUEST_URI']);
            exit();
        }
    }

    $sql_result_set = db::sql_select( 'SELECT * FROM BLOG_IMAGE WHERE BLOG_ID = :blog ORDER BY IS_DEFAULT DESC, TITLE ASC', array('blog'=>$blog_id) );
    if ( count($sql_result_set)>0 ){
        $BLOG_IMAGES = array();
        while ( $current_row=current($sql_result_set) ){
            $current_row['IMAGE_DATE'] = lib::unpack_date($current_row['IMAGE_DATE'],'long');
            $BLOG_IMAGES[] = $current_row;
            next($sql_result_set); 
        } 
        $this->tpl->assign('BLOG_IMAGES', $BLOG_IMAGES);
    }
}

/**
 * Проверяем, удовлетворяет ли загружаемый рисунок требованиям
 */
protected function isCorrectImage($file_name) {
    if ( !empty($_FILES[$file_name]['error']) ){
        switch ( $_FILES[$file_name]['error'] ){
            case 1:
                $this->tpl->assign('ERROR_IMAGE_7', 1); //размер файла больше, чем позволено настройками системы
                break;
            case 2:
                $this->tpl->assign('ERROR_IMAGE_7', 1); //размер файла больше, чем позволено настройками системы
                break;
            case 3:
                $this->tpl->assign('ERROR_IMAGE_8', 1); //файл был загружен не полностью
                break;
            case 4:
                $this->tpl->assign('ERROR_IMAGE_9', 1); //загруженный файл отсутствует
                break;
        }
        return false;
    }
    if ( strpos($_FILES[$file_name]['type'], 'image') === false ){
        $this->tpl->assign('ERROR_IMAGE_1', 1); //файл не является файлом изображения
        return false;
    }
    $file_params = getimagesize($_FILES[$file_name]['tmp_name']);
    if ( empty($file_params) ){
        $this->tpl->assign('ERROR_IMAGE_1', 1); //файл не является файлом изображения
        return false;
    }
    if ( !in_array($file_params[2],array(1,2,3)) ){
        $this->tpl->assign('ERROR_IMAGE_2', 1); //не поддерживаемый формат изображения
        return false;
    }
    $sql_result_set = db::sql_select( 'SELECT * FROM BLOG_IMAGE_SETTINGS ORDER BY BLOG_IMAGE_SETTINGS_ID ASC' );
    if ( count($sql_result_set)>0 ){
        $count_sel = db::sql_select( 'SELECT count(BLOG_IMAGE_ID) as COUNT_IMAGE FROM BLOG_IMAGE WHERE BLOG_ID = :blog', array('blog'=>$this->authorization->blog_id) );
        if ( count($count_sel)>0 && $count_sel[0]['COUNT_IMAGE']>=$sql_result_set[0]['TOTAL'] ){
            $this->tpl->assign('ERROR_IMAGE_3', 1); //превышено макс. количество файлов
            return false;
        }
        $_kw = $file_params[0]/$sql_result_set[0]['WIDTH'];
        $_kh = $file_params[1]/$sql_result_set[0]['HEIGHT'];
        $_k = ( $_kw>$_kh ) ? $_kw : $_kh;
        if ( $_k>1 ){
            // уменьшаем изображение в масштабе $_k
            if ( $file_params[2]==1 ){      //GIF
                $im_src = @imagecreatefromgif($_FILES[$file_name]['tmp_name']);
            }elseif ( $file_params[2]==2 ){ //JPG
                $im_src = @imagecreatefromjpeg($_FILES[$file_name]['tmp_name']);
            }elseif ( $file_params[2]==3 ){ //PNG
                $im_src = @imagecreatefrompng($_FILES[$file_name]['tmp_name']);
            }
            if ($im_src){
                $im_dst = imagecreatetruecolor(floor($file_params[0]/$_k),floor($file_params[1]/$_k));
                imagecopyresized($im_dst,$im_src, 0,0, 0,0, floor($file_params[0]/$_k),floor($file_params[1]/$_k), $file_params[0],$file_params[1]);
                if ( $file_params[2]==1 ){      //GIF
                    imagegif($im_dst,$_FILES[$file_name]['tmp_name']);
                }elseif ( $file_params[2]==2 ){ //JPG
                    imagejpeg($im_dst,$_FILES[$file_name]['tmp_name']);
                }elseif ( $file_params[2]==3 ){ //PNG
                    imagepng($im_dst,$_FILES[$file_name]['tmp_name']);
                }
                imagedestroy($im_dst);
                imagedestroy($im_src);
            }
            // проверяем соответствие изображения параметрам
            $file_params = getimagesize($_FILES[$file_name]['tmp_name']);
            if ( filesize($_FILES[$file_name]['tmp_name']) > $sql_result_set[0]['SIZE']*1024 ){
                $this->tpl->assign('ERROR_IMAGE_4', 1); //превышен размер файла
                return false;
            }
            if ( $file_params[0]>$sql_result_set[0]['WIDTH'] ){
                $this->tpl->assign('ERROR_IMAGE_5', 1); //превышена ширина
                return false;
            }
            if ( $file_params[1]>$sql_result_set[0]['HEIGHT'] ){
                $this->tpl->assign('ERROR_IMAGE_6', 1); //превышена высота
                return false;
            }
        }
    }
    return true;
}





/** 
 * Функция обработки Тегов пользователя и сообщества 
 * Для вариантов использования :
 *    Редактирование профиля - Теги
 *    Управление сообществами - Теги
 */
protected function managerTagsCommunity() {
    $this->managerTags( true );
}
protected function managerTagsPerson() {
    $this->managerTags( false );
}
protected function managerTags( $is_community=false ) {
    $this->tpl->assign('MODE_TAGS', 1);
    $blog_id = $is_community ? $this->q_param['COMMUNITY_ID'] : ( !empty($this->q_param['BLOG_ID']) ? $this->q_param['BLOG_ID'] : $this->authorization->blog_id );
    $blog_arr = $this->get_user_info($blog_id);
    if ( isset($this->q_param['action']) ){
        //сохранение изменений в БД
        if ( $this->q_param['action']=='save' ){
            if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id && $this->q_param['BLOG_TAG_ID']>0 && (trim($this->q_param['TITLE']) != '') ) {
                db::update_record( 'BLOG_TAG', 
                    array( 'TITLE' => $this->q_param['TITLE'] ), '',
                    array( 'BLOG_TAG_ID' => $this->q_param['BLOG_TAG_ID'] )
                );
            }
        }elseif ( $this->q_param['action']=='del' ){
            if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id && $this->q_param['BLOG_TAG_ID']>0 ) {
                db::delete_record( 'BLOG_POST_SV_BLOG_TAG', array('BLOG_TAG_ID'=>$this->q_param['BLOG_TAG_ID']) );
                db::delete_record( 'BLOG_TAG', array('BLOG_TAG_ID'=>$this->q_param['BLOG_TAG_ID']) );
            }
        }elseif ( $this->q_param['action']=='add' ){
            if ( is_numeric($this->q_param['BLOG_ID']) && $this->q_param['BLOG_ID']==$this->authorization->blog_id && (trim($this->q_param['TITLE']) != '') ) {
                db::insert_record( 'BLOG_TAG', 
                    array( 
                        'BLOG_ID' => $blog_id, 
                        'TITLE'   => $this->q_param['TITLE'], 
                        'RATING'  => 0
                    )
                );
            }
        }
        Header('Location: '.$_SERVER['REQUEST_URI']);
        exit();
    }

    $order_field_array = array('ta'=>'TITLE ASC','td'=>'TITLE DESC','ra'=>'RATING ASC','rd'=>'RATING DESC','0'=>'');
    $_order = ' ORDER BY '.( (isset($this->q_param['ord']) && isset($order_field_array[$this->q_param['ord']]) ) ? $order_field_array[$this->q_param['ord']] : $order_field_array['ta']);
    $this->tpl->assign(strtoupper('ORD_'.$this->q_param['ord']), 1);
    
    $this->tpl->assign('PATH_TO_BLOG', $this->get_blog_path($blog_arr['TITLE']).'/index.php');

    $sql_result_set = db::sql_select( 'SELECT * FROM BLOG_TAG WHERE BLOG_ID = :blog '.$_order, array('blog'=>$blog_id) );
    if ( count($sql_result_set)>0 ){
        $BLOG_TAGS = array();
        while ( $current_row=current($sql_result_set) ){
            unset($current_row['BLOG_ID']);
            $BLOG_TAGS[] = $current_row;
            next($sql_result_set); 
        } 
        $this->tpl->assign('BLOG_TAGS', $BLOG_TAGS);
    }
}





/**
 * Вариант использования "Добавление записи"
 */
protected function modeAddPost() {
    $user = $this->get_current_user_info();
    $this->tpl->assign('BLOG_NAME', $user['NAME']);
    $this->tpl->assign('BLOG_USER', $user['TITLE']);

    if ( isset($this->q_param['save']) || isset($this->q_param['public']) ){
        //сохранение записи в БД
        $is_public = isset($this->q_param['public']) ? 1 : 0;
        $is_confirm = 0;
        if ( $is_public ){
            $blog = $this->get_user_info($this->q_param['BLOG_ID']);
            $is_confirm = ( !empty($blog) && $blog['IS_COMMUNITY']==1 && $blog['MODERATION']==2 && !$this->isMainBlog() && !$this->isModerator($this->q_param['BLOG_ID']) ) ? 1 : 0;
        }
        if ( $this->q_param['action']=='add' ){
            db::insert_record( 'BLOG_POST', 
                array(
                    'ADDED_DATE'  => lib::pack_date($this->q_param['ADDED_DATE'],'long'),
                    'CHANGE_DATE' => lib::pack_date($this->q_param['ADDED_DATE'],'long'),
                    'AUTHOR_ID'   => $this->authorization->blog_id, 
                    'BLOG_ID'     => $this->q_param['BLOG_ID'],
                    'BLOG_IMAGE_ID' => $this->q_param['BLOG_IMAGE_ID'],
                    'TITLE'       => $this->q_param['TITLE'],
                    'BODY'        => $this->q_param['BODY'],
                    'BLOG_MOOD_ID' => $this->q_param['BLOG_MOOD_ID'],
                    'CURRENT_MUSIC' => $this->q_param['CURRENT_MUSIC'], 
                    'IS_PUBLIC'   => $is_public,
                    'IS_CONFIRM'  => $is_confirm,
                    'IS_DISABLECOMMENT' => isset($this->q_param['IS_DISABLECOMMENT']) ? 1 : 0, 
                    'ACCESS'      => $this->q_param['ACCESS']
                )
            );
            $post_id = db::last_insert_id( 'BLOG_POST_SEQ' );
        }elseif ( $this->q_param['action']=='edit' ){
            if ( is_numeric($this->q_param['BLOG_POST_ID']) ){
                db::update_record( 'BLOG_POST', 
                    array( 
                        'CHANGE_DATE' => lib::pack_date(date("d.m.Y H:i"),'long'), 
                        'AUTHOR_ID'   => $this->authorization->blog_id,
                        'BLOG_ID'     => $this->q_param['BLOG_ID'],
                        'BLOG_IMAGE_ID' => $this->q_param['BLOG_IMAGE_ID'],
                        'TITLE'       => $this->q_param['TITLE'],
                        'BODY'        => $this->q_param['BODY'],
                        'BLOG_MOOD_ID' => $this->q_param['BLOG_MOOD_ID'], 
                        'CURRENT_MUSIC' => $this->q_param['CURRENT_MUSIC'], 
                        'IS_PUBLIC'   => $is_public, 
                        'IS_CONFIRM'  => $is_confirm,
                        'IS_DISABLECOMMENT' => isset($this->q_param['IS_DISABLECOMMENT']) ? 1 : 0,
                        'ACCESS'      => $this->q_param['ACCESS']
                    ), '',
                    array( 'BLOG_POST_ID' => intval($this->q_param['BLOG_POST_ID']) )
                );
                $post_id = intval($this->q_param['BLOG_POST_ID']);
            }
        }
        if ( $is_confirm ){
            $this->sendMsgWhenInviteList( $this->q_param['BLOG_ID'], 'confirm' );
        }
        $this->q_param['post_id'] = $post_id;
        // группы доступа обрабатываем (сначала все удаляем, потом пишем новый расклад)
        db::delete_record( 'BLOG_POST_SV_BLOG_FRIENDGROUP', array('BLOG_POST_ID'=>$post_id) );
        if ( $this->q_param['ACCESS']==4 ){
            if ( is_array($this->q_param['access_group']) && count($this->q_param['access_group'])>0 ){
                foreach ( $this->q_param['access_group'] as $item ){
                    db::insert_record( 'BLOG_POST_SV_BLOG_FRIENDGROUP', 
                        array(
                            'BLOG_POST_ID' => $post_id, 
                            'BLOG_FRIENDGROUP_ID' => $item
                        )
                    );    
                }
            }
        }

        // теги обрабатываем (сначала все удаляем, потом пишем новый расклад)
        // создаём новые теги, если заполнено поле "Новые теги"
        $this->q_param['NEW_TAGS'] = trim($this->q_param['NEW_TAGS']);
        if ( !empty($this->q_param['NEW_TAGS']) ){
            $arr_new_tag = explode(',', $this->q_param['NEW_TAGS']);
            foreach ( $arr_new_tag as $value ){
                $value = trim($value);
                if (!empty($value)){
                    $tags_sel = db::sql_select( '
                        SELECT BLOG_TAG_ID 
                        FROM BLOG_TAG 
                        WHERE BLOG_ID = :blog AND TITLE = :title 
                        ORDER BY BLOG_TAG_ID DESC',
                        array('blog'=>$this->authorization->blog_id, 'title'=>$value)
                    );
                    if ( count($tags_sel)>0 ){
                        $this->q_param['tags'.$this->q_param['BLOG_ID'].'_'][] = $tags_sel[0]['BLOG_TAG_ID'];
                    }else{
                        db::insert_record( 'BLOG_TAG', 
                            array(
                                'BLOG_ID' => $this->q_param['BLOG_ID'],
                                'TITLE'   => $value, 
                                'RATING'  => 0
                            )
                        );
                        $this->q_param['tags'.$this->q_param['BLOG_ID'].'_'][] = db::last_insert_id( 'BLOG_TAG_SEQ' );
                    }
                }
            }
        }
        db::sql_query( '
            UPDATE BLOG_TAG SET RATING = RATING-1 
            WHERE RATING>0 AND BLOG_TAG_ID IN (SELECT BLOG_TAG_ID FROM BLOG_POST_SV_BLOG_TAG WHERE BLOG_POST_ID = :post)',
            array('post'=>$post_id)
        );
        db::delete_record( 'BLOG_POST_SV_BLOG_TAG', array('BLOG_POST_ID'=>$post_id) );
        if ( is_array($this->q_param['tags'.$this->q_param['BLOG_ID'].'_']) && count($this->q_param['tags'.$this->q_param['BLOG_ID'].'_'])>0 ){
            $this->q_param['tags'.$this->q_param['BLOG_ID'].'_'] = array_unique($this->q_param['tags'.$this->q_param['BLOG_ID'].'_']);
            foreach ( $this->q_param['tags'.$this->q_param['BLOG_ID'].'_'] as $item ){
                db::insert_record( 'BLOG_POST_SV_BLOG_TAG', 
                    array( 
                        'BLOG_POST_ID' => $post_id, 
                        'BLOG_TAG_ID'  => $item
                    )
                );    
                db::sql_query( 'UPDATE BLOG_TAG SET RATING = RATING+1 WHERE BLOG_TAG_ID = :tag', array('tag'=>$item) );
            }
        }
        //переход на блог в случае публикации
        if ($is_public){
            Header('Location: '.$this->get_blog_path($blog['TITLE']));
            exit();
        }
    }

    if ( isset($this->q_param['post_id']) ){
        //загружаем в шаблон данные
        $BLOG_POST_ID = intval($this->q_param['post_id']);
        $sql_result_set = db::sql_select( '
            SELECT * 
            FROM BLOG_POST 
            WHERE BLOG_POST_ID = :post and AUTHOR_ID = :author',
            array('post'=>$BLOG_POST_ID, 'author'=>$this->authorization->blog_id)
        );
        if ( count($sql_result_set)>0 ){
            $BLOG_ID = $sql_result_set[0]['BLOG_ID'];
            $ADDED_DATE = $sql_result_set[0]['ADDED_DATE'];
            $CHANGE_DATE = $sql_result_set[0]['CHANGE_DATE'];
            $AUTHOR_ID = $sql_result_set[0]['AUTHOR_ID'];
            $BLOG_IMAGE_ID = $sql_result_set[0]['BLOG_IMAGE_ID'];
            $TITLE = $sql_result_set[0]['TITLE'];
            $BODY = $sql_result_set[0]['BODY'];
            $BLOG_MOOD_ID = $sql_result_set[0]['BLOG_MOOD_ID'];
            $CURRENT_MUSIC = $sql_result_set[0]['CURRENT_MUSIC'];
            $IS_PUBLIC = $sql_result_set[0]['IS_PUBLIC'];
            $IS_DISABLECOMMENT = $sql_result_set[0]['IS_DISABLECOMMENT'];
            $ACCESS = $sql_result_set[0]['ACCESS'];

            $this->tpl->assign('EDIT', 1);
        }
    }

    $sql_result_set_headings = db::sql_select( '
        SELECT B.BLOG_ID, B.TITLE 
        FROM BLOG_FRIEND BF, BLOG B 
        WHERE BF.OWNER_ID = B.BLOG_ID and BF.FRIEND_ID = :friend and BF.LEVEL = \'2\' 
            and (BF.IS_INVITE = \'0\' OR BF.IS_INVITE IS NULL) and (BF.IS_INQUIRY = \'0\' OR BF.IS_INQUIRY IS NULL)
        ORDER BY B.TITLE ASC',
        array('friend'=>$this->authorization->blog_id)
    );
    array_unshift($sql_result_set_headings, array( 'BLOG_ID'=>$this->authorization->blog_id, 'TITLE'=>$user['TITLE'] ));
    if ( count($sql_result_set_headings)>0 ){
        $BLOG_BLOG_ID = array();
        foreach ( $sql_result_set_headings as $current_row ){
            $tags_sel = db::sql_select( '
                SELECT DISTINCT T.BLOG_ID, T.BLOG_TAG_ID, T.TITLE As BLOG_TAG_NAME, PT.BLOG_POST_ID 
                FROM BLOG_TAG T 
                    LEFT JOIN BLOG_POST_SV_BLOG_TAG PT on (T.BLOG_TAG_ID = PT.BLOG_TAG_ID and PT.BLOG_POST_ID = :post) 
                WHERE T.BLOG_ID = :blog 
                ORDER BY BLOG_TAG_NAME',
                array('post'=>intval($this->q_param['post_id']), 'blog'=>$current_row['BLOG_ID'])
            );
            if ( count($tags_sel)>0 ){
                foreach ( $tags_sel as $v=>$tag ){
                    if (!empty($tag['BLOG_POST_ID']) && ($tag['BLOG_POST_ID']==$BLOG_POST_ID)){
                        $tags_sel[$v]['CHECKED'] = 1;
                    }
                }
                $current_row['BLOG_TAGS'] = $tags_sel;
            }
            if ( $current_row['BLOG_ID']==$BLOG_ID ){
                $current_row['SELECTED'] = ' selected ';
            }
            $BLOG_BLOG_ID[] = $current_row;
        }
        $this->tpl->assign('BLOG_BLOG_ID', $BLOG_BLOG_ID); 
    }

    $this->tpl->assign('BLOG_ID_FOR_JS', $this->authorization->blog_id); 
    $this->tpl->assign('IS_DISABLECOMMENT', $IS_DISABLECOMMENT); 
    $this->tpl->assign('ADDED_DATE', ($BLOG_POST_ID == 0 ? date("d.m.Y H:i") : lib::unpack_date($ADDED_DATE, 'long'))); 

    $sql_result_set_headings = db::sql_select( '
        SELECT BLOG_IMAGE_ID as BLOG_IMAGE_ID, TITLE As BLOG_IMAGE_NAME, IMG, IS_DEFAULT 
        FROM BLOG_IMAGE 
        WHERE BLOG_ID = :blog 
        ORDER BY BLOG_IMAGE_NAME',
        array('blog'=>$this->authorization->blog_id)
    );
    if ( count($sql_result_set_headings) > 0 ){
        $BLOG_IMAGE_BLOG_IMAGE_ID = array();
        foreach($sql_result_set_headings as $current_row) {
            if ( ($current_row['BLOG_IMAGE_ID']==$BLOG_IMAGE_ID) || (!isset($this->q_param['post_id']) && $current_row['IS_DEFAULT']==1) ){
                $current_row['SELECTED'] = ' selected ';
            }
            $BLOG_IMAGE_BLOG_IMAGE_ID[] = $current_row;
        }
        $this->tpl->assign('BLOG_IMAGE_BLOG_IMAGE_ID', $BLOG_IMAGE_BLOG_IMAGE_ID); 
    }

    $this->tpl->assign('TITLE', ($BLOG_POST_ID == 0 ? '' : htmlspecialchars($TITLE))); 
    $this->tpl->assign('BODY', ($BLOG_POST_ID == 0 ? '' : htmlspecialchars($BODY))); 

    $sql_result_set_headings = db::sql_select( 'SELECT BLOG_MOOD_ID as BLOG_MOOD_ID, TITLE As BLOG_MOOD_NAME, IMAGE FROM BLOG_MOOD ORDER BY BLOG_MOOD_NAME' );
    if (count($sql_result_set_headings) > 0) {
        $BLOG_MOOD_BLOG_MOOD_ID = array();
        foreach($sql_result_set_headings as $current_row) {
            if ($current_row['BLOG_MOOD_ID']==$BLOG_MOOD_ID){
                $current_row['SELECTED'] = ' selected ';
            }
            $BLOG_MOOD_BLOG_MOOD_ID[] = $current_row;
        }
        $this->tpl->assign('BLOG_MOOD_BLOG_MOOD_ID', $BLOG_MOOD_BLOG_MOOD_ID); 
    }

    $this->tpl->assign('CURRENT_MUSIC', ($BLOG_POST_ID == 0 ? '' : htmlspecialchars($CURRENT_MUSIC))); 

    $ACCESS_SELECT = array();
    array_push($ACCESS_SELECT, ($ACCESS == '1') ? array('ACCESS_VALUE'=>'1', 'SELECTED'=>'selected', 'ACCESS_NAME'=>'для всех') : array('ACCESS_VALUE'=>'1', 'ACCESS_NAME'=>'для всех'));
    array_push($ACCESS_SELECT, ($ACCESS == '2') ? array('ACCESS_VALUE'=>'2', 'SELECTED'=>'selected', 'ACCESS_NAME'=>'для друзей') : array('ACCESS_VALUE'=>'2', 'ACCESS_NAME'=>'для друзей'));
    array_push($ACCESS_SELECT, ($ACCESS == '3') ? array('ACCESS_VALUE'=>'3', 'SELECTED'=>'selected', 'ACCESS_NAME'=>'личное') : array('ACCESS_VALUE'=>'3', 'ACCESS_NAME'=>'личное'));
    array_push($ACCESS_SELECT, ($ACCESS == '4') ? array('ACCESS_VALUE'=>'4', 'SELECTED'=>'selected', 'ACCESS_NAME'=>'выборочно') : array('ACCESS_VALUE'=>'4', 'ACCESS_NAME'=>'выборочно'));
    $this->tpl->assign('ACCESS', $ACCESS_SELECT);

    if ( isset($this->q_param['post_id']) ){
        $sql_result_set_headings = db::sql_select( '
            SELECT FG.BLOG_FRIENDGROUP_ID, FG.TITLE As BLOG_FRIENDGROUP_NAME, PFG.BLOG_POST_ID 
            FROM BLOG_FRIENDGROUP FG 
                LEFT JOIN BLOG_POST_SV_BLOG_FRIENDGROUP PFG on (FG.BLOG_FRIENDGROUP_ID = PFG.BLOG_FRIENDGROUP_ID and PFG.BLOG_POST_ID = :post) 
            WHERE FG.BLOG_ID = :blog
            ORDER BY BLOG_FRIENDGROUP_NAME',
            array('post'=>intval($this->q_param['post_id']), 'blog'=>$this->authorization->blog_id)
        );
    }else{
        $sql_result_set_headings = db::sql_select( '
            SELECT FG.BLOG_FRIENDGROUP_ID, FG.TITLE As BLOG_FRIENDGROUP_NAME 
            FROM BLOG_FRIENDGROUP FG 
            WHERE FG.BLOG_ID = :blog 
            ORDER BY BLOG_FRIENDGROUP_NAME',
            array('blog'=>$this->authorization->blog_id)
        );
    }
    if ( count($sql_result_set_headings) > 0 ){
        $BLOG_FRIENDGROUP_BLOG_FRIENDGROUP_ID = array();
        foreach($sql_result_set_headings as $current_row) {
            if (!empty($current_row['BLOG_POST_ID']) && $current_row['BLOG_POST_ID']==$BLOG_POST_ID){
                $current_row['CHECKED'] = 1;
            }
            $BLOG_FRIENDGROUP_BLOG_FRIENDGROUP_ID[] = $current_row;
        }
        $this->tpl->assign('BLOG_FRIENDGROUP_BLOG_FRIENDGROUP_ID', $BLOG_FRIENDGROUP_BLOG_FRIENDGROUP_ID); 
    }

    $this->tpl->assign('BLOG_POST_ID', $BLOG_POST_ID);
}





/**
 * Вариант использования "Блог(расширенный, с формой аутентификации)"
 */
protected function modeAdvBlog() {
    $module_info=array();

    $blogs_auth = module::factory("blogs");
    $env_auth = $this->env;
    $env_auth['area_id'] = 1;
    $view_param_auth = array(
        'view_mode'	=> 'auth',
        'template'	=> $this->view_param['template']
    );
    $blogs_auth->init($env_auth, $view_param_auth, &$module_info);

    $blogs_body = module::factory("blogs");
    $env_body = $this->env;
    $env_body['area_id'] = 4;
    $view_param_body = array(
        'view_mode'	=> 'blog',
        'submode'	=> $this->view_param['submode'],
        'template'	=> $this->view_param['template'],
        'blog_id'	=> $this->view_param['blog_id']
    );
    $blogs_body->init($env_body, $view_param_body, &$module_info);

    $this->tpl->assign( 'AUTH_BODY', $blogs_auth->get_body() );
    $this->tpl->assign( 'BLOG_BODY', $blogs_body->get_body() );
}





/**
 * Вариант использования "Блог"
 */
protected function modeBlog() {
    $this->view_param['blog_id'] = ($this->view_param['blog_id'] ? $this->view_param['blog_id'] : 0);
    $user = $this->get_user_info($this->view_param['blog_id']);
    if ( !empty($user) ){
        $is_community = ($user['IS_COMMUNITY']==1) ? 1 : 0; // флаг сообщества

        $this->tpl->assign( 'INDEX_FILE', substr($_SERVER['SCRIPT_NAME'], strrpos($_SERVER['SCRIPT_NAME'], '/')) );
        // заголовок формы заполняем
        $this->tpl->assign('BLOG_NAME', $user['NAME']);
        $this->tpl->assign('BLOG_USER', $user['TITLE']);
        $this->tpl->assign('BLOG_ID', $this->view_param['blog_id']);

        switch ( $this->view_param['submode'] ){
            case 'profile' :
                $this->modeBlogProfile($user);
                break;
            case 'photogallery' :
                $this->modeBlogPhotoGallery($user);
                break;
            case 'blog' :
                $this->modeBlogBlog($user, false);
                break;
            case 'friendtape' :
                $this->modeBlogBlog($user, true);
                break;
        }
    }else{
        return false;
    }
}


/**
 * Вариант использования "Блог - Блог"
 */
protected function modeBlogBlog($blog_arr, $is_friendtape=false) {
    $this->tpl->assign('IS_COMMUNITY', $is_community = intval($blog_arr['IS_COMMUNITY']));
    if ( $is_friendtape ){
        $this->tpl->assign('IS_FRIENDTAPE', 1);
        // определяем, выводить опубликованные или нет (по умолчению - опубликованные)
        $is_public = 1;
        $is_newpost = 0;
    }else{
        // определяем, выводить опубликованные или нет (по умолчению - опубликованные)
        //$is_public = (isset($this->q_param['no_public']) && $this->q_param['no_public']==1 && ( $this->isMainBlog() || $this->isModerator($blog_arr['BLOG_ID']) ) ) ? 0 : 1;
        $is_public = (isset($this->q_param['no_public']) && $this->q_param['no_public']==1 ) ? 0 : 1;
        $is_newpost = (isset($this->q_param['newpost']) && $this->q_param['newpost']==1 && $this->isModerator($blog_arr['BLOG_ID']) ) ? 1 : 0;
    }
    $this->tpl->assign('IS_PUBLIC', $is_public);
    if ( !$is_community ){
        $is_newpost = '';
    }
    $this->tpl->assign('IS_NEWPOST', $is_newpost);

    // Подготавливаем правильный оффсет для лимита
    $this->from = ( !is_numeric($this->q_param['from']) || intval($this->q_param['from'])<=0 ) ? 0 : intval($this->q_param['from'])-1;
    $this->from *= $this->post_on_page_arr[$blog_arr['POSTS_ON_PAGE']];

    if ( !$is_friendtape ){
        // проверяем наличие неопубликованных постов
        if ( $this->isMainBlog() || $this->isModerator($blog_arr['BLOG_ID']) ){
            $this->tpl->assign('IS_MAIN_BLOG', $is_main_blog=1);
            //$nopublic_posts = db::sql_select( $this->make_sql_for_posts($blog_arr, 'list', 0, '', intval($this->q_param['blog_tag_id']), intval($this->q_param['post_id'])) );
            //if ( count($nopublic_posts)>0 ){
            //    $this->tpl->assign('SHOW_NOPUBLIC_LINK', 1);
            //}
        }
        $nopublic_posts = db::sql_select( $this->make_sql_for_posts($blog_arr, 'list', 0, '', intval($this->q_param['blog_tag_id']), intval($this->q_param['post_id'])) );
        if ( count($nopublic_posts)>0 ){
            $this->tpl->assign('SHOW_NOPUBLIC_LINK', 1);
        }
    
        // проверяем наличие новых (не проверенных модератором) постов в сообществах
        if ( $is_community && $this->isModerator($blog_arr['BLOG_ID']) ){
            //$new_posts = db::sql_select( $this->make_sql_for_posts($blog_arr, 'list', '', 1, intval($this->q_param['blog_tag_id']), intval($this->q_param['post_id'])) );
            $new_posts = db::sql_select( $this->make_sql_for_posts($blog_arr, 'list', 1, 1, intval($this->q_param['blog_tag_id']), intval($this->q_param['post_id'])) );
            if ( count($new_posts)>0 ){
                $this->tpl->assign('SHOW_NEWPOST_LINK', 1);
            }
        }
    }
    
    $this->tpl->assign('POST_ID', intval($this->q_param['post_id']));

    if ( isset($this->q_param['add_comment']) && !empty($this->q_param['add_comment']) )
    {   // добавление комментария
        $author_comment = ($this->q_param['is_auth_user']==0) ? 0 : $this->authorization->blog_id;
        $blog_image_id = ($this->q_param['is_auth_user']==0) ? 0 : $this->q_param['blog_image_id'];
        $this->addComment( $this->q_param['blog_post_id'], $author_comment, $this->q_param['parent_id'], $blog_image_id );
    }
    elseif ( isset($this->q_param['action']) && $this->q_param['action']=='del_comment' && ($this->isMainBlog() || $this->isModerator($blog_arr['BLOG_ID']) || $this->isMainComment($this->q_param['comment_id'])) )
    {   // удаление комментария (если мой блог или комментарий)
        $this->delComment( $this->q_param['comment_id'] );
    }
    elseif ( isset($this->q_param['action']) && $this->q_param['action']=='del_post' && ($this->isMainBlog() || $this->isModerator($blog_arr['BLOG_ID'])) )
    {   // удаление поста со всеми комментариями (если этот пост мой)
        $this->delPost( $this->q_param['post_id'], $this->authorization->blog_id );
    }
    elseif ( isset($this->q_param['action']) && $this->q_param['action']=='public' && ($this->isMainBlog() || $this->isModerator($blog_arr['BLOG_ID'])) )
    {   // публикация записи в сообществе
        db::update_record( 'BLOG_POST', 
            array( 
                'IS_CONFIRM' => 0
                //, 
                //'IS_PUBLIC'  => 1
            ), '',
            array( 'BLOG_POST_ID'=>$this->q_param['post_id'] )
        );
        //$new_posts = db::sql_select( $this->make_sql_for_posts($blog_arr, 'list', '', 1, intval($this->q_param['blog_tag_id']), intval($this->q_param['post_id'])) );
        $new_posts = db::sql_select( $this->make_sql_for_posts($blog_arr, 'list', 1, 1, intval($this->q_param['blog_tag_id']), intval($this->q_param['post_id'])) );
        $location = count($new_posts)>0 
            ? $_SERVER['REQUEST_URI']
            : lib::make_request_uri(array('newpost_'.$this->env['area_id']=>0));
        Header('Location: '.$location);
        exit();
    }
    
    if ( isset($this->q_param['post_id']) && $this->q_param['post_id']>0 ){
        // карточка поста
        $this->tpl->assign('IS_CARD', 1);
        $post_arr = db::sql_select( $this->make_sql_for_posts($blog_arr, 'list', '', '', $is_friendtape, intval($this->q_param['blog_tag_id']), intval($this->q_param['post_id'])) );
        if ( is_array($post_arr) && count($post_arr)>0 ){
            $this->tpl->assign('THEME', $post_arr[0]['TITLE']);
            $post_arr = $this->make_post_array($post_arr);
            // "Редактировать", определяем эту ссылку
            if ( ($this->isMainBlog() && !$is_friendtape) || $this->isAuthor($post_arr[0]['BLOG_POST_ID']) ){
                $post_arr[0]['EDIT_LINK'] = $this->getPathToVI('blogs', 'add_post', array( array('name'=>'post_id', 'value'=>intval($this->q_param['post_id'])) ));
                $this->tpl->assign('SHOW_EDIT_LINK', 1);
            }
            if ( (($this->isMainBlog() || $this->isModerator($post_arr[0]['BLOG_ID'])) && !$is_friendtape) || $this->isAuthor($post_arr[0]['BLOG_POST_ID']) ){
                $this->tpl->assign('SHOW_DELPOST_LINK', 1);
            }
            if ( isset($this->q_param['is_add_comment']) && $this->q_param['is_add_comment']==1 ){
                $this->tpl->assign('IS_ADD_COMMENT', 1);
            }
            // вытаскиваем массив изображений, для формы добавления комментария
            $images = db::sql_select( '
                SELECT * 
                FROM BLOG_IMAGE 
                WHERE BLOG_ID = :blog 
                ORDER BY IS_DEFAULT desc, IMAGE_DATE desc',
                array('blog'=>(!empty($this->authorization->blog_id) ? intval($this->authorization->blog_id) : 0))
            );
            if ( is_array($images) && count($images)>0 ){
                $this->tpl->assign('IMAGES', $images);
            }
            // делаем форму для комментария
        	$post_arr[0]['FORM_COMMENT'] = $this->addCommentForm( $this->q_param['post_id'] );
            // подцепляем комментарии
            if ( $post_arr[0]['IS_DISABLECOMMENT']!=1 || $this->isMainBlog() || $this->isModerator($post_arr[0]['BLOG_ID']) || $this->isAuthor($post_arr[0]['BLOG_POST_ID']) ){
            	$post_arr[0]['COMMENTS'] = $this->showComments( $this->q_param['post_id'] );
            }
            $this->tpl->assign('POSTS', $post_arr);
            
        }else{
            return false;
        }
    }else{
        // список постов
        $this->tpl->assign('IS_LIST', 1);
        if ( isset($this->q_param['blog_tag_id']) ){
            $tag_arr = db::sql_select( 'select * from BLOG_TAG where BLOG_TAG_ID = :tag', array('tag'=>intval($this->q_param['blog_tag_id'])) );
            if ( is_array($tag_arr) && count($tag_arr)>0 ){
                $this->tpl->assign('BLOG_TAG_NAME', $tag_arr[0]['TITLE']);
            }
        }
        $post_arr = db::sql_select( $this->make_sql_for_posts($blog_arr, 'list', $is_public, $is_newpost, $is_friendtape, intval($this->q_param['blog_tag_id'])) );
        $counter = db::sql_select( $this->make_sql_for_posts($blog_arr, 'list_counter', $is_public, $is_newpost, $is_friendtape, intval($this->q_param['blog_tag_id'])) );   
    	if ( $counter[0]['COUNTER']>$this->post_on_page_arr[$blog_arr['POSTS_ON_PAGE']] ){
            $this->tpl->assign('pager', lib::page_navigation($this->post_on_page_arr[$blog_arr['POSTS_ON_PAGE']], $counter[0]['COUNTER'], 'from_'.$this->env['area_id'], $this->tpl_dir.'pager.tpl'));
        }
    	$post_arr = $this->make_post_array($post_arr);
    	if ( count($post_arr)>0 ){
            $this->tpl->assign( 'POSTS', $post_arr );
        }
    }

    $all_tags_arr = db::sql_select( 'select T.* from BLOG_TAG T where T.BLOG_ID = :blog', array('blog'=>$this->view_param['blog_id']) );
    if ( is_array($all_tags_arr) && count($all_tags_arr)>0 ){
        foreach ( $all_tags_arr as $v=>$item ){
            $all_tags_arr[$v]['PATH'] = 'index.php?blog_tag_id_'.$this->env['area_id'].'='.$item['BLOG_TAG_ID'];
        }
        $this->tpl->assign( 'BLOG_ALLTAGS', $all_tags_arr );
    }
}



/**
 * Преобразование массива записей к виду удобному для отображения
 */
protected function make_post_array( $post_arr ){
    if ( !is_array($post_arr) || !(count($post_arr)>0) ){
        return array();
    }

    for ( $i=0; $i<count($post_arr); $i++ ){
        preg_match("/(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)/",$post_arr[$i]['ADDED_DATE'],$d);
        $post_arr[$i]['ADDED_DATE'] = date($this->date_format_in_blog, mktime($d[4],$d[5],$d[6],$d[2],$d[3],$d[1]));
        $post_arr[$i]['ACCESS_TITLE'] = $this->lang[$this->access_arr[$post_arr[$i]['ACCESS']]];
        $post_arr[$i]['AUTHOR_PATH'] = $this->get_blog_path($post_arr[$i]['AUTHOR_TITLE']);
        if ( $post_arr[$i]['_IS_COMMUNITY'] ){
            $post_arr[$i]['COMMUNITY_PATH'] = $this->get_blog_path($post_arr[$i]['COMMUNITY_TITLE']);
        }
        $tags_arr = db::sql_select( '
            select T.* 
            from BLOG_POST_SV_BLOG_TAG PT, BLOG_TAG T 
            where T.BLOG_TAG_ID = PT.BLOG_TAG_ID and PT.BLOG_POST_ID = :post',
            array('post'=>$post_arr[$i]['BLOG_POST_ID'])
        );
        if ( is_array($tags_arr) && count($tags_arr)>0 ){
            foreach ( $tags_arr as $v=>$item ){
                $tags_arr[$v]['PATH'] = 'index.php?blog_tag_id_'.$this->env['area_id'].'='.$item['BLOG_TAG_ID'];
            }
            $post_arr[$i]['BLOG_TAGS'] = $tags_arr;
        }
        if ( $post_arr[$i]['IS_DISABLECOMMENT']!=1 || $this->isMainBlog() || $this->isModerator($post_arr[$i]['BLOG_ID']) || $this->isAuthor($post_arr[$i]['BLOG_POST_ID']) ){
            $count_comments = db::sql_select( '
                select count(BLOG_COMMENT_ID) as COUNTER 
                from BLOG_COMMENT 
                where BLOG_POST_ID = :post',
                array('post'=>$post_arr[$i]['BLOG_POST_ID'])
            );
            if ( is_array($count_comments) && count($count_comments)>0 ){
                $post_arr[$i]['COUNT_COMMENTS'] = $count_comments[0]['COUNTER'];
            }
        }
    }
    return $post_arr;
}

/**
 * Формируем запрос для извлечения постов ( строка для доступа )
 */
protected function make_sql_access($blog_id) {
    $search_access = '';
    $access_arr = array(1);
    if ( !$this->authorization->passed_blog ){
        // если не аутентифицирован, то только "для всех"
        $access_arr = array(1);
    }elseif ( $this->authorization->blog_id==$blog_id ){
        // если это мой блог, то полный доступ
        $access_arr = array(1,2,3,4);
    }else{
        // если я в списке друзей(член сообщества), то и "для друзей"
        $friend_arr = db::sql_select( '
            SELECT F.BLOG_FRIEND_ID 
            FROM BLOG_FRIEND F 
            WHERE F.OWNER_ID = :owner AND F.FRIEND_ID = :friend',
            array('owner'=>$blog_id, 'friend'=>$this->authorization->blog_id)
        );
        if ( is_array($friend_arr) && count($friend_arr)>0 ){
            $access_arr = array(1,2);
            //тут делаем строку подзапроса для проверки варианта "выборочно"
            $search_access .= ' OR ( P.ACCESS = 4 AND P.BLOG_POST_ID IN ( 
                    SELECT  PF.BLOG_POST_ID 
                    FROM    BLOG_POST_SV_BLOG_FRIENDGROUP PF, BLOG_FRIENDGROUP FG, BLOG_FRIEND_SV_BLOG_FRIENDGROUP FFG, BLOG_FRIEND F
                    WHERE   PF.BLOG_FRIENDGROUP_ID = FG.BLOG_FRIENDGROUP_ID AND FG.BLOG_ID = \''.$blog_id.'\'
                            AND FFG.BLOG_FRIENDGROUP_ID = FG.BLOG_FRIENDGROUP_ID AND FFG.BLOG_FRIEND_ID = F.BLOG_FRIEND_ID AND F.OWNER_ID = \''.$blog_id.'\'
                            AND F.FRIEND_ID = \''.$this->authorization->blog_id.'\' ) ) ';
        }
    }
    $search_access = ' ( P.ACCESS in ('.implode(',', $access_arr).') '.$search_access.' ) ';
    return $search_access;
}

/**
 * Формируем запрос для извлечения постов
 */
protected function make_sql_for_posts($blog_arr, $mode, $is_public, $is_newpost, $is_friendtape, $blog_tag_id=0, $id='') {
    $search_access = $search_str = '';
    // массив прав добавим
    if ( !$is_friendtape ){
        $search_access .= ' AND ( P.BLOG_ID = \''.$this->view_param['blog_id'].'\' AND '.$this->make_sql_access( $this->view_param['blog_id'] ). ' ) ';
    }else{
        $friend_arr = db::sql_select( '
            SELECT FRIEND_ID 
            FROM BLOG_FRIEND 
            WHERE OWNER_ID = :owner AND ( IS_INQUIRY = \'0\' OR IS_INQUIRY IS NULL )',
            array('owner'=>$this->view_param['blog_id'])
        );
        if ( count($friend_arr)>0 ){
            $access_arr = array();
            foreach ( $friend_arr as $blog_id ){
                $access_arr[] = ' ( P.BLOG_ID = \''.$blog_id['FRIEND_ID'].'\' AND '.$this->make_sql_access($blog_id['FRIEND_ID']).' ) ';
            }
            $search_access = ' AND ( '.implode(' OR ', $access_arr).' ) ';
        }else{
            $search_access = ' AND ( P.BLOG_ID = \'0\' ) ';
        }
    }
    if ($id!=''){
        $search_str .= ' AND P.BLOG_POST_ID = \''.$id.'\' ';
    }
    
    // вытаскиваем необходимые посты
    $template = '
        select  <fields>
        from    BLOG_POST P 
                    left join BLOG_MOOD M on (P.BLOG_MOOD_ID = M.BLOG_MOOD_ID) 
                    left join BLOG_IMAGE I on (P.BLOG_IMAGE_ID = I.BLOG_IMAGE_ID)
                    left join BLOG B on (P.AUTHOR_ID = B.BLOG_ID)
                    left join BLOG B2 on (P.BLOG_ID = B2.BLOG_ID)
        where   TRUE '
                .($blog_tag_id==0 ? '' : ' and P.BLOG_POST_ID IN ( SELECT BLOG_POST_ID FROM BLOG_POST_SV_BLOG_TAG WHERE BLOG_TAG_ID = \''.$blog_tag_id.'\') ')
                .($is_public==='' ? '' : ' and P.IS_PUBLIC = \''.$is_public.'\''.($is_public==1 ? '' : ' and AUTHOR_ID = \''.$this->authorization->blog_id.'\''))
                .($is_newpost==='' ? '' : ' and P.IS_CONFIRM = \''.$is_newpost.'\'')
                .$search_access.$search_str.'
                <where>
        <order>
        <limit>';

	$pattern = array('/<fields>/','/<where>/','/<order>/','/<limit>/');
	if ( $mode=='list' ){
		$replace = array(' P.*, M.TITLE AS MOOD_TITLE, M.IMAGE AS MOOD_IMAGE, I.IMG AS POST_IMAGE, B.TITLE AS AUTHOR_TITLE, B2.IS_COMMUNITY AS _IS_COMMUNITY, B2.TITLE AS COMMUNITY_TITLE ','',' ORDER BY P.ADDED_DATE DESC ','LIMIT '.$this->from.', '.$this->post_on_page_arr[$blog_arr['POSTS_ON_PAGE']]);
	}elseif ( $mode=='list_counter' ){
		$replace = array('COUNT(*) AS COUNTER','','','');
	}
	$sql = preg_replace($pattern,$replace,$template);
	return $sql;
}


/**
 * Мой ли блог?
 */
protected function isMainBlog() {
    return ( $this->authorization->passed_blog && $this->authorization->blog_id==$this->view_param['blog_id'] );
}

/**
 * Мой ли комментарий?
 */
protected function isMainComment( $COMMENT_ID ) {
    $COMMENT_ID = !isset($COMMENT_ID) ? '' : $COMMENT_ID;
    $USER_ID = !isset($this->authorization->blog_id) ? '' : $this->authorization->blog_id;
    if ( !empty($COMMENT_ID) && !empty($USER_ID) && $this->authorization->passed_blog ){
        $comments = db::sql_select( '
            SELECT BLOG_COMMENT_ID 
            FROM BLOG_COMMENT 
            WHERE BLOG_COMMENT_ID = :comment AND AUTHOR_ID = :author',
            array('comment'=>$COMMENT_ID, 'author'=>$USER_ID)
        );
        if ( count($comments)>0 ){
            return true;
        }
    }
    return false;
}

/**
 * Модератор ли я в сообществе?
 */
protected function isModerator( $BLOG_ID ) {
    $BLOG_ID = !isset($BLOG_ID) ? '' : $BLOG_ID;
    $USER_ID = !isset($this->authorization->blog_id) ? '' : $this->authorization->blog_id;
    if ( !empty($BLOG_ID) && !empty($USER_ID) && $this->authorization->passed_blog ){
        $moderators = db::sql_select( '
            SELECT BLOG_FRIEND_ID 
            FROM BLOG_FRIEND 
            WHERE OWNER_ID = :owner AND FRIEND_ID = :friend AND IS_MODERATOR = \'1\'',
            array('owner'=>$BLOG_ID, 'friend'=>$USER_ID)
        );
        if ( count($moderators)>0 ){
            return true;
        }
    }
    return false;
}

/**
 * Моя ли запись в блоге(я ли автор)?
 */
protected function isAuthor( $BLOG_POST_ID ) {
    $BLOG_POST_ID = !isset($BLOG_POST_ID) ? '' : $BLOG_POST_ID;
    $USER_ID = !isset($this->authorization->blog_id) ? '' : $this->authorization->blog_id;
    if ( !empty($BLOG_POST_ID) && !empty($USER_ID) && $this->authorization->passed_blog ){
        $posts = db::sql_select( '
            SELECT BLOG_POST_ID 
            FROM BLOG_POST 
            WHERE BLOG_POST_ID = :post AND AUTHOR_ID = :author',
            array('post'=>$BLOG_POST_ID, 'author'=>$USER_ID)
        );
        if ( count($posts)>0 ){
            return true;
        }
    }
    return false;
}



/**
 * Удаление поста со всеми комментриями пользователем $USER_ID
 */
protected function delPost( $BLOG_POST_ID, $USER_ID ) {
    $BLOG_POST_ID = !isset($BLOG_POST_ID) ? '' : $BLOG_POST_ID;
    $USER_ID = !isset($USER_ID) ? '' : $USER_ID;
    if ( !empty($BLOG_POST_ID) && !empty($USER_ID) ){
        $posts = db::sql_select( '
            SELECT BLOG_POST_ID 
            FROM BLOG_POST 
            WHERE BLOG_POST_ID = :post AND AUTHOR_ID = :author',
            array('post'=>$BLOG_POST_ID, 'author'=>$USER_ID)
        );
        if ( count($posts)>0 ) {
            db::delete_record( 'BLOG_POST_SV_BLOG_FRIENDGROUP', array('BLOG_POST_ID'=>$BLOG_POST_ID) );
            db::delete_record( 'BLOG_POST_SV_BLOG_TAG', array('BLOG_POST_ID'=>$BLOG_POST_ID) );
            db::delete_record( 'BLOG_COMMENT', array('BLOG_POST_ID'=>$BLOG_POST_ID) );
            db::delete_record( 'BLOG_POST', array('BLOG_POST_ID'=>$BLOG_POST_ID) );
            Header('Location: index.php');
            exit();
        }
    }
}


/**
 * Удаление комментрия
 */
protected function delComment( $COMMENT_ID ) {
    $COMMENT_ID = !isset($COMMENT_ID) ? '' : $COMMENT_ID;
    $USER_ID = !isset($this->authorization->blog_id) ? '' : $this->authorization->blog_id;
    if ( !empty($COMMENT_ID) && !empty($USER_ID) && ($this->isMainBlog() || $this->isMainComment($COMMENT_ID)) ){
        // отсылаем владельцу комментария письмо
        $this->sendMsgWhenDelComment( $COMMENT_ID );
        // делаем комментарий удалённым
        db::update_record( 'BLOG_COMMENT', 
            array(
                'BLOG_IMAGE_ID' => 0,
                'TITLE' => '', 
                'BODY'  => ''
            ), '',
            array( 'BLOG_COMMENT_ID' => $COMMENT_ID )
        );
        Header('Location: index.php?post_id'.$this->env['area_id'].'='.$this->q_param['post_id']);
        exit();
    }
}


/**
 * Отображение комментариев с формами для ответов
 */
protected function showComments( $post_id, $parent_id=0 ) {
    $fileTPL = 'blog_comment.tpl';
    if ( !file_exists($this->tpl_dir.$fileTPL) ) {
        $commentForm = 'Шаблон отсутствует: '.$fileTPL.'';
    }else{
        // инициализация шаблонизатора
        $comment_tpl = new smarty_ee_module($this);

        $post_arr = db::sql_select( 'SELECT BLOG_ID FROM BLOG_POST WHERE BLOG_POST_ID = :post', array('post'=>$post_id) );
        $comment_arr = db::sql_select( '
            SELECT C.*, I.IMG 
            FROM BLOG_COMMENT C 
                LEFT JOIN BLOG_IMAGE I ON (C.BLOG_IMAGE_ID = I.BLOG_IMAGE_ID) 
            WHERE C.BLOG_POST_ID = :post AND C.PARENT_ID = :parent 
            ORDER BY C.ADDED_DATE ASC',
            array('post'=>$post_id, 'parent'=>$parent_id)
        );
        $out_comment_arr = array();
        for ( $i=0; $i<count($comment_arr); $i++ ){
            if ( $comment_arr[$i]['IS_DISABLE']!=1 || $this->isMainBlog() || $this->isModerator($post_arr[0]['BLOG_ID']) || $this->isMainComment($comment_arr[$i]['BLOG_COMMENT_ID']) ){
                // вытаскиваем информацию об авторе, если он есть
                if ( $comment_arr[$i]['AUTHOR_ID']>0 ){
                    $author = $this->get_user_info($comment_arr[$i]['AUTHOR_ID']); 
                    if ( !empty($author) ){
                        $comment_arr[$i]['AUTHOR_NICK'] = $author['TITLE'];
                        $comment_arr[$i]['AUTHOR_BLOG'] = $this->get_blog_path($author['TITLE']);
                        $comment_arr[$i]['AUTHOR_PROFILE'] = $this->get_blog_path($author['TITLE']).'/profile/';
                    }else{
                        $comment_arr[$i]['AUTHOR_ID'] = 0;
                    }
                }
                // дату приодим к формату
                preg_match("/(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)/", $comment_arr[$i]['ADDED_DATE'], $d);
                $comment_arr[$i]['ADDED_DATE'] = date($this->date_format_in_blog, mktime($d[4],$d[5],$d[6],$d[2],$d[3],$d[1]));
                // делаем форму для комментария
            	$comment_arr[$i]['FORM_COMMENT'] = $this->addCommentForm( $post_id, $comment_arr[$i]['BLOG_COMMENT_ID'] );
            	// оцениваем возможность удаления 
            	if ( ($this->isMainBlog() || $this->isModerator($post_arr[0]['BLOG_ID'])  || $this->isMainComment( $comment_arr[$i]['BLOG_COMMENT_ID'] ) ) && $comment_arr[$i]['BODY'] != '' ){
                	$comment_arr[$i]['SHOW_DELETE_LINK'] = 1 ;
                }
                // в случае удалённого комментария(тело пусто) присваиваем надпись "Комментарий удалён"
            	if ( $comment_arr[$i]['BODY'] == '' ){
                	$comment_arr[$i]['COMMENT_IS_DEL'] = 1;
                }
            	// прицепляем вложенные комментарии
                $comment_arr[$i]['CHILD'] = $this->showComments( $post_id, $comment_arr[$i]['BLOG_COMMENT_ID'] );
                $out_comment_arr[] = $comment_arr[$i];
            }
        }
		$comment_tpl->assign('COMMENTS', $out_comment_arr);
		$commentForm = $comment_tpl->fetch($this->tpl_dir . $fileTPL);
        unset($comment_tpl);
    }
    return $commentForm;
}


/**
 * Отображение комментария со свoим родителем, если он есть
 */
protected function showCommentsWithParent( $comment_id, $is_mail ) {
    $fileTPL = 'blog_comment.tpl';
    if ( !file_exists($this->tpl_dir.$fileTPL) ) {
        $commentForm = 'Шаблон отсутствует: '.$fileTPL.'';
    }else{
        // инициализация шаблонизатора
        $comment_tpl = new smarty_ee_module($this);

        $comment_arr = db::sql_select( '
            SELECT C.*, I.IMG 
            FROM BLOG_COMMENT C 
                LEFT JOIN BLOG_IMAGE I ON (C.BLOG_IMAGE_ID = I.BLOG_IMAGE_ID) 
            WHERE C.BLOG_COMMENT_ID = :comment',
            array('comment'=>$comment_id)
        );
        if ( count($comment_arr)>0 ){
            preg_match("/(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)/", $comment_arr[0]['ADDED_DATE'], $d);
            $comment_arr[0]['ADDED_DATE'] = date($this->date_format_in_blog, mktime($d[4],$d[5],$d[6],$d[2],$d[3],$d[1]));
            $comment_arr[0]['IMG'] =  'http://'.$_SERVER['HTTP_HOST'].$comment_arr[0]['IMG'];
            // в случае удалённого комментария(тело пусто) присваиваем надпись "Комментарий удалён"
        	if ( $comment_arr[0]['BODY'] == '' ){
            	$comment_arr[0]['COMMENT_IS_DEL'] = 1;
            }
          	$comment_arr[0]['IS_MAIL'] = $is_mail;
            // вытаскиваем информацию об авторе, если он есть
            if ( $comment_arr[0]['AUTHOR_ID']>0 ){
                $author = $this->get_user_info($comment_arr[0]['AUTHOR_ID']); 
                if ( !empty($author) ){
                    $comment_arr[0]['AUTHOR_NICK'] = $author['TITLE'];
                }
            }
            $comment_tpl->assign('COMMENTS',$comment_arr);
        }
        $commentForm = $comment_tpl->fetch($this->tpl_dir . $fileTPL);
        unset($comment_tpl);

        // инициализация шаблонизатора
        $comment_tpl_p = new smarty_ee_module($this);
        $comment_arr_p = db::sql_select( '
            SELECT C.*, I.IMG 
            FROM BLOG_COMMENT C 
                LEFT JOIN BLOG_IMAGE I ON (C.BLOG_IMAGE_ID = I.BLOG_IMAGE_ID) 
            WHERE C.BLOG_COMMENT_ID = :comment',
            array('comment'=>$comment_arr[0]['PARENT_ID'])
        );
        if ( count($comment_arr_p)>0 ) {
            preg_match("/(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)/", $comment_arr_p[0]['ADDED_DATE'], $d);
            $comment_arr_p[0]['ADDED_DATE'] = date($this->date_format_in_blog, mktime($d[4],$d[5],$d[6],$d[2],$d[3],$d[1]));
            $comment_arr_p[0]['IMG'] =  'http://'.$_SERVER['HTTP_HOST'].$comment_arr_p[0]['IMG'];
            // в случае удалённого комментария(тело пусто) присваиваем надпись "Комментарий удалён"
        	if ( $comment_arr_p[0]['BODY'] == '' ){
            	$comment_arr_p[0]['COMMENT_IS_DEL'] = 1;
            }
          	$comment_arr_p[0]['IS_MAIL'] = $is_mail;
        	$comment_arr_p[0]['CHILD'] = $commentForm;
            // вытаскиваем информацию об авторе, если он есть
            if ( $comment_arr_p[0]['AUTHOR_ID']>0 ){
                $author = $this->get_user_info($comment_arr_p[0]['AUTHOR_ID']); 
                if ( !empty($author) ){
                    $comment_arr_p[0]['AUTHOR_NICK'] = $author['TITLE'];
                }
            }
            $comment_tpl_p->assign('COMMENTS',$comment_arr_p);
            $commentForm = $comment_tpl_p->fetch($this->tpl_dir . $fileTPL);
            unset($comment_tpl_p);
        }
    }
    return $commentForm;
}


/**
 * Форма добавления комментария
 */
protected function addCommentForm( $POST_ID, $COMMENT_ID='' ){
    $fileTPL = 'blog_add_comment.tpl';
    if ( !file_exists($this->tpl_dir.$fileTPL) ) {
        $commentForm = 'Шаблон отсутствует: '.$fileTPL.'';
    }else{
        // инициализация шаблонизатора
        $comment_tpl = new smarty_ee_module($this);

        $images = db::sql_select( '
            SELECT * 
            FROM BLOG_IMAGE 
            WHERE BLOG_ID = :blog 
            ORDER BY IS_DEFAULT desc, IMAGE_DATE desc',
            array('blog'=>$this->authorization->blog_id)
        );
        if ( is_array($images) && count($images)>0 ){
            $comment_tpl->assign('IMAGES', $images);
        }
        if ( $this->authorization->passed_blog ){
        	$comment_tpl->assign('IS_AUTH', 1);
            $auth_user = $this->get_user_info($this->authorization->blog_id); 
            if ( !empty($auth_user) ){
            	$comment_tpl->assign('NICK', $auth_user['TITLE']);
            	$comment_tpl->assign('PATH_TO_BLOG', $this->get_blog_path($auth_user['TITLE']));
            }
        }else{
        	$comment_tpl->assign('PATH_TO_AUTH', $this->getPathToVI('blogs','auth'));
        }
    	$comment_tpl->assign('BLOG_POST_ID', intval($POST_ID));
    	$comment_tpl->assign('BLOG_COMMENT_ID', intval($COMMENT_ID));
    	$commentForm = $comment_tpl->fetch($this->tpl_dir . $fileTPL);
        unset($comment_tpl);
    }
    return $commentForm;
}


/**
 * Добавить комментарий
 */
protected function addComment( $BLOG_POST_ID, $AUTHOR_ID, $PARENT_ID, $BLOG_IMAGE_ID ) {
    $BLOG_POST_ID = !isset($BLOG_POST_ID) ? '' : $BLOG_POST_ID;
    $AUTHOR_ID = !isset($AUTHOR_ID) ? '' : $AUTHOR_ID;
    $PARENT_ID = !isset($PARENT_ID) ? '' : $PARENT_ID;
    $BLOG_IMAGE_ID = !isset($BLOG_IMAGE_ID) ? '' : $BLOG_IMAGE_ID;
    if ( $BLOG_POST_ID ){
        db::insert_record( 'BLOG_COMMENT',
            array(
                'ADDED_DATE' => lib::pack_date(date("d.m.Y H:i"),'long'),
                'BLOG_POST_ID' => $BLOG_POST_ID, 
                'AUTHOR_ID' => $AUTHOR_ID,
                'PARENT_ID' => $PARENT_ID, 
                'BLOG_IMAGE_ID' => $BLOG_IMAGE_ID,
                'IS_DISABLE' => intval($this->q_param['is_disable']), 
                'TITLE' => $this->q_param['title'], 
                'BODY'  => $this->q_param['body']
            )
        );
        // надо разослать сообщения владельцу блога и автору вышестоящего комментария
        $comment = db::sql_select( 'select BLOG_COMMENT_ID from BLOG_COMMENT order by BLOG_COMMENT_ID desc' );
        $this->sendMsgWhenAddComment($comment[0]['BLOG_COMMENT_ID']);
    }

    $_path = $_SERVER['REQUEST_URI'];
    $_pos = strpos($_SERVER['REQUEST_URI'], '&add_comment'.$this->env['area_id'].'');
    if ( $_pos!==false ){
        $_path = substr($_SERVER['REQUEST_URI'], 0, $_pos);
        $_pos2 = strpos($_SERVER['REQUEST_URI'], '&', $_pos+1);
        if ( $_pos2!==false ){
            $_path .= substr($_SERVER['REQUEST_URI'], $_pos2);
        }
    }
    $_pos3 = strpos($_path, '#');
    $_path .= substr($_path, 0, $_pos3).'#comment'.$comment[0]['BLOG_COMMENT_ID'].'_'.$this->env['area_id'].'';
    Header('Location: '.$_path);
    exit();
}




/**
 * Вариант использования "Блог - ФотоГалерея"
 */
protected function modeBlogPhotoGallery($blog_arr) {
    $cols_count = 3; // количество столбцов в галерее
    $user = $this->get_user_info($this->view_param['blog_id']);
    if ( !empty($user) ) {
        // заголовок формы заполняем
        $this->tpl->assign('FORM_TITLE', $user['TITLE'].' : изображения пользователя');
        $this->tpl->assign('BLOG_ID', $this->view_param['blog_id']);
        // добавляем рисунки
        $images_arr = db::sql_select( 'select * from BLOG_IMAGE where BLOG_ID = :blog ORDER BY IMAGE_DATE ASC', array('blog'=>$this->view_param['blog_id']) );
        $images = array();
        if ( is_array($images_arr) && count($images_arr)>0 ){
            for ( $i=0; $i<count($images_arr); $i++ ){
                $images[floor($i/$cols_count)]['COLS'][] = $images_arr[$i];
            }
        }
        $this->tpl->assign('IMAGES', $images);
    }else{
        return false;
    }
}


/**
 * Вариант использования "Блог - Профиль"
 */
protected function modeBlogProfile($blog_arr) {
    if ( isset($this->q_param['action']) ){
        //сохранение изменений в БД
        if ( $this->q_param['action']=='add' ){
            // вступление в сообщество
        	if ( !$this->modeAuth() ){
                return false;
          	}elseif ( is_numeric($this->q_param['community_id']) && $this->q_param['user_id']==$this->authorization->blog_id && $this->q_param['community_id']>0 ) {
                $community_set = $this->get_user_info($this->q_param['community_id']);
                $friend_sel = db::sql_select( '
                    select BLOG_FRIEND_ID
                    from BLOG_FRIEND 
                    where FRIEND_ID = :friend and OWNER_ID = :owner',
                    array('friend'=>$this->authorization->blog_id, 'owner'=>$this->q_param['community_id'])
                );
                if ( !(count($friend_sel)>0) ){
                    db::insert_record( 'BLOG_FRIEND', 
                        array(
                            'ADDED_DATE' => lib::pack_date(date("d.m.Y H:i"),'long'),
                            'FRIEND_ID'  => $this->authorization->blog_id,
                            'OWNER_ID'   => $this->q_param['community_id'],
                            'INVITER_ID' => $this->authorization->blog_id,
                            'LEVEL'      => (($community_set['POSTLEVEL']==2) ? 1 : 2),
                            'IS_CREATOR' => 0,
                            'IS_MODERATOR' => 0,
                            'IS_INVITE'  => 0,
                            'IS_INQUIRY' => (($community_set['MEMBERSHIP']==2) ? 1 : 0)
                        )
                    );
                    if ( $community_set['MEMBERSHIP']!=2 ){
                        $friend_sel = db::sql_select( '
                            select BLOG_FRIEND_ID 
                            from BLOG_FRIEND 
                            where FRIEND_ID = :friend and OWNER_ID = :owner',
                            array('friend'=>$this->q_param['community_id'], 'owner'=>$this->authorization->blog_id)
                        );
                        if ( !(count($friend_sel)>0) ){
                            db::insert_record( 'BLOG_FRIEND', 
                                array(
                                    'ADDED_DATE' => lib::pack_date(date("d.m.Y H:i"),'long'),
                                    'FRIEND_ID'  => $this->q_param['community_id'],
                                    'OWNER_ID'   => $this->authorization->blog_id,
                                    'INVITER_ID' => $this->authorization->blog_id,
                                    'LEVEL'      => 0,
                                    'IS_CREATOR' => 0,
                                    'IS_MODERATOR' => 0,
                                    'IS_INVITE'  => 0,
                                    'IS_INQUIRY' => 0
                                )
                            );
                        }
                    }
                    $this->sendMsgWhenInviteList( db::last_insert_id( 'BLOG_FRIEND_SEQ' ), 'inquiry' );
                }
            }
        }elseif ( $this->q_param['action']=='del' ){
            // выход из сообщества
            if ( is_numeric($this->q_param['community_id']) && $this->q_param['user_id']==$this->authorization->blog_id && $this->q_param['community_id']>0 ) {
                db::delete_record( 'BLOG_FRIEND', 
                    array(
                        'OWNER_ID'  => intval($this->q_param['community_id']), 
                        'FRIEND_ID' => intval($this->q_param['user_id'])
                    )
                );
                db::delete_record( 'BLOG_FRIEND', 
                    array(
                        'OWNER_ID'  => intval($this->q_param['user_id']),
                        'FRIEND_ID' => intval($this->q_param['community_id'])
                    )
                );
            }
        }
        Header('Location: '.$_SERVER['SCRIPT_NAME']);
        exit();
    }

    $sex_arr = array('1'=>'не указан','2'=>'мужской','3'=>'женский');
    $birthdate_format = array('1'=>'d.m.Y','2'=>'d.m','3'=>'Y');	
    $user = $this->get_user_info($this->view_param['blog_id']);
    if ( !empty($user) ){
        $is_community = ($user['IS_COMMUNITY']==1) ? true : false;
        // добавляем "Изображение" и ссылку на всю галерею
        $images_arr = db::sql_select( '
            select BI.*
            from BLOG_IMAGE BI 
            where BI.BLOG_ID = :blog 
            ORDER BY BI.IS_DEFAULT DESC, BI.IMAGE_DATE ASC',
            array('blog'=>$this->view_param['blog_id'])
        );
        if ( is_array($images_arr) && count($images_arr)>0 ){
            $this->tpl->assign('DEFAULT_IMAGE', $images_arr[0]['IMG']);
            $this->tpl->assign('ALLIMAGE_LINK', 'photogallery.php');
        }else{
            $this->tpl->assign('DEFAULT_IMAGE', '');
        }
        // проверяем для всех полей возможность отображения и пишем им значение
        $fields_arr = db::sql_select( '
            select BF.* 
            from BLOG_SV_BLOG_FIELD BBF, BLOG_FIELD BF 
            where BF.BLOG_FIELD_ID = BBF.BLOG_FIELD_ID and BBF.BLOG_ID = :blog',
            array('blog'=>$this->view_param['blog_id'])
        );
        foreach ( $fields_arr as $field ){
            if ( $field['FIELD_NAME'] == 'BIRTHDATE' && !$is_community && $user[$field['FIELD_NAME']] ){
                preg_match("/(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)/", $user[$field['FIELD_NAME']], $d);
                $format_id = $user['BIRTHDATE_FORMAT'] ? $user['BIRTHDATE_FORMAT'] : 1;
                $this->tpl->assign('SHOW_BIRTHDATE', 1);
                $this->tpl->assign('BIRTHDATE', date($birthdate_format[$format_id], mktime(0,0,0, $d[2],$d[3],$d[1])));
            }elseif ( $field['FIELD_NAME'] == 'SEX' && !$is_community ){
                $this->tpl->assign('SHOW_SEX', 1);
                $this->tpl->assign('SEX', $user[$field['FIELD_NAME']] ? $sex_arr[$user[$field['FIELD_NAME']]] : $sex_arr[1]);
            }elseif ( $field['FIELD_NAME'] == 'BLOG_COUNTRY_ID' ){
                if ( $user['BLOG_COUNTRY_ID'] ){
                    $country_arr = db::sql_select( 'select * from BLOG_COUNTRY where BLOG_COUNTRY_ID = :country', array('country'=>$user['BLOG_COUNTRY_ID']) );
                    $country = is_array($country_arr)&&count($country_arr)>0 ? $country_arr[0]['TITLE'] : '';
                }else{
                    $country = '';
                }
                $this->tpl->assign('SHOW_BLOG_COUNTRY', 1);
                $this->tpl->assign('BLOG_COUNTRY', $country);
            }elseif ( $field['FIELD_NAME'] == 'BLOG_CITY_ID' ){
                if ( $user['BLOG_CITY_ID'] ){
                    $city_arr = db::sql_select( 'select * from BLOG_CITY where BLOG_CITY_ID = :city', array('city'=>$user['BLOG_CITY_ID']) );
                    $city = is_array($city_arr)&&count($city_arr)>0 ? $city_arr[0]['TITLE'] : '';
                }else{
                    $city = '';
                }
                $this->tpl->assign('SHOW_BLOG_CITY', 1);
                $this->tpl->assign('BLOG_CITY', $city);
            }else{
                $this->tpl->assign('SHOW_'.$field['FIELD_NAME'], 1);
                $this->tpl->assign($field['FIELD_NAME'], $user[$field['FIELD_NAME']]);
            }
        }
        // поле "Ник" всегда отображается, его видимость не редактируется
        $this->tpl->assign('SHOW_NICK', 1);
        $this->tpl->assign('NICK', $user['TITLE']);
        
        if ( $is_community ){
            $this->tpl->assign('IS_COMMUNITY', 1);
            // добавляем "Создателя"
            $creater_arr = db::sql_select( 'select FRIEND_ID from BLOG_FRIEND where IS_CREATOR = \'1\' and OWNER_ID = :owner', array('owner'=>$this->view_param['blog_id']) );
            $creater_arr = $this->get_user_info($creater_arr[0]['FRIEND_ID']);
            if ( !empty($creater_arr) ){
                $this->tpl->assign('SHOW_CREATER', 1);
                $this->tpl->assign('CREATER_PATH', $this->get_blog_path($creater_arr['TITLE']).'/profile/');
                $this->tpl->assign('CREATER', $creater_arr['TITLE']);
            }
            // добавляем "Модераторов"
            $moderator_arr = db::sql_select( '
                select B.TITLE 
                from BLOG_FRIEND BF, BLOG B 
                where BF.FRIEND_ID = B.BLOG_ID and BF.IS_MODERATOR = \'1\' and BF.OWNER_ID = :owner 
                ORDER BY B.TITLE ASC',
                array('owner'=>$this->view_param['blog_id'])
            );
            if ( is_array($moderator_arr) && count($moderator_arr)>0 ){
                $moderators = array();
                $this->tpl->assign('SHOW_MODERATOR', 1);
                foreach ( $moderator_arr as $item ){
                    $item['PATH_TO_PROFILE'] = $this->get_blog_path($item['TITLE']).'/profile/';
                    $moderators[] = $item;
                }
                $this->tpl->assign('MODERATORS', $moderators);
            }
            // добавляем "Информацию по правилам сообщества"
            $this->tpl->assign('SHOW_MEMBERSHIP', 1);
            $this->tpl->assign('MEMBERSHIP'.$user['MEMBERSHIP'], 1);
            $this->tpl->assign('SHOW_POSTLEVEL', 1);
            $this->tpl->assign('POSTLEVEL'.$user['POSTLEVEL'], 1);
            $this->tpl->assign('SHOW_MODERATION', 1);
            $this->tpl->assign('MODERATION'.$user['MODERATION'], 1);
        }

        // добавляем "Интересы"
        $interests_arr = db::sql_select( '
            select BI.* 
            from BLOG_SV_BLOG_INTEREST BBI, BLOG_INTEREST BI 
            where BI.BLOG_INTEREST_ID = BBI.BLOG_INTEREST_ID and BBI.BLOG_ID = :blog 
            ORDER BY BI.TITLE ASC',
            array('blog'=>$this->view_param['blog_id'])
        );
        $interests = array();
        if ( is_array($interests_arr) && count($interests_arr)>0 ){
            foreach ( $interests_arr as $item ){
                $interests[] = $item['TITLE'];
            }
        }
        $this->tpl->assign('INTERESTS', implode(', ', $interests));
        // добавляем "Друзей(Участников)"
        $friends_arr = db::sql_select( '
            select B.BLOG_ID, B.TITLE 
            from BLOG_FRIEND BF, BLOG B 
            where BF.FRIEND_ID = B.BLOG_ID and (B.IS_COMMUNITY = \'0\' OR B.IS_COMMUNITY IS NULL) 
                and (BF.IS_INVITE = \'0\' OR BF.IS_INVITE IS NULL) and (BF.IS_INQUIRY = \'0\' OR BF.IS_INQUIRY IS NULL) 
                and BF.OWNER_ID = :owner 
            ORDER BY B.TITLE ASC',
            array('owner'=>$this->view_param['blog_id'])
        );
        $friends = array();
        if ( is_array($friends_arr) && count($friends_arr)>0 ){
            foreach ( $friends_arr as $item ){
                $item['PATH_TO_PROFILE'] = $this->get_blog_path($item['TITLE']).'/profile/';
                $friends[] = $item;
                if ( $item['BLOG_ID']==$this->authorization->blog_id ){
                    $this->tpl->assign('IS_FRIEND_CONNUNITY', 1);
                }
            }
        }
        $this->tpl->assign('FRIENDS', $friends);

        if ( !$is_community ){
            // добавляем "Сообщества"
            $friends_arr = db::sql_select( '
                select B.TITLE 
                from BLOG_FRIEND BF, BLOG B 
                where BF.FRIEND_ID = B.BLOG_ID and B.IS_COMMUNITY = \'1\' and (BF.IS_INVITE = \'0\' OR BF.IS_INVITE IS NULL) 
                    and (BF.IS_INQUIRY = \'0\' OR BF.IS_INQUIRY IS NULL) and BF.OWNER_ID = :owner 
                ORDER BY B.TITLE ASC',
                array('owner'=>$this->view_param['blog_id'])
            );
            $friends = array();
            if ( is_array($friends_arr) && count($friends_arr)>0 ){
                foreach ( $friends_arr as $item ){
                    $item['PATH_TO_PROFILE'] = $this->get_blog_path($item['TITLE']).'/profile/';
                    $friends[] = $item;
                }
            }
            $this->tpl->assign('COMMUNITY', $friends);
            $this->tpl->assign('SHOW_COMMUNITY', 1);
        }
    }else{
        return false;
    }

    // заголовок формы заполняем
    $title_arr = db::sql_select( '
        select BF.FIELD_NAME 
        from BLOG_SV_BLOG_FIELD BBF, BLOG_FIELD BF 
        where BF.BLOG_FIELD_ID = BBF.BLOG_FIELD_ID and BF.FIELD_NAME = \'NAME\' and BBF.BLOG_ID = :blog',
        array('blog'=>$this->view_param['blog_id'])
    );
    $this->tpl->assign('FORM_TITLE', !empty($user['NAME'])&&is_array($title_arr)&&count($title_arr)>0 ? $user['NAME'] : $user['TITLE'].' : профиль' );
    $this->tpl->assign('BLOG_ID', $this->view_param['blog_id']);
    $this->tpl->assign('USER_ID', $this->authorization->blog_id);

    // оцениваем доступность ссылки "Редактировать", определяем эту ссылку
    $show_edit_link = false;
  	if ( $this->authorization->passed_blog ){
        if ( !$is_community ){
            if ($this->authorization->blog_id==$this->view_param['blog_id'] ){
                $show_edit_link = true;
            }
        }else{
            $moderators_arr = db::sql_select( '
                select BF.BLOG_FRIEND_ID 
                from BLOG_FRIEND BF 
                where BF.FRIEND_ID = :friend and BF.IS_MODERATOR = \'1\' and BF.OWNER_ID = :owner',
                array('friend'=>$this->authorization->blog_id, 'owner'=>$this->view_param['blog_id'])
            );
            if ( is_array($moderators_arr) && count($moderators_arr)>0 ){
                $show_edit_link = true;
            }
        }
    }
    if ( $show_edit_link ){
        $_edit_link = $is_community
            ? $this->getPathToVI('blogs', 'community_manager', array( array('name'=>'submode', 'value'=>'profile'), array('name'=>'community_id', 'value'=>$this->view_param['blog_id']) ))
            : $this->getPathToVI('blogs', 'edit_profile', array());
        $this->tpl->assign( 'EDIT_LINK', $_edit_link );
        $this->tpl->assign( 'SHOW_EDIT_LINK', (!empty($_edit_link) ? 1 : 0) );
    }
}






/**
 * Вариант использования "Напоминание забытого пароля"
 */
protected function modeReminder() {
    // при интеграции перенаправляем на страницу напоминания пароля модуля Пользователи
    if ( in_array('PASSWORD', $this->authorization->fields_from_client) ){
        $user_reminder_path = $this->get_url_by_module_param('CLIENT', 'view_mode', 'reminder');
        $user_reminder_path = empty($user_reminder_path['PATH'])
            ? $this->getPathToVI('blogs','auth')
            : $user_reminder_path['PATH'];
        Header('Location: '.$user_reminder_path);
        return false;
    }

    if ( $this->q_param['EMAIL']){
        // отправка письма со ссылкой
        $user = $this->get_user_info_use_email($this->q_param['EMAIL']);
        if (count($user)>0){
            $user['NICK'] = $user['TITLE'];
            $user['REMINDER_URL'] = lib::make_request_uri(
                array(
                    'blog_id_'.$this->env['area_id'] => $user['BLOG_ID'],
                    'blog_key_'.$this->env['area_id'] => $this->authorization->getBlogKey($user['BLOG_ID'],$user['EMAIL'],$user['PASSWORD_MD5'])
                ),
                'http'.( $_SERVER['HTTPS']=='on' ? 's' : '' ).'://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']
            );

            $fileTPL = 'reminderNotice.tpl';
            // формируется тело письма для клиента
            if ( !file_exists($this->tpl_dir.$fileTPL) ) {
                $htmlBody = 'Шаблон отсутствует: '.$fileTPL.'';
            }else{
                // инициализация шаблонизатора
                $htmlBody_tpl = new smarty_ee_module($this);
        		$htmlBody_tpl->assign(lib::array_htmlspecialchars($user));
        		$htmlBody_tpl->assign('HTTP_HOST', $_SERVER['HTTP_HOST']);
        		$htmlBody = $htmlBody_tpl->fetch($this->tpl_dir . $fileTPL);
                unset($htmlBody_tpl);
            }
            $mail_param = array(
                'to' 		=> $user['EMAIL'],
                'toName'   	=> ( !empty($user['FIO']) ? $user['FIO'] : $user['NICK']),
                'from'		=> 'webmaster@'.$_SERVER['HTTP_HOST'],
                'fromName'	=> $this->lang['sysw_blog_reminder_from'].' '.$_SERVER['HTTP_HOST'],
                'subject'	=> $this->lang['sysw_blog_reminder_subj'].' '.$user['NICK'],
                'htmlBody'	=> $htmlBody
            );
            // отсылается уведомление клиенту
            $this->sendMail($mail_param);
            $this->tpl->assign(lib::array_htmlspecialchars($user));
            $this->tpl->assign('_is_reminded', 1);
        }else{
            $this->tpl->assign('_error6', 1);
            $this->tpl->assign('_is_error', 1);
            $this->tpl->assign('EMAIL', $this->q_param['EMAIL']);
        }
	}elseif ( $this->q_param['blog_id'] ){
        // форма ввода пароля
        $is_error = false;
        $user = $this->get_user_info($this->q_param['blog_id']);
        if ( count($user)>0 ){
            $blog_key = $this->authorization->getBlogKey($user['BLOG_ID'],$user['EMAIL'],$user['PASSWORD_MD5']);
            if ( $blog_key===$this->q_param['blog_key'] ){
                $this->tpl->assign( '_is_change_form', 1 );
                if ( $this->q_param['PASSWORD'] ){
                    if ( $this->q_param['PASSWORD'] == $this->q_param['PASSWORD2'] ){
                        // Сохранение нового пароля
                        $fields = array( 'PASSWORD_MD5'=>md5($this->q_param['PASSWORD']) );
                        $this->set_user_info($user['BLOG_ID'], $fields);
                        $this->tpl->assign('_is_updated', 1);
                    }else{
                        $this->tpl->assign('_error4', 1);
                        $is_error = true;
                    }
                }
            }else{
                Header('Location: '.$this->getPathToVI('blogs','auth'));
                exit();
			}
        }else{
            Header('Location: '.$this->getPathToVI('blogs','auth'));
            exit();
        }
        // выводим ошибки в шаблон
        if ( $is_error ){
            $this->tpl->assign('_is_error', 1);
        }
    }
}




/**
 * Вариант использования "Форма аутентификации"
 */
protected function modeAuth() {
    if ( $_GET['from_url'] ){
        $this->q_param['from_url'] = $_GET['from_url'];
    }

    $_goToPage = 'index.php';
    $_goToDate = ( !empty($this->view_param['goToData']) ) ? $this->get_url_by_page($this->view_param['goToData']) : '';
    $_goToReminder = ( !empty($this->view_param['goToReminder']) ) ? $this->get_url_by_page($this->view_param['goToReminder']) : $this->getPathToVI('blogs','reminder');
    $_goToRegistration = ( !empty($this->view_param['goToRegistration']) ) ? $this->get_url_by_page($this->view_param['goToRegistration']) : $this->getPathToVI('blogs','registration');

   	$this->tpl->assign( '_goToPage', $_goToPage);
	$this->tpl->assign( '_goToReminder', $_goToReminder );
	$this->tpl->assign( '_goToRegistration', $_goToRegistration );
   	$this->tpl->assign( '_query_string', $_SERVER['QUERY_STRING']);

	$this->tpl->assign( '_goToAddPost', $_goToAddPost = $this->getPathToVI('blogs','add_post') );
	$this->tpl->assign( '_goToRegCommunity', $_goToRegCommunity = $this->getPathToVI('blogs','registration_community') );
	$this->tpl->assign( '_goToProfile', $_goToProfile = $this->getPathToVI('blogs', 'edit_profile') );
	$this->tpl->assign( '_goToProfilePwd', $_goToProfilePwd = $this->getPathToVI('blogs','edit_profile', array( array('name'=>'submode', 'value'=>'pwd') )) );
	$this->tpl->assign( '_goToProfileImage', $_goToProfileImage = $this->getPathToVI('blogs','edit_profile', array( array('name'=>'submode', 'value'=>'images') )) );
	$this->tpl->assign( '_goToProfileTag', $_goToProfileTag = $this->getPathToVI('blogs','edit_profile', array( array('name'=>'submode', 'value'=>'tags') )) );
	$this->tpl->assign( '_goToProfileFriendgroup', $_goToProfileFriendgroup = $this->getPathToVI('blogs','edit_profile', array( array('name'=>'submode', 'value'=>'friendgroups') )) );
	$this->tpl->assign( '_goToProfileFriend', $_goToProfileFriend = $this->getPathToVI('blogs','edit_profile', array( array('name'=>'submode', 'value'=>'friends') )) );
	$this->tpl->assign( '_goToProfileOffer', $_goToProfileOffer = $this->getPathToVI('blogs','edit_profile', array( array('name'=>'submode', 'value'=>'offers') )) );
	$this->tpl->assign( '_goToProfileComManager', $_goToProfileComManager = $this->getPathToVI('blogs','community_manager') );

	$this->tpl->assign( 'from_url', $this->q_param['from_url'] );
	$this->tpl->assign( '_login', $this->q_param['login'] );
	$this->tpl->assign( '_password', $this->q_param['password'] );
	
  	// выход пользователя
  	if ( $this->q_param['exit'] ){
  	    $this->authorization->delCookie();
		$location = $this->is_protected_page() ? $this->getPathToVI('blogs','auth') : 'index.php';
		$location = $location ? $location : '/';
		Header('Location: '.$location);
        exit();
  	}
    
   	if ( (!$this->q_param['login'] || !$this->q_param['password']) && !$this->authorization->passed_blog ){
   	    // нет запроса на авторизацию или еще не авторизован
   	    // делаем ссылку на форму авторизации "клиента"
   	    $this->tpl->assign( 'client_auth_link', $this->getPathToVI('client', 'auth') );
        return false;
    }

    $use_cookies = !isset($this->q_param['login']) && !isset($this->q_param['password']) && $this->authorization->passed_blog;
    if ( $use_cookies ){
        $client_arr = $this->get_current_user_info();
    }else{
        $client_arr = $this->get_user_info_use_title($this->q_param['login']);
        if ( $client_arr['PASSWORD_MD5']!=md5($this->q_param['password']) ){
            $client_arr = array();
        }
    }

  	if ( count($client_arr)==0 ) {
		$this->tpl->assign('_is_error', 1);  		
  		return false;
  	}

    // вытаскиваем изображение "по умолчанию"
  	$image_arr = db::sql_select( '
        select * 
        from BLOG_IMAGE 
        where BLOG_ID = :blog 
        ORDER BY IS_DEFAULT DESC', 
        array('blog'=>$client_arr['BLOG_ID']) 
    );
    if ( is_array($image_arr) && count($image_arr)>0 ){
    	$this->tpl->assign('_image_default', $image_arr[0]['IMG']);
    }
    // считаем количество предложений вступить в сообщество
    $sql_friends_sel = db::sql_select( '
        SELECT count(BLOG_FRIEND_ID) AS COUNT_OFFERS 
        FROM BLOG_FRIEND F 
        WHERE FRIEND_ID = :friend AND F.IS_INVITE = \'1\'',
        array('friend'=>$client_arr['BLOG_ID'])
    );
    if ( is_array($sql_friends_sel) && count($sql_friends_sel)>0 ){
    	$this->tpl->assign('_countOffer', $sql_friends_sel[0]['COUNT_OFFERS']);
    }
    
	$this->tpl->assign('_nick', $client_arr['TITLE']);
   	$this->tpl->assign('_goToBlog', $this->get_blog_path($client_arr['TITLE']));

  	// если пользователь уже прошел аутентификацию
  	if ( $this->authorization->passed_blog ){
  		$this->tpl->assign('_is_registrated', 1);
		if ( $this->q_param['from_url'] ){
            Header('Location: '.base64_decode($this->q_param['from_url']));
            exit();
        }
  		return true;
  	}
	$this->authorization->setCookie($client_arr['BLOG_ID'], $this->authorization->getBlogKey($client_arr['BLOG_ID'],$client_arr['EMAIL'],$client_arr['PASSWORD_MD5']), $this->q_param['outComputer']);
	$this->tpl->assign('_is_registrated', 1);

    if ( $this->q_param['from_url'] ){
	    $location = base64_decode($this->q_param['from_url']);
	}elseif ( $this->q_param['login'] && $this->q_param['password'] ){
	    $location = $_goToDate;
	}
	if ( empty($location) ){
        $location = $_goToPage;
        $this->q_param['_query_string'] = strtr( $this->q_param['_query_string'], array_flip(get_html_translation_table(HTML_SPECIALCHARS)) );
        if ( $this->q_param['_query_string']!='' ){
            $location .= '?'.$this->q_param['_query_string'];
        }
    }

	Header('Location: '.$location);
    exit();
}


/**
 * Возвращает путь к разделу, в главной области которого расположена указанная форма(вариант использования)
 * Формирует из $params QUERY_STRING
 */
protected function getPathToVI( $class, $vi, $params=array() ) {
    $query = '
        SELECT  MP.MODULE_PARAM_ID, PV.PARAM_VALUE_ID
        FROM    MODULE_PARAM MP, PRG_MODULE PM, PARAM_VALUE PV
        WHERE   MP.SYSTEM_NAME = \'view_mode\' AND PV.VALUE = :vi AND
                PV.MODULE_PARAM_ID = MP.MODULE_PARAM_ID AND MP.PRG_MODULE_ID = PM.PRG_MODULE_ID AND PM.SYSTEM_NAME = :class';
    $result = db::sql_select($query, array('vi'=>$vi, 'class'=>$class));
    if ( count($result)>0 && !empty($result[0]['MODULE_PARAM_ID']) && !empty($result[0]['PARAM_VALUE_ID']) ){
        $query = '
            SELECT  PA.PAGE_ID as PAGE_ID, PA.TEMPLATE_AREA_ID AS TAREA_ID, S.HOST, S.TEST_HOST
            FROM    PAGE_AREA PA, PAGE P, TEMPLATE_AREA TA, PAGE_AREA_PARAM PAP, SITE S
            WHERE   P.SITE_ID = :site_id and P.VERSION = :version and P.LANG_ID = :lang_id and 
                    P.SITE_ID = S.SITE_ID and P.IS_PROTECTED = \'0\' and P.PAGE_ID = PA.PAGE_ID and P.VERSION = PA.VERSION and
                    PA.TEMPLATE_AREA_ID = TA.TEMPLATE_AREA_ID and TA.IS_MAIN = \'1\' AND
                    PAP.PAGE_ID = PA.PAGE_ID AND PAP.VERSION = PA.VERSION AND PAP.TEMPLATE_AREA_ID = PA.TEMPLATE_AREA_ID AND
                    PAP.MODULE_PARAM_ID = :mp_id AND PAP.VALUE = :pv_id
            LIMIT   0, 1';
        $result = db::sql_select($query, array('site_id'=>$this->env['site_id'], 'version'=>$this->env['version'], 'lang_id'=>$this->env['lang_id'], 'mp_id'=>$result[0]['MODULE_PARAM_ID'], 'pv_id'=>$result[0]['PARAM_VALUE_ID']));
        if ( count($result)>0 ) {
            $_query_str = '';
            if ( is_array($params) && count($params)>0 ){
                foreach ( $params as $item ){
                    $_query_str .= (empty($_query_str)? '' : '&').$item['name'].'_'.$result[0]['TAREA_ID'].'='.$item['value'];
                }
            }
            return 'http://'.($this->env['version']==0 ? $result[0]['HOST'] : $result[0]['TEST_HOST']).$this->get_url_by_page($result[0]['PAGE_ID']).(empty($_query_str) ? '' : '?'.$_query_str);
        }
    }
    return '';
}



/**
 * Вариант использования "Форма регистрации Блога"
 */
protected function modeRegistration() {
    // при интеграции регистрация Блога возможна только зарегистрированным Клиентом
	if ( !$this->authorization->passed_client ){
	    $user_auth_path = $this->get_url_by_module_param('CLIENT', 'view_mode', 'auth');
        $user_auth_path = empty($user_auth_path['PATH'])
            ? $this->getPathToVI('blogs','auth')
            : $user_auth_path['PATH'];
        Header('Location: '.$user_auth_path);
        return false;
    }else{
        $this->tpl->assign($this->authorization->getRegistrationDataFromClient());
    }
    
    // исключаем регистрацию Блога аутентифированным пользователем
	if ( $this->authorization->passed_blog ){
        Header('Location: '.$this->getPathToVI('blogs','auth'));
        return false;
    }

    if ( is_array($this->authorization->fields_from_client) && (count($this->authorization->fields_from_client)>0) ){
        foreach ( $this->authorization->fields_from_client as $item ){
            $this->tpl->assign($item.'_READONLY', 1);
        }
    }
    // проверка корректности введённых данных с формы + уникальность
    if ( !$this->isCorrectClientData() ){
        return false;
    }

    // в случае корректных данных - сохранение данных в БД
    $fields = array(
        'BLOG_DATE'         => lib::pack_date(date("d.m.Y H:i"),'long'),
        'TITLE'             => $this->q_param['NICK'],
        'EMAIL'             => $this->q_param['EMAIL'],
        'PASSWORD_MD5'      => md5($this->q_param['PASSWORD']),
        'BIRTHDATE_FORMAT'  => 1,
        'SEX'               => 1,
        'POSTS_ON_PAGE'     => 1,
        'IS_ACTIVE'         => 1
    );
    $new_user = $this->set_newuser_info($fields);

	$this->authorization->setCookie($new_user['BLOG_ID'], $this->authorization->getBlogKey($new_user['BLOG_ID'],$new_user['EMAIL'],$new_user['PASSWORD_MD5']), $this->q_param['outComputer']);

	// создание папок и index-файлов в папке virtual
	$this->createBlogFolder( $this->q_param['NICK'], $new_user['BLOG_ID'] );

    // ставим всем полям "признак отображения"
    $_fields_arr = array('ICQ', 'SKYPE', 'FIO', 'BIRTHDATE', 'SEX', 'BLOG_COUNTRY_ID', 'BLOG_CITY_ID', 'HOMEPAGE', 'ABOUT', 'NAME', 'INTEREST');
    foreach ( $_fields_arr as $_field ){
    	$_field_sel = db::sql_select( 'select BLOG_FIELD_ID from BLOG_FIELD where FIELD_NAME = :field', array('field'=>$_field) );
    	if ( count($_field_sel)>0 ){
            db::insert_record( 'BLOG_SV_BLOG_FIELD',
                array(
                    'BLOG_ID' => $new_user['BLOG_ID'],
                    'BLOG_FIELD_ID' => $_field_sel[0]['BLOG_FIELD_ID']
                )
            );
        }
    }

    // рассылка сообщений
    $fileTPL = 'registrationNotice.tpl';
    // формируется тело письма для менеджера и клиента
    if ( !file_exists($this->tpl_dir.$fileTPL) ) {
        $htmlBodyToManager = 'Шаблон отсутствует: '.$fileTPL.'';
    }else{
        // инициализация шаблонизатора
        $htmlBody_tpl = new smarty_ee_module($this);
		$htmlBody_tpl->assign($this->q_param);
		$htmlBody_tpl->assign('HTTP_HOST', $_SERVER['HTTP_HOST']);
		$htmlBodyToManager = $htmlBody_tpl->fetch($this->tpl_dir . $fileTPL);

		$htmlBody_tpl->assign('toClient', 1);
		$htmlBodyToClient = $htmlBody_tpl->fetch($this->tpl_dir . $fileTPL);
        unset($htmlBody_tpl);
  	}

	$param = array(
        'to' 		=> $this->q_param['EMAIL'],
        'toName'   	=> $this->q_param['NICK'],
        'from'		=> 'webmaster@'.$_SERVER['HTTP_HOST'],
        'fromName'	=> $this->lang['sysw_blog_registration_from'].' '.$_SERVER['HTTP_HOST'],
        'subject'	=> $this->lang['sysw_blog_registration_subj'].' '.$_SERVER['HTTP_HOST'],
        'htmlBody'	=> $htmlBodyToClient
    );

    // при интеграции письмо пользователю не отправляем
    if ( !in_array('PASSWORD', $this->authorization->fields_from_client) ){
    	// отсылается уведомление клиенту
    	$this->sendMail($param);
    }

	$param['toName'] 	= 'Уважаемый администратор!';
	$param['subject'] 	= 'Регистрация нового пользователя';
	$param['htmlBody'] 	= $htmlBodyToManager;
	
	// отсылается уведомление менеджеру
	if ( $mailArr = preg_split("/[,; ]+/", $this->view_param['emailToNotice']) ){
 		for	( $i = 0; $i < count($mailArr); $i++ ){
 			if ( preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9_-]+(\.[_a-z0-9-]+)+$/i', $mailArr[$i]) ){
	 			$param['to'] = $mailArr[$i];
				$this->sendMail($param);
			}
		}
	}
	
    $this->tpl->assign('_is_registrated', 1);
}



/**
 * Проверяет корректность данных введенных пользователем
 */
protected function isCorrectClientData( $afterAuth=false, $email='' ){
    // если не передан ни один параметр
    if ( !count($this->q_param) ){
        if ( empty($this->authorization->fields_from_client) ){
            // без интеграции
            $this->tpl->assign('captcha_id', captcha::generate());
        }
        return false;
    }
    if ( !$afterAuth ){
        foreach ($this->q_param as $key=>$value){
            $this->tpl->assign($key, $value);
        }
    }

    // проверяется ник
    if ( !$this->q_param['NICK'] ){
        $this->tpl->assign('_error1', 1);
        $is_error = true;
    }else{
        // проверяется уникальность логина пользователя (ника)
        $user = $this->get_user_info_use_title($this->q_param['NICK']);
        $nick = strtolower($this->q_param['NICK']);
        if ( in_array($nick, array('adm','test','common','ru','en')) || !empty($user) && ($this->view_param['view_mode']=='registration') ){
            $this->tpl->assign('_error2', 1);
            $is_error = true;
        }
    }

    // проверяется email
    if ( !$this->q_param['EMAIL'] ){
        $this->tpl->assign('_error3', 1);
        $is_error = true;
    }elseif ( !in_array('EMAIL', $this->authorization->fields_from_client) ){
        // без интеграции
        $users_with_email = $this->get_user_info_use_email($this->q_param['EMAIL']);
        if ( (count($users_with_email)>0) && ($this->view_param['view_mode']=='registration') ){
            $this->tpl->assign('_error31', 1);
            $is_error = true;
        }
    }

	// проверяет правильность введенных паролей
	if ( !in_array('PASSWORD', $this->authorization->fields_from_client) ){
        // без интеграции
    	if ( ($this->q_param['PASSWORD']!=$this->q_param['PASSWORD2']) 
    	      // при персонализации пустое поле пароля возможно (в этом случае пароль не меняется)
             || (!$this->q_param['PASSWORD'] && $this->view_param['view_mode']!='person_data') )
        {
    		$this->tpl->assign('_error4', 1);
    		$is_error = true;
    	}
    }
    
    if ( empty($this->authorization->fields_from_client) ){
        // без интеграции
    	// здесь проверяем код на картинке
		if ( $this->view_param['view_mode'] == 'registration' && !captcha::check( $this->q_param['captcha_id'], $this->q_param['captcha_value'] ) ){
            $this->tpl->assign('_error5', 1);
            $is_error = true;
        }
    }
    
    if ( $is_error ){
        // заново генерим картинку
        if ( empty($this->authorization->fields_from_client) ){
            // без интеграции
            $this->tpl->assign('captcha_id', captcha::generate());
        }
 		$this->tpl->assign('_is_error', 1);
		return false;
	}
	return true;
}



/**
 * $action = add => отсылка сообщения модераторам сообщества о расширении сообщества
 * $action = inquiry => отсылка сообщения модераторам сообщества о желании вступить нового пользователя
 * $action = confirm => отсылка сообщения модераторам сообщества о публикации "модерируемой" записи
 * $action = del => отсылка сообщения пригласившему об отказе от вступления
 */
protected function sendMsgWhenInviteList( $blog_friend_id, $action ){
    $blog_friend_id = intval($blog_friend_id);
    if ( !( $blog_friend_id>0 ) ){
        return false;
    }
    if ( $action == 'del'){
        $_sql = 'select INVITER_ID as BLOG_ID, OWNER_ID from BLOG_FRIEND WHERE BLOG_FRIEND_ID = :friend';
    }
    if ( $action == 'add' || $action == 'inquiry'){
        $_sql = 'select FRIEND_ID as BLOG_ID, OWNER_ID from BLOG_FRIEND where IS_MODERATOR = \'1\' AND OWNER_ID IN ( select OWNER_ID from BLOG_FRIEND WHERE BLOG_FRIEND_ID = :friend )';
    }
    if ( $action == 'confirm'){
        $_sql = 'select FRIEND_ID as BLOG_ID, OWNER_ID from BLOG_FRIEND where IS_MODERATOR = \'1\' AND OWNER_ID = :friend';
    }
    if ( !empty($_sql) ){
        $user_list = db::sql_select( $_sql, array('friend'=>$blog_friend_id) );
    }
    $to_arr = array();
    if ( count($user_list)>0 ){
        $_owner_id = $user_list[0]['OWNER_ID'];
        $_friend_id = $user_list[0]['BLOG_ID'];
        foreach ( $user_list as $item ){
            $user = $this->get_user_info($item['BLOG_ID']);
            if ( !empty($user['EMAIL']) ){
                $to_arr[] = $user;
            }
        }
    }
    
    if ( count($to_arr)>0 ){
        // формируется тело письма
        $fileTPL = 'inviteNotice.tpl';
        if ( !file_exists($this->tpl_dir.$fileTPL) ) {
            $htmlBody = 'Шаблон отсутствует: '.$fileTPL.'';
        }else{
            // инициализация шаблонизатора
            $htmlBody_tpl = new smarty_ee_module($this);

            $friend_info = $this->get_user_info($_friend_id);
            $user_title = !empty($friend_info) ? $friend_info['TITLE'] : '';
            $community_info = $this->get_user_info($_owner_id);
            $community_title = !empty($community_info) ? $community_info['TITLE'] : '';
            $community_name = !empty($community_info) ? $community_info['NAME'] : '';

        	if ( $action == 'del'){
                $htmlBody_tpl->assign('ACTION_DEL', 1);
            }
        	if ( $action == 'add'){
                $htmlBody_tpl->assign('ACTION_ADD', 1);
            }
        	if ( $action == 'inquiry'){
                $htmlBody_tpl->assign('ACTION_INQUIRY', 1);
            }
        	if ( $action == 'confirm'){
                $htmlBody_tpl->assign('ACTION_CONFIRM', 1);
            }
            $htmlBody_tpl->assign('USER_TITLE', $user_title);
            $htmlBody_tpl->assign('COMMUNITY_TITLE', $community_title);
            $htmlBody_tpl->assign('COMMUNITY_NAME', $community_name);
            if ( $action == 'inquiry'){
                $community_path = $this->getPathToVI('blogs', 'community_manager', array( array('name'=>'submode', 'value'=>'party'), array('name'=>'community_id', 'value'=>$this->view_param['blog_id']) ));
                $htmlBody_tpl->assign('PATH_TO_COMMUNITYPAGE', $community_path);
            }
            $htmlBody = $htmlBody_tpl->fetch($this->tpl_dir . $fileTPL);
            unset($htmlBody_tpl);

            foreach ( $to_arr as $recepient ){
                $mail_param = array( 
                    'to' 		=> $recepient['EMAIL'],
                    'toName'   	=> (!empty($recepient['FIO']) ? $recepient['FIO'] : $recepient['TITLE']),
                    'from'		=> 'webmaster@'.$_SERVER['HTTP_HOST'],
                    'fromName'	=> 'Служба поддержки '.$_SERVER['HTTP_HOST'],
                    'htmlBody'	=> $htmlBody
                );
            	if ( $action == 'del'){
                    $mail_param['subject']="Отказ от вступления в сообщество";
                }
            	if ( $action == 'add'){
                    $mail_param['subject']="Принятие приглашения в сообщество";
                }
            	if ( $action == 'inquiry'){
                    $mail_param['subject']="Запрос на вступление в сообщество";
                }
            	if ( $action == 'confirm'){
                    $mail_param['subject']="Уведомление о публикации записи в 'модерируемом' сообществе";
                }
                // отсылается уведомление
                $this->sendMail( $mail_param );
            }
        }
    }    
}


/**
 * $action = invite => отсылка сообщения приглашаемому пользователю со ссылкой на страницу со списком его приглашений
 * $action = change => отсылка сообщения об изменении информации
 * $action = del => отсылка сообщения об удалении пользователя из сообщества
 */
protected function sendMsgWhenCommunityUser( $user_id, $community_id, $action ){
    $to_arr = array();
    if ( !empty($user_id) ) {
        $user = $this->get_user_info($user_id);
        if ( !empty($user) && !empty($user['EMAIL']) ){
            $to_arr = $user;
        }
    }

    $from_arr = array();
    if ( !empty($community_id) ) {
        $user = $this->get_user_info($community_id);
        if ( !empty($user) ){
            $from_arr = $user;
        }
    }
    
    if ( count($to_arr)>0 && count($from_arr)>0 && in_array($action, array('del', 'change', 'invite')) ){
        // формируется тело письма
        $fileTPL = 'communityUserNotice.tpl';
        if ( !file_exists($this->tpl_dir.$fileTPL) ) {
            $htmlBody = 'Шаблон отсутствует: '.$fileTPL.'';
        }else{
            // инициализация шаблонизатора
            $htmlBody_tpl = new smarty_ee_module($this);

        	if ( $action == 'del'){
                $htmlBody_tpl->assign('ACTION_DEL', 1);
            }
        	if ( $action == 'change'){
                $htmlBody_tpl->assign('ACTION_CHANGE', 1);
            }
        	if ( $action == 'invite'){
                $htmlBody_tpl->assign('ACTION_INVITE', 1);
            }
            $htmlBody_tpl->assign('COMMUNITY_TITLE', $from_arr['TITLE']);
            $htmlBody_tpl->assign('COMMUNITY_NAME', $from_arr['NAME']);
            $htmlBody_tpl->assign('PATH_TO_INVITEPAGE', $this->getPathToVI('blogs','edit_profile', array( array('name'=>'submode', 'value'=>'offers') )));
            $htmlBody = $htmlBody_tpl->fetch($this->tpl_dir . $fileTPL);
            unset($htmlBody_tpl);

            $mail_param = array( 
                'to' 		=> $to_arr['EMAIL'],
                'toName'   	=> ( !empty($to_arr['FIO']) ? $to_arr['FIO'] : $to_arr['TITLE'] ),
                'from'		=> 'webmaster@'.$_SERVER['HTTP_HOST'],
                'fromName'	=> 'Служба поддержки '.$_SERVER['HTTP_HOST'],
                'htmlBody'	=> $htmlBody
            );
        	if ( $action == 'del'){
                $mail_param['subject']="Удаление из сообщества";
            }
        	if ( $action == 'change'){
                $mail_param['subject']="Изменение данных";
            }
        	if ( $action == 'invite'){
                $mail_param['subject']="Приглашение в сообщество";
            }
            // отсылается уведомление
            $this->sendMail( $mail_param );
        }
    }    
}

/**
 * Рассылка сообщений при добавлении комментария
 */
protected function sendMsgWhenAddComment( $comment_id ){
    $comment = db::sql_select( 'select * from BLOG_COMMENT where BLOG_COMMENT_ID = :comment', array('comment'=>$comment_id) );
    if ( !(count($comment)>0) ){
        return false;
    }
    $post = db::sql_select( '
        select P.*, B.NAME as BLOG_NAME, B.TITLE as BLOG_USER, B.TITLE AS AUTHOR_TITLE, M.TITLE AS MOOD_TITLE, M.IMAGE AS MOOD_IMAGE 
        from BLOG_POST P
            inner join BLOG B on (P.BLOG_ID = B.BLOG_ID)
            left join BLOG_MOOD M on (P.BLOG_MOOD_ID = M.BLOG_MOOD_ID) 
        where P.BLOG_POST_ID = :post',
        array('post'=>$comment[0]['BLOG_POST_ID'])
    );
    if ( !(count($post)>0) ){
        return false;
    }
    $comment_parent = db::sql_select( 'select * from BLOG_COMMENT where BLOG_COMMENT_ID = :comment', array('comment'=>$comment[0]['PARENT_ID']) );
      
    $to_arr = array();
    if ( count($comment_parent)>0 && !empty($comment_parent[0]['AUTHOR_ID']) ){
        $user = $this->get_user_info($comment_parent[0]['AUTHOR_ID']);
        if ( !empty($user) && !empty($user['EMAIL']) ){
            $to_arr[] = $user;
        }
    }
    if ( count($post)>0 && !empty($post[0]['AUTHOR_ID']) && ($post[0]['AUTHOR_ID']!=$comment_parent[0]['AUTHOR_ID']) ){
        $user = $this->get_user_info($post[0]['AUTHOR_ID']);
        if ( !empty($user) && !empty($user['EMAIL']) ){
            $to_arr[] = $user;
        }
    }
    if ( count($post)>0 && !empty($post[0]['BLOG_ID']) ){
        $moderators = db::sql_select( 'select FRIEND_ID from BLOG_FRIEND where IS_MODERATOR = \'1\' AND OWNER_ID = :owner', array('owner'=>$post[0]['BLOG_ID']) );
        if ( count($moderators)>0 ){
            foreach ( $moderators as $item ){
                $user = $this->get_user_info($item['FRIEND_ID']);
                if ( !empty($user) && !empty($user['EMAIL']) ){
                    $to_arr[] = $user;
                }
            }
        }
    }

    if ( count($to_arr)>0 ){
         // формируется тело письма
        $fileTPL = 'blog_blog.tpl';
        if ( !file_exists($this->tpl_dir.$fileTPL) ) {
            $htmlBody = 'Шаблон отсутствует: '.$fileTPL.'';
        }else{
            // инициализация шаблонизатора
            $htmlBody_tpl = new smarty_ee_module($this);

            $htmlBody_tpl->assign('BLOG_NAME', $post[0]['BLOG_NAME']);
            $htmlBody_tpl->assign('BLOG_USER', $post[0]['BLOG_USER']);
            $htmlBody_tpl->assign('BLOG_ID', $this->view_param['blog_id']);
            $htmlBody_tpl->assign('HTTP_HOST', $_SERVER['HTTP_HOST']);
            $htmlBody_tpl->assign('IS_CARD', 1);
            $htmlBody_tpl->assign('IS_MAIL', 1);
            preg_match("/(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)/", $post[0]['ADDED_DATE'], $d);
            $post[0]['ADDED_DATE'] = date($this->date_format_in_blog, mktime($d[4],$d[5],$d[6],$d[2],$d[3],$d[1]));
            $image = db::sql_select( 'select * from BLOG_IMAGE where BLOG_IMAGE_ID = :image', array('image'=>$post[0]['BLOG_IMAGE_ID']) );   
            if ( is_array($image) && count($image)>0 ){
                $post[0]['IMAGE'] = 'http://'.$_SERVER['HTTP_HOST'].$image[0]['IMG'];
            }
            $tags = db::sql_select( '
                select T.* 
                from BLOG_TAG T, BLOG_POST_SV_BLOG_TAG PT 
                where T.BLOG_TAG_ID = PT.BLOG_TAG_ID AND PT.BLOG_POST_ID = :post',
                array('post'=>$post[0]['BLOG_POST_ID'])
            );
            if ( is_array($tags) && count($tags)>0 ){
                $post[0]['TAGS'] = $tags;
            }
            $post[0]['MOOD_IMAGE'] = 'http://'.$_SERVER['HTTP_HOST'].$post[0]['MOOD_IMAGE'];
            $post[0]['CARD_LINK'] = 'http://'.$_SERVER['HTTP_HOST'].'/index.php?post_id='.$post[0]['BLOG_POST_ID'];
            $post[0]['COMMENTS'] = $this->showCommentsWithParent( $comment[0]['BLOG_COMMENT_ID'], 1 );
            $htmlBody_tpl->assign('POSTS', $post );
            $htmlBody = $htmlBody_tpl->fetch($this->tpl_dir . $fileTPL);
            unset($htmlBody_tpl);
            // отсылается уведомление
            foreach ( $to_arr as $recepient ){
                $this->sendMail(
                    array(
                        'to' 		=> $recepient['EMAIL'],
                        'toName'   	=> (!empty($recepient['FIO']) ? $recepient['FIO'] : $recepient['TITLE']),
                        'from'		=> 'webmaster@'.$_SERVER['HTTP_HOST'],
                        'fromName'	=> 'Служба поддержки '.$_SERVER['HTTP_HOST'],
                        'subject'	=> 'Добавление комментария',
                        'htmlBody'	=> $htmlBody
                    )
                );
            }
        }
    }    
}

/**
 * Отсылка писем при удалении комментария
 */
protected function sendMsgWhenDelComment( $COMMENT_ID ){
    $comment = db::sql_select( 'select * from BLOG_COMMENT where BLOG_COMMENT_ID = :comment', array('comment'=>$COMMENT_ID) );
    if ( count($comment)>0 && $comment[0]['AUTHOR_ID']!=$this->authorization->blog_id ){
        $owner = $this->get_user_info($comment[0]['AUTHOR_ID']);
        if ( !empty($owner) && $owner['EMAIL']!='' ){
             // формируется тело письма
            $fileTPL = 'noticeDelComment.tpl';
            if ( !file_exists($this->tpl_dir.$fileTPL) ) {
                $htmlBody = 'Шаблон отсутствует: '.$fileTPL.'';
            }else{
                // инициализация шаблонизатора
                $htmlBody_tpl = new smarty_ee_module($this);

                $htmlBody_tpl->assign('BLOG_NAME', $post[0]['BLOG_NAME']);
                $htmlBody_tpl->assign('BLOG_USER', $post[0]['BLOG_USER']);

                preg_match("/(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)/", $comment[0]['ADDED_DATE'], $d);
                $comment[0]['ADDED_DATE'] = date($this->date_format_in_blog, mktime($d[4],$d[5],$d[6],$d[2],$d[3],$d[1]));
                $htmlBody_tpl->assign($comment[0]);
                $htmlBody_tpl->assign('HTTP_HOST', $_SERVER['HTTP_HOST']);
                $htmlBody = $htmlBody_tpl->fetch($this->tpl_dir . $fileTPL);
                unset($htmlBody_tpl);
            }
            // отсылается уведомление
            $this->sendMail(
                array(
                    'to' 		=> $owner['EMAIL'],
                    'toName'   	=> (!empty($owner['FIO']) ? $owner['FIO'] : $owner['TITLE']),
                    'from'		=> 'webmaster@'.$_SERVER['HTTP_HOST'],
                    'fromName'	=> 'Служба поддержки '.$_SERVER['HTTP_HOST'],
                    'subject'	=> 'Удаление комментария',
                    'htmlBody'	=> $htmlBody
                )
            );
        }
    }
}


/**
 * Отсылает письмо
 */
protected function sendMail($param){
    lib::post_mail(
        $param['to'], 
        $param['toName'], 
        $param['from'],
        $param['fromName'], 
        $param['subject'], 
        $param['htmlBody']
    );
}

/**
 * Проверяет закрыт ли раздел
 */
protected function is_protected_page() {
    $query = 'select IS_PROTECTED from PAGE where PAGE_ID = :page_id and SITE_ID = :site_id and VERSION = :version';
    $result = db::sql_select( $query, array('page_id'=>$this->env['page_id'], 'site_id'=>$this->env['site_id'], 'version'=>$this->env['version']) );
    return $result[0]['IS_PROTECTED'] ? true : false;
}


/**
 * Отключается кэширование модуля
 */
protected function get_hash_code() {
    return false;
}


/**
 * Генерация адреса блога пользователя с ником $nick
 */
protected function get_blog_path($nick){
    if ( defined('BLOG_SITE_ROOT') && strlen(BLOG_SITE_ROOT)>0 ){
        return 'http://'.$nick.'.'.$this->authorization->blog_site_postfix;
    }else{
        $result = db::sql_select('
            SELECT '.($this->env['version']==0 ? 'S.HOST' : 'S.TEST_HOST').' AS HOST 
            FROM SITE S 
            WHERE S.SITE_ID = :site_id',
            array('site_id'=>$this->env['site_id'])
        );
        if ( count($result)>0 ){
            return 'http://'.$result[0]['HOST'].'/blogs/'.$nick;
        }
    }
    return '';
}

/**
 * Получение информации о пользователе с указанным "ником"(поле TITLE)
 */
protected function get_user_info_use_title($title){
    $title = strtolower(trim($title));
    $user_sel = db::sql_select( 'SELECT BLOG_ID FROM BLOG WHERE TITLE = :title AND IS_ACTIVE = \'1\'', array('title'=>$title) );
	return (count($user_sel)>0) ? $this->get_user_info($user_sel[0]['BLOG_ID']) : array();
}

/**
 * Получение информации о пользователе с указанным "e-mail'ом'"(поле EMAIL)
 */
protected function get_user_info_use_email($email){
    $email = strtolower(trim($email));
    $user_sel = db::sql_select( 'SELECT BLOG_ID FROM BLOG WHERE EMAIL = :email AND IS_ACTIVE = \'1\'', array('email'=>$email) );
	return (count($user_sel)>0) ? $this->get_user_info($user_sel[0]['BLOG_ID']) : array();
}

/**
 * Получение информации о текущем пользователе
 */
protected function get_current_user_info(){
	return $this->get_user_info($this->authorization->blog_id);
}

/**
 * Получение информации о произвольном пользователе
 */
protected function get_user_info($blogID){
    $user_sel = db::sql_select( 'SELECT * FROM BLOG WHERE BLOG_ID = :blog AND IS_ACTIVE = \'1\'', array('blog'=>$blogID) );
    if ( count($user_sel)>0 ){
        $user_sel[0] = $this->authorization->getClientInfo($blogID, $user_sel[0]);
        return $user_sel[0];
    }
	return array();
}

/**
 * Сохранение информации о пользователе
 * $fields = array('NAME_FIELD1'=>'VALUE_FIELD1',...,'NAME_FIELDN'=>'VALUE_FIELDN')
 */
protected function set_user_info($blogID, $fields){
    $user_sel = db::sql_select( 'SELECT BLOG_ID FROM BLOG WHERE BLOG_ID = :blog AND IS_ACTIVE = \'1\'', array('blog'=>$blogID) );
    if ( count($user_sel)>0 ){
        $blogID = (int)$user_sel[0]['BLOG_ID'];
        db::update_record( 'BLOG', $fields, '', array( 'BLOG_ID'=>$blogID ) );
        $this->authorization->setClientInfo($blogID, $fields);
    	return true;
    }
    return false;
}

/**
 * Добавление информации о новом пользователе
 * $fields = array('NAME_FIELD1'=>'VALUE_FIELD1',...,'NAME_FIELDN'=>'VALUE_FIELDN')
 */
protected function set_newuser_info($fields){
    db::insert_record( 'BLOG', $fields );
    $new_id = db::last_insert_id( 'BLOG_SEQ' );
    // создаём новую запись в таблице связи
    $this->authorization->setNewClientInfo($new_id, $fields);
    return $this->get_user_info($new_id);
}

}

?>