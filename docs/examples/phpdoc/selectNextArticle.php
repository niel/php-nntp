try {
	$article = $nntp->selectNextArticle();

    // success

} catch (\Exception $e) {
    // handle error
}
