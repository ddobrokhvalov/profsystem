<?php
/**
 * Файловый менеджер
 *
 * @package    RBC_Contents_5_0
 * @subpackage te
 * @copyright  Copyright (c) 2006 RBC SOFT
 * 
 * @todo Переделать модуль под Винду
 */
class fm extends tool
{
	/**
	 * Список корневых каталогов
	 * @var array()
	 */
	private $root_dirs = array();
	
	/**
	 * Список расширений файлов, доступных для редактирования
	 * @var array()
	 */
	private $editable_files = array(
		'php', 'php4', 'php3', 'phtml', 'phps', 'cgi', 'pl', 'js',
		'txt', 'ini', 'html', 'htm', 'css', 'xml', 'xsl', 'cfg', 'log',
		'inc', 'asp', 'asa', 'aspx', 'asax', 'jsp', 'shtml', 'shtm',
		'tpl', 'tmpl', 'vtmpl' );
	
	/**
	 * Таблица соответствия иконок типам файлов
	 * @var array()
	 */
	private $mime_icons = array(
		'img' => array( 'gif', 'jpg', 'png', 'bmp', 'ico' ), 'txt' => array( 'txt' ), 'htm' => array( 'htm', 'html' ),
		'doc' => array( 'doc' ), 'xls' => array( 'xls' ), 'ppt' => array( 'ppt' ), 'pdf' => array( 'pdf' ),
		'scr' => array( 'php', 'asp', 'js', 'vbs', 'pl', 'pm' ), 'tpl' => array( 'tpl', 'tmpl', 'vtmpl' ),
		'css' => array( 'css', 'ini' ), 'arc' => array( 'zip', 'arj', 'rar', 'gz', 'tgz' ) );
	
	/**
	 * Тип сортировки. Возможные значения: name, size, mtime, perms
	 * @var string
	 */
	private $sort;
	
	/**
	 * Порядок сортировки. Возможные значения: asc, desc
	 * @var string
	 */
	private $order;
	
