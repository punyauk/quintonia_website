<?php
//

  $handle = fopen("MOTD", "r");
  $MOTD = fread($handle, filesize("MOTD"));
  fclose($handle);
  $reply = "TOPTEN|" . $MOTD . "|";
  print $reply;

?>


