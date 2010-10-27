<?php
/* SVN FILE: $Id$ */
/**
 * SQLite3 DBO拡張
 *
 * PHP versions 4 and 5
 *
 * BaserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright 2008 - 2010, Catchup, Inc.
 *								9-5 nagao 3-chome, fukuoka-shi
 *								fukuoka, Japan 814-0123
 *
 * @copyright		Copyright 2008 - 2010, Catchup, Inc.
 * @link			http://basercms.net BaserCMS Project
 * @package			baser.models.datasources.dbo
 * @since			Baser v 0.1.0
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://basercms.net/license/index.html
 */
/**
 * Include files
 */
App::import('Core','DboSqlite3',array('file'=>BASER_MODELS.'datasources'.DS.'dbo'.DS.'dbo_sqlite3.php'));
/**
 * SQLite3 DBO拡張
 *
 * @package			baser.models.datasources.dbo
 */
class DboSqlite3Ex extends DboSqlite3 {
/**
 * カラムを追加するSQLを生成
 *
 * @param string $tableName
 * @param array $column
 * @return string
 * @access public
 */
	function buildAddColumn($tableName, $column) {
		if($column['type'] == 'integer' && !empty($column['length'])){
			unset($column['length']);
		}
		return "ALTER TABLE ".$tableName." ADD ".$this->buildColumn($column);
	}
/**
 * カラムを変更するSQLを生成
 * 未サポート
 * @param string $oldFieldName
 * @param string $newFieldName
 * @param array $column
 * @return string
 * @access public
 */
	function buildEditColumn($tableName, $oldFieldName, $column) {
		return '';
	}
/**
 * カラムを削除する
 * 未サポート
 * @param string $delFieldName
 * @param array $column
 * @return string
 * @access public
 */
	function buildDelColumn($tableName, $delFieldName) {
		return '';
	}
/**
 * テーブル名のリネームステートメントを生成
 *
 * @param	string	$sourceName
 * @param	string	$targetName
 * @return	string
 * @access	public
 */
	function buildRenameTable($sourceName, $targetName) {
		return "ALTER TABLE ".$sourceName." RENAME TO ".$targetName;
	}
/**
 * カラムを変更する
 * 
 * @param	array	$options [ table / new / old / prefix ]
 * @return boolean
 * @access public
 */
	function renameColumn($options) {

		extract($options);

		if(!isset($table) || !isset($new) || !isset($old)) {
			return false;
		}

		if(!isset($prefix)){
			$prefix = $this->config['prefix'];
		}

		$_table = $table;
		$model = Inflector::classify(Inflector::singularize($table));
		$table = $prefix . $table;

		App::import('Model','Schema');
		$Schema = ClassRegistry::init('CakeSchema');
		$Schema->connection = $this->configKeyName;
		$schema = $Schema->read(array('models'=>array($model)));
		$schema = $schema['tables'][$_table];

		$this->execute('BEGIN TRANSACTION;');

		// リネームして一時テーブル作成
		if(!$this->renameTable($table, $table.'_temp')) {
			$this->execute('ROLLBACK;');
			return false;
		}

		// スキーマのキーを変更（並び順を変えないように）
		$newSchema = array();
		foreach($schema as $key => $field) {
			if($key == $old) {
				$key = $new;
			}
			$newSchema[$key] = $field;
		}

		// フィールドを変更した新しいテーブルを作成
		if(!$this->createTable(array('schema'=>$newSchema, 'table'=>$_table))) {
			$this->execute('ROLLBACK;');
			return false;
		}

		// データの移動
		$sql = 'INSERT INTO '.$table.' SELECT '.$this->_convertCsvFieldsFromSchema($schema).' FROM '.$table.'_temp';
		$sql = str_replace($old,$old.' AS '.$new, $sql);
		if(!$this->execute($sql)) {
			$this->execute('ROLLBACK;');
			return false;
		}

		// 一時テーブルを削除
		if(!$this->dropTable(array('table'=>$_table.'_temp'))) {
			$this->execute('ROLLBACK;');
			return false;
		}

		$this->execute('COMMIT;');
		return true;

	}
/**
 * カラムを削除する
 * 
 * @param	array	$options [ table / field / prefix ]
 * @return boolean
 * @access public
 */
	function delColumn($options) {

		extract($options);

		if(!isset($table) || !isset($field)) {
			return false;
		}

		if(!isset($prefix)){
			$prefix = $this->config['prefix'];
		}
		$_table = $table;
		$model = Inflector::classify(Inflector::singularize($table));
		$table = $prefix . $table;

		App::import('Model','Schema');
		$Schema = ClassRegistry::init('CakeSchema');
		$Schema->connection = $this->configKeyName;
		$schema = $Schema->read(array('models'=>array($model)));
		$schema = $schema['tables'][$_table];

		$this->execute('BEGIN TRANSACTION;');

		// リネームして一時テーブル作成
		if(!$this->renameTable($table,$table.'_temp')) {
			$this->execute('ROLLBACK;');
			return false;
		}

		// フィールドを削除した新しいテーブルを作成
		unset($schema[$delFieldName]);
		if(!$this->createTable(array('schema'=>$schema, 'table'=>$_table))) {
			$this->execute('ROLLBACK;');
			return false;
		}

		// データの移動
		if(!$this->_moveData($table.'_temp',$table,$schema)) {
			$this->execute('ROLLBACK;');
			return false;
		}

		// 一時テーブルを削除
		if(!$this->dropTable(array('table'=>$_table.'_temp'))) {
			$this->execute('ROLLBACK;');
			return false;
		}

		$this->execute('COMMIT;');
		return true;

	}
/**
 * テーブルからテーブルへデータを移動する
 * @param	string	$sourceTableName
 * @param	string	$targetTableName
 * @param	array	$schema
 * @return	booelan
 * @access	protected
 */
	function _moveData($sourceTableName,$targetTableName,$schema) {
		$sql = 'INSERT INTO '.$targetTableName.' SELECT '.$this->_convertCsvFieldsFromSchema($schema).' FROM '.$sourceTableName;
		return $this->execute($sql);
	}
/**
 * スキーマ情報よりCSV形式のフィールドリストを取得する
 * @param	array	$schema
 * @return	string
 * @access	protected
 */
	function _convertCsvFieldsFromSchema($schema) {
		$fields = '';
		foreach($schema as $key => $field) {
			$fields .= "'".$key."',";
		}
		return substr($fields,0,strlen($fields)-1);
	}
}
?>