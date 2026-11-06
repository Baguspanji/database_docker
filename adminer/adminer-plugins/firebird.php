<?php
/**
* @author Steve Krämer
*/

namespace Adminer;

add_driver('firebird', 'Firebird (alpha)');

if (isset($_GET["firebird"])) {
	define('Adminer\DRIVER', "firebird");

	if (extension_loaded("interbase")) {
		class Db extends SqlDb {
			public $extension = "Firebird", $_link;

			function attach(array $server, $username, $password) {
				$host = $server["host"] . ($server["port"] != "" ? "/" . $server["port"] : "");
				$this->_link = ibase_connect($host . ($server["path"] != "" ? ":" . $server["path"] : ""), $username, $password); // the ibase connection string is 'host/port:/path/to/your.gdb'
				if ($this->_link) {
					$service_link = ibase_service_attach($host, $username, $password);
					$this->server_info = ibase_server_info($service_link, IBASE_SVC_SERVER_VERSION);
					return '';
				}
				return ibase_errmsg();
			}

			function quote($string) {
				return "'" . str_replace("'", "''", $string) . "'";
			}

			function select_db($database) {
				return ($database == "domain");
			}

			function query($query, $unbuffered = false) {
				$result = ibase_query($this->_link, $query);
				if (!$result) {
					$this->errno = ibase_errcode();
					$this->error = ibase_errmsg();
					return false;
				}
				$this->error = "";
				if ($result === true) {
					$this->affected_rows = ibase_affected_rows($this->_link);
					return true;
				}
				return new Result($result);
			}
		}

		class Result {
			public $num_rows;
			private $result, $offset = 0;

			function __construct($result) {
				$this->result = $result;
				// $this->num_rows = ibase_num_rows($result);
			}

			function fetch_assoc() {
				return ibase_fetch_assoc($this->result);
			}

			function fetch_row() {
				return ibase_fetch_row($this->result);
			}

			function fetch_field() {
				$types = array(
					'TEXT' => 'char', 'VARYING' => 'varchar', 'SHORT' => 'smallint', 'LONG' => 'integer', 'INT64' => 'bigint',
					'FLOAT' => 'float', 'DOUBLE' => 'double precision', 'BLOB' => 'blob', 'DATE' => 'date', 'TIME' => 'time', 'TIMESTAMP' => 'timestamp',
				);
				$field = ibase_field_info($this->result, $this->offset++);
				return (object) array(
					'name' => $field['name'],
					'native_type' => idx($types, $field['type'], ''),
				);
			}
		}

	}



	class Driver extends SqlDriver {
		static $extensions = array("interbase");
		static $jush = "firebird";

		static $serverPath = true; // the path to the database file

		function operators($tableStatus) {
			return array("=");
		}

		/** Get the JUSH module inlined in the released driver by the release script */
		static function jushModule() {
			return <<<'JS'
jush.tr.firebird = { sqlite_apo: /'/, sqlite_quo: /"/, one: /--/, com: /\/\*/, num: jush.num };

jush.autocompleting.sql.push('firebird', 'sqlite_quo'); // sqlite_quo is a quoted identifier

jush.slugs.firebird = name => name.toLowerCase().replace(/_/g, '-'); // the anchors of the functions use dashes

// the whole language reference is a single page, the keys are its anchors
jush.build_links2('firebird', 'https://firebirdsql.org/file/documentation/html/en/refdocs/fblangref50/firebird-50-language-reference.html$key', /(\b)/, /(\b)/gi, {
	'#fblangref50-dml-select': /(SELECT)/,
	'#fblangref50-dml-select-first-skip': /(FIRST|SKIP)/,
	'#fblangref50-dml-select-from': /(FROM)/,
	'#fblangref50-dml-select-joins': /((?:(?:NATURAL|INNER|CROSS|LEFT|RIGHT|FULL|OUTER)\s+)*JOIN|ON|USING)/,
	'#fblangref50-dml-select-where': /(WHERE)/,
	'#fblangref50-dml-select-groupby': /(GROUP\s+BY|HAVING)/,
	'#fblangref50-dml-select-window': /(WINDOW|OVER|PARTITION\s+BY)/,
	'#fblangref50-dml-select-plan': /(PLAN)/,
	'#fblangref50-dml-select-union': /(UNION)/,
	'#fblangref50-dml-select-orderby': /(ORDER\s+BY|ASC(?:ENDING)?|DESC(?:ENDING)?|NULLS)/,
	'#fblangref50-dml-select-rows': /(ROWS)/,
	'#fblangref50-dml-select-offsetfetch': /(OFFSET|FETCH)/,
	'#fblangref50-dml-with-lock': /(WITH\s+LOCK)/, // must be before WITH
	'#fblangref50-dml-select-cte': /(WITH)/,
	'#fblangref50-dml-insert': /(INSERT)/,
	'#fblangref50-dml-insert-returning': /(RETURNING)/,
	'#fblangref50-dml-update-or-insert': /(UPDATE\s+OR\s+INSERT)/, // must be before UPDATE
	'#fblangref50-dml-update': /(UPDATE)/,
	'#fblangref50-dml-delete': /(DELETE)/,
	'#fblangref50-dml-merge': /(MERGE|MATCHED)/,
	'#fblangref50-dml-execblock': /(EXECUTE\s+BLOCK)/,
	'#fblangref50-dml-execproc': /(EXECUTE\s+PROCEDURE)/,
	'#fblangref50-ddl-tbl-create': /(CREATE(?:\s+GLOBAL\s+TEMPORARY)?\s+TABLE)/,
	'#fblangref50-ddl-tbl-alter': /(ALTER\s+TABLE)/,
	'#fblangref50-ddl-tbl-drop': /(DROP\s+TABLE)/,
	'#fblangref50-ddl-idx-create': /(CREATE(?:\s+UNIQUE)?(?:\s+ASC(?:ENDING)?|\s+DESC(?:ENDING)?)?\s+INDEX)/,
	'#fblangref50-ddl-idx-dropidx': /(DROP\s+INDEX)/,
	'#fblangref50-ddl-view-create': /(CREATE\s+VIEW)/,
	'#fblangref50-ddl-view-drop': /(DROP\s+VIEW)/,
	'#fblangref50-ddl-proc-create': /(CREATE\s+PROCEDURE)/,
	'#fblangref50-ddl-proc-drop': /(DROP\s+PROCEDURE)/,
	'#fblangref50-ddl-func-create': /(CREATE\s+FUNCTION)/,
	'#fblangref50-ddl-func-drop': /(DROP\s+FUNCTION)/,
	'#fblangref50-ddl-trgr-create': /(CREATE\s+TRIGGER)/,
	'#fblangref50-ddl-trgr-drop': /(DROP\s+TRIGGER)/,
	'#fblangref50-ddl-sequence-create': /(CREATE\s+(?:SEQUENCE|GENERATOR))/,
	'#fblangref50-ddl-sequence-drop': /(DROP\s+(?:SEQUENCE|GENERATOR))/,
	'#fblangref50-ddl-domn-create': /(CREATE\s+DOMAIN)/,
	'#fblangref50-ddl-db-create': /(CREATE\s+DATABASE)/,
	'#fblangref50-ddl-comment-create': /(COMMENT\s+ON)/,
	'#fblangref50-transacs-settransac': /(SET\s+TRANSACTION)/,
	'#fblangref50-transacs-commit': /(COMMIT)/,
	'#fblangref50-transacs-rollback': /(ROLLBACK)/,
	'#fblangref50-transacs-savepoint': /(SAVEPOINT)/,
	'#fblangref50-security-grant': /(GRANT)/,
	'#fblangref50-security-revoke': /(REVOKE)/,
	'#fblangref50-datatypes-inttypes': /(SMALLINT|INTEGER|INT128|BIGINT|INT)/,
	'#fblangref50-datatypes-floattypes': /(DOUBLE\s+PRECISION|DECFLOAT|FLOAT|REAL)/,
	'#fblangref50-datatypes-fixedtypes': /(NUMERIC|DECIMAL)/,
	'#fblangref50-datatypes-datetime': /(TIMESTAMP|DATE|TIME)/,
	'#fblangref50-datatypes-chartypes': /(CHARACTER\s+VARYING|CHARACTER|VARCHAR|NCHAR|CHAR)/,
	'#fblangref50-datatypes-boolean': /(BOOLEAN)/,
	'#fblangref50-datatypes-bnrytypes': /(BLOB)/,
	'#fblangref50-functions-datetime': /(CURRENT_DATE|CURRENT_TIME(?:STAMP)?|LOCALTIME(?:STAMP)?)/, // these have anchors in another namespace
	'#fblangref50-scalarfuncs-ceil': /(CEILING|CEIL)/,
	'#fblangref50-scalarfuncs-char-length': /(CHARACTER_LENGTH|CHAR_LENGTH)/,
	'#fblangref50-scalarfuncs-firstday': /(FIRST_DAY)/,
	'#fblangref50-scalarfuncs-lastday': /(LAST_DAY)/,
	'#fblangref50-scalarfuncs-$1': /(ABS|ACOSH|ACOS|ASINH|ASIN|ATAN2|ATANH|ATAN|COSH|COS|COT|EXP|FLOOR|LN|LOG10|LOG|MOD|PI|POWER|RAND|ROUND|SIGN|SINH|SIN|SQRT|TANH|TAN|TRUNC|ASCII_CHAR|ASCII_VAL|BIT_LENGTH|BLOB_APPEND|OCTET_LENGTH|LEFT|LOWER|LPAD|OVERLAY|POSITION|REPLACE|REVERSE|RIGHT|RPAD|SUBSTRING|TRIM|UNICODE_CHAR|UNICODE_VAL|UPPER|HASH|DATEADD|DATEDIFF|EXTRACT|BIN_AND|BIN_NOT|BIN_OR|BIN_SHL|BIN_SHR|BIN_XOR|CHAR_TO_UUID|GEN_UUID|UUID_TO_CHAR|GEN_ID|CAST|COALESCE|DECODE|IIF|MAXVALUE|MINVALUE|NULLIF)(?=\s*\(|$)/,
	'#fblangref50-aggfuncs-$1': /(AVG|COUNT|LIST|MAX|MIN|SUM)(?=\s*\(|$)/,
	'': /(AND|OR|NOT|IN|LIKE|CONTAINING|STARTING\s+WITH|SIMILAR\s+TO|BETWEEN|IS|EXISTS|SINGULAR|SOME|ANY|ALL|DISTINCT|CASE|WHEN|THEN|ELSE|END|AS|INTO|VALUES|SET|DEFAULT|PRIMARY\s+KEY|FOREIGN\s+KEY|REFERENCES|UNIQUE|CHECK|CONSTRAINT|COMPUTED\s+BY|COLLATE|GENERATED|IDENTITY|NULL|TRUE|FALSE|UNKNOWN)/,
}); // collisions: CHARACTER VARYING and CHAR_LENGTH with the CHAR type, MAX and MIN with MAXVALUE and MINVALUE
JS;
		}
	}



	function idf_escape($idf) {
		return '"' . str_replace('"', '""', $idf) . '"';
	}

	function table($idf) {
		return idf_escape($idf);
	}

	function get_databases($flush) {
		return array("domain");
	}

	function limit($query, $where, $limit, $offset = 0, $separator = " ") {
		$return = '';
		$return .= ($limit ? $separator . "FIRST $limit" . ($offset ? " SKIP $offset" : "") : "");
		$return .= " $query$where";
		return $return;
	}

	function limit1($table, $query, $where, $separator = "\n") {
		return limit($query, $where, 1, 0, $separator);
	}

	function db_collation($db, array $collations) {
	}

	function logged_user() {
		$credentials = adminer()->credentials();
		return $credentials[1];
	}

	function tables_list() {
		$query = 'SELECT RDB$RELATION_NAME FROM rdb$relations WHERE rdb$system_flag = 0';
		$result = ibase_query(connection()->_link, $query);
		$return = array();
		while ($row = ibase_fetch_assoc($result)) {
				$return[$row['RDB$RELATION_NAME']] = 'table';
		}
		ksort($return);
		return $return;
	}

	function count_tables(array $databases) {
		return array();
	}

	function table_status($name = "", $fast = false) {
		$return = array();
		$data = ($name != "" ? array($name => 1) : tables_list());
		foreach ($data as $index => $val) {
			$index = trim($index);
			$return[$index] = array(
				'Name' => $index,
				'Engine' => 'standard',
			);
		}
		return $return;
	}

	function is_view(array $table_status) {
		return false;
	}

	function fk_support(array $table_status) {
		return preg_match('~InnoDB|IBMDB2I~i', $table_status["Engine"]);
	}

	function fields($table) {
		$return = array();
		$query = 'SELECT r.RDB$FIELD_NAME AS field_name,
r.RDB$DESCRIPTION AS field_description,
r.RDB$DEFAULT_VALUE AS field_default_value,
r.RDB$NULL_FLAG AS field_not_null_constraint,
r.RDB$IDENTITY_TYPE AS field_identity_type,
f.RDB$FIELD_LENGTH AS field_length,
f.RDB$FIELD_PRECISION AS field_precision,
f.RDB$FIELD_SCALE AS field_scale,
CASE f.RDB$FIELD_TYPE
WHEN 261 THEN \'BLOB\'
WHEN 14 THEN \'CHAR\'
WHEN 40 THEN \'CSTRING\'
WHEN 11 THEN \'D_FLOAT\'
WHEN 27 THEN \'DOUBLE\'
WHEN 10 THEN \'FLOAT\'
WHEN 16 THEN \'INT64\'
WHEN 8 THEN \'INTEGER\'
WHEN 9 THEN \'QUAD\'
WHEN 7 THEN \'SMALLINT\'
WHEN 12 THEN \'DATE\'
WHEN 13 THEN \'TIME\'
WHEN 35 THEN \'TIMESTAMP\'
WHEN 37 THEN \'VARCHAR\'
ELSE \'UNKNOWN\'
END AS field_type,
f.RDB$FIELD_SUB_TYPE AS field_subtype,
coll.RDB$COLLATION_NAME AS field_collation,
cset.RDB$CHARACTER_SET_NAME AS field_charset
FROM RDB$RELATION_FIELDS r
LEFT JOIN RDB$FIELDS f ON r.RDB$FIELD_SOURCE = f.RDB$FIELD_NAME
LEFT JOIN RDB$COLLATIONS coll ON f.RDB$COLLATION_ID = coll.RDB$COLLATION_ID
LEFT JOIN RDB$CHARACTER_SETS cset ON f.RDB$CHARACTER_SET_ID = cset.RDB$CHARACTER_SET_ID
WHERE r.RDB$RELATION_NAME = ' . q($table) . '
ORDER BY r.RDB$FIELD_POSITION';
		$result = ibase_query(connection()->_link, $query);
		while ($row = ibase_fetch_assoc($result)) {
			$return[trim($row['FIELD_NAME'])] = array(
				"field" => trim($row["FIELD_NAME"]),
				"full_type" => trim($row["FIELD_TYPE"]),
				"type" => trim($row["FIELD_SUB_TYPE"]),
				"default" => ($row["FIELD_IDENTITY_TYPE"] !== null // 0 - GENERATED ALWAYS, 1 - GENERATED BY DEFAULT
					? "GENERATED " . ($row["FIELD_IDENTITY_TYPE"] ? "BY DEFAULT" : "ALWAYS") . " AS IDENTITY"
					: trim($row['FIELD_DEFAULT_VALUE'])
				),
				"null" => (trim($row["FIELD_NOT_NULL_CONSTRAINT"]) == "YES"),
				"auto_increment" => ($row["FIELD_IDENTITY_TYPE"] !== null),
				"collation" => trim($row["FIELD_COLLATION"]),
				"privileges" => array("insert" => 1, "select" => 1, "update" => 1, "where" => 1, "order" => 1),
				"comment" => trim($row["FIELD_DESCRIPTION"]),
			);
		}
		return $return;
	}

	function indexes($table, $connection2 = null) {
		$return = array();
		/*
		$query = 'SELECT RDB$INDEX_SEGMENTS.RDB$FIELD_NAME AS field_name,
RDB$INDICES.RDB$DESCRIPTION AS description,
(RDB$INDEX_SEGMENTS.RDB$FIELD_POSITION + 1) AS field_position
FROM RDB$INDEX_SEGMENTS
LEFT JOIN RDB$INDICES ON RDB$INDICES.RDB$INDEX_NAME = RDB$INDEX_SEGMENTS.RDB$INDEX_NAME
LEFT JOIN RDB$RELATION_CONSTRAINTS ON RDB$RELATION_CONSTRAINTS.RDB$INDEX_NAME = RDB$INDEX_SEGMENTS.RDB$INDEX_NAME
WHERE UPPER(RDB$INDICES.RDB$RELATION_NAME) = ' . q($table) . '
// AND UPPER(RDB$INDICES.RDB$INDEX_NAME) = \'TEST2_FIELD5_IDX\'
AND RDB$RELATION_CONSTRAINTS.RDB$CONSTRAINT_TYPE IS NULL
ORDER BY RDB$INDEX_SEGMENTS.RDB$FIELD_POSITION';
		*/
		return $return;
	}

	function foreign_keys($table) {
		return array();
	}

	function collations() {
		return array();
	}

	function information_schema($db) {
		return false;
	}

	function error() {
		return h(connection()->error);
	}

	function last_id($result) {
	}

	function explain(Db $connection, $query) {
	}

	function found_rows(array $table_status, array $where) {
	}

	function types() {
		return array();
	}

	function convert_field(array $field) {
	}

	function unconvert_field(array $field, $return) {
		return $return;
	}

	function support($feature) {
		return preg_match("~^(columns|sql|table)$~", $feature);
	}
}
