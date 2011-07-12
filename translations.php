<?php
class Translations{
	const TRANSLATIONS_TABLE = 'translation_strings';  //Translation strings table (VARCHAR or such)
	const LONG_TRANSLATIONS_TABLE = 'translation_strings_long';  //Long translation strings table (TEXT or such)
	
	private static $db;
	private static $dbtype;
	private static $data = array();
	
	const DBTYPE_PDO = 1;
	const DBTYPE_MYSQLI = 2;
	
	public static function get($name, $long = false){
		$table = (!(boolean)$long ? self::TRANSLATIONS_TABLE : self::LONG_TRANSLATIONS_TABLE);
		
		if(!isset(self::$data[$name][$table])){
			$rows = self::query("SELECT data FROM $table WHERE name = ?", $name);
			
			if(!count($rows)) return false;
			self::$data[$name][$table] = new TranslationResult($rows[0]['data']);
		}
		
		return self::$data[$name][$table];
	}
	
	public static function all(){
		$rows = self::query(
			'SELECT ' . self::TRANSLATIONS_TABLE . '.name, ' .
			self::TRANSLATIONS_TABLE . '.data AS short, ' .
			self::LONG_TRANSLATIONS_TABLE . '.data AS long ' .
			'FROM' . self::TRANSLATIONS_TABLE . ' ' .
			'INNER JOIN ' . self::LONG_TRANSLATIONS_TABLE . ' ' .
			'ON ' . self::LONG_TRANSLATIONS_TABLE . '.name = ' .
			self::TRANSLATIONS_TABLE . '.name'
		);
		
		foreach($rows as $row)
			self::$data[$row['name']] = array(
				self::TRANSLATIONS_TABLE => new TranslationResult($row['short']),
				self::LONG_TRANSLATIONS_TABLE => new TranslationResult($row['long'])
			);
		
		return true;
	}
	
	public static function all_short(){
		$rows = self::query(
			'SELECT ' . self::TRANSLATIONS_TABLE . '.name, ' .
			self::TRANSLATIONS_TABLE . '.data ' .
			'FROM ' . self::TRANSLATIONS_TABLE
		);
		
		foreach($rows as $row)
			self::$data[$row['name']][self::TRANSLATIONS_TABLE] = new TranslationResult($row['data']);
		
		return true;
	}
	
	public static function all_long(){
		$rows = self::query(
			'SELECT ' . self::LONG_TRANSLATIONS_TABLE . '.name, ' .
			self::LONG_TRANSLATIONS_TABLE . '.data ' .
			'FROM ' . self::LONG_TRANSLATIONS_TABLE
		);
		
		foreach($rows as $row)
			self::$data[$row['name']][self::LONG_TRANSLATIONS_TABLE] = new TranslationResult($row['data']);
		
		return true;
	}
	
	public static function db($set = false){
		if($set instanceof PDO){
			self::$db = $set;
			self::$dbtype = self::DBTYPE_PDO;
			return true;
		}
		if($set instanceof MySQLi){
			self::$db = $set;
			self::$dbtype = self::DBTYPE_MYSQLI;
			return true;
		}
		return false;
	}
	
	private static function query(){  //XXX: It just seems like this could be done better
		$args = func_get_args();
		$ret = NULL;
		switch(self::$dbtype){
			case self::DBTYPE_PDO:
				$stmt = self::$db->prepare(array_shift($args));
				$stmt->execute($args);
				$ret = $stmt->fetchAll();
				$stmt->closeCursor();
			break;
			case self::DBTYPE_MYSQLI:
				$stmt = self::$db->prepare(array_shift($args));
				$argarray = array(str_repeat('s', count($args)));
				foreach(array_keys($args) as $num)
					$argarray[] = &$args[$num];
				call_user_func_array(
					array($stmt, 'bind_param'),
					$argarray
				);
				$data = $stmt->get_result();
				$stmt->close();
				$ret = array();
				while($ret[] = $data->fetch_assoc());
				array_pop($ret);
		}
		return $ret;
	}
}

class TranslationResult{
	private $data;
	
	public function __construct($data){
		$this->data = $data;
	}
	
	public function p(){
		echo $this->data;
		return $this->data;
	}
	
	public function up(){
		$this->data = strtoupper($this->data);
		return $this;
	}
	
	public function low(){
		$this->data = strtolower($this->data);
		return $this;
	}
	
	public function cap(){
		$this->data = ucwords($this->data);
		return $this;
	}
	
	public function __toString(){
		return $this->data;
	}
}