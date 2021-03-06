<?php
class Bitbucket
{
    var $url          = 'bitbucket.org';
    var $protocol     = 'https://';
    private $username = 'yourusername'; # you need to set your username
    private $password = 'yourpassword'; # and your password correctly.
    var $branch       = NULL;
    var $payload      = FALSE;
    var $repository   = FALSE;
    var $output       = array();
    var $mail         = FALSE;

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
        $this->mail = new stdClass();

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

        $this->output[$action] = $output;

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

    private function execute($command = FALSE)
    {
        $this->output[$command] = shell_exec($command);
        return $this;
    }

    public function callback($script = FALSE)
    {
        $output = shell_exec($command = "cd $this->repository; if [ -e \"$script\" ]; then sh \"$script\"; fi");
        $this->output[$command] = $output;
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
        $this->output = $output = (file_exists($file = 'bitbucket-refresh.log')
            ? "\n"
            : NULL) . date('Ymd His') . "\n$output\n---\n";
        file_put_contents($file, $output, FILE_APPEND);
    }

    public function log($actions = FALSE)
    {
        $this->process_output();
        return $this;
    }

    public function mail($emails = FALSE, $subject = FALSE, $from = FALSE, $template = FALSE)
    {
        foreach (array('emails', 'subject', 'from', 'template') as $field)
        {
            $this->mail->{$field} = $$field = $$field
                ? $$field
                : $this->mail->{$field};
        }
        $to = is_array($emails)
            ? implode(', ', $emails)
            : ( is_string($emails)
                ? $emails
                : FALSE );
        $fields = array(
            'date'       => date('H:i:s d/m/Y'),
            'repository' => $this->repository,
            'commands'   => $this->output
        );
        $message = $template = file_get_contents($template);

        foreach ($fields as $field => $value)
        {
            if ( ! is_array($value))
            {
                $message = str_replace('{{' . "$field}}", $value, $message);
            }
            else if (strpos($message, '{{' . "$field}}") && strpos($message, '{{/' . "$field}}"))
            {
                $part   = explode('{{' . "$field}}", $message);
                $block  = explode('{{/' . "$field}}", $part[1]);
                $part   = array($part[0], $block[1]);
                $block  = $block[0];
                $blocks = array();

                foreach ($value as $command => $output)
                {
                    $blocks [] = str_replace(array('{{command}}', '{{output}}'), array($command, str_replace("\n", "<br />", $output)), $block);
                }
                $blocks  = preg_replace('/\x1B\[[0-9]*m/s', '', $blocks);
                $message = $part[0] . implode("\n", $blocks) . $part[1];
            }
        }
        mail($to, $subject, $message, "MIME-Version: 1.0\r\n" .
            "Content-type: text/html; charset=utf-8\r\n" .
            "To: $to\r\nFrom: $from\r\n");
        return $this;
    }

    public function autoconfigure($filename = 'bitbucket-userdata.json')
    {
        $config = file_exists($filename)
            ? json_decode(file_get_contents($filename))
            : FALSE;
        foreach ($config as $item => $value)
        {
            if (is_array($value))
            {
                $this->{$item} = new stdClass();
                foreach ($value as $sub_item => $sub_val)
                {
                    $this->{$item}->{$sub_item} = $sub_val;
                }
            }
            else
            {
                $this->{$item} = $value;
            }
        }
        return $this;
    }
}

$service = new Bitbucket();
$service
    ->autoconfigure()
    ->deploy()
    ->callback('deploy.sh')
    ->mail()
    ->log();
?>
