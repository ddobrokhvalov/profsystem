<?php
/**
 * Инициализация БД значениями
 *
 * Кодировка файла: utf8
 */



/**
 * DML Блоги - Поля
 */
$BLOG_FIELD_param = array(
    array('id'=>1, 'title'=>'Ник(Название сообщества)', 'field'=>'TITLE'),
    array('id'=>2, 'title'=>'E-mail', 'field'=>'EMAIL'),
    array('id'=>3, 'title'=>'ICQ', 'field'=>'ICQ'),
    array('id'=>4, 'title'=>'SKYPE', 'field'=>'SKYPE'),
    array('id'=>5, 'title'=>'ФИО', 'field'=>'FIO'),
    array('id'=>6, 'title'=>'Дата рождения', 'field'=>'BIRTHDATE'),
    array('id'=>7, 'title'=>'Пол', 'field'=>'SEX'),
    array('id'=>8, 'title'=>'Страна', 'field'=>'BLOG_COUNTRY_ID'),
    array('id'=>9, 'title'=>'Город', 'field'=>'BLOG_CITY_ID'),
    array('id'=>10, 'title'=>'Web-сайт', 'field'=>'HOMEPAGE'),
    array('id'=>11, 'title'=>'О себе', 'field'=>'ABOUT'),
    array('id'=>12, 'title'=>'Заголовок блога(сообщества)', 'field'=>'NAME'),
    array('id'=>13, 'title'=>'Интересы', 'field'=>'INTEREST')
);
foreach ( $BLOG_FIELD_param as $item_BLOG_FIELD_param ){
    db::insert_record( 'BLOG_FIELD', 
        array( 
            'BLOG_FIELD_ID' => $item_BLOG_FIELD_param['id'], 
            'TITLE' => $item_BLOG_FIELD_param['title'], 
            'FIELD_NAME' => $item_BLOG_FIELD_param['field']
        )
    );
}

/**
 * DML Блоги - Параметры изображении
 */
db::insert_record( 'BLOG_IMAGE_SETTINGS', 
    array( 
        'BLOG_IMAGE_SETTINGS_ID' => 1, 
        'SIZE' => 100, 
        'WIDTH' => 160, 
        'HEIGHT' => 160, 
        'TOTAL' => 10
    )
);

/**
 * DML Блоги - Настроения
 */
$BLOG_MOOD_param = array(
    array('id'=>1, 'title'=>'весёлый', 'image'=>'/common/upload/blogs/mood/ar.gif'),
    array('id'=>2, 'title'=>'удивлённый', 'image'=>'/common/upload/blogs/mood/ai.gif'),
    array('id'=>3, 'title'=>'злой', 'image'=>'/common/upload/blogs/mood/aq.gif')
);
foreach ( $BLOG_MOOD_param as $item_BLOG_MOOD_param ){
    db::insert_record( 'BLOG_MOOD', 
        array( 
            'BLOG_MOOD_ID' => $item_BLOG_MOOD_param['id'], 
            'TITLE' => $item_BLOG_MOOD_param['title'], 
            'IMAGE' => $item_BLOG_MOOD_param['image']
        )
    );
}

?>