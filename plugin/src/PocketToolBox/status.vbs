'  ____             _         _   _____             _ ____ 
' |  _ \  ___   ___| | __ ___| |_|_   _| ___   ___ | | __ )  ___ __  __
' | |_) |/ _ \ / __| |/ // _ \ __| | |  / _ \ / _ \| |  _ \ / _ \\ \/ /
' |  __/| (_) | (__|   <|  __/ |_  | | | (_) | (_) | | |_) | (_) |)  ( 
' |_|    \___/ \___|_|\_\\___|\__| |_|  \___/ \___/|_|____/ \___//_/\_\
'
' This program is free software: you can redistribute it and/or modify
' it under the terms of the GNU Lesser General Public License as published by
' the Free Software Foundation, either version 3 of the License, or
' (at your option) any later version.
'
' @author chs
' @github https://github.com/hyperzlib/PocketMine-PocketToolBox/
' @website http://mcleague.xicp.net/

SET Wshell=CreateObject("Wscript.Shell")
 
if lcase(right(Wscript.fullName,11)) = "wscript.exe" then
    Wshell.run "cmd /k cscript.exe //nologo " & chr(34) & wscript.scriptfullname & chr(34)
    Wscript.quit
end if

On Error Resume Next
strComputer = "."

While True
	Set objWMIService = GetObject("winmgmts:\\" & strComputer & "\root\cimv2")
	Set colItems = objWMIService.ExecQuery("Select * from Win32_Processor",,48)
	Set wmiObjects = objWMIService.ExecQuery("SELECT * FROM CIM_OperatingSystem")
	For Each wmiObject In wmiObjects
		ramall = wmiObject.TotalVisibleMemorySize
		ramfree = wmiObject.FreePhysicalMemory
	next 
	For Each objItem in colItems
		cpu = objItem.LoadPercentage
	Next
	Wscript.Echo("{""cpu"":" & cpu & ",""ramall"":" & ramall & ",""ramfree"":" & ramfree & "}")
Wend