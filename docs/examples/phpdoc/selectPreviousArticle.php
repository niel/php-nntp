try {
	$article = $nntp->selectPreviousArticle();

    // success

} catch (\Exception $e) {
    // handle error
}
