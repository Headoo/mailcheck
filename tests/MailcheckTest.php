<?php
use Mailcheck\Mailcheck;

$tests = array(
"test@.com",
"test@com",
"test@gooooogle.con",
"test@gooooogle.com",
"test",
"test@google",
"test@gmail.fr",
"test@google.co",
"test@google.c",
"test@havasww.fr",
"test@havasww.org",
"test@havasww.com",
"test@hotmail.fr",
"test@25@wanadoo.fr",
"test@bnpparisbas.com:",
);

require_once(__DIR__."/../src/Mailcheck/mailcheck.php");

$mailcheck = new Mailcheck();
$mailcheck->setDebug(0);

foreach ($tests as $test) {
	$suggestion = $mailcheck->suggest($test);
	echo $test.'->'.$suggestion.PHP_EOL;
}