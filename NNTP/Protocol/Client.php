<?php

/**
 *
 *
 * <pre>
 * +-----------------------------------------------------------------------+
 * |                                                                       |
 * | W3C® SOFTWARE NOTICE AND LICENSE                                      |
 * | http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231   |
 * |                                                                       |
 * | This work (and included software, documentation such as READMEs,      |
 * | or other related items) is being provided by the copyright holders    |
 * | under the following license. By obtaining, using and/or copying       |
 * | this work, you (the licensee) agree that you have read, understood,   |
 * | and will comply with the following terms and conditions.              |
 * |                                                                       |
 * | Permission to copy, modify, and distribute this software and its      |
 * | documentation, with or without modification, for any purpose and      |
 * | without fee or royalty is hereby granted, provided that you include   |
 * | the following on ALL copies of the software and documentation or      |
 * | portions thereof, including modifications:                            |
 * |                                                                       |
 * | 1. The full text of this NOTICE in a location viewable to users       |
 * |    of the redistributed or derivative work.                           |
 * |                                                                       |
 * | 2. Any pre-existing intellectual property disclaimers, notices,       |
 * |    or terms and conditions. If none exist, the W3C Software Short     |
 * |    Notice should be included (hypertext is preferred, text is         |
 * |    permitted) within the body of any redistributed or derivative      |
 * |    code.                                                              |
 * |                                                                       |
 * | 3. Notice of any changes or modifications to the files, including     |
 * |    the date changes were made. (We recommend you provide URIs to      |
 * |    the location from which the code is derived.)                      |
 * |                                                                       |
 * | THIS SOFTWARE AND DOCUMENTATION IS PROVIDED "AS IS," AND COPYRIGHT    |
 * | HOLDERS MAKE NO REPRESENTATIONS OR WARRANTIES, EXPRESS OR IMPLIED,    |
 * | INCLUDING BUT NOT LIMITED TO, WARRANTIES OF MERCHANTABILITY OR        |
 * | FITNESS FOR ANY PARTICULAR PURPOSE OR THAT THE USE OF THE SOFTWARE    |
 * | OR DOCUMENTATION WILL NOT INFRINGE ANY THIRD PARTY PATENTS,           |
 * | COPYRIGHTS, TRADEMARKS OR OTHER RIGHTS.                               |
 * |                                                                       |
 * | COPYRIGHT HOLDERS WILL NOT BE LIABLE FOR ANY DIRECT, INDIRECT,        |
 * | SPECIAL OR CONSEQUENTIAL DAMAGES ARISING OUT OF ANY USE OF THE        |
 * | SOFTWARE OR DOCUMENTATION.                                            |
 * |                                                                       |
 * | The name and trademarks of copyright holders may NOT be used in       |
 * | advertising or publicity pertaining to the software without           |
 * | specific, written prior permission. Title to copyright in this        |
 * | software and any associated documentation will at all times           |
 * | remain with copyright holders.                                        |
 * |                                                                       |
 * +-----------------------------------------------------------------------+
 * </pre>
 *
 * @package    Net_NNTP
 * @author     Heino H. Gehlsen <heino@gehlsen.dk>
 * @copyright  2002-2017 Heino H. Gehlsen <heino@gehlsen.dk>. All Rights Reserved.
 * @license    http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 W3C® SOFTWARE NOTICE AND LICENSE
 * @version    GIT: $Id$
 * @link       http://pear.php.net/package/Net_NNTP
 * @see
 */

/**
 *
 */
require_once 'Net/NNTP/Protocol/Responsecode.php';


/**
 * Low level NNTP Client
 *
 * Implements the client part of the NNTP standard acording to:
 *  - RFC 977,
 *  - RFC 2980,
 *  - RFC 850/1036, and
 *  - RFC 822/2822
 *
 * Each NNTP command is represented by a method: cmd*()
 *
 * WARNING: The Net_NNTP_Protocol_Client class is considered an internal class
 *          (and should therefore currently not be extended directly outside of
 *          the Net_NNTP package). Therefore its API is NOT required to be fully
 *          stable, for as long as such changes doesn't affect the public API of
 *          the Net_NNTP_Client class, which is considered stable.
 *
 * TODO:	cmdListActiveTimes()
 *      	cmdDistribPats()
 *
 * @package    Net_NNTP
 * @author     Heino H. Gehlsen <heino@gehlsen.dk>
 * @version    package: @package_version@ (@package_state@)
 * @internal
 * @see        Net_NNTP_Client
 */
class Net_NNTP_Protocol_Client
{
    /**
     * The socket resource being used to connect to the NNTP server.
     *
     * @var resource
     * @access private
     */
    private $socket = null;

    /**
     * Contains the last received status response code and text
     *
     * @var array
     * @access private
     */
    private $currentStatusResponse = null;

    /**
     *
     *
     * @var     object
     * @access  private
     */
    private $logger = null;

    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
	{
    }

    /**
     *
     *
     * @access public
     */
    function getPackageVersion() {
	return '@package_version@';
    }

    /**
     *
     *
     * @access public
     */
    function getApiVersion() {
	return '@api_version@';
    }

    /**
     *
     *
     * @param object $logger
     *
     * @access protected
     */
    function setLogger($logger)
    {
    }

    /**
     * @deprecated
     */
    function setDebug($debug = true)
    {
		if (! $logger instanceof \Log) {
			throw new \InvalidArgumentException("Logger must be extend PEAR's Log class.");
		}
        $this->logger = $logger;
    }

    /**
     * Send command
     *
     * Send a command to the server. A carriage return / linefeed (CRLF) sequence
     * will be appended to each command string before it is sent to the IMAP server.
     *
     * @param string $cmd The command to launch, ie: "ARTICLE 1004853"
     *
     * @return int response code
     * @access private
	 * @throws
     */
    protected function sendCommand($cmd)
    {
        // NNTP/RFC977 only allows command up to 512 (-2) chars.
        if (!strlen($cmd) > 510) {
            throw new \Exception('Failed writing to socket! (Command to long - max 510 chars)');
        }

/***************************************************************************************/
/* Credit: Thanks to Brendan Coles <bcoles@gmail.com> (http://itsecuritysolutions.org) */
/*         for pointing out possibility to inject pipelined NNTP commands into pretty  */
/*         much any Net_NNTP command-sending function with user input, by appending    */
/*         a new line character followed by the injection.                             */
/***************************************************************************************/
        // Prevent new line (and possible future) characters in the NNTP commands
        // Net_NNTP does not support pipelined commands. Inserting a new line charecter
        // allows sending multiple commands and thereby making the communication between
        // NET_NNTP and the server out of sync...
        if (preg_match_all('/\r?\n/', $cmd, $matches, PREG_PATTERN_ORDER)) {
            foreach ($matches[0] as $key => $match) {
                $this->logger->debug("Illegal character in command: ". htmlentities(str_replace(array("\r","\n"), array("'Carriage Return'", "'New Line'"), $match)));
            }
            throw new \Exception("Illegal character(s) in NNTP command!", null);
        }

    	// Check if connected
    	if (!$this->isConnected()) {
            throw new \Exception('Failed to write to socket! (connection lost!)', null);
        }

    	// Send the command
    	$R = @fwrite($this->socket, $cmd . "\r\n");
        if ($R === false) {
            throw new \Exception('Failed to write to socket!', null);
        }

    	//
    	if ($this->logger && $this->logger->_isMasked(PEAR_LOG_DEBUG)) {
    	    $this->logger->debug('C: ' . $cmd);
        }

    	//
    	return $this->getStatusResponse();
    }

