<?
	class region extends DataStore implements Iterator {
		const City='City';
		const Country='Country';
		const Region='Region';
		/**		 
		 *
		 * @return dbselect
		 */
		static function select(){
			$a= func_get_args();
			return dbselect::factory(array('id','Url','Type','Title','lat','lng'), 'regions', null, $a);
		}
		/**		 
		 * 
		 * @return dbselect
		 */
		static function select_dict(){
			$a= func_get_args();
			return dbselect::factory(array('id','Title'),'regions_dict',null,$a);
		}
		/**		 
		 * 
		 * @return NestedSets
		 */
		static function select_tree(){		
			$a=func_get_args();
			return dbselect::where_command(new NestedSets('regions_tree'), $a);
		}
		/**
		 * 
		 *
		 * @return region
		 */
		static function loadByID($id){		
			$sel=self::select('id',$id);
			$sel->Join(self::select_tree(),'id','id','tree','LEFT');
			
			return $ret=$sel->SelectObject('region');				
		}
		/**		 
		 * 
		 * @return dbselect
		 */
		function selectChilds(){
			$sel= self::select_tree();
			$sel->Where('RootID',eq,$this->getID());
			if (!$this->getID()) $sel->Where('id',noteq,0);
			$ret=self::select();
			$ret->OrderBy('Title','ASC');
			$sel= $ret->RightJoin($sel, 'id', 'id', 'tree');
			
			return $sel;
		}
		/**		 
		 */
		function selectPathAndChilds(){
			
		}
		/**		 
		 *
		 * @param string $URL
		 * @return region
		 */
		static function loadByUrl($URL){
			if (!$URL=str::StrongString($URL)) throw new Exception('Bad request',401);
			return self::select('Url',$URL)->SelectObject('region');
		}
		/**		 
		 * 
		 * @return 
		 */
		function loadPath(){
			if (isset($this->Info['Path'])) return;
			$sel= $this->select_tree();
			$sel->Where('id', exist);
			$sel->selectPath($this->getObject('tree'));
			$sel= self::select()->RightJoin($sel,'id','id','tree');
			$this->Info['Path']=array();
			foreach ($sel as $o) $this->Info['Path'][]=$o;
			
		}
		/**		 
		 * 
		 * @return region
		 */
		function getParent($Type){
			if ($this->getInfo('Type')==$Type) return $this;
			$this->loadPath();
			
			foreach ($this->Info['Path'] as $Point) {
				if ($Point['Type']==$Type) return new region($Point);
			}
			return null;
			
		}
		/**		 
		 * 
		 * @return region
		 */
		function getIndex(){	
			$this->loadPath();		
			if ($this->getInfo('Type')=='City') return $this;
			
			foreach ($this->getInfo('Path') as $Point){
				if ($Point['Type']=='City') return new region($Point);
			}
			return null;
		}
		
		function _getInfo($path){
			if (isset($path[0])&&$path[0]=='tree'&&!isset($this->Info['tree'])){							
				$this->Info['tree']=region::select_tree('id',$this->getID())->SelectObject();				
			}
			if (isset($path[0])&&$path[0]=='Path'){
				$this->loadPath();
				//str::print_r($path);
				if (count($path)==1&&is_numeric($path[0])){
					if (isset($this->Info['Path'][$path[0]])){
						return new region($this->Info['Path'][$path[0]]);
					}
					else return null;
				}
			}
			if ($path[0]=='childs'){
				return $this->selectChilds();
			}
			return parent::_getInfo($path);
		}
		/**
		 * 
		 * @return unknown
		 */
		function __toString(){
			$this->loadPath();
			$ret='';
			if (count($this->Info['Path'])){
				foreach ($this->Info['Path'] as $variable) {
					$ret.=$variable['Title'].', ';
				}
			}
			return $ret.$this->getInfo('Title');
		}
		/**
		 * 
		 * 
		 * @return int
		 */
		function getIndexID(){
			if ($Index=$this->getIndex()) return $Index->getID();
			else return 0;
		}
		/**
		 * 
		 * @return dbselect
		 */
		function InRegion($sel, $RegionKey='region',$IndexRegionKey='city', $RightJoin=false){
			/*@var $sel dbselect*/
			if ($IndexRegionKey&&$this->getIndexID()==$this->getID()){
				$sel->Where($IndexRegionKey,eq,$this->getID());
			}
			else {
				$tree=$this->select_tree();
				$tree->SelectBranch($this->getObject('tree'));
				if ($RightJoin){
					$sel=$sel->RightJoin($tree, $RegionKey, 'id','region');
				}
				else {
					$sel->Join($tree, $RegionKey, 'id', 'region','INNER');
				}
			}
			return $sel;
		}
		/**
		 * 
		 */
		function selectBranch(){
			$ret=self::select_tree();
			/*@var $ret nestedSets*/
			return $ret->selectBranch($this->getObject('tree'));
		}
		
		static $fromStringStemming=array(			
			'/(?<!,)\s+(�\.\s+)/'=>', ',// �., ��., ���, ��������, �����- �������� ��� �����������
			'/(?<!,)\s+(���\s+)/'=>', ',
			'/(?<!,)\s*(��\.\s+)/'=>', ',
			'/(?<!,)\s*(��������\s+)/'=>', ',
			'/(?<!,)\s*(�����\s+)/'=>', ',
			'/\b(��\.\s+)/'=>'',
			'/\b(��\.\s+)/'=>'',
			//'/(?<!,)\s+([�-�])/'=>', \1',
		);
		/**
		
		 * 
		 * @var string	$String
		 * @var region	$Root	
		 * @var boolean	$CreateIfNotExists
		 * 
		 * @return region
		 */
		static function fromString($String, $Root=null, $CreateIfNotExists=true){
			foreach (self::$fromStringStemming as $find=>$replace) {
				$String=preg_replace($find, $replace, $String);
			}
			$p= explode(',',$String);
			
			if (!$count=count($p)) return null;
			$Path=array();
			for ($i=0; $i<$count; $i++){
				if (trim($p[$i])) $Path[$i]=trim($p[$i]);
			}
			if (!$count=count($Path)) return null;
			
			$TitleSearch=self::select_dict('Title',$Path);
			$TitleSearch->Join(self::select_tree(),'id','id','tree','LEFT');
			$Found=$TitleSearch->ToArray('Title');
			$NewBranch=false;
			for ($i=0; $i<$count; $i++){
				$Title=$Path[$i];
				
				if (isset($Found[$Title])&&($count2=count($Found[$Title]))){				
					if ($Root&&!$NewBranch){
						$cur=null;
						
						for ($j=0;$j<$count;$j++){
						
							if ($Root->getInfo('tree','LeftKey')>$Found[$Title][$j]['tree']['LeftKey']||$Root->getInfo('tree','RightKey')<$Found[$Title][$j]['tree']['LeftKey']) unset($Found[$Title][$j]);
						}						
						
						if (count($Found[$Title])>1) throw new region_to_many_variants_exception($Root, $Found[$Title]);
						elseif (count($Found[$Title])==0){
						
							if ($CreateIfNotExists) $Root=self::createRegion($Root, $Title);
							else return null;
							
							$NewBranch=true;
						}
						else {
							reset($Found[$Title]);							
							$Root=new region(current($Found[$Title]));							
						}
					}
					
					elseif(count($Found)>1) throw new region_to_many_variants_exception(0, $Found[$Title]);
					
					else $Root= new region($Found[$Title]);					
				}
				else{
					if ($CreateIfNotExists) self::createRegion($Root, $Title);
					else return null;
					$NewBranch=true;
				}
			}
			
			return $Root->Normalize();
		}
		
		function Normalize(){
			str::print_r($this->Info);
			if ($this->getInfo('Url')) return $this;			
			else return region::loadByID($this->getID());
		}
		
		static $Types=array('', 'City', 'Country', 'Region', 'Metro', 'Street', 'House');
		/**
		
		 * @var region	$Root
		 * @var string	$Title
		 */
		static function createRegion($Root, $Title, $Type=''){
			 /*@var $Root region*/
			 $m=  new url_manager('regions');
			 $URL=$m->getURL($Title);
			 
			 $id= kernel::getNextID();
			 if (!in_array($Type, self::$Types)) throw new Exception('Unsupported type');
			 			 
			 $Info=array('id'=>$id,'Title'=>$Title,'Url'=>$URL,'Type'=>$Type);
			 self::select()->Insert($Info);
			 self::select_dict()->Insert(array('id'=>$id,'Title'=>$Title));
			 
			 $Info['tree']=self::select_tree()->AddNode($Root->getObject('tree'), $id);
			  
			 return new region($Info);
		}
		/**
		 
		 * 
		 * @return array()
		 */
		function Delete(){
			$tree= self::select_tree();
			/*@var $tree nestedsets*/
			$tree->selectBranch($this->getObject('tree'));
			$id=array();
			foreach ($tree as $el) $id[]=$el['id'];
			
			self::select_dict('id',$id)->Delete();
			
			self::select('id',$id)->Delete();
			
			self::select_tree()->Remove($this->getID());
			
			return $id;			
		}
		/**
		 
		 * 
		 * @var string $Alias �����
		 */
		function addAlias($Alias){
			$sel= self::select_dict('id',$this->getID(),'Title',$Alias);
			/*@var $sel dbSelect*/
			if ($sel->SelectObject()) throw new Exception('Alias just exists');
			$sel->Insert(array('id'=>$this->getID(),'Title'=>$Alias));
			return true;
		}
		/**
		 
		 * 
		 * @var string $Title
		 */
		function setTitle($Title){
			self::select('id',$this->getID())->Update(array('Title'=>$Title));
			return true;			
		}
		/**		 
		 * mc-donalds-smolenskaya-ploshad-moskva
		 * mc-donalds-ploshad-revolucii-nizhniy-novgorod
		 * mc-donalds-baker-street-london
		 * mc-donalds-kirov
		 * 
		 */
		function getURLPostfix(){
			$Path= $this->getInfo('Path');
			$break=false;		
			if ($count=count($Path)){	
				for ($i=0; $i<count($Path); $i++){
					if ($Path[$i]['Type']=='City'){
						return $Path[$i]['Url'].'-'.(($i+1<$count)?$Path[$i+1]['Url']:$this->getInfo('Url'));
					}				
				}
			}
			return $this->getInfo('Url');
		}
		var $res=null;
		/**
		 * 
		 */
		public function rewind() {
			if (!$this->res){
				foreach ($this->getInfo('Path') as $variable) {
					$this->res[$variable['id']]=new DataStore($variable);
				}
				
				$this->res[]=$this;    
			}
			reset($this->res);
		}

		public function current() {
			return current($this->res);   
		}
	
		public function key() {
    		return key($this->res);    
  		}

  		public function next() {
    		return next($this->res);
  		}

		public function valid() {
    		return $this->current();     
  		}
	}
	
	class region_to_many_variants_exception extends Exception {
		/**
		 * 
		 */
		var $Root=null;
		/**
		 * 
		 */
		var $Vars=null;
		function __construct($RootID=0, $Vars){
			if ($RootID) $this->Root=region::loadByID($RootID);
			$this->Vars=$Vars;
			parent::__construct('To many variants',403);
		}
		/**
		 *
		 */
		function getVariants(){
			//str::print_r($this->Vars);
			foreach ($this->Vars as $var) {
				$ret[]= region::loadByID($var['id']);
			}			
			return $ret;
		}		
	}
	
?>