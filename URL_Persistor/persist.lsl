
string serverURL = "https://www.quintonia.net/farm/persist.php/";
string password = "R8hG5aE-k5619GxPee3";
string UUID = "2f94157e-a261-11e9-a2a3-2a2ae2dbcce4";    // A unique key linked to one in-world service.  Use different numbers for different services 
key req_url = NULL_KEY;
string surl;

postMessage(string msg)
{
    req_url = llHTTPRequest( serverURL, [HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"], msg);
}

RequestHTTPInURL()
{
    // Release any existing
    llReleaseURL(req_url);
    //Request a new lsl url
    osRequestURL ([ "allowXss" ]);
}


default 
{
    on_rez(integer num)
    {
        llResetScript();
    }
    
    state_entry()
    {
        RequestHTTPInURL();
    }
     
     touch_start(integer num)
     {
        llOwnerSay("TOUCHED SO SENDING A GET");
        postMessage("slurl=GET|" + password +  "|" + UUID);   //Post message to server to ask for the in-world server script  url
     }
 
    http_request(key id, string method, string body)
    {
        
        // If we got a new url, save it to the website so it knows how to talk to us
        if (method == URL_REQUEST_GRANTED)
        {
            llOwnerSay("url granted so sending to persist.php");            
            postMessage("slurl=SET|" + password +  "|" + UUID + "|" + body); // body is the url we have been given
        }
        else if (method == "POST" )
        {
            llOwnerSay("Got a POST message from the server: " + method + " : " + body);
        }
        else
        {
            llOwnerSay("Got SOMETHING ELSE from the server: " + method + " : " + body);
        }
    }
 
    http_response(key request_id, integer status, list metadata, string body)
    {
        if (request_id != req_url)
        {
            llOwnerSay("http_response UNKNOWN?");
        }
        else
        {
            list tk = llParseStringKeepNulls(body, ["|"], []);
            string reply = llList2String(tk,0);
            llOwnerSay("BODY is: " + body + "Parsed to give REPLY " + reply);
            if (reply == "SOT")
            {
                // Confirmation that the url we sent was set
            }
            else if (reply == "GOT")
            {
                surl = llList2String(tk,1);
                llOwnerSay("Server url is:" + surl);
            }
        }
    }

 
}
