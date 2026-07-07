<?php

// SPDX-FileCopyrightText: 2025 Bundesrepublik Deutschland, vertreten durch das BMI/ITZBund
//
// SPDX-License-Identifier: GPL-3.0-or-later

declare(strict_types=1);

/*
 * This file is part of the package itzbund/gsb-places of the GSB 11 Project by ITZBund
 *
 * Copyright (C) 2025 Bundesrepublik Deutschland, vertreten durch das
 * BMI/ITZBund. Author: Thomas Maroschik
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * This file is defined in UnitTests.xml and called by phpunit
 * before instantiating the test suites.
 */
(static function () {
	$testbase = new \TYPO3\TestingFramework\Core\Testbase();

	// These if's are for core testing (package typo3/cms) only. cms-composer-installer does
	// not create the autoload-include.php file that sets these env vars and sets composer
	// mode to true. testing-framework can not be used without composer anyway, so it is safe
	// to do this here. This way it does not matter if 'bin/phpunit' or 'vendor/phpunit/phpunit/phpunit'
	// is called to run the tests since the 'relative to entry script' path calculation within
	// SystemEnvironmentBuilder is not used. However, the binary must be called from the document
	// root since getWebRoot() uses 'getcwd()'.
	if (!getenv('TYPO3_PATH_ROOT')) {
		putenv('TYPO3_PATH_ROOT=' . rtrim($testbase->getWebRoot(), '/'));
	}
	if (!getenv('TYPO3_PATH_WEB')) {
		putenv('TYPO3_PATH_WEB=' . rtrim($testbase->getWebRoot(), '/'));
	}

	$testbase->defineSitePath();

	$requestType = \TYPO3\CMS\Core\Core\SystemEnvironmentBuilder::REQUESTTYPE_BE | \TYPO3\CMS\Core\Core\SystemEnvironmentBuilder::REQUESTTYPE_CLI;
	\TYPO3\CMS\Core\Core\SystemEnvironmentBuilder::run(0, $requestType);

	$testbase->createDirectory(\TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/typo3conf/ext');
	$testbase->createDirectory(\TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/typo3temp/assets');
	$testbase->createDirectory(\TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/typo3temp/var/tests');
	$testbase->createDirectory(\TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/typo3temp/var/transient');

	// Retrieve an instance of class loader and inject to core bootstrap
	$classLoader = require $testbase->getPackagesPath() . '/autoload.php';
	\TYPO3\CMS\Core\Core\Bootstrap::initializeClassLoader($classLoader);

	// Initialize default TYPO3_CONF_VARS
	$configurationManager = new \TYPO3\CMS\Core\Configuration\ConfigurationManager();
	$GLOBALS['TYPO3_CONF_VARS'] = $configurationManager->getDefaultConfiguration();

	$cache = new \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend(
		'core',
		new \TYPO3\CMS\Core\Cache\Backend\NullBackend('production', [])
	);
	// Set all packages to active
	$packageManager = \TYPO3\CMS\Core\Core\Bootstrap::createPackageManager(\TYPO3\CMS\Core\Package\UnitTestPackageManager::class, \TYPO3\CMS\Core\Core\Bootstrap::createPackageCache($cache));

	\TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(\TYPO3\CMS\Core\Package\PackageManager::class, $packageManager);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::setPackageManager($packageManager);

	$testbase->dumpClassLoadingInformation();

	\TYPO3\CMS\Core\Utility\GeneralUtility::purgeInstances();
})();
