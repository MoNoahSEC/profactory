<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // تهيئة الصلاحيات أولاً
        $this->call(RolesAndPermissionsSeeder::class);

        // مدير النظام
        $admin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@factory.com'],
            ['name' => 'محمد', 'password' => bcrypt('12345678')]
        );
        $admin->assignRole('Admin');

        // فئات المنتجات
        \App\Models\Category::insert([
            ['name' => 'قفص صغير', 'description' => 'أقفاص حتى 30 سم'],
            ['name' => 'قفص متوسط', 'description' => 'أقفاص 30-60 سم'],
            ['name' => 'قفص كبير', 'description' => 'أقفاص أكبر من 60 سم'],
            ['name' => 'قفص خاص', 'description' => 'تصميمات خاصة بالطلب'],
        ]);

        // الخامات
        \App\Models\RawMaterial::insert([
            ['name' => 'سلك حديد 2mm', 'unit' => 'كجم', 'unit_cost' => 15.00, 'current_stock' => 500, 'minimum_stock' => 50],
            ['name' => 'طلاء زيتي', 'unit' => 'لتر', 'unit_cost' => 45.00, 'current_stock' => 100, 'minimum_stock' => 20],
            ['name' => 'مسامير', 'unit' => 'كجم', 'unit_cost' => 20.00, 'current_stock' => 200, 'minimum_stock' => 30],
            ['name' => 'قضبان خشبية', 'unit' => 'قطعة', 'unit_cost' => 5.00, 'current_stock' => 300, 'minimum_stock' => 50],
        ]);

        // الورديات
        \App\Models\Shift::insert([
            ['name' => 'الوردية الصباحية', 'start_time' => '07:00', 'end_time' => '15:00', 'overtime_rate' => 1.5],
            ['name' => 'الوردية المسائية', 'start_time' => '15:00', 'end_time' => '23:00', 'overtime_rate' => 1.5],
        ]);
    }
}
