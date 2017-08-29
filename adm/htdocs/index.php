<?php
/*
 * @todo Что делать с бенчами? Сейчас вывод закомментирован, но они считаются.
 */

//session_start();
//$_SESSION["AUTH_USER_ID"]=1;

// 1-ый метод обработки ошибок до подгрузки object_interface
try {
	include_once("../data/config/params.php");
	include_once(params::$params["adm_data_server"]["value"]."class/core/object/object.php");
	$object_interface=object::factory("_object_interface");
	}
catch (Exception $e) {
	if ($_REQUEST['action']=='service') {
		header( 'Content-Type: text/xml; charset=' . params::$params['encoding']['value'] );
		echo object::xml_error($e->getMessage(), $e->getFile(), $e->getLine(),
			(method_exists($e, 'getDebugMessage'))?$e->getDebugMessage():'',
			bench::get_trace_as_string(bench::filter_trace($e->getTrace())));
	}
	else {
		if (!class_exists('metadata'))
			include_once(params::$params["adm_data_server"]["value"]."prebuild/metadata_".params::$params["default_interface_lang"]["value"].".php");
			if (class_exists('object'))
				$error_form = object::html_error($e->getMessage(), $e->getFile(), $e->getLine(),
			(method_exists($e, 'getDebugMessage'))?$e->getDebugMessage():'',
			bench::get_trace_as_string(bench::filter_trace($e->getTrace())));
			else
				$error_form = 'Error: '.$e->getMessage().'; <br> Debug info:'.$e->getDebugMessage();
			
		$tpl=new smarty_ee(metadata::$lang);
		$tpl->assign("body", $error_form);
		echo $tpl->fetch(params::$params["adm_data_server"]["value"]."tpl/core/object/html_common.tpl");
	}
	exit;
}

// 2-ой метод обработки ошибок - стандартный, описанный в object_interface
try {
	$bench->register(bench::bencher("all_parts"), "interface factory", 1);//
	$object_interface->print_common();
}
catch (Exception $e) {
	trigger_error(object_interface::get_exception_serialized($e), E_USER_ERROR);
}

// отладочная инфа
// $bench->echo_report(false, true); echo number_format(memory_get_usage());
?>
