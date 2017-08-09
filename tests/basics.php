<?php

class DependencyFailureTest
	extends PHPUnit_Framework_TestCase
{
	
    function test_Create()
    {
		$this->nntp = new Net_NNTP_Client();
    }

    /**
     * @depends test_Create
     */
    function test_Connect()
    {
		$nntp = new Net_NNTP_Client();
		
		$posting = $nntp->connect('news.php.net');
    }

    /**
     * @depends test_Connect
     */
//    function test_Disconnect()
//    {
//		$nntp = new Net_NNTP_Client();
//		
//		$nntp->disconnect();
//    }

    /**
     * @depends test_Connect
     */
    function test_GetGroups()
    {
		$nntp = new Net_NNTP_Client();
		$posting = $nntp->connect('news.php.net');
		
		$groups = $nntp->getGroups('php.pear');
    }

    /**
     * @depends test_GetGroups
     */
    function test_GetGroupDescriptions()
    {
		$nntp = new Net_NNTP_Client();
		$posting = $nntp->connect('news.php.net');
		$groups = $nntp->getGroups('php.pear');
		
		$descriptions = $nntp->getDescriptions('php.pear');
    }
	
    /**
     * @depends test_GetGroups
     */
    function test_SelectGroup()
    {
		$nntp = new Net_NNTP_Client();
		$posting = $nntp->connect('news.php.net');
		
		$summary = $nntp->selectGroup('php.pear');
    }
	
    /**
     * @depends test_GetGroups
     */
    function test_SelectLastArticle()
    {
		$nntp = new Net_NNTP_Client();
		$posting = $nntp->connect('news.php.net');
		$summary = $nntp->selectGroup('php.pear');
		
		$article = $nntp->selectArticle($nntp->last());
    }
	
    /**
     * @depends test_SelectLastArticle
     */
    function test_getHeader()
    {
		$nntp = new Net_NNTP_Client();
		$posting = $nntp->connect('news.php.net');
		$summary = $nntp->selectGroup('php.pear');
		$article = $nntp->selectArticle($nntp->last());

		$header = $nntp->getHeader();
    }

	/**
     * @depends test_SelectLastArticle
     */
    function test_getBody()
    {
		$nntp = new Net_NNTP_Client();
		$posting = $nntp->connect('news.php.net');
		$summary = $nntp->selectGroup('php.pear');
		$article = $nntp->selectArticle($nntp->last());

		$body = $nntp->getBody();
    }
}
