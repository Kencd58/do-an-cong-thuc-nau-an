<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $categories = [
                ['id' => 1, 'name' => 'Món chính'],
                ['id' => 2, 'name' => 'Món canh'],
                ['id' => 3, 'name' => 'Món chay'],
                ['id' => 4, 'name' => 'Tráng miệng'],
                ['id' => 5, 'name' => 'Đồ uống'],
            ];

            $tags = [
                ['id' => 1, 'name' => 'Nhanh'],
                ['id' => 2, 'name' => 'Dễ làm'],
                ['id' => 3, 'name' => 'Tiết kiệm'],
                ['id' => 4, 'name' => 'Ít dầu'],
                ['id' => 5, 'name' => 'Cay'],
                ['id' => 6, 'name' => 'Không cay'],
                ['id' => 7, 'name' => 'Ăn sáng'],
                ['id' => 8, 'name' => 'Ăn trưa'],
                ['id' => 9, 'name' => 'Ăn tối'],
                ['id' => 10, 'name' => 'Chay'],
            ];

            $ingredients = [
                ['id' => 1, 'name' => 'Gạo', 'defaultUnit' => 'g'],
                ['id' => 2, 'name' => 'Thịt heo', 'defaultUnit' => 'g'],
                ['id' => 3, 'name' => 'Thịt bò', 'defaultUnit' => 'g'],
                ['id' => 4, 'name' => 'Thịt gà', 'defaultUnit' => 'g'],
                ['id' => 5, 'name' => 'Cá', 'defaultUnit' => 'g'],
                ['id' => 6, 'name' => 'Tôm', 'defaultUnit' => 'g'],
                ['id' => 7, 'name' => 'Trứng gà', 'defaultUnit' => 'quả'],
                ['id' => 8, 'name' => 'Đậu phụ', 'defaultUnit' => 'g'],
                ['id' => 9, 'name' => 'Cà chua', 'defaultUnit' => 'quả'],
                ['id' => 10, 'name' => 'Hành tây', 'defaultUnit' => 'cái'],
                ['id' => 11, 'name' => 'Hành lá', 'defaultUnit' => 'nhánh'],
                ['id' => 12, 'name' => 'Tỏi', 'defaultUnit' => 'g'],
                ['id' => 13, 'name' => 'Gừng', 'defaultUnit' => 'g'],
                ['id' => 14, 'name' => 'Cà rốt', 'defaultUnit' => 'cái'],
                ['id' => 15, 'name' => 'Khoai tây', 'defaultUnit' => 'cái'],
                ['id' => 16, 'name' => 'Bí đỏ', 'defaultUnit' => 'g'],
                ['id' => 17, 'name' => 'Nấm', 'defaultUnit' => 'g'],
                ['id' => 18, 'name' => 'Cải xanh', 'defaultUnit' => 'g'],
                ['id' => 19, 'name' => 'Rau muống', 'defaultUnit' => 'g'],
                ['id' => 20, 'name' => 'Dưa leo', 'defaultUnit' => 'quả'],
                ['id' => 21, 'name' => 'Sữa tươi', 'defaultUnit' => 'ml'],
                ['id' => 22, 'name' => 'Đường', 'defaultUnit' => 'g'],
                ['id' => 23, 'name' => 'Muối', 'defaultUnit' => 'g'],
                ['id' => 24, 'name' => 'Nước mắm', 'defaultUnit' => 'ml'],
                ['id' => 25, 'name' => 'Dầu ăn', 'defaultUnit' => 'ml'],
                ['id' => 26, 'name' => 'Tiêu', 'defaultUnit' => 'g'],
                ['id' => 27, 'name' => 'Bột mì', 'defaultUnit' => 'g'],
                ['id' => 28, 'name' => 'Bơ', 'defaultUnit' => 'g'],
                ['id' => 29, 'name' => 'Chanh', 'defaultUnit' => 'quả'],
                ['id' => 30, 'name' => 'Nước', 'defaultUnit' => 'ml'],
            ];

            foreach (['categories' => $categories, 'tags' => $tags, 'ingredients' => $ingredients] as $table => $rows) {
                $rows = array_map(fn (array $row): array => $row + ['createdAt' => $now, 'updatedAt' => $now], $rows);
                $updates = $table === 'ingredients' ? ['name', 'defaultUnit', 'updatedAt'] : ['name', 'updatedAt'];
                DB::table($table)->upsert($rows, ['id'], $updates);
            }
        });
    }
}
