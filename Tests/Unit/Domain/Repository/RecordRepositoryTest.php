<?php

/***************************************************************
 *  Copyright notice
 *  (c) 2011 Fabien Udriot <fabien.udriot
 * @ecodev.ch>, Ecodev
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase for class Tx_RssOutput_Domain_Repository_RecordRepository.
 *
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @package TYPO3
 * @subpackage rss_output
 * @author Fabien Udriot <fabien.udriot@ecodev.ch>
 */
class Tx_RssOutput_Domain_Repository_RecordRepositoryTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Phpunit_Framework
	 */
	protected $testingFramework = NULL;

	/**
	 * @var Tx_RssOutput_Domain_Repository_RecordRepository
	 */
	protected $fixture;

	public function setUp() {
		$this->testingFramework = new Tx_Phpunit_Framework('tx_rssoutput');
		$this->fixture = new Tx_RssOutput_Domain_Repository_RecordRepository();
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function findFakeRecord() {
		$tableName = 'tx_rssoutput_feed';
		$recordData = array(
			'title' => 'ITEST',
			'description' => 'ITEST',
			'description' => 'ITEST',
			'number_of_items' => '10',
			'configuration' => '

			',
		);
		#$recordUid = $this->testingFramework->createRecord($tableName, $recordData);
	}
}

?>