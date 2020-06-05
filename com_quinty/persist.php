<?php
//////////////////////////////////////////////////////////////////////////////
//    External lsl-DNS system to allow scripts to find in-world lsl servers
//////////////////////////////////////////////////////////////////////////////
//
// This is the hash of the pasword that is used when calling from lsl scripts
$hash = '$2y$12$6R155FQWKe0Z2FUZxm/.ruRF8LxAvIq5g1i5gz8eeuDFnZfRX4etu';
$PIN = '18965175';

function encrypt_decrypt($action, $string)
{
	/**
 	* simple method to encrypt or decrypt a plain text string
 	* initialization vector(IV) has to be the same when encrypting and decrypting
 	* 
 	* @param string $action: can be 'encrypt' or 'decrypt'
 	* @param string $string: string to encrypt or decrypt
	*
 	* @return string
 	*/
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $secret_key = 'This is my secret key';
    $secret_iv = 'This is my secret iv';
    // hash
    $key = hash('sha256', $secret_key);
    
    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ( $action == 'encrypt' ) {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    } else if( $action == 'decrypt' ) {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    return $output;
}

$basefilename = "urls/";    /** Our base folder **/
$msg = $_POST['slurl'];
if ($msg != '')
{
	// $msg should be in format "cmd|password|UUID|[slurl]    where cmd is SET or GET and slurl is the url to store against UUID
	$items = explode("|", $msg);
	$cmd = $items[0];
	$password =  $items[1];
    if (password_verify($password, $hash))
	{
		$uuid = $items[2];
		if ($uuid != '')
		{
			$filename = $basefilename . $uuid;
			switch ($cmd)
			{
				case "SET":
                    // Command to store the url that the lsl script was given 
					$slurl = $items[3];
        			$encrypted_slurl = encrypt_decrypt('encrypt', $slurl);
        			$handle = fopen($filename, "w") or die("Unable to open file!");
        			fwrite($handle, $encrypted_slurl);
        			fclose($handle);
        			echo "SOT|". $slurl . "|";
        			break;
        	//
        		case "GET":
                    // Command asking for the current url for requested uuid
        			$handle = fopen($filename, "r");
        			$encrypted_slurl = fread($handle, filesize($filename));
        			fclose($handle);
        			$slurl = encrypt_decrypt('decrypt', $encrypted_slurl);
        			echo "GOT|" . $slurl . "|";
        			break;
            //
                case "PIN":
                    // PIN requested for use in llRemoteLoadScriptPin functions
                    echo "PIN|" . $PIN . "|";
        	}
        }
    }
    else 
    {
    	echo 'Invalid password.';
    }
}
?>