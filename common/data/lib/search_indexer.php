<?
require_once("lib_abstract.php");
//var_dump(params::$params);
$te_objects = db::sql_select("select * from TE_OBJECT where IN_SEARCH = 1");
$te_objects_ids = array();
foreach($te_objects as $te_object){
	$te_objects_ids[] = $te_object["TE_OBJECT_ID"];
}
$te_objects_ids = "( ".implode(", ", $te_objects_ids)." )";
//var_dump($te_objects);
db::sql_query("delete from SEARCH_CONTENT where TE_OBJECT_ID not in ".$te_objects_ids);

$search_content = db::sql_select("select * from SEARCH_CONTENT");
$search_content = lib::array_reindex($search_content, "TE_OBJECT_ID", "OBJECT_ID", "VERSION", "LANG_ID");

foreach($te_objects as $te_object){
	$te_object_content_sql = "select * from `".$te_object["SYSTEM_NAME"]."`";
	$te_object_content = db::sql_select($te_object_content_sql);
	foreach($te_object_content as $te_object_item){
		if(!isset($search_content[$te_object["TE_OBJECT_ID"]][$te_object_item[$te_object["SYSTEM_NAME"]."_ID"]][$te_object_item["VERSION"]][$te_object_item["LANG_ID"]])){
			var_dump($te_object["SYSTEM_NAME"]." ".$te_object_item[$te_object["SYSTEM_NAME"]."_ID"]." ".$te_object_item["VERSION"]." ".$te_object_item["LANG_ID"]." insert");
			db::insert_record("SEARCH_CONTENT", 
								array(	"TE_OBJECT_ID"=>$te_object["TE_OBJECT_ID"], 
										"OBJECT_ID"=>$te_object_item[$te_object["SYSTEM_NAME"]."_ID"], 
										"TITLE"=>$te_object_item["TITLE"],
										"ANNOUNCE"=>$te_object_item["ANNOUNCE"],
										"BODY"=>$te_object_item["BODY"],
										"VERSION"=>$te_object_item["VERSION"],
										"LANG_ID"=>$te_object_item["LANG_ID"]
									)
							);
		}else{
			if($search_content[$te_object["TE_OBJECT_ID"]][$te_object_item[$te_object["SYSTEM_NAME"]."_ID"]][$te_object_item["VERSION"]][$te_object_item["LANG_ID"]]["TITLE"] != $te_object_item["TITLE"]
				|| $search_content[$te_object["TE_OBJECT_ID"]][$te_object_item[$te_object["SYSTEM_NAME"]."_ID"]][$te_object_item["VERSION"]][$te_object_item["LANG_ID"]]["ANNOUNCE"] != $te_object_item["ANNOUNCE"]
				|| $search_content[$te_object["TE_OBJECT_ID"]][$te_object_item[$te_object["SYSTEM_NAME"]."_ID"]][$te_object_item["VERSION"]][$te_object_item["LANG_ID"]]["BODY"] != $te_object_item["BODY"]){
				
				var_dump($te_object["SYSTEM_NAME"]." ".$te_object_item[$te_object["SYSTEM_NAME"]."_ID"]." ".$te_object_item["VERSION"]." ".$te_object_item["LANG_ID"]." update");
				db::update_record("SEARCH_CONTENT",
									array(	"TITLE"=>$te_object_item["TITLE"],
											"ANNOUNCE"=>$te_object_item["ANNOUNCE"],
											"BODY"=>$te_object_item["BODY"]
										),
									array(),
									array(	"TE_OBJECT_ID"=>$te_object["TE_OBJECT_ID"], 
											"OBJECT_ID"=>$te_object_item[$te_object["SYSTEM_NAME"]."_ID"], 
											"VERSION"=>$te_object_item["VERSION"],
											"LANG_ID"=>$te_object_item["LANG_ID"]
										)
								);
			}
		}
	}
}