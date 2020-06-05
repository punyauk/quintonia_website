<?php
 $filename = "recipes.txt";

$txt = $_POST['response'];
if ($txt != '')
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


