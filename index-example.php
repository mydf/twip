<?
    require('twip.php');
    $options['webroot'] = '/twip';
    $options['debug'] = false;
    $options['dolog'] = true;
    $options['logfile'] = 'log.txt';
    $options['replace_shorturl'] = true;
    $options['docompress'] = false;
    $options['cgi_workaround'] = false; //change this to "YES I DO NEED THE WORKAROUND!" to make this work

    $twip = new twip($options);
?>