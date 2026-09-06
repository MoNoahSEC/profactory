<?php
foreach(App\Models\User::all() as $user) {
    echo $user->id . ' - ' . $user->name . ' - ' . $user->roles->pluck('name')->join(', ') . PHP_EOL;
}
