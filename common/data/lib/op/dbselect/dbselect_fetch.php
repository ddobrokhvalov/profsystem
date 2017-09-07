<?php
	/**
	 
	 */
	class dbselect_fetch extends dbselect_sql {
		/**
	
		 */
		//abstract function Next();
		
		/**
		 * e.c. $Items=$Items->RightJoin($Rate,'id','id','Rate');
		 */
		function RightJoin($sel, $ForeingKey, $JoinedKey, $As){
			$sel->Join($this, $JoinedKey,$ForeingKey,$As,'LEFT',true);
			return $sel;
		}
		
		/**
		 * @return	  array
		 */
		function AssocResult(&$row, &$Offset=0, $key=null){		
			$count=count($this->Select);
			$ret=array();
			
			for ($i=0; $i<$count;$i++){
				$key=(false==$pos=strrpos($this->Select[$i],' '))?$this->Select[$i]:substr($this->Select[$i],$pos+1);
				$ret[$key]=($this->serialized_fields&&in_array($key,$this->serialized_fields))?unserialize($row[$Offset]):$row[$Offset];
				$Offset++;
			}
		
			if ($count=count($this->Join)){
				foreach (array_keys($this->Join) as $i){
					list($Join, $ForeingKey, $JoinedKey, $As, $Type, $Class)=$this->Join[$i];				
					$key=($As)?$As:$Join->Table;
					if ($Class){
						$res= new $Class($Join->AssocResult($row,$Offset,$ret[$ForeingKey]));
					}
					else {
						$res=$Join->AssocResult($row,$Offset,$ret[$ForeingKey]);
					}
					if ($this->Join[$i][5]){
						$res[$key]=$ret;
						$ret=$res;				
					}
					else {
						$ret[$key]=$res;
					}
				}
			}
			
			return $ret;
		}
		/**
		 * @return array
		 */
		function GroupResult($GroupBy='id', $GroupOnce=true, $Field=null){
			
			if ($GroupBy){
				if (is_array($GroupBy)&&($count=count($GroupBy))){
					$Group=array();					
					for ($i=0;$i<$count;$i++){
						$Group[]=explode('.',$GroupBy[$i]);						
					}					
				}
				else $Group=array(explode('.',$GroupBy));
			}
			else $Group=array();
			
			$ret=array();
			$count=count($Group);
			
			if (!$count) $GroupOnce=false;			
			foreach ($this as $obj){
				$point=&$ret;
				
				for ($i=0; $i<$count; $i++){
					unset($key);
					$key=$obj;
					if ($count2=count($Group[$i])){						
						for ($j=0;$j<$count2;$j++){
							$key=$key[$Group[$i][$j]];							
						}
					}
					if (!isset($point[$key])) $point[$key]=array();
					$point=&$point[$key];
				}
				if ($GroupOnce) $point=(!$Field)?$obj:$obj[$Field];
				else $point[count($point)]=(!$Field)?$obj:$obj[$Field];				
			}
			
			return $ret;
		}
		function ToArray($GroupBy=null, $GroupOnce=false, $Field=null){
			return self::GroupResult($GroupBy, $GroupOnce, $Field);
		}

		function ToAssoc(){
			return self::GroupResult($this->primary_key, true);
		}
	}
?>