<?php
/**
 * @author xuqiang76@163.com
 * @final 20180518
 * 这个类的作用是从数据库里读出表结构，然后生成一个bean类，并将其属性与类一一映射。
 * 具体生成的内容包括：
 * 1. 私有变量
 * 2. 表字段与属性的映射关系
 * 3. 表字段的信息，用于server的验证
 */

error_reporting(7);
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 'on');

class TableClassGenerator {
	const DEFAULT_DIR = './classes';
	const DEFAULT_INDENT = 4;
	const DEFAULT_MIN = 1;
	private $excludedProperties;
	private $database;
	private $file;
	private $givenTables;
	private $parentClass;
	private $classPre;
	private $keyPre;
	private $multi_arr;

	public function __construct($config) {
		if (!isset($config) || empty($config) || !is_array($config)) {
			die('Invalid config: ' . print_r($config, true));
		}

		$this->database = $config['database'];
		global $conn;
		$conn = isset($config['password'])
		? @mysqli_connect($config['host'], $config['user'], $config['password'])
		: @mysqli_connect($config['host'], $config['user']);
		if (!isset($conn)) {
			die('Failed to connect.' . mysqli_error());
		}
		mysqli_query($conn, "set names 'utf8'");
		mb_internal_encoding('utf-8');

		$this->classPre = $config['class_pre'];
		$this->keyPre = $config['key_pre'];

		$this->givenTables = $config['tables'];
		if (isset($this->givenTables)
			&& (!is_array($this->givenTables)
			)) {
			echo ("Tables(" . json_encode($this->givenTables) . ") in config is not an array.");
		}

		$this->parentClass = $config['parentClass'];

		if ($config['excludedProperties']) {
			$this->excludedProperties = $config['excludedProperties'];
			if (!is_array($this->excludedProperties)
				|| empty($this->excludedProperties)) {
				die('excludedProperties should be an array and shoudnot be empty.');
			}
		}

		if ($config['multi_arr']) {
			$this->multi_arr = $config['multi_arr'];
			if (!is_array($this->multi_arr)
				|| empty($this->multi_arr)) {
				die('multi_arr should be an array and shoudnot be empty.');
			}
		}

		if (!file_exists(self::DEFAULT_DIR)) {
			mkdir(self::DEFAULT_DIR);
		}
	}

	public function __destroy() {
		mysql_close();
	}

	public function generateClasses() {
		$allTables = $this->getTables();

		$tables = $this->givenTables
		? $this->givenTables
		: $allTables;

		if (empty($tables)) {
			die("Empty given tables");
		}

		foreach ($tables as $table) {
			$index = array_search($table, $allTables);
			if (!is_int($index)) {
				echo "Table($table) not found in database({$this->database}).\n";
				continue;
			}

			$this->generateClassForTable($table);
			$this->generateClassForTableFactory($table);
			$this->generateClassForTableListFactory($table);
			$this->generateClassForTableMultiFactory($table);
		}
	}

	private function generateClassForTable($table) {
		$class = ucfirst($this->transform($table));
		$second_key = 'uid';
		if ($this->multi_arr[$table]) {
			$second_key = $this->multi_arr[$table];
		}

		$primary_key = '';

		$fileName = self::DEFAULT_DIR . "/$class.php";

		$columns = $this->getTableColumns($table);
		if (!isset($columns) || empty($columns)) {
			echo "The table($table) doesn't have columns.\n";
			return;
		}

		$keys = array_keys($columns);
		foreach ($keys as $key) {
			if ($columns[$key]['is_primary_key'] || $columns[$key]['is_auto_increment']) {
				$primary_key = $key;
			}
		}
		if (!$primary_key) {
			$primary_key = 'id';
		}

		$this->file = fopen($fileName, 'w');

		if (!isset($this->file)) {
			die("Failed to open file: $fileName");
		}

		echo "Generating class for table: $table.\n";

		$this->writeToFile("<?php");
		$this->writeToFile("namespace bigcat\model;\n");

		$this->writeToFile("use bigcat\inc\BaseObject;");
		$this->writeToFile("use bigcat\inc\BaseFunction;");

		if ($this->parentClass) {
			$this->writeToFile("class $class extends {$this->parentClass}\n{");
		} else {
			$this->writeToFile("class $class \n{");
		}

		$this->generateConst($table);
		//		$this->generateColumnPropMapping($columns);
		//		$this->generateValidateConfig($table, $columns);
		$this->generateProperties($columns);
		$this->generateUpdate($columns, $table);
		$this->generateInsert($columns, $table);
		$this->generateDelete($columns, $table);
		$this->generateBeforWrite();
		//		$this->generateGetters($columns);
		//		$this->generateSetters($columns);
		$this->writeToFile("}");
		$this->writeNewLine();

		fclose($this->file);
		echo "Class($class) was created in the file($fileName).\n\n";
	}

	private function generateClassForTableFactory($table) {
		$class = ucfirst($this->transform($table));
		$class_factory = $class . 'Factory';
		$class_factory_parent = 'Factory';

		$second_key = 'uid';
		if ($this->multi_arr[$table]) {
			$second_key = $this->multi_arr[$table];
		}

		$primary_key = '';

		$fileName = self::DEFAULT_DIR . "/$class_factory.php";
		$columns = $this->getTableColumns($table);
		if (!isset($columns) || empty($columns)) {
			echo "The table($table) doesn't have columns.\n";
			return;
		}

		$keys = array_keys($columns);
		foreach ($keys as $key) {
			if ($columns[$key]['is_primary_key'] || $columns[$key]['is_auto_increment']) {
				$primary_key = $key;
			}
		}
		if (!$primary_key) {
			$primary_key = 'id';
		}

		$this->file = fopen($fileName, 'w');

		if (!isset($this->file)) {
			die("Failed to open file: $fileName");
		}

		echo "Generating class for table: $table.\n";

		$this->writeToFile("<?php");
		$this->writeToFile("namespace bigcat\model;\n");

		$this->writeToFile("use bigcat\inc\Factory;");
		$this->writeToFile("use bigcat\inc\BaseFunction;");

		$this->writeToFile("class $class_factory extends {$class_factory_parent}\n{");
		$this->writeToFile("const objkey = '" . $this->keyPre . "_" . $table . "_multi_';", 1);
		$this->writeToFile("private \$sql;", 1);

		$this->generateConstruct($columns, $table, $primary_key);
		$this->generateRetrive($columns, $class);

		$this->writeToFile("}");
		$this->writeNewLine();

		fclose($this->file);
		echo "Class($class) was created in the file($fileName).\n\n";
	}

	private function generateClassForTableListFactory($table) {
		$class = ucfirst($this->transform($table));
		$class_list_factory = $class . 'ListFactory';
		$class_list_factory_parent = 'ListFactory';

		$second_key = 'uid';
		if ($this->multi_arr[$table]) {
			$second_key = $this->multi_arr[$table];
		}

		$primary_key = '';

		$fileName = self::DEFAULT_DIR . "/$class_list_factory.php";
		$columns = $this->getTableColumns($table);
		if (!isset($columns) || empty($columns)) {
			echo "The table($table) doesn't have columns.\n";
			return;
		}

		$keys = array_keys($columns);
		foreach ($keys as $key) {
			if ($columns[$key]['is_primary_key'] || $columns[$key]['is_auto_increment']) {
				$primary_key = $key;
			}
		}
		if (!$primary_key) {
			$primary_key = 'id';
		}

		$this->file = fopen($fileName, 'w');

		if (!isset($this->file)) {
			die("Failed to open file: $fileName");
		}

		echo "Generating class for table: $table.\n";

		$this->writeToFile("<?php");

		$this->writeToFile("namespace bigcat\model;\n");

		$this->writeToFile("use bigcat\inc\ListFactory;");

		$this->writeToFile("class $class_list_factory extends {$class_list_factory_parent}\n{");

		$this->writeToFile("public \$key = '" . $this->keyPre . "_" . $table . "_list_';", 1);
		$this->writeToFile("public function __construct(\$dbobj, \$" . $second_key . " = null, \$id_multi_str='') ", 1);
		$this->writeToFile("{", 1);
		$this->writeToFile("//\$id_multi_str 是用,分隔的字符串", 2);
		$this->writeToFile("if(\$" . $second_key . ") ", 2);
		$this->writeToFile("{", 2);
		$this->writeToFile("\$this->key = \$this->key.\$$second_key;", 3);
		if ($columns[$second_key]['type'] == 'int') {
			$this->writeToFile("\$this->sql = \"select `$primary_key` from `$table` where $second_key=\".intval(\$$second_key).\"\";", 3);
		} else if ($columns[$second_key]['type'] == 'string') {
			$this->writeToFile("\$this->sql = \"select `$primary_key` from `$table` where $second_key='\".BaseFunction::my_addslashes(\$$second_key).\"'\";", 3);
		} else if ($columns[$second_key]['type'] == 'real') {
			$this->writeToFile("\$this->sql = \"select `$primary_key` from `$table` where $second_key='\".(\$$second_key).\"'\";", 3);
		}
		$this->writeToFile("parent::__construct(\$dbobj, \$this->key);", 3);
		$this->writeToFile("return true;", 3);
		$this->writeToFile("}", 2);
		$this->writeToFile("elseif (\$id_multi_str) ", 2);
		$this->writeToFile("{", 2);
		$this->writeToFile("\$this->key = \$this->key.md5(\$id_multi_str);", 3);
		$this->writeToFile("parent::__construct(\$dbobj, \$this->key, null, \$id_multi_str);", 3);
		$this->writeToFile("return true;", 3);
		$this->writeToFile("}", 2);
		$this->writeToFile("return false;", 2);
		$this->writeToFile("}", 1);

		$this->writeToFile("}");

		$this->writeNewLine();

		fclose($this->file);
		echo "Class($class) was created in the file($fileName).\n\n";
	}

	private function generateClassForTableMultiFactory($table) {
		$class = ucfirst($this->transform($table));
		$class_multi_factory = $class . 'MultiFactory';
		$class_multi_factory_parent = 'MutiStoreFactory';

		$second_key = 'uid';
		if ($this->multi_arr[$table]) {
			$second_key = $this->multi_arr[$table];
		}

		$primary_key = '';

		$fileName = self::DEFAULT_DIR . "/$class_multi_factory.php";
		$columns = $this->getTableColumns($table);
		if (!isset($columns) || empty($columns)) {
			echo "The table($table) doesn't have columns.\n";
			return;
		}

		$keys = array_keys($columns);
		foreach ($keys as $key) {
			if ($columns[$key]['is_primary_key'] || $columns[$key]['is_auto_increment']) {
				$primary_key = $key;
			}
		}
		if (!$primary_key) {
			$primary_key = 'id';
		}

		$this->file = fopen($fileName, 'w');

		if (!isset($this->file)) {
			die("Failed to open file: $fileName");
		}

		echo "Generating class for table: $table.\n";

		$this->writeToFile("<?php");
		$this->writeToFile("namespace bigcat\model;\n");

		$this->writeToFile("use bigcat\inc\MutiStoreFactory;");
		$this->writeToFile("use bigcat\inc\BaseFunction;");

		$this->writeToFile("class $class_multi_factory extends {$class_multi_factory_parent}\n{");
		$this->writeToFile("public \$key = '" . $this->keyPre . "_" . $table . "_multi_';", 1);
		$this->writeToFile("private \$sql;", 1);
		$this->writeNewLine();
		$this->generateMultiConstruct($columns, $table, $primary_key);
		$this->generateMultiRetrive($columns, $class, $primary_key);

		$this->writeToFile("}");

		$this->writeNewLine();
		$this->writeNewLine();

		fclose($this->file);
		echo "Class($class) was created in the file($fileName).\n\n";
	}

	private function generateColumnPropMapping($columns) {
		$this->writeToFile('private static $_colPropMapping = array(', 1);

		foreach ($columns as $key => $value) {
			$value;
			$prop = $this->transform($key);
			$this->writeToFile("'$key' => '$prop',", 2);
		}

		$this->writeToFile(');', 1);
		$this->writeNewLine();
	}

	private function generateConst($table) {
		$this->writeToFile("const TABLE_NAME = '$table';", 1);
		$this->writeNewLine();
	}

