try {
	$article = $nntp->selectArticle(5);

	// success

} catch (\Exception $e) {
    // handle error
}
