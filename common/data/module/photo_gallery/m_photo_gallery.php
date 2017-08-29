<?php
/**
 * Модуль "Фотогалерея"
 *
 * @package    RBC_Contents_5_0
 * @subpackage module
 * @copyright  Copyright (c) 2008 RBC SOFT
 * @version 1.0
 * @todo Возможно для модуля нужно будет завести отдельный шаблон раздела.
 */

/**
 * Количество дней на которые ставится кука при голосовании
 */
define('VOTE_COOKIE_DAYS_COUNT', 100);

class m_photo_gallery extends module
{
	/**
	* Объект шаблонизатора модуля
	*/
	protected $tpl = null;

	/**
	* Диспетчер модуля
	*/
	protected function content_init()
	{
		// Инициализация шаблонизатора
		$this->tpl = new smarty_ee_module($this);

		// ID фотографии
		$item_id = intval($this->q_param['id']);

		if ($item_id > 0) {
			// Одна фотография
			$tpl_file = 'item.tpl';
			$this->mode_item($item_id);
		}
		else {
			// Таблица (список) фотографий
			$tpl_file = 'photo_gallery.tpl';
			$this->mode_list();
		}

		$this->body = $this->tpl->fetch($this->tpl_dir . $tpl_file);
	}

	/**
	 * Список фотографий с табличным выводом
	 */
	function mode_list()
	{
		$filter_str = '';
		$limit_str  = '';
		$filter_arr = array();

		// Получаем общее число фотографий
		$query_count   = $this->get_module_sql('count(*) as ITEMS_COUNT', $filter_str);
		$content_count = db::sql_select($query_count, $this->get_module_binds() + $filter_arr);
		$items_count   = isset($content_count[0]['ITEMS_COUNT']) ?
			$content_count[0]['ITEMS_COUNT'] : 0;

		// Возврат, если галерея пуста
		if (!$items_count) {
			return;
		}

		$cols_per_page = max( intval( $this -> view_param['cols'] ), 1 );
		$rows_per_page = max( intval( $this -> view_param['rows'] ), 1 );

		// Делаем постраничную навигацию
		$items_per_page = $cols_per_page * $rows_per_page;
		if ($items_count > $items_per_page) {
			$from = (max(intval($this->q_param['from']), 1) - 1) * $items_per_page;
			$this->tpl->assign('navigation',
				lib::page_navigation($items_per_page, $items_count, 'from_'.$this->env['area_id'], $this->tpl_dir.'navigation.tpl'));
			$limit_str = "limit $from, $items_per_page";
		}

		// Получаем содержимое фотогалереи
		$query_gal = $this->get_module_sql(
			$this->module_table.'.*', $filter_str,
			"order by {$this->module_table}.LIST_ORDER",
			$limit_str);

		$content = db::sql_select($query_gal, $this->get_module_binds() + $filter_arr);

		// Формируем таблицу и ссылки на фотографии
		$content_table = array();
		$row = 0;
		$col = 0;
		$content_table[$row] = array();

		foreach ($content as $item)
		{
			// Ссылка на фотографию
			$item['URL'] 	= "photo.php?id_{$this -> env['area_id']}={$item['PHOTO_GALLERY_ID']}";

			if ($col == $cols_per_page) {
				$col = 0;
				$row++;
				$content_table[$row] = array();
			}

			array_push($content_table[$row], $item);
			$col++;
		}

		// Записываем результат в шаблон
		$this->tpl->assign('content', $content_table);
	}

	/**
	 * Одна фотография
	 *
	 * @param int $item_id ID фотографии
	 */
	function mode_item($item_id)
	{
		// Получаем текущую фотографию
		$filter_str	= " AND {$this->module_table}.{$this->module_table}_ID=:item_id ";
		$filter_arr = array('item_id'=>$item_id);

		$query_item = $this->get_module_sql( $this->module_table.".*", $filter_str);
		$content = db::sql_select($query_item, $this->get_module_binds() + $filter_arr);

		// Если есть текущая фотография, получим следующую фотографию. А если следующей нет, то первую
		if (isset($content[0])) {

			$content[0]['URL_CURRENT'] = "photo.php?id_{$this -> env['area_id']}=$item_id";
			// Получаем следующую фотографию
			$filter_str	= " AND {$this->module_table}.LIST_ORDER > :list_order_val ";
			$filter_arr = array('list_order_val'=>$content[0]['LIST_ORDER']);

			$query_next = $this->get_module_sql(
			$this->module_table.".{$this->module_table}_ID", $filter_str,
				"order by {$this->module_table}.LIST_ORDER ", " LIMIT 1");
			$next = db::sql_select($query_next, $this->get_module_binds() + $filter_arr);

			//если нет следующей, то получим первую
			if (!isset($next[0])) {
				$query_next = $this->get_module_sql(
				$this->module_table.".{$this->module_table}_ID", '',
					"order by {$this->module_table}.LIST_ORDER ", " LIMIT 1");
				$next = db::sql_select($query_next, $this->get_module_binds());
			}


			$content[0]['URL_NEXT'] = ($next[0][$this->module_table.'_ID'] != $content[0][$this->module_table.'_ID']) ?
				"photo.php?id_{$this -> env['area_id']}={$next[0]['PHOTO_GALLERY_ID']}" : null;

			// Голосование
			if ($this->view_param['can_vote']) {
				$this->vote($item_id);
			}
		}


		// Записываем результат в шаблон
		$this->tpl->assign('content', $content);
	}

	/**
	 * Голосование по фотографиям
	 *
	 * @param int $id ID фотографии
	 */
	function vote($item_id)
	{
		$this->tpl->assign('can_vote', true);
		if ($_COOKIE['phg_' . $this->env['block_id']]) {
			$this->tpl->assign('voted', 1);
			return;
		}

		// Стартуем сессию для Captcha
		session_start();

		// Еще не голосовали (или уже пытаемся голосовать)
		if ($this->q_param['vote']) {
			// пытаемся голосовать
			if (captcha::check($this->q_param['captcha_id'], $this->q_param['captcha_code'])) {
				if ($this->do_vote($item_id)) {
					$this->tpl->assign('thanks', true);
				}
				else {
					$this->tpl->assign('fail', true);
				}
				return;
			}
			else {
				$this->tpl->assign('capcha_error', true);
			}
		}

		// Генерируем код CAPTCH-и
		$capcha_id = captcha::generate();
		$this->tpl->assign('capcha_id', $capcha_id);
	}

	/**
	 * Операция голосования
	 *
	 * @param int $item_id ID фотографии
	 * @return mixed Результат апдейта таблицы
	 */
	function do_vote($item_id)
	{
		// Инкрементируем количество голосов за данную фотографию
		$result = db::sql_query("update PHOTO_GALLERY set VOTE_COUNT=VOTE_COUNT+1 where PHOTO_GALLERY_ID='$item_id'");
		if ($result) {
			// ставим куку на VOTE_COOKIE_DAYS_COUNT дней.
			setcookie('phg_' . $this->env['block_id'], 1, time() + VOTE_COOKIE_DAYS_COUNT*24*60*60, "/", $_SERVER['HTTP_HOST']);
		}

		return $result;
	}
}
?>
