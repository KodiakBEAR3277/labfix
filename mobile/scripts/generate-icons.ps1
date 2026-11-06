<#
  generate-icons.ps1

  Regenerates the LabFix mobile branding assets from the source brand mark
  (backend/public/assets/icons/LabFix Tech Orbit Logo.png):

    mobile/assets/images/icon.png                    1024  launcher / fallback (white bg, colour mark)
    mobile/assets/images/android-icon-foreground.png 1024  adaptive foreground (transparent)
    mobile/assets/images/android-icon-monochrome.png 1024  adaptive monochrome (white silhouette)
    mobile/assets/images/splash-icon.png              512  white silhouette on transparent
    mobile/assets/images/favicon.png                  196  web favicon (white bg, colour mark)

  Usage:
    powershell -ExecutionPolicy Bypass -File mobile/scripts/generate-icons.ps1

  Requires Windows PowerShell (uses System.Drawing).
#>
param(
  [string]$Source,
  [string]$OutDir
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing

$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path

if (-not $Source) { $Source = Join-Path $repoRoot 'backend\public\assets\icons\LabFix Tech Orbit Logo.png' }
if (-not $OutDir) { $OutDir = Join-Path $repoRoot 'mobile\assets\images' }

if (-not (Test-Path $Source)) { throw "Source brand mark not found: $Source" }
if (-not (Test-Path $OutDir)) { throw "Output directory not found: $OutDir" }

$src = [System.Drawing.Image]::FromFile($Source)

# The source is the full lockup (circular mark + "LabFix" wordmark); the wordmark
# begins at y=1000, so crop the circular mark from the region above that.
$cropX = 96; $cropY = 114; $cropW = 1064; $cropH = 886

$mark = New-Object System.Drawing.Bitmap($cropW, $cropH, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
$mg = [System.Drawing.Graphics]::FromImage($mark)
$mg.DrawImage(
  $src,
  (New-Object System.Drawing.Rectangle(0, 0, $cropW, $cropH)),
  (New-Object System.Drawing.Rectangle($cropX, $cropY, $cropW, $cropH)),
  [System.Drawing.GraphicsUnit]::Pixel
)
$mg.Dispose()
$src.Dispose()

$markAspect = $cropW / $cropH

function Save-Render {
  param([int]$Canvas, [string]$Bg, [double]$MarkW, [bool]$Mono, [string]$Out)

  $bmp = New-Object System.Drawing.Bitmap($Canvas, $Canvas, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
  $g = [System.Drawing.Graphics]::FromImage($bmp)
  $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
  $g.PixelOffsetMode   = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
  $g.SmoothingMode     = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality

  if ($Bg -eq 'transparent') { $g.Clear([System.Drawing.Color]::Transparent) }
  else { $g.Clear([System.Drawing.ColorTranslator]::FromHtml($Bg)) }

  $mw = [int]$MarkW
  $mh = [int]($mw / $markAspect)
  $x  = [int](($Canvas - $mw) / 2)
  $y  = [int](($Canvas - $mh) / 2)
  $dest = New-Object System.Drawing.Rectangle($x, $y, $mw, $mh)

  if ($Mono) {
    # White silhouette: drop source RGB, add white offsets (5th ROW in .NET ColorMatrix), keep alpha.
    $ia = New-Object System.Drawing.Imaging.ImageAttributes
    $cm = New-Object System.Drawing.Imaging.ColorMatrix
    $cm.Matrix00 = 0.0; $cm.Matrix11 = 0.0; $cm.Matrix22 = 0.0; $cm.Matrix33 = 1.0
    $cm.Matrix40 = 1.0; $cm.Matrix41 = 1.0; $cm.Matrix42 = 1.0
    $ia.SetColorMatrix($cm)
    $g.DrawImage($mark, $dest, 0, 0, $cropW, $cropH, [System.Drawing.GraphicsUnit]::Pixel, $ia)
    $ia.Dispose()
  }
  else {
    $g.DrawImage($mark, $dest)
  }

  $g.Dispose()
  $bmp.Save($Out, [System.Drawing.Imaging.ImageFormat]::Png)
  Write-Output ("wrote {0} ({1}x{2})" -f $Out, $bmp.Width, $bmp.Height)
  $bmp.Dispose()
}

Save-Render -Canvas 1024 -Bg '#ffffff'     -MarkW (0.72 * 1024) -Mono $false -Out (Join-Path $OutDir 'icon.png')
Save-Render -Canvas 1024 -Bg 'transparent' -MarkW 500           -Mono $false -Out (Join-Path $OutDir 'android-icon-foreground.png')
Save-Render -Canvas 1024 -Bg 'transparent' -MarkW 500           -Mono $true  -Out (Join-Path $OutDir 'android-icon-monochrome.png')
Save-Render -Canvas 512  -Bg 'transparent' -MarkW (0.80 * 512)  -Mono $true  -Out (Join-Path $OutDir 'splash-icon.png')
Save-Render -Canvas 196  -Bg '#ffffff'     -MarkW (0.80 * 196)  -Mono $false -Out (Join-Path $OutDir 'favicon.png')

$mark.Dispose()
Write-Output 'done'
