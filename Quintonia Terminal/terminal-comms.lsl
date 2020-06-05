// terminal-comms.lsl
//
// Allows user to link their existing avatar from any grid, to a Quintonia Joomla web account
//

float version = 1.5;    // 24 February 2020

string TXT_TALKING_TO_SERVER = "Talking to server...";

string BASEURL="https://quintonia.net/index.php?option=com_quinty&format=raw&";

string llD_register = "Registration";
string llD_Click    = "\nLog in to your Quintonia account and select the 'In-World account' option from the User menu\nhttps://quintonia.net/account-worlds\n
                       \nCopy the code from the left hand 'Link account' box, then click the Registration button below and paste in the code.";

integer dialogChannel;
key farmHTTP = NULL_KEY;
key userToPay;
string code;    // The activation code we give them
vector RED      = <1.000, 0.255, 0.212>;
vector PURPLE   = <0.694, 0.051, 0.788>;


integer chan(key u)
{
    return -1 - (integer)("0x" + llGetSubString( (string) u, -6, -1) )-393;
}

integer listener = -1;
integer listenTs;

startListen()
{
    if (listener<0)
    {
        listener = llListen(chan(llGetKey()), "", "", "");
        listenTs = llGetUnixTime();
    }
}

checkListen()
{
    if (listener > 0 && llGetUnixTime() - listenTs > 300)
    {
        llListenRemove(listener);
        listener = -1;
    }
}

refresh()
{
    llListenRemove(listener);
    listener = -1;
}

postMessage(string msg)
{
    farmHTTP = llHTTPRequest( BASEURL, [HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"], msg);
}


floatText(string msg, vector colour)
{
    llMessageLinked(LINK_SET, 1 , "TEXT|" + msg + "|" + (string)colour + "|", NULL_KEY);
}



// --- STATE DEFAULT -- //

default
{

    state_entry()
    {
        listener=-1;
    }

    link_message(integer sender_num, integer num, string msg, key id)
    {
        list tk = llParseStringKeepNulls(msg, ["|"], []);
        string cmd = llList2String(tk,0);

        if(cmd == "CMD_LINKACC")
        {
            userToPay = id;
            listener = llListen( dialogChannel, "", "", "");
            llDialog(id, llD_Click, [llD_register, "CLOSE"], dialogChannel);
        }
        else if (cmd == "CMD_UNLINKACC")
        {
            userToPay = id;
            postMessage("task=deluser&data1="+(string)userToPay);
            floatText(TXT_TALKING_TO_SERVER, PURPLE);
            refresh();
        }
    }


    timer()
    {
        refresh();
        llSetTimerEvent(600);
        checkListen();
    }


    listen(integer c, string nm, key id, string m)
    {
        if (m == "CLOSE")
        {
            refresh();
            return;
        }
        else if (m == llD_register)
        {
            refresh();
            listener = llListen( dialogChannel, "", "", "");
            llTextBox(id, "\nEnter your activation code from the website\n", dialogChannel);
        }
        else
        {
            // code should be xxxx-yyy   where yyy is joomlaID
            if (llStringLength(m) < 8)
            {
                //ERROR
                floatText("Sorry, code not recognised", RED);
                refresh();
                return;
            }
            floatText("Talking to server...", PURPLE);
            string jStr = llGetSubString(m, 5, llStringLength(m));
            postMessage("task=adduser&data1=" + jStr + "&data2=" + (string)userToPay);
            refresh();
        }
    }


    http_response(key request_id, integer Status, list metadata, string body)
    {
        if (request_id == farmHTTP)
        {
            list tok = llParseStringKeepNulls(body, ["|"], []);
            string cmd = llList2String(tok, 0);

            if (cmd == "LINKED")
            {
                // -1 is failed to link, 0 is already linked, 1 is managed to link okay
                llMessageLinked(LINK_SET, llList2Integer(tok, 1), "CMD_LINKRESULT", llList2Key(tok, 2));
            }
            else if (cmd == "UNLINKED")
            {
                // -1 is failed to un-link, 1 is managed to un-link okay
                llMessageLinked(LINK_SET, llList2Integer(tok, 1), "CMD_UNLINKRESULT", llList2Key(tok, 2));
            }
            else if ((cmd == "REJECT") || (cmd == "DISABLE"))
            {
                llSay(0, "Sorry, unable to verify with server.");
                llMessageLinked(LINK_SET, 0, "CMD_LINKRESULT", userToPay);
                llResetScript();
            }
        }
        else
        {
            // Response not for this script
        }
    }
}
