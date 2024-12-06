<?php

$EM_CONF[$_EXTKEY] = [
	'title' => 'Media Processing',
	'description' => 'Integrates various image processing libraries and SaaS cloud services into TYPO3 by leveraging their APIs to process images.',
	'category' => 'services',
	'author' => 'Thomas Deinhamer',
	'author_email' => 'code@thasmo.dev',
	'author_company' => 'somehow.digital',
	'state' => 'alpha',
	'version' => '0.7.1',
	'autoload' => [
		'psr-4' => [
			'SomehowDigital\\Typo3\MediaProcessing\\' => 'Classes/',
		],
	],
	'constraints' => [
		'depends' => [
			'php' => '8.1.0-8.3.99',
			'typo3' => '12.0.0-12.4.99',
		],
		'conflicts' => [],
		'suggests' => [],
	],
];
