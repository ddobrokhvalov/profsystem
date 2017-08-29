<?php
/**
 * Класс для реализации нестандартного поведения таблицы "Простой текст"
 *
 * @package    RBC_Contents_5_0
 * @subpackage app
 * @copyright  Copyright (c) 2006 RBC SOFT
 */
class content_text extends table_workflow
{
	/**
	 * Перед выводом карточки редактирования меняем метаданные таким образом, чтобы не показывать
	 * ссылку на расширенный редактор в случае, если заголовок текста начинается с символа '*'
	 *
	 * @see table::action_change()
	 */
	public function action_change()
	{
		$record = $this -> full_object -> get_change_record( $this -> primary_key -> get_from_request() );
		
		metadata::$objects[$this->obj]['fields']['BODY']['editor'] = 
			metadata::$objects[$this->obj]['fields']['BODY']['editor'] && mb_substr( $record['TITLE'], 0, 1, params::$params["encoding"]["value"] ) != '*';
		
		$this->call_parent('action_change');
	}
}
?>