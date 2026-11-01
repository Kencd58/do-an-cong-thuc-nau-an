# Bếp Nhà — website công thức nấu ăn

Laravel 12 + Blade theo MVC; nghiệp vụ công thức và ảnh local được xử lý trực tiếp trong RecipeController. Chức năng: tài khoản, công thức công khai, ảnh lưu trên public disk, tìm/lọc/phân trang, đánh giá và bình luận. Ứng dụng không cần API hoặc khóa dịch vụ ảnh bên ngoài. Xem [báo cáo kiểm thử](docs/test-report/report.md), [trạng thái triển khai](docs/implementation-state.md) và [hợp đồng/schema](docs/contracts.md).

## Runtime và thư mục chạy lệnh

Đã kiểm thử Windows/XAMPP PHP **8.2.12**, MariaDB **10.4.32**, Laravel **12.69.3**, Composer **2.10.3**, Node **24.21.0**, npm **11.19.0**, Vite **7.3.7**. Dùng lockfile; không cần Laravel toàn cục. `storage` và `bootstrap/cache` cần quyền ghi.

`composer check-platform-reqs` kiểm tra dom, fileinfo, filter, hash, iconv, json, libxml, openssl, pcre, phar, session, tokenizer, xml, xmlwriter; ctype/mbstring được polyfill đáp ứng trên máy thử. Ứng dụng cần thêm **pdo_mysql** và **Argon2id**. Kiểm tra `php -m` và `php -r "var_export(password_algos());"`.

Mọi lệnh dưới chạy ở **gốc source chứa artisan**, ví dụ `D:\DoAn2026\code`. Trong PowerShell:

```powershell
$env:Path = 'C:\xampp\php;' + $env:Path
php -v
composer --version
```

Nếu Composer wrapper ép PHP khác (máy hiện tại ép 8.5.9), thay `composer` bằng `php D:\LaravelTools\composer.phar`; xác nhận Composer báo PHP 8.2.12. Linux/macOS dùng `cp` thay `Copy-Item`, `npm` thay `npm.cmd`.

## Cài mới bởi người phụ trách database

Toàn bộ 10 bảng nghiệp vụ được định nghĩa trong `database/migrations`, chạy theo thứ tự khóa ngoại. `php artisan migrate --seed` tạo schema và nạp catalog; không cần file SQL tạo bảng hoặc installer riêng. `CatalogSeeder` chứa dữ liệu 5 danh mục, 10 thẻ và 30 nguyên liệu.

8 migration nền kiểm tra bảng đã tồn tại để tiếp nhận database cũ mà không tạo lại bảng hay sửa dữ liệu. Chúng không tự sửa cấu trúc bảng cũ nếu cấu trúc đó khác schema chuẩn; thay đổi schema về sau phải dùng migration mới. `down()` xóa bảng như migration Laravel thông thường, vì vậy không rollback/reset/fresh trên dữ liệu cần giữ.

Bật MySQL trong XAMPP; tạo database trống bằng phpMyAdmin/SQL console, cấp quyền cho tài khoản ứng dụng:

```sql
CREATE DATABASE recipe_shared CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
```

Sửa `.env` trước khi cài DB: `APP_ENV=local`, `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. Ví dụ env giữ `webcookingrecipe` theo máy cũ; đổi `recipe_shared` cho DB nhóm. Không copy đè `.env` đang dùng.

```powershell
php artisan key:generate
php artisan config:clear
# Chỉ người phụ trách DB, trên database TRỐNG:
php artisan migrate --seed
php artisan storage:link
# Tùy chọn, local/testing; chỉ người phụ trách demo chạy:
php artisan db:seed --class=DemoSeeder
npm.cmd ci
npm.cmd run build
composer check-platform-reqs
php artisan serve --host=127.0.0.1 --port=8000
```

Kết quả: 10 bảng nghiệp vụ + migrations; catalog 5 danh mục/10 thẻ/30 nguyên liệu. `DatabaseSeeder` chỉ gọi `CatalogSeeder`; `php artisan db:seed` chạy lại không nhân đôi. Catalog dùng ID cố định theo hợp đồng, không dùng seed để hợp nhất catalog tùy biến. Nếu cài bị ngắt, xem `migrate:status` và kiểm tra bước đã hoàn tất; không reset database cần giữ.

Mở http://127.0.0.1:8000/ để xem công thức cộng đồng mới chia sẻ; đây cũng là trang mặc định sau đăng nhập. Mục Tìm kiếm tại `/recipes` chứa bộ lọc. Công thức hợp lệ công khai ngay; chỉ owner sửa/xóa. Không Admin, bản nháp, Policy/API ảnh riêng. Danh sách 12 món/trang, AND giữa nhóm filter, OR trong nhóm thẻ/nguyên liệu. Bình luận 10 mục/trang; điểm 1–5 và cấm tự chấm.

## Thành viên kết nối database đã có và demo LAN

Cài dependency, tạo `.env` riêng nếu chưa có, trỏ máy A/`recipe_shared`, build và chạy server riêng. **Bỏ qua migrate --seed và DemoSeeder.** Không tự đổi APP_KEY đang dùng. Ứng dụng không tự seed khi mở web.

Xem [phần 01](docs/plans/01_Nen_tang_Database_Catalog_Layout.docx) cho XAMPP, SQL tài khoản, my.ini, firewall và cấu hình A/B/C. Người phụ trách sao lưu rồi chạy `php artisan migrate` khi có migration mới. Không `migrate:fresh/reset/rollback` trên dữ liệu cần giữ.

Mỗi máy chạy `php artisan tinker`, rồi:

```php
DB::selectOne('SELECT @@hostname AS serverName, DATABASE() AS dbName, VERSION() AS version');
```

Phải cùng server/recipe_shared; B tạo món, C đọc/bình luận, B tải lại xem. LAN01–LAN04 **chưa chạy trên máy thật**.

Nếu B/C chỉ dùng website, A chạy cả Laravel/MariaDB, đặt `APP_URL=http://<IP-LAN-A>:8000` rồi:

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

B/C mở URL trên bằng trình duyệt. Chỉ mở firewall TCP 8000 ở Private, giới hạn IP B/C theo phần 01; không cần mở DB 3306. A phải bật/không Sleep. Đây là demo LAN, không phải triển khai Internet.

## DemoSeeder

Để có 10 công thức tiếng Việt thực tế cho kiểm thử trang chủ, chạy riêng `php artisan db:seed --class=SampleRecipeSeeder` trong local/testing (catalog phải có sẵn). Bộ mẫu gồm trứng chiên hành, gà kho gừng, cơm chiên trứng, hai món canh, hai món chay, bánh kếp, bánh flan và nước chanh; có định lượng, các bước nấu, khẩu phần, thời gian và thẻ. Công thức chưa tải ảnh riêng sẽ dùng ảnh món Việt mặc định đi kèm dự án.

Hai tài khoản `sample.minh@example.test` (Bếp Minh) và `sample.an@example.test` (Bếp An) dùng mật khẩu `Sample12345!`, mỗi người sở hữu 5 món. Dùng tài khoản còn lại để thử bình luận/đánh giá; chỉ chủ công thức được sửa/xóa. Seed chạy trong transaction, tìm món theo chủ sở hữu/tên và bỏ qua món đã có để giữ nội dung bạn đã sửa. Không đổi tên món nếu muốn chạy lại mà không tạo bản mẫu mới. Seeder không được gọi tự động bởi `DatabaseSeeder` và không chạy bộ 30 món bên dưới.

`php artisan db:seed --class=DemoSeeder` tạo **demo.a@example.test**, **demo.b@example.test**, tên Demo A/B; mật khẩu local **Demo12345!**, hash bằng Hash::make. Chỉ dùng local/testing.

30 món `Demo Recipe 01`–`30`: A sở hữu 01–15, B sở hữu 16–30; không ảnh, 5 category/3 difficulty, nhiều mức thời gian, mỗi món 2 nguyên liệu/bước/thẻ. Người còn lại chấm mỗi món, điểm luân phiên 1–5. B có 12 bình luận món 01; A có 3 bình luận món 16–18.

Chạy lại tìm theo email, userId/title, recipeId/userId, nội dung bình luận cố định; không nhân đôi hoặc reset nội dung/mật khẩu đã đổi. Email demo thuộc tài khoản khác khiến toàn bộ lượt seed bị từ chối/rollback. Không truncate, không gọi dịch vụ ngoài, không tự seed catalog; ngoài local/testing bị chặn kể cả `--force`.

## Ảnh công thức lưu local

JPEG/PNG/WebP tối đa 2 MB được lưu trong `storage/app/public/recipes`. Chạy `php artisan storage:link` một lần để tạo đường dẫn `public/storage`. Không cần Cloudinary, API key hoặc kết nối mạng.

Không chọn file khi sửa sẽ giữ ảnh; chọn gỡ để bỏ ảnh, không vừa thay vừa gỡ. Lỗi lưu giữ dữ liệu cũ; lỗi dọn ảnh sau lưu/xóa trả cảnh báo. RecipeController ghi DB tuần tự, lỗi giữa chừng có thể để lại dữ liệu một phần. Mọi post không có ảnh riêng đều hiển thị `public/images/recipe-default.png`.

## Kiểm thử

Chạy tại checkout thử nghiệm. Tạo `recipe_test` trong MariaDB local, cấp quyền cho tài khoản test riêng:

```powershell
if (-not (Test-Path .env.testing)) { Copy-Item .env.testing.example .env.testing }
# Điền DB_USERNAME/DB_PASSWORD riêng trong .env.testing
php artisan key:generate --env=testing
php artisan config:clear
php tests/prepare-database.php
php artisan test --no-ansi
composer check-platform-reqs
```

Tạo DB trước nếu tài khoản test không có quyền CREATE DATABASE. `.env.testing`: APP_ENV=testing, DB_HOST=127.0.0.1, DB_DATABASE=recipe_test, DB_URL trống. Script chuẩn bị đọc env testing, ép host local/recipe_test. PHPUnit cũng ép DB này, TestCase xác nhận DB thực tế trước transaction. Các fixture rollback; không RefreshDatabase, không phụ thuộc DemoSeeder ngoài chính ca kiểm thử seeder. Test ảnh dùng public disk giả lập, không gọi mạng.

CSRF thật cần middleware trên server. Mở PowerShell riêng tại checkout thử nghiệm:

```powershell
php artisan serve --env=testing --host=127.0.0.1 --port=8001 --no-reload
```

Env testing dùng session file/cookie bep_nha_auth_check. Với playwright-cli đã cài:

```powershell
playwright-cli.cmd -s=recipe-check open http://127.0.0.1:8001/register
playwright-cli.cmd -s=recipe-check --raw run-code --filename=tests/browser-auth.js
playwright-cli.cmd -s=recipe-check --raw run-code --filename=tests/browser-recipes.js
playwright-cli.cmd -s=recipe-check --raw eval 'JSON.stringify(window.recipeCheckResults)'
playwright-cli.cmd -s=recipe-check --raw run-code --filename=tests/browser-list.js
playwright-cli.cmd -s=recipe-check --raw run-code --filename=tests/browser-interactions.js
playwright-cli.cmd -s=recipe-check close
```

Script kiểm tra cookie trước ghi, thử CSRF thiếu/sai 419, 375px/1440px, old input, XSS, Hủy/xác nhận xóa, filter, tương tác. Tài khoản test được giữ lại; công thức xóa khi ca hoàn tất, ca bị ngắt có thể để lại fixture. Không chạy trên DB chung.

`php tests/check-schema-migrations.php` luôn tạo database ngẫu nhiên riêng để kiểm tra cài mới, rollback trên DB tạm, tiếp nhận 8 bảng có công thức/ảnh mà không đổi dữ liệu, ràng buộc khóa và chạy migrate/seed lại. Script không migrate database ứng dụng và tự dọn DB tạm.

## Cài sạch và gói bàn giao

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File scripts/package-source.ps1 -OutputPath ..\handover\recipe-source.zip
Expand-Archive ..\handover\recipe-source.zip ..\recipe-install-check
php tests/check-fresh-install.php ..\recipe-install-check D:\LaravelTools\composer.phar
```

Thư mục giải nén phải mới, chưa có .env/vendor. Thay đường dẫn Composer phar theo máy. Script dùng tài khoản DB local có quyền CREATE/DROP, tạo `recipe_install_<random>`, cô lập biến môi trường, kiểm tra đúng DB trống trước ghi, cài Composer/npm từ lock, key/migrations/seed hai lần, kiểm tra migrate lại không đổi dữ liệu, build/Blade/routes/HTTP. Xóa đúng DB tạm và `.env` do script tạo; giữ checkout để xem.

Gói gồm source, lang, scripts/tests, placeholder/build assets, storage trống với .gitignore, lockfile, ví dụ env, README, report/minh chứng, trạng thái và 7 Word trong docs/plans. Không .env thật/vendor/node_modules/session/log runtime/cache config. Script không ghi đè zip cũ, ghi SHA-256 bên cạnh. Kế hoạch Word là đặc tả, không phải chứng nhận kiểm thử.

## Khắc phục lỗi

| Triệu chứng | Kiểm tra |
| --- | --- |
| Không catalog | Đúng DB/8 bảng nền; người phụ trách chạy DatabaseSeeder |
| Không assets | npm ci/build, public/build/manifest.json, không mang public/hot từ máy cũ |
| Không ảnh upload | `php artisan storage:link`, quyền ghi `storage/app/public`, file JPEG/PNG/WebP dưới 2 MB |
| 419 | Tải lại form, session ghi được, token; không tắt middleware |
| DB lỗi | MySQL XAMPP, DB_*, quyền user; che secret trước chia sẻ log |
| Cache/quyền ghi lỗi | storage/bootstrap/cache; config:clear tại checkout thử nghiệm |
| Migration không tạo lại bảng cũ | Đây là bước tiếp nhận schema cũ; xem migrate:status, dùng migration mới khi cần đổi cột |