	/**
	 * Конструктор
	 *
	 * @see tool::__constructor()
	 */
	function __construct( $obj, $full_object = '' )
	{
		parent::__construct( $obj, $full_object );
		
		// Получаем список корневых директорий с учетом текущего языка
		$root_dir_obj = object::factory( 'ROOT_DIR' );
		list( $dec_field, $dec_where_search, $dec_join, $dec_binds ) =
			 $root_dir_obj -> ext_field_selection( 'TITLE', 1 );
		$root_dir_obj -> __destruct();
		
		$root_dirs = db::replace_field(db::sql_select( '
			select ROOT_DIR.*, ' . $dec_field . ' as "_TITLE" from ROOT_DIR ' . $dec_join[0], $dec_binds ), 'TITLE', '_TITLE');
		
		 // Заполняем список алиасов корневых каталогов
		foreach ( $root_dirs as $root_dir )
		{
			switch ( $root_dir['ROOT_DIR_TYPE'] )
			{
				case 1: $path = realpath( params::$params['adm_data_server']['value'] ) . DIRECTORY_SEPARATOR . $root_dir['ROOT_DIR_VALUE']; break;
				case 2: $path = realpath( params::$params['common_data_server']['value'] ) . DIRECTORY_SEPARATOR . $root_dir['ROOT_DIR_VALUE']; break;
				case 3: $path = realpath( params::$params['adm_htdocs_server']['value'] ) . DIRECTORY_SEPARATOR . $root_dir['ROOT_DIR_VALUE']; break;
				case 4: $path = realpath( params::$params['common_htdocs_server']['value'] ) . DIRECTORY_SEPARATOR . $root_dir['ROOT_DIR_VALUE']; break;
				default: $path = realpath( $root_dir['ROOT_DIR_VALUE'] );
			}
			
			$this -> root_dirs[ $this->adjustPath(DIRECTORY_SEPARATOR . $root_dir['ALIAS']) ] = array('title' => $root_dir['TITLE'], 'path' => $this->adjustPath($path) );
		}
	}
	
	function adjustPath($string)
	{
		return str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $string);
	}
	
	/**
	 * Действие по умолчанию - список файлов
	 */
	protected function action_index()
	{
		$path = $_REQUEST['path'] ? $this->adjustPath($_REQUEST['path']) : DIRECTORY_SEPARATOR;
		$this -> sort = ( in_array( $_REQUEST['sort'], array( 'name', 'size', 'mtime', 'perms' ) ) ) ? $_REQUEST['sort'] : 'name';
		$this -> order = ( in_array( $_REQUEST['order'], array( 'asc', 'desc' ) ) ) ? $_REQUEST['order'] : 'asc';
		
		if ( $path == DIRECTORY_SEPARATOR )
		{
			// Выводим список корневых директорий
			$fs = array();
			foreach ( $this -> root_dirs as $root_dir_alias => $root_dir_value )
				$fs[] = array(	'name' => $root_dir_value['title'],
								'pure_name' => $root_dir_value['title'],
								'is_dir' => 1,
								'icon' => 'root',
							 	'path' => urlencode( $root_dir_alias ),
								'readable' => is_readable( $root_dir_value['path'] ) );
			
			usort( $fs, array( $this, 'sort_file_list' ) );
		}
		else
		{
			list( $path, $real_path, $root_dir ) = $this -> get_real_path( $path );
			
			$fs = filesystem::ls_r( $real_path, true, true );
			
			usort( $fs, array( $this, 'sort_file_list' ) );
			
			foreach ( $fs as & $f )
			{
				$f['path'] = urlencode( $path.DIRECTORY_SEPARATOR.$f['pure_name'] );
				$f['size'] = $f['is_dir'] ? '' : str_replace( ' ', '&nbsp;', number_format( $f['size'], 0, ',', ' ') );
				$f['mtime'] = str_replace( ' ', '&nbsp;', date( 'd.m.Y H.i', $f['mtime'] ) );
				$f['icon'] = $f['is_dir'] ? 'folder' : $this -> get_icon( $real_path.DIRECTORY_SEPARATOR.$f['pure_name'] );
				$f['readable'] = is_readable( $real_path.DIRECTORY_SEPARATOR.$f['pure_name'] );
				$f['editable'] = $f['is_dir'] ? '' : $this -> is_editable( $real_path.DIRECTORY_SEPARATOR.$f['pure_name'] );
				
				if ( $f['is_dir'] ) {
					$f['no_empty'] = false;
					if ( $dh = opendir( $real_path.DIRECTORY_SEPARATOR.$f['pure_name'] ) ) {
						while ( ( $file = readdir( $dh ) ) !== false )
							$f['no_empty'] |= $file != '.' && $file != '..';
						closedir( $dh );
					}
				}
			}
			// добавляем фиктивную ссылку на родительскую директорию
			$parent_path = ( $path == $root_dir ) ? DIRECTORY_SEPARATOR : str_replace( $this -> root_dirs[$root_dir]['path'], $root_dir, realpath( $real_path.DIRECTORY_SEPARATOR.'..') );
			$fs = array_merge( array( array(
				'pure_name' => '..',
				'is_dir' => 1,
				'icon' => 'root',
				'path' => urlencode( $parent_path ),
				'readable' => 1 ) ), $fs );
		}
		
		$tpl = new smarty_ee( metadata::$lang );
		
		$tpl -> assign( 'current_path', urlencode($path) );
		$tpl -> assign( 'sort', $this -> sort );
		$tpl -> assign( 'order', $this -> order );
		$tpl -> assign( 'file_list', $fs );
		$tpl -> assign( 'file_count', count( $fs ) - 1 );
		
		$tpl -> assign( 'status_line', $this -> get_status_line( $path ) );
		$tpl -> assign( 'base_url', $this -> url -> get_url( '', array( 'save_params' => 1 ) ) );
		
		$this -> title = metadata::$objects[$this -> obj]['title'];
		$this -> body = $tpl -> fetch( $this -> tpl_dir.'te'.DIRECTORY_SEPARATOR.'fm'.DIRECTORY_SEPARATOR.'file_list.tpl' );
	}
	
	/**
	 * Мегаважный метод возвращает канонизированные путь к файлу (относительный и абсолютный),
	 * а также текущий алиас корневого каталога. В методе также проводятся проверки на 
	 * существование пути и на принадлежность пути выбранному корневому каталогу
	 */
	protected function get_real_path( $path )
	{
		// Проверка принадлежности пути одной из корневых директорий
		$root_dir = '';
		foreach ( $this -> root_dirs as $root_dir_alias => $root_dir_value ){
			if ( strpos( $path, $root_dir_alias ) === 0 ){
				$root_dir = $root_dir_alias;
			};
		};
		if ( !$root_dir ) $this -> error_not_in_root_path( $path );
		
		// Определение реального пути с учетом /../ и символических ссылок
		$real_path = realpath( $this -> root_dirs[$root_dir]['path'] .DIRECTORY_SEPARATOR. substr( $path, strlen( $root_dir ) ) );

		// Проверка существования пути
		if ( $real_path === false ) $this -> error_not_exists( $path );
		// Дополнительная проверка принадлежности пути корневой директории
		if ( strpos( realpath($real_path), realpath($this -> root_dirs[$root_dir]['path']) ) !== 0 )
			$this -> error_not_in_root_path( $path );
		
		$path = $root_dir . substr( $real_path, strlen( $this -> root_dirs[$root_dir]['path'] ) );
		
		return array( $this->adjustPath($path), $this->adjustPath($real_path), $this->adjustPath($root_dir) );
	}
	
	/**
	 * Действие - создание файла или каталога
	 */
	protected function action_create()
	{
		$path = $this->adjustPath($_REQUEST['path']); $name = $_REQUEST['name']; $new_name = $_REQUEST['new_name'];
		
		list( $path, $real_path, $root_dir ) = $this -> get_real_path( $path );
		
		if ( !$name ) $this -> error_no_name();
		if ( !upload::is_valid_name( $name ) ) $this -> error_name_not_valid( $name );
		if ( !upload::is_valid_ext( $name ) ) $this -> error_ext_not_valid( $name );
		
		$name = upload::get_translit_file_name( $name );
		$name = upload::get_unique_file_name( $real_path, $name );
		
		if ( $new_name == 'dir' )
			if ( !( @mkdir( $real_path.DIRECTORY_SEPARATOR.$name ) &&
					@chmod( $real_path.DIRECTORY_SEPARATOR.$name, 0777 ) ) )
				$this -> error_can_not_create( $real_path.DIRECTORY_SEPARATOR.$name );
		if ( $new_name == 'file' )
			if ( !( @touch( $real_path.DIRECTORY_SEPARATOR.$name ) &&
					@chmod( $real_path.DIRECTORY_SEPARATOR.$name, 0777 ) ) )
				$this -> error_can_not_create( $real_path.DIRECTORY_SEPARATOR.$name );
		
		$this -> redirect();
	}
	
	/**
	 * Действие - переименование файла или каталога
	 */
	protected function action_rename()
	{
		$path = $this->adjustPath($_REQUEST['path']); $name = $_REQUEST['name']; $new_name = $_REQUEST['new_name'];
		
		list( $path, $real_path, $root_dir ) = $this -> get_real_path( $path );
		
		if ( !$name ) $this -> error_no_name();
		if ( !$new_name ) $this -> error_no_name();
		if ( !upload::is_valid_name( $new_name ) ) $this -> error_name_not_valid( $new_name );
		
		if ( !upload::is_valid_ext( $name ) ) $this -> error_ext_not_valid( $name );
		if ( !upload::is_valid_ext( $new_name ) ) $this -> error_ext_not_valid( $new_name );
		
		$new_name = upload::get_translit_file_name( $new_name );
		$new_name = upload::get_unique_file_name( $real_path, $new_name );
		
		if ( !file_exists( $real_path.DIRECTORY_SEPARATOR.$name ) ) $this -> error_not_exists( $real_path.DIRECTORY_SEPARATOR.$name );
		
		if ( !@rename( $real_path.DIRECTORY_SEPARATOR.$name, $real_path.DIRECTORY_SEPARATOR.$new_name ) )
			$this -> error_can_not_rename( $real_path.DIRECTORY_SEPARATOR.$name );
		
		$this -> redirect();
	}
	
	/**
	 * Действие - удаление файла или каталога
	 */
	protected function action_delete()
	{
		$path = $this->adjustPath($_REQUEST['path']); $name = $_REQUEST['name'];
		
		list( $path, $real_path, $root_dir ) = $this -> get_real_path( $path );
		
		if ( !$name ) $this -> error_no_name();
		if ( !file_exists( $real_path.DIRECTORY_SEPARATOR.$name ) ) $this -> error_not_exists( $real_path.DIRECTORY_SEPARATOR.$name );
		
		if ( !upload::is_valid_ext( $name ) ) $this -> error_ext_not_valid( $name );
		
		if ( is_dir( $real_path.DIRECTORY_SEPARATOR.$name ) )
			filesystem::rm_r( $real_path.DIRECTORY_SEPARATOR.$name, false, false );
		else
			@unlink( $real_path.DIRECTORY_SEPARATOR.$name );
		
		if ( file_exists( $real_path.DIRECTORY_SEPARATOR.$name ) )
			$this -> error_can_not_delete( $real_path.DIRECTORY_SEPARATOR.$name );
		
		$this -> redirect();
	}
	
	/**
	 * Действие - карточка аплоада файлов
	 */
	protected function action_upload()
	{
		$path = $this->adjustPath($_REQUEST['path']);
		
		list( $path, $real_path, $root_dir ) = $this -> get_real_path( $path );
		
		$tpl = new smarty_ee( metadata::$lang );
		
		$tpl -> assign( 'back_url', $this -> url -> get_url( '', array( 'restore_params' => 1 ) ) );
		$tpl -> assign( 'html_hidden', $this -> url -> get_hidden( 'upload_save', array( 'pk' => array( 'path' =>  $path ) ) ) );
		
		$this -> title = metadata::$lang['lang_fm_uploading'];
		$this -> body = $tpl -> fetch( $this -> tpl_dir.'te'.DIRECTORY_SEPARATOR.'fm'.DIRECTORY_SEPARATOR.'file_upload.tpl' );
	}
	
	/**
	 * Действие - аплоад файлов
	 */
	protected function action_upload_save()
	{
		$path = $this->adjustPath($_REQUEST['path']); $rewrite = $_REQUEST['rewrite'] ? 1 : 0;
		
		list( $path, $real_path, $root_dir ) = $this -> get_real_path( $path );
		
		for ( $i = 0; $i < count( $_FILES['file']['name'] ); $i++ )
		{
			if ( $_FILES['file']['error'][$i] == UPLOAD_ERR_INI_SIZE )
				$this -> error_upload_ini_size();
			elseif ( $_FILES['file']['error'][$i] == UPLOAD_ERR_FORM_SIZE )
				$this -> error_upload_form_size();
			elseif ( $_FILES['file']['error'][$i] == UPLOAD_ERR_PARTIAL )
				$this -> error_upload_partial();
			elseif ( $_FILES['file']['error'][$i] == UPLOAD_ERR_OK )
			{
				$file_descr = array();
				foreach ( array_keys( $_FILES['file'] ) as $file_params )
					$file_descr[$file_params] = $_FILES['file'][$file_params][$i];
				upload::upload_file( $file_descr, $real_path, false, $rewrite );
			}
		}
		
		$this -> redirect();
	}
	
	/**
	 * Действие - даунлоад файла
	 */
	protected function action_download()
	{
		$path = $this->adjustPath($_REQUEST['path']); $name = $_REQUEST['name'];
		
		list( $path, $real_path, $root_dir ) = $this -> get_real_path( $path );
		
		if ( !$name ) $this -> error_no_name();
		if ( !file_exists( $real_path.DIRECTORY_SEPARATOR.$name ) || !is_file( $real_path.DIRECTORY_SEPARATOR.$name ) )
			 $this -> error_not_exists( $real_path.DIRECTORY_SEPARATOR.$name );
		if ( !is_readable( $real_path.DIRECTORY_SEPARATOR.$name ) )
			$this -> error_not_readable( $real_path.DIRECTORY_SEPARATOR.$name );
		
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Length: '.filesize( $real_path.DIRECTORY_SEPARATOR.$name ) );
		header( 'Content-Disposition: attachment; filename="'.$name.'"' );
		
		readfile( $real_path.DIRECTORY_SEPARATOR.$name );
		
		exit;
	}
	
	/**
	 * Действие - карточка редактирования файла
	 */
	protected function action_edit()
	{
		$path = $this->adjustPath($_REQUEST['path']); $name = $_REQUEST['name'];
		
		list( $path, $real_path, $root_dir ) = $this -> get_real_path( $path );
		
		$path_line = array_keys( lib::array_reindex( $this -> get_status_line( $path ), 'name' ) ) + array( $name );
		$path_line = str_replace ( $path_line[0] . DIRECTORY_SEPARATOR, $path_line[0] . ': ', join( DIRECTORY_SEPARATOR, $path_line ) );
		
		if ( !$name ) $this -> error_no_name();
		if ( !file_exists( $real_path.DIRECTORY_SEPARATOR.$name ) || !is_file( $real_path.DIRECTORY_SEPARATOR.$name ) )
			 $this -> error_not_exists( $real_path.DIRECTORY_SEPARATOR.$name );
		if ( !is_readable( $real_path.DIRECTORY_SEPARATOR.$name ) )
			$this -> error_not_readable( $real_path.DIRECTORY_SEPARATOR.$name );
		if ( !$this -> is_editable( $real_path.DIRECTORY_SEPARATOR.$name ) )
			$this -> error_not_editable( $real_path.DIRECTORY_SEPARATOR.$name );
		
		$tpl = new smarty_ee( metadata::$lang );
		
		$tpl -> assign( 'text', file_get_contents( $real_path.DIRECTORY_SEPARATOR.$name ) );
		$tpl -> assign( 'readonly', !is_writeable( $real_path.DIRECTORY_SEPARATOR.$name ) );
		$tpl -> assign( 'path_line', $path_line.DIRECTORY_SEPARATOR.$name );
		
		$tpl -> assign( 'back_url', $this -> url -> get_url( '', array( 'restore_params' => 1 ) ) );
		$tpl -> assign( 'html_hidden', $this -> url -> get_hidden( 'edit_save', array( 'pk' => array( 'path' =>  $path, 'name' => $name ) ) ) );
		
		// Выводим сообщение о невозможности редактирования файла
		if ( !is_writeable( $real_path.DIRECTORY_SEPARATOR.$name ) )
		{
			$error_tpl = new smarty_ee( metadata::$lang );
			$error_tpl -> assign( 'msg', metadata::$lang['lang_fm_not_editable'] );
			$tpl -> assign( 'error_message', $error_tpl -> fetch(
					params::$params['adm_data_server']['value'] . 'tpl'.DIRECTORY_SEPARATOR.'core'
						.DIRECTORY_SEPARATOR.'object'.DIRECTORY_SEPARATOR.'html_warning.tpl'
				) 
			);
		}
		
		$this -> title = metadata::$lang['lang_fm_editing'];
		$this -> body = $tpl -> fetch( $this -> tpl_dir.'te'.DIRECTORY_SEPARATOR.'fm'.DIRECTORY_SEPARATOR.'file_edit.tpl' );
	}
	
	/**
	 * Действие - сохранение файла
	 */
	protected function action_edit_save()
	{
		$path = $this->adjustPath($_REQUEST['path']); $name = $_REQUEST['name']; $text = $_REQUEST['text'];
		
		list( $path, $real_path, $root_dir ) = $this -> get_real_path( $path );
		
		if ( !$name ) $this -> error_no_name();
		if ( !file_exists( $real_path.DIRECTORY_SEPARATOR.$name ) || !is_file( $real_path.DIRECTORY_SEPARATOR.$name ) )
			 $this -> error_not_exists( $real_path.DIRECTORY_SEPARATOR.$name );
		if ( !is_writeable( $real_path.DIRECTORY_SEPARATOR.$name ) )
			$this -> error_not_writeable( $real_path.DIRECTORY_SEPARATOR.$name );
		if ( !$this -> is_editable( $real_path.DIRECTORY_SEPARATOR.$name ) )
			$this -> error_not_editable( $real_path.DIRECTORY_SEPARATOR.$name );
		
		file_put_contents( $real_path.DIRECTORY_SEPARATOR.$name, $text );
		
		$this -> redirect();
	}
	
	/**
	 * Построение статусной строки для текущего пути
	 */
	private function get_status_line( $path )
	{
		$status_line = array();
		while( preg_match( '/\/([^\/]+)$/', $path, $path_match ) )
		{
			$status_line[] = array( 'name' => $path_match[1], 'link' => $path );
			$path = preg_replace( '/\/[^\/]+$/' , '', $path );
		}
		
		$status_line = array_reverse( $status_line );
		
		if ( count( $status_line ) )
			$status_line[0]['name'] =
				$this -> root_dirs[$status_line[0]['link']]['title'];
		
		return $status_line;
	}
	
	/**
	 * Метод для параметрической сортировки списка файлов/каталогов
	 */
	private function sort_file_list( $a, $b )
	{
		if ( !$a['is_dir'] && $b['is_dir'] ) return 1;
		if ( $a['is_dir'] && !$b['is_dir'] ) return -1;
		if ( $this -> sort == 'size' )
			$result = strnatcmp( $a[ $this -> sort ], $b[ $this -> sort ] );
		else
			$result = strcmp( $a[ $this -> sort ], $b[ $this -> sort ] );
		return ( ( $this -> order == 'asc' ) ? 1 : -1 ) * $result;
	}
	
	/**
	 * Редирект на список файлов в заданном каталоге
	 */
	private function redirect()
	{
		header( 'Location: ' . $this -> url -> get_url( '', array( 'restore_params' => 1 ) ) );
	}
	
	/**
	 * Метод возвращает имя иконки по расширению файла
	 */
	private function get_icon( $name )
	{
		$name_parts = pathinfo( $name );
		$ext = $name_parts['extension'];
		foreach ( $this -> mime_icons as $icon => $types )
			if ( in_array( $ext, $types ) ) return $icon;
		return 'bin';
	}
	
	/**
	 * Проверка принадлежности файла множеству редактируемых файлов
	 */
	private function is_editable( $name )
	{
		$name_parts = pathinfo( $name );
		$ext = $name_parts['extension'];
		return in_array( $ext, $this -> editable_files ) &&
			filesize( $name ) <= params::$params['max_edit_file_size']['value'];
	}
	
	
	private function error_not_in_root_path( $name = '' ) {
		throw new Exception( metadata::$lang['lang_fm_tool'] . ': ' . metadata::$lang['lang_fm_not_in_root_path'] . ' "' . $name . '"' );
	}
	private function error_no_name() {
		throw new Exception( metadata::$lang['lang_fm_tool'] . ': ' . metadata::$lang['lang_fm_no_name'] );
	}
	private function error_name_not_valid( $name = '' ) {
		throw new Exception( metadata::$lang['lang_fm_tool'] . ': ' . metadata::$lang['lang_fm_name_not_valid'] . ' "' . $name . '"' );
	}
	private function error_ext_not_valid( $name = '' ) {
		throw new Exception( metadata::$lang['lang_fm_tool'] . ': ' . metadata::$lang['lang_fm_ext_not_valid'] . ' "' . $name . '"' );
	}
	private function error_not_exists( $name = '' )	{
		throw new Exception( metadata::$lang['lang_fm_tool'] . ': ' . metadata::$lang['lang_fm_not_exists'] . ' "' . $name . '"' );
	}
	private function error_can_not_create( $name = '' )	{
		throw new Exception( metadata::$lang['lang_fm_tool'] . ': ' . metadata::$lang['lang_fm_can_not_create'] . ' "' . $name . '"' );
	}
	private function error_can_not_rename( $name = '' ) {
		throw new Exception( metadata::$lang['lang_fm_tool'] . ': ' . metadata::$lang['lang_fm_can_not_rename'] . ' "' . $name . '"' );
	}
	private function error_can_not_delete( $name = '' ) {
		throw new Exception( metadata::$lang['lang_fm_tool'] . ': ' . metadata::$lang['lang_fm_can_not_delete'] . ' "' . $name . '"' );
	}
	private function error_not_readable( $name = '' ) {
		throw new Exception( metadata::$lang['lang_fm_tool'] . ': ' . metadata::$lang['lang_fm_not_readable'] . ' "' . $name . '"' );
	}
	private function error_not_writeable( $name = '' ) {
		throw new Exception( metadata::$lang['lang_fm_tool'] . ': ' . metadata::$lang['lang_fm_not_writeable'] . ' "' . $name . '"' );
	}
	private function error_not_editable( $name = '' ) {
		throw new Exception( metadata::$lang['lang_fm_tool'] . ': ' . metadata::$lang['lang_fm_not_editable'] . ' "' . $name . '"' );
	}
	private function error_upload_ini_size() {
		throw new Exception( metadata::$lang['lang_fm_tool'] . ': ' . metadata::$lang['lang_upload_err_ini_size'] . ' ( ' . ini_get( 'upload_max_filesize' ) . ' )' );
	}
	private function error_upload_form_size() {
		throw new Exception( metadata::$lang['lang_fm_tool'] . ': ' . metadata::$lang['lang_upload_err_form_size'] );
	}
	private function error_upload_partial( $name = '' ) {
		throw new Exception( metadata::$lang['lang_fm_tool'] . ': ' . metadata::$lang['lang_upload_err_partial'] );
	}
}