	private function generateGetters($columns) {
		foreach ($columns as $key => $value) {
			$value;
			$prop = $this->transform($key);
			if ($this->shouldExcludeProp($prop)) {
				continue;
			}

			$method = 'get' . ucfirst($prop);
			$this->writeToFile("public function $method() ", 1);
			$this->writeToFile("{", 1);
			$this->writeToFile('return $' . "this->$prop;", 2);
			$this->writeToFile("}", 1);
			$this->writeNewLine();
		}
	}

	private function generateProperties($columns) {
		$keys = array_keys($columns);
		$j = 0;
		foreach ($keys as $key) {
			if ($j % 5 == 0 && $j != 0) {
				$this->writeNewLine();
			}
			$j++;
			$val = '';
			if ($columns[$key]['type'] == 'int') {
				$val = " = 0";
			} else if ($columns[$key]['type'] == 'string') {
				$val = " = ''";
			} else if ($columns[$key]['type'] == 'real') {
				$val = "  0.0";
			}
			if ($columns[$key]['is_primary_key'] || $columns[$key]['is_auto_increment']) {
				$val = "";
			}

			$this->writeToFile("public $" . $key . $val . ";	//" . $columns[$key]['comment'] . "", 1);
		}
		$this->writeNewLine();
	}

	private function generateUpdate($columns, $table) {
		$method = 'getUpdateSql';
		$this->writeToFile("public function $method() ", 1);
		$this->writeToFile("{", 1);
		$this->writeToFile("return \"update `$table` SET", 2);
		$keys = array_keys($columns);
		$i = 0;
		$j = 0;
		$primary_key = '';
		foreach ($keys as $key) {
			if ($j % 5 == 0 && $j != 0) {
				$this->writeNewLine();
			}
			$j++;
			if ($columns[$key]['is_primary_key'] || $columns[$key]['is_auto_increment']) {
				$primary_key = $key;
				continue;
			}
			$key_str = '';
			if ($columns[$key]['type'] == 'int') {
				$key_str = "`$key`=\".intval(\$this->" . $key . ").\"";
			} else if ($columns[$key]['type'] == 'string') {
				$key_str = "`$key`='\".BaseFunction::my_addslashes(\$this->" . $key . ").\"'";
			} else if ($columns[$key]['type'] == 'real') {
				$key_str = "`$key`='\".(\$this->" . $key . ").\"'";
			}
			if ($i > 0) {
				$key_str = ", " . $key_str;
			}
			$this->writeToFile($key_str, 3);

			$i++;
		}
		$this->writeNewLine();
		if (!$primary_key) {
			$primary_key = 'id';
		}
		if ($primary_key) {
			if ($columns[$primary_key]['type'] == 'int') {
				$this->writeToFile("where `$primary_key`=\".intval(\$this->$primary_key).\"\";", 3);
			} else if ($columns[$primary_key]['type'] == 'string') {
				$this->writeToFile("where `$primary_key`='\".BaseFunction::my_addslashes(\$this->$primary_key).\"'\";", 3);
			} else if ($columns[$primary_key]['type'] == 'real') {
				$this->writeToFile("where `$primary_key`='\".(\$this->$primary_key).\"'\";", 3);
			}
		}
		$this->writeToFile("}", 1);
		$this->writeNewLine();
	}

	private function generateInsert($columns, $table) {
		$method = 'getInsertSql';
		$this->writeToFile("public function $method() ", 1);
		$this->writeToFile("{", 1);
		$this->writeToFile("return \"insert into `$table` SET", 2);
		$this->writeNewLine();
		$keys = array_keys($columns);
		$i = 0;
		$j = 0;
		foreach ($keys as $key) {
			if ($j % 5 == 0 && $j != 0) {
				$this->writeNewLine();
			}
			$j++;
			if ($columns[$key]['is_auto_increment']) {
				continue;
			}
			$key_str = '';
			if ($columns[$key]['type'] == 'int') {
				$key_str = "`$key`=\".intval(\$this->" . $key . ").\"";
			} else if ($columns[$key]['type'] == 'string') {
				$key_str = "`$key`='\".BaseFunction::my_addslashes(\$this->" . $key . ").\"'";
			} else if ($columns[$key]['type'] == 'real') {
				$key_str = "`$key`='\".(\$this->" . $key . ").\"'";
			}
			if ($i > 0) {
				$key_str = ", " . $key_str;
			}
			$this->writeToFile($key_str, 3);

			$i++;
		}
		$this->writeToFile("\";", 3);
		$this->writeToFile("}", 1);
		$this->writeNewLine();
	}

