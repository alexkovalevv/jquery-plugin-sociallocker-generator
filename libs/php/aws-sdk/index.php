<?php	
	require('vendor/autoload.php');

	$s3 = new Aws\S3\S3Client([
		'version' => 'latest',
		'region' => 'eu-central-1',
		'credentials' => [
			'key' => '',
			'secret' => ''
		],
		/*'debug' => [
			'logfn' => function ($msg) {
				echo $msg . "\n";
			},
			'stream_size' => 0,
			'scrub_auth' => true,
			'http' => true,
			'auth_headers' => [
				'X-My-Secret-Header' => '[REDACTED]',
			],
			'auth_strings' => [
				'/SuperSecret=[A-Za-z0-9]{20}/i' => 'SuperSecret=[REDACTED]',
			],
		]*/
	]);

	// Perform an operation.
	$result = $s3->listBuckets();

	/*$iterator = $s3->getIterator('ListObjects', array(
		'Bucket' => 'cdn.sociallocker.ru'
	));

	foreach($iterator as $object) {
		echo $object['Key'] . "\n";
	}*/
	// Get an object using the getObject operation
	/*$result = $s3->getObject(array(
		'Bucket' => 'cdn.sociallocker.ru'
	));*/

	$result = $s3->putObject(array(
		'Bucket' => 'cdn.sociallocker.ru',
		'Key' => 'tests/test.js',
		'ContentType' => 'text/javascript',
		'Body' => 'var i = 0;',
		'ACL' => 'public-read'
	));

	// Access parts of the result object
	echo $result['Expiration'] . "\n";
	echo $result['ServerSideEncryption'] . "\n";
	echo $result['ETag'] . "\n";
	echo $result['VersionId'] . "\n";
	echo $result['RequestId'] . "\n";

	// Get the URL the object can be downloaded from
	echo $result['ObjectURL'] . "\n";