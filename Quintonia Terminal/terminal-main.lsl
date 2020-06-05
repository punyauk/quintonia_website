// terminal-main.lsl
//
// Allows user to link their existing avatar from any grid, to a Quintonia Joomla web account
//
//  Version 1.1    // 25 January 2020

string BASEURL="https://quintonia.net/index.php?option=com_quinty&format=raw&";
key farmHTTP = NULL_KEY;  

vector GRAY     = <0.667, 0.667, 0.667>;
vector RED      = <1.000, 0.255, 0.212>;
vector PURPLE   = <0.694, 0.051, 0.788>;
vector GREEN    = <0.000, 0.502, 0.000>;

string info_nc = "Linking accounts info";
integer active = FALSE;
integer listener=-1;
integer listenTs;
integer lastTs;
string status;
string lastText;
key ownerID = NULL_KEY;
key toucher;
string dlgTitle;


integer chan(key u)
{
    return -1 - (integer)("0x" + llGetSubString( (string) u, -6, -1) )-393;
}


startListen()
{
    if (listener<0) 
    {
        listener = llListen(chan(llGetKey()), "", "", "");
        listenTs = llGetUnixTime();
    }
}

checkListen(integer force)
{
    if ( (listener > 0 && llGetUnixTime() - listenTs > 300) || (force == TRUE) ) 
    {
        llListenRemove(listener);
        listener = -1;
    }
}


postMessage(string msg)
{
    farmHTTP = llHTTPRequest( BASEURL, [HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"], msg);
}


floatText(string msg, vector colour)
{
    if (msg != lastText)
    {
        if (llStringLength(msg) == 0)
        {
            txt_off();
            return;
        }
        llSetText(msg + "\n ", colour, 1.0);
        lastText = msg;
    }
    llSay(0, msg);   
}

txt_off()
{
    llSetText("", ZERO_VECTOR, 0);
    llMessageLinked(LINK_ALL_CHILDREN, 1, "SCREENOFF", "");
}

qlSetDynamicText(string msg, integer logo, integer face, string bgCol)
{
    string body = "width:512,height:512,aplha:FALSE,bgcolour:" + bgCol;    
    string draw = "";  // Storage for our drawing commands
    draw = osMovePen(draw, 25, 20);
    draw = osSetFontSize(draw, 9); 
    draw = osSetPenColor(draw, "DarkViolet");
    draw = osDrawText(draw, msg);
    osSetDynamicTextureDataFace("", "vector", draw, body, 0, face);   
}


refresh()
{    
    if (active != TRUE)
    {
        floatText("OFFLINE", RED);
        llSetColor(RED, 0);
    }
    else
    {
        floatText("ONLINE", PURPLE);
        llSetColor(GREEN, 0);
    }
}


activate()
{
    // Talk to PHP script and check comms is okay then activate
    llSetColor(PURPLE, 0);
    llSleep(1);
    postMessage("task=activq327&data1=1");
    llSetTimerEvent(30);   
}

// -State default- //

default 
{ 
    on_rez(integer n)
    {
        llResetScript();

    }


    state_entry()
    {
        listener = -1;
        active = FALSE;
        lastTs = llGetUnixTime();
        ownerID = llGetOwner();   
        toucher = ownerID;
        dlgTitle = llGetObjectDesc();
        // Talk to PHP script and check comms is okay then activate
        floatText("Connecting to server...", PURPLE);
        llSetColor(PURPLE, 0);
        llSleep(1);
        postMessage("task=activq327&data1=1");
        qlSetDynamicText("Initializing\n    Please wait...", FALSE, 2, "White");
        llSetTimerEvent(30);   
    }


    timer()
    {
        checkListen(FALSE);
        if (active == FALSE)
        {
            qlSetDynamicText("OFFLINE", FALSE, 2, "White");
            floatText("OFFLINE...", RED);
            llSetColor(RED, 0);
            activate();
            llSetTimerEvent(60);
        }
        else
        {
            llSetTimerEvent(0);
        }
        refresh();
    }


    listen(integer c, string nm, key id, string m)
    {   
        if (m == "CLOSE") 
        {
            status = "";
            refresh();
            return;
        }
        else if (m== "Info")
        {
            llGiveInventory(id, info_nc);
            refresh();
        }
        else if (m== "Points")
        {
            status = "ExchangePoints";
            llListenRemove(listener);
            llMessageLinked(LINK_THIS, 1, "CMD_POINTS", id);
        }
    }
    
       
    touch_start(integer n)
    {   
        if (active == TRUE) 
        {
            checkListen(TRUE);
            toucher = llDetectedKey(0);
            list opts = [];
            opts += "Info";
            opts += "Points";
            opts += "CLOSE";
            status = "";
            startListen();    
            llDialog(toucher, dlgTitle, opts, chan(llGetKey()));
            llSetTimerEvent(300);
        }
        else if (status != "ExchangePoints")
        {
            llSay(0,"Sorry, currently busy...");

        }
        else
        {
            llSay(0, "Sorry, this terminal is currently inactive");
        }
    }
    

    http_response(key request_id, integer httpstatus, list metadata, string body)
    {
        if (request_id == farmHTTP)
        {
            list tok = llParseStringKeepNulls(body, ["|"], []);
            string cmd = llList2String(tok, 0);
            if (cmd == "2017053016xR")
            {
                qlSetDynamicText(dlgTitle + "\n \nLink your avatar to use\nQuintonia points or check\nyour current score!", FALSE, 2, "White");
                //  Communication established okay
                active = TRUE;
                llSleep(10.0);
                refresh();
            }
        }
    }


    link_message(integer sender_num, integer num, string msg, key id)
    {   
        list tk = llParseStringKeepNulls(msg , ["|"], []);
        string cmd = llList2String(tk, 0);

        if (cmd = "TEXT")
        {
            floatText(llList2String(tk, 1), llList2Vector(tk, 2));

        }
        else if (cmd == "POINTS-DONE")
        {
            if (status == "ExchangePoints") status = "";
        }
        else if (cmd == "IDLE")
        {
            status = "";
            refresh();
        }
    }

}