	private function generateBeforWrite() {
		$method = 'before_writeback';
		$this->writeToFile("public function $method() ", 1);
		$this->writeToFile("{", 1);
		$this->writeToFile("parent::$method();", 2);
		$this->writeToFile("return true;", 2);
		$this->writeToFile("}", 1);
		$this->writeNewLine();
	}

	private function generateDelete($columns, $table) {
		$method = 'getDelSql';
		$this->writeToFile("public function $method() ", 1);
		$this->writeToFile("{", 1);
		$this->writeToFile("return \"delete from `$table`", 2);
		$keys = array_keys($columns);
		$i = 0;
		$primary_key = '';
		foreach ($keys as $key) {
			if ($columns[$key]['is_primary_key']) {
				$primary_key = $key;
			}
			$i++;
		}
		if (!$primary_key) {
			$primary_key = 'id';
		}
		if ($primary_key) {
			if ($columns[$primary_key]['type'] == 'int') {
				$this->writeToFile("where `$primary_key`=\".intval(\$this->$primary_key).\"\";", 3);
			} else if ($columns[$primary_key]['type'] == 'string') {
				$this->writeToFile("where `$primary_key`='\".BaseFunction::my_addslashes(\$this->$primary_key).\"'\";", 3);
			} else if ($columns[$primary_key]['type'] == 'real') {
				$this->writeToFile("where `$primary_key`='\".(\$this->$primary_key).\"'\";", 3);
			}
		}
		$this->writeToFile("}", 1);
		$this->writeNewLine();
	}

	private function generateConstruct($columns, $table, $primary_key) {
		$this->writeToFile("public function __construct(\$dbobj, \$$primary_key) ", 1);
		$this->writeToFile("{", 1);
		$this->writeToFile("\$serverkey = self::objkey;", 2);
		$this->writeToFile("\$objkey = self::objkey.\"_\".\$$primary_key;", 2);

		$this->writeToFile("\$this->sql = \"select", 2);
		$keys = array_keys($columns);
		$i = 0;
		$j = 0;
		$primary_key = '';
		foreach ($keys as $key) {
			if ($j % 5 == 0 && $j != 0) {
				$this->writeNewLine();
			}
			$j++;
			if ($columns[$key]['is_primary_key'] || $columns[$key]['is_auto_increment']) {
				$primary_key = $key;
			}
			$key_str = "`$key`";
			if ($i > 0) {
				$key_str = ", " . "`$key`";
			}
			$this->writeToFile($key_str, 3);

			$i++;
		}
		$this->writeNewLine();
		$this->writeToFile("from `$table`", 3);
		if (!$primary_key) {
			$primary_key = 'id';
		}
		if ($primary_key) {
			if ($columns[$primary_key]['type'] == 'int') {
				$this->writeToFile("where `$primary_key`=\".intval(\$$primary_key).\"\";", 3);
			} else if ($columns[$primary_key]['type'] == 'string') {
				$this->writeToFile("where `$primary_key`='\".BaseFunction::my_addslashes(\$$primary_key).\"'\";", 3);
			} else if ($columns[$primary_key]['type'] == 'string') {
				$this->writeToFile("where `$primary_key`='\".(\$$primary_key).\"'\";", 3);
			}
		}
		$this->writeNewLine();
		$this->writeToFile("parent::__construct(\$dbobj, \$serverkey, \$objkey);", 2);
		$this->writeToFile("return true;", 2);
		$this->writeToFile("}", 1);
		$this->writeNewLine();
	}

