<?php
$salaries = App\Models\SalaryRecord::orderBy('id')->get();
$seen = [];
foreach($salaries as $s) {
    $key = $s->worker_id . '_' . $s->start_date->format('Y-m-d');
    if(isset($seen[$key])) {
        $s->delete();
        echo "Deleted duplicate " . $s->id . "\n";
    } else {
        $seen[$key] = true;
    }
}
echo "Done\n";
