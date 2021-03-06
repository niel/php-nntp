<?php

/**
 * 
 * 
 * <pre>
 * +-----------------------------------------------------------------------+
 * |                                                                       |
 * | W3C� SOFTWARE NOTICE AND LICENSE                                      |
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
 * @license    http://www.w3.org/Consortium/Legal/2002/copyright-software-20021231 W3C� SOFTWARE NOTICE AND LICENSE
 * @version    GIT: $Id$
 * @link       http://pear.php.net/package/Net_NNTP
 * @see        
 *
 * @filesource
 */

namespace Net\NNTP;

/**
 * Implementation of the client side of NNTP (Network News Transfer Protocol)
 *
 * The Net_NNTP_Client class is a frontend class to the Net_NNTP_Protocol_Client class.
 *
 * @package    Net_NNTP
 * @version    package: @package_version@ (@package_state@) 
 * @see        Net_NNTP_Protocol_Client
 */
class Client
	extends Protocol\Client
{
    /**
     * Information summary about the currently selected group.
     *
     * @var array
     * 
     */
    protected $selectedGroupSummary = null;

    /**
     * 
     *
     * @var array
     * 
     * @since 1.3.0
     */
    protected $overviewFormatCache = null;

    /**
     * Constructor
     *
     * @example docs/examples/phpdoc/constructor.php
     *
     * 
     */
    public function __construct()
    {
    	parent::__construct();
    }

    /**
     * Connect to a server.
     *
     * xxx
     * 
     * @example docs/examples/phpdoc/connect.php
     *
     * @param string	$host	(optional) The hostname og IP-address of the NNTP-server to connect to, defaults to localhost.
     * @param mixed	$encryption	(optional) false|'tls'|'ssl', defaults to false.
     * @param int	$port	(optional) The port number to connect to, defaults to 119 or 563 dependng on $encryption.
     * @param int	$timeout	(optional) 
     *
     * @return bool True when posting allowed, otherwise false
     * 
     * @see Net_NNTP_Client::disconnect()
     * @see Net_NNTP_Client::authenticate()
	 * @throws
     */
    public function connect($host = null, $encryption = null, $port = null, $timeout = null)
    {
    	return parent::connect($host, $encryption, $port, $timeout);
    }

    /**
     * Disconnect from server.
     *
     * @return bool
     * 
     * @see Net_NNTP_Client::connect()
	 * @throws
     */
    public function disconnect()
    {
        return parent::disconnect();
    }

    /**
     * Deprecated alias for disconnect().
     *
     * 
     * @deprecated 
     * @ignore
	 * @throws
     */
    public function quit()
    {
        return $this->disconnect();
    }

    /**
     * Authenticate.
     *
     * xxx
     *
     * <b>Non-standard!</b><br>
     * This method uses non-standard commands, which is not part
     * of the original RFC977, but has been formalized in RFC2890.
     *
     * @example docs/examples/phpdoc/authenticate.php
     *
     * @param string	$user	The username
     * @param string	$pass	The password
     *
     * @return bool True on successful authentification
     * 
     * @see Net_NNTP_Client::connect()
	 * @throws
     */
    public function authenticate($user, $pass)
    {
        // Username is a must...
        if ($user == null) {
            throw new \InvalidArgumentException('No username supplied', null);
        }

        return $this->cmdAuthinfo($user, $pass);
    }

    /**
     * Selects a group.
     * 
     * Moves the servers 'currently selected group' pointer to the group 
     * a new group, and returns summary information about it.
     *
     * <b>Non-standard!</b><br>
     * When using the second parameter, 
     * This method uses non-standard commands, which is not part
     * of the original RFC977, but has been formalized in RFC2890.
     *
     * @example docs/examples/phpdoc/selectGroup.php
     *
     * @param string	$group	Name of the group to select
     * @param mixed	$articles	(optional) experimental! When true the article numbers is returned in 'articles'
     *
     * @return array Summary about the selected group
     * 
     * @see Net_NNTP_Client::getGroups()
     * @see Net_NNTP_Client::group()
     * @see Net_NNTP_Client::first()
     * @see Net_NNTP_Client::last()
     * @see Net_NNTP_Client::count()
	 * @throws
     */
    public function selectGroup($group, $articles = false)
    {
	// Select group (even if $articles is set, since many servers does not select groups when the listgroup command is run)
    	$summary = $this->cmdGroup($group);

    	// Store group info in the object
    	$this->selectedGroupSummary = $summary;

	// 
    	if ($articles !== false) {
    	    $summary2 = $this->cmdListgroup($group, ($articles === true ? null : $articles));

	    // Make sure the summary array is correct...
    	    if ($summary2['group'] == $group) {
    	    	$summary = $summary2;

	    // ... even if server does not include summary in status reponce.
    	    } else {
    	    	$summary['articles'] = $summary2['articles'];
    	    }
    	}
	
    	return $summary;
    }

    /**
     * Select the previous article.
     *
     * Select the previous article in current group.
     *
     * @example docs/examples/phpdoc/selectPreviousArticle.php
     *
     * @param int	$_ret	(optional) Experimental
     *
     * @return mixed <br>
     *  - (integer)	Article number, if $ret=0 (default)
     *  - (string)	Message-id, if $ret=1
     *  - (array)	Both article number and message-id, if $ret=-1
     *  - (bool)	False if no prevoius article exists
     * 
     * @see Net_NNTP_Client::selectArticle()
     * @see Net_NNTP_Client::selectNextArticle()
	 * @throws
     */
    public function selectPreviousArticle($_ret = 0)
    {
		try {
			$response = $this->cmdLast();
		} catch (\Exception $e) {
    	    return false;
		}

    	switch ($_ret) {
    	    case -1:
    	    	return array('Number' => (int) $response[0], 'Message-ID' =>  (string) $response[1]);
    	    
			case 0:
    	        return (int) $response[0];

			case 1:
    	        return (string) $response[1];
    	    
			default:
				throw new \InvalidArgumentException('ERROR');
	}
    }

    /**
     * Select the next article.
     *
     * Select the next article in current group.
     *
     * @example docs/examples/phpdoc/selectNextArticle.php
     *
     * @param int	$_ret	(optional) Experimental
     *
     * @return mixed <br>
     *  - (integer)	Article number, if $ret=0 (default)
     *  - (string)	Message-id, if $ret=1
     *  - (array)	Both article number and message-id, if $ret=-1
     *  - (bool)	False if no further articles exist
     * 
     * @see Net_NNTP_Client::selectArticle()
     * @see Net_NNTP_Client::selectPreviousArticle()
	 * @throws
     */
    public function selectNextArticle($_ret = 0)
    {
        $response = $this->cmdNext();

    	switch ($_ret) {
    	    case -1:
    	    	return array('Number' => (int) $response[0], 'Message-ID' =>  (string) $response[1]);
    	    
			case 0:
    	        return (int) $response[0];
    	    
			case 1:
    	        return (string) $response[1];
    	    
			default:
				throw new \InvalidArgumentException('ERROR');
	}
    }

    /**
     * Selects an article by article message-number.
     *
     * xxx
     *
     * @example docs/examples/phpdoc/selectArticle.php
     *
     * @param mixed	$article	The message-number (on the server) of
     *                                  the article to select as current article.
     * @param int	$_ret	(optional) Experimental
     *
     * @return mixed <br>
     *  - (integer)	Article number
     *  - (bool)	False if article doesn't exists
     * 
     * @see Net_NNTP_Client::selectNextArticle()
     * @see Net_NNTP_Client::selectPreviousArticle()
	 * @throws
     */
    public function selectArticle($article = null, $_ret = 0)
    {
        $response = $this->cmdStat($article);

    	switch ($_ret) {
    	    case -1:
    	    	return array('Number' => (int) $response[0], 'Message-ID' =>  (string) $response[1]);
    	    
			case 0:
    	        return (int) $response[0];
    	    
			case 1:
    	        return (string) $response[1];
    	    
			default:
				throw new \InvalidArgumentException('ERROR');
	}
    }

    /**
     * Fetch article into transfer object.
     *
     * Select an article based on the arguments, and return the entire
     * article (raw data).
     *
     * @example docs/examples/phpdoc/getArticle.php
     *
     * @param mixed	$article	(optional) Either the message-id or the
     *                                  message-number on the server of the
     *                                  article to fetch.
     * @param bool	$implode	(optional) When true the result array
     *                                  is imploded to a string, defaults to
     *                                  false.
     *
     * @return mixed <br>
     *  - (array)	Complete article (when $implode is false)
     *  - (string)	Complete article (when $implode is true)
     * 
     * @see Net_NNTP_Client::getHeader()
     * @see Net_NNTP_Client::getBody()
	 * @throws
     */
    public function getArticle($article = null, $implode = false)
    {
        $data = $this->cmdArticle($article);

    	if ($implode == true) {
    	    $data = implode("\r\n", $data);
    	}

    	//
    	return $data;
    }

    /**
     * Fetch article header.
     *
     * Select an article based on the arguments, and return the article
     * header (raw data).
     *
     * @example docs/examples/phpdoc/getHeader.php
     *
     * @param mixed	$article	(optional) Either message-id or message
     *                                  number of the article to fetch.
     * @param bool	$implode	(optional) When true the result array
     *                                  is imploded to a string, defaults to
     *                                  false.
     *
     * @return mixed <br>
     *  - (bool)	False if article does not exist
     *  - (array)	Header fields (when $implode is false)
     *  - (string)	Header fields (when $implode is true)
     * 
     * @see Net_NNTP_Client::getArticle()
     * @see Net_NNTP_Client::getBody()
	 * @throws
     */
    public function getHeader($article = null, $implode = false)
    {
        $data = $this->cmdHead($article);

    	if ($implode == true) {
    	    $data = implode("\r\n", $data);
    	}

    	//
    	return $data;
    }

    /**
     * Fetch article body.
     *
     * Select an article based on the arguments, and return the article
     * body (raw data).
     *
     * @example docs/examples/phpdoc/getBody.php
     *
     * @param mixed	$article	(optional) Either the message-id or the
     *                                  message-number on the server of the
     *                                  article to fetch.
     * @param bool	$implode	(optional) When true the result array
     *                                  is imploded to a string, defaults to
     *                                  false.
     *
     * @return mixed <br>
     *  - (array)	Message body (when $implode is false)
     *  - (string)	Message body (when $implode is true)
     * 
     * @see Net_NNTP_Client::getHeader()
     * @see Net_NNTP_Client::getArticle()
	 * @throws
     */
    public function getBody($article = null, $implode = false)
    {
        $data = $this->cmdBody($article);

    	if ($implode == true) {
    	    $data = implode("\r\n", $data);
    	}

    	//
    	return $data;
    }

    /**
     * Post a raw article to a number of groups.
     *
     * @example docs/examples/phpdoc/post.php
     *
     * @param mixed	$article	<br>
     *  - (string) Complete article in a ready to send format (lines terminated by LFCR etc.)
     *  - (array) First key is the article header, second key is article body - any further keys are ignored !!!
     *  - (mixed) Something 'callable' (which must return otherwise acceptable data as replacement)
     *
     * @return string	Server response

     * 
     * @ignore
	 * @throws
     */
    public function post($article)
    {
    	// Only accept $article if array or string
    	if (!is_array($article) && !is_string($article)) {
    	    throw new \InvalidArgumentException('$article is not a string or array', null);
    	}

    	// Check if server will receive an article
    	$post = $this->cmdPost();

    	// Get article data from callback function
    	if (is_callable($article)) {
    	    $article = call_user_func($article);
    	}

    	// Actually send the article
    	return $this->cmdPost2($article);
    }

    /**
     * Post an article to a number of groups - using same parameters as PHP's mail() function.
     *
     * Among the aditional headers you might think of adding could be:
     * "From: <author-email-address>", which should contain the e-mail address
     * of the author of the article.
     * Or "Organization: <org>" which contain the name of the organization
     * the post originates from.
     * Or "NNTP-Posting-Host: <ip-of-author>", which should contain the IP-address
     * of the author of the post, so the message can be traced back to him.
     *
     * @example docs/examples/phpdoc/mail.php
     *
     * @param string	$groups	The groups to post to.
     * @param string	$subject	The subject of the article.
     * @param string	$body	The body of the article.
     * @param string	$additional	(optional) Additional header fields to send.
     *
     * @return string Server response
     * 
	 * @throws
     */
    public function mail($groups, $subject, $body, $additional = null)
    {
    	// Check if server will receive an article
    	$post = $this->cmdPost();

        // Construct header
        $header  = "Newsgroups: $groups\r\n";
        $header .= "Subject: $subject\r\n";
        $header .= "X-poster: PEAR::Net_NNTP v@package_version@ (@package_state@)\r\n";
    	if ($additional !== null) {
    	    $header .= $additional;
    	}
        $header .= "\r\n";

    	// Actually send the article
    	return $this->cmdPost2(array($header, $body));
    }

    /**
     * Get the server's internal date
     *
     * <b>Non-standard!</b><br>
     * This method uses non-standard commands, which is not part
     * of the original RFC977, but has been formalized in RFC2890.
     *
     * @example docs/examples/phpdoc/getDate.php
     *
     * @param int	$format	(optional) Determines the format of returned date:
     *                           - 0: return string
     *                           - 1: return integer/timestamp
     *                           - 2: return an array('y'=>year, 'm'=>month,'d'=>day)
     *
     * @return mixed <br>
     *  - (mixed)	
     * 
	 * @throws
     */
    public function getDate($format = 1)
    {
        $date = $this->cmdDate();

    	switch ($format) {
    	    case 0:
    	        return $date;
    	    
			case 1:
    			return strtotime(substr($date, 0, 8).' '.substr($date, 8, 2).':'.substr($date, 10, 2).':'.substr($date, 12, 2));
    	    
			case 2:
    	        return array('y' => substr($date, 0, 4),
    	                     'm' => substr($date, 4, 2),
    	                     'd' => substr($date, 6, 2));
    	    
			default:
				throw new \InvalidArgumentException('ERROR');
    	}
    }

    /**
     * Get new groups since a date.
     *
     * Returns a list of groups created on the server since the specified date
     * and time.
     *
     * @example docs/examples/phpdoc/getNewGroups.php
     *
     * @param mixed	$time	<br>
     *  - (integer)	A timestamp
     *  - (string)	Somthing parseable by strtotime() like '-1 week'
     * @param string	$distributions	(optional) 
     *
     * @return array	

     * 
	 * @throws
     */
    public function getNewGroups($time, $distributions = null)
    {
    	switch (true) {
				
    	    case is_integer($time):
    	    	break;
				
    	    case is_string($time):
    	    	$time = strtotime($time);
    	    	if ($time === false) {
    	    	    throw new \InvalidArgumentException('$time could not be converted into a timestamp!', null);
    	    	}
    	    	break;
				
    	    default:
    	    	throw new \InvalidArgumentException('$time must be either a string or an integer/timestamp!', null);
    	}

    	return $this->cmdNewgroups($time, $distributions);
    }

    /**
     * Get new articles since a date.
     *
     * Returns a list of message-ids of new articles (since the specified date
     * and time) in the groups whose names match the wildmat
     *
     * @example docs/examples/phpdoc/getNewArticles.php
     *
     * @param mixed	$time	<br>
     *  - (integer)	A timestamp
     *  - (string)	Somthing parseable by strtotime() like '-1 week'
     * @param string	$groups	(optional) 
     * @param string	$distributions	(optional) 
     *
     * @return array
     * 
     * @since 1.3.0
	 * @throws
     */
    public function getNewArticles($time, $groups = '*', $distribution = null)
    {
    	switch (true) {
				
    	    case is_integer($time):
    	    	break;
				
    	    case is_string($time):
    	    	$time = strtotime($time);
				if ($time === false) {

    	    	    throw new \InvalidArgumentException('$time could not be converted into a timestamp!', null);
				}
    	    	break;
				
    	    default:
    	    	throw new \InvalidArgumentException('$time must be either a string or an integer/timestamp!', null);
    	}

    	return $this->cmdNewnews($time, $groups, $distribution);
    }

    /**
     * Fetch valid groups.
     *
     * Returns a list of valid groups (that the client is permitted to select)
     * and associated information.
     *
     * @example docs/examples/phpdoc/getGroups.php
     *
     * @return array Nested array with information about every valid group
     * 
     * @see Net_NNTP_Client::getDescriptions()
     * @see Net_NNTP_Client::selectGroup()
	 * @throws
     */
    public function getGroups($wildmat = null)
    {
    	$backup = false;

		try {
			// Get groups
    		$groups = $this->cmdListActive($wildmat);

		} catch (\Exception $e) {
    	    switch ($e->getCode()) {
    	    	
				case 500:
    	    	case 501:
    	    	    $backup = true;
		    		break;
    		
				default:
    	    	    throw $e;
    	    }
    	}
		
    	// 
    	if ($backup == true) {

    	    // 
    	    if (!is_null($wildmat)) {
    	    	throw new \Exception("The server does not support the 'LIST ACTIVE' command, and the 'LIST' command does not support the wildmat parameter!", null, null);
    	    }
	    
   	    	$groups = $this->cmdList();
	}


    	return $groups;
    }

    /**
     * Fetch all known group descriptions.
     *
     * Fetches a list of known group descriptions - including groups which
     * the client is not permitted to select.
     *
     * <b>Non-standard!</b><br>
     * This method uses non-standard commands, which is not part
     * of the original RFC977, but has been formalized in RFC2890.
     *
     * @example docs/examples/phpdoc/getDescriptions.php
     *
     * @param mixed	$wildmat	(optional) 
     *
     * @return array Associated array with descriptions of known groups
     * 
     * @see Net_NNTP_Client::getGroups()
	 * @throws
     */
    public function getDescriptions($wildmat = null)
    {
    	if (is_array($wildmat)) {
	    $wildmat = implode(',', $wildmat);
    	}

    	// Get group descriptions
    	$descriptions = $this->cmdListNewsgroups($wildmat);

    	// TODO: add xgtitle as backup
	
    	return $descriptions;
    }

    /**
     * Fetch an overview of article(s) in the currently selected group.
     *
     * Returns the contents of all the fields in the database for a number
     * of articles specified by either article-numnber range, a message-id,
     * or nothing (indicating currently selected article).
     *
     * The first 8 fields per article is always as follows:
     *   - 'Number' - '0' or the article number of the currently selected group.
     *   - 'Subject' - header content.
     *   - 'From' - header content.
     *   - 'Date' - header content.
     *   - 'Message-ID' - header content.
     *   - 'References' - header content.
     *   - ':bytes' - metadata item.
     *   - ':lines' - metadata item.
     *
     * The server may send more fields form it's database...
     *
     * <b>Non-standard!</b><br>
     * This method uses non-standard commands, which is not part
     * of the original RFC977, but has been formalized in RFC2890.
     *
     * @example docs/examples/phpdoc/getOverview.php
     *
     * @param mixed	$range	(optional)
     *                          - '<message number>'
     *                          - '<message number>-<message number>'
     *                          - '<message number>-'
     *                          - '<message-id>'
     * @param boolean	$_names	(optional) experimental parameter! Use field names as array kays
     * @param boolean	$_forceNames	(optional) experimental parameter! 
     *
     * @return array Nested array of article overview data
     * 
     * @see Net_NNTP_Client::getHeaderField()
     * @see Net_NNTP_Client::getOverviewFormat()
	 * @throws
     */
    public function getOverview($range = null, $_names = true, $_forceNames = true)
    {
    	// Fetch overview from server
    	$overview = $this->cmdXOver($range);

    	// Use field names from overview format as keys?
    	if ($_names) {

    	    // Already cached?
    	    if (is_null($this->overviewFormatCache)) {
    	    	// Fetch overview format
    	        $format = $this->getOverviewFormat($_forceNames, true);

    	    	// Prepend 'Number' field
    	    	$format = array_merge(array('Number' => false), $format);

    	    	// Cache format
    	        $this->overviewFormatCache = $format;

    	    // 
    	    } else {
    	        $format = $this->overviewFormatCache;
    	    }

    	    // Loop through all articles
            foreach ($overview as $key => $article) {

    	        // Copy $format
    	        $f = $format;

    	        // Field counter
    	        $i = 0;
		
		// Loop through forld names in format
    	        foreach ($f as $tag => $full) {

    	    	    //
    	            $f[$tag] = $article[$i++];

    	            // If prefixed by field name, remove it
    	            if ($full === true) {
	                $f[$tag] = ltrim( substr($f[$tag], strpos($f[$tag], ':') + 1), " \t");
    	            }
    	        }

    	        // Replace article 
	        $overview[$key] = $f;
    	    }
    	}

    	//
    	switch (true) {

    	    // Expect one article
			case is_null($range):
			case is_int($range):
            case is_string($range) && ctype_digit($range):
    	    case is_string($range) && substr($range, 0, 1) == '<' && substr($range, -1, 1) == '>':
    	        if (count($overview) == 0) {
    	    	    return false;
    	    	} else {
    	    	    return reset($overview);
    	    	}
    	    	break;

    	    // Expect multiple articles
    	    default:
    	    	return $overview;
    	}
    }

    /**
     * Fetch names of fields in overview database
     *
     * Returns a description of the fields in the database for which it is consistent.
     *
     * <b>Non-standard!</b><br>
     * This method uses non-standard commands, which is not part
     * of the original RFC977, but has been formalized in RFC2890.
     *
     * @example docs/examples/phpdoc/getOveriewFormat.php
     *
     * @return array Overview field names
     * 
     * @see Net_NNTP_Client::getOverview()
	 * @throws
     */
    public function getOverviewFormat($_forceNames = true, $_full = false)
    {
        $format = $this->cmdListOverviewFmt();

    	// Force name of first seven fields
    	if ($_forceNames) {
    	    array_splice($format, 0, 7);
    	    $format = array_merge(array('Subject'    => false,
    	                                'From'       => false,
    	                                'Date'       => false,
    	                                'Message-ID' => false,
    	    	                        'References' => false,
    	                                ':bytes'     => false,
    	                                ':lines'     => false), $format);
    	}

    	if ($_full) {
    	    return $format;
    	} else {
    	    return array_keys($format);
    	}
    }

    /**
     * Fetch content of a header field from message(s).
     *
     * Retreives the content of specific header field from a number of messages.
     *
     * <b>Non-standard!</b><br>
     * This method uses non-standard commands, which is not part
     * of the original RFC977, but has been formalized in RFC2890.
     *
     * @example docs/examples/phpdoc/getHeaderField.php
     *
     * @param string	$field	The name of the header field to retreive
     * @param mixed	$range	(optional)
     *                            '<message number>'
     *                            '<message number>-<message number>'
     *                            '<message number>-'
     *                            '<message-id>'
     *
     * @return array Nested array of 
     * 
     * @see Net_NNTP_Client::getOverview()
     * @see Net_NNTP_Client::getReferences()
	 * @throws
     */
    public function getHeaderField($field, $range = null)
    {
    	$fields = $this->cmdXHdr($field, $range);

    	//
    	switch (true) {

    	    // Expect one article
			case is_null($range):
			case is_int($range):
            case is_string($range) && ctype_digit($range):
    	    case is_string($range) && substr($range, 0, 1) == '<' && substr($range, -1, 1) == '>':

    	        if (count($fields) == 0) {
    	    	    return false;
    	    	} else {
    	    	    return reset($fields);
    	    	}
    	    	break;

    	    // Expect multiple articles
    	    default:
    	    	return $fields;
    	}
    }








    /**
     *
     *
     * <b>Non-standard!</b><br>
     * This method uses non-standard commands, which is not part
     * of the original RFC977, but has been formalized in RFC2890.
     *
     * @example docs/examples/phpdoc/getGroupArticles.php
     *
     * @param mixed	$range	(optional) Experimental!
     *
     * @return array
     * 
	 * @throws
     * @since 1.3.0
     */
    public function getGroupArticles($range = null)
    {
        $summary = $this->cmdListgroup();

    	// Update summary cache if group was also 'selected'
    	if ($summary['group'] !== null) {
    	    $this->selectedGroupSummary = $summary;
    	}
	
    	//
    	return $summary['articles'];
    }

    /**
     * Fetch reference header field of message(s).
     *
     * Retrieves the content of the references header field of messages via
     * either the XHDR ord the XROVER command.
     *
     * Identical to getHeaderField('References').
     *
     * <b>Non-standard!</b><br>
     * This method uses non-standard commands, which is not part
     * of the original RFC977, but has been formalized in RFC2890.
     *
     * @example docs/examples/phpdoc/getReferences.php
     *
     * @param mixed	$range	(optional)
     *                            '<message number>'
     *                            '<message number>-<message number>'
     *                            '<message number>-'
     *                            '<message-id>'
     *
     * @return array Nested array of references
     * 
     * @see Net_NNTP_Client::getHeaderField()
	 * @throws
     */
    public function getReferences($range = null)
    {
    	$backup = false;

		try {
			$references = $this->cmdXHdr('References', $range);
		} catch (\Exception $e) {
    	    switch ($e->getCode()) {
    	    	case 500:
    	    	case 501:
    	    	    $backup = true;
		    		break;
    			default:
    	    	    throw $e;
    	    }
    	}

    	if (true && (is_array($references) && count($references) == 0)) {
    	    $backup = true;
    	}

    	if ($backup == true) {
    	    $references = $this->cmdXROver($range);
	}

    	if (is_array($references)) {
    	    foreach ($references as $key => $val) {
    	        $references[$key] = preg_split("/ +/", trim($val), -1, PREG_SPLIT_NO_EMPTY);
    	    }
	}

    	//
    	switch (true) {

    	    // Expect one article
			case is_null($range):
			case is_int($range):
    	    case is_string($range) && ctype_digit($range):
    	    case is_string($range) && substr($range, 0, 1) == '<' && substr($range, -1, 1) == '>':
    	        if (count($references) == 0) {
    	    	    return false;
    	    	} else {
    	    	    return reset($references);
    	    	}
    	    	break;

    	    // Expect multiple articles
    	    default:
    	    	return $references;
    	}
    }






    /**
     * Number of articles in currently selected group
     *
     * @example docs/examples/phpdoc/count.php
     *
     * @return string The number of article in group
     * 
     * @see Net_NNTP_Client::group()
     * @see Net_NNTP_Client::first()
     * @see Net_NNTP_Client::last()
     * @see Net_NNTP_Client::selectGroup()
     * @ignore
	 * @throws
     */
    public function count()
    {
        return $this->selectedGroupSummary['count'];
    }

    /**
     * Maximum article number in currently selected group
     *
     * @example docs/examples/phpdoc/last.php
     *
     * @return string The last article's number
     * 
     * @see Net_NNTP_Client::first()
     * @see Net_NNTP_Client::group()
     * @see Net_NNTP_Client::count()
     * @see Net_NNTP_Client::selectGroup()
     * @ignore
	 * @throws
     */
    public function last()
    {
    	return $this->selectedGroupSummary['last'];
    }

    /**
     * Minimum article number in currently selected group
     *
     * @example docs/examples/phpdoc/first.php
     *
     * @return string The first article's number
     * 
     * @see Net_NNTP_Client::last()
     * @see Net_NNTP_Client::group()
     * @see Net_NNTP_Client::count()
     * @see Net_NNTP_Client::selectGroup()
     * @ignore
	 * @throws
     */
    public function first()
    {
    	return $this->selectedGroupSummary['first'];
    }

    /**
     * Currently selected group
     *
     * @example docs/examples/phpdoc/group.php
     *
     * @return string Group name
     * 
     * @see Net_NNTP_Client::first()
     * @see Net_NNTP_Client::last()
     * @see Net_NNTP_Client::count()
     * @see Net_NNTP_Client::selectGroup()
     * @ignore
	 * @throws
     */
    public function group()
    {
    	return $this->selectedGroupSummary['group'];
    }








    /**
     * Test whether a connection is currently open or closed.
     *
     * @return bool	True if connected, otherwise false
     * 
     * @see Net_NNTP_Client::connect()
     * @see Net_NNTP_Client::quit()
     * @deprecated	since v1.3.0 due to use of protected method: Net_NNTP_Protocol_Client::isConnected()
     * @ignore
	 * @throws
     */
    public function isConnected()
    {
//	throw new \Exception('You are using deprecated API v1.0 in Net_NNTP_Client: isConnected() !', null);
        return parent::isConnected();
    }
}
