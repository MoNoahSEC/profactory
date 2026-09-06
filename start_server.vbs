Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
currentDir = fso.GetParentFolderName(WScript.ScriptFullName)
WshShell.Run chr(34) & currentDir & "\RunServer.bat" & Chr(34), 1, false
Set WshShell = Nothing
Set fso = Nothing