    /**
     * Get servers status response after a command.
     *
     * @return int Status code
     * @access private
	 * @throws
     */
    protected function getStatusResponse()
    {
    	// Retrieve a line (terminated by "\r\n") from the server.
        // RFC says max is 510, but IETF says "be liberal in what you accept"...
    	$response = @fgets($this->socket, 4096);
        if ($response === false) {
			
			//
			$meta = stream_get_meta_data($this->socket);
			if ($meta['timed_out']) {
				throw new \Exception('Connection timed out', null);
			}

			//
            throw new \Exception('Failed to read from socket...!', null);
        }

    	//
    	if ($this->logger && $this->logger->_isMasked(PEAR_LOG_DEBUG)) {
    	    $this->logger->debug('S: ' . rtrim($response, "\r\n"));
        }

    	// Trim the start of the response in case of misplased whitespace (should not be needen!!!)
    	$response = ltrim($response);

        $this->currentStatusResponse = array(
    	    	    	    	    	      (int) substr($response, 0, 3),
    	                                      (string) rtrim(substr($response, 4))
    	    	    	    	    	     );

    	//
    	return $this->currentStatusResponse[0];
    }

    /**
     * Retrieve textural data
     *
     * Get data until a line with only a '.' in it is read and return data.
     *
     * @return array Text response
     * @access private
	 * @throws
     */
    protected function getTextResponse()
    {
        $data = array();
        $line = '';

    	//
    	$debug = $this->logger && $this->logger->_isMasked(PEAR_LOG_DEBUG);

        // Continue until connection is lost
        while (!feof($this->socket)) {

            // Retrieve and append up to 1024 characters from the server.
            $received = @fgets($this->socket, 1024);

            if ($received === false) {
				
				//
				$meta = stream_get_meta_data($this->socket);
				if ($meta['timed_out']) {
					throw new \Exception('Connection timed out', null);
				}

				//
                throw new \Exception('Failed to read line from socket.', null);
    	    }

			//
            $line .= $received;

            // Continue if the line is not terminated by CRLF
            if (substr($line, -2) != "\r\n" || strlen($line) < 2) {
				
				// 
                usleep(25000);

                // 
                continue;
            }

            // Validate received line
            if (false) {
                // Lines should/may not be longer than 998+2 chars (RFC2822 2.3)
                if (strlen($line) > 1000) {
    	    	    if ($this->logger) {
    	    	    	$this->logger->notice('Max line length...');
    	    	    }
                    throw new \Exception('Invalid line received!', null);
                }
            }

            // Remove CRLF from the end of the line
            $line = substr($line, 0, -2);

            // Check if the line terminates the textresponse
            if ($line == '.') {

    	    	if ($this->logger) {
    	    	    $this->logger->debug('T: ' . $line);
    	    	}

                // return all previous lines
                return $data;
            }

            // If 1st char is '.' it's doubled (NNTP/RFC977 2.4.1)
            if (substr($line, 0, 2) == '..') {
                $line = substr($line, 1);
            }

    	    //
    	    if ($debug) {
    	    	$this->logger->debug('T: ' . $line);
    	    }

            // Add the line to the array of lines
            $data[] = $line;

            // Reset/empty $line
            $line = '';
        }

    	if ($this->logger) {
    	    $this->logger->warning('Broke out of reception loop! This souldn\'t happen unless connection has been lost?');
    	}

    	//
    	throw new \Exception('End of stream! Connection lost?', null);
    }

    /**
     *
     *
     * @access private
	 * @throws
     */
    protected function sendArticle($article)
    {
    	/* data should be in the format specified by RFC850 */

    	switch (true) {
    	case is_string($article):
    	    //
    	    @fwrite($this->socket, $article);
    	    @fwrite($this->socket, "\r\n.\r\n");

    	    //
    	    if ($this->logger && $this->logger->_isMasked(PEAR_LOG_DEBUG)) {
    	        foreach (explode("\r\n", $article) as $line) {
    		    $this->logger->debug('D: ' . $line);
    	        }
    	    	$this->logger->debug('D: .');
    	    }
	    break;

    	case is_array($article):
    	    //
    	    $header = reset($article);
    	    $body = next($article);

/* Experimental...
    	    // If header is an array, implode it.
    	    if (is_array($header)) {
    	        $header = implode("\r\n", $header) . "\r\n";
    	    }
*/

    	    // Send header (including separation line)
    	    @fwrite($this->socket, $header);
    	    @fwrite($this->socket, "\r\n");

    	    //
    	    if ($this->logger && $this->logger->_isMasked(PEAR_LOG_DEBUG)) {
    	        foreach (explode("\r\n", $header) as $line) {
    	    	    $this->logger->debug('D: ' . $line);
    	    	}
    	    }


/* Experimental...
    	    // If body is an array, implode it.
    	    if (is_array($body)) {
    	        $header = implode("\r\n", $body) . "\r\n";
    	    }
*/

    	    // Send body
    	    @fwrite($this->socket, $body);
    	    @fwrite($this->socket, "\r\n.\r\n");

    	    //
    	    if ($this->logger && $this->logger->_isMasked(PEAR_LOG_DEBUG)) {
    	        foreach (explode("\r\n", $body) as $line) {
    	    	    $this->logger->debug('D: ' . $line);
    	    	}
    	        $this->logger->debug('D: .');
    	    }
	    break;

	default:
    		throw new \InvalidArgumentException('Ups...', null);
    	}

	return true;
    }

    /**
     *
     *
     * @return string Status text
     * @access private
     */
    protected function currentStatusResponse()
    {
    	return $this->currentStatusResponse[1];
    }

