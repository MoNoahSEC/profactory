<?php

$files = glob('app/Models/*.php');
foreach($files as $file) {
    $content = file_get_contents($file);
    $changed = false;
    
    // We match: public function something() { return $this->belongsTo(...
    // and replace with: public function something(): \Illuminate\Database\Eloquent\Relations\BelongsTo { return $this->belongsTo(...
    $content = preg_replace_callback('/public function ([a-zA-Z0-9_]+)\(\)\s*\{\s*return \$this->(belongsTo|hasMany|hasOne|belongsToMany|morphTo|morphMany|hasManyThrough)\(/m', function($m) use (&$changed) {
        $changed = true;
        $type = ucfirst($m[2]);
        return 'public function ' . $m[1] . '(): \Illuminate\Database\Eloquent\Relations\\' . $type . "\n    {\n        return \$this->" . $m[2] . '(';
    }, $content);
    
    if($changed) {
        file_put_contents($file, $content);
        echo "Updated $file\n";
    }
}
