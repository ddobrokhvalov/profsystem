<?PHP

	include_once (params::$params["adm_data_server"]["value"]."class/core/object/table.php");

	/**
	* Класс измененимя метаданных через web-интерфейс. Абстрактный класс работы с таблицами данных
	* 
	* @package		RBC_Contents_5_0
 	* @subpackage te
 	* @copyright	Copyright (c) 2008 RBC SOFT
	* @author Alexandr Vladykin <avladykin@rbc.ru>
	*/

	abstract class metadata_change_datatable extends metadata_change {
		
		/**
		* Метод по умолчанию, выводит таблицу данных
		*/
		
		protected function action_index () {
			$tpl = new smarty_ee( metadata::$lang  );
			$tpl -> assign( 'title', $this -> get_title() );
			
			$tpl -> assign( 'header', $this->get_html_operations('top')  );
			$tpl -> assign( 'footer', $this->get_html_operations('bottom') );
			$tpl -> assign( 'table',  $this->get_html_table($this->get_pk($_REQUEST)));
			
			$this -> body = $this->info_block.$tpl -> fetch( $this -> tpl_dir . 'core/html_element/html_grid.tpl' );			
			
		}
		
		/**
		* Абстрактный метод, возвращает из массива данных первичный ключ записи
		* @param array $data Данные
		* @return array
		*/
		
		protected function get_pk($data) {}
		
		
		/**
		* Возвращает html операций для таблиц
		* @param string $label Указывает в каком месте будут операции выведены - сверху или снизу (top/bottom)
		* @return string
		*/
		
		protected function get_html_operations($label) {
			return html_element::html_operations( array( 'operations' => $this->get_table_operations(), 'label' => $label ), $this -> tpl_dir . 'core/html_element/html_operation.tpl' );
		}

		/**
		* Абстрактный метод, возвращает основные операции для таблиц в формате для подставновки в html_element::html_operations
		* @return array
		*/
		
		protected function get_table_operations() {}


		/**
		* Возвращает таблицу в html-виде
		* @param array $pk Первичный ключ таблицы
		* @return string
		*/
		
		protected function get_html_table($pk) {
			$records = $this->get_table_records($pk);
			return html_element::html_table( 
				array( 
					'header' => $this->get_table_header(), 
					'list' => $records,
					'counter' => sizeof($records),
					'html_hidden' => $this -> url -> get_hidden( 'group_delete' )
				), 
				$this -> tpl_dir . 'core/html_element/html_table.tpl'
			);
		} 
		
		/**
		* Возвращает массив заглавной строки таблицы для подстановки в html_element::html_table
		* @return array
		*/

		protected function get_table_header () {}
		
		/**
		* Возвращает строки таблицы в html-виде для подстановки в html_element::html_table
		* @return array
		*/

		protected function get_table_records () {}
		
		/**
		* Возвращает данные операций для строки таблицы
		* @param array $pk Первичный ключ записи
		* @return string
		*/

		protected function get_record_operations ($pk) {}
		
//--------------------------------------------------------------------------------------------------------------------------------------------------

		/**
		* Добавление записи
		*/
		
		public function action_add () {
			list($this->title, $this->body)=$this->html_card($this->get_pk($_REQUEST));
		}
		
		/**
		* Редактирование записи
		*/
		
		public function action_change () {
			list($this->title, $this->body)=$this->html_card($this->get_pk($_REQUEST));
		}
		
		/**
		* HTML-карточка записи
		* @param array $pk Первичный ключ
		* @todo Вынести общее
		* @return array название-содержимое
		* @see table::html_card
		*/
		
		protected function html_card($pk) {}
		
		/**
		* Возвращает поля для формы редактирования
		* @param array $pk Первичный ключ
		* @return array
		*/
		
		protected function get_fields_for_form($pk) {}

		/**
		* Возвращает кнопки операций для подстановки в html_element::html_operations
		* @param array $oper_array Массив операций, которые нужно вывести
		* @param string $form_name Название формы
		* @return array
		*/
		
		protected function get_operations ($oper_array, $form_name) {
			$apply_action = $_REQUEST['action'];
			
			// формируем apply_action
			if ($apply_action[strlen($apply_action)-1]=='y') 
				$apply_action[strlen($apply_action)-1]='i';
		
			if ($apply_action[strlen($apply_action)-1]!='e') 
				$apply_action .= 'e';
			
			$apply_action .= 'd_apply';
			
			// кнопки
			$operations['apply'] = array("name"=>"apply", "alt"=>metadata::$lang["lang_action_apply"], "onClick"=>"if ( CheckForm.validate( document.forms['{$form_name}'] ) ) { document.forms['{$form_name}'].action.value='{$apply_action}'; document.forms['{$form_name}'].submit() }; return false", "url"=>"#");
			$operations['save'] = array("name"=>"save", "alt"=>metadata::$lang["lang_action_save"], "onClick"=>"if ( CheckForm.validate( document.forms['{$form_name}'])) {document.forms['{$form_name}'].submit()}; return false;", url=>"#"); 
			$operations['cancel'] = array("name"=>"cancel", "alt"=>metadata::$lang["lang_cancel"], "url"=>$this->url->get_url( "", array( "restore_params" => 1 ) ));
			return array_intersect_key($operations, array_flip($oper_array));
		}
//---------------------------------------------------------------------------------------------------------------------------------------------------		
		
		/**
		* Метод, вызываемый при сохранении записи
		* @see table::action_added
		*/
		
		public function action_added() {
			$this->exec_add($_REQUEST, "_form_", $this->get_pk($_REQUEST));
			$this->url->redirect ("", array("restore_params"=>1));
		}
		
		/**
		* Метод, сохраняющий запись
		* @see table::exec_add
		*/
		public function exec_add($raw_fields, $prefix, $pk) {
			$_SESSION['metadata']['changed']=true;
		}
		
		/**
		* Возвращает подготовленые к вставке в БД значения полей
		* @see table::get_prepared_fields
		* @param array $raw_fields Поля из формы
		* @param string $prefix префикс полей
		* @param array $pk первичный ключ
		*/
		
		protected function get_prepared_fields($raw_fields, $prefix, $pk) {}

		
		/**
		* Метод, вызываемый при сохранении записи
		* see table::action_changed
		*/
		
		public function action_changed() {
			$this->exec_change($_REQUEST, "_form_", $this->get_pk($_REQUEST));
			$this->url->redirect( "", array( "restore_params" => 1 ) );
		}
		
		/** 
		* Метод, вызываемый при сохранении записи, при этом с возвратом на страницу редактирования
		* @see table::action_changed_apply
		*/
		
		public function action_changed_apply() {}
		
		/**
		* Метод, изменяющий запись
		* @see table::exec_change
		*/
		
		public function exec_change ($raw_fields, $prefix, $pk) {
			$_SESSION['metadata']['changed']=true;
		}
		
		/**
		* Метод, вызываемый при удалении записи
		* @see table::action_delete
		*/
		
		public function action_delete () {}
		
		/**
		* Метод, удаляющий запись
		* @see table::exec_delete
		*/
		
		public function exec_delete ($pk) {
			$_SESSION['metadata']['changed']=true;
		}
		
		public function action_order() {}
		
		/**
		* Метод, изменяющий порядок записи в списке
		* @param array $pk Первичный ключ поля
		* @param string $dir Направление, up - вверх, down - вниз
		*/

		public function exec_order($pk, $dir) {
			$_SESSION['metadata']['changed']=true;
		}
		
	}
?>