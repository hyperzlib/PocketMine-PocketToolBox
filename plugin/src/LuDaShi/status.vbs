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