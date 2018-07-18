<?php

/**
 *  @desc SQLite3 class 版本
 *  @created 2017/04/18
 */

class CSQLite3 {

	// Variables
	var $m_sDb		=	"";
	var $m_iDbh		=	0;
	var $m_iRs 		= 	0;
	private $mode;

	/**
	 * @param string $sDb database filename
	 * @param [type] $mode SQLITE3_BOTH、SQLITE3_ASSOC、SQLITE3_NUM
	 * @created 2017/04/18
	 */
	public function __construct($sDb='', $mode = SQLITE3_ASSOC) {
		$this->m_sDb=defined('_MYSQL_DB')?_MYSQL_DB:null;
		$this->mode = $mode;

		if($sDb) $this->m_sDb=$sDb;

		//check the file exists
		if( !is_file( $this->m_sDb ) )
			throw new Exception( 'CSQLite3: File '.$this->m_sDb.' wasn\'t found.' );

		if(!$this->m_iDbh) {
			$this->vConnect();
		}
		return $this->m_iDbh;
    }

	public function __destruct() {
		$this->vClose();
	}


	/**
	 *  @desc 連線資料庫
	 */
	function vConnect() {
		$this->m_iDbh = new SQLite3($this->m_sDb);
	}

	/**
	 *  @desc 關閉資料庫
	 */
	function vClose() {
		$this->m_iDbh->close();
		$this->m_iDbh = NULL;
	}

	/**
	 *  @desc query db
	 *  @param $sSql SQL語法
	 *  @return value of variable $m_iRs
	 */
	function iQuery($sSql){
		$result = $this->m_iDbh->query($sSql);
		if (!$result){
			throw new Exception($this->m_iDbh->lastErrorMsg());
		}
		$this->m_iRs = $result;
		return $result;
	}

	/**
	 * @desc 執行不須回傳值的語法
	 * @created 2017/04/18
	 */
	function vExec($sSql){
		$result = $this->m_iDbh->exec($sSql);
		if (!$result){
			throw new Exception($this->m_iDbh->lastErrorMsg());
		}
		$this->m_iRs = $result;
	}

	/**
	 *  @desc 取得sql結果
	 *  @param $iRs resource result
	 *  @param result_type: MYSQLI_BOTH, MYSQLI_ASSOC, MYSQLI_NUM
	 *  @return Fetch a result row as an associative array, a numeric array, or both.
	 */
	function aFetchArray($iRs) {
       	# Get Columns
       	// $i = 0;
       	// while($iRs->columnName($i)){
       	// 	$columns[] = $iRs->columnName($i);
       	// 	$i++;
       	// }

       	$resx = $iRs->fetchArray(MYSQLI_BOTH);
       	return $resx;
	}

	/**
	* @param $iRs resource result
	* @return Fetch a result row as an associative array, a numeric array, or both.
	* @desc 取得sql結果
	*/
	function aFetchAssoc($iRs=0) {
    	$resx = $iRs->fetchArray(SQLITE3_ASSOC);
       	return $resx;
	}

	/**
	* @return Get the ID generated from the previous INSERT operation
	* @desc
	*/
	function iGetInsertId() {
		return $this->m_iDbh->lastInsertRowID();
	}

	/**
	 * @desc 資料庫更動序號(取得insert後的自動流水號)
	 * @created 2017/04/18
	 */
	function iGetChangeRowID(){
		return $this->m_iDbh->changes();
	}

	/**
	 * delete
	 *
	 * @param string $table
	 * @param string $where
	 * @param integer $limit
	 * @return integer Affected Rows
	 */
    function vDelete($sTable,$sWhere){
		if (!$sWhere) throw new Exception("CSQLite3->vDelete: fail no where. table: $sTable");
    	$this->iQuery("DELETE FROM $sTable WHERE $sWhere");
		if(!$this->m_iRs){
			throw new Exception("CSQLite3->vDelete: fail to delete data in $sTable");
		}
		$iChangeRows = $this->iGetChangeRowID();
		return $iChangeRows;
    }

  	/**
	* @param $sTable db table $aField field array $aValue value array
	* @return if return sql is ok  "" is failure
	* @desc insert into table
	*/
	function sInsert($sTable,$aField,$aValue) {
		if(!is_array($aField)) return 0;
		if(!is_array($aValue)) return 0;

		count($aField)==count($aValue) or die(count($aField) .":". count($aValue) );

		$sSql="INSERT INTO $sTable ( ";
		for($i=1;$i<=count($aField);$i++) {
			$sSql.="`".$aField[$i-1]."`";
			if($i!=count($aField)) $sSql.=",";
		}

		$sSql.=") values(";

		for($i=1;$i<=count($aValue);$i++) {
			$sSql.="'".$this->escapeString($aValue[$i-1])."'";
			if($i!=count($aValue)) $sSql.=",";
		}
		$sSql.=")";

		$this->iQuery($sSql);

		//if(!$this->m_iRs) return NULL;
		if(!$this->m_iRs) throw new Exception("CSQLite3->sInsert: fail to insert data into $sTable");
		else return $sSql;
	}

	/**
	* @param $sTable db table $aField field array $aValue value array $sWhere trem
	* @return if return sql is ok  "" is failure
	* @desc update  table
	*/
	function sUpdate($sTable,$aField,$aValue,$sWhere) {
		if(!is_array($aField)) return 0;
		if(!is_array($aValue)) return 0;

		if(count($aField)!=count($aValue)) return 0;

		$sSql="update $sTable set ";
		for($i=0;$i<count($aField);$i++) {
			$sSql.="`".$aField[$i]."`='".$this->escapeString($aValue[$i])."'";
			if(($i+1)!=count($aField)) $sSql.=",";
		}

		$sSql.=" where ".$sWhere;
		$this->sSql = $sSql;
		$this->iQuery($sSql);
		if(!$this->m_iRs) throw new Exception("CSQLite3->sUpdate: fail to update data in $sTable");
		else return $sSql;
    }

	/**
	* @param string $sTable The table name, array $aAdd The add data array
	* @return boolean
	* @desc insert into table
	*/
	function bInsert( $sTable , $aAdd ) {
		$sSql="INSERT INTO $sTable (";
		foreach( $aAdd AS $key => $value ) {
			$sSql.="`".$key."`,";
		}
		$sSql = substr($sSql,0,-1);
		$sSql.=") values (";
		foreach( $aAdd AS $key => $value ) {
			$sSql.="'".$value."',";
		}
		$sSql = substr($sSql,0,-1);
		$sSql.=")";

		$this->sSql = $sSql;
		$this->vExec( $sSql );
		if(!$this->m_iRs) throw new Exception("CSQLite3->bInsert: fail to insert data in $sTable");
		return $this->iGetInsertId();
	}

	/**
	* @param string $sTable The table name, array $aSrc The source data array, array $aTar The target data array
	* @return boolean
	* @desc update table
	*/
	function bUpdate( $sTable , $aSrc , $aTar ) {
		$aWhere = array();
		foreach( $aSrc AS $key => $value ) {
			$aWhere[] = "$key = '".$this->escapeString($value)."'";
		}
		$aSrc = array();
		foreach( $aTar AS $key => $value ) {
			$aSet[] = "$key = '".$this->escapeString($value)."'";
		}
		$sSQL = "UPDATE $sTable SET " . implode( "," , $aSet ) . " WHERE " . ( count( $aWhere ) > 0 ? implode( " AND " , $aWhere ) : "1" );

		$this->sSql = $sSQL;
		$this->vExec( $sSQL );
		if(!$this->m_iRs) throw new Exception("CSQLite3->bUpdate: fail to update data in $sTable");
		$iChangeRows = $this->iGetChangeRowID();
		return $iChangeRows;
	}

	function escapeString($value) {
		$result = $this->m_iDbh->escapeString($value);
		return $result;
	}

}
?>
