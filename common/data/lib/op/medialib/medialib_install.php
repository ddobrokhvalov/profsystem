<?php
class medialib_install {
	static function installTables(){
		dbSelect::query(" 
CREATE TABLE `medialib_libs` (
	`id` INT UNSIGNED DEFAULT '0' NOT NULL AUTO_INCREMENT ,
	`content_id` INT UNSIGNED NOT NULL ,
	`type` CHAR( 10 ) NOT NULL ,
	`version` TINYINT UNSIGNED NOT NULL ,
	`lang` TINYINT UNSIGNED NOT NULL ,
	`total` INT UNSIGNED NOT NULL ,
	`ts` INT UNSIGNED NOT NULL ,
	`stat` INT UNSIGNED NOT NULL ,
	PRIMARY KEY ( `id` )
);");
		
	}
}
?>