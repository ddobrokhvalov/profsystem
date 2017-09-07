<?	
class region_country_areas {
	
	var $name;
	var $value;
	
	function __construct($name, $value){
		$this->name=$name;
		$this->value=$value;
	}
	
	var $error=null;
	
	function throwError(){
		$this->error=$error;
		return false;
	}	
	
	function isValid(){
		return (!$this->error);
	}
	/**
	 * В процессе:
	 * @return boolean
	 */
	function inProgress(){
		return $this->inProgress;
	}
	
	var $isProgress=false;
	
	function validateData($q){
		//Выбранная страна:
		if (!$country= region::loadByID($q['country'])){
			return $this->throwError('invalidCountry');
		}
		$this->value=array($country->getID());
		//Сменили страну:
		if ($country->getID()!=$q['current']){
			$this->inProgress=true;			
			return false;
		}
		if ($q['area']){
			foreach ($q['area'] as $id){
				if ($id=str::natural($id)) $this->value[]=$id;
			}
		}
		//Проверяем:
		$sel= region::select();
		$sel->Where('id',eq,$this->value);
		if ($sel->selectCount()!=count($this->value)){
			$sel->Select('id');
			$this->value=array();
			foreach ($sel as $obj) $this->value[]=$obj['id'];
			return $this->throwError('invalidRegions');
		}
		
		return true;
	}
	
	function commitData(){
		return $this->value;
	}
	/**
	 * Страна по умолчанию
	 * 
	 * @param $value
	 * @return unknown_type
	 */
	function setDefaultValue($value){
		if (count($this->value)) return;
		if (is_numeric($value)) return $this->value=array($value);
		elseif ($r=region::loadByUrl($value)) $this->value=array($r->getID());		
	}
	/**
	 * Вывести форму:
	 * @return
	 */
	function printForm(){
		
		//return;
		$sel= region::select('type','country');
		$sel->OrderBy('Title','ASC');
		$currentCountry=null;
		?>
		<script type="text/javascript" src="/common/js/app/region_multiinput.js"></script>
		<script type="text/javascript">
			var region_<?=$this->name;?>= new region_multiinput('<?=$this->name;?>');
		</script>
		<div class="search">
		<select name="<?=$this->name;?>[country]" id="<?=$this->name;?>_country">
		<option></option>
		<?
		foreach ($sel as $country){
			if (in_array($country['id'], $this->value)){
				$currentCountry=$country;
				?><option value="<?=$country['id'];?>" selected><?=$country['Title'];?></option><?
			}
			else {
				?><option value="<?=$country['id'];?>"><?=$country['Title'];?></option><?
			}
		}
		?></select>		
		<input type="text" id="<?=$this->name;?>_search"/>
		<input type="hidden" name="<?=$this->name;?>[current]" id="<?=$this->name;?>_current" value="<?=$currentCountry['id'];?>"/>
		</div>		
		<?
			if (!$currentCountry) return;			
			$tree= region::select_tree('RootID', $currentCountry['id']);
			$regions=region::select();
			$regions->OrderBy('Title','ASC');
			$regions->Join($tree, 'id', 'id','tree','INNER');		
		?>				
		<ul id="<?=$this->name;?>_areas" class="areas">
		<?
		foreach ($regions as $obj){
		?>
		<li><input type="checkbox" name="<?=$this->name;?>[area][<?=$obj['id'];?>]" id="<?=$this->name;?>_area_<?=$obj['id'];?>" value="<?=$obj['id'];?>"<?=(in_array($obj['id'],$this->value))?'checked':'';?>/><label for="<?=$this->name;?>_area_<?=$obj['id'];?>"><?=$obj['Title'];?></label></li>
		<?
		}
		?>
		</ul>
		<?
	}
	
	function __toString(){		
		ob_start();
		$this->printForm();
		return ob_get_clean();
	}
}
?>