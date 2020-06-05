// terminal-points.lsl
//
// Allows user to link their existing avatar from any grid, to a Quintonia Joomla web account
//
//  Version 1.3    // 28 May 2020

string TXT_YOU_HAVE = "You have ";
string TXT_POINTS = "Points";
string TXT_RANK = "Rank ";

string BASEURL="https://quintonia.net/index.php?option=com_quinty&format=raw&";
key farmHTTP = NULL_KEY;

key dlgUser;
key userToPay;
string lookingFor;
integer startOffset=0;
string status;

vector RED      = <1.000, 0.255, 0.212>;
vector GREEN    = <0.180, 0.800, 0.251>;
vector YELLOW   = <1.000, 0.863, 0.000>;
vector PURPLE   = <0.694, 0.051, 0.788>;
vector WHITE    = <1.000, 1.000, 1.000>;

integer chan(key u)
{
    return -1 - (integer)("0x" + llGetSubString( (string) u, -6, -1) )-393;
}

integer listener=-1;
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
    llMessageLinked(LINK_SET, 0, "IDLE", "");
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

    on_rez(integer n)
    {
        llResetScript();
    }

    state_entry()
    {
        listener=-1;
    }

    link_message(integer sender_num, integer num, string msg, key id)
    {
        if (msg == "CMD_POINTS")
        {
            list opts = [];
            opts += "Link avatar";
            opts += "UnLink avatar";
            opts += "CLOSE";
            opts += "My Points";

            startListen();
            userToPay = id;
            llDialog(userToPay, "Choose points option", opts, chan(llGetKey()));
            llSetTimerEvent(300);
        }
        else if (msg == "CMD_PLUSPNT")
        {
            floatText("Talking to server...", PURPLE);
            llSleep(0.5);
            postMessage("task=sold&data1=" + (string)userToPay + "&data2=Farm time" + "&data3=20");
            dlgUser = NULL_KEY;
            status = "verifying-pluspnt";
        }
        else if (msg == "CMD_LINKRESULT")
        {
            if (num == -1)
            {
                string errMsg;
                if (id == "INVALID-E") errMsg = "You already have an avatar linked to this account (maybe you made a Quintonia one when you registered?)\nYou can try using the Un-link option first.";
                if (id == "INVALID-J") errMsg = "Can't find matching account, please check the last three numbers are correct and try again.";
                if (id == "INVALID-Q") errMsg = "Sorry, something went wrong on our systems - please contact us ot wait a while and try again.";
                floatText("Unable to link account. [err: " + (string)id + "]\n" + errMsg, RED);
            }
            else
            {
                if (num == 1)
                {
                    floatText("Success! Your avatar (" + (string)id + ") is now\nlinked to your Quintonia points account.", GREEN);
                    llInstantMessage(llGetOwner(), "Avatar " + (string)id + "has been linked okay");
                }
                else
                {
                    floatText("Your avatar is already linked to your Quintonia points account.", YELLOW);
                }
            }
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
        else if (m == "Link avatar")
        {
            llMessageLinked(LINK_SET, 1, "CMD_LINKACC", userToPay);
        }
        else if (m == "UnLink avatar")
        {
            llMessageLinked(LINK_SET, 1, "CMD_UNLINKACC", userToPay);
        }
        else if (m == "My Points")
        {
            floatText("Checking your points total...", PURPLE);
            llSleep(0.5);
            postMessage("task=points&data1=" + (string)id);
            dlgUser = NULL_KEY;
        }
    }


    http_response(key request_id, integer Status, list metadata, string body)
    {
        if (request_id == farmHTTP)
        {
            list tok = llParseStringKeepNulls(body, ["|"], []);
            string cmd = llList2String(tok, 0);

            if (cmd == "DISABLE")
            {
                llOwnerSay("DISABLED");
                llResetScript();
            }
            else if (cmd == "REJECT")
            {
                // status == "verifying-soldpnt"
                floatText("Sorry, you need to have a linked account\nat www.quintonia.net to use points.", YELLOW);
                status = "";
                refresh();
            }
            else if (cmd == "POINTTALLY")
            {
                    floatText(TXT_YOU_HAVE+" " + (string)llList2Integer(tok,1) + " "+TXT_POINTS + "\n" + TXT_RANK + ": " +(string)llList2String(tok,2), WHITE);
                    refresh();
            }

            else
            {
              // debug(" == "+llList2String(tok,1));
            }
        }
        else
        {
            // Response not for this script
        }
    }

}
