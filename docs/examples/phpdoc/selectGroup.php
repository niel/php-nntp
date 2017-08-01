try {

	$groupsummary = $nntp->selectGroup('php.pear.general');

	$group = $groupsummary['group'];
	$count = $groupsummary['count'];
	$first = $groupsummary['first'];
	$last  = $groupsummary['last'];

} catch (\Exception $e) {

	// handle error

}
