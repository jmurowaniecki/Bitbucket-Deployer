<?php

$default_url = 'bitbucket.org';
$username = 'yourusername';
$password = 'yourpassword';

# check if requisition is ok
if ( ! isset($_POST['payload']))
{
	die('sorry, I didn\'t understand your requisition. ):');
}

$payload = json_decode($_POST['payload']);

file_put_contents('bitbucket.log', print_r($payload, TRUE) . "\n---\n\n");

$bitbucket = array(
	$default_url,
	$username,
	$project = $payload->repository->slug
);

# check if folder exists
file_put_contents('bitbucket.log', print_r($bitbucket, TRUE) . "\n");

$action = is_dir("./$project")
	? "cd $project; git reset --hard HEAD; git pull origin master"
	: "git clone https://$username:$password@" . implode('/', $bitbucket);

# clone of pull (:
$output = '';
exec($action, $output);
file_put_contents('bitbucket.log', $output . "\n---\n\n");
# thanks
die('(:');
?>
