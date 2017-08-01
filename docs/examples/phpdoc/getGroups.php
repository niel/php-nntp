try {
	$groups = $nntp->getGroups('*.pear.*');

	foreach ($groups as $group) {
		echo $group['group'], ': ';
		echo $group['first'], '-', $group['last'];
		echo ' (', $group['posting'], ')', "\r\n";
	}

} catch (\Exception $e) {
    // handle error
}
