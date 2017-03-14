<?php

require_once __DIR__.'/config.inc.php';
require_once __DIR__.'/../init.php';

// Create a simple "default" Doctrine ORM configuration for Annotations
$isDevMode = true;
// or if you prefer yaml or XML
//$config = Setup::createXMLMetadataConfiguration(array(__DIR__."/config/xml"), $isDevMode);
//$config = Setup::createYAMLMetadataConfiguration(array(__DIR__."/config/yaml"), $isDevMode);

// obtaining the entity manager
$entityManager = new EntityManager();

return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($entityManager);
