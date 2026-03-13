$ShortcutPath = "$env:USERPROFILE\Desktop\JUSTSALE_POS.lnk"
$TargetFile = "C:\Program Files\Google\Chrome\Application\chrome.exe"
$Arguments = "--kiosk-printing http://localhost/justsale/pos"
$WScriptShell = New-Object -ComObject WScript.Shell
$Shortcut = $WScriptShell.CreateShortcut($ShortcutPath)
$Shortcut.TargetPath = $TargetFile
$Shortcut.Arguments = $Arguments
$Shortcut.Save()
Write-Host "Shortcut created on your Desktop."