	private function generateMultiConstruct($columns, $table, $primary_key) {
		$this->writeToFile("public function __construct(\$dbobj, \$key_objfactory=null, \$" . $primary_key . "=null, \$key_add='') ", 1);
		$this->writeToFile("{", 1);
		$this->writeToFile("if( !\$key_objfactory && !\$$primary_key )", 2);
		$this->writeToFile("{", 2);
		$this->writeToFile("return false;", 3);
		$this->writeToFile("}", 2);
		$this->writeToFile("\$this->key = \$this->key.\$key_add;", 2);
		$this->writeToFile("\$ids = '';", 2);
		$this->writeToFile("if(\$key_objfactory) ", 2);
		$this->writeToFile("{", 2);
		$this->writeToFile("if(\$key_objfactory->initialize()) ", 3);
		$this->writeToFile("{", 3);
		$this->writeToFile("\$key_obj = \$key_objfactory->get();", 4);
		$this->writeToFile("\$ids = implode(',', \$key_obj);", 4);
		$this->writeToFile("}", 3);
		$this->writeToFile("}", 2);
		$this->writeToFile("\$fields = \"", 2);

		$keys = array_keys($columns);
		$i = 0;
		$j = 0;
		foreach ($keys as $key) {
			if ($j % 5 == 0 && $j != 0) {
				$this->writeNewLine();
			}
			$j++;
			$key_str = "`$key`";
			if ($i > 0) {
				$key_str = ", " . "`$key`";
			}
			$this->writeToFile($key_str, 3);

			$i++;
		}
		$this->writeToFile("\";", 3);

		$this->writeNewLine();
		$this->writeToFile("if( \$" . $primary_key . " != null )", 2);
		$this->writeToFile("{", 2);
		$this->writeToFile("\$this->bInitMuti = false;", 3);
		if ($columns[$primary_key]['type'] == 'int') {
			$this->writeToFile("\$this->sql = \"select \$fields from $table where `$primary_key`=\".intval(\$$primary_key).\"\";", 3);
		} else if ($columns[$primary_key]['type'] == 'string') {
			$this->writeToFile("\$this->sql = \"select \$fields from $table where `$primary_key`='\".BaseFunction::my_addslashes(\$$primary_key).\"'\";", 3);
		} else if ($columns[$primary_key]['type'] == 'string') {
			$this->writeToFile("\$this->sql = \"select \$fields from $table where `$primary_key`='\".(\$$primary_key).\"'\";", 3);
		}
		$this->writeToFile("}", 2);
		$this->writeToFile("else", 2);
		$this->writeToFile("{", 2);
		$this->writeToFile("\$this->sql = \"select \$fields from $table \";", 3);
		$this->writeToFile("if(\$ids)", 3);
		$this->writeToFile("{", 3);
		$this->writeToFile("\$this->sql = \$this->sql.\" where `$primary_key` in (\$ids) \";", 4);
		$this->writeToFile("}", 3);
		$this->writeToFile("}", 2);
		$this->writeToFile("parent::__construct(\$dbobj, \$this->key, \$this->key, \$key_objfactory, \$$primary_key);", 2);
		$this->writeToFile("return true;", 2);
		$this->writeToFile("}", 1);

		$this->writeNewLine();
	}

	private function generateRetrive($columns, $class) {
		$this->writeToFile("public function retrive() ", 1);
		$this->writeToFile("{", 1);
		$this->writeToFile("\$records = BaseFunction::query_sql_backend(\$this->sql);", 2);
		$this->writeToFile("if( !\$records ) ", 2);
		$this->writeToFile("{", 2);
		$this->writeToFile("return null;", 3);
		$this->writeToFile("}", 2);
		$this->writeNewLine();
		$this->writeToFile("\$obj = null;", 2);
		$this->writeToFile("while ( (\$row = \$records->fetch_row()) != false ) ", 2);
		$this->writeToFile("{", 2);

		$this->writeToFile("\$obj = new $class;", 3);
		$this->writeNewLine();
		$keys = array_keys($columns);
		$i = 0;
		$j = 0;
		$primary_key = '';
		foreach ($keys as $key) {
			if ($j % 5 == 0 && $j != 0) {
				$this->writeNewLine();
			}
			$j++;
			if ($columns[$key]['is_primary_key'] || $columns[$key]['is_auto_increment']) {
				$primary_key = $key;
			}
			if ($columns[$key]['type'] == 'int') {
				$this->writeToFile("\$obj->$key = intval(\$row[$i]);", 3);
			} else {
				$this->writeToFile("\$obj->$key = (\$row[$i]);", 3);
			}

			$i++;
		}
		$primary_key;
		$this->writeNewLine();
		$this->writeToFile("\$obj->before_writeback();", 3);
		$this->writeToFile("break;", 3);
		$this->writeToFile("}", 2);
		$this->writeToFile("\$records->free();", 2);
		$this->writeToFile("unset(\$records);", 2);
		$this->writeToFile("return \$obj;", 2);
		$this->writeToFile("}", 1);
		//		$this->writeNewLine();
	}