    /**
     *
     *
     * @param int $code Status code number
     * @param string $text Status text
     *
     * @return mixed
     * @access private
	 * @throws
     */
    protected function handleUnexpectedResponse($code = null, $text = null)
    {
    	if ($code === null) {
    	    $code = $this->currentStatusResponse[0];
	}

    	if ($text === null) {
    	    $text = $this->currentStatusResponse();
	}

    	switch ($code) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NOT_PERMITTED: // 502, 'access restriction or permission denied' / service permanently unavailable
    	    	break;
    	    default:
				throw new \Exception('Command not permitted / Access restriction / Permission denied', $code, new \Exception($text));
    	    	throw new \UnexpectedValueException("Unexpected response: '$text'", $code, new \Exception($text));
    	}
    }

/* Session administration commands */

    /**
     * Connect to a NNTP server
     *
     * @param string	$host	(optional) The address of the NNTP-server to connect to, defaults to 'localhost'.
     * @param mixed	$encryption	(optional)
     * @param int	$port	(optional) The port number to connect to, defaults to 119.
     * @param int	$timeout	(optional)
     *
     * @return bool True when posting allowed, otherwise false
     * @access protected
	 * @throws
     */
    function connect($host = null, $encryption = null, $port = null, $timeout = null)
    {
    	//
    	    return $this->throwError('Already connected, disconnect first!', null);
    	}

    	// v1.0.x API
    	if (is_int($encryption)) {
	    trigger_error('You are using deprecated API v1.0 in Net_NNTP_Protocol_Client: connect() !', E_USER_NOTICE);
    	    $port = $encryption;
	    $encryption = false;
        if ($this->isConnected()) {
    	}

    	//
    	if (is_null($host)) {
    	    $host = 'localhost';
    	}

    	// Choose transport based on encryption, and if no port is given, use default for that encryption
    	switch ($encryption) {
	    case null:
	    case false:
		$transport = 'tcp';
    	    	$port = is_null($port) ? 119 : $port;
		break;
	    case 'ssl':
	    case 'tls':
		$transport = $encryption;
    	    	$port = is_null($port) ? 563 : $port;
		break;
	    default:
    	    	throw new \InvalidArgumentException('$encryption parameter must be either tcp, tls or ssl.', null);
    	}

    	//
    	if (is_null($timeout)) {
    	    $timeout = 15;
    	}

    	// Open Connection
    	$R = stream_socket_client($transport . '://' . $host . ':' . $port, $errno, $errstr, $timeout);
    	if ($R === false) {
    	    if ($this->logger) {
    	        $this->logger->notice("Connection to $transport://$host:$port failed.");
    	    }
    	    return $R;
    	}

    	$this->socket = $R;

    	//
    	if ($this->logger) {
    	    $this->logger->info("Connection to $transport://$host:$port has been established.");
    	}

		// Set a stream timeout for each operation
		stream_set_timeout($this->socket, $timeout);

    	// Retrive the server's initial response.
    	if (PEAR::isError($response)) {
    	    return $response;
        }
    	$response = $this->getStatusResponse();

        switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_ALLOWED: // 200, Posting allowed
    	    	// TODO: Set some variable before return

    	        return true;
    	        break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_PROHIBITED: // 201, Posting NOT allowed
    	        //
    	    	if ($this->logger) {
    	    	    $this->logger->info('Posting not allowed!');
    	    	}

	    	// TODO: Set some variable before return

    	    	return false;
    	        break;
    	    case 400:
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NOT_PERMITTED: // 502, 'access restriction or permission denied' / service permanently unavailable
    	    	break;
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
//    	    	throw new \Exception('Server refused connection', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception("Server refused connection: '".$this->currentStatusResponse()."'", $response);
//    	    	throw new \Exception('Server refused connection', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception("Server refused connection: '".$this->currentStatusResponse()."'", $response);
    	}
    }

    /**
     * alias for cmdQuit()
     *
     * @access protected
	 * @throws
     */
    function disconnect()
    {
    	return $this->cmdQuit();
    }

    /**
     * Returns servers capabilities
     *
     * @return array List of capabilities
     * @access protected
	 * @throws
     */
    function cmdCapabilities()
    {
        // tell the newsserver we want an article
        if (PEAR::isError($response)) {
            return $response;
        }
        $response = $this->sendCommand('CAPABILITIES');

    	switch ($response) {
            case NET_NNTP_PROTOCOL_RESPONSECODE_CAPABILITIES_FOLLOW: // 101, Draft: 'Capability list follows'
    	    	if (PEAR::isError($data)) {
    	    	    return $data;
    	    	}
    	    	$data = $this->getTextResponse();
    	    	return $data;
    	        break;
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	}
    }

    /**
     *
     *
     * @return bool True when posting allowed, false when posting disallowed
     * @access protected
	 * @throws
     */
    function cmdModeReader()
    {
        // tell the newsserver we want an article
        if (PEAR::isError($response)) {
            return $response;
        }
        $response = $this->sendCommand('MODE READER');

    	switch ($response) {
            case NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_ALLOWED: // 200, RFC2980: 'Hello, you can post'

	    	// TODO: Set some variable before return

    	    	return true;
    	        break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_READY_POSTING_PROHIBITED: // 201, RFC2980: 'Hello, you can't post'
    	    	if ($this->logger) {
    	    	    $this->logger->info('Posting not allowed!');
    	    	}

	    	// TODO: Set some variable before return

    	    	return false;
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NOT_PERMITTED: // 502, 'access restriction or permission denied' / service permanently unavailable
    	    	break;
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	    	throw new \Exception('Connection being closed, since service so permanently unavailable', $response, new \Exception($this->currentStatusResponse()));
    	}
    }

    /**
     * Disconnect from the NNTP server
     *
     * @return bool true
     * @access protected
	 * @throws
     */
    function cmdQuit()
    {
    	// Tell the server to close the connection
    	$response = $this->_sendCommand('QUIT');
        if (PEAR::isError($response)) {
            return $response;
    	}

        switch ($response) {
    	    case 205: // RFC977: 'closing connection - goodbye!'
    	    	// If socket is still open, close it.
    	    	if ($this->isConnected()) {
    	    	    fclose($this->socket);
    	    	}

    	    	if ($this->logger) {
    	    	    $this->logger->info('Connection closed.');
    	    	}

    	    	return true;
    	    	break;
    	    default:
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

/* */

    /**
     *
     *
     * @return bool
     * @access protected
	 * @throws
     */
    function cmdStartTLS()
    {
        if (PEAR::isError($response)) {
            return $response;
        }
        $response = $this->sendCommand('STARTTLS');

    	switch ($response) {
    	    case 382: // RFC4642: 'continue with TLS negotiation'
    	    	$encrypted = stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    	    	switch (true) {
    	    	    case $encrypted === true:
    	    	    	if ($this->logger) {
    	    	    	    $this->logger->info('TLS encryption started.');
    	    	    	}
    	    	    	return true;
    	    	    	break;
    	    	    case $encrypted === false:
    	    	    	if ($this->logger) {
    	    	    	    $this->logger->info('TLS encryption failed.');
    	    	    	}
    	    	    	break;
    	    	    case is_int($encrypted):
    	    	    	break;
    	    	    default:
    	    	    	throw new \Exception('Could not initiate TLS negotiation', $response, new \Exception($this->currentStatusResponse()));
    	    	    	throw new \Exception('', $response, new \Exception($this->currentStatusResponse()));
    	    	    	throw new \Exception('Internal error - unknown response from stream_socket_enable_crypto()', $response, new \Exception($this->currentStatusResponse()));
    	    	}
    	    	break;
    	    case 580: // RFC4642: 'can not initiate TLS negotiation'
    	    	break;
    	    default:
    	    	throw new \Exception('', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

/* Article posting and retrieval */

    /* Group and article selection */

    /**
     * Selects a news group (issue a GROUP command to the server)
     *
     * @param string $newsgroup The newsgroup name
     *
     * @return array Group info
     * @access protected
	 * @throws
     */
    function cmdGroup($newsgroup)
    {
        if (PEAR::isError($response)) {
            return $response;
        }
        $response = $this->sendCommand('GROUP '.$newsgroup);

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_GROUP_SELECTED: // 211, RFC977: 'n f l s group selected'
    	    	$response_arr = explode(' ', trim($this->currentStatusResponse()));

    	    	if ($this->logger) {
    	    	    $this->logger->info('Group selected: '.$response_arr[3]);
    	    	}

    	    	return array('group' => $response_arr[3],
    	                     'first' => $response_arr[1],
    	    	             'last'  => $response_arr[2],
    	                     'count' => $response_arr[0]);
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_GROUP: // 411, RFC977: 'no such news group'
    	    	break;
    	    default:
    	    	throw new \Exception('No such news group', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /**
     *
     *
     * @param optional string $newsgroup
     * @param optional mixed $range
     *
     * @return array
     * @access protected
	 * @throws
     */
    function cmdListgroup($newsgroup = null, $range = null)
    {
        if (is_null($newsgroup)) {
    	    $command = 'LISTGROUP';
    	} else {
    	    if (is_null($range)) {
    	        $command = 'LISTGROUP ' . $newsgroup;
    	    } else {
    	        $command = 'LISTGROUP ' . $newsgroup . ' ' . $range;
    	    }
        }

        if (PEAR::isError($response)){
            return $response;
        }
        $response = $this->sendCommand($command);

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_GROUP_SELECTED: // 211, RFC2980: 'list of article numbers follow'

    	    	$articles = $this->_getTextResponse();
    	        if (PEAR::isError($articles)) {
    	            return $articles;
    	        }

    	        $response_arr = explode(' ', trim($this->currentStatusResponse()), 4);

		// If server does not return group summary in status response, return null'ed array
    	    	if (!is_numeric($response_arr[0]) || !is_numeric($response_arr[1]) || !is_numeric($response_arr[2]) || empty($response_arr[3])) {
    	    	    return array('group'    => null,
    	        	         'first'    => null,
    	    	    	         'last'     => null,
    	    	    		 'count'    => null,
    	    	    	         'articles' => $articles);
		}

    	    	return array('group'    => $response_arr[3],
    	                     'first'    => $response_arr[1],
    	    	             'last'     => $response_arr[2],
    	    	             'count'    => $response_arr[0],
    	    	             'articles' => $articles);
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED: // 412, RFC2980: 'Not currently in newsgroup'
    	    	break;
    	    case 502: // RFC2980: 'no permission'
    	    	break;
    	    default:
    	    	throw new \Exception('Not currently in newsgroup', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No permission', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /**
     *
     *
     * @return mixed (array) or (string) or (int)
     * @access protected
	 * @throws
     */
    function cmdLast()
    {
        //
        if (PEAR::isError($response)) {
            return $response;
        }
        $response = $this->sendCommand('LAST');

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_SELECTED: // 223, RFC977: 'n a article retrieved - request text separately (n = article number, a = unique article id)'
    	    	$response_arr = explode(' ', trim($this->currentStatusResponse()));

    	    	if ($this->logger) {
    	    	    $this->logger->info('Selected previous article: ' . $response_arr[0] .' - '. $response_arr[1]);
    	    	}

    	    	return array($response_arr[0], (string) $response_arr[1]);
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED: // 412, RFC977: 'no newsgroup selected'
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED: // 420, RFC977: 'no current article has been selected'
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_PREVIOUS_ARTICLE: // 422, RFC977: 'no previous article in this group'
    	    	break;
    	    default:
    	    	return $this->_handleUnexpectedResponse($response);
    	    	throw new \Exception('No newsgroup has been selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No current article has been selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No previous article in this group', $response, new \Exception($this->currentStatusResponse()));
    	}
    }

    /**
     *
     *
     * @return mixed (array) or (string) or (int)
     * @access protected
	 * @throws
     */
    function cmdNext()
    {
        //
        if (PEAR::isError($response)) {
            return $response;
        }
        $response = $this->sendCommand('NEXT');

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_SELECTED: // 223, RFC977: 'n a article retrieved - request text separately (n = article number, a = unique article id)'
    	    	$response_arr = explode(' ', trim($this->currentStatusResponse()));

    	    	if ($this->logger) {
    	    	    $this->logger->info('Selected previous article: ' . $response_arr[0] .' - '. $response_arr[1]);
    	    	}

    	    	return array($response_arr[0], (string) $response_arr[1]);
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED: // 412, RFC977: 'no newsgroup selected'
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED: // 420, RFC977: 'no current article has been selected'
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_NEXT_ARTICLE: // 421, RFC977: 'no next article in this group'
    	    	break;
    	    default:
    	    	throw new \Exception('No newsgroup has been selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No current article has been selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No next article in this group', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /* Retrieval of articles and article sections */

    /**
     * Get an article from the currently open connection.
     *
     * @param mixed $article Either a message-id or a message-number of the article to fetch. If null or '', then use current article.
     *
     * @return array Article
     * @access protected
	 * @throws
     */
    function cmdArticle($article = null)
    {
        if (is_null($article)) {
    	    $command = 'ARTICLE';
    	} else {
            $command = 'ARTICLE ' . $article;
        }

        // tell the newsserver we want an article
        if (PEAR::isError($response)) {
            return $response;
        }
        $response = $this->sendCommand($command);

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_FOLLOWS:  // 220, RFC977: 'n <a> article retrieved - head and body follow (n = article number, <a> = message-id)'
    	    	if (PEAR::isError($data)) {
    	    	    return $data;
    	    	}
    	    	$data = $this->getTextResponse();

    	    	if ($this->logger) {
    	    	    $this->logger->info(($article == null ? 'Fetched current article' : 'Fetched article: '.$article));
    	    	}
    	    	return $data;
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED: // 412, RFC977: 'no newsgroup has been selected'
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED: // 420, RFC977: 'no current article has been selected'
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_NUMBER: // 423, RFC977: 'no such article number in this group'
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_ID: // 430, RFC977: 'no such article found'
    	    	break;
    	    default:
    	    	throw new \Exception('No newsgroup has been selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No current article has been selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No such article number in this group', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No such article found', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /**
     * Get the headers of an article from the currently open connection.
     *
     * @param mixed $article Either a message-id or a message-number of the article to fetch the headers from. If null or '', then use current article.
     *
     * @return array Headers
     * @access protected
	 * @throws
     */
    function cmdHead($article = null)
    {
        if (is_null($article)) {
    	    $command = 'HEAD';
    	} else {
            $command = 'HEAD ' . $article;
        }

        // tell the newsserver we want the header of an article
        if (PEAR::isError($response)) {
            return $response;
        }
        $response = $this->sendCommand($command);

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_HEAD_FOLLOWS:     // 221, RFC977: 'n <a> article retrieved - head follows'
    	    	if (PEAR::isError($data)) {
    	    	    return $data;
    	    	}
    	    	$data = $this->getTextResponse();

    	    	if ($this->logger) {
    	    	    $this->logger->info(($article == null ? 'Fetched current article header' : 'Fetched article header for article: '.$article));
    	    	}

    	        return $data;
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED: // 412, RFC977: 'no newsgroup has been selected'
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED: // 420, RFC977: 'no current article has been selected'
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_NUMBER: // 423, RFC977: 'no such article number in this group'
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_ID: // 430, RFC977: 'no such article found'
    	    	break;
    	    default:
    	    	throw new \Exception('No newsgroup has been selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No current article has been selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No such article number in this group', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No such article found', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /**
     * Get the body of an article from the currently open connection.
     *
     * @param mixed $article Either a message-id or a message-number of the article to fetch the body from. If null or '', then use current article.
     *
     * @return array Body
     * @access protected
	 * @throws
     */
    function cmdBody($article = null)
    {
        if (is_null($article)) {
    	    $command = 'BODY';
    	} else {
            $command = 'BODY ' . $article;
        }

        // tell the newsserver we want the body of an article
        if (PEAR::isError($response)) {
            return $response;
        }
        $response = $this->sendCommand($command);

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_BODY_FOLLOWS:     // 222, RFC977: 'n <a> article retrieved - body follows'
    	    	$data = $this->_getTextResponse();
    	    	if (PEAR::isError($data)) {
    	    	    return $data;
    	    	}

    	    	if ($this->logger) {
    	    	    $this->logger->info(($article == null ? 'Fetched current article body' : 'Fetched article body for article: '.$article));
    	    	}

    	        return $data;
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED: // 412, RFC977: 'no newsgroup has been selected'
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED: // 420, RFC977: 'no current article has been selected'
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_NUMBER: // 423, RFC977: 'no such article number in this group'
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_ID: // 430, RFC977: 'no such article found'
    	    	break;
    	    default:
    	    	throw new \Exception('No newsgroup has been selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No current article has been selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No such article number in this group', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No such article found', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /**
     *
     *
     * @param mixed $article
     *
     * @return mixed (array) or (string) or (int)
     * @access protected
	 * @throws
     */
    function cmdStat($article = null)
    {
        if (is_null($article)) {
    	    $command = 'STAT';
    	} else {
            $command = 'STAT ' . $article;
        }

        // tell the newsserver we want an article
        if (PEAR::isError($response)) {
            return $response;
        }
        $response = $this->sendCommand($command);

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_ARTICLE_SELECTED: // 223, RFC977: 'n <a> article retrieved - request text separately' (actually not documented, but copied from the ARTICLE command)
    	    	$response_arr = explode(' ', trim($this->currentStatusResponse()));

    	    	if ($this->logger) {
    	    	    $this->logger->info('Selected article: ' . $response_arr[0].' - '.$response_arr[1]);
    	    	}

    	    	return array($response_arr[0], (string) $response_arr[1]);
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED: // 412, RFC977: 'no newsgroup has been selected' (actually not documented, but copied from the ARTICLE command)
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_NUMBER: // 423, RFC977: 'no such article number in this group' (actually not documented, but copied from the ARTICLE command)
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_ID: // 430, RFC977: 'no such article found' (actually not documented, but copied from the ARTICLE command)
    	    	break;
    	    default:
    	    	throw new \Exception('No newsgroup has been selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No such article number in this group', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No such article found', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /* Article posting */

    /**
     * Post an article to a newsgroup.
     *
     * @return bool True
     * @access protected
	 * @throws
     */
    function cmdPost()
    {
        // tell the newsserver we want to post an article
    	if (PEAR::isError($response)) {
    	    return $response;
        }
    	$response = $this->sendCommand('POST');

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_SEND: // 340, RFC977: 'send article to be posted. End with <CR-LF>.<CR-LF>'
    	    	return true;
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_PROHIBITED: // 440, RFC977: 'posting not allowed'
    	    	break;
    	    default:
    	    	throw new \Exception('Posting not allowed', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}

    }

    /**
     * Post an article to a newsgroup.
     *
     * @param mixed $article (string/array)
     *
     * @return bool True
     * @access protected
	 * @throws
     */
    function cmdPost2($article)
    {
    	/* should be presented in the format specified by RFC850 */

    	//
    	$this->sendArticle($article);

    	// Retrive server's response.
    	if (PEAR::isError($response)) {
    	    return $response;
    	}
    	$response = $this->getStatusResponse();

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_SUCCESS: // 240, RFC977: 'article posted ok'
    	    	return true;
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_POSTING_FAILURE: // 441, RFC977: 'posting failed'
    	    	break;
    	    default:
    	    	throw new \Exception('Posting failed', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /**
     *
     *
     * @param string $id
     *
     * @return bool True
     * @access protected
	 * @throws
     */
    function cmdIhave($id)
    {
        // tell the newsserver we want to post an article
    	if (PEAR::isError($response)) {
    	    return $response;
        }
    	$response = $this->sendCommand('IHAVE ' . $id);

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_SEND: // 335
    	    	return true;
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_UNWANTED: // 435
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_FAILURE: // 436
    	    	break;
    	    default:
    	    	throw new \Exception('Article not wanted', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('Transfer not possible; try again later', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /**
     *
     *
     * @param mixed $article (string/array)
     *
     * @return bool True
     * @access protected
	 * @throws
     */
    function cmdIhave2($article)
    {
    	/* should be presented in the format specified by RFC850 */

    	//
    	$this->sendArticle($article);

    	// Retrive server's response.
    	if (PEAR::isError($response)) {
    	    return $response;
    	}
    	$response = $this->getStatusResponse();

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_SUCCESS: // 235
    	    	return true;
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_FAILURE: // 436
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_TRANSFER_REJECTED: // 437
    	    	break;
    	    default:
    	    	throw new \Exception('Transfer not possible; try again later', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('Transfer rejected; do not retry', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

/* Information commands */

    /**
     * Get the date from the newsserver format of returned date
     *
     * @return mixed (string) 'YYYYMMDDhhmmss' / (int) timestamp
     * @access protected
	 * @throws
     */
    function cmdDate()
    {
        if (PEAR::isError($response)){
            return $response;
        }
        $response = $this->sendCommand('DATE');

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_SERVER_DATE: // 111, RFC2980: 'YYYYMMDDhhmmss'
    	    	break;
    	    default:
    	        return $this->currentStatusResponse();
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /**
     * Returns the server's help text
     *
     * @return array Help text
     * @access protected
	 * @throws
     */
    function cmdHelp()
    {
        // tell the newsserver we want an article
        if (PEAR::isError($response)) {
            return $response;
        }
        $response = $this->sendCommand('HELP');

    	switch ($response) {
            case NET_NNTP_PROTOCOL_RESPONSECODE_HELP_FOLLOWS: // 100
    	    	if (PEAR::isError($data)) {
    	    	    return $data;
    	    	}
    	    	$data = $this->getTextResponse();
    	    	return $data;
    	        break;
    	    default:
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /**
     * Fetches a list of all newsgroups created since a specified date.
     *
     * @param int $time Last time you checked for groups (timestamp).
     * @param optional string $distributions (deprecaded in rfc draft)
     *
     * @return array Nested array with informations about existing newsgroups
     * @access protected
	 * @throws
     */
    function cmdNewgroups($time, $distributions = null)
    {
	$date = gmdate('ymd His', $time);

        if (is_null($distributions)) {
    	    $command = 'NEWGROUPS ' . $date . ' GMT';
    	} else {
    	    $command = 'NEWGROUPS ' . $date . ' GMT <' . $distributions . '>';
        }

        if (PEAR::isError($response)){
            return $response;
        }
        $response = $this->sendCommand($command);

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NEW_GROUPS_FOLLOW: // 231, REF977: 'list of new newsgroups follows'
    	    	if (PEAR::isError($data)) {
    	    	    return $data;
    	    	}
    	    	$data = $this->getTextResponse();

    	    	$groups = array();
    	    	foreach($data as $line) {
    	    	    $arr = explode(' ', trim($line));

    	    	    $group = array('group'   => $arr[0],
    	    	                   'last'    => $arr[1],
    	    	                   'first'   => $arr[2],
    	    	                   'posting' => $arr[3]);

    	    	    $groups[$group['group']] = $group;
    	    	}
    	        return $groups;



    	    default:
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /**
     *
     *
     * @param timestamp $time
     * @param mixed $newsgroups (string or array of strings)
     * @param mixed $distribution (string or array of strings)
     *
     * @return mixed
     * @access protected
	 * @throws
     */
    protected function cmdNewnews($time, $newsgroups, $distribution = null)
    {
        $date = gmdate('ymd His', $time);

    	if (is_array($newsgroups)) {
    	    $newsgroups = implode(',', $newsgroups);
    	}

        if (is_null($distribution)) {
    	    $command = 'NEWNEWS ' . $newsgroups . ' ' . $date . ' GMT';
    	} else {
    	    if (is_array($distribution)) {
    		$distribution = implode(',', $distribution);
    	    }

    	    $command = 'NEWNEWS ' . $newsgroups . ' ' . $date . ' GMT <' . $distribution . '>';
        }

	// TODO: the lenght of the request string may not exceed 510 chars

        if (PEAR::isError($response)){
            return $response;
        }
        $response = $this->sendCommand($command);

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NEW_ARTICLES_FOLLOW: // 230, RFC977: 'list of new articles by message-id follows'
    	    	$messages = array();
    	    	foreach($this->getTextResponse() as $line) {
    	    	    $messages[] = $line;
    	    	}
    	    	return $messages;
    	    	break;
    	    default:
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /* The LIST commands */

    /**
     * Fetches a list of all avaible newsgroups
     *
     * @return array Nested array with informations about existing newsgroups
     * @access protected
	 * @throws
     */
    function cmdList()
    {
        if (PEAR::isError($response)){
            return $response;
        }
        $response = $this->sendCommand('LIST');

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_GROUPS_FOLLOW: // 215, RFC977: 'list of newsgroups follows'
    	    	if (PEAR::isError($data)) {
    	    	    return $data;
    	    	}
    	    	$data = $this->getTextResponse();

    	    	$groups = array();
    	    	foreach($data as $line) {
    	    	    $arr = explode(' ', trim($line));

    	    	    $group = array('group'   => $arr[0],
    	    	                   'last'    => $arr[1],
    	    	                   'first'   => $arr[2],
    	    	                   'posting' => $arr[3]);

    	    	    $groups[$group['group']] = $group;
    	    	}
    	        return $groups;
    	    	break;
    	    default:
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /**
     * Fetches a list of all avaible newsgroups
     *
     * @param string $wildmat
     *
     * @return array Nested array with informations about existing newsgroups
     * @access protected
	 * @throws
     */
    function cmdListActive($wildmat = null)
    {
        if (is_null($wildmat)) {
    	    $command = 'LIST ACTIVE';
    	} else {
            $command = 'LIST ACTIVE ' . $wildmat;
        }

        if (PEAR::isError($response)){
            return $response;
        }
        $response = $this->sendCommand($command);

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_GROUPS_FOLLOW: // 215, RFC977: 'list of newsgroups follows'
    	    	if (PEAR::isError($data)) {
    	    	    return $data;
    	    	}
    	    	$data = $this->getTextResponse();

    	    	$groups = array();
    	    	foreach($data as $line) {
    	    	    $arr = explode(' ', trim($line));

    	    	    $group = array('group'   => $arr[0],
    	    	                   'last'    => $arr[1],
    	    	                   'first'   => $arr[2],
    	    	                   'posting' => $arr[3]);

    	    	    $groups[$group['group']] = $group;
    	    	}

    	    	if ($this->logger) {
    	    	    $this->logger->info('Fetched list of available groups');
    	    	}

    	        return $groups;
    	    	break;
    	    default:
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /**
     * Fetches a list of (all) avaible newsgroup descriptions.
     *
     * @param string $wildmat Wildmat of the groups, that is to be listed, defaults to null;
     *
     * @return array Nested array with description of existing newsgroups
     * @access protected
	 * @throws
     */
    function cmdListNewsgroups($wildmat = null)
    {
        if (is_null($wildmat)) {
    	    $command = 'LIST NEWSGROUPS';
    	} else {
            $command = 'LIST NEWSGROUPS ' . $wildmat;
        }

        if (PEAR::isError($response)){
            return $response;
        }
        $response = $this->sendCommand($command);

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_GROUPS_FOLLOW: // 215, RFC2980: 'information follows'
    	        if (PEAR::isError($data)) {
    	            return $data;
    	        }
    	    	$data = $this->getTextResponse();

    	    	$groups = array();

    	        foreach($data as $line) {
    	            if (preg_match("/^(\S+)\s+(.*)$/", ltrim($line), $matches)) {
    	    	        $groups[$matches[1]] = (string) $matches[2];
    	    	    } else {
    	    	        if ($this->logger) {
    	    	            $this->logger->warning("Received non-standard line: '$line'");
    	    	        }
    	    	    }
    	        }

    	        if ($this->logger) {
    	            $this->logger->info('Fetched group descriptions');
    	        }

    	        return $groups;
    		break;
    	    case 503: // RFC2980: 'program error, function not performed'
    	    	break;
    	    default:
    	    	throw new \Exception('Internal server error, function not performed', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

/* Article field access commands */

    /**
     * Fetch message header from message number $first until $last
     *
     * The format of the returned array is:
     * $messages[][header_name]
     *
     * @param optional string $range articles to fetch
     *
     * @return array Nested array of message and there headers
     * @access protected
	 * @throws
     */
    function cmdOver($range = null)
    {
        if (is_null($range)) {
	    $command = 'OVER';
    	} else {
    	    $command = 'OVER ' . $range;
        }

        if (PEAR::isError($response)){
            return $response;
        }
        $response = $this->sendCommand($command);

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_OVERVIEW_FOLLOWS: // 224, RFC2980: 'Overview information follows'
    	        if (PEAR::isError($data)) {
    	            return $data;
    	        }
    	    	$data = $this->getTextResponse();

    	        foreach ($data as $key => $value) {
    	            $data[$key] = explode("\t", trim($value));
    	        }

    	    	if ($this->logger) {
    	    	    $this->logger->info('Fetched overview ' . ($range == null ? 'for current article' : 'for range: '.$range));
    	    	}

    	    	return $data;
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED: // 412, RFC2980: 'No news group current selected'
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED: // 420, RFC2980: 'No article(s) selected'
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_SUCH_ARTICLE_NUMBER: // 423:, Draft27: 'No articles in that range'
    	    	break;
    	    case 502: // RFC2980: 'no permission'
    	    	break;
    	    default:
    	    	throw new \Exception('No news group current selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No article(s) selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No articles in that range', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No permission', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /**
     * Fetch message header from message number $first until $last
     *
     * The format of the returned array is:
     * $messages[message_id][header_name]
     *
     * @param optional string $range articles to fetch
     *
     * @return array Nested array of message and there headers
     * @access protected
	 * @throws
     */
    function cmdXOver($range = null)
    {
	// deprecated API (the code _is_ still in alpha state)
    	if (func_num_args() > 1 ) {
    	    die('The second parameter in cmdXOver() has been deprecated! Use x-y instead...');
        }

        if (is_null($range)) {
	    $command = 'XOVER';
    	} else {
    	    $command = 'XOVER ' . $range;
        }

        if (PEAR::isError($response)){
            return $response;
        }
        $response = $this->sendCommand($command);

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_OVERVIEW_FOLLOWS: // 224, RFC2980: 'Overview information follows'
    	        if (PEAR::isError($data)) {
    	            return $data;
    	        }
    	    	$data = $this->getTextResponse();

    	        foreach ($data as $key => $value) {
    	            $data[$key] = explode("\t", trim($value));
    	        }

    	    	if ($this->logger) {
    	    	    $this->logger->info('Fetched overview ' . ($range == null ? 'for current article' : 'for range: '.$range));
    	    	}

    	    	return $data;
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED: // 412, RFC2980: 'No news group current selected'
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED: // 420, RFC2980: 'No article(s) selected'
    	    	break;
    	    case 502: // RFC2980: 'no permission'
    	    	break;
    	    default:
    	    	throw new \Exception('No news group current selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No article(s) selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No permission', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /**
     * Returns a list of avaible headers which are send from newsserver to client for every news message
     *
     * @return array Header names
     * @access protected
	 * @throws
     */
    function cmdListOverviewFmt()
    {
        if (PEAR::isError($response)){
            return $response;
        }
    	$response = $this->sendCommand('LIST OVERVIEW.FMT');

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_GROUPS_FOLLOW: // 215, RFC2980: 'information follows'
    	        if (PEAR::isError($data)) {
    	            return $data;
    	        }
    	    	$data = $this->getTextResponse();

    	        $format = array();

    	        foreach ($data as $line) {

		    // Check if postfixed by ':full' (case-insensitive)
		    if (0 == strcasecmp(substr($line, -5, 5), ':full')) {
    	    		// ':full' is _not_ included in tag, but value set to true
    	    		$format[substr($line, 0, -5)] = true;
		    } else {
    	    		// ':' is _not_ included in tag; value set to false
    	    		$format[substr($line, 0, -1)] = false;
    	            }
    	        }

    	        if ($this->logger) {
    	            $this->logger->info('Fetched overview format');
    	        }
    	        return $format;
    	    	break;
    	    case 503: // RFC2980: 'program error, function not performed'
    	    	break;
    	    default:
    	    	throw new \Exception('Internal server error, function not performed', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /**
     *
     *
     * The format of the returned array is:
     * $messages[message_id]
     *
     * @param optional string $field
     * @param optional string $range articles to fetch
     *
     * @return array Nested array of message and there headers on success
     * @access protected
	 * @throws
     */
    function cmdXHdr($field, $range = null)
    {
        if (is_null($range)) {
	    $command = 'XHDR ' . $field;
    	} else {
    	    $command = 'XHDR ' . $field . ' ' . $range;
        }

        if (PEAR::isError($response)){
            return $response;
        }
        $response = $this->sendCommand($command);

    	switch ($response) {
    	    case 221: // 221, RFC2980: 'Header follows'
    	        if (PEAR::isError($data)) {
    	            return $data;
    	        }
    	    	$data = $this->getTextResponse();

    	    	$return = array();
    	        foreach($data as $line) {
    	    	    $line = explode(' ', trim($line), 2);
    	    	    $return[$line[0]] = $line[1];
    	        }

    	    	return $return;
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED: // 412, RFC2980: 'No news group current selected'
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED: // 420, RFC2980: 'No current article selected'
    	    	break;
    	    case 430: // 430, RFC2980: 'No such article'
    	    	break;
    	    case 502: // RFC2980: 'no permission'
    	    	break;
    	    default:
    	    	throw new \Exception('No news group current selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No current article selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No such article', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No permission', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }



















    /**
     * Fetches a list of (all) avaible newsgroup descriptions.
     * Depresated as of RFC2980.
     *
     * @param string $wildmat Wildmat of the groups, that is to be listed, defaults to '*';
     *
     * @return array Nested array with description of existing newsgroups
     * @access protected
	 * @throws
     */
    function cmdXGTitle($wildmat = '*')
    {
        if (PEAR::isError($response)){
            return $response;
        }
        $response = $this->sendCommand('XGTITLE '.$wildmat);

    	switch ($response) {
    	    case 282: // RFC2980: 'list of groups and descriptions follows'
    	        if (PEAR::isError($data)) {
    	            return $data;
    	        }
    	    	$data = $this->getTextResponse();

    	    	$groups = array();

    	        foreach($data as $line) {
    	            preg_match("/^(.*?)\s(.*?$)/", trim($line), $matches);
    	            $groups[$matches[1]] = (string) $matches[2];
    	        }

    	        return $groups;
    	    	break;

    	    case 481: // RFC2980: 'Groups and descriptions unavailable'
    	    	break;
    	    default:
    	    	throw new \Exception('Groups and descriptions unavailable', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /**
     * Fetch message references from message number $first to $last
     *
     * @param optional string $range articles to fetch
     *
     * @return array Assoc. array of message references
     * @access protected
	 * @throws
     */
    function cmdXROver($range = null)
    {
	// Warn about deprecated API (the code _is_ still in alpha state)
    	if (func_num_args() > 1 ) {
    	    die('The second parameter in cmdXROver() has been deprecated! Use x-y instead...');
    	}

        if (is_null($range)) {
    	    $command = 'XROVER';
    	} else {
    	    $command = 'XROVER ' . $range;
        }

        if (PEAR::isError($response)){
            return $response;
        }
        $response = $this->sendCommand($command);

    	switch ($response) {
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_OVERVIEW_FOLLOWS: // 224, RFC2980: 'Overview information follows'
    	        if (PEAR::isError($data)) {
    	            return $data;
    	        }
    	    	$data = $this->getTextResponse();

    	    	$return = array();
    	        foreach($data as $line) {
    	    	    $line = explode(' ', trim($line), 2);
    	    	    $return[$line[0]] = $line[1];
    	        }
    	    	return $return;
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_GROUP_SELECTED: // 412, RFC2980: 'No news group current selected'
    	    	break;
    	    case NET_NNTP_PROTOCOL_RESPONSECODE_NO_ARTICLE_SELECTED: // 420, RFC2980: 'No article(s) selected'
    	    	break;
    	    case 502: // RFC2980: 'no permission'
    	    	break;
    	    default:
    	    	throw new \Exception('No news group current selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No article(s) selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No permission', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }





    /**
     *
     *
     * @param string $field
     * @param string $range
     * @param mixed $wildmat
     *
     * @return array Nested array of message and there headers
     * @access protected
	 * @throws
     */
    function cmdXPat($field, $range, $wildmat)
    {
        if (is_array($wildmat)) {
	    $wildmat = implode(' ', $wildmat);
    	}

        if (PEAR::isError($response)){
            return $response;
        }
        $response = $this->sendCommand('XPAT ' . $field . ' ' . $range . ' ' . $wildmat);

    	switch ($response) {
    	    case 221: // 221, RFC2980: 'Header follows'
    	        if (PEAR::isError($data)) {
    	            return $data;
    	        }
    	    	$data = $this->getTextResponse();

    	    	$return = array();
    	        foreach($data as $line) {
    	    	    $line = explode(' ', trim($line), 2);
    	    	    $return[$line[0]] = $line[1];
    	        }

    	    	return $return;
    	    	break;
    	    case 430: // 430, RFC2980: 'No such article'
    	    	break;
    	    case 502: // RFC2980: 'no permission'
    	    	break;
    	    default:
    	    	throw new \Exception('No current article selected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('No permission', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /**
     * Authenticate using 'original' method
     *
     * @param string $user The username to authenticate as.
     * @param string $pass The password to authenticate with.
     *
     * @return bool True
     * @access protected
	 * @throws
     */
    function cmdAuthinfo($user, $pass)
    {
    	// Send the username
        if (PEAR::isError($response)) {
            return $response;
    	}
        $response = $this->sendCommand('AUTHINFO user '.$user);

    	// Send the password, if the server asks
    	if (($response == 381) && ($pass !== null)) {
    	    // Send the password
    	    if (PEAR::isError($response)) {
    	    	return $response;
    	    }
            $response = $this->sendCommand('AUTHINFO pass '.$pass);
    	}

        switch ($response) {
    	    case 281: // RFC2980: 'Authentication accepted'
    	    	if ($this->logger) {
    	    	    $this->logger->info("Authenticated (as user '$user')");
    	    	}

	    	// TODO: Set some variable before return

    	        return true;
    	        break;
    	    case 381: // RFC2980: 'More authentication information required'
    	        break;
    	    case 482: // RFC2980: 'Authentication rejected'
    	    	break;
    	    case 502: // RFC2980: 'No permission'
    	    	break;
//    	    case 500:
    	        throw new \Exception('Authentication uncompleted', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('Authentication rejected', $response, new \Exception($this->currentStatusResponse()));
    	    	throw new \Exception('Authentication rejected', $response, new \Exception($this->currentStatusResponse()));
//    	    case 501:
//    	    	break;
    	    default:
//    	    	throw new \Exception('Authentication failed', $response, new \Exception($this->currentStatusResponse()));
    	    	return $this->handleUnexpectedResponse($response);
    	}
    }

    /**
     * Authenticate using 'simple' method
     *
     * @param string $user The username to authenticate as.
     * @param string $pass The password to authenticate with.
     *
     * @access protected
	 * @throws
     */
    function cmdAuthinfoSimple($user, $pass)
    {
        throw new \Exception("The auth mode: 'simple' is has not been implemented yet", null);
    }

    /**
     * Authenticate using 'generic' method
     *
     * @param string $user The username to authenticate as.
     * @param string $pass The password to authenticate with.
     *
     * @return mixed (bool) true on success or (object) pear_error on failure
     * @access protected
	 * @throws
     */
    function cmdAuthinfoGeneric($user, $pass)
    {
        throw new \Exception("The auth mode: 'generic' is has not been implemented yet", null);
    }

    /**
     * Test whether we are connected or not.
     *
     * @return bool true or false
     * @access protected
     */
    function _isConnected()
    {
        return (is_resource($this->socket) && (!feof($this->socket)));
    }
}
