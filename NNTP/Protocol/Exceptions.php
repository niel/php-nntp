<?php

// This file is not loadable via PSR-4.
// It is currently loaded specifically via compposer.json, to make the exceptions available.
// They should/could be moved inte separate PSR-4 compliant files...

namespace Net\NNTP\Protocol;



// Base socket exception
class SocketException
	extends \RuntimeException {}

class SocketTimeoutException
	extends SocketException {}


// Base command related exception (for both 4xx and 5xx failures and errors)
class CommandException
	extends \RuntimeException
{
	public function __construct($message = null, $code = 0, $defaultMessage = null, \Exception $previous = null)
	{
		// Override with server response
		$text = empty($message) ? $defaultMessage : $message;

		parent::__construct($text, $code, $previous);
	}
}

// 4xx
class CommandFailureException 
	extends CommandException {}


// 40x
class ServiceFailureException
	extends CommandFailureException {}
// 400
class ServiceNotAvailableException
	extends ServiceFailureException {}
// 401
class WrongModeException
	extends ServiceFailureException {}
// 403
class InternalFailureException
	extends ServiceFailureException {}


// Base selection exception (for both 41x + 42x failures)
class SelectionException 
	extends CommandFailureException {}

// 41x
class GroupSelectionException
	extends SelectionException {}
// 411
class NoSuchGroupException
	extends GroupSelectionException {}
// 412
class NoGroupSelectedException
	extends GroupSelectionException {}


// 42x
class ArticleSelectionException
	extends SelectionException {}
// 420
class NoArticleSelectedException
	extends ArticleSelectionException {}
// 421
class NoNextArticleException
	extends ArticleSelectionException {}
// 422
class NoLastArticleException
	extends ArticleSelectionException {}
// 423
class NoSuchArticleNumberException
	extends ArticleSelectionException {}

// 43x
class DistributionException
	extends CommandFailureException {}
// 430
class NoSuchArticleIdException
	extends DistributionException {}
// 435
class TransferNotWantedException
	extends DistributionException {}
// 436... (base class for two variations)
class TransferTryLaterException
	extends DistributionException {}
// 436a
class TransferNotPossibleException
	extends TransferTryLaterException {}
// 436b
class TransferFailedException
	extends TransferTryLaterException {}
// 437
class TransferRejectedException
	extends DistributionException {}

// 44x
class PostingException
	extends CommandFailureException {}
// 440
class PostingNotPermittedException
	extends PostingException {}
// 441
class PostingFailedException
	extends PostingException {}

// 48x
class X_48x_Exception
	extends CommandFailureException {}
// 480
class AuthenticationRequiredException
	extends X_48x_Exception {}
// 483
class EncryptionRequiredException
	extends X_48x_Exception {}




// 5xx
class CommandErrorException
	extends CommandException {}

// 500
class UnknownCommandException
	extends CommandErrorException {}

// 501
class SyntaxErrorException
	extends CommandErrorException {}

// 502a
class ServicePermanentlyNotAvailableException
	extends CommandErrorException {}

// 502b
class NotPermittedException
	extends CommandErrorException {}

// 503
class NotSupportedException
	extends CommandErrorException {}

// 504
class X_504_Exception
	extends CommandErrorException {}



/*
      1xx - Informative message
      2xx - Command completed OK
      3xx - Command OK so far; send the rest of it
      4xx - Command was syntactically correct but failed for some reason
      5xx - Command unknown, unsupported, unavailable, or syntax error

   The next digit in the code indicates the function response category:

      x0x - Connection, setup, and miscellaneous messages
      x1x - Newsgroup selection
      x2x - Article selection
      x3x - Distribution functions
      x4x - Posting
      x8x - Reserved for authentication and privacy extensions
      x9x - Reserved for private use (non-standard extensions)


4xx Fail

40x Service_Fail
400 ServiceNotAvailable_Fail
401 WrongMode_Fail
403 Internal_Fail

41x GroupSelection_Fail
411 NoSuchGroup_Fail
412 NoGroupSelected_Fail

42x ArticleSelection_Fail
420 InvalidCurrentArticle_Fail
421 NoNextArticle_Fail
422 NoLastArticle_Fail
423 NoSuchArticle_Fail !!! (number/range)

43x Distribution_Fail
430 NoSuchArticle_Fail !!! (message-id)
435 TransferNotWanted_Fail
436 TransferTryLater_Fail
    - TransferNotPossible_Fail (1st state)
    - TransferFailed_Fail (2nd state)
437 TransferRejected_Fail

44x Posting_Fail
440 PostingNotPermitted
441 PostingFailed

48x
480 AuthenticationRequired
483 EncryptionRequired


5xx Error
50x Error
500 UnknownCommand_Error
501 Syntax_Error
502
503 NotSupported_Error
504

*/
