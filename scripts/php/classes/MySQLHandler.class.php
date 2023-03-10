<?php

class MySQLHandler {

  protected $_config = array();
  protected $_link;
  protected $_result;

  public function __construct( array $config ) {
    if ( count( $config ) !== 4 ) {
      throw new InvalidArgumentException( 'Invalid Number Of Connection Parameters' );
    }

    $this -> _config = $config;
  }

  /**
   * Close Automatically The Database Connection, When The Instance Of The Class Is Destroyed
   */
  public function __destruct() {
    $this -> disconnect();
  }

  public function connect() {
    // its calld single teone design pattern, 
    // mean make sure that there is one connection exists
    if ( $this -> _link === null ) {
      list( $host, $user, $password, $database ) = $this -> _config;

      if ( ! $this -> _link = @mysqli_connect( $host, $user, $password, $database ) ) {
        throw new RuntimeException( 'Error Connecting To The Server : ' . mysqli_connect_error() );
      }

      unset( $host, $user, $password, $database );
    }

    return $this -> _link;
  }

  public function query( $query ) {
    if ( !is_string( $query ) || empty( $query ) ) {
      throw new InvalidArgumentException( 'The Specified Query Is Not Valid. ');
    }

    // lazy connect to mysql means connection open when need to execute query
    $this -> connect();

    if ( ! $this -> _result = mysqli_query( $this -> _link, $query ) ) {
      throw new RuntimeException( 'Error Executing The Specified Query ' . $query . mysqli_error( $this -> _link ) );
    }

    return $this -> _result;
  }

  public function select( $table, $where = '', $fields = '*', $order = '', $limit = null, $offset = null ) {
    $query = "SELECT $fields FROM $table"
      . ( $where ? " WHERE " . $where : '' )
      . ( $limit ? " LIMIT " . $limit : '' )
      . ( $offset && $limit ? " OFFSET " . $offset : '' )
      . ( $order ? " ORDER BY " . $order : '' );

    $this -> query( $query );

    return $this -> countRows();
  }

  public function insert( $table, array $data ) {
    $fields = implode( ',', array_keys( $data ) );
    $values = implode( ',', array_map( array( $this, 'quoteValue' ), array_values( $data ) ) );

    // $values = implode( ',', array_map( array( $this, 'quoteValue' ), array( $data ) ) );

    $query  = "INSERT INTO $table ( $fields ) VALUES ( $values )";

    $this -> query( $query );

    return $this -> getInsertId();
  }

  public function update( $table, array $data, $where = '' ) {
    $set = array();

    foreach ( $data as $field => $value ) {
      $set[] = "$field = " . $this -> quoteValue( $value );
    }

    $set = implode( ',', $set );
    
    $query = "UPDATE $table SET $set"
      . ( $where ? ' WHERE ' . $where : '' );

    $this -> query( $query );
    
    return $this -> getAffectedRows();
  }

  public function delete( $table, $where = '' ) {
    $query = "DELETE FROM $table"
      . ( $where ? ' WHERE ' . $where : '' );

    $this -> query( $query );

    return $this -> getAffectedRows();
  }

  /**
   * Escape The Specified Value
   */
  public function quoteValue( $value ) {
    $this -> connect();

    if ( $value === null ) {
      $value = 'NULL';
    } else if ( ! is_numeric( $value ) ) {
      $value = "'" . mysqli_real_escape_string( $this -> _link, $value ) . "'";
    }

    return $value;
  }

  public function fetch() {
    if ( $this -> _result !== null ) {

      if ( ( $row = mysqli_fetch_array( $this -> _result, MYSQLI_ASSOC ) ) === false ) {
        $this -> freeResult();
      }

      return $row;
    }

    return false;
  }

  public function fetchAll() {
    if ( $this -> _result !== null ) {

      if ( ( $all = mysqli_fetch_all( $this -> _result, MYSQLI_ASSOC ) ) === false ) {
        $this -> freeResult();
      }

      return $all;
    }

    return false;
  }

  public function getInsertId() {
    return $this -> _link !== null
      ? mysqli_insert_id( $this -> _link )
      : null;
  }

  public function countRows() {
    return $this -> _result !== null
      ? mysqli_num_rows( $this -> _result )
      : 0;
  }

  public function getAffectedRows() {
    return $this -> _link !== null
      ? mysqli_affected_rows( $this -> _link )
      : 0;
  }

  public function freeResult() {
    if ( $this -> _result === null ) {
      return false;
    }

    mysqli_free_result( $this -> _result );

    return true;
  }

  /**
   * Close Explicitly The Database Connection
   */
  public function disconnect() {
    if ( $this -> _link === null ) {
      return false;
    }

    mysqli_close( $this -> _link );
    $this -> _link = null;

    return true;
  }

}