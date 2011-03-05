<?php
	require_once 'PHPUnit/Framework.php';
	require_once 'bootstrap.php';
	require_once 'Frawst/RequestTest.php';
	
	$suite = new PHPUnit_Framework_TestSuite();
	$suite->addTestSuite('Frawst\RequestTest');
	$suite->run();