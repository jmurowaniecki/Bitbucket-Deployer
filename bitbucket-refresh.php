<?php
class Bitbucket
{
    var $url        = 'bitbucket.org';
    var $protocol   = 'https://';
    private $username   = 'yourusername'; # you need to set your username
    private $password   = 'yourpassword'; # and your password correctly.
    var $branch     = NULL;
    var $payload    = FALSE;
    var $repository = FALSE;

    public function __construct($repository = FALSE, $branch = 'master', $payload = FALSE)
    {
        $this->configure(array(
            'repository' => $repository,
            'branch'     => $branch,
            'payload'    => $payload
        ));
        return $this;
    }

    public function send($message = FALSE, $header_code = 200, $header_message = 'OK')
    {
        header("HTTP/1.0 $header_code $header_message", TRUE, $header_code);
        print($message);
        return $this;
    }

    private function hasPayload()
    {
        return $this->payload = isset($_POST['payload'])
            ? json_decode($_POST['payload'])
            : $this->payload;
    }

    public function configure($parameters = FALSE)
    {
        if (is_array($parameters))
        {
            foreach ($parameters as $variable => $value)
            {
                $this->{$variable} = $value;
            }
        }
        return $this;
    }

    public function deploy()
    {
        if ( ! $this->hasPayload())
        {
            return $this->send(json_encode(array(
                'code'    => -1,
                'error'   => TRUE,
                'message' => 'You\'ve requested and invalid function or posted a malformed data.'
            )), 400, 'Bad Request');
        }

        if ( ! is_string($this->repository = $this->payload->repository->slug))
        {
            return $this->send(json_encode(array(
                'code'    => -2,
                'error'   => TRUE,
                'message' => 'Invalid repository.'
            )), 400, 'Bad Request');
        }

        $output = NULL;

        exec($action = is_dir("./$this->repository")
            ? "cd $this->repository; git reset --hard HEAD; git pull origin $this->branch"
            : "git clone $this->protocol$this->username:$this->password@$this->url/$this->username/$this->repository", $output);

        return $this->send(json_encode(array(
            'code'    => 1,
            'error'   => TRUE,
            'message' => "'$action' returns '$output'"
        )), 200, 'Acepted');
    }

    public function simulate($repository = FALSE)
    {
        $this->payload = new stdClass();
        $this->payload->repository = new stdClass();
        $this->payload->repository->slug = $repository
            ? $repository
            : $this->repository;
        return $this;
    }
}

$service = new Bitbucket();
$service
    ->configure(array(
        'username'   => 'yourusername',
        'password'   => 'yourpassword',
        'branch'     => 'master'
    ))
    ->deploy();
/*
 * If you want to test using some repository
$service
    ->configure(array(
        'username'   => 'yourusername',
        'password'   => 'yourpassword',
        'branch'     => 'master'
    ))
    ->simulate('repositoryname') // put here the name of your repository
    ->deploy();
 */
?>
