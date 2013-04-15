<?
    $data = Date('l jS \of F Y h:i:s A');
    $respositorio = "";

    $log = Array(
                 Array(
                       "command" => "git clone",
                       "output" => "$asdas dasd ada asd asdsa "
                ),

                Array(
                       "command" => "git clone",
                       "output" => "$asdas dasd ada asd asdsa "
                )
            );

?>
<meta charset="utf8" />

<div style="font-family: 'Trebuchet MS', sans-serif; color: #333;">
    <h1>Bitbucket Refresh</h1>
    <p><strong>Date:</strong> <?=$data?></p>
    <p><strong>Processes amount:</strong> <?=sizeof($log)?></p>


    <br />


    <table cellpadding="20">
        <tr style="background: #eee;">
            <th>Command</th>
            <th>Output</th>
        </tr>

        <? foreach($log as $item){ ?>
        <tr>
            <td style="border-bottom: 1px solid #ccc;">
                $ <?=$item['command']?>
            </td>
            <td style="border-bottom: 1px solid #ccc;">

                <code style="font-family: monospace;"><?=$item['output']?></code>

            </td>
        </tr>
        <? }?>

    </table>

    <br />

</div>