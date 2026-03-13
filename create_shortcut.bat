@echo off
set "SC_NAME=JUSTSALE_POS"
set "SC_URL=http://localhost/justsale/pos"

echo Searching for Google Chrome...

:: Check for Chrome in standard locations
if exist "C:\Program Files\Google\Chrome\Application\chrome.exe" (
    set "CHROME_PATH=C:\Program Files\Google\Chrome\Application\chrome.exe"
) else if exist "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe" (
    set "CHROME_PATH=C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"
) else if exist "%LocalAppData%\Google\Chrome\Application\chrome.exe" (
    set "CHROME_PATH=%LocalAppData%\Google\Chrome\Application\chrome.exe"
) else (
    echo.
    echo ERROR: Google Chrome was not found!
    echo Please make sure Chrome is installed.
    pause
    exit /b
)

echo Chrome found at: %CHROME_PATH%

:: Get Desktop path handle OneDrive or redirection
for /f "usebackq tokens=2,*" %%A in (`reg query "HKCU\Software\Microsoft\Windows\CurrentVersion\Explorer\User Shell Folders" /v Desktop`) do set "DESKTOP=%%B"
call set "DESKTOP=%DESKTOP%"

echo Creating shortcut on: %DESKTOP%

:: Create a temporary VBScript to make the shortcut
set "TEMP_SCRIPT=%temp%\create_pos_lnk.vbs"
echo Set oWS = WScript.CreateObject("WScript.Shell") > "%TEMP_SCRIPT%"
echo sLinkFile = "%DESKTOP%\%SC_NAME%.lnk" >> "%TEMP_SCRIPT%"
echo Set oLink = oWS.CreateShortcut(sLinkFile) >> "%TEMP_SCRIPT%"
echo oLink.TargetPath = "%CHROME_PATH%" >> "%TEMP_SCRIPT%"
echo oLink.Arguments = "--app=%SC_URL% --kiosk-printing --disable-print-preview --start-fullscreen" >> "%TEMP_SCRIPT%"
echo oLink.IconLocation = "%CHROME_PATH%,0" >> "%TEMP_SCRIPT%"
echo oLink.Save >> "%TEMP_SCRIPT%"

:: Run the script
cscript /nologo "%TEMP_SCRIPT%"
del "%TEMP_SCRIPT%"

echo.
echo SUCCESS! The "JUSTSALE_POS" shortcut has been created on your desktop.
echo ----------------------------------------------------------------------
echo IMPORTANT: Close ALL Chrome windows before opening this shortcut.
echo ----------------------------------------------------------------------
pause