	private function generateMultiRetrive($columns, $class, $primary_key) {
		$this->writeToFile("public function retrive() ", 1);
		$this->writeToFile("{", 1);
		$this->writeToFile("\$records = BaseFunction::query_sql_backend(\$this->sql);", 2);
		$this->writeToFile("if( !\$records ) ", 2);
		$this->writeToFile("{", 2);
		$this->writeToFile("return null;", 3);
		$this->writeToFile("}", 2);
		$this->writeNewLine();
		$this->writeToFile("\$objs = array();", 2);
		$this->writeToFile("while ( (\$row = \$records->fetch_row()) != false ) ", 2);
		$this->writeToFile("{", 2);

		$this->writeToFile("\$obj = new $class;", 3);
		$this->writeNewLine();
		$keys = array_keys($columns);
		$i = 0;
		$j = 0;
		foreach ($keys as $key) {
			if ($j % 5 == 0 && $j != 0) {
				$this->writeNewLine();
			}
			$j++;
			if ($columns[$key]['type'] == 'int') {
				$this->writeToFile("\$obj->$key = intval(\$row[$i]);", 3);
			} else {
				$this->writeToFile("\$obj->$key = (\$row[$i]);", 3);
			}
			$i++;
		}
		$this->writeNewLine();
		$this->writeToFile("\$obj->before_writeback();", 3);
		$this->writeToFile("\$objs[\$this->key.'_'.\$obj->$primary_key] = \$obj;", 3);
		$this->writeToFile("}", 2);
		$this->writeToFile("\$records->free();", 2);
		$this->writeToFile("unset(\$records);", 2);
		$this->writeToFile("return \$objs;", 2);
		$this->writeToFile("}", 1);
	}

	private function generateSetters($columns) {
		foreach ($columns as $key => $value) {
			$value;
			$prop = $this->transform($key);
			if ($this->shouldExcludeProp($prop)) {
				continue;
			}

			$method = 'set' . ucfirst($prop);
			$this->writeToFile("public function $method($$prop) ", 1);
			$this->writeToFile("{", 1);
			$this->writeToFile('$' . "this->$prop = $" . "$prop;", 2);
			$this->writeToFile("}", 1);
			$this->writeNewLine();
		}
	}

	private function generateValidateConfig($table, $columns) {
		$this->writeToFile('private static $_validator = array(', 1);
		$this->writeToFile("'$table' => array(", 2);
		foreach ($columns as $key => $value) {
			$this->writeToFile("'$key' => array(", 3);
			foreach ($value as $k => $v) {
				if (is_string($v)) {
					$this->writeToFile("'$k' => '$v',", 4);
				} else {
					$this->writeToFile("'$k' => $v,", 4);
				}
			}
			$this->writeToFile("),", 3);
		}
		$this->writeToFile("), // $table", 2);
		$this->writeToFile(');', 1);
		$this->writeNewLine();
	}

	private function getMin($max, $type) {
		if (!isset($max)) {
			return null;
		}

		$min = self::DEFAULT_MIN;
		if ($type == 'date' || $min > $max) {
			$min = $max;
		}
		return $min;
	}

	private function _mysql_list_fields($table) {
		global $conn;
		mysqli_query($conn, 'use ' . $this->database);
		$result = mysqli_query($conn, "SHOW COLUMNS FROM " . $table);
		if (!$result) {
			die("Failed to get fields" . mysqli_error());
		}
		$fieldnames = array();
		if (mysqli_num_rows($result) > 0) {
			while ($row = mysqli_fetch_assoc($result)) {
				$fieldnames[] = $row;
			}
		}
		return $fieldnames;
	}

	public function getTableColumns($table) {
		global $conn;
		$fields = $this->_mysql_list_fields($table);
		//$count = mysql_num_fields($fields);
		$count = count($fields);
		if (!isset($fields)) {
			die("Failed to get fields" . mysqli_error());
		}

		$comment = array();
		$result = mysqli_query($conn, "show full fields from $table");
		while (($row = mysqli_fetch_assoc($result)) != false) {
			$comment[$row['Field']] = $row['Comment'];
		}

		$columns = array();
		for ($i = 0; $i < $count; $i++) {
			$flags = $this->_mysql_field_flags($fields, $i);
			$isRequired = preg_match('/NO/', $flags) ? 'not_null' : '';
			$is_primary_key = preg_match('/PRI/', $flags) ? 'primary_key' : '';
			$is_auto_increment = preg_match('/auto_increment/', $flags) ? 'auto_increment' : '';

			$col = $this->_mysql_field_name($fields, $i);
			$max = $this->_mysql_field_len($fields, $i);
			$type = $this->_mysql_field_type($fields, $i);
			$min = $this->getMin($max, $type);

			$columns[$col] = array(
				'isRequired' => $isRequired,
				'max' => $max,
				'min' => $min,
				'type' => $type,
				'comment' => $comment[$col],
				'is_primary_key' => $is_primary_key,
				'is_auto_increment' => $is_auto_increment,
			);

		}

		$sortedColumns = array();
		$keys = array_keys($columns);
		//		sort($keys);
		foreach ($keys as $key) {
			$sortedColumns[$key] = $columns[$key];
		}
		return $sortedColumns;
	}

	private function _mysql_field_flags($fields, $i) {
		if (isset($fields) && $i >= 0) {
			return $fields[$i]['Null'] . ' ' . $fields[$i]['Key'] . ' ' . $fields[$i]['Extra'];
		} else {
			return null;
		}

	}

	private function _mysql_field_name($fields, $i) {
		if (isset($fields) && $i >= 0) {
			return $fields[$i]['Field'];
		} else {
			return null;
		}
	}

	private function _mysql_field_len($fields, $i) {
		if (isset($fields) && $i >= 0) {
			return substr($fields[$i]['Type'], strpos($fields[$i]['Type'], '(') + 1, (strpos($fields[$i]['Type'], ')') - strpos($fields[$i]['Type'], '(') - 1));
		} else {
			return null;
		}
	}

	private function _mysql_field_type($fields, $i) {
		if (isset($fields) && $i >= 0) {
			return preg_match('/int/', $fields[$i]['Type']) ? 'int' : (preg_match('/char/', $fields[$i]['Type']) ? 'string' : (preg_match('/text/', $fields[$i]['Type']) ? 'text' : null));
		} else {
			return null;
		}
	}

	private function getTables() {
		global $conn;
		$sql = "SHOW TABLES FROM {$this->database}";
		$result = mysqli_query($conn, $sql);
		$tables = array();
		for ($i = 0; $i < mysqli_num_rows($result); $i++) {
			//$tables[] = mysql_tablename($result, $i);
			$tables[] = mysqli_fetch_array($result)[0];
		}
		return $tables;
	}

	private function shouldExcludeProp($prop) {
		if (!isset($this->excludedProperties)) {
			return false;
		}

		$index = array_search($prop, $this->excludedProperties);
		return is_int($index);
	}

	private function transform($name) {
		$words = explode('_', $name);
		$newName = null;
		foreach ($words as $word) {
			if ($newName == null) {
				$newName = $word;
			} else {
				$newName .= ucfirst($word);
			}
		}

		return $newName;
	}

	private function writeNewLine() {
		$this->writeToFile('');
	}

	private function writeToFile($str, $count = 0) {
		$space = null;
		$count *= self::DEFAULT_INDENT;
		while ($count) {
			if ($space == null) {
				$space = ' ';
			} else {
				$space .= ' ';
			}
			$count--;
		}
		fwrite($this->file, $space);
		fwrite($this->file, "$str\n");
	}
} // TableClassGenerator

$gen = new TableClassGenerator(array(
	'excludedProperties' => array(),
	'database' => 'game_user_test',
	'host' => 'rm-wz9zl5k3xh05i6913.mysql.rds.aliyuncs.com:3306',
	'parentClass' => 'BaseObject',
	'password' => 'Ncbd%@!@)^',
	'tables' => array(),
	'user' => 'game',
	'class_pre' => 'kx',

	'key_pre' => 'user_php70',
	'multi_arr' => array('user' => '', 'user_more' => '', 'wx_openid' => '',
	),
));

$gen->generateClasses();