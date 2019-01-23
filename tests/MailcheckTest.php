<?php
/**
 * Unit test for Mailcheck
 */

use PHPUnit\Framework\TestCase;

class MailcheckTest extends TestCase
{
	public function suggestDataProvider()
	{
		return [
			['test@me.com', 'test@me.com'],
			['test@me.com', 'test@me.com'],
			['test@gooooogle.con', 'test@gooooogle.com'],
			['test@gooooogle.com', 'test@google.com'],
			['test', false],
			['test@google', 'test@google.com'],
			['test@gmail.fr', 'test@gmail.com'],
			['test@google.co', 'test@google.com'],
			['test@google.c', 'test@google.com'],
			['test@havasww.fr', 'test@havasww.fr'],
			['test@havasww.org', 'test@havasww.org'],
			['test@havasww.com', 'test@havasww.com'],
			['test@hotmail.fr', 'test@hotmail.com'],
			['test@25@wanadoo.fr', 'test@wanadoo.fr'],
			['test@bnpparisbas.com:', 'test@bnpparisbas.com'],
			['toto@gmail.com', 'toto@gmail.com'],
			['toto@gmailcom', 'toto@gmail.com'],
			['toto@gmaicom', 'toto@gmail.com'],
			['toto@gmaiĺcom', 'toto@gmail.com'],
			['toto@xn--gmaicom-whb', 'toto@gmail.com'],
            ['toto@hotmailcom', 'toto@hotmail.com'],
		];
	}

	/**
	 * @dataProvider suggestDataProvider
	 */
	public function testSuggest($emailInput, $emailxpected)
	{
		$mailcheck = new Mailcheck\Mailcheck();
		$mailcheck->setDebug(0);

		$this->assertEquals($emailxpected, $mailcheck->suggest($emailInput));
	}
}
