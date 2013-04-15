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
    var $output     = array();

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
        if (is_string($parameters) && file_exists($parameters))
        {
            $this->autoconfigure($parameters);
        }
        elseif (is_array($parameters))
        {
            foreach ($parameters as $variable => $value)
            {
                $this->{$variable} = $value;
            }
        }
        return $this;
    }

    public function deploy($output = NULL)
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

        $output = shell_exec($action = is_dir("./$this->repository")
            ? "cd $this->repository; git reset --hard HEAD; git pull origin $this->branch"
            : "git clone $this->protocol$this->username:$this->password@$this->url/$this->username/$this->repository");

        $this->output [] = array(
            $action => $output
        );

        return $this->send(json_encode(array(
            'code'    => 1,
            'error'   => TRUE,
            'message' => 'Everything updated'
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

    private function execute($command = FALSE, $output = NULL)
    {
        exec($command, $output);
        $this->output [] = $output;
        return $this;
    }

    public function callback($script = FALSE, $output = NULL)
    {
        exec("cd $this->repository; if [ -e \"$script\" ]; then sh \"$script\"; fi", $output);
        $this->output [] = $output;
        return $this;
    }

    private function process_output($node = FALSE)
    {
        $message = array();
        $tx = $node
            ? $node
            : $this->output;

        $tx = ! is_array($tx)
            ? array($tx)
            : $tx;

        foreach ($tx as $label => $desc)
        {
            if ( ! is_numeric($label) )
            {
                $message [] = "exec> $label";
            }
            if ( ! empty($desc))
            {
                $message [] = is_array($desc)
                    ? $this->process_output($desc)
                    : $desc;
            }
        }
        $message = implode("\n", $message);
        return $node
            ? $message
            : $this->send_output($message);
    }

    private function send_output($output)
    {
        $output = (file_exists($file = 'bitbucket-refresh.log')
            ? "\n"
            : NULL) . date('Ymd His') . "\n$output\n---\n";
        file_put_contents($file, $output, FILE_APPEND);
    }

    public function log($actions = FALSE)
    {
        $this->process_output();
        return $this;
    }

    public function autoconfigure($filename = 'bitbucket-userdata.json')
    {
        $config = file_exists($filename)
            ? json_decode(file_get_contents($filename))
            : FALSE;
        foreach ($config as $item => $value)
        {
            $this->{$item} = $value;
        }
        return $this;
    }
}

$service = new Bitbucket();

// Using some json file to configure
$service
    ->autoconfigure('bitbucket-userdata.json') // or just ->configure('filename.json')
    ->simulate('joy')
    ->deploy()
    ->callback('deploy.sh')
    ->log();

// Standart/manual configuration
// $service
//     ->configure(array(
//         'username'   => 'yourusername',
//         'password'   => 'yourpassword',
//         'branch'     => 'master'
//     ))
//     ->deploy()
//     ->callback('deploy.sh')
//     ->log();

// If you want to test using some repository
// $service
//     ->configure(array(
//         'username'   => 'yourusername',
//         'password'   => 'yourpassword',
//         'branch'     => 'master'
//     ))
//     ->simulate('repositoryname') // put here the name of your repository
//     ->deploy()
//     ->log();

?>
