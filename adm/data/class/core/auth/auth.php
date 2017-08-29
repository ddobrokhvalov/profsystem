<?php
/**
 * Класс разделения доступа
 *
 * Инстанцированный вариант содержит предвыбранные данные для обеспечения разделения доступа текущего пользователя.
 * Инстанцируется только через singleton(), чтобы не повторять одни и те же запросы несколько раз.
 * Также класс содержит набор статических методов для различных проверок произвольных пользователей и ролей на доступ к объектам системы
 *
 * @package    RBC_Contents_5_0
 * @subpackage core
 * @copyright  Copyright (c) 2006 RBC SOFT
 * @todo Нужно приделать к разделению доступа по системным разделам наследование прав от родителя
 * @todo подумать над аяксом, который будет периодически дергать сервер, чтобы сессия не умирала
 */
class auth {
	
	/**
	 * Экземпляр класса
	 * @var object auth
	 */
	public static $auth_instance;

	/**
	 * Роли текущего пользователя
	 *
	 * Конструктор объекта заполняет это свойство кляузой IN (без обрамляющих скобок) со списком ролей пользователя (включая родительские) для
	 * того чтобы удобно было организовать разные проверки прав доступа текущего пользователя без лишних запросов.
	 * Если ни одной записи не было выбрано, то свойство будет содержать ноль, что и соответствует невыбору записей
	 * @var string
	 */
	public $user_roles_in=0;

	/**
	 * Флаг, является ли данный пользователь главным администратором с полным доступом к объектам системы
	 * @var boolean
	 */
	public $is_main_admin;

	/**
	 * Сайты, администратором которых является текущий пользователь
	 *
	 * Формат в точности такой же, как описано в {@link auth::$user_roles_in}
	 * @var string
	 */
	public $sites_in=0;

	/**
	 * Сайты, администратором которых является текущий пользователь в виде списка индексированного идентификаторами
	 * @var array
	 */
	public $sites_ids=array();

	/**
	 * Запись из БД с данными текущего пользователя
	 * @var array
	 */
	public $user_info;

///////////////////////////////////////////////////////////////////////////////////////////////////////////


	/**
	 * Конструктор
	 *
	 * Заполняем специальные свойства объекта, а также подключаем пользовательские параметры и метаданные
	 */
	private function __construct(){
		if($_SESSION["AUTH_USER_ID"]){
			system_params::apply_auth_user_params_from_db();
			// Роли текущего пользователя и информация о текущем пользователе
			$this->user_roles_in=self::get_user_roles_in($_SESSION["AUTH_USER_ID"]);
			$this->user_info=current(db::sql_select("SELECT * FROM AUTH_USER WHERE AUTH_USER_ID=:auth_user_id", array("auth_user_id"=>$_SESSION["AUTH_USER_ID"])));
			if ($this->user_info['IS_LOCKED'] || !self::check_user_ip($_SERVER['REMOTE_ADDR']))
				$this->user_info=array();
			
			// Системный пользователь всегда главный админ, для прочих проверяем
			$this->is_main_admin=($this->user_info["LOGIN"]=="system" ? true : self::get_system_privilege($this->user_roles_in, "main_admin"));
		}

		// Подключение метаданных
		if (!class_exists('metadata'))
				include_once(params::$params["adm_data_server"]["value"]."prebuild/metadata_".params::$params["default_interface_lang"]["value"].".php");
				
		// Если пользователь является администратором сайта, то собираем перечень этих сайтов
		if($_SESSION["AUTH_USER_ID"]){
			if (params::$params['install_cms']['value']) {
				list($auth_tables, $auth_clause, $auth_binds)=auth::get_auth_clause($this->user_roles_in, "access", "site", "SITE");
				$rights=db::sql_select("SELECT DISTINCT SITE.SITE_ID FROM {$auth_tables} WHERE {$auth_clause}", $auth_binds);
				if(count($rights)>0){
					$this->sites_in=lib::array_make_in($rights, "SITE_ID");
					$this->sites_ids=array_combine(array_keys(lib::array_reindex($rights, "SITE_ID")), array_keys(lib::array_reindex($rights, "SITE_ID")));
				}
			}
		}
	}

	/**
	 * Синглтон, возвращающий ссылку на экземпляр объекта
	 *
	 * Если переданы параметры, то объект будет создаваться для пользователя, указанного в параметрах
	 *
	 * @param string $auth_user_login	логин пользователя для которого должен быть создан объект auth
	 * @param string $secure_key		секретный ключ - md5(<id>.<login>.<password_md5>)
	 * @return object auth
	 */
	public static function singleton($auth_user_login="", $secure_key=""){
		if(!isset(self::$auth_instance) || !self::$auth_instance->user_info){
			if (!$_SESSION["AUTH_USER_ID"]) {
				// @todo Для принудительного кэширования данных форм можно сделать session_cache_limiter("private_no_expire");
				// @todo Но это влечет за собой массу других проблем. Другого простого способа кэширования форм пока не найдено.
				session_start();
			}
			
			// добавлена проверка на логин, пароль, лок аккаунта, с записью в лог. Остальное пока решено не проверять. Будет вызываться только через api.
			if($auth_user_login && $secure_key){
				$auth_user=db::sql_select("SELECT * FROM AUTH_USER WHERE LOGIN=:login", array("login"=>$auth_user_login));
				if (!sizeof($auth_user)) 
					self::log_register_auth_failed('login_failed', $auth_user_login);
				elseif($secure_key==self::s_get_secure_key($auth_user[0])){
					if ($auth_user[0]['IS_LOCKED']) {
						self::log_register_auth_failed('login_blocked', $auth_user_login);
					}
					else {
						$_SESSION["AUTH_USER_ID"]=$auth_user[0]["AUTH_USER_ID"];
						self::log_register_authorization('authorization');
					}
				}
				else {
					self::log_register_auth_failed('password_failed', $auth_user_login);
				}
			}
			self::$auth_instance=new auth();
		}
		return self::$auth_instance;
	}

	/**
	 * Синглтон для системного пользователя
	 *
	 * Должен применяться только в shell-скриптах, которые никаким образом не могут быть вызваны через веб.
	 *
	 * @return object auth
	 */
	 
