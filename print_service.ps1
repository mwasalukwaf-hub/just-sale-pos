# JUSTSALE Background Print Service
# This script watches the receipts folder and prints new PDF files automatically.

$watchDir = "c:\xampp\htdocs\justsale\receipts"
$archivedDir = "c:\xampp\htdocs\justsale\receipts\archived"

if (!(Test-Path $archivedDir)) {
    New-Item -ItemType Directory -Path $archivedDir | Out-Null
}

Write-Host "JUSTSALE Print Service is running..."
Write-Host "Watching: $watchDir"
Write-Host "Press Ctrl+C to stop."

while($true) {
    $files = Get-ChildItem -Path "$watchDir\*.pdf" | Where-Object { $_.PSIsContainer -eq $false }
    
    foreach ($file in $files) {
        try {
            Write-Host "Printing: $($file.Name)..."
            
            # Print command (uses default system PDF viewer to print)
            # This is the most compatible way to print a PDF via CLI on Windows
            Start-Process -FilePath $file.FullName -Verb Print -WindowStyle Hidden -ErrorAction Stop
            
            # Wait for the print job to be handed off
            Start-Sleep -Seconds 5
            
            # Move to archive
            Move-Item -Path $file.FullName -Destination $archivedDir -Force
            Write-Host "Archived: $($file.Name)"
        } catch {
            Write-Host "Error printing $($file.Name): $($_.Exception.Message)"
        }
    }
    
    Start-Sleep -Seconds 2
}
