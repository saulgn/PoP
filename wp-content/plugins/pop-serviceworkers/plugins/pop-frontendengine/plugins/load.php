<?php

//-------------------------------------------------------------------------------------
// Load Plugin-specific Libraries
//-------------------------------------------------------------------------------------

if (defined('POP_MULTIDOMAIN_INITIALIZED')) {
	require_once 'pop-multidomain/load.php';		
}

if (defined('EM_POPPROCESSORS_VERSION')) {
	require_once 'events-manager-popprocessors/load.php';
}