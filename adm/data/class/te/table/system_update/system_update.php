<?PHP
	include_once(params::$params['common_data_server']['value']."lib/pear/Tar.php"); 
	include_once(params::$params["adm_data_server"]["value"]."class/te/table/system_update/exception_system_update.php");

	/**
	* Класс загрузки обновлений для системы RBC Contents
	*
	* @package		RBC_Contents_5_0
	* @subpackage te
	* @copyright	Copyright (c) 2007 RBC SOFT
	* @author Alexandr Vladykin
	*/

	class system_update extends table {
		
		/**
		* Путь к exec_script-ам для пользования SuExec
		*/
		const HTTP_EXEC_SCRIPT_PATH='/cgi-bin/';

		/**
		* Имя поля для заливки файла
		*/
		const UPLOAD_FILE_FIELD_NAME='UPDATE_FILE';
		
		
		/**
		* Кодировка обновления по умолчанию (если не указана другая)
		*/
		
		const default_enctype = 'utf-8';

		/**
		* @var string $updates_path Поле, содержашее путь к дирректории, куда аплоадятся файлы обновления
		*/

		public  $updates_path = '{adm_data_server}updates/';
		
		/**
		* @var array $writeable_dirs	Список каталогов с файлами ядра
		* @todo Поменять metadata_template.php
		*/
		
		public $writeable_dirs  = array (
			"te" => array (
				'{adm_data_server}class/core/',
				'{adm_data_server}class/te/',
				'{adm_data_server}def/te_objects.php',
				'{adm_data_server}installator/registrator.php',
				'{adm_data_server}installator/xml_processor_registrator.php',
				'{adm_data_server}installator/xml_processor_registrator_mysql.php',
				'{adm_data_server}lang/ru/lang.ini',
				'{adm_data_server}lang/ru/core/',
				'{adm_data_server}lang/ru/te/',
				'{adm_data_server}lang/ru/def/te_objects.ini',
				'{adm_data_server}lang/en/lang.ini',
				'{adm_data_server}lang/en/core/',
				'{adm_data_server}lang/en/te/',
				'{adm_data_server}lang/en/def/te_objects.ini',
				'{adm_data_server}prebuild/',
				'{adm_data_server}tpl/core/',
				'{adm_data_server}tpl/lib/',
				'{adm_data_server}tpl/te/',
				'{adm_htdocs_server}editor/',
				'{adm_htdocs_server}help/',
				'{adm_htdocs_server}index.php',
				'{common_data_server}interface/',
				'{common_data_server}lib/',
				'{common_htdocs_server}adm/',
				'{common_htdocs_server}js/',
			),
			"cms" => array (
				'{adm_data_server}class/cms/',
				'{adm_data_server}class/te/tool/autotest/module/check_content/',
				'{adm_data_server}class/te/tool/autotest/module/check_content_map/',
				'{adm_data_server}class/te/tool/autotest/module/check_main_area/',
				'{adm_data_server}class/te/tool/autotest/module/check_page_area/',
				'{adm_data_server}class/te/tool/autotest/module/check_page_template/',
				'{adm_data_server}class/te/tool/autotest/module/check_template_area/',
				'{adm_data_server}class/te/tool/autotest/module/check_template_area_map/',
				'{adm_data_server}class/te/tool/autotest/module/check_template_type/',
				'{adm_data_server}class/te/tool/autotest/module/check_unusable_template/',
				'{adm_data_server}lang/ru/def/cms_objects.ini',
				'{adm_data_server}lang/en/def/cms_objects.ini',
				'{adm_data_server}lang/ru/cms/',
				'{adm_data_server}lang/en/cms/',
				'{adm_data_server}tpl/cms/',
				'{adm_data_server}def/cms_objects.php',
				'{adm_htdocs_server}toolbar/',
				'{common_htdocs_server}js/lang/ru/cms.js',
				'{common_htdocs_server}js/lang/en/cms.js',
			),
		);		
		/**
		*  array $exclude_writeable_list	Шаблоны регулярных выражений, исключающих дирректорию по пути из ядра
		*/
		
		public $exclude_writeable_list = array (
			'/lang\/spec\.ini/',
			'|prebuild/metadata_(?!template)[A-Za-z]+\.php|',
			'/templates_c/',
			'/CVS/',
			'/autotest\.xml/',
			'/check_search_/',
			'/check_taxonomy/'
		);
					
		/**
		* список параметров класса, записанных через шаблон, нужно преобразовать в соответствии с параметрами
		*/
		
		public $constant_list = array('updates_path', 'writeable_dirs');

		/**
		* @var mixed $upload_field Поле аплоада файла в общем формате для полей
		*/
		
		private $upload_field=array();
		
		/**
		* @var array $exec_scripts Массив путей к exec-скриптам
		*/
		private $exec_scripts;
		
		/**
		* @var string $last_update_date Дата последнего обновления, которое не просто зарегистрировано
		*/
		private $last_update_date;
		
		/**
		* @var string $last_installed_update_date Дата последнего установленного обновления
		*/
		private $last_installed_update_date;
		

		/**
		* Конструктор, добавляем дополнительные необходимые значения полей
		*/
		
		function __construct($obj, $full_object=""){
			parent::__construct($obj, $full_object);
			$this->set_exec_scripts();
			system_params::parse_template_param_for_object ($this, $this->constant_list);
		}
		
		/**
		* Установка путей к exec_скриптам
		*/
		
		private function set_exec_scripts() {
			$this->exec_scripts = array (
				'check_can_read_script' => self::HTTP_EXEC_SCRIPT_PATH.'can_read.cgi',
				'check_can_write_script' => self::HTTP_EXEC_SCRIPT_PATH.'can_write.cgi',
				'copy_script' => self::HTTP_EXEC_SCRIPT_PATH.'copy.cgi',
				'remove_script' => self::HTTP_EXEC_SCRIPT_PATH.'rm.cgi'
			);
		}
		
		
		// -----------------------------------------------------------------------------
		// РЕГИСТРАЦИЯ ОБНОВЛЕНИЯ
		// -----------------------------------------------------------------------------
		
		/**
		* Совершаем регистрацию в системе обновления, дополнительно пишем в лог инфу если все нормально
		*/	 

		public function exec_add($raw_fields, $prefix) {
			$result=parent::exec_add($raw_fields, $prefix);
			$this->full_object->log_register_update ('update_registered', $result);
			return $result;
		}

		
		/**
		* Переопределяем обработку полей - получаем файл обновления, и по нему определяем все необходимые поля для занесения в БД
		* Сделано так, потому что все поля у нас объявлены как недобавляемые, поэтому родительским get_prepared_fields воспользоваться затруднительно
		*/
		
		public function get_prepared_fields($raw_fields, $prefix, $mode){
			$upload_file=$this->field->get_prepared($raw_fields[$prefix.'UPLOAD_FIELD'], metadata::$objects[$this->obj]['fields']['UPLOAD_FIELD'], $prefix.'UPLOAD_FIELD');
			$fields = $this->upload_system_update($upload_file);
			return $fields;
		}
		
		/**
		* функция обработки файла обновления
		* @param string $filepath - путь к файлу
		*/
		
		private function upload_system_update($filepath) {
			// в случае если есть такой параметр, то было подтверждение, таким образом файл обновления уже разархивирован и готов к использованию
			if ($_POST['tmp_dirname']) {
				$tmp_dirname=$_POST['tmp_dirname'];
			}
			else {
				$filename=basename($filepath);
			
				// сначала создаем временный каталог, и в него разархивируем все
				$tmp_dirname = lib::tempdir($this->updates_path, 'tmp_', 0777).'/';
			
				// Копируется и разархивируется обновление
				rename($filepath, $tmp_dirname.$filename);
	
				$tar = new Archive_Tar($tmp_dirname.$filename, true);
				$result = $tar -> extract($tmp_dirname);
			}
			
			$install_info_arr = $this->get_install_info($tmp_dirname);
			
			if (!($db_date=$install_info_arr[0]['attributes']['date']))
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_has_bad_date'].' "'.$install_info_arr[0]['attributes']['name'].'"');
			
 			if ($db_date < params::$params['system_revision_date']['value'])
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_is_earlier_than_system_revision'].'='.params::$params['system_revision_date']['value'].' '.metadata::$lang['lang_for'].' "'.$install_info_arr[0]['attributes']['name'].'"');
			
			if ($system_update = db::sql_select("select * from SYSTEM_UPDATE where SU_DATE = :su_date",	array('su_date'=>$db_date))) {
				// если такое обновление уже установлено, то кидаем Exception
				if ($system_update[0]['SU_STATE'] == 'installed') {
						throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_update_already_installed'].lib::unpack_date($db_date));
				} 
				elseif ($system_update[0]['SU_STATE'] == 'registered') {
					// если такое обновление уже зарегистрировано, то переспрашиваем пользователя, хочет ли он его перерегистрировать
					if ($this->confirm_action($this->get_msg_about_reregister_update($this->get_dirname_by_date($system_update[0]['SU_DATE']), $tmp_dirname), array('tmp_dirname'=>$tmp_dirname)))
					//if ($this->confirm_action(metadata::$lang['lang_system_update_are_you_sure_to_reregister_update'], array('tmp_dirname'=>$tmp_dirname)))
						$this->exec_delete(array('SYSTEM_UPDATE_ID'=>$system_update[0]['SYSTEM_UPDATE_ID']));
					else {
						$this->url->redirect();
						exit;
					}
				}
				elseif (in_array($system_update[0]['SU_STATE'], array('install_failed', 'uninstall_failed'))) {
					throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_has_install_failed']);
				}
			}

			// Переименовываем каталог в соответствии с числом обновления
			
			rename($tmp_dirname, $this->updates_path.$install_info_arr[0]['attributes']['_date']);
			//@mkdir($dirname, 0777, 1);

			$system_update_arr['TITLE'] = $install_info_arr[0]['attributes']['name'];
			$system_update_arr['PREVIOUS_UPDATE'] = $install_info_arr[0]['attributes']['previous_update'];
			if ($install_info_arr[0]['attributes']['not_uninstallable']) 
				$system_update_arr['NOT_UNINSTALLABLE']=1;
			
			$install_info_arr = $install_info_arr[0]['children'];
	
			foreach ($install_info_arr as $install_info) {
				 if ($install_info['tag'] == 'descr') {
					 $system_update_arr['DESCR'] = $install_info['value'];
					 break;			
				 }
			}
	
			$system_update_arr['SU_DATE']=$db_date;
			$system_update_arr['SU_STATE']='registered';	
			
			return $system_update_arr;
		}
		
		/**
		* Возвращает сообщение о перезаливке файла
		* @param string $old_dirname Директории с зарегистренным обновлением
		* @param string $new_dirname Директория с новозалитым обновлением
		* @return string
		*/ 
		private function get_msg_about_reregister_update($old_dirname, $new_dirname) {
			$old_data = $this->get_install_info($old_dirname);
			$new_data = $this->get_install_info($new_dirname);
			
			return metadata::$lang['lang_system_update_are_you_sure_to_reregister_update'].'<BR><BR>'.
			metadata::$lang['lang_system_update_registered_earlier'].': <BR>'.
			metadata::$lang['lang_date'].': '.$old_data[0]['attributes']['_date'].'<BR>'.
			metadata::$lang['lang_system_update_filesize'].': '.number_format( filesize($old_dirname.$old_data[0]['attributes']['_date'].'.tar.gz'), 0, ',', ' ').' <BR><BR>'.
			metadata::$lang['lang_system_update_registered_new'].': <BR>'.
			metadata::$lang['lang_date'].': '.$new_data[0]['attributes']['_date'].'<BR>'.
			metadata::$lang['lang_system_update_filesize'].': '.number_format( filesize($new_dirname.$new_data[0]['attributes']['_date'].'.tar.gz'), 0, ',', ' ').' <BR><BR>';
		}

		// -----------------------------------------------------------------------------
		// ОПЕРАЦИИ С ОБНОВЛЕНИЯМИ СИСТЕМЫ
		// -----------------------------------------------------------------------------
		
		public function action_index() {
			// устанавливаем статус обновления для install_failed, uninstall_failed
			$cancelled_updates = db::sql_select('SELECT * FROM SYSTEM_UPDATE WHERE SU_STATE IN (\'install_failed\', \'uninstall_failed\')');
			if (sizeof($cancelled_updates)) 
				foreach($cancelled_updates as $cu)
					$this->set_update_status($cu);

			parent::action_index();			
		}

		/**
		* Меняем пункты меню для каждой записи
		* Для не установленных обновлений будет Установить/Удалить
		* Для установленных - Отменить установку
		*/
		
		public function get_index_ops($record) {
			$pk=$this->primary_key->get_from_record($record);
			
			$record=$this->full_object->get_change_record($pk);
			$do_status=base64_encode(serialize(array('pk'=>$pk)));
			$ops=array();
			
			if ($record['SU_STATE']=='registered') {
				$ops[]=array("name"=>"install", "alt"=>metadata::$lang["lang_system_update_install"], "url"=>lib::make_request_uri(array("obj"=>"SYSTEM_UPDATE", "action"=>"distributed", "do_op"=>"install", "do_status"=>$do_status)));
				$ops[]=array("name"=>"delete", "alt"=>metadata::$lang["lang_delete"], "url"=>$this->url->get_url("delete" ,array("pk"=>$pk)), "confirm" => true );
			}
			elseif ($record['SU_STATE']=='installed') {
				// анинсталлировать можно только самое последнее обновление
				if ($record['SU_DATE']==$this->get_last_update_date() && !$record['NOT_UNINSTALLABLE'])
					$ops[]=array("name"=>"uninstall", "alt"=>metadata::$lang["lang_system_update_uninstall"], "url"=>lib::make_request_uri(array("obj"=>"SYSTEM_UPDATE", "action"=>"distributed", "do_op"=>"uninstall", "do_status"=>$do_status)), "confirm" => true,  "confirm_question" => metadata::$lang['lang_system_update_do_you_really_want_to_uninstall_update']);
			}
			elseif ($record['SU_STATE']=='install_failed') {
				$ops[]=array("name"=>"continue_install_failed", "alt"=>metadata::$lang["lang_system_update_continue_install_failed"], "url"=>lib::make_request_uri(array("obj"=>"SYSTEM_UPDATE", "action"=>"distributed", "do_op"=>"continue_install_failed", "do_status"=>$do_status)));
				$ops[]=array("name"=>"uninstall_install_failed", "alt"=>metadata::$lang["lang_system_update_uninstall"], "url"=>lib::make_request_uri(array("obj"=>"SYSTEM_UPDATE", "action"=>"distributed", "do_op"=>"uninstall_install_failed", "do_status"=>$do_status)));
			}
			elseif ($record['SU_STATE']=='uninstall_failed') {
				$ops[]=array("name"=>"continue_uninstall_failed", "alt"=>metadata::$lang["lang_system_update_continue_uninstall_failed"], "url"=>lib::make_request_uri(array("obj"=>"SYSTEM_UPDATE", "action"=>"distributed", "do_op"=>"continue_uninstall_failed", "do_status"=>$do_status)));
				$ops[]=array("name"=>"uninstall_uninstall_failed", "alt"=>metadata::$lang["lang_system_update_uninstall_uninstall_failed"], "url"=>lib::make_request_uri(array("obj"=>"SYSTEM_UPDATE", "action"=>"distributed", "do_op"=>"uninstall_uninstall_failed", "do_status"=>$do_status)));
			}
			
			return array('_ops'=>$ops);
		}
		
		/**
		* Устанавливает статус обновления. Если обновление не было в прошлый раз завершено, и нет действий, которыми необходимо его вернуть к 
		* первоначальному состоянию, то устанавливает ему первоначальное состояние
		*/
		
		private function set_update_status ($record) {
			// если была ошибка, то проверяем очередь действий, если для отмены действий не нужно, то устанавливаем первоначальный статус
			if (in_array($record['SU_STATE'], array('install_failed', 'uninstall_failed'))) {
				$queue = db::sql_select ('SELECT * FROM SYSTEM_UPDATE_ACTION_QUEUE WHERE SYSTEM_UPDATE_ID=:system_update_id AND STATUS="done" AND REVERT_ACTION <> ""', array('system_update_id'=>$record['SYSTEM_UPDATE_ID']));
				if (!sizeof($queue)) {
					$fields=array('SU_STATE'=>($record['SU_STATE']=='install_failed')?'registered':'installed');
					db::update_record($this->obj, $fields, "change", array('SYSTEM_UPDATE_ID'=>$record['SYSTEM_UPDATE_ID']));
					db::sql_query('DELETE FROM SYSTEM_UPDATE_ACTION_QUEUE WHERE SYSTEM_UPDATE_ID=:system_update_id', array('system_update_id'=>$record['SYSTEM_UPDATE_ID']));
				}
			}
		}
		
		/**
		* Выполняем удаление обновления, если оно только зарегистрировано в системе, но не инсталлировано
		*/
		
		public function exec_delete($pk) {
			$record=$this->full_object->get_change_record($pk, true);
	
			if ($record['SU_STATE']=='installed')
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_can_not_delete_due_update_is_installed'].": \"".$this->full_object->get_record_title($pk)."\" (".$this->primary_key->pk_to_string($pk).")");
			
			parent::exec_delete($pk);
		}
		
		/**
		* Финализация удаления
		*/
		
		public function ext_finalize_delete($pk, $partial=false) {
			parent::ext_finalize_delete($pk, $partial);
			$record=$this->full_object->get_change_record($pk, true);
			$dirname = $this->get_dirname_by_date ($record['SU_DATE']);
			filesystem::rm_r($dirname);
			$this->full_object->log_register_update ('update_deleted', $record);
		}
		

		// -----------------------------------------------------------------------------
		// РАСПРЕДЕЛЕННАЯ ОПЕРАЦИЯ ИНСТАЛЛЯЦИИ ОБНОВЛЕНИЯ
		// -----------------------------------------------------------------------------
		
		/**
		* Функция для получения общей информации о распределенной (distributed) операции инсталляции
		* @param array $status - передаваемые данные
		* @return array
		*/ 
		
		public function install_info (&$status) {
			$record=$this->full_object->get_change_record($status['pk'], true);
			
			if ($record['SU_STATE']=='installed')
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_is_already_installed'].": \"".$this->full_object->get_record_title($status['pk'])."\" (".$this->primary_key->pk_to_string($status['pk']).")");
				
			if ($record['PREVIOUS_UPDATE']!=$this->get_last_installed_update_date())
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_no_previous_update_installed'].': '.lib::unpack_date($record['PREVIOUS_UPDATE']).": \"".$this->full_object->get_record_title($status['pk'])."\" (".$this->primary_key->pk_to_string($status['pk']).")");
			
			$dirname=$this->get_dirname_by_date($record['SU_DATE']);
			
			$install_info = $this->get_install_info($dirname);
			$install_info_arr = $install_info[0]['children'];
			
			// создаем очередь действий инсталляции
			$total=$this->set_action_queue($record, $install_info_arr, array(array('action'=>'check_update'), array('action'=>'create_backup'), array('action'=>'process_update', 'revert_action'=>'uninstall_update')), 'check_install_action');

			// начинаем инсталляцию, присваиваем статус "install_failed", чтобы он остался если какие-то проблемы возникли в процессе инсталляции
			$fields=array('SU_STATE'=>'install_failed');
			db::update_record($this->obj, $fields, "change", array('SYSTEM_UPDATE_ID'=>$record['SYSTEM_UPDATE_ID']));


			$status['log_record_id'] = $this->log_register_update('update_install', $status['pk']['SYSTEM_UPDATE_ID']);

			return array("title"=>metadata::$lang["lang_system_update_install"], "back_url"=>$this->url->get_url(), "total"=>$total, "complete_message"=>metadata::$lang["lang_operation_completed_succesfully"], "for_once"=>5, "exception_fatal"=>1);
		}
		
		/**
		* Получение списка операций для распределенной операции инсталляции
		*/
		
		public function install_list(&$status, $from, $for_once) {
			$actions=db::sql_select('SELECT SYSTEM_UPDATE_ACTION_QUEUE_ID, ACTION AS PENDING_ACTION, FILE_ELEMENT FROM SYSTEM_UPDATE_ACTION_QUEUE WHERE SYSTEM_UPDATE_ID=:system_update_id AND STATUS=:status ORDER BY SYSTEM_UPDATE_ACTION_QUEUE_ID LIMIT '.$for_once, array('system_update_id'=>$status['pk']['SYSTEM_UPDATE_ID'], 'status'=>'pending'));
			return $this->distributed_list($status, $actions);
		}
		
		/**
		* Обработка одной операции для распределенной операции инсталляции
		*/
		
		public function install_item ($item, &$status) {
			return $this->distributed_item($item, $status);
		}
		
		/**
		* Обработка счастливого окончания распределенной операции инсталляции
		*/
		
		public function install_commit (&$status) {
			$this->distributed_commit($status, 'installed');
			
			$this->set_log_operation($status['log_record_id'], 'update_installed');
		}

		/**
		* Проверка, нужно ли проводить действие с конкретным файловым элементом
		* @param mixed $file_element - файловый элемент
		* @param string $action - действие
		* @return boolean
		*/

		private function check_install_action($file_element, $action) {
			if (
				 ($action=='create_backup') 
					&& 
						(!$file_element['attributes']['original_checksum'] || !$file_element['attributes']['current_checksum'])
				 ) 
						return false;
						
			return true;
		}

		// *** ДЕЙСТВИЯ ИНСТАЛЛЯЦИИ ДЛЯ КАЖДОГО ИТЕМА ***
		
		/**
		* Функция проверки одного элемента инсталляции
		* @param mixed $item - содержимое одного элемента очереди
		* @return string - информация
		*/

		private function check_update($item) {
			if (!$this->check_all_fields_in_file_element($item['file_element']))
				throw new SystemUpdateException($this->te_object_name.": ".metadata::$lang['lang_system_update_installation_file_invalid'].': '.print_r($item['file_element'], 1), $this, $item['record']);
			
			// если файл предназначен для копирования 
			if ($item['file_element']['attributes']['to']) {
				$source = params::$params[$item['file_element']['attributes']['place']]['value'].$item['file_element']['attributes']['to'].$item['file_element']['attributes']['name'];
				$update = $item['dirname']."update/".$item['file_element']['attributes']['from'].'/'.$item['file_element']['attributes']['name'];
				
 				if (!$item['file_element']['attributes']['original_checksum'] && 
							!$item['file_element']['attributes']['current_checksum'] &&
								file_exists($source)) 
					throw new SystemUpdateException($this->te_object_name.": ".$this->te_object_name.": ".metadata::$lang['lang_system_update_file_exists'].': '.$source, $this, $item['record']);
					
				if ($item['file_element']['attributes']['original_checksum'] 
							&& $item['file_element']['attributes']['current_checksum']) {
							$this->check_update_files ($source, $update, $item);
				} 
				elseif (!$item['file_element']['attributes']['original_checksum'] 
						&& !$item['file_element']['attributes']['current_checksum']) {
					try {
						$this->check_can_copy($update, $source);
					}
					catch (Exception $e) {
						throw new SystemUpdateException($this->te_object_name.": ".metadata::$lang['lang_system_update_can_not_copy_file'].': '.$source.' - '.$e->getMessage(), $this, $item['record']);
					}
				}
			}

			return metadata::$lang['lang_system_update_file_checked'].': '.$item['file_element']['attributes']['from'].$item['file_element']['attributes']['name'];
		}
		
		/**
		* Функция создания резервной копии файла
		* @param mixed $item - содержимое одного элемента очереди
		* @return string - информация
		*/
		private function create_backup($item) {
			if ($item['file_element']['attributes']['original_checksum'] &&	$item['file_element']['attributes']['current_checksum']) {
				$source = params::$params[$item['file_element']['attributes']['place']]['value'].$item['file_element']['attributes']['to'].$item['file_element']['attributes']['name'];
				$backup_dir = $item['dirname'].'backup/'.$item['file_element']['attributes']['from'];
				if (!is_dir($backup_dir) && !mkdir($backup_dir, 0777, true)) {
					throw new SystemUpdateException($this->te_object_name.": ".metadata::$lang['lang_system_update_no_rights_to_create_backup_dir'].': '.$backup_dir, $this, $item['record']);
				}
			
				if (@copy($source, $backup_dir.$item['file_element']['attributes']['name'])) {
						return metadata::$lang['lang_system_update_file_backuped'].': '.$source;
				} 
				throw new SystemUpdateException($this->te_object_name.": ".metadata::$lang['lang_system_update_can_not_find_file_for_backup'].': '.$source, $this, $item['record']);
			}
		}
		
		/**
		* Функция процессинга файла из обновления
		* @param mixed $item - содержимое одного элемента очереди
		* @return string - результат выполнения ф-ии
		*/

		private function process_update($item) {
			if ($item['file_element']['attributes']['install_exec'])
				$this->exec_file_function($item['dirname'].'update/'.$item['file_element']['attributes']['from'].$item['file_element']['attributes']['name'], $item['file_element']['attributes']['install_exec']);
			
			if ($item['file_element']['attributes']['to'])
				try {
					$this->copy_update($item);
				}
				catch (Exception $e) {
					throw new SystemUpdateException($this->te_object_name.": ".metadata::$lang['lang_system_update_can_not_copy_update'].': '.$source.' - '.$e->getMessage(), $this, $item['record']);
				}
			
			return metadata::$lang['lang_system_update_file_installed'].': '.params::$params[$item['file_element']['attributes']['place']]['value'].$item['file_element']['attributes']['to'].$item['file_element']['attributes']['name'];
		}
		
		/**
		* Запуск ф-иии из текущего итема
		* @param mixed $item - содержимое одного элемента очереди
		* @param string $func - название ф-ии, которую необходимо из него вызвать
		*/
		private function exec_file_function($file, $func) {
			include_once($file);
			$func();
		}
		
		/**
		* Функция копирования файла обновления
		* @param mixed $item - содержимое одного элемента очереди
		* @return boolean
		*/
		private function copy_update($item) {
			$source = params::$params[$item['file_element']['attributes']['place']]['value'].$item['file_element']['attributes']['to'].$item['file_element']['attributes']['name'];
			$update = $item['dirname'].'update/'.$item['file_element']['attributes']['from'].$item['file_element']['attributes']['name'];
			
			return $this->copy_file($update, $source);
		}

		// -----------------------------------------------------------------------------
		// РАСПРЕДЕЛЕННАЯ ОПЕРАЦИЯ ИНСТАЛЛЯЦИИ ДЕИНСТАЛЛЯЦИИ
		// -----------------------------------------------------------------------------
		
		/**
		* Функция для получения общей информации о распределенной (distributed) операции деинсталляции
		* @param array $status - передаваемые данные - массив. 
		* @return array
		*/ 

		public function uninstall_info (&$status) {
			$record=$this->full_object->get_change_record($status['pk'], true);
			
			if ($record['SU_STATE']=='registered')
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_is_not_installed'].": \"".$this->full_object->get_record_title($status['pk'])."\" (".$this->primary_key->pk_to_string($status['pk']).")");
				
			if ($record['SU_DATE']!=$this->get_last_installed_update_date())
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_is_not_last_update'].": \"".$this->full_object->get_record_title($status['pk'])."\" (".$this->primary_key->pk_to_string($status['pk']).")"); 

			$dirname=$this->get_dirname_by_date($record['SU_DATE']);
			
			$install_info = $this->get_install_info($dirname);
			$install_info_arr = $install_info[0]['children'];
			$install_info_arr=$this->sort_xml_array_by_attribute($install_info_arr, 'uninstallOrder');

			// формируем действия, которые необходимо сделать:
			$total=$this->set_action_queue($record, $install_info_arr, array(array('action'=>'check_backup'), array('action'=>'uninstall_update', 'revert_action'=>'process_update')));

			// начинаем деинсталляцию, присваиваем статус "uninstall_failed", чтобы он остался если какие-то проблемы возникли в процессе инсталляции
			$fields=array('SU_STATE'=>'uninstall_failed');
			db::update_record($this->obj, $fields, "change", array('SYSTEM_UPDATE_ID'=>$record['SYSTEM_UPDATE_ID']));
			
			$status['log_record_id'] = $this->log_register_update('update_uninstall', $status['pk']['SYSTEM_UPDATE_ID']);
			return array("title"=>metadata::$lang["lang_system_update_uninstall"], "back_url"=>$this->url->get_url(), 'total'=>$total, "complete_message"=>metadata::$lang["lang_operation_completed_succesfully"], "for_once"=>5, "exception_fatal"=>1); 
		}
		
		/**
		* Получение списка операций для распределенной операции деинсталляции
		*/
		public function uninstall_list (&$status, $from, $for_once) {
			$actions=db::sql_select('SELECT SYSTEM_UPDATE_ACTION_QUEUE_ID, ACTION AS PENDING_ACTION, FILE_ELEMENT FROM SYSTEM_UPDATE_ACTION_QUEUE WHERE SYSTEM_UPDATE_ID=:system_update_id AND STATUS=:status ORDER BY SYSTEM_UPDATE_ACTION_QUEUE_ID LIMIT '.$for_once, array('system_update_id'=>$status['pk']['SYSTEM_UPDATE_ID'], 'status'=>'pending'));
			return $this->distributed_list($status, $actions);
		}
		
		/**
		* Обработка одной операции для распределенной операции деинсталляции
		*/
		public function uninstall_item ($item, &$status) {
			return $this->distributed_item($item, $status);
		}
		
		/**
		* Обработка счастливого окончания распределенной операции деинсталляции
		*/
		public function uninstall_commit (&$status) {
			$this->distributed_commit($status, 'registered');
			$this->set_log_operation($status['log_record_id'], 'update_uninstalled');
		}
		
		// *** ДЕЙСТВИЯ ДЕИНСТАЛЛЯЦИИ ДЛЯ КАЖДОГО ИТЕМА ***
		
		/**
		* Проверка на наличие и корректность резервной копии
		* @param mixed $item - содержимое одного элемента очереди
		*/
		private function check_backup ($item) {
			if (!$this->check_all_fields_in_file_element($item['file_element']))
				throw new SystemUpdateException($this->te_object_name.": ".metadata::$lang['lang_system_update_installation_file_invalid'], $this, $item['record']);

			// если файл предназначен для копирования 
			if ($item['file_element']['attributes']['to']) {
				$update = params::$params[$item['file_element']['attributes']['place']]['value'].$item['file_element']['attributes']['to'].$item['file_element']['attributes']['name'];
				$source = $item['dirname']."backup/".$item['file_element']['attributes']['from'].$item['file_element']['attributes']['name'];
				if ($item['file_element']['attributes']['original_checksum'] && $item['file_element']['attributes']['current_checksum']) {
					$this->check_update_files($source, $update, $item);
				} 
			}
			
			return metadata::$lang['lang_system_update_file_checked'].': '.$item['file_element']['attributes']['from'].$item['file_element']['attributes']['name'];
		}
		
		/**
		* Функция деинсталляции файла из обновления
		* @param mixed $item - содержимое одного элемента очереди
		* @return string - результат выполнения ф-ии
		*/
		private function uninstall_update ($item) {
			if ($item['file_element']['attributes']['uninstall_exec'])
				$this->exec_file_function($item['dirname'].'update/'.$item['file_element']['attributes']['from'].$item['file_element']['attributes']['name'], $item['file_element']['attributes']['uninstall_exec']);
			
			if ($item['file_element']['attributes']['to'])
				$this->revert_update($item);
					
	 		return metadata::$lang['lang_system_update_file_uninstalled'].': '.params::$params[$item['file_element']['attributes']['place']]['value'].$item['file_element']['attributes']['to'].$item['file_element']['attributes']['name'];
		}
		
		/**
		* Функция возвращения к состоянию до применения обновления
		* @param mixed $item - содержимое одного элемента очереди
		* @return boolean
		*/
		private function revert_update($item) {
				$source = params::$params[$item['file_element']['attributes']['place']]['value'].$item['file_element']['attributes']['to'].$item['file_element']['attributes']['name'];
				$update = $item['dirname'].'backup/'.$item['file_element']['attributes']['from'].$item['file_element']['attributes']['name'];
			
				// если файл был заменен, то возвращаем старую копию из бекапа
				if ($item['file_element']['attributes']['original_checksum'] &&	$item['file_element']['attributes']['current_checksum']) 
					try {
						$this->copy_file($update, $source);
					}
					catch (Exception $e) {
						throw new SystemUpdateException($this->te_object_name.": ".metadata::$lang['lang_system_update_can_not_copy_backup'].': '.$source.' - '.$e->getMessage(), $this, $item['record']);
					}
				// если файл был новым, то удаляем оный
				if (!$item['file_element']['attributes']['original_checksum'] &&	!$item['file_element']['attributes']['current_checksum'])
					try {
						$this->remove_file($source);
					}
					catch (Exception $e) {
						throw new SystemUpdateException(metadata::$lang['lang_system_update_can_not_delete_file'].': '.$source.' - '.$e->getMessage(), $this, $item['record']);
					}
				
				return true;
		}

		// -----------------------------------------------------------------------------
		// РАСПРЕДЕЛЕННАЯ ОПЕРАЦИЯ ИНСТАЛЛЯЦИИ ДОУСТАНОВКИ НЕДОУСТАНОВЛЕННОГО ОБНОВЛЕНИЯ
		// -----------------------------------------------------------------------------
		
		/**
		* Функция для получения общей информации о распределенной (distributed) операции доустановки неудоустановленного обновления
		* @param array $status - передаваемые данные - массив. 
		* @return array
		*/
		
		public function continue_install_failed_info(&$status) {
			$record=$this->full_object->get_change_record($status['pk'], true);
			
			$dirname=$this->get_dirname_by_date($record['SU_DATE']);
			
			$install_info = $this->get_install_info($dirname);
			$install_info_arr = $install_info[0]['children'];
			$install_info_arr=$this->sort_xml_array_by_attribute($install_info_arr, 'uninstallOrder');


			// дополняем действия, которые необходимо сделать:
			$this->set_action_queue_fail($record, array('process_update'=>array('pending'=>'check_update', 'done'=>'check_backup')), 'pending');
			
			$total=$this->get_action_queue_count($status['pk'], 'pending');
			$status['log_record_id'] = $this->log_register_update('update_install', $status['pk']['SYSTEM_UPDATE_ID'], 'update_continue_install_failed');

			return array("title"=>metadata::$lang["lang_system_update_continue_install_failed"], "back_url"=>$this->url->get_url(), 'total'=>$total, "complete_message"=>metadata::$lang["lang_operation_completed_succesfully"], "for_once"=>5, "exception_fatal"=>1);
		}
		
		/**
		* Получение списка операций для распределенной операции доустановки недоустановленного обновления
		*/
		public function continue_install_failed_list(&$status, $from, $for_once) {
			return $this->install_list($status, $from, $for_once);
		}
		
		/**
		* Обработка одной операции для распределенной операции доустановки недоустановленного обновления
		*/
		public function continue_install_failed_item($item, &$status) {
			return $this->install_item($item, $status);
		}
		
		/**
		* Обработка счастливого окончания распределенной операции доустановки недоустановленного обновления
		*/
		public function continue_install_failed_commit (&$status) {
			$this->install_commit($status);
		}

		// -----------------------------------------------------------------------------
		// РАСПРЕДЕЛЕННАЯ ОПЕРАЦИЯ ИНСТАЛЛЯЦИИ ДЕИНСТАЛЛЯЦИИ НЕДОУСТАНОВЛЕННОГО ОБНОВЛЕНИЯ
		// -----------------------------------------------------------------------------
		
		/**
		* Функция для получения общей информации о распределенной (distributed) операции деинсталляции неудоустановленного обновления
		* @param array $status - передаваемые данные - массив. 
		* @return array
		*/
		public function uninstall_install_failed_info(&$status) {
			$record=$this->full_object->get_change_record($status['pk'], true);
			
			$dirname=$this->get_dirname_by_date($record['SU_DATE']);
			
			$install_info = $this->get_install_info($dirname);
			$install_info_arr = $install_info[0]['children'];
			$install_info_arr=$this->sort_xml_array_by_attribute($install_info_arr, 'uninstallOrder');

			// дополняем действия, которые необходимо сделать:
			$this->set_action_queue_fail($record, array('process_update'=>array('pending'=>'check_update', 'done'=>'check_backup')), 'done');
			
			$total=$this->get_action_queue_count($status['pk'], 'done');
			$status['log_record_id'] = $this->log_register_update('update_uninstall', $status['pk']['SYSTEM_UPDATE_ID'], 'update_uninstall_install_failed');
			return array("title"=>metadata::$lang["lang_system_update_uninstall"], "back_url"=>$this->url->get_url(), 'total'=>$total, "complete_message"=>metadata::$lang["lang_operation_completed_succesfully"], "for_once"=>5, "exception_fatal"=>1);
		}
		
		/**
		* Получение списка операций для распределенной операции деинсталляции неудоустановленного обновления
		*/
		public function uninstall_install_failed_list(&$status, $from, $for_once) {
			$actions=db::sql_select('SELECT SYSTEM_UPDATE_ACTION_QUEUE_ID, REVERT_ACTION AS PENDING_ACTION, FILE_ELEMENT FROM SYSTEM_UPDATE_ACTION_QUEUE WHERE SYSTEM_UPDATE_ID=:system_update_id AND STATUS=:status ORDER BY SYSTEM_UPDATE_ACTION_QUEUE_ID DESC LIMIT '.$for_once, array('system_update_id'=>$status['pk']['SYSTEM_UPDATE_ID'], 'status'=>'done'));
			return $this->distributed_list($status, $actions);
		}
		
		/**
		* Обработка одной операции для распределенной операции деинсталляции неудоустановленного обновления
		*/
		public function uninstall_install_failed_item($item, &$status) {
			return $this->distributed_item($item, $status, 'pending');
		}
		
		/**
		* Обработка счастливого окончания распределенной операции деинсталляции неудоустановленного обновления
		*/	
		public function uninstall_install_failed_commit(&$status) {
			$this->uninstall_commit($status);
		}
		
		// -----------------------------------------------------------------------------
		// РАСПРЕДЕЛЕННАЯ ОПЕРАЦИЯ ДЕИНСТАЛЛЯЦИИ НЕДОДЕИНСТАЛЛИРОВАННОГО ОБНОВЛЕНИЯ
		// -----------------------------------------------------------------------------
		
		/**
		* Функция для получения общей информации о распределенной (distributed) операции продолжения деинсталляции недодеинсталированного обновления
		* @param array $status - передаваемые данные - массив. 
		* @return array
		*/
		public function continue_uninstall_failed_info(&$status) {
			$record=$this->full_object->get_change_record($status['pk'], true);
			
			$dirname=$this->get_dirname_by_date($record['SU_DATE']);
			
			$install_info = $this->get_install_info($dirname);
			$install_info_arr = $install_info[0]['children'];
			$install_info_arr=$this->sort_xml_array_by_attribute($install_info_arr, 'uninstallOrder');

			// дополняем действия, которые необходимо сделать:
			$this->set_action_queue_fail($record, array('uninstall_update'=>array('pending'=>'check_backup', 'done'=>'check_update')), 'pending');
			
			$total = $this->get_action_queue_count($status['pk'], 'pending');
			$status['log_record_id'] = $this->log_register_update('update_uninstall', $status['pk']['SYSTEM_UPDATE_ID'], 'update_continue_uninstall_failed');
			return array("title"=>metadata::$lang["lang_system_update_continue_uninstall_failed"], "back_url"=>$this->url->get_url(), 'total'=>$total, "complete_message"=>metadata::$lang["lang_operation_completed_succesfully"], "for_once"=>5, "exception_fatal"=>1);
		}
		
		/**
		* Получение списка операций для распределенной операции операции продолжения деинсталляции недодеинсталированного обновления
		*/
		public function continue_uninstall_failed_list(&$status, $from, $for_once) {
			return $this->uninstall_list($status, $from, $for_once);
		}
		
		/**
		* Обработка одной операции для распределенной операции продолжения деинсталляции недодеинсталированного обновления
		*/
		public function continue_uninstall_failed_item($item, &$status) {
			return $this->uninstall_item($item, $status);
		}
		
		/**
		* Обработка счастливого окончания распределенной операции продолжения деинсталляции недодеинсталированного обновления
		*/
		public function continue_uninstall_failed_commit (&$status) {
			$this->uninstall_commit($status);
		}
		
		// -----------------------------------------------------------------------------
		// РАСПРЕДЕЛЕННАЯ ОПЕРАЦИЯ ОТМЕНЫ ДЕИНСТАЛЛЯЦИИ НЕДОДЕИНСТАЛЛИРОВАННОГО ОБНОВЛЕНИЯ
		// -----------------------------------------------------------------------------
		
		/**
		* Функция для получения общей информации о распределенной (distributed) операции отмены деинсталляции недодеинсталированного обновления
		* @param array $status - передаваемые данные - массив. 
		* @return array
		*/
		
		public function uninstall_uninstall_failed_info(&$status) {
			$record=$this->full_object->get_change_record($status['pk'], true);
			
			$dirname=$this->get_dirname_by_date($record['SU_DATE']);
			
			$install_info = $this->get_install_info($dirname);
			$install_info_arr = $install_info[0]['children'];
			$install_info_arr=$this->sort_xml_array_by_attribute($install_info_arr, 'uninstallOrder');

			// дополняем действия, которые необходимо сделать:
			$this->set_action_queue_fail($record, array('uninstall_update'=>array('pending'=>'check_backup', 'done'=>'check_update')), 'done');
			
			$total=$this->get_action_queue_count($status['pk'], 'done');
			$status['log_record_id'] = $this->log_register_update('update_uninstall', $status['pk']['SYSTEM_UPDATE_ID'], 'update_uninstall_uninstall_failed');
			return array("title"=>metadata::$lang["lang_system_update_uninstall"], "back_url"=>$this->url->get_url(), 'total'=>$total, "complete_message"=>metadata::$lang["lang_operation_completed_succesfully"], "for_once"=>5, "exception_fatal"=>1);
		}
		
		/**
		* Получение списка операций для распределенной операции отмены деинсталляции недодеинсталированного обновления
		*/
		public function uninstall_uninstall_failed_list(&$status, $from, $for_once) {
			return $this->uninstall_install_failed_list($status, $from, $for_once);
		}
		
		/**
		* Обработка одной операции для распределенной	операции отмены деинсталляции недодеинсталированного обновления
		*/
		public function uninstall_uninstall_failed_item($item, &$status) {
			return $this->distributed_item($item, $status, 'pending');
		}
		
		/**
		* Обработка счастливого окончания распределенной операции отмены деинсталляции недодеинсталированного обновления
		*/
		public function uninstall_uninstall_failed_commit(&$status) {
			$this->install_commit($status);
		}

		//--------------------------------------------------------------------------
		// Общие функции для распределенных операций
		//--------------------------------------------------------------------------
		
		/**
		* ф-ия выдачи списка для распределенных механизмов
		* @param int $for_once - кол-во итемов, которое нужно выдать за 1 раз
		*/
		
		private function distributed_list (&$status, $pending_actions) {
			$res=array();
			$record=$this->full_object->get_change_record($status['pk'], true);
			$dirname=$this->get_dirname_by_date($record['SU_DATE']);
			
			if (sizeof($pending_actions))
				foreach ($pending_actions as $action) 
					$res[]=array(
						'system_update_action_queue_id'=>$action['SYSTEM_UPDATE_ACTION_QUEUE_ID'],
						'dirname'=>$dirname, 
						'record'=>$record, 
						'action'=>$action['PENDING_ACTION'], 
						'file_element'=>unserialize($action['FILE_ELEMENT'])
					);
			
			return $res;	
		}
		
		/**
		* Обработка одной операции для распределенных механизмов
		*/
		
		private function distributed_item ($item, &$status, $result_status='done') {
			if ($item['action']) {
				$msg=$this->$item['action']($item);
				$ret=array('message'=>$msg);
				db::update_record('SYSTEM_UPDATE_ACTION_QUEUE', array('STATUS'=>$result_status), 'change', array('SYSTEM_UPDATE_ACTION_QUEUE_ID'=>$item['system_update_action_queue_id']));
				log::add_data_to_extended_info($status['log_record_id'], $msg."; \n");
				return $ret;
			}

			return array('message'=>'--');
		}
		
		/**
		* Обработка счастливого окончания распределенных механизмов
		*/
		
		private function distributed_commit (&$status, $result_state) {
			// запускаем пребилдер
			$this->run_prebuilder();
			
			// удаляем все действия, поскольку они завершились успешно
			db::sql_query('DELETE FROM SYSTEM_UPDATE_ACTION_QUEUE WHERE SYSTEM_UPDATE_ID=:system_update_id', array('system_update_id'=>$status['pk']['SYSTEM_UPDATE_ID']));
			
			// присваиваем необходимое состояние инсталляшке
			$fields=array('SU_STATE'=>$result_state);
			db::update_record($this->obj, $fields, "change", $status['pk']);
		}
		
		//-----------------------------------------------------------------------------
		// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
		//-----------------------------------------------------------------------------
		
		/**
		* Получает содержимое файла инсталяции
		*
		* @return mixed содержимое xml-файла в dom-массиве
		*/
		
		private function get_install_info ($dirname) {
			if(!file_exists($dirname.'installator.xml')) 
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_no_install_file'].$dirname.'installator.xml');

			if(!is_dir($dirname.'update/'))
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_no_files_included'].$dirname.'update/');
	
			// Проверяется файл инсталляции
			$xml = & new ExpatXMLParser();
			
			$installInfoArr = $xml->Parse($dirname.'installator.xml', _LOAD_FROM_FILE);
			$xml -> Free();	

			if(!is_array($installInfoArr)) 
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_installation_file_xml_error'].$dirname.'installator.xml');
	
			if(!$installInfoArr[0]['attributes']['date'])
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_has_no_date'].' "'.$dirname.'installator.xml"');
			
			if(!$installInfoArr[0]['attributes']['name'])
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_has_no_name'].' "'.$dirname.'installator.xml"');
			
			// кодировка
			if(!$installInfoArr[0]['attributes']['enctype'])
				$installInfoArr[0]['attributes']['enctype'] = self::default_enctype;
				
			// проверяем соответствие
			if (params::$params['encoding']['value'] != $installInfoArr[0]['attributes']['enctype'])
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_has_bad_enctype'].' '.$installInfoArr[0]['attributes']['enctype'].' ("'.$installInfoArr[0]['attributes']['name'].'")');
						
			// проверка дат
			$installInfoArr[0]['attributes']['_date']=$installInfoArr[0]['attributes']['date'];
			if(!$installInfoArr[0]['attributes']['date']=$this->get_packed_date($installInfoArr[0]['attributes']['date']))
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_has_bad_date'].' '.$installInfoArr[0]['attributes']['date'].' ("'.$installInfoArr[0]['attributes']['name'].'")');

			$installInfoArr[0]['attributes']['_previous_update']=$installInfoArr[0]['attributes']['previous_update'];
			if(!$installInfoArr[0]['attributes']['previous_update']=$this->get_packed_date($installInfoArr[0]['attributes']['previous_update']))
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_has_no_previous_update'].' ("'.$installInfoArr[0]['attributes']['name'].'")');
			
			if ($installInfoArr[0]['attributes']['previous_update']<params::$params['system_revision_date']['value'])
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_previous_update_is_earlier_than_system_revision'].' ("'.$installInfoArr[0]['attributes']['name'].'")');

			// если система без CMS нужно удалить все элементы, относящиеся к CMS
			if (!params::$params['install_cms'] && sizeof($installInfoArr[0]['children'])) 
				$installInfoArr[0]['children'] = array_values(array_filter($installInfoArr[0]['children'], array($this, '_array_filter_cms_array')));
			
			return $installInfoArr;
		}
		
		/**
		* Фильтрация массива для удаления CMS элементов
		* Вызывается в array_filter
		*/
		
		public function _array_filter_cms_array($var) {
			return !$var['attributes']['is_cms'];
		}
		
		/**
		* Проверка файлового элемента файла инсталляции на корректность
		*/

		private function check_all_fields_in_file_element($file_element) {
			if (!$file_element['attributes']['name'] ||
							 !$file_element['attributes']['from']
				 )
						return false; 
			
			// файл можно скопировать, а можно просто запустить какую-нить ф-ию из него
			if (
			 		 (!isset($file_element['attributes']['to']) ||
			 				!$file_element['attributes']['place'] ||
			 					 !params::$params[$file_element['attributes']['place']]['value'])
			 			&&
			 		 (!isset($file_element['attributes']['install_exec']) &&
			 				!isset($file_element['attributes']['uninstall_exec'])
			 		 )
				 )
						return false;
			
			if (
						(
							!$file_element['attributes']['original_checksum'] 
								&& 
									$file_element['attributes']['current_checksum']
						) 
						||
						(
							$file_element['attributes']['original_checksum'] 
								&& 
									!$file_element['attributes']['current_checksum']
						)
				 )
						return false;
			 
			return true;
		}

		/**
		* Строит список операций и заносит в БД и лог
		* @param array $record - запись из таблицы системы обновления
		* @param array $install_info_arr - массив из XML-файла инсталляции
		* @param array $actions - возможные действия
		* @param function $check_callback_function - функция обратного вызова, если задана, то должна быть методом данного класса 
		*								 передаются 2 аргумента $file_element и $action, и необходимо чтобы ф-ия возвратила boolean - помещать ли итем в очередь
		*								 или нет 
		* @return array
		*/ 
		private function set_action_queue($record, $install_info_arr, $actions, $callback_check_function=null) {
			$is_previous=$this->get_action_queue_count($record);
			if ($is_previous) 
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_already_run'].': '.$source);
			
			$count=0;
			
			// записывает все действия, которые необходимо соверщить, в таблтцу SYSTEM_UPDATE_ACTION_QUEUE
			foreach ($actions as $act)
				foreach ($install_info_arr as $install_info_element)
					if ($install_info_element['tag']=='file') {
						// проверяем, нужно ли обрабатывать итем через ф-ию обратного вызова
						if ($callback_check_function && !$this->$callback_check_function($install_info_element, $act['action']))
							continue;
						
						$insert_record=array (
							'SYSTEM_UPDATE_ID' => $record['SYSTEM_UPDATE_ID'],
							'ACTION' => $act['action'],
							'REVERT_ACTION' => $act['revert_action'],
							'FILE_ELEMENT' => serialize($install_info_element),
							'STATUS' => 'pending',
						);
						
						db::insert_record('SYSTEM_UPDATE_ACTION_QUEUE', $insert_record);
						++$count;
					}
			return $count;
		}
		
		/**
		* Дополнение действий которые мы хотим сделать проверкой файлов, вызывается  в случае если действие оказалось незавершенным
		* @param array $record - запись из таблицы системы обновления
		* @param array $actions - проверочные действия
		* @param string $status - статус, с которым необходимо добавлять данные в очередь
		*/
		
		private function set_action_queue_fail ($record, $actions, $status) {
			$queue = db::sql_select('SELECT * FROM SYSTEM_UPDATE_ACTION_QUEUE WHERE SYSTEM_UPDATE_ID=:system_update_id ORDER BY SYSTEM_UPDATE_ACTION_QUEUE_ID', array('system_update_id'=>$record['SYSTEM_UPDATE_ID']));
			
			// заполняем дополнительную очередь с проверками, которые необходимо произвести
			$add_queue = array();
			// массив проверочных действий, которые впоследствии удалим из реальной очереди
			$check_actions = array();

			foreach ($queue as $que) {
				// для каждого элемента в очереди, соответствующему нужному действию, формируем проверочное действие
				if (in_array($que['ACTION'], array_keys($actions)) && in_array($que['STATUS'], array_keys($actions[$que['ACTION']]))) {
					// формируем стандартную запись для очереди
					$add_queue[] = array (
						'SYSTEM_UPDATE_ID' => $record['SYSTEM_UPDATE_ID'],
						($status=='pending')?'ACTION':'REVERT_ACTION' => $actions[$que['ACTION']][$que['STATUS']],
						'FILE_ELEMENT' => $que['FILE_ELEMENT'],
						'STATUS' => $status
					);
					
					// запоминаем проверочное действие
					if (!in_array($actions[$que['STATUS']][$que['STATUS']], $check_actions))
						$check_actions[] = $actions[$que['STATUS']][$que['STATUS']];
				}
			}
			
			$pending_queue = db::sql_select('SELECT * FROM SYSTEM_UPDATE_ACTION_QUEUE WHERE SYSTEM_UPDATE_ID=:system_update_id AND STATUS=:status  ORDER BY SYSTEM_UPDATE_ACTION_QUEUE_ID', array('system_update_id'=>$record['SYSTEM_UPDATE_ID'], 'status'=>$status));
			
			if ($status == 'pending') {
				// удаляем в начале все данные, связанные с проверкой 
				while (sizeof($pending_queue) && in_array($pending_queue[0]['ACTION'], $check_actions)) 
					array_unshift($pending_queue);
			
				// соединяем массивы и вставляем в очередь в БД
				$pending_queue = array_merge($add_queue, $pending_queue);
			}
			else {
				// удаляем в конце все данные, связанные с проверкой 
				while (sizeof($pending_queue) && in_array($pending_queue[sizeof($pending_queue)-1]['ACTION'], $check_actions)) 
					array_pop($pending_queue);
			
				// соединяем массивы и вставляем в очередь в БД
				$pending_queue = array_merge($pending_queue, $add_queue);
			}
			
			db::sql_query('DELETE FROM SYSTEM_UPDATE_ACTION_QUEUE WHERE SYSTEM_UPDATE_ID=:system_update_id AND STATUS=:status', array('system_update_id'=>$record['SYSTEM_UPDATE_ID'], 'status'=>$status));
			foreach ($pending_queue as $q) {
				unset($q['SYSTEM_UPDATE_ACTION_QUEUE_ID']);
				db::insert_record('SYSTEM_UPDATE_ACTION_QUEUE', $q);
			}
		}
		
		/**
		* Возвращает кол-во действий, зарегистрированных для данного обновления
		* @param array $pk - первичный ключ записи
		* @param string $status - статус действий, которые нужно возвратить
		* @return int		
		*/
		private function get_action_queue_count($pk, $status='') {
			$add_sql='';
			$add_params=array();
			
			if ($status) {
				$add_sql.=' AND status=:status';
				$add_params['status'] = $status;
			}
			$count=db::sql_select('SELECT COUNT(*) AS CNT FROM SYSTEM_UPDATE_ACTION_QUEUE WHERE SYSTEM_UPDATE_ID=:system_update_id'.$add_sql, array('system_update_id'=>$pk['SYSTEM_UPDATE_ID'])+$add_params);
			return $count[0]['CNT']; 
		}
		
		
		/**
		* Функция выдает дату последнего обновления, которое не просто зарегистрировано в системе
		* @return string
		*/ 
		
		private function get_last_update_date() {
			if (!$this->last_update_date) {
				$last_update_date=db::sql_select('SELECT MAX(SU_DATE) AS LAST_UPDATE_DATE FROM SYSTEM_UPDATE WHERE SU_STATE<>:su_registered', array('su_registered'=>'registered'));
				$this->last_update_date=$last_update_date[0]['LAST_UPDATE_DATE']?$last_update_date[0]['LAST_UPDATE_DATE']:$this->get_packed_date(params::$params['system_revision_date']['value']);
			}
			return $this->last_update_date;
		}
		
		/**
		* Функция выдает дату последнего обновления, проинсталлированного в системе
		* @return string
		*/ 
		
		private function get_last_installed_update_date() {
			if (!$this->last_installed_update_date) {
				$last_installed_update_date=db::sql_select('SELECT MAX(SU_DATE) AS LAST_INSTALLED_UPDATE_DATE FROM SYSTEM_UPDATE WHERE SU_STATE=:su_installed', array('su_installed'=>'installed'));
				$this->last_installed_update_date=$last_installed_update_date[0]['LAST_INSTALLED_UPDATE_DATE']?$last_installed_update_date[0]['LAST_INSTALLED_UPDATE_DATE']:$this->get_packed_date(params::$params['system_revision_date']['value']);
			}
			return $this->last_installed_update_date;
		}

		/**
		* Получает путь к каталогу обновления по дате
		* @param string $date - дата в формате lib::pack_date
		*/
		private function get_dirname_by_date ($date) {
			list($day, $month, $year) = split('\.', lib::unpack_date($date, 'short'));
			return $this->updates_path."{$year}-{$month}-{$day}/";
		}
		
		
		/**
		* Возвращает запакованную дату в нашем стандарном формате
		* @param string $date Дата в формате strtotime
		* @return string Дата в нашем стандартном запакованном формате, который возвращается ф-ией lib::packdate
		* Если дату получить не удалось - возвращаем false
		*/
		private function get_packed_date ($date) {
			if ($real_date=strtotime($date)) {
				$str_date = date('d.m.Y', $real_date);
				if ($db_date=lib::pack_date($str_date, 'short')) {
					if ($real_date>0 && $real_date<=time())
						return $db_date;
				}
			}
			return false;
		}


		/**
		* Функция сортировки массива, полученного из xml-файла по значению атрибута
		* @param array $xml_arr - массив
		* @param string $attr_name - название атрибута
		*/
		private function sort_xml_array_by_attribute($xml_arr, $attr_name) {
			$this->sort_xml_array_by_attribute_name=$attr_name;
			usort($xml_arr, array($this, sort_xml_array_by_attribute_callback));
			return $xml_arr;
		}
		
		/**
		* Функция обратного вызова для ф-ии usort для sort_xml_array_by_attribute
		*/
		private function sort_xml_array_by_attribute_callback($a, $b) {
			return ($a['attributes'][$this->sort_xml_array_by_attribute_name]<$b['attributes'][$this->sort_xml_array_by_attribute_name])?-1:1;
		}
		

		/**
		* Проверка файла на наличие, возможность перезаписи
		* @param string $src_file_path - путь к файлу
		* @param int $sum - необходимая сумма
		*/

		private function check_file ($src_file_path, $sum) {
			if (!file_exists($src_file_path))				
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_system_update_file_not_exists'].': '.$src_file_path);
			
			if (!is_readable($src_file_path)) 
				throw new Exception($this->te_object_name.": ".metadata::$lang['lang_fm_not_readable'].': '.$src_file_path);
			
			// проверка на возможность перезаписи - вызовется exception если нет
			$this->is_writeable($src_file_path);
		}
		/**
		* Функция проверки контрольной суммы файла
		* @param string $src_file_path - путь к файлу
		* @param int $needed_checksum - контрольная сумма для проверки
		* @return boolean
		*/
		public function compare_checksum ($src_file_path, $needed_checksum) {
			if (!file_exists($src_file_path) || !is_readable($src_file_path)) 
				return false;
			$new_checksum = $this->get_checksum($src_file_path);
			return $needed_checksum==$new_checksum;
		}
		
		/**
		* Вычисление контрольной суммы файла
		* @param string $src_file_path - путь к файлу
		* @return int контрольная сумма
		*/
		public function get_checksum ($src_file_path) {
			$patterns = array ("/[ \t]+/s", "/\r/s"); 
			$replace = array (' ', ''); 
			
			return md5(preg_replace($patterns, $replace, file_get_contents($src_file_path)));
		}
		/**
		* Проверка файлов обновления
		*/
		
		private function check_update_files ($source, $update, $item) {
				try {
					$this->check_file($source, $item['file_element']['attributes']['original_checksum']);
					$this->check_file($update, $item['file_element']['attributes']['current_checksum']);
				}
				catch (Exception $e) {
					throw new SystemUpdateException($e->getMessage(), $this, $item['record']);
				}

				// возможен вариант, когда вручную был заменен какой-либо файл на файл более новой версии.
				if (!$this->compare_checksum($source, $item['file_element']['attributes']['original_checksum']) 
							&& 
								!$this->compare_checksum($source, $item['file_element']['attributes']['current_checksum'])
				) 
					throw new SystemUpdateException($this->te_object_name.": ".metadata::$lang['lang_system_update_checksum_invalid'].': '.$source, $this, $item['record']);
					
				if (!$this->compare_checksum($update, $item['file_element']['attributes']['current_checksum']))
					throw new SystemUpdateException($this->te_object_name.": ".metadata::$lang['lang_system_update_checksum_invalid'].': '.$source, $this, $item['record']);
		}

		/**
		* Меняет инфу в журнале
		* Заменяет тип операции в журнале для записи с log_record_id
		* @param int $log_record_id	id записи в журнале для изменения
		* @param string $operation_name	Название операции, на которую меняем
		*/
		
		private function set_log_operation ($log_record_id, $operation_name) {
			log::change_data($log_record_id, array('LOG_OPERATION_ID'=>log::$log_operations[log::$log_types['log_system_updates']['LOG_TYPE_ID']][$operation_name]['LOG_OPERATION_ID']));
		}

		/**
		* Регистрация информации в журнале обновления
		* @param string $type - тип сообщения
		* @param mixed $update_data - массив данных обновления. Или ID обновления
		* @param mixed $additional_info - дополнительная информация
		*/
		
		public function log_register_update ($type, $update_data, $start_mode='', $additional_info='') {
			if (!log::is_enabled('log_system_updates')) return;
			
			if (!is_array($update_data) && $update_data)
				$update_data=$this->get_change_record(array('SYSTEM_UPDATE_ID'=>$update_data));
			
			$start_mode= $start_mode?$start_mode:$type;
			if (log::$log_operations[log::$log_types['log_system_updates']['LOG_TYPE_ID']][$start_mode]['TITLE'])
				$start_mode = log::$log_operations[log::$log_types['log_system_updates']['LOG_TYPE_ID']][$start_mode]['TITLE'];
			elseif (metadata::$lang['lang_log_system_'.$start_mode])
				$start_mode = metadata::$lang['lang_uncheckable_log_system_'.$start_mode];
			
			$log_info = array (
				'system_update_id' => $update_data['SYSTEM_UPDATE_ID'],
				'system_update_date' => $update_data['SU_DATE'],
				'system_update_name' => $update_data['TITLE'],
				'additional_info' => $additional_info,
				'start_mode' => $start_mode
			);
			
			$extended_info = $start_mode.':';
			
			return log::register('log_system_updates', $type, $log_info, 0, 0, 0, 0, $extended_info);
		}
		
		// *** SUEXEC ***
		
		/**
		* Вычисление параметров доступа для передачи в SuExec скрипты
		*/
		
		public function get_secure_params_for_suexec() {
				$result = array();
				// login
				$result['l'] = base64_encode($this->auth->user_info['LOGIN']);
				// secure key
				$result['sk']	= base64_encode($this->auth->get_secure_key());
				return $result;
		}
		
		
		/**
		* Получение текущего хоста
		*/
		public function get_host() {
			$httpType	= ($_SERVER['HTTPS'] == 'on' ? 's' : '');
			return "http{$httpType}://{$_SERVER['SERVER_NAME']}";
		}
		
		/**
		* Проверка на возможность записи нового файла
		*/
		
		private function check_can_copy($src_file, $dest_file, $exception_on_false=true) {
			if ($this->is_readable($src_file, $exception_on_false) && $this->is_writeable($dest_file, $exception_on_false))
				return true;
				
			return false;
		}
		
		/**
		* Проверка, есть ли право переписать файл, используется suExec
		* @param string $src_file_path - путь к файлу
		* @return boolean
		*/ 
		
		public function is_readable ($src_file_path, $exception_on_false=true) {
			$params=$this->get_secure_params_for_suexec();
			$params['path']	= base64_encode($src_file_path);
			$can_read = @file_get_contents($this->get_host().lib::make_request_uri($params, $this->exec_scripts['check_can_read_script']));
			if ($can_read == 'true') return true;
			
			if ($exception_on_false) {
				if (!$can_write) {
					$can_write = metadata::$lang['lang_system_update_can_not_run_cgi_script'].': '.$this->get_host().lib::make_request_uri($params, $this->exec_scripts['check_can_read_script']).'<br>'.metadata::$lang['lang_system_update_can_not_run_cgi_script_descr'];
				}
				throw new Exception($this->te_object_name.": ".$can_read);
			}
			
			return false;
		}
		
		
		/**
		* Проверка, есть ли право переписать файл, используется suExec
		* @param string $src_file_path - путь к файлу
		* @return boolean
		*/ 
		public function is_writeable ($src_file_path, $exception_on_false=true) {
			$params=$this->get_secure_params_for_suexec();
			$params['path']	= base64_encode($src_file_path);
			$can_write = @file_get_contents($this->get_host().lib::make_request_uri($params, $this->exec_scripts['check_can_write_script']));
			if ($can_write == 'true') return true;
			if ($exception_on_false) {
				if (!$can_write) {
					$can_write = metadata::$lang['lang_system_update_can_not_run_cgi_script'].': '.$this->get_host().lib::make_request_uri($params, $this->exec_scripts['check_can_write_script']).'<br>'.metadata::$lang['lang_system_update_can_not_run_cgi_script_descr'];
				}
				throw new Exception($this->te_object_name.": ".$can_write);
			}
			return false;
		}
		
		/**
		* Функция копирования файла, используется suExec
		* @param string $src_file - путь к новому файлу
		* @param string $dest_file - путь к старому файлу
		* @return boolean - удалось ли скопировать
		*/
		
		private function copy_file($src_file, $dest_file, $exception_on_false=true) {
			$params = $this->get_secure_params_for_suexec();
			$params['from'] = base64_encode($src_file);
			$params['to'] = base64_encode($dest_file);

			$is_copied = @file_get_contents($this->get_host().lib::make_request_uri($params, $this->exec_scripts['copy_script']));
			if ($is_copied == 'true') return true;
			if ($exception_on_false) {
				if (!$can_write) 
					$can_write = metadata::$lang['lang_system_update_can_not_run_cgi_script'].': '.$this->get_host().lib::make_request_uri($params, $this->exec_scripts['copy_script']).'<br>'.metadata::$lang['lang_system_update_can_not_run_cgi_script_descr'];
			
				throw new Exception($this->te_object_name.": ".$is_copied);
			}
			return false;
		}
		
		/**
		* Функция удаления файла, используется suExec. 
		* Если после удаления файла остается пустая директория, то удаляется и она
		*
		* @param string $file_path - путь к удаляемому файлу
		*/
		
		private function remove_file($file_path, $exception_on_false=true) {
				$params = $this->get_secure_params_for_suexec();
				$params['from'] = base64_encode($file_path);
				$is_removed = @file_get_contents($this->get_host().lib::make_request_uri($params, $this->exec_scripts['remove_script']));

				if ($is_removed == 'true') return true;
			
				if ($exception_on_false) {
					if (!$is_removed) 
						$is_removed = metadata::$lang['lang_system_update_can_not_run_cgi_script'].': '.$this->get_host().lib::make_request_uri($params, $this->exec_scripts['remove_script']).'<br>'.metadata::$lang['lang_system_update_can_not_run_cgi_script_descr'];
				
					throw new Exception($this->te_object_name.": ".$is_removed);
				}
				return false;
		}
		
		/**
		* Запускаем prebuilder
		*/
		
		private function run_prebuilder() {
			ob_start();
			include (params::$params['adm_data_server']['value'].'prebuild/prebuilder.php');
			ob_end_clean();
		}
		
	}
?>