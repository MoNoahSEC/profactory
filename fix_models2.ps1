$files = Get-ChildItem -Path app\Models -Filter *.php
$types = @('BelongsTo', 'HasMany', 'HasOne', 'BelongsToMany', 'MorphTo', 'MorphMany', 'HasManyThrough')

foreach ($file in $files) {
    $originalContent = Get-Content $file.FullName -Raw
    $content = $originalContent
    
    foreach ($type in $types) {
        $lower = $type.Substring(0,1).ToLower() + $type.Substring(1)
        $pattern = 'public function ([a-zA-Z0-9_]+)\(\)\s*\{\s*return \$this->' + $lower + '\('
        $replacement = 'public function $1(): \Illuminate\Database\Eloquent\Relations\' + $type + "`r`n    {`r`n        return `$this->" + $lower + '('
        $content = [regex]::Replace($content, $pattern, $replacement)
    }
    
    if ($content -ne $originalContent) {
        Set-Content -Path $file.FullName -Value $content -NoNewline
        Write-Host "Updated $($file.Name)"
    }
}
