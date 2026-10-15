#Requires -RunAsAdministrator
[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$phpDirectory = 'C:\laragon\bin\php\php-8.4.26-Win32-vs17-x64'
$phpExecutable = Join-Path $phpDirectory 'php.exe'
$projectDirectory = Split-Path -Parent $PSScriptRoot
$stateDirectory = Join-Path $projectDirectory 'storage\app\php-runtime'
$resultPath = Join-Path $stateDirectory 'path-update-result.json'

try {
    if (-not (Test-Path -LiteralPath $phpExecutable -PathType Leaf)) {
        throw 'PHP 8.4.26 is not installed at the expected location.'
    }
    $phpVersion = & $phpExecutable -r 'echo PHP_VERSION;'
    if ($LASTEXITCODE -ne 0 -or $phpVersion -ne '8.4.26') {
        throw 'The selected PHP executable is not version 8.4.26.'
    }

    New-Item -ItemType Directory -Path $stateDirectory -Force | Out-Null
    $environmentKey = [Microsoft.Win32.Registry]::LocalMachine.OpenSubKey(
        'SYSTEM\CurrentControlSet\Control\Session Manager\Environment', $true
    )
    if (-not $environmentKey) { throw 'Cannot open the machine environment key.' }

    try {
        $originalPath = $environmentKey.GetValue('Path', '', [Microsoft.Win32.RegistryValueOptions]::DoNotExpandEnvironmentNames)
        $originalKind = $environmentKey.GetValueKind('Path')
        $backupPath = Join-Path $stateDirectory ('machine-path-before-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.json')
        [ordered]@{ Path = $originalPath; Kind = $originalKind.ToString() } |
            ConvertTo-Json | Set-Content -LiteralPath $backupPath -Encoding UTF8

        # Change only Laragon PHP entries; preserve other PATH entries and registry type.
        $retainedEntries = @($originalPath -split ';' | Where-Object {
            $_.Trim().TrimEnd('\') -notmatch '^C:\\laragon\\bin\\php\\php-[^\\]+$'
        })
        $updatedPath = (@($phpDirectory) + $retainedEntries) -join ';'
        $environmentKey.SetValue('Path', $updatedPath, $originalKind)
        if ($environmentKey.GetValue('Path', '', [Microsoft.Win32.RegistryValueOptions]::DoNotExpandEnvironmentNames) -ne $updatedPath) {
            throw 'Machine PATH verification failed.'
        }
    } finally {
        $environmentKey.Close()
    }

    # Notify desktop applications that new processes should inherit the updated PATH.
    Add-Type -TypeDefinition @'
using System;
using System.Runtime.InteropServices;
public static class HejiraEnvironmentNotification {
    [DllImport("user32.dll", CharSet = CharSet.Unicode, SetLastError = true)]
    public static extern IntPtr SendMessageTimeout(IntPtr window, uint message,
        UIntPtr wParam, string lParam, uint flags, uint timeout, out UIntPtr result);
}
'@
    $notificationResult = [UIntPtr]::Zero
    [void][HejiraEnvironmentNotification]::SendMessageTimeout(
        [IntPtr]0xffff, 0x001a, [UIntPtr]::Zero, 'Environment', 2, 5000, [ref]$notificationResult
    )

    [ordered]@{ status = 'success'; php_version = $phpVersion; php_executable = $phpExecutable; backup = $backupPath } |
        ConvertTo-Json | Set-Content -LiteralPath $resultPath -Encoding UTF8
} catch {
    if (Test-Path -LiteralPath $stateDirectory) {
        [ordered]@{ status = 'failed'; error = $_.Exception.Message } |
            ConvertTo-Json | Set-Content -LiteralPath $resultPath -Encoding UTF8
    }
    throw
}
