// Detches single article
try {
	$overview = $nntp->getOverview(5);
	$overview = $nntp->getOverview('<Message-ID>');

	echo 'Number: ',  $overview['Number'],  "\r\n";
	echo 'Subject: ', $overview['Subject'], "\r\n";
	echo 'From: ',    $overview['From'],    "\r\n";
	echo 'Date: ',    $overview['Date'],    "\r\n";

} catch (\Exception $e) {
    // handle error
}


// Fetches multiple articles
try {
	$overview = $nntp->getOverview();
	$overview = $nntp->getOverview('5-');
	$overview = $nntp->getOverview('5-9');

	foreach ($overview as $name => $content) {
		echo 'Number: ',  $content['Number'],  "\r\n";
		echo 'Subject: ', $content['Subject'], "\r\n";
		echo 'From: ',    $content['From'],    "\r\n";
		echo 'Date: ',    $content['Date'],    "\r\n";
	}

} catch (\Exception $e) {
    // handle error
}