	public static function singleton_system(){
		$auth_user_login = 'system';
		$secure_key='';
		
		if(!isset(self::$auth_instance)){
			$auth_user=db::sql_select("SELECT * FROM AUTH_USER WHERE LOGIN=:login", array("login"=>$auth_user_login));
			$secure_key = self::s_get_secure_key($auth_user[0]);
		}
		
		return self::singleton($auth_user_login, $secure_key);
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Возвращает секретный ключ
	 *
	 * @param array $user_info Данные пользователя, если не заданан, берется из параметра $this->user_info
	 *
	 * @return string
	 */
	 
	public function get_secure_key($user_info=null){
		if (!$user_info) $user_info =& $this->user_info;
		return self::s_get_secure_key($user_info);
	}
	

	/**
	 * Возвращает секретный ключ по имени пользователя паролю и идентификатору, статическая ф-ия
	 *
	 * @param array $user_info Данные пользователя
	 *
	 * @return string
	 */

	public static function s_get_secure_key ($user_info) {
		return md5($user_info['AUTH_USER_ID'].'|'.$user_info['LOGIN'].'|'.$user_info['PASSWORD_MD5']);
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Проверка, авторизован ли пользователь
	 *
	 * @return object boolean
	 */
	public static function is_auth(){
		if($_SESSION["AUTH_USER_ID"]){
			if(auth::singleton()->user_info["AUTH_USER_ID"]){
				return true;
			}
			// Зачищаем переменную сессии в случае неуспеха, чтобы не мешалась
			$_SESSION["AUTH_USER_ID"]='';
		}
		return false;
	}

	/**
	 * Форма авторизации, она же проверяет попытки авторизоваться, сообщает об ошибках авторизации и создает сессию в случае успешной авторизации
	 *
	 * @param string $template	шаблон
	 * @return string
	 */
	public static function html_login_form($template){
		$tpl=new smarty_ee(metadata::$lang);
		if($_REQUEST["login"] && $_REQUEST["password"]){
			$user=db::sql_select("SELECT * FROM AUTH_USER WHERE LOGIN=:login", array("login"=>$_REQUEST["login"])); // AND PASSWORD_MD5=:password_md5 AND IS_LOCKED=0", array("login"=>$_REQUEST["login"], "password_md5"=>md5($_REQUEST["password"])));

			if (!count($user)) {
				$error = 'auth_bad_user_name';
				
				// неверный логин
				self::log_register_auth_failed('login_failed');
			}
			elseif ($user[0]['IS_LOCKED']) {
				// заблокирован
				$error = 'auth_locked';
				self::log_register_auth_failed('login_blocked');
			}
			elseif ($user[0]['PASSWORD_MD5']!=md5($_REQUEST["password"])) {
				// если пришло время, блокируем пользователя
				if (
					(self::get_bad_password_max_count_for_user($user[0])>0) 
					&&
					(++$user[0]['BAD_PASSWORD_COUNT']>=self::get_bad_password_max_count_for_user($user[0]))
				) {
					$error = 'auth_bad_password_locked';
					db::update_record('AUTH_USER', array('IS_LOCKED'=>1, 'BAD_PASSWORD_COUNT'=>0), array(), array('AUTH_USER_ID'=>$user[0]['AUTH_USER_ID']));
					self::log_register_auth_failed('password_failed_login_blocked');
				}
				else {
					$error = 'auth_bad_password';
					// иначе увеличиваем счетчик неудачных попыток
					db::update_record('AUTH_USER', array('BAD_PASSWORD_COUNT'=>$user[0]['BAD_PASSWORD_COUNT']), array(), array('AUTH_USER_ID'=>$user[0]['AUTH_USER_ID']));
					self::log_register_auth_failed('password_failed');
				}
				
			}
			elseif (!self::check_user_ip($_SERVER['REMOTE_ADDR'])) {
				$error = 'auth_blocked_ip';
				self::log_register_auth_failed('IP_denied');
			}
			else {
				// все нормально, заходим!

				// но сначала очищаем кол-во неудачных попыток
				db::update_record('AUTH_USER', array('BAD_PASSWORD_COUNT'=>0), array(), array('AUTH_USER_ID'=>$user[0]['AUTH_USER_ID']));
				
				$_SESSION["AUTH_USER_ID"]=$user[0]["AUTH_USER_ID"];
				
				self::log_register_authorization('authorization');
				
				// Переводим пользователя на ту страницу, которую он запрашивал
				if($_SESSION["back_url"]){
					$url=$_SESSION["back_url"];
					$_SESSION["back_url"]="";
				}else{
					$url="index.php";
				}
				setcookie('curStatus', '', time()-3600);
				header("Location: {$url}");
				exit();
			}

			// по соображениям безопасности принято решение не выводить конкретную причину неуспешной авторизации
			/*if (!$error)*/ //$error="auth_unsuccessful";
			$tpl->assign("error", metadata::$lang['lang_'.$error]);
			$tpl->assign("error", metadata::$lang['lang_auth_unsuccessful']);
		}
		return $tpl->fetch($template);
	}

	/**
	 * Логаут из системы
	 * @param boolean $log_register Сохранять ли данные в лог
	 */
	public static function logout($log_register=true){
		if ($log_register)
			self::log_register_authorization('logout');
		
		$_SESSION['AUTH_USER_ID']='';
		header("Location: index.php");
		exit();
	}

///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Проверяет IP на возможность захода в систему
	 * @param string $ip	IP
	 * @return booolean
	 */

	public static function check_user_ip($ip){
		$res=db::sql_select('
			SELECT 
				* 
			FROM 
				AUTH_IP_FILTER 
			WHERE 
				:ip_long 
					BETWEEN 
						CASE 
							WHEN START_IP IS NULL THEN 0
							ELSE START_IP
						END
					AND 
						CASE 
							WHEN FINISH_IP IS NULL THEN 0
							ELSE FINISH_IP
						END
			', 
			array('ip_long' => sprintf('%u', ip2long($ip)))
		);
		return sizeof($res)?true:false;
	}

	
///////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * Сбор кляузы IN для цепочки ролей от самого корня до указанной роли
	 *
	 * Собирает кляузу IN в формате "AND AUTH_ACL.AUTH_SYSTEM_ROLE_ID IN (1, 2)"
	 * для ролей, родительских для $role_id, включая и ее (если $with_self==true).
	 * Если ролей не нашлось, то вернет "AND AUTH_ACL.AUTH_SYSTEM_ROLE_ID IN (0)"
	 *
	 * @param int $role_id			идентификатор роли
	 * @param boolean $with_self	указание включать саму эту роль в результат
	 * @return string
	 */
	public static function get_parent_roles_in($role_id, $with_self=true){
		$selected_roles[]=$role_id;
		return auth::get_parent_roles_in_by_array($selected_roles, $with_self);
	}

	/**
	 * Сбор кляузы IN со всеми ролями пользователя
	 *
	 * Собирает кляузу IN в формате "AND AUTH_ACL.AUTH_SYSTEM_ROLE_ID IN (1, 2)"
	 * для ролей, назначенных для указанного пользователя.
	 * Если ролей не нашлось, то вернет "AND AUTH_ACL.AUTH_SYSTEM_ROLE_ID IN (0)"
	 *
	 * @param int $auth_user_id	идентификатор пользователя
	 * @return string
	 */
	public static function get_user_roles_in($auth_user_id){
		// Набор ролей данного пользователя
		$selected_roles=array();
	
		$user_roles = db::sql_select("
			SELECT 
				AUTH_SYSTEM_ROLE_ID 
			FROM 
				AUTH_USER_SYSTEM_ROLE
			WHERE 
				AUTH_USER_ID=:auth_user_id
			", array("auth_user_id"=>$auth_user_id));		
		
		foreach($user_roles as $ur)
			$selected_roles[]=$ur["AUTH_SYSTEM_ROLE_ID"];
		return auth::get_parent_roles_in_by_array($selected_roles);
	}

	/**
	 * Сбор кляузы IN для цепочки ролей от самого корня до указанных ролей из переданного списка
	 *
	 * Принцип сбора кляузы см. в {@link auth::get_parent_roles_in()}, только цепочка строится не от одной роли, а от нескольких
	 *
	 * @param array $selected_roles		список идентификаторов ролей
	 * @param boolean $with_self		указание включать сами эти роли в результат
	 * @return string
	 * @todo Пока список ролей строится с помощью выемки сразу всех ролей в память и пробежки по нему. Это может быть непроизводительно на большом количестве ролей. Но если много крайних ролей и все они назначены
	 */
	private static function get_parent_roles_in_by_array($selected_roles, $with_self=true){
		bench::bencher("auth_in");
		$roles_in=array();
		// Все роли и их иерархия
		$r_roles=lib::array_reindex(db::sql_select("SELECT * FROM AUTH_SYSTEM_ROLE"), 'AUTH_SYSTEM_ROLE_ID');

		// Путь до коренного родителя для каждой роли, родителей которых надо собрать
		foreach($selected_roles as $role_id){
			$cur_id=$role_id;
			if($with_self){
				$roles_in[$cur_id]=$cur_id;
			}
			while($r_roles[$cur_id]["PARENT_ID"]){
				$cur_id=$r_roles[$cur_id]["PARENT_ID"];
				$roles_in[$cur_id]=$cur_id;
			}
		}
		// Генережка кляузы
		if(!sizeof($roles_in))
			$roles_in[]=0;
		$roles_clause=join(", ", $roles_in);
//		bench::bencher("auth_in", "Сбор кляузы ИН для ролей пользователя");
		return $roles_clause;
	}

	/**
	 * Получение массива потушенных чекбоксов для привязки прав к системным ролям
	 *
	 * Это универсальный метод для всех разделений доступа,
	 * кроме системных, потому что там вторичная и третичная таблицы перевернуты для удобства интерфейса.
	 *
	 * @param int $role_id				идентификатор роли
	 * @param string $index_records_in	{@link table::$index_records_in}
	 * @param string $aot				тип объекта разделения доступа
	 * @param string $aot_table			таблица типа объекта разделения доступа
	 * @return array
	 * @todo Переменные привязки в IN
	 */
	public static function get_disabled_for_auth($role_id, $index_records_in, $aot, $aot_table){
		$disabled=db::sql_select("
			SELECT AUTH_ACL.* FROM AUTH_ACL
			WHERE AUTH_ACL.OBJECT_ID IN ({$index_records_in})
				AND AUTH_ACL.AUTH_SYSTEM_ROLE_ID IN (".auth::get_parent_roles_in($role_id, false).")
				AND AUTH_ACL.AUTH_OBJECT_TYPE_TABLE_ID IN (
					SELECT AUTH_OBJECT_TYPE_TABLE.AUTH_OBJECT_TYPE_TABLE_ID
					FROM AUTH_OBJECT_TYPE_TABLE, AUTH_OBJECT_TYPE
					WHERE AUTH_OBJECT_TYPE_TABLE.SYSTEM_NAME=:aot_table
						AND AUTH_OBJECT_TYPE_TABLE.AUTH_OBJECT_TYPE_ID=AUTH_OBJECT_TYPE.AUTH_OBJECT_TYPE_ID
						AND AUTH_OBJECT_TYPE.SYSTEM_NAME=:aot
				)
			", array("aot"=>$aot, "aot_table"=>$aot_table));
		$r_disabled=array();
		foreach($disabled as $dis){
			$r_disabled[$dis["OBJECT_ID"]][$dis["AUTH_PRIVILEGE_ID"]]=1;
		}
		return $r_disabled;
	}

	/**
	 * Куски кляузы для проверки прав ролей
	 *
	 * Собирает куски кляузы для проверки, есть ли у ролей права $privilege_name на тип объекта разделения доступа с системным именем $system_name.
	 * То есть работает по таблице AUTH_ACL, достраивая все необходимые ограничения вокруг нее
	 *
	 * Если параметр $roles_in является ПУСТОЙ СТРОКОЙ (нужно применять осторожно, чтобы не выдать лишних прав),
	 * то будут выбраны любые роли, то есть метод можно использовать для решения обратной задачи - найти все роли,
	 * которые имеют некоторую привилегию (имеют непосредственно, то есть администраторы сайтов и главные администраторы здесь не учитываются).
	 * При этом нужно помнить, что будут найдены только те роли, которым непосредственно назначена привилегия, а набор ролей-детей, которые
	 * также наследуют эти привилегии придется вычислять дополнительно.
	 *
	 * @param string $roles_in				кляуза IN с ролями, формат как у {@link auth::$user_roles_in}
	 * @param mixed $privilege_name			системное название права. Может быть не только строкой, но и списком с перечнем системных имен
	 * @param string $system_name			системное название типа разделения доступа (INF_BLOCK_SIMPLE, AUTH_SYSTEM_SECTION и т.д.)
	 * @param string $table_system_name		системное название таблицы объектов разделения доступа (то есть ее название в БД)
	 * @param string $pk_field				название поля-первичного ключа в таблице объектов разделения доступа. Если не указано, то равно названию таблицы плюс "_ID". Красивее было бы получать его из соответствующего объекта таблицы, но так быстрее будет работать
	 * @return array
	 * @todo Переменные привязки в IN
	 * @todo Нужно ли проверять поступающие системные названия таблиц на отсутствие инъекций? Или это сугубо разработческое место, а разработчик и так все может?
	 */
	public static function get_auth_clause($roles_in, $privilege_name, $system_name, $table_system_name, $pk_field=""){
		// Определяемся с кляузой привилегий в зависимости от массивности $privilege_name
		if(is_array($privilege_name)){
			$privilege_clause="IN ('".join("', '", $privilege_name)."')";
			$privilege_binds=array();
		}else{
			$privilege_clause="=:privilege_name";
			$privilege_binds=array("privilege_name"=>$privilege_name);
		}
		// Собираем $pk_field
		$pk_field=($pk_field ? $pk_field : $table_system_name."_ID");
		// Собираем готовые кляузы
		$auth_tables="AUTH_ACL, AUTH_OBJECT_TYPE, AUTH_OBJECT_TYPE_TABLE, AUTH_PRIVILEGE, {$table_system_name}";
		$auth_clause="
				AUTH_ACL.OBJECT_ID={$table_system_name}.{$pk_field}
			".($roles_in!=="" ? "AND AUTH_ACL.AUTH_SYSTEM_ROLE_ID IN ({$roles_in})" : "" )."
			AND AUTH_ACL.AUTH_PRIVILEGE_ID=AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID
			AND AUTH_PRIVILEGE.SYSTEM_NAME {$privilege_clause}
			AND AUTH_PRIVILEGE.AUTH_OBJECT_TYPE_ID=AUTH_OBJECT_TYPE.AUTH_OBJECT_TYPE_ID
			AND AUTH_OBJECT_TYPE.SYSTEM_NAME=:system_name
			AND AUTH_ACL.AUTH_OBJECT_TYPE_TABLE_ID=AUTH_OBJECT_TYPE_TABLE.AUTH_OBJECT_TYPE_TABLE_ID
			AND AUTH_OBJECT_TYPE_TABLE.SYSTEM_NAME=:table_system_name
			AND AUTH_OBJECT_TYPE_TABLE.AUTH_OBJECT_TYPE_ID=AUTH_OBJECT_TYPE.AUTH_OBJECT_TYPE_ID
		";
		$auth_binds=array("system_name"=>$system_name, "table_system_name"=>$table_system_name)+$privilege_binds;
		return array($auth_tables, $auth_clause, $auth_binds);
	}

	/**
	 * Возвращает список идентификаторов пользователей, сгруппированных по ролям, список которых передается на вход метода
	 *
	 * Пользователь будет продублирован во всех ролях, которые ему назначены. Пользователи выбираются не только для переданных
	 * в метод ролей, но и для их детей, так как все роли-дети обладают такими же правами, что и их родители
	 *
	 * @param string $roles			список ролей, для которых надо найти пользователей
	 * @return array
	 * @todo устроена не очень оптимально - вначале вынимаются все роли, из них строится иерархия, а потом уже вычисляется результат. А вот с пользователями все наоборот - они вынимаются столько раз, сколько ролей было передано, так как показалось неправильным предвыбирать их заранее - их может быть очень много
	 */
	public static function get_users_by_roles($roles){
		$users=array();
		$roles_by_parent=lib::array_group(db::sql_select("SELECT * FROM AUTH_SYSTEM_ROLE"), "PARENT_ID");
		foreach($roles as $role){
			$children=self::get_role_children($role, $roles_by_parent);
			$children[$role]=$role;
			$grouped_users=lib::array_group(db::sql_select("SELECT AUTH_USER_SYSTEM_ROLE.AUTH_USER_ID FROM AUTH_USER_SYSTEM_ROLE WHERE AUTH_USER_SYSTEM_ROLE.AUTH_SYSTEM_ROLE_ID IN (".join(", ", $children).")"), "AUTH_USER_ID");
			$users[$role]=(count($grouped_users) ? array_combine(array_keys($grouped_users), array_keys($grouped_users)) : array());
		}
		return $users;
	}

	/**
	 * Вспомогательная рекурсивная функция для сборки детей для ролей для auth::get_users_by_roles()
	 *
	 * @param string $role				роль для которой собираются дети
	 * @param array $roles_by_parent	список всех ролей перегруппированнй по родителям
	 * @return array
	 */
	private static function get_role_children($role, $roles_by_parent){
		$children=array();
		if(is_array($roles_by_parent[$role])){
			foreach($roles_by_parent[$role] as $child){
				$children[$child["AUTH_SYSTEM_ROLE_ID"]]=$child["AUTH_SYSTEM_ROLE_ID"];
				$children+=self::get_role_children($child["AUTH_SYSTEM_ROLE_ID"], $roles_by_parent);
			}
		}
		return $children;
	}

	/**
	 * Возвращает bool, есть ли у указанных ролей указанное системное право
	 *
	 * @param string $roles_in			кляуза IN с ролями, формат как у {@link auth::$user_roles_in}
	 * @param string $privilege_name	системное название права
	 * @return bool
	 */
	public static function get_system_privilege($roles_in, $privilege_name){
		$rights=db::sql_select("
			SELECT COUNT(*) AS COUNTER FROM AUTH_ACL, AUTH_PRIVILEGE, AUTH_OBJECT_TYPE
			WHERE AUTH_ACL.AUTH_SYSTEM_ROLE_ID IN ({$roles_in})
				AND AUTH_ACL.OBJECT_ID=1
				AND AUTH_ACL.AUTH_PRIVILEGE_ID=AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID
				AND AUTH_PRIVILEGE.SYSTEM_NAME=:privilege_name
				AND AUTH_PRIVILEGE.AUTH_OBJECT_TYPE_ID=AUTH_OBJECT_TYPE.AUTH_OBJECT_TYPE_ID
				AND AUTH_OBJECT_TYPE.SYSTEM_NAME='system'
		", array("privilege_name"=>$privilege_name));
		return (bool)$rights[0]["COUNTER"];
	}

	/**
	 * Возвращает список идентификаторов администраторов (индексированный их же идентификаторами), которые обладают указанными правами
	 *
	 * @param string $privilege_name	системное название нужной привилегии
	 * @param string $aot_name			системное название типа объекта разделения доступа
	 * @param string $ext_clause		уточнение запроса (если нужно)
	 * @param string $ext_binds			переменные привязки для уточнения запроса (если нужно)
	 * @return array
	 */
	public static function get_admins($privilege_name, $aot_name, $ext_clause="", $ext_binds=array()){
		$admin_roles_info=db::sql_select("
			SELECT AUTH_SYSTEM_ROLE_ID
			FROM AUTH_ACL, AUTH_PRIVILEGE, AUTH_OBJECT_TYPE
			WHERE AUTH_ACL.AUTH_PRIVILEGE_ID=AUTH_PRIVILEGE.AUTH_PRIVILEGE_ID
				AND AUTH_PRIVILEGE.SYSTEM_NAME=:privilege_name
				AND AUTH_OBJECT_TYPE.AUTH_OBJECT_TYPE_ID=AUTH_PRIVILEGE.AUTH_OBJECT_TYPE_ID
				AND AUTH_OBJECT_TYPE.SYSTEM_NAME=:aot_name
				{$ext_clause}
		", array("privilege_name"=>$privilege_name, "aot_name"=>$aot_name)+$ext_binds);
		$admin_roles=array_keys(lib::array_group($admin_roles_info, "AUTH_SYSTEM_ROLE_ID"));
		$admins_by_roles=auth::get_users_by_roles($admin_roles);
		$admins=array();
		foreach($admins_by_roles as $abr){
			$admins+=$abr;
		}
		return $admins;
	}

	/**
	 * Возвращает список идентификаторов главных администраторов, индексированный этими же идентификаторами
	 *
	 * @return array
	 */
	public static function get_main_admins(){
		return self::get_admins("main_admin", "system");
	}

	/**
	 * Возвращает список идентификаторов администраторов сайтов (для указанного объекта), индексированный этими же идентификаторами
	 *
	 * В таблице дожно быть поле SITE_ID, по которому проверка и проводится (кроме случая с параметром $by_block)
	 *
	 * @param $obj			название объекта для которого собираются администраторы
	 * @param $id			идентификатор записи
	 * @param $by_block		собирать не для самого объекта, а для его главного блока
	 * @return array
	 */
	public static function get_site_admins($obj, $id, $by_block=false){
		$site_id=self::get_site_id($obj, $id, $by_block);
		return self::get_admins("access", "site", "AND AUTH_ACL.OBJECT_ID=:site_id", array("site_id"=>$site_id));
	}

	/**
	 * Проверяет, есть ли у текущего пользователя права администратора сайта на указанную запись
	 *
	 * В таблице дожно быть поле SITE_ID, по которому проверка и проводится (кроме случая с параметром $by_block)
	 *
	 * @param $obj			название объекта для которого проверяются права
	 * @param $id			идентификатор проверяемой записи
	 * @param $by_block		проверять не для самого объекта, а для его главного блока
	 * @return boolean
	 */
	public static function is_site_admin_for($obj, $id, $by_block=false){
		if(self::$auth_instance->sites_in){
			$site_id=self::get_site_id($obj, $id, $by_block);
		}
		return isset(self::$auth_instance->sites_ids[$site_id]);
	}

	/**
	 * Возвращает идентификатор сайта для указанного объекта
	 *
	 * В таблице дожно быть поле SITE_ID, по которому проверка и проводится (кроме случая с параметром $by_block)
	 *
	 * @param $obj			название объекта для которого проверяются права
	 * @param $id			идентификатор проверяемой записи
	 * @param $by_block		проверять не для самого объекта, а для его главного блока
	 * @return boolean
	 */
	public static function get_site_id($obj, $id, $by_block=false){
		if(!$by_block){
			$site_id=db::sql_select("SELECT SITE_ID FROM {$obj} WHERE {$obj}_ID=:id", array("id"=>$id));
		}else{
			$site_id=db::sql_select("
				SELECT INF_BLOCK.SITE_ID
				FROM INF_BLOCK, CONTENT_MAP
				WHERE CONTENT_MAP.CONTENT_ID=:id
					AND CONTENT_MAP.IS_MAIN=1
					AND CONTENT_MAP.INF_BLOCK_ID=INF_BLOCK.INF_BLOCK_ID
					AND INF_BLOCK.TE_OBJECT_ID=:te_object_id
			", array("id"=>$id, "te_object_id"=>object_name::$te_object_ids[$obj]["TE_OBJECT_ID"]));

		}
		return $site_id[0]["SITE_ID"];
	}
	
	/**
	* Регистрация события в журнале авторизации
	* @param $type - тип события
	*/
	
	public static function log_register_authorization ($type) {
		if (log::is_enabled('log_auth'))
			log::register('log_auth', $type);
	}
	
	/**
	* Регистрация события в журнале неуспешных авторизаций
	* @param $type - тип события
	* @param $login - введенный логин
	*/ 
	
	public static function log_register_auth_failed ($type, $login='') {
		if (!log::is_enabled('log_auth_failed')) return;
		
		if (!$login) 
			$login=$_REQUEST['login'];
			
		$user = db::sql_select("SELECT * FROM AUTH_USER WHERE LOGIN=:login", array("login"=>$login));
		
		$log_info=array(
			'login' => $login,
			'user' => (sizeof($user)>0)?$user[0]:'No such user in our database'
		);
		
		if (sizeof($user)) {
			$log_info['BAD_PASSWORD_MAX_COUNT'] = self::get_bad_password_max_count_for_user($user[0]);
			if ($type == 'password_failed_login_blocked') 
				$log_info['user']['BAD_PASSWORD_COUNT'] = $log_info['BAD_PASSWORD_MAX_COUNT'];
		}

		
		log::register('log_auth_failed', $type, $log_info);
	}
	
	/**
	* Получает максимальное кол-во неудачных входов для пользователя
	* @param array $user Данные пользователя из БД
	* @return int
	*/
	
	private static function get_bad_password_max_count_for_user ($user) {
		if (!isset($user['BAD_PASSWORD_MAX_COUNT'])) {
			return params::$params['bad_password_count_for_lock']['value'];
		}
			
		return $user['BAD_PASSWORD_MAX_COUNT'];
	}
	
	/**
	 * Принудительное удаление связанных записей из таблицы AUTH_ACL
	 */
	public static function clear_AUTH_ACL( $object_id, $obj )
	{
		db::sql_query( 'delete from AUTH_ACL where OBJECT_ID = :object_id and AUTH_OBJECT_TYPE_TABLE_ID in
				( select AUTH_OBJECT_TYPE_TABLE_ID from AUTH_OBJECT_TYPE_TABLE where SYSTEM_NAME = :obj )',
			array( 'object_id' => $object_id, 'obj' => $obj ) );
	}
}
?>
