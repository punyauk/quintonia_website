<?php

$cmd = $_POST['task'];
$filename = $_POST['ncname'];

if (($filename == '') ||  (! file_exists($filename)))
{
  print 'FILE_NAME_ERROR: ' .$filename;
  return;
}

if ($cmd == 'VER-REQ')
{
  $file = fopen($filename, "r");
  while(! feof($filename))
  {
    $line = fgets($file);
    if (strstr($line, '@VER='))
    {
      echo $line;
      return;
    }
  }
  fclose($file);
}

else if ($cmd == 'DUMP')
{
  $handle = fopen($filename, "r");
  if ($handle)
  {
  if (stream_set_read_buffer($handle, 1) !== 1024)
  {
        // changing the buffering failed
  }
  }
  $cardinfo = fread($handle, filesize($filename));
  fclose($handle);
  print $cardinfo;
}
?>

