<?php
//
// Version 0.3.9   5 June 2020
/**
 * @package     Joomla
 * @subpackage  com_quinty
 *
 * @copyright   Copyright (C) 2019 Punya.co.uk All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// Get an instance of the controller prefixed by Quinty
$controller = JControllerLegacy::getInstance('Quinty');

// Perform the Request task
$input = JFactory::getApplication()->input;
$controller->execute($input->getCmd('task'));

// Redirect if set by the controller
$controller->redirect();
//

// AltaUserPoints API
$api_AUP = JPATH_SITE.'/components/com_altauserpoints/helper.php';
if ( file_exists($api_AUP))
{
	require_once $api_AUP;
	AltaUserPointsHelper::newpoints('sysplgaup_qfarm');
}


function prepMenu($cardname)
{
    if ($cardname == "") $filename = JPATH_SITE . '/components/com_quinty/market.inf'; else $filename = JPATH_SITE . '/components/com_quinty/' . $cardname;
    $handle = fopen($filename, "r");
    if ($handle)
    {
 		if (stream_set_read_buffer($handle, 1) !== 1024)
 		{
      		// changing the buffering failed
 		}
    $cardinfo = fread($handle, filesize($filename));
    fclose($handle);
    $cardinfo = 'MENU|' . $cardinfo;
	}
	else
	{
		$cardinfo = "READ-FAIL|";
	}
    //$cardinfo = "MENU|Biker jacket,BLT sandwich,Carpet, ...etc";
	return $cardinfo;
}

function checkUser($osid)
{
	// Checks in joomla database to see if opensim ID matches a joomla ID
	$reply = "";
	// Database connection info
	$servername = "localhost";
	$dbname = "opensim_joomla";
	$username = "opensim";
	$password = "WQQbcSkjy1s2To3t";
	$prefix = "p3e13c_";
	// Create db connection to see if user has account
	$conn = new mysqli($servername, $username, $password, $dbname);
	// Check connection
	if ($conn->connect_error)
	{
		$reply = "DISABLE|Connection failed: " . $conn->connect_error . "|";
	}
	else
	{
		//build the query
		$sql = "SELECT joomlaID FROM " . $prefix . "opensim_userrelation WHERE opensimID = '" . $osid . "'";
		//run the query, store the result in a variable called $result
		$result = $conn->query($sql);
		//Check the result
		if ($result->num_rows > 0)
		{
			 // output data of each row
			while($row = $result->fetch_assoc())
			{
				// we found the user
				$reply = "OKAY|" . $row["joomlaID"] . "|";
			}
		}
		else
		{
			$reply = "REJECT|0|";
		}
		//close the connection
		$conn->close();
	}
	return $reply;
}

function addUser($joomid, $osid)
{
	// Checks in joomla database to see if opensim ID matches a joomla ID
	$reply = "INIT-VALUE";
	// Database connection info
	$servername = "localhost";
	$dbname = "opensim_joomla";
	$username = "opensim";
	$password = "WQQbcSkjy1s2To3t";
	$prefix = "p3e13c_";
	// Create db connection to see if user has account
	$conn = new mysqli($servername, $username, $password, $dbname);
	// Check connection
	if ($conn->connect_error)
	{
		$reply = "DISABLE|Connection failed: " . $conn->connect_error . "|";
	}
	else
	{
		//build the query
		$sql = "SELECT joomlaID FROM " . $prefix . "opensim_userrelation WHERE opensimID = '" . $osid . "'";
		//run the query
		$result = $conn->query($sql);
		//Check the result
		if ($result->num_rows > 0)
		{
			 // output data of each row
			while($row = $result->fetch_assoc())
			{
				// User already linked
				$reply = "LINKED|0" . "|";
			}
		}
		else
		{
			// user relationship doesn't exist so first check joomla user exists
			$user = JFactory::getUser($joomid);
			if (@$user->id == 0)
			{
				// the joomla user doesn't exist
				$reply = "LINKED|-1" . "|INVALID-J|" . $joomid . "|";
			}
			else
			{
				// joomla user exists - now check that user not linked to different avatar
				$sql = "SELECT opensimID FROM " . $prefix . "opensim_userrelation WHERE joomlaID = '" . $joomid . "'";
				//run the query
		    	$result = $conn->query($sql);
		    	if ($result->num_rows == 0)
		    	{
		    		// all okay, not another user, so build query to link joomla account to opensim account
					$sql = "INSERT INTO " . $prefix . "opensim_userrelation (joomlaID, opensimID) VALUES ('" . $joomid . "', '" . $osid . "')";
					// run the query and get result
					if ($conn->query($sql) == TRUE)
					{
						// Add them to the 'Non-Residents' group (id 12)
						JUserHelper::addUserToGroup($user->get('id'), 12 );
						// Remove them from the 'New users' group
						JUserHelper::removeUserFromGroup($user->get('id'), 14 );
						// reload the user object so the user is connected with its new access rights
						$session = JFactory::getSession();
						$session->set('user', new JUser($user->get('id')));
						// Send response back to opensim
						$reply = "LINKED|1" . "|" . $user->name;
					}
					else
					{
						$reply = "LINKED|-1" . "|INVALID-Q";
					}
				}
				else
				{
					// Joomla user already linked to a different avatar
					$reply = "LINKED|-1" . "|INVALID-E";
				}
			}
		}
	}
	//close the connection
	$conn->close();
	return $reply;
}

function deleteUser($osid)
{
	// Checks in joomla database to see if opensim ID matches a joomla ID
	$reply = "INIT-VALUE";
	// Database connection info
	$servername = "localhost";
	$dbname = "opensim_joomla";
	$username = "opensim";
	$password = "WQQbcSkjy1s2To3t";
	$prefix = "p3e13c_";
	// Create db connection to see if user has account
	$conn = new mysqli($servername, $username, $password, $dbname);
	// Check connection
	if ($conn->connect_error)
	{
		$reply = "DISABLE|Connection failed: " . $conn->connect_error . "|";
	}
	else
	{
		//build the query
		$sql = "SELECT joomlaID FROM " . $prefix . "opensim_userrelation WHERE opensimID = '" . $osid . "'";
		//run the query
		$result = $conn->query($sql);
		//Check the result
		if ($result->num_rows > 0)
		{
			 // output data of each row
			while($row = $result->fetch_assoc())
			{
				$joomid = $row["joomlaID"];
				// User link relationship found
				$user = JFactory::getUser($joomid);
				if (@$user->id == 0)
				{
					// the joomla user doesn't exist
					$reply = "UNLINKED|-1" . "|INVALID-J|" . $joomid . "|";
				}
				else
				{
    			// all okay so build query to delete the  opensim-joomla account relationship entry from the db
					$sql = "DELETE FROM " . $prefix . "opensim_userrelation WHERE opensimID = '" . $osid . "'";
					// run the query and get result
					if ($conn->query($sql) == TRUE)
					{
						// Add them back into the 'New users' group (id 12)
						JUserHelper::addUserToGroup($user->get('id'), 14);
						// Remove them from the 'Non-Residents' group
						JUserHelper::removeUserFromGroup($user->get('id'), 12);
						// Also remove them from the 'Residents' group in case it was a Quintonia grid account
						JUserHelper::removeUserFromGroup($user->get('id'), 11);
						// reload the user object so the user is connected with its new access rights
						$session = JFactory::getSession();
						$session->set('user', new JUser($user->get('id')));
						// Send response back to opensim
						$reply = "UNLINKED|1" . "|" . $user->name;
					}
					else
				  {
						$reply = "UNLINKED|-1" . "|INVALID-Q";
					}
				}
			}
		}
		else
		{
			// Joomla user already linked to a different avatar
			$reply = "UNLINKED|-1" . "|INVALID-E";
		}
	}
	//close the connection
	$conn->close();
	return $reply;
}

function getURL($user, $sizex, $sizey)
{
		$user = KunenaFactory::getUser($user);
		$avatar = $user->avatar;
		$config = KunenaFactory::getConfig();
		$path = KPATH_MEDIA . "/avatars";
		$origPath = "{$path}/{$avatar}";
		if (!is_file($origPath)) {
				// If avatar does not exist use default image.
				if ($sizex <= 90) {
						$avatar = 's_nophoto.png';
				} else {
						$avatar = 'nophoto.png';
				}
				// Search from the template.
				$template = KunenaFactory::getTemplate();
				$origPath = JPATH_SITE . '/' . $template->getAvatarPath($avatar);
				$avatar = $template->name . '/' . $avatar;
		}
		$dir = dirname($avatar);
		$file = basename($avatar);
		if ($sizex == $sizey) {
				$resized = "resized/size{$sizex}/{$dir}";
		} else {
				$resized = "resized/size{$sizex}x{$sizey}/{$dir}";
		}
		// TODO: make timestamp configurable?
		$timestamp = '';
		if (!is_file("{$path}/{$resized}/{$file}")) {
				KunenaImageHelper::version($origPath, "{$path}/{$resized}", $file, $sizex, $sizey, intval($config->avatarquality), KunenaImage::SCALE_INSIDE, intval($config->avatarcrop));
				$timestamp = '?' . round(microtime(true));
		}
		return KURL_MEDIA . "avatars/{$resized}/{$file}{$timestamp}";
}

function topTen()
{
  // Database connection info
  $servername = "localhost";
  $dbname = "opensim_joomla";
  $username = "opensim";
  $password = "WQQbcSkjy1s2To3t";
  $prefix = "p3e13c_";

  // Read the MOTD file
	$filename = JPATH_SITE . '/components/com_quinty/MOTD';
	$handle = fopen($filename, "r");
  $MOTD = fread($handle, filesize($filename));
	$reply = "TOPTEN|" . $MOTD . "|";
  fclose($handle);

  // Create db connection to see if user has account
  $conn = new mysqli($servername, $username, $password, $dbname);
  // Check connection
  if ($conn->connect_error)
  {
    $reply = "DISABLE|Connection failed: " . $conn->connect_error . "|";
  }
  else
  {
    //build the query
    $sql = "SELECT userid, points FROM " . $prefix . "alpha_userpoints ORDER BY points DESC LIMIT 10";
    //run the query, store the result in a variable called $result
    $result = $conn->query($sql);
    if ($result->num_rows > 0)
    {
      // Associative array
      while($row = $result -> fetch_assoc())
      {
        $points = $row["points"];
        $userid = $row["userid"];
				$user = JFactory::getUser($userid);
				$name = $user->name;
				$profil = AltaUserPointsHelper::getUserInfo('', $userid);
				//
				$avatar = getURL($userid, "36", "36");
//		$avatar = "";
				$reply = $reply . $name . "|" . $points . "|" . $avatar . "|";
      }
      // Free result sets
      $result -> free_result();
    }
    else
    {
      $reply = "TENFAIL|";
    }
  }
  //close the connection
  $conn->close();
  return $reply;
}

function sim_com($cmd, $data1, $data2, $data3)
{
	// Define variable for our response back to lsl
	$response = "";
	// data is  $farmValues array('task'   'data1'   'data2'   'data3')
	switch ($cmd)
	{
		case "activq327":
			// Okay to activate the seller
			$response = "2017053016xR|OK|";
			break;
		//
		case "getmenu":
			// Check if they have a joomla account
			$jid = explode("|", checkUser($data1));
			if ($jid[0] == "OKAY")
			{
				// Send them the menu of what we will buy
				$response = prepMenu($data2);
			}
			else
			{
				$response = "REJECT|0|";
			}
			break;
		//
		case "sold":
			// Transaction okay so credit them points
			// First get their joomla ID from opensim ID
			$jid = explode("|", checkUser($data1));
			if ($jid[0] == "OKAY")
			{
				// Valid Joomla user so get their AUP ID
				$user = JFactory::getUser($jid[1]);
				$userid = $user->id ;
				$profil = AltaUserPointsHelper::getUserInfo ( '', $user->id );
				$aupid = $profil->referreid;
				if ($aupid)
				{
					$transaction = "Quintonia Exchange - ";
					// we found the user so credit $data3 points
					$transaction = $transaction . $data2;
					$pointval = $data3;
					if ($pointval != "")
					{
						//  AltaUserPointsHelper::userpoints ( $plugin_function, $referrerid, $keyreference, $datareference, $randompoints, $feedback, $force, $frontmessage );
						AltaUserPointsHelper::newpoints('sysplgaup_qfarm', $aupid, '', $transaction, $pointval);
						$response = "PLUSQP|" . $pointid ."|" . $pointval . "|";
					}
				}
			}
			else
			{
				$response = implode("|", $jid);
			}
			break;
		//
		case "points":
      // First get their joomla ID from opensim ID
      $jid = explode("|", checkUser($data1));
      if ($jid[0] == "OKAY")
      {
        $user = JFactory::getUser($jid[1]);
        $userid = $user->id;
        $profil = AltaUserPointsHelper::getUserInfo('', $userid);
        $points = $profil->points;
        $rankinfo = AltaUserPointsHelper::getUserRank('', $userid);
        $rankname = $rankinfo->rank;
        $response = "POINTTALLY|" . $points ."|" . $rankname ."|";
      }
      else
      {
              $response = implode("|", $jid);
      }
      break;
    //
		case "topten":
			// Call the top 10 function, no data needed
			$response = topTen();
			break;
		//
		case "adduser":
		case "test":
  		// Link opensim account to joomla account
  		echo addUser($data1, $data2);
  		break;
   	//
		case "deluser":
			// Un-link opensim account from joomla account
			echo deleteUser($data1);
			break;
		//
		//
	}
	//
	if ($response != "")
	{
		echo $response;
	}
}


//
// Entry point //
//
// Get up array with the data sent to us via Post
	$farmValues['task']  = $input->post->get(task);
	$farmValues['data1'] = $input->post->get(data1);
	$farmValues['data2'] = $input->post->get(data2);
	$farmValues['data3'] = $input->post->get(data3);

	if ($farmValues['task'] != '') sim_com($farmValues['task'], $farmValues['data1'], $farmValues['data2'], $farmValues['data3']);

?>
