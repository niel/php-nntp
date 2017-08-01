try {
	$nntp->authenticate('somebody', 'secret');

    // success

} catch (\Exception $e) {
    // handle error
}
