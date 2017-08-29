<?php
	/**
	* Класс автотеста - проверка данных таксономии
	* @package		RBC_Contents_5_0
	* @subpackage module
	* @copyright	Copyright (c) 2007 RBC SOFT 
	*/
	class check_taxonomy extends autotest_test
	{
		/**
		* тест
		*/
		public function do_test ()
		{
			$this -> find_no_object_records();
			$this -> find_no_taxonomy_records();
			$this -> find_no_tag_records();
			$this -> find_no_record_records();
			$this -> find_no_record_tags();
		}
		
		/**
		* Поиск записей, привязанных к несуществующему объекту
		*/
		private function find_no_object_records()
		{
			$error_records = $this -> get_no_object_records();
		
			if ( count( $error_records ) )
			{
				$report = array(
					array(
						'descr' => metadata::$lang['lang_autotest_check_tag_no_object_records'] . ': ' . count( $error_records ) ,
						'link_descr' => metadata::$lang['lang_delete'],
						'action' => 'fix_action',
						'confirm_message' => metadata::$lang['lang_autotest_are_you_sure'],
						'fix_link' => 'fix_target=no_object' ) );
				
				$this -> report = array_merge( $this -> report, $report );
			}
		}
		
		private function get_no_object_records()
		{
			return db::sql_select( '
				select TAG_OBJECT.TE_OBJECT_ID
				from TAG_OBJECT
				left join TE_OBJECT on TE_OBJECT.TE_OBJECT_ID = TAG_OBJECT.TE_OBJECT_ID
				where TE_OBJECT.TE_OBJECT_ID is null' );
		}
		
		/**
		* Поиск записей, привязанных к неразрешенному объекту
		*/
		private function find_no_taxonomy_records()
		{
			$error_records = $this -> get_no_taxonomy_records();
		
			if ( count( $error_records ) )
			{
				$report = array(
					array(
						'descr' => metadata::$lang['lang_autotest_check_tag_no_taxonomy_records'] . ': ' . count( $error_records ) ,
						'link_descr' => metadata::$lang['lang_delete'],
						'action' => 'fix_action',
						'confirm_message' => metadata::$lang['lang_autotest_are_you_sure'],
						'fix_link' => 'fix_target=no_taxonomy' ) );
				
				$this -> report = array_merge( $this -> report, $report );
			}
		}
		
		private function get_no_taxonomy_records()
		{
			return db::sql_select( '
				select TAG_OBJECT.TE_OBJECT_ID
				from TAG_OBJECT
				inner join TE_OBJECT on TE_OBJECT.TE_OBJECT_ID = TAG_OBJECT.TE_OBJECT_ID
				where TE_OBJECT.TAXONOMY_ALLOWED = 0' );
		}
		
		/**
		* Поиск записей, привязанных к несуществующим тегам
		*/
		private function find_no_tag_records()
		{
			$error_records = $this -> get_no_tag_records();
		
			if ( count( $error_records ) )
			{
				$report = array(
					array(
						'descr' => metadata::$lang['lang_autotest_check_tag_no_tag_records'] . ': ' . count( $error_records ) ,
						'link_descr' => metadata::$lang['lang_delete'],
						'confirm_message' => metadata::$lang['lang_autotest_are_you_sure'],
						'action' => 'fix_action',						
						'fix_link' => 'fix_target=no_tag' ) );
				
				$this -> report = array_merge( $this -> report, $report );
			}
		}
		
		private function get_no_tag_records()
		{
			return db::sql_select( '
				select TAG_OBJECT.TAG_ID
				from TAG_OBJECT
				left join TAG on TAG.TAG_ID = TAG_OBJECT.TAG_ID
				where TAG.TAG_ID is null' );
		}
		
		/**
		* Поиск записей, привязанных к несуществующим записям таблиц
		*/
		private function find_no_record_records()
		{
			$error_records = $this -> get_no_record_records();
		
			if ( count( $error_records ) )
			{
				$report = array(
					array(
						'descr' => metadata::$lang['lang_autotest_check_tag_no_record_records'] . ': ' . count( $error_records ) ,
						'link_descr' => metadata::$lang['lang_delete'],
						'confirm_message' => metadata::$lang['lang_autotest_are_you_sure'],						
						'action' => 'fix_action',						
						'fix_link' => 'fix_target=no_record' ) );
				
				$this -> report = array_merge( $this -> report, $report );
			}
		}
		
		private function get_no_record_records()
		{
			$tag_object_records = db::sql_select( '
				select distinct TAG_OBJECT.OBJECT_ID, TE_OBJECT.TE_OBJECT_ID, TE_OBJECT.SYSTEM_NAME
				from TAG_OBJECT
				inner join TE_OBJECT on TE_OBJECT.TE_OBJECT_ID = TAG_OBJECT.TE_OBJECT_ID' );
			
			$error_records = array();
			
			foreach ( $tag_object_records as $tag_object_record )
			{
				$record_exists = db::sql_select( '
						select count(*) as RECORD_COUNT from ' . $tag_object_record['SYSTEM_NAME'] . '
						where ' .$tag_object_record['SYSTEM_NAME'] . '_ID = :object_id',
					array( 'object_id' => $tag_object_record['OBJECT_ID'] ) );
				
				unset( $tag_object_record['SYSTEM_NAME'] );
				if ( !$record_exists[0]['RECORD_COUNT'] )
					$error_records[] = $tag_object_record;
			}
			
			return $error_records;
		}
		
		/**
		* Поиск тегов, не привязанных ни к одной записи
		*/
		private function find_no_record_tags()
		{
			$error_records = $this -> get_no_record_tags();
		
			if ( count( $error_records ) )
			{
				$report = array(
					array(
						'descr' => metadata::$lang['lang_autotest_check_tag_no_record_tags'] . ': ' . count( $error_records ) ) );
				
				$this -> report = array_merge( $this -> report, $report );
			}
		}
		
		private function get_no_record_tags()
		{
			return db::sql_select( '
				select TAG.TAG_ID
				from TAG
				left join TAG_OBJECT on TAG_OBJECT.TAG_ID = TAG.TAG_ID
				where TAG_OBJECT.TAG_ID is null' );
		}
		
		/**
		* Удаление ошибочных записей
		*/
		public function fix_action()
		{
			$fix_target = $_REQUEST['fix_target'];
			
			if ( $fix_target == 'no_object' )
				foreach ( $this -> get_no_object_records() as $pk )
					db::delete_record( 'TAG_OBJECT', $pk );
			else if ( $fix_target == 'no_taxonomy' )
				foreach ( $this -> get_no_taxonomy_records() as $pk )
					db::delete_record( 'TAG_OBJECT', $pk );
			else if ( $fix_target == 'no_tag' )
				foreach ( $this -> get_no_tag_records() as $pk )
					db::delete_record( 'TAG_OBJECT', $pk );
			else if ( $fix_target == 'no_record' )
				foreach ( $this -> get_no_record_records() as $pk )
					db::delete_record( 'TAG_OBJECT', $pk );
			
			$tags_count = db::sql_select( '
				select TAG.TAG_ID, count( TAG_OBJECT.TAG_ID ) as NUM_LINKS
				from TAG left join TAG_OBJECT on TAG_OBJECT.TAG_ID = TAG.TAG_ID
				group by TAG.TAG_ID' );
			
			foreach ( $tags_count as $tags_item )
				db::update_record( 'TAG', array( 'NUM_LINKS' => $tags_item['NUM_LINKS'] ), '', array( 'TAG_ID' => $tags_item['TAG_ID'] ) );
			
			return metadata::$lang['lang_done'];
		}
	}
?>