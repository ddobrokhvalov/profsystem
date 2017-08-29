<?php
/**
 * Утилита автоматической рассылки писем подписчикам
 * 
 * Утилиту рекомендуется запускать с увеличенным максимальным временем выполнения скрипта, например
 * /usr/local/bin/php -d max_execution_time=1800 /www/project/adm/scripts/autosend_tool.php > /dev/null 2>&1
 */
include_once( dirname( __FILE__ ) . "/../data/config/params.php" );
include_once( params::$params["adm_data_server"]["value"]."class/app/table/subscribe_list/subscribe_common.php" );
include_once( params::$params["adm_data_server"]["value"] . "prebuild/metadata_" . params::$params["default_interface_lang"]["value"] . ".php" );

$today = getdate();

$subscribe_lists = db::sql_select( 'select * from SUBSCRIBE_LIST order by LIST_ORDER' );

foreach ( $subscribe_lists as $list_item )
{
	$isInPeriod = false;

	// дата первой рассылки в формате YYYYMMDD
	$startDate = substr( $list_item["START_DATE"], 0, 8 );
	
	// дата запуска скрипта рассылки в формате YYYYMMDD
	$todayDate = sprintf( "%4d%02d%02d", $today['year'], $today['mon'], $today['mday'] );
	
	// ежедневная рассылка
	if ( $list_item["PERIOD"] == 2 )
	{
		$isInPeriod = true;
	}
	// еженедельная рассылка
	elseif ( $list_item["PERIOD"] == 3 )
	{
		// для вычисления еженедельного периода применяем простой метод:
		// 1. получаем день недели от даты первой рассылки
		// 2. получаем день недели даты запуска скрипта рассылки
		// 3. сравниваем обе даты и дни недели

		// получаем день недели даты первой рассылки
		$startDateArray = getdate( mktime(0, 0, 0, substr( $startDate, 4, 2 ), substr( $startDate, 6 ), substr( $startDate, 0, 4 ) ) );
		$startDateWday = $startDateArray['wday'];
		
		// получаем день недели даты запуска скрипта рассылки
		$todayWday = $today['wday'];

		// сравниваем даты и дни недели
		$isInPeriod = $todayDate >= $startDate && $startDateWday == $todayWday;
	}
	// ежемесячная рассылка
	elseif ( $list_item["PERIOD"] == 4 )
	{
		// день месяца даты первой рассылки
		$startDateMday = substr( $startDate, 6 );

		// день месяца даты первой рассылки
		$todayMday = $today['mday'];
		$todayMonth = $today['mon'];
		
		$isInPeriod = $todayDate >= $startDate && ( $startDateMday == $todayMday ||
			( ( $todayMonth == 2 ) && $startDateMday > 28 && $todayMday == 29 ) ||
			( ( $todayMonth == 4 || $todayMonth == 6 || $todayMonth == 9 || $todayMonth == 11) && $startDateMday == 31 && $todayMday == 30 ) );
	}
	
	// если лист рассылки попадает в нужный период
	if ( $isInPeriod )
	{
		$module = new subscribe_common();
		
		// подпись со ссылками
		$signature = subscribe_common::get_signature( $list_item['LANG_ID'], '', $list_item['SUBSCRIBE_LIST_ID'] );
		
		// выдергиваем список высланного контента
		$sendedList = db::sql_select( "select * from SUBSCRIBE_SENDED_CONTENT where SUBSCRIBE_LIST_ID = :SUBSCRIBE_LIST_ID",
				array( 'SUBSCRIBE_LIST_ID' => $list_item['SUBSCRIBE_LIST_ID'] ) );
		
		$sendedContent = array();
		foreach( $sendedList as $record )
			$sendedContent[$record["SUBSCRIBE_LIST_ID"]][$record["PRG_MODULE_ID"]][$record["CONTENT_ID"]] = 1;
		unset( $sendedList );
		
		$contentForSend = array();
		
		foreach ( array( 'NEWS', 'CONTENT_LIST' ) as $module_name )
		{
			// выбираем контент
			$availableContent = db::sql_select( "
					select {$module_name}.*, INF_BLOCK.INF_BLOCK_ID, INF_BLOCK.PRG_MODULE_ID
					from {$module_name}, CONTENT_MAP, SUBSCRIBE_LIST_INF_BLOCK, INF_BLOCK, PRG_MODULE
					where {$module_name}.VERSION = 0 and {$module_name}.LANG_ID = :LANG_ID and
						{$module_name}.SEND_IT = 1 and {$module_name}.{$module_name}_ID = CONTENT_MAP.CONTENT_ID and
						CONTENT_MAP.INF_BLOCK_ID = SUBSCRIBE_LIST_INF_BLOCK.INF_BLOCK_ID and
						SUBSCRIBE_LIST_INF_BLOCK.SUBSCRIBE_LIST_ID = :SUBSCRIBE_LIST_ID and
						INF_BLOCK.INF_BLOCK_ID = SUBSCRIBE_LIST_INF_BLOCK.INF_BLOCK_ID and
						PRG_MODULE.PRG_MODULE_ID = INF_BLOCK.PRG_MODULE_ID and 
						PRG_MODULE.SYSTEM_NAME = '{$module_name}'
					order by {$module_name}." . ( $module_name == 'NEWS' ? 'NEWS_DATE' : 'LIST_ORDER' ) . " desc",
				array( 'LANG_ID' => $list_item['LANG_ID'], 'SUBSCRIBE_LIST_ID' => $list_item['SUBSCRIBE_LIST_ID'] ) );
			
			// проверяем его доступность на рассылку
			foreach ( $availableContent as $availableRecord )
			{
				if ( !$sendedContent[$list_item["SUBSCRIBE_LIST_ID"]][$availableRecord["PRG_MODULE_ID"]][$availableRecord["{$module_name}_ID"]] )
				{
					if ( $module_name == 'NEWS' )
					{
						$module -> env['lang_id'] = $list_item['LANG_ID'];
						$module -> env['version'] = $module -> env['page_id'] = $module -> env['area_id'] = 0;
						
						$path_and_area = $module -> __call( 'get_url_by_module_name', array( 'NEWS', $availableRecord['INF_BLOCK_ID'], true, false ) );
						
						// генерим ссылку на страницу с контентом
						if ( $path_and_area['PATH'] && $path_and_area['PATH'] != 'index.php' )
						{
							$availableRecord['NEWS_DATE'] = lib::unpack_date( $availableRecord['NEWS_DATE'], 'short' );
							$availableRecord['URL'] = $path_and_area['PATH'] . '?id_' . $path_and_area['AREA'] . '=' . $availableRecord["{$module_name}_ID"];
						}
					}
					
					if ( $module_name == 'CONTENT_LIST' || ( $module_name == 'NEWS' && $availableRecord['URL'] ) )
					{
						$contentForSend[$module_name][] = $availableRecord;
						
						$sendedContent[$list_item["SUBSCRIBE_LIST_ID"]][$availableRecord["PRG_MODULE_ID"]][$availableRecord["{$module_name}_ID"]] = 1;
						
						// пишем в базу инфо об отправленном контенте
						db::insert_record( 'SUBSCRIBE_SENDED_CONTENT', array(
							'SUBSCRIBE_LIST_ID'=> $list_item['SUBSCRIBE_LIST_ID'],
							'CONTENT_ID' => $availableRecord["{$module_name}_ID"],
							'PRG_MODULE_ID' => $availableRecord['PRG_MODULE_ID'] ) );
					}
				}
			}
		}
		
		// рассылка
		if ( count( $contentForSend["NEWS"] ) > 0 || count( $contentForSend["CONTENT_LIST"] ) > 0 )
		{
			$tpl = new smarty_ee( metadata::$lang ); $tpl -> assign( $contentForSend );
			$email_body = $tpl -> fetch( params::$params['common_data_server']['value'] . 'module_tpl/subscription/autosend.tpl' );
			unset( $tpl );
			
			$signature = subscribe_common::get_signature( $list_item['LANG_ID'], '', $list_item['SUBSCRIBE_LIST_ID'] );
			
			$subscriber_list = db::sql_select( '
				select SUBSCRIBER.*
				from SUBSCRIBER, SUBSCRIBER_SUBSCRIBE_LIST
				where SUBSCRIBER.SUBSCRIBER_ID = SUBSCRIBER_SUBSCRIBE_LIST.SUBSCRIBER_ID
					and SUBSCRIBER_SUBSCRIBE_LIST.SUBSCRIBE_LIST_ID = :SUBSCRIBE_LIST_ID
					and SUBSCRIBER.ACTIVE = 1 and SUBSCRIBER.ACTIVE IS NOT NULL',
				  array( 'SUBSCRIBE_LIST_ID' => $list_item['SUBSCRIBE_LIST_ID'] ) );
			
			foreach ( $subscriber_list as $recipient )
			{
				$to_address = $recipient['EMAIL'];
				$to_name = $recipient['FIO'];
				$from_address = $list_item['SENDER_EMAIL'];
				$from_name = $list_item['SENDER_NAME'];
				$subject = preg_replace( '/<SUBSCRIBER_NAME>/', $to_name, $list_item['EMAIL_SUBJECT'] );
				$message = preg_replace( '/<SUBSCRIBER_NAME>/', $to_name, $list_item['EMAIL_HEADER'] . $email_body  . $list_item['EMAIL_FOOTER'] );
				$encoding = params::$params['encoding']['value'];
				
				$signature_link = preg_replace( array( '/<EMAIL>/', '/<PASSWORD>/' ), array( $recipient['EMAIL'], $recipient['PASSWORD'] ), $signature );
				
				lib::post_mail( $to_address, $to_name, $from_address, $from_name, $subject, $message . $signature_link, $encoding );
			}
			
			db::insert_record( 'SUBSCRIBE_ARCHIVE', array(
				'SUBSCRIBE_LIST_ID' => $list_item['SUBSCRIBE_LIST_ID'],
				'SITE_ID' => '', 'LANG_ID' => '', 'AUTH_USER_ID' => '',
				'EMAIL_SUBJECT' => $list_item['EMAIL_SUBJECT'],
				'EMAIL_BODY' => $list_item['EMAIL_HEADER'] . $email_body  . $list_item['EMAIL_FOOTER'] . $signature,
				'EMAIL_DATETIME' => lib::pack_date(date('d.m.Y H:i'), 'long'),
				'EMAIL_ATTACH' => '' ) );
		}
	}
}
?>
