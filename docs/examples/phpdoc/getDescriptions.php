try {
	$descriptions = $nntp->getDescriptions('*.pear.*');

	foreach ($descriptions as $group => $description) {
		echo $group, ': ', $description, "\r\n";
	}
      
} catch (\Exception $e) {
    // handle error
}
