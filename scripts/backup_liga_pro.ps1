param(
    [string]$Database = "liga_futbol_pro",
    [string]$MysqlBin = "C:\xampp\mysql\bin",
    [string]$OutputDir = "C:\xampp\htdocs\liga_pro\backups"
)

$ErrorActionPreference = "Stop"

$mysqldump = Join-Path $MysqlBin "mysqldump.exe"
if (!(Test-Path $mysqldump)) {
    throw "mysqldump.exe not found in $MysqlBin"
}

if (!(Test-Path $OutputDir)) {
    New-Item -Path $OutputDir -ItemType Directory | Out-Null
}

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$dumpFile = Join-Path $OutputDir ("{0}_{1}.sql" -f $Database, $timestamp)

& $mysqldump -u root --databases $Database --routines --triggers --single-transaction --quick --add-drop-table --default-character-set=utf8mb4 > $dumpFile

if (!(Test-Path $dumpFile)) {
    throw "Backup failed. File was not created."
}

Write-Host "Backup created:" $dumpFile
