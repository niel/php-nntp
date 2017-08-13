<?php

class NoConnection
	extends PHPUnit_Framework_TestCase
{
	static $nntp;

    static function setUpBeforeClass()
    {
		self::$nntp = new \Net_NNTP_Client();
    }

    static function tearDownAfterClass()
    {
		self::$nntp = null;
    }

    /**
     * @expectedException \Exception
     */
    function test_Disconnect()
    {
		self::$nntp->disconnect();
    }

	/**
     * @depends test_Disconnect
     * @expectedException \Exception
     */
    function test_GetGroups()
    {
		self::$nntp->getGroups();
    }

    /**
     * @depends test_Disconnect
     * @expectedException \Exception
     */
    function test_GetGroupDescriptions()
    {
		self::$nntp->getDescriptions();
    }
	
    /**
     * @depends test_GetGroups
     * @expectedException \Exception
     */
    function test_SelectGroup()
    {
		self::$nntp->selectGroup('dummy');
    }
	
    /**
     * @depends test_SelectGroup
     * @expectedException \Exception
     */
    function test_SelectArticle()
    {
		self::$nntp->selectArticle('dummy');
    }
	
    /**
     * @depends test_SelectArticle
     * @expectedException \Exception
     */
    function test_getArticleHeader()
    {
		$header = self::$nntp->getHeader();
    }

    /**
     * @depends test_SelectArticle
     * @expectedException \Exception
     */
    function test_getArticleBody()
    {
		$body = self::$nntp->getBody();
    }
}
