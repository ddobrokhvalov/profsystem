<?php 
// ================================================
// SPAW PHP WYSIWYG editor control
// ================================================
// Russian language file
// ================================================
// Developed: Alan Mendelevich, alan@solmetra.lt
// Copyright: Solmetra (c)2003 All rights reserved.
// ------------------------------------------------
//                                www.solmetra.com
// ================================================
// v.1.0, 2003-04-10
// ================================================

// charset to be used in dialogs
$spaw_lang_charset = params::$params["encoding"]["value"];

// language text data array
// first dimension - block, second - exact phrase
// alternative text for toolbar buttons and title for dropdowns - 'title'

$spaw_lang_data = array(
  'title' => array(
    'title' => 'Редактирование в визуальном редакторе'
  ),
  'save' => array(
    'title' => 'Сохранить'
  ),
  'cut' => array(
    'title' => 'Вырезать'
  ),
  'copy' => array(
    'title' => 'Копировать'
  ),
  'paste' => array(
    'title' => 'Вставить'
  ),
  'undo' => array(
    'title' => 'Отменить'
  ),
  'redo' => array(
    'title' => 'Повторить'
  ),
  'image_insert' => array(
    'title' => 'Вставить изображение',
    'select' => 'Вставить',
	'delete' => 'Стереть', // new 1.0.5
	'delete_confirm' => 'Вы действительно хотите удалить этот файл?',
    'cancel' => 'Отменить',
    'library' => 'Библиотека',
    'preview' => 'Просмотр',
    'images' => 'Изображения',
    'upload' => 'Загрузить изображение',
    'upload_button' => 'Загрузить',
    'error' => 'Ошибка',
    'error_no_image' => 'Выберите изображение',
    'error_uploading' => 'Во время загрузки произошла ошибка. Попробуйте еще раз.',
    'error_wrong_type' => 'Неправильный тип изображения',
    'error_no_dir' => 'Библиотека не существует',
	'error_cant_delete' => 'Стереть не удалось', // new 1.0.5
  ),
  'image_prop' => array(
    'title' => 'Параметры изображения',
    'ok' => 'ГОТОВО',
    'cancel' => 'Отменить',
    'source' => 'Источник',
    'alt' => 'Краткое описание',
    'align' => 'Выравнивание',
    'left' => 'слева (left)',
    'right' => 'справа (right)',
    'top' => 'сверху (top)',
    'middle' => 'в центре (middle)',
    'bottom' => 'снизу (bottom)',
    'absmiddle' => 'абс. центр (absmiddle)',
    'texttop' => 'сверху (texttop)',
    'baseline' => 'снизу (baseline)',
    'width' => 'Ширина',
    'height' => 'Высота',
    'border' => 'Рамка',
    'hspace' => 'Гор. поля',
    'vspace' => 'Верт. поля',
    'error' => 'Ошибка',
    'error_width_nan' => 'Ширина не является числом',
    'error_height_nan' => 'Высота не является числом',
    'error_border_nan' => 'Рамка не является числом',
    'error_hspace_nan' => 'Горизонтаьные поля не является числом',
    'error_vspace_nan' => 'Вертикальные поля не является числом',
  ),
  // ФАЙЛ!
  'file_insert' => array(
    'title' => 'Вставить ссылку на файл',
    'select' => 'Вставить',
    'delete' => 'Стереть', // new 1.0.5
	'delete_confirm' => 'Вы действительно хотите удалить этот файл?',
    'cancel' => 'Отменить',
    'files' => 'Файлы',
    'upload' => 'Загрузить файл',
    'upload_button' => 'Загрузить',
    'urlname' => 'Название ссылки',
    'error' => 'Ошибка',
    'error_no_file' => 'Выберите файл',
    'error_no_url' => 'Введите название ссылки',
    'error_uploading' => 'Во время загрузки произошла ошибка. Попробуйте еще раз.',
    'error_no_dir' => 'Библиотека не существует',
    'error_cant_delete' => 'Стереть не удалось', // new 1.0.5
  ),
  //
  'hr' => array(
    'title' => 'Горизонтальная линия'
  ),
  'table_create' => array(
    'title' => 'Создать таблицу'
  ),
  'table_prop' => array(
    'title' => 'Параметры таблицы',
    'ok' => 'ГОТОВО',
    'cancel' => 'Отменить',
    'rows' => 'Строки',
    'columns' => 'Столбцы',
    'css_class' => 'Стиль', // <=== new 1.0.6
    'width' => 'Ширина',
    'height' => 'Высота',
    'border' => 'Рамка',
    'pixels' => 'пикс.',
    'cellpadding' => 'Отступ от рамки',
    'cellspacing' => 'Растояние между ячейками',
    'bg_color' => 'Цвет фона',
    'background' => 'Фоновое изображение', // <=== new 1.0.6
    'error' => 'Ошибка',
    'error_rows_nan' => 'Строки не является числом',
    'error_columns_nan' => 'Столбцы не является числом',
    'error_width_nan' => 'Ширина не является числом',
    'error_height_nan' => 'Высота не является числом',
    'error_border_nan' => 'Рамка не является числом',
    'error_cellpadding_nan' => 'Отступ от рамки не является числом',
    'error_cellspacing_nan' => 'Растояние между ячейками не является числом',
  ),
  'table_cell_prop' => array(
    'title' => 'Параметры ячейки',
    'horizontal_align' => 'Горизонтальное выравнивание',
    'vertical_align' => 'Вертикальное выравнивание',
    'width' => 'Ширина',
    'height' => 'Высота',
    'css_class' => 'Стиль',
    'no_wrap' => 'Без переноса',
    'bg_color' => 'Цвет фона',
    'background' => 'Фоновое изображение', // <=== new 1.0.6
    'ok' => 'ГОТОВО',
    'cancel' => 'Отменить',
    'left' => 'Слева',
    'center' => 'В центре',
    'right' => 'Справа',
    'top' => 'Сверху',
    'middle' => 'В центре',
    'bottom' => 'Снизу',
    'baseline' => 'Базовая линия текста',
    'error' => 'Ошибка',
    'error_width_nan' => 'Ширина не является числом',
    'error_height_nan' => 'Высота не является числом',
    
  ),
  'table_row_insert' => array(
    'title' => 'Вставить строку'
  ),
  'table_column_insert' => array(
    'title' => 'Вставить столбец'
  ),
  'table_row_delete' => array(
    'title' => 'Удалить строку'
  ),
  'table_column_delete' => array(
    'title' => 'Удалить столбец'
  ),
  'table_cell_merge_right' => array(
    'title' => 'Объединить вправо'
  ),
  'table_cell_merge_down' => array(
    'title' => 'Объединить вниз'
  ),
  'table_cell_split_horizontal' => array(
    'title' => 'Разделить по горизонтали'
  ),
  'table_cell_split_vertical' => array(
    'title' => 'Разделить по вертикали'
  ),
  'style' => array(
    'title' => 'Стиль'
  ),
  'font' => array(
    'title' => 'Шрифт'
  ),
  'fontsize' => array(
    'title' => 'Размер'
  ),
  'paragraph' => array(
    'title' => 'Абзац'
  ),
  'bold' => array(
    'title' => 'Жирный'
  ),
  'italic' => array(
    'title' => 'Курсив'
  ),
  'underline' => array(
    'title' => 'Подчеркнутый'
  ),
  'ordered_list' => array(
    'title' => 'Упорядоченный список'
  ),
  'bulleted_list' => array(
    'title' => 'Неупорядоченный список'
  ),
  'indent' => array(
    'title' => 'Увеличить отступ'
  ),
  'unindent' => array(
    'title' => 'Уменьшить отступ'
  ),
  'left' => array(
    'title' => 'Выравнивание слева'
  ),
  'center' => array(
    'title' => 'Выравнивание по центру'
  ),
  'right' => array(
    'title' => 'Выравнивание справа'
  ),
  'fore_color' => array(
    'title' => 'Цвет текста'
  ),
  'bg_color' => array(
    'title' => 'Цвет фона'
  ),
  'design_tab' => array(
    'title' => 'Переключиться в режим макетирования (WYSIWYG)'
  ),
  'html_tab' => array(
    'title' => 'Переключиться в режим редактирования кода (HTML)'
  ),
  'colorpicker' => array(
    'title' => 'Выбор цвета',
    'ok' => 'ГОТОВО',
    'cancel' => 'Отменить',
  ),
  'cleanup' => array(
    'title' => 'Чистка HTML',
    'confirm' => 'Эта операция уберет все стили, шрифты и ненужные тэги из текущего содержимого редактора. Часть или все ваше форматиолвание может быть утеряно.',
    'ok' => 'ГОТОВО',
    'cancel' => 'Отменить',
  ),
  'toggle_borders' => array(
    'title' => 'Включить рамки',
  ),
  'hyperlink' => array(
    'title' => 'Гиперссылка',
    'url' => 'Адрес',
    'name' => 'Имя',
    'target' => 'Открыть',
    'title_attr' => 'Всплывающая подсказка',
	'a_type' => 'Тип', // <=== new 1.0.6
	'type_link' => 'Ссылка', // <=== new 1.0.6
	'type_anchor' => 'Якорь', // <=== new 1.0.6
	'type_link2anchor' => 'Ссылка на якорь', // <=== new 1.0.6
	'anchors' => 'Якоря', // <=== new 1.0.6
    'ok' => 'ГОТОВО',
    'cancel' => 'Отменить',
  ),
  'hyperlink_targets' => array( // <=== new 1.0.5
  	'_self' => 'в том же фрейме (_self)',
	'_blank' => 'в новом окне (_blank)',
	'_top' => 'на все окно (_top)',
	'_parent' => 'в родительском фрейме (_parent)'
  ),
  'table_row_prop' => array(
    'title' => 'Параметры строки',
    'horizontal_align' => 'Горизонтальное выравнивание',
    'vertical_align' => 'Вертикальное выравнивание',
    'css_class' => 'Стиль',
    'no_wrap' => 'Без переноса',
    'bg_color' => 'Цвет фона',
    'ok' => 'ГОТОВО',
    'cancel' => 'Отменить',
    'left' => 'Слева',
    'center' => 'В центре',
    'right' => 'Справа',
    'top' => 'Сверху',
    'middle' => 'В центре',
    'bottom' => 'Снизу',
    'baseline' => 'Базовая линия текста',
  ),
  'symbols' => array(
    'title' => 'Спец. символы',
    'ok' => 'ГОТОВО',
    'cancel' => 'Отменить',
  ),
  'templates' => array(
    'title' => 'Шаблоны',
  ),
  'page_prop' => array(
    'title' => 'Параметры страницы',
    'title_tag' => 'Заголовок',
    'charset' => 'Набор символов',
    'background' => 'Фоновое изображение',
    'bgcolor' => 'Цвет фона',
    'text' => 'Цвет текста',
    'link' => 'Цвет ссылок',
    'vlink' => 'Цвет посщенных ссылок',
    'alink' => 'Цвет активных ссылок',
    'leftmargin' => 'Отступ слева',
    'topmargin' => 'Отступ сверху',
    'css_class' => 'Стиль',
    'ok' => 'ГОТОВО',
    'cancel' => 'Отменить',
  ),
  'preview' => array(
    'title' => 'Предварительный просмотр',
  ),
  'image_popup' => array(
    'title' => 'Popup изображения',
  ),
  'zoom' => array(
    'title' => 'Увеличение',
  ),
  'subscript' => array( // <=== new 1.0.7
    'title' => 'Нижний индекс',
  ),
  'superscript' => array( // <=== new 1.0.7
    'title' => 'Верхний индекс',
  ),
);
?>