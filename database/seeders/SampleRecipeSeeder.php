<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SampleRecipeSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('SampleRecipeSeeder is only available in local/testing.');
        }

        DB::transaction(function (): void {
            $users = [];
            foreach (['minh' => 'Bếp Minh', 'an' => 'Bếp An'] as $key => $name) {
                $email = 'sample.'.$key.'@example.test';
                $user = User::where('email', $email)->first();
                if ($user && ($user->name !== $name || ! Hash::check('Sample12345!', $user->password))) {
                    throw new RuntimeException('A sample email is already in use. No sample data was changed.');
                }
                $users[] = $user ?? User::create([
                    'name' => $name, 'email' => $email, 'password' => Hash::make('Sample12345!'),
                ]);
            }

            foreach ($this->recipes() as $index => $data) {
                $recipe = Recipe::firstOrCreate([
                    'userId' => $users[$index % 2]->id, 'title' => $data['title'],
                ], Arr::only($data, ['description', 'prepTime', 'cookTime', 'servings', 'difficulty']) + [
                    'categoryId' => Category::where('name', $data['category'])->sole()->id,
                ]);
                // Keep any edits made while testing when this seeder is run again.
                if (! $recipe->wasRecentlyCreated) {
                    continue;
                }
                foreach ($data['ingredients'] as [$name, $quantity, $unit, $note]) {
                    $recipe->ingredients()->create([
                        'ingredientId' => Ingredient::where('name', $name)->sole()->id,
                        'quantity' => $quantity, 'unit' => $unit, 'note' => $note,
                    ]);
                }
                foreach ($data['steps'] as $step => $instruction) {
                    $recipe->steps()->create(['stepOrder' => $step + 1, 'instruction' => $instruction]);
                }
                $recipe->tags()->sync(array_map(fn ($name) => Tag::where('name', $name)->sole()->id, $data['tags']));
            }
        });
    }

    private function recipes(): array
    {
        return [
            [
                'title' => 'Trứng chiên hành lá', 'category' => 'Món chính',
                'description' => 'Trứng chiên vàng mềm, thơm hành lá, phù hợp cho bữa cơm nhanh cùng gia đình.',
                'prepTime' => 5, 'cookTime' => 7, 'servings' => 2, 'difficulty' => 'easy',
                'tags' => ['Nhanh', 'Dễ làm', 'Tiết kiệm'],
                'ingredients' => [
                    ['Trứng gà', 3, 'quả', null], ['Hành lá', 2, 'nhánh', 'Rửa sạch, thái nhỏ'],
                    ['Nước mắm', 5, 'ml', null], ['Dầu ăn', 10, 'ml', null], ['Tiêu', 0.5, 'g', null],
                ],
                'steps' => [
                    'Đập trứng vào bát, thêm hành lá, nước mắm và tiêu. Đánh đều cho lòng trắng và lòng đỏ hòa quyện.',
                    'Làm nóng dầu trong chảo chống dính ở lửa vừa. Đổ trứng vào và nghiêng chảo để trứng dàn đều.',
                    'Chiên khoảng 3–4 phút, lật nhẹ rồi chiên tiếp đến khi trứng chín hoàn toàn. Cắt miếng và dùng nóng.',
                ],
            ],
            [
                'title' => 'Gà kho gừng', 'category' => 'Món chính',
                'description' => 'Thịt gà kho đậm đà với gừng tươi, nước kho sánh nhẹ, ăn cùng cơm trắng rất hợp.',
                'prepTime' => 15, 'cookTime' => 30, 'servings' => 3, 'difficulty' => 'medium',
                'tags' => ['Ăn tối', 'Ăn trưa'],
                'ingredients' => [
                    ['Thịt gà', 500, 'g', 'Chặt miếng vừa ăn'], ['Gừng', 25, 'g', 'Thái sợi'],
                    ['Tỏi', 10, 'g', 'Băm nhỏ'], ['Nước mắm', 20, 'ml', null],
                    ['Đường', 10, 'g', null], ['Dầu ăn', 10, 'ml', null], ['Nước', 150, 'ml', null],
                ],
                'steps' => [
                    'Ướp gà với nước mắm, đường và một nửa lượng gừng trong 10 phút.',
                    'Làm nóng dầu, phi tỏi cùng phần gừng còn lại. Cho gà vào đảo đến khi các mặt săn lại.',
                    'Thêm nước, đun sôi rồi hạ lửa nhỏ. Đậy nắp kho khoảng 20 phút, đảo nhẹ giữa chừng.',
                    'Mở nắp, kho thêm đến khi thịt gà chín hoàn toàn và nước kho sánh lại. Nêm lại vừa ăn rồi tắt bếp.',
                ],
            ],
            [
                'title' => 'Cơm chiên trứng cà rốt', 'category' => 'Món chính',
                'description' => 'Cơm chiên tơi hạt với trứng và cà rốt, tận dụng cơm nguội cho bữa sáng đủ no.',
                'prepTime' => 10, 'cookTime' => 12, 'servings' => 2, 'difficulty' => 'easy',
                'tags' => ['Ăn sáng', 'Nhanh', 'Tiết kiệm'],
                'ingredients' => [
                    ['Gạo', 150, 'g', 'Nấu chín từ trước, để nguội và bảo quản lạnh; dùng khoảng 400 g cơm'],
                    ['Trứng gà', 2, 'quả', null], ['Cà rốt', 1, 'cái', 'Củ nhỏ, thái hạt lựu'],
                    ['Hành lá', 2, 'nhánh', 'Thái nhỏ'], ['Dầu ăn', 15, 'ml', null], ['Nước mắm', 10, 'ml', null],
                ],
                'steps' => [
                    'Tách cơm nguội thành các hạt rời. Đánh tan trứng trong bát riêng.',
                    'Làm nóng dầu trong chảo, xào cà rốt khoảng 3 phút. Đẩy cà rốt sang một bên, cho trứng vào đảo thành miếng nhỏ.',
                    'Thêm cơm, đảo đều trên lửa vừa lớn khoảng 5–7 phút đến khi cơm nóng kỹ và trứng chín.',
                    'Cho nước mắm và hành lá vào, đảo thêm 1 phút rồi chia ra đĩa, dùng ngay khi nóng.',
                ],
            ],
            [
                'title' => 'Canh cải xanh thịt băm', 'category' => 'Món canh',
                'description' => 'Bát canh cải xanh thanh nhẹ với thịt băm và chút gừng, dễ kết hợp trong bữa cơm hằng ngày.',
                'prepTime' => 10, 'cookTime' => 15, 'servings' => 3, 'difficulty' => 'easy',
                'tags' => ['Dễ làm', 'Ít dầu', 'Ăn trưa'],
                'ingredients' => [
                    ['Cải xanh', 300, 'g', 'Rửa sạch, cắt khúc'], ['Thịt heo', 150, 'g', 'Băm nhỏ'],
                    ['Gừng', 10, 'g', 'Thái sợi'], ['Nước mắm', 10, 'ml', null],
                    ['Muối', 2, 'g', null], ['Nước', 1000, 'ml', null],
                ],
                'steps' => [
                    'Ướp thịt băm với nước mắm. Nhặt bỏ lá cải già, rửa sạch và để ráo.',
                    'Đun sôi nước với gừng. Cho từng phần thịt băm vào, khuấy nhẹ cho tơi và hớt bọt.',
                    'Nấu đến khi thịt chín, cho cải xanh vào đun thêm khoảng 3–5 phút. Nêm muối vừa ăn và múc ra bát.',
                ],
            ],
            [
                'title' => 'Canh bí đỏ nấu tôm', 'category' => 'Món canh',
                'description' => 'Bí đỏ bùi mềm hòa cùng vị ngọt của tôm, tạo nên món canh có màu sắc ấm áp và dễ ăn.',
                'prepTime' => 15, 'cookTime' => 20, 'servings' => 3, 'difficulty' => 'easy',
                'tags' => ['Không cay', 'Ít dầu', 'Ăn tối'],
                'ingredients' => [
                    ['Bí đỏ', 400, 'g', 'Gọt vỏ, bỏ hạt, cắt miếng'], ['Tôm', 200, 'g', 'Bóc vỏ, bỏ chỉ lưng'],
                    ['Hành lá', 2, 'nhánh', 'Thái nhỏ'], ['Nước mắm', 10, 'ml', null],
                    ['Muối', 2, 'g', null], ['Nước', 1000, 'ml', null],
                ],
                'steps' => [
                    'Rửa sạch tôm, băm hoặc cắt nhỏ. Chuẩn bị bí đỏ thành các miếng đều nhau.',
                    'Đun sôi nước, cho bí đỏ vào nấu ở lửa vừa khoảng 12–15 phút đến khi gần mềm.',
                    'Thêm tôm, nấu thêm khoảng 4–5 phút đến khi tôm chín hoàn toàn. Nêm nước mắm, muối và thêm hành lá trước khi tắt bếp.',
                ],
            ],
            [
                'title' => 'Đậu phụ sốt cà chua', 'category' => 'Món chay',
                'description' => 'Đậu phụ mềm thấm sốt cà chua chua ngọt nhẹ, món chay đơn giản cho bữa cơm tiết kiệm.',
                'prepTime' => 10, 'cookTime' => 20, 'servings' => 2, 'difficulty' => 'easy',
                'tags' => ['Chay', 'Tiết kiệm', 'Dễ làm'],
                'ingredients' => [
                    ['Đậu phụ', 400, 'g', 'Thấm khô, cắt miếng'], ['Cà chua', 3, 'quả', 'Thái nhỏ'],
                    ['Hành lá', 2, 'nhánh', 'Thái nhỏ'], ['Dầu ăn', 20, 'ml', null],
                    ['Muối', 3, 'g', null], ['Đường', 5, 'g', null], ['Nước', 100, 'ml', null],
                ],
                'steps' => [
                    'Làm nóng dầu, áp chảo đậu phụ đến khi vàng nhẹ hai mặt rồi gắp ra.',
                    'Cho cà chua vào chảo, thêm muối, đường và nước. Đun khoảng 5–7 phút, dằm nhẹ để tạo sốt.',
                    'Cho đậu phụ trở lại chảo, đun nhỏ lửa khoảng 7 phút cho thấm sốt. Rắc hành lá và dùng nóng.',
                ],
            ],
            [
                'title' => 'Rau muống xào tỏi', 'category' => 'Món chay',
                'description' => 'Rau muống xanh giòn, thơm tỏi, chế biến nhanh với những nguyên liệu dễ tìm.',
                'prepTime' => 10, 'cookTime' => 5, 'servings' => 2, 'difficulty' => 'easy',
                'tags' => ['Chay', 'Nhanh', 'Dễ làm'],
                'ingredients' => [
                    ['Rau muống', 400, 'g', 'Nhặt bỏ phần già, rửa sạch'], ['Tỏi', 20, 'g', 'Đập dập'],
                    ['Dầu ăn', 15, 'ml', null], ['Muối', 3, 'g', null], ['Nước', 30, 'ml', null],
                ],
                'steps' => [
                    'Cắt rau muống thành đoạn vừa ăn và để ráo nước. Tách riêng phần cọng to nếu có.',
                    'Làm nóng chảo với dầu, cho tỏi vào đảo nhanh đến khi dậy mùi.',
                    'Cho cọng rau vào trước, sau đó thêm phần lá, muối và nước. Đảo đều trên lửa lớn khoảng 3–4 phút đến khi rau chín, dùng ngay.',
                ],
            ],
            [
                'title' => 'Bánh kếp sữa tươi', 'category' => 'Tráng miệng',
                'description' => 'Những chiếc bánh kếp mỏng mềm, thơm sữa và bơ, thích hợp cho bữa sáng hoặc bữa xế.',
                'prepTime' => 15, 'cookTime' => 15, 'servings' => 3, 'difficulty' => 'medium',
                'tags' => ['Ăn sáng', 'Không cay'],
                'ingredients' => [
                    ['Bột mì', 120, 'g', null], ['Trứng gà', 2, 'quả', null], ['Sữa tươi', 250, 'ml', 'Không đường'],
                    ['Đường', 20, 'g', null], ['Bơ', 20, 'g', 'Đun chảy, để nguội bớt'], ['Muối', 1, 'g', null],
                ],
                'steps' => [
                    'Đánh trứng với đường, muối và sữa. Rây bột mì vào, khuấy đến khi mịn rồi thêm một nửa lượng bơ.',
                    'Để bột nghỉ khoảng 10 phút. Làm nóng chảo chống dính ở lửa vừa nhỏ và quét một lớp bơ mỏng.',
                    'Múc một vá nhỏ bột vào chảo, nghiêng chảo để bột dàn mỏng. Rán khoảng 1–2 phút mỗi mặt đến khi bánh chín vàng nhẹ.',
                    'Lặp lại với phần bột còn lại, quét thêm bơ khi cần. Gấp bánh và thưởng thức khi còn ấm.',
                ],
            ],
            [
                'title' => 'Bánh flan caramel', 'category' => 'Tráng miệng',
                'description' => 'Bánh flan mềm mịn với lớp caramel thơm dịu. Sau khi hấp, cần thêm thời gian làm nguội và giữ lạnh trước khi dùng.',
                'prepTime' => 20, 'cookTime' => 35, 'servings' => 4, 'difficulty' => 'medium',
                'tags' => ['Không cay', 'Ít dầu'],
                'ingredients' => [
                    ['Trứng gà', 3, 'quả', null], ['Sữa tươi', 400, 'ml', 'Không đường'],
                    ['Đường', 90, 'g', '50 g làm caramel, 40 g pha sữa'], ['Nước', 40, 'ml', 'Làm caramel'],
                ],
                'steps' => [
                    'Đun 50 g đường với 40 ml nước ở lửa vừa đến màu hổ phách. Chia ngay vào 4 khuôn chịu nhiệt; cẩn thận vì caramel rất nóng.',
                    'Làm ấm sữa với 40 g đường, khuấy tan rồi nhấc khỏi bếp. Đánh trứng nhẹ tay, rót từ từ sữa ấm vào và khuấy đều.',
                    'Lọc hỗn hợp qua rây, chia vào khuôn và đậy bằng giấy bạc. Chuẩn bị nồi hấp có nước sôi nhẹ.',
                    'Hấp lửa nhỏ khoảng 30–35 phút đến khi hỗn hợp đông, tâm bánh còn rung nhẹ nhưng không lỏng. Tránh để nước trên nắp nhỏ vào bánh.',
                    'Để bánh nguội bớt rồi cho vào tủ lạnh ít nhất 2 giờ. Khi dùng, lách dao quanh thành khuôn và úp bánh ra đĩa.',
                ],
            ],
            [
                'title' => 'Nước chanh tươi', 'category' => 'Đồ uống',
                'description' => 'Ly nước chanh chua ngọt dịu, pha nhanh để dùng cùng bữa ăn. Ngon hơn khi dùng nước đã làm lạnh.',
                'prepTime' => 5, 'cookTime' => 0, 'servings' => 2, 'difficulty' => 'easy',
                'tags' => ['Nhanh', 'Dễ làm', 'Chay'],
                'ingredients' => [
                    ['Chanh', 2, 'quả', null], ['Đường', 35, 'g', 'Điều chỉnh theo độ chua của chanh'],
                    ['Nước', 500, 'ml', 'Nước uống, có thể làm lạnh trước'], ['Muối', 0.5, 'g', 'Một nhúm nhỏ'],
                ],
                'steps' => [
                    'Rửa sạch chanh, cắt đôi và vắt lấy nước cốt. Lọc bỏ hạt, tránh ép mạnh phần vỏ làm nước bị đắng.',
                    'Hòa tan đường và muối trong nước uống. Thêm nước cốt chanh từng chút, khuấy và nếm để điều chỉnh vị.',
                    'Chia vào hai ly. Dùng ngay hoặc giữ lạnh trước khi thưởng thức.',
                ],
            ],
        ];
    }
}
