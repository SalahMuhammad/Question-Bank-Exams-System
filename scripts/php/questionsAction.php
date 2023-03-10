<?php

session_start();

if ( ! $_SESSION ['user_data'] ['admin'] ) header( 'Location: ../../app/login.php');

require_once './myFunctions.php';

require_once './classes/database_config.php';
require_once './classes/MySQLHandler.class.php';
require_once './classes/crud/questions.class.php';

$question  = new Questions();
$result = '';

if ( isset( $_GET ['submit'] ) ) {

  $q_id       = isset( $_GET ['q_id'] ) ? clean( $_GET ['q_id'] ) : null;
  $q          = isset( $_GET ['q'] )    ? clean( $_GET ['q'] )    : null;
  $selections = isset( $_GET ['s'] )    ? clean( $_GET ['s'] )    : null;
  $answer     = isset( $_GET ['an'] )   ? clean( $_GET ['an'] )   : null;
  $e_id       = isset( $_GET ['e_id'] ) ? clean( $_GET ['e_id'] ) : null;

  switch ( $_GET ['submit'] ) {
    case 'Save':
      $result = $question -> insertQ( array( "q_id" => null, "question" => $q, "selections" => $selections, "answer" => $answer, "e_id" => $e_id ) );
      break;

    case 'Update':
      $result = $question -> updateQ( array( "question" => $q, "selections" => $selections, "answer" => $answer, "e_id" => $e_id ), $q_id );
      break;

    case 'Delete':
      $result = $question -> deleteQ( $q_id );
      break;
  }

  $question = null;
  header( 'Location: ../../app/questions.php?status=' . $result );
  exit;

}

$question = null;
header( 'Location: ../../app/questions.php' );
exit;