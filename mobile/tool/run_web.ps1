param(
    [string]$Device = 'chrome',
    [string]$EnvFile = '.env'
)

$previousLuciContext = $env:LUCI_CONTEXT

try {
    # Flutter's DDC loader uses a request pool of 1000 during ordinary local
    # runs. Large applications can exhaust Chrome's available resources while
    # thousands of debug modules are loading. Flutter already limits this pool
    # to 100 in its CI path; enabling that path for this process applies the
    # same upstream workaround without modifying the Flutter SDK.
    $env:LUCI_CONTEXT = 'local-web-debug'

    $arguments = @('run', '-d', $Device)
    if (Test-Path -LiteralPath $EnvFile) {
        $arguments += "--dart-define-from-file=$EnvFile"
    }

    & flutter @arguments
    exit $LASTEXITCODE
}
finally {
    if ($null -eq $previousLuciContext) {
        Remove-Item Env:LUCI_CONTEXT -ErrorAction SilentlyContinue
    }
    else {
        $env:LUCI_CONTEXT = $previousLuciContext
    }
}
