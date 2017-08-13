<?php

class Classes
	extends PHPUnit_Framework_TestCase
{
	/**
     * 
     */
    function test_Protocol_Client()
    {
		$protocol = new \Net_NNTP_Protocol_Client();
		$this->assertTrue($protocol instanceof \Net_NNTP_Protocol_Client);
    }

	/**
     * @depends test_Protocol_Client
     */
    function test_Client()
    {
		$nntp = new \Net_NNTP_Client();
		$this->assertTrue($nntp instanceof \Net_NNTP_Client);

		$this->assertFalse($nntp->isDebug());
		
		$nntp->enableDebug();
		$this->assertTrue($nntp->isDebug());
		
		$nntp->disableDebug();
		$this->assertFalse($nntp->isDebug());
    }

}
