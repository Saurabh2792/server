<?php
// This file generated by Propel  convert-conf target
// from XML runtime conf file /Users/hila.karimov/Documents/kaltura/app/alpha/config/runtime-conf.xml
return array (
  'datasources' => 
  array (
    'kaltura' => 
    array (
      'adapter' => 'mysql',
      'connection' => 
      array (
        'phptype' => 'mysql',
        'database' => 'kaltura',
        'hostspec' => 'localhost',
        'username' => 'root',
        'password' => 'root',
      ),
    ),
    'default' => 'kaltura',
  ),
  'log' => 
  array (
    'ident' => 'kaltura',
    'level' => '7',
  ),
  'generator_version' => '1.4.2',
  'classmap' => 
  array (
    'BusinessProcessServerTableMap' => 'lib/model/map/BusinessProcessServerTableMap.php',
    'BusinessProcessServerPeer' => 'lib/model/BusinessProcessServerPeer.php',
    'BusinessProcessServer' => 'lib/model/BusinessProcessServer.php',
    'BusinessProcessCaseTableMap' => 'lib/model/map/BusinessProcessCaseTableMap.php',
    'BusinessProcessCasePeer' => 'lib/model/BusinessProcessCasePeer.php',
    'BusinessProcessCase' => 'lib/model/BusinessProcessCase.php',
  ),
);