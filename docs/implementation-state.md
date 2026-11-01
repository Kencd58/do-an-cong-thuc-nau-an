# Cập nhật ảnh local ngày 02/11/2026

Cloudinary SDK, binding, cấu hình `CLOUDINARY_URL` và script kiểm tra API đã được gỡ khỏi mã nguồn. RecipeController hiện lưu/thay/xóa ảnh bằng Laravel public disk tại `storage/app/public/recipes`; mọi post thiếu ảnh riêng dùng `public/images/recipe-default.png`. Các phần Cloudinary bên dưới được giữ như lịch sử của phiên bản cũ, không còn mô tả runtime hiện tại.

# Cập nhật kiến trúc MVC ngày 13/10/2026

Đã chuyển toàn bộ xử lý create/update/delete của RecipeService vào store/update/destroy trong RecipeController, cùng các hàm private saveChildren/upload/destroyImage. Đã xóa class và thư mục app/Services. Form Request, Model, route, quyền sở hữu, phản hồi và thứ tự xử lý Cloudinary giữ nguyên; AppServiceProvider và config/services.php vẫn là cấu hình chuẩn của Laravel/SDK.

Hồi quy sau khi chuyển: 56 tests / 1961 assertions đạt trên database thử nghiệm riêng, với Cloudinary mock. Ba tài liệu khảo sát, đặc tả UC và Proposal được cập nhật theo Laravel MVC và migration cho toàn bộ 10 bảng. Các mô tả RecipeService trong phần lịch sử bên dưới phản ánh mã nguồn ở thời điểm trước khi đổi kiến trúc.

# Cập nhật quản lý schema ngày 13/10/2026

Toàn bộ 10 bảng nghiệp vụ đã có migration trong `database/migrations`: 8 migration nền và 2 migration ratings/comments. Tên cột camelCase, kiểu dữ liệu, khóa, chỉ mục và collation được giữ nguyên. 8 migration nền bỏ qua bảng đã tồn tại để ghi nhận lịch sử mà không tạo lại bảng; chúng không tự điều chỉnh schema cũ khác chuẩn.

Cài mới dùng `php artisan migrate --seed`; database có dữ liệu dùng `php artisan migrate` sau khi sao lưu, không seed demo hoặc reset dữ liệu. CatalogSeeder chứa trực tiếp 5 danh mục, 10 thẻ và 30 nguyên liệu. Đã loại bỏ SQL tạo/kiểm tra schema cũ, installer SQL và script kiểm thử migration tương tác cũ; kiểm tra schema hiện dùng `tests/check-schema-migrations.php`, luôn chạy trong DB tạm.

Đã kiểm chứng trên MariaDB XAMPP tạm ở cổng 3307: schema mới trùng schema SQL cũ về cột, index, FK và engine/collation; migrate/seed cài mới; rollback DB tạm; migrate lại không đổi dữ liệu; tiếp nhận 8 bảng đã có công thức/ảnh và bảng tương tác không đổi hash dữ liệu/schema. Bộ PHPUnit đạt **56 tests, 1961 assertions**. Database ứng dụng ở cổng 3306 không bị thay đổi; MySQL XAMPP chính đang tắt, nên chưa áp dụng 8 bản ghi migration mới vào DB chính.

Các kết quả này chỉ xác nhận chuyển schema và hồi quy ứng dụng; không thay thế kiểm thử ảnh Cloudinary thật hoặc LAN trên máy nhóm.

## Lịch sử kiểm thử và triển khai trước ngày 13/10/2026

Các mục bên dưới được giữ làm lịch sử. Những ghi chú cũ về SQL, installer hoặc migration nền còn thiếu không còn mô tả phiên bản hiện tại; tham chiếu script cũ trong lịch sử không phải hướng dẫn chạy.

# Trạng thái triển khai

Ngày: 11/10/2026 (Asia/Saigon). Source: thư mục `D:\Đồ án 2026\code`, chưa có Git commit. Tài liệu: v1.1 XAMPP.

## Kết nối Cloudinary thật — cập nhật sau bàn giao phần 06

- Đã dùng tài khoản Cloudinary đang đăng nhập của người dùng, sau khi người dùng xác nhận email; lưu CLOUDINARY_URL riêng trong `.env` và xóa cache cấu hình. Không đổi API key, không đưa secret vào source/log/báo cáo. Trình cấu hình loopback tạm đã dừng và xóa.
- `tests/check-cloudinary.php --live`: chỉ local/opt-in, sử dụng UploadApi binding hiện có, upload ảnh thử 1 pixel có public ID riêng, xác nhận HTTPS trả 200/image rồi destroy ảnh đó. Retry có quyền truy cập mạng đạt exit 0 sau khi sandbox chặn lần đầu. Không ghi DB hoặc đụng ảnh có sẵn.
- Minh chứng: [cloudinary-live-connection.txt](test-report/evidence/cloudinary-live-connection.txt). Đã xác nhận kết nối dịch vụ thật; chưa coi đây là toàn bộ luồng form recipe TC06/09/20. Feature tests vẫn mock, không tự gọi mạng.
- README/report đã cập nhật. ZIP phần 06 trước đó là snapshot cũ; cấu hình thật không thuộc bất kỳ gói source nào. Phần migration nền chưa thay đổi trong lượt kết nối này.

## Feature 06 — Kiểm thử demo cài đặt bàn giao

Tài liệu: `06_Kiem_thu_Demo_Cai_dat_Ban_giao.docx` v1.1 XAMPP. Source: `recipe-feature06-20261011`, không có Git SHA; gói `../handover/recipe-feature06.zip` có checksum riêng. Chi tiết TC01–30, A01–21, lỗi/lượt chạy lại trong [report](test-report/report.md). Các phần phía dưới là lịch sử trước lượt này.

### Đã hoàn thành

- `database/seeders/{CatalogSeeder,DatabaseSeeder,DemoSeeder}.php`: tái sử dụng catalog SQL, default chỉ catalog; DemoSeeder riêng có guard local/testing, 2 user, 30 món, 30 rating chéo, 15 comment; chạy lại không duplicate hoặc sửa dữ liệu đã có; collision email từ chối/rollback. Không gọi Cloudinary.
- `scripts/install-database.php`: chỉ DB local/testing trống, dùng 8 CREATE TABLE có sẵn, bỏ CREATE DATABASE/USE, sau đó migrate --seed. Từ chối DB có bảng với exit code 1, không reset/fresh. Đây là đường cài SQL theo hiện trạng, không thay thế migration nền còn thiếu.
- `tests/Feature/DemoSeederTest.php`: dữ liệu/phân bố/rerun/public show, default seed chỉ catalog, production guard, collision không ghi tài khoản khác. `tests/TestCase.php` thêm guard DB thực tế local recipe_test trước transaction; phpunit.xml ép APP_ENV testing. `tests/prepare-database.php` đọc .env.testing và ép cả host/DB test, `.env.testing.example` không secret.
- `tests/check-fresh-install.php`: kiểm tra cài từ source giải nén mới + lockfiles, DB random riêng, cô lập env subprocess, xác nhận DB trống trước ghi, seed hai lần, chặn installer chạy lại, build/Blade/routes/HTTP kernel; dọn DB tạm và .env do script tạo. npm.cmd dùng đường dẫn tuyệt đối trên Windows.
- README viết lại runtime/extensions, cài mới SQL, thành viên DB chung, test riêng, demo, Cloudinary, LAN, lỗi thường gặp, đóng gói. `docs/contracts.md` có ERD 10 bảng/sơ đồ chức năng/endpoint; `docs/plans` giữ nguyên đủ 7 Word. `docs/test-report` có report và bằng chứng mới.
- `scripts/package-source.ps1` đóng zip bằng allowlist, giữ storage .gitignore, bỏ .env thật/dependency/session/log/cache config, ghi SHA-256 và từ chối ghi đè zip cũ.

### Migration và tác động dữ liệu

Không thêm migration, không đổi schema/route/service/giao diện. Giữ database webcookingrecipe; 8 bảng nền bằng SQL, 2 bảng tương tác bằng migration. Bộ kiểm thử chính dùng transaction recipe_test, browser để lại tài khoản test; fixture migration/install tạo và xóa đúng DB ngẫu nhiên riêng.

Lượt thử installer v1 có lỗi kế thừa env, seed vào DB chính 2 tài khoản/30 món demo và tương tác lúc 12:32:31–32 UTC+7. Đã gỡ đúng dữ liệu vừa tạo theo email/title/timestamp, có guard số lượng, trong transaction. Kiểm tra lại 0 users/recipes/ratings/comments, catalog 5/10/30; không reset/truncate. `updatedAt` catalog đã được seed cập nhật, AUTO_INCREMENT đã tăng và không được khôi phục. Lỗi đã sửa bằng cô lập env và kiểm tra DB tạm trước mọi ghi; bằng chứng và lần chạy lại ở report. Không tuyên bố DB chưa từng bị tác động.

### Kiểm thử thực tế

- Nền: 50 passed/1487 assertions. Sau sửa: **54 passed/2063 assertions**, PHP XAMPP 8.2.12, Laravel 12.69.3, MariaDB 10.4.32; mock Cloudinary.
- Cả 4 browser scripts auth/CRUD/list/interactions chạy lại đạt; CSRF HTTP thật 419, 375px/1440px, XSS/old input/filter/page/owner/cancel-confirm; đã xem ảnh minh chứng.
- `npm.cmd run build`, `composer check-platform-reqs` bằng PHP XAMPP, Pint --test đều đạt.
- Cài sạch từ zip không vendor/node_modules/.env vào thư mục mới: Composer install, npm ci/build, SQL+migration/catalog, demo seed hai lần, guard DB không trống, 19 routes và HTTP kernel list/detail/page3/register/login 200 đạt. DB tạm và file credentials đã dọn.
- `check-interaction-migrations.php --fixture`: hash dữ liệu 8 bảng trước/sau bằng nhau, đủ 10 bảng nghiệp vụ + migrations.
- Report: **26 Pass/4 Bị chặn**; TC01 thiếu migration nền; TC06/09 thiếu ảnh thật; TC20 thiếu luồng Cloudinary thật. LAN01–04 Chưa chạy trên A/B/C.

### Chưa hoàn thành và bước tiếp theo

Chưa đủ điều kiện nghiệm thu toàn bộ: cần phần 01 tiếp nhận 8 bảng nền vào migration mà giữ dữ liệu; cấu hình Cloudinary thật để chạy TC06/09/20; chạy LAN01–04 trên máy nhóm. README cài sạch hiện dùng SQL đúng source, ghi rõ khác kế hoạch migrate-only. Không tạo lại feature 01 trong lượt 06, không thêm tính năng demo vào UI.

Hợp đồng dùng chung thay đổi: **không**. Bản sao source trước lượt sửa đã giữ trong thư mục tạm `recipe-feature06-before-*`, không chứa .env/dependencies; zip bàn giao cuối có checksum.

## Feature 05 — Đánh giá và bình luận

Tài liệu: `05_Danh_gia_va_Binh_luan.docx`, v1.1 XAMPP. Source: `D:\Đồ án 2026\code`, bản ngày 11/10/2026, không có Git commit. Các mục phía dưới là lịch sử bàn giao trước feature 05.

### Đã hoàn thành và file thay đổi

- Thêm `Rating`, `Comment`, quan hệ hai chiều trong `User`/`Recipe`, timestamp camelCase và cast ID/score integer. Hai migration `2026_10_11_000001_create_ratings_table.php`, `2026_10_11_000002_create_comments_table.php`: unique rating theo recipe/user, index bình luận theo recipe/createdAt/id, FK recipe CASCADE và user RESTRICT.
- Thêm `RatingController`, `CommentController` và 5 named routes auth với ID số. Rating upsert giữ createdAt, chỉ cập nhật score/updatedAt; gỡ lại là no-op. Chủ công thức bị cấm đánh giá trước validation. Bình luận kiểm tra thuộc recipe (404), người viết (403) trước validation; không lấy owner/recipe từ payload. Lỗi ghi DB báo chưa lưu được, giữ form, log SQLSTATE/driver code, không log nội dung/SQL bindings hoặc flash success.
- `RecipeController@show` thêm averageRating/ratingCount/myRating/comments; giữ nguyên eager load và dữ liệu Recipe/ảnh. Comments eager load user, thứ tự createdAt DESC/id DESC, paginate 10 với commentsPage. Không lưu cột tổng hợp vào recipes.
- Thêm `recipes/_rating.blade.php`, `recipes/_comments.blade.php`, include cuối show. Form điểm 1–5, cập nhật/gỡ; khách có link login, owner chỉ xem tổng hợp. Bình luận escaped, tên/ngày giờ/nhãn chỉnh sửa, sửa tại chỗ, xác nhận xóa; bag rating/commentCreate/commentUpdate_<id> cô lập old input, lỗi sửa giữ trang, thành công về trang 1.
- `resources/js/app.js`: toggle form sửa bằng JS thuần, tái sử dụng xác nhận xóa với thông điệp theo form. `resources/css/app.css`: khoảng cách, bố cục tương tác, ngắt dòng nội dung dài. Đã build `public/build`.
- Thêm `tests/Feature/RecipeInteractionTest.php`, `tests/browser-interactions.js`, `tests/check-interaction-migrations.php`. `tests/prepare-database.php` chạy migration chỉ trong recipe_test sau xác nhận tên DB. Sửa kiểm thử LIST10: thay giả định ratings/comments chưa tồn tại bằng kiểm tra danh sách public/my không truy vấn hai bảng này. Không đổi logic danh sách.
- Cập nhật README, bản ghi này và minh chứng `docs/evidence/interaction-*`, `browser-interactions-result.json`.

### Migration và tác động dữ liệu

Giữ tên database `webcookingrecipe` theo source cũ, không đổi thành recipe_shared. Đã áp dụng 2 migration vào database chính; hiện có **10 bảng nghiệp vụ + migrations**. 8 bảng nền vẫn được tạo từ SQL, chưa có baseline migration cho chúng. Không chạy migrate:fresh/reset/rollback, không sửa schema cũ hoặc seed dữ liệu cá nhân.

`tests/check-interaction-migrations.php --fixture` tạo một database MariaDB tạm với 8 bảng nền, catalog, user, công thức có imageUrl/imageId, nguyên liệu/bước/thẻ; chạy migration và so sánh SHA-256 nội dung từng bảng trước/sau, đạt, rồi xóa database tạm. Chạy không --fixture trên database chính cũng đạt: 0 users/recipes, catalog 5/10/30 và toàn bộ dữ liệu nền giữ nguyên. Hai bằng chứng: `interaction-migration-fixture.txt`, `interaction-migration-main.txt`.

Feature tests dùng transaction rồi rollback trong `recipe_test`. Browser test tạo dữ liệu riêng, xóa công thức và các tương tác trong finally, để lại tài khoản thử trong recipe_test; không tạo tài khoản/công thức demo trong database chính.

### Kiểm thử thực tế

Môi trường: PHP XAMPP 8.2.12, Laravel 12.69.3, MariaDB 10.4.32, Chromium qua playwright-cli. Đã xem cả 3 ảnh giao diện, không tràn ngang, lỗi hiển thị đúng form.

| Mã | Kết quả và minh chứng |
| --- | --- |
| INT01 | Đạt: điểm 1–5, owner/recipe từ server, avg/count đọc DB, rating độc lập comment |
| INT02 | Đạt: gửi lặp/upsert chỉ một dòng, giữ createdAt/đổi updatedAt, DB chặn duplicate; gỡ/gỡ lại thành công |
| INT03 | Đạt: tự chấm/gỡ 403 trước validation, thiếu/0/6/1.5/mảng/chuỗi sai bị từ chối bằng bag rating |
| INT04 | Đạt: nhiều bình luận, trim, giới hạn 2000 ký tự Unicode; rỗng/2001/mảng sai bị từ chối; owner được bình luận |
| INT05 | Đạt: chỉ tác giả sửa/xóa; chủ recipe và người thứ ba bị 403; không đổi userId/recipeId, hiện Đã chỉnh sửa |
| INT06 | Đạt: bình luận thuộc recipe khác hoặc ID thiếu/sai trả 404, dữ liệu giữ nguyên |
| INT07 | Đạt: guest đọc công khai/ghi về login; HTTP thật cả 5 endpoint trả 419 với CSRF thiếu và sai |
| INT08 | Đạt: 12 bình luận phân trang 10/2, createdAt/id ổn định; lỗi sửa giữ trang 2 và old input ở một form; trang vượt cuối có link về trang 1 |
| INT09 | Đạt: script/tên được escape ở hiển thị và form sửa; browser không thực thi script |
| INT10 | Đạt: xóa recipe cascade tương tác đúng recipe, giữ recipe khác/user/catalog; FK user chặn xóa |
| INT11 | Đạt: migration giữ công thức/ảnh/dữ liệu cũ trong DB thử; migration DB chính giữ 8 bảng; hồi quy auth/CRUD/ảnh mock/search đạt |
| Lỗi DB | Đạt: giả lập lỗi ghi ở cả 5 endpoint, giữ nội dung/bag, không success, log không chứa dữ liệu riêng |

- Trước sửa: `C:\xampp\php\php.exe artisan test --no-ansi`: **39 passed, 1266 assertions** (`interaction-baseline-tests.txt`).
- Sau sửa: cùng lệnh: **50 passed, 1487 assertions** (`interaction-tests.txt`). Test LIST10 ban đầu còn giả định hai bảng tương tác chưa có; đã thay bằng kiểm tra đúng tính độc lập của danh sách, chạy lại toàn bộ đạt.
- `npm.cmd run build`: đạt. Pint `--test` toàn bộ file PHP mới/sửa: đạt. `artisan migrate:status`: cả hai migration ở batch 1, Ran.
- `playwright-cli.cmd -s=recipe-interactions --raw run-code --filename=tests/browser-interactions.js`: đạt; bằng chứng `browser-interactions-result.json`, `interaction-show-375.png`, `interaction-show-1440.png`, `interaction-error-375.png`. Có kiểm tra UI gửi/đổi/gỡ điểm, thêm/sửa/xóa bình luận, Hủy xóa không gửi request, xác nhận chỉ gửi một request.

### Hợp đồng và bước tiếp theo

Không đổi chữ ký RecipeService, routes CRUD, quan hệ cũ, form tạo/sửa Recipe hoặc sắp xếp danh sách. Bổ sung Request injection trong show để lấy user hiện tại và bốn biến cho view. Không Policy, interaction service, reply/like/report/moderation, status hoặc thư viện UI mới.

Chưa hoàn thành/lỗi còn lại trong feature 05: không ghi nhận sau kiểm thử. Cloudinary thật vẫn **Chưa chạy** vì chưa có cấu hình như lịch sử feature 03; các ca ảnh hồi quy sử dụng mock. Bước tiếp theo: feature 06/DemoSeeder khi có yêu cầu; tiếp nhận baseline 8 bảng SQL vẫn là việc riêng của phần 01.

## Feature 04 — Danh sách, tìm kiếm, bộ lọc và phân trang

Tài liệu: `04_Danh_sach_Tim_kiem_Bo_loc.docx`, v1.1 XAMPP. Source: `D:\Đồ án 2026\code`, bản source ngày 11/10/2026, không có thư mục Git/commit SHA. Các phần phía dưới là lịch sử bàn giao trước feature 04.

### Đã hoàn thành và phạm vi sửa

- `app/Http/Requests/ListRecipeRequest.php` (mới): chỉ validate query GET cho cả hai danh sách; trim keyword, giới hạn/exists/distinct, mảng thiếu/rỗng thành [], sort/page mặc định. Lỗi chuyển về URL sạch của route hiện tại với error bag `filters`, không thực hiện truy vấn danh sách bằng input sai, không lặp redirect.
- `app/Models/Recipe.php`: thêm public `scopeFilter(Builder $query, array $filters)`. Tìm chuỗi con ở title, escape `!`, `%`, `_` bằng escape character `!` và binding; AND giữa nhóm, OR trong nhóm tag/ingredient qua whereHas, không nhân bản dòng. Tổng thời gian dùng binding; ba thứ tự cố định có id làm tiêu chí phụ.
- `RecipeController@index`: dùng catalog có sẵn và scope; `MyRecipeController@index`: tái sử dụng quan hệ user()->recipes() để giữ userId đáng tin, thêm scope và kiểm tra có bài thật cho trạng thái rỗng. Cả hai eager load user/category/tags, paginate(12)->withQueryString(). Không đổi các action ghi/show hoặc RecipeService.
- `resources/views/recipes/_filters.blade.php` (mới), `recipes/index.blade.php`, `my/recipes.blade.php`: form GET chung, native checkbox/select với label, đầy đủ bảy nhóm điều khiển, lựa chọn lấy từ `$filters` đã validate, không có input page. Áp dụng quay về trang 1, Xóa bộ lọc về đúng danh sách, lỗi phía trên form.
- `resources/views/recipes/_list.blade.php`: bổ sung thẻ, trạng thái không khớp/chưa có bài/trang vượt cuối, liên kết trang 1 giữ query; tái sử dụng pagination và CSS responsive đã có. Source cũ có thumbnail; đã thông báo trước khi bỏ ảnh tại danh sách cho đúng phạm vi tài liệu 04. Ảnh ở show/create/edit giữ nguyên.
- `tests/Feature/RecipeListTest.php`, `tests/browser-list.js` (mới): kiểm thử LIST01–LIST10 và HTTP/trình duyệt. `README.md`, bản ghi này và `docs/evidence/list-*`: hướng dẫn và minh chứng.

### Migration và tác động dữ liệu

Không migration hoặc đổi schema/catalog; không chạy migrate:fresh, không seed/reset database chính. Giữ database `webcookingrecipe` theo source hiện có, không đổi sang `recipe_shared`. Feature tests chỉ dùng `recipe_test`, xác nhận tên DB trước transaction; xóa fixture cũ trong transaction rồi rollback khôi phục. Mỗi test danh sách có 15 công thức, hai chủ sở hữu, nhiều nhóm tag/ingredient. Browser tạo riêng 13 công thức rồi xóa trong finally qua endpoint hiện có; để lại một tài khoản test trong `recipe_test`.

### Kiểm thử thực tế

Môi trường: PHP XAMPP 8.2.12, Laravel 12.69.3, MariaDB 10.4.32 với schema/collation hiện có; Chromium qua playwright-cli. Không dùng SQLite thay MariaDB.

| Mã | Kết quả |
| --- | --- |
| LIST01 | Đạt: `/` và `/recipes` tương đương, mặc định newest, 12/3 mục từ 15 bản ghi; query rỗng không lọc |
| LIST02 | Đạt: chỉ tìm title, trim, tiếng Việt, `%`, `_`, `!`, backslash, keyword `0`, chuỗi giống SQL được coi là dữ liệu |
| LIST03 | Đạt: từng bộ lọc và AND giữa toàn bộ nhóm; tổng thời gian đúng biên |
| LIST04 | Đạt: OR trong nhóm, nhiều thẻ/nguyên liệu khớp không nhân bản công thức |
| LIST05 | Đạt: newest/title/time và tiêu chí id khi giá trị bằng nhau |
| LIST06 | Đạt: pagination giữ query và lựa chọn; form GET không page, đổi sort từ trang 2 về trang 1 trên cả hai danh sách |
| LIST07 | Đạt: guest về login; gửi userId của A hoặc array userId không làm B thấy bài A |
| LIST08 | Đạt: 24 dạng query sai trên cả 3 URL; lỗi đúng bag, không truy vấn recipes trước redirect, trang sạch trả 200, lỗi flash chỉ hiển thị một lần |
| LIST09 | Đạt: kết quả rỗng, người chưa có bài, trang vượt cuối và liên kết trang 1 giữ bộ lọc |
| LIST10 | Đạt: auth/CRUD/show/menu hồi quy; card đủ thông tin/thẻ, không ảnh danh sách; không cần bảng ratings/comments |

- Trước sửa: `C:\xampp\php\php.exe artisan test --no-ansi`: **29 passed, 431 assertions**, `docs/evidence/list-baseline-tests.txt`.
- Sau sửa: cùng lệnh: **39 passed, 1266 assertions**, `docs/evidence/list-tests.txt`.
- `npm.cmd run build`: thành công. Pint kiểm tra các file PHP thay đổi: đạt.
- `playwright-cli.cmd -s=recipe-list --raw run-code --filename=tests/browser-list.js`: đạt, 13 công thức, phân trang 12/1. Cả public/my: giữ query/lựa chọn, submit về trang 1, xóa đúng route, lỗi redirect sạch, trạng thái rỗng/vượt trang; không tràn ngang ở 375px/1440px hoặc lỗi JavaScript. Kết quả: `docs/evidence/browser-list-result.json`; ảnh: `list-public-*`, `list-my-*`, `list-errors-*`. Đã xem ảnh kiểm tra bố cục.

### Hợp đồng và bước tiếp theo

Giữ named routes `recipes.index`, `my.recipes.index`, toàn bộ URL và `$recipes` paginator. Bổ sung `$filters`, `$categories`, `$tags`, `$ingredients`, `$difficulties`; `$hasRecipes` chỉ phục vụ trạng thái danh sách cá nhân. Giữ tên quan hệ user/category/ingredients/steps/tags; ingredients vẫn là HasMany recipe_ingredients nên scope dùng ingredientId tại bảng con. Không status, QueryService/Repository, API hoặc phụ thuộc mới.

Chưa hoàn thành/lỗi còn lại trong feature 04: không ghi nhận sau các kiểm thử trên. Cloudinary thật vẫn **Chưa chạy** như lịch sử feature 03; không thuộc thay đổi lần này. Bước tiếp theo: feature 05 bổ sung đánh giá/bình luận vào show; không cần triển khai lại danh sách hoặc sửa nghiệp vụ lưu công thức.

## Feature 03 — CRUD Recipe, cập nhật ngày 11/10/2026

Tài liệu: `03_CRUD_Recipe.docx`, v1.1 XAMPP. Source: `D:\Đồ án 2026\code`, bản source ngày 11/10/2026, chưa có Git commit. Các mục Feature 02 phía dưới là bản ghi lịch sử trước lượt này.

### Đã hoàn thành

- CRUD công thức và công khai ngay sau lưu; `/` là alias index, `/recipes` mang tên `recipes.index`; danh sách công khai/cá nhân sắp xếp `createdAt DESC, id DESC`, 12 mục/trang.
- Auth bảo vệ create/store/edit/update/destroy/my; owner kiểm tra phía server, Update authorize trước validation/upload. ID số, ID không tồn tại 404, người khác 403; CSRF cho mọi request ghi.
- Request chung/kế thừa, allowlist scalar/từng dòng, 1–50 nguyên liệu khác nhau, lượng tối đa 3 chữ số thập phân, 1–30 bước, tổng thời gian > 0, catalog ID hợp lệ. Thiếu tagIds thành []; service đánh stepOrder 1..N, không nhận userId/imageUrl/imageId/stepOrder từ client.
- Form Blade chung multipart POST, Update giả lập PUT, old input/lỗi sát dòng; JS thuần thêm/bỏ/đánh lại chỉ số, gợi ý defaultUnit khi đơn vị chưa nhập. Nhắc chọn lại file sau lỗi, không tạo catalog từ form.
- Chi tiết đầy đủ, ảnh trong trang, placeholder cục bộ/fallback một lần; nội dung escape. Chỉ owner thấy sửa/xóa, xóa có xác nhận; Hủy không gửi request.
- Cloudinary SDK 3.1.3 qua container/config, mock UploadApi trực tiếp; không thêm service/interface ảnh. Upload trước ghi DB, dọn ảnh cũ sau lưu đầy đủ hoặc sau xóa; lỗi dọn trả warning, lỗi upload về form. Thiếu/sai cấu hình không chặn CRUD không ảnh.
- Ghi tuần tự theo tài liệu. Lỗi lưu giữa chừng báo “Thao tác chưa hoàn tất, vui lòng kiểm tra lại.” và log operation/id/loại lỗi, không secret/SQL bindings; không tự xóa dữ liệu hoặc hứa rollback.

### File mới/sửa và lý do

- `app/Models/{Recipe,Category,Tag,Ingredient,RecipeIngredient,RecipeStep}.php`, `User.php`, `config/recipe.php`: quan hệ/cấu hình nền còn thiếu, khớp SQL camelCase.
- `app/Http/Requests/{StoreRecipeRequest,UpdateRecipeRequest}.php`: validation/quyền; `app/Services/RecipeService.php`, `app/Exceptions/RecipeUploadException.php`: dữ liệu/ảnh và lỗi upload.
- `app/Http/Controllers/{RecipeController,MyRecipeController}.php`, `routes/web.php`: endpoint theo hợp đồng, thay trang chủ tạm bằng danh sách.
- `resources/views/recipes/`, `resources/views/my/recipes.blade.php`, `resources/js/app.js`, `resources/css/app.css`, `public/images/recipe-placeholder.svg`, `lang/vi/validation.php`: giao diện responsive/form động/lỗi tiếng Việt. Menu hiện có tự dùng named routes mới.
- `composer.json`, `composer.lock`, `config/services.php`, `app/Providers/AppServiceProvider.php`, `.env.example`: SDK/cấu hình, không khóa Cloudinary thật.
- `tests/prepare-database.php`: mở rộng database test thành 8 bảng/catalog; `RecipeCrudTest.php`, `browser-recipes.js`: ca CRUD. `AuthTest.php`, `browser-auth.js`: đổi URL đích hợp lệ sang `/recipes` khi route thật đã có.
- `README.md`, `docs/evidence/`, bản ghi này: hướng dẫn/minh chứng.

### Migration và tác động dữ liệu

Không migration, không đổi schema, không reset/seed database chính. Giữ tên `webcookingrecipe` theo source thay cho `recipe_shared`. Model/config nền thiếu được bổ sung đúng mức cần cho feature 03, không triển khai lại phần 01. Database test `recipe_test` có 8 bảng/catalog; feature tests rollback, browser chỉ tạo dữ liệu test. Lượt browser thử bị ngắt để lại một công thức test, không phải dữ liệu chính. Kiểm tra cuối: database chính 0 users, 0 recipes, catalog 5/10/30, đủ 8 bảng và các ràng buộc.

### Kiểm thử thực tế

Laravel 12.69.3, PHP XAMPP 8.2.12, MariaDB 10.4.32, Cloudinary PHP 3.1.3, Chromium qua playwright-cli; không thay MariaDB bằng SQLite.

| Mã | Kết quả và bằng chứng |
| --- | --- |
| REC01 | Đạt: tạo không ảnh, owner đáng tin, quan hệ/công khai; feature + browser |
| REC02 | Đạt với mock: upload trước DB, lưu HTTPS secure_url/public_id; chưa thử dịch vụ thật |
| REC03 | Đạt: thiếu/sai/trùng/quá giới hạn, tổng thời gian 0, khóa thừa; không upload/ghi DB |
| REC04 | Đạt: thay dữ liệu con, bước 1..N, thiếu tagIds bỏ hết thẻ, id/userId giữ nguyên |
| REC05 | Đạt với mock: giữ/thay/gỡ ảnh, đúng thứ tự upload/ghi/destroy |
| REC06 | Đạt: ảnh giả/SVG/quá cỡ, vừa thay vừa gỡ; upload lỗi giữ công thức cũ; thiếu cấu hình vẫn dùng CRUD không ảnh |
| REC07 | Đạt với mock: dọn ảnh lỗi sau update/delete có warning, kết quả DB đã thay đổi |
| REC08 | Đạt: guest về login, sai owner 403 kể cả payload sai/ảnh; HTTP thật thiếu/sai CSRF đều 419 |
| REC09 | Đạt: cascade, catalog giữ nguyên; browser Hủy không request, xác nhận xóa một request |
| REC10 | Đạt: guest xem được, ID mất/không phải số 404, XSS dạng văn bản |
| REC11 | Đạt: form 375px/1440px, old rows/tags, dòng động/thứ tự, fallback một lần; không status, Policy, API hoặc route ảnh |
| Hồi quy | Auth 13 ca và browser auth đạt; quan hệ/catalog/phân trang được kiểm thử |

- Trước sửa: PHP XAMPP `artisan test --compact --no-ansi`: **13 passed, 163 assertions**, `docs/evidence/recipe-baseline-tests.txt`.
- Sau sửa: cùng lệnh: **29 passed, 431 assertions**, `docs/evidence/recipe-tests.txt`.
- `npm.cmd run build`: thành công; `composer check-platform-reqs` trên PHP XAMPP và Pint các file PHP thay đổi: đạt.
- `playwright-cli -s=recipe-crud --raw run-code --filename=tests/browser-auth.js`: đạt toàn bộ auth browser.
- `playwright-cli -s=recipe-crud --raw run-code --filename=tests/browser-recipes.js`: đạt; đọc `window.recipeCheckResults` lưu `docs/evidence/browser-recipes-result.txt`. Ảnh `recipe-create-*`, `recipe-show-*`, `recipe-edit-375.png`, `recipe-errors-375.png`, `recipe-my-375.png`; đã xem ảnh kiểm tra bố cục.
- Kiểm tra chỉ đọc database chính: `docs/evidence/recipe-database-check.txt`, cả 10 kiểm tra bằng 1. HTTP thật `/recipes` cổng 8000 trả 200.

### Chưa hoàn thành và bước tiếp theo

Upload/thay/xóa Cloudinary **thật: Chưa chạy**, do `.env` chưa có CLOUDINARY_URL. Khi có cấu hình, kiểm tra vòng tạo có ảnh → thay ảnh → gỡ/xóa để xác nhận quyền/kết nối tài khoản. Các ca ảnh hiện dùng mock; không khẳng định kết nối thật đã đạt.

Hợp đồng chung: không đổi schema/auth; named routes Recipe, model và config bổ sung theo tài liệu. Redirect auth dùng `/recipes` đúng cơ chế đã có. Tiếp nhận baseline SQL vào migration còn thuộc phần 01; danh sách cơ bản dùng ngay cho phần 04, không phụ thuộc rating/comment phần 05.

## Nền ban đầu và phạm vi (lịch sử Feature 02)

Lượt trước chỉ tạo database `webcookingrecipe` trực tiếp bằng `database/WebCookingRecipe.sql`. Có 8 bảng và catalog 5 danh mục, 10 thẻ, 30 nguyên liệu; chưa có source Laravel, composer.lock, model, layout hoặc test. Vì thế không có bộ test ứng dụng ban đầu để chạy. Database không có bảng migrations.

Yêu cầu lần này: tiếp tục từ tài liệu 02. Đã bổ sung nền Laravel tối thiểu cần cho tài khoản, không triển khai lại toàn bộ feature 01 hoặc mở rộng sang CRUD. Tên database theo yêu cầu người dùng thay cho `recipe_shared` trong đặc tả.

## Feature 02 — đã hoàn thành

- Laravel 12.69.3, Blade, PHP XAMPP 8.2.12, MariaDB 10.4.32; Composer lock khóa môi trường PHP 8.2.12. Node 24.21.0, Vite 7.3.7.
- GET/POST register và login dùng guest; POST logout dùng auth. Đúng tên register, register.store, login, login.store, logout.
- Name được trim; email trim/lowercase trước validation/lưu. Validation tiếng Việt, email unique; name 2–100, email tối đa 254, password ít nhất 8 và confirmed. Không trim mật khẩu, không lưu confirmation, không nhận role/id.
- Hash::make một lần, cấu hình Argon2id (XAMPP hỗ trợ) để không cắt mật khẩu dài. User dùng Authenticatable, createdAt/updatedAt, rememberToken, hidden password/rememberToken; không có hashed cast.
- Đăng ký tự đăng nhập; đăng nhập regenerate session và intended redirect; logout invalidate session và đổi CSRF token. Redirect dùng recipes.index nếu có, ngược lại trang chủ. Middleware Laravel chuẩn, không thêm middleware tùy biến.
- Duplicate đúng index users_email_unique khi ghi được chuyển thành lỗi email. Lỗi database khác được ghi mã SQLSTATE/driver và báo chung; không log SQL/bindings có dữ liệu tài khoản.
- Blade có @csrf, label, autocomplete, old name/email, lỗi theo trường, mật khẩu để trống sau lỗi; thông báo 419 tiếng Việt. Layout chung có flash success/warning/error, menu theo session, kiểm tra route Recipe trước khi tạo liên kết.
- Giao diện đã kiểm tra trực tiếp ở 375px và 1440px; sửa font tiêu đề để dấu tiếng Việt hiển thị đúng. Có trang chủ tạm phục vụ tài khoản, chưa tạo recipes.index giả.

## File chính

- `composer.json`, `composer.lock`, `artisan`, `bootstrap/`, `config/`, `public/`, `storage/`, `app/Providers/`: nền Laravel chuẩn; bỏ script migrate tự động và gói UI/queue không cần.
- `app/Models/User.php`, `app/Http/Controllers/AuthController.php`, `routes/web.php`, `bootstrap/app.php`: xác thực và điều hướng.
- `resources/views/layouts/app.blade.php`, `resources/views/auth/`, `resources/views/errors/419.blade.php`, `resources/views/welcome.blade.php`: form, menu, lỗi.
- `resources/css/app.css`, `resources/js/app.js`, `vite.config.js`, `package.json`, `package-lock.json`: giao diện CSS thuần và build Vite, không SPA/UI framework.
- `lang/vi/validation.php`: lỗi tiếng Việt.
- `.env.example`, `.gitignore`: mẫu cấu hình không secret; `.env` local có APP_KEY riêng, không đưa vào Git.
- `phpunit.xml`, `tests/Feature/AuthTest.php`, `tests/prepare-database.php`, `tests/browser-auth.js`: kiểm thử database riêng và HTTP thật.
- `README.md`, `docs/evidence/`: cách chạy và minh chứng.

## Migration và tác động dữ liệu

Không chạy migration, không sửa schema và không seed lại database chính. `webcookingrecipe` giữ 8 bảng, 7 FK (3 CASCADE/4 RESTRICT), 6 UNIQUE, catalog 5/10/30. Có 0 user ở thời điểm bắt đầu; vẫn 0 sau kiểm thử. Không thay đổi firewall, cấu hình MariaDB hoặc tài khoản MySQL. Ứng dụng local dùng root XAMPP đã có.

Chỉ tạo database riêng `recipe_test`, bảng users theo schema SQL có sẵn. Feature tests dùng transaction/rollback; browser test để lại các tài khoản thử có tên AUTH_BROWSER_CHECK và email browser-<UUID>@example.test tại database test. Không có tài khoản thử trong database chính.

## Kết quả kiểm thử thực tế

| Mã | Bằng chứng | Kết quả |
| --- | --- | --- |
| AUTH01 | Feature test + trình duyệt: normalize, hash, timestamp, đăng nhập ngay, chống gán id/role; mật khẩu dài/phân biệt hậu tố | Đạt |
| AUTH02 | Email trùng khác hoa/thường, xác nhận sai, thiếu/sai kiểu/độ dài; giả lập insert đồng thời bằng MariaDB thật | Đạt |
| AUTH03 | Đăng nhập đúng/sai/không tồn tại, giữ khoảng trắng password, đổi session, intended URL, fallback recipes.index | Đạt |
| AUTH04 | POST logout hủy dữ liệu phiên/token; route ghi test chuyển login; replay cookie/token cũ qua HTTP bị 419; GET logout 405 | Đạt |
| AUTH05 | Guest vào route auth chuyển login; thành viên vào GET/POST guest chuyển trang chủ/recipes.index, không lặp | Đạt |
| AUTH06 | HTTP thật môi trường local cổng 8001: POST register/login/logout thiếu CSRF đều 419; token sai cũng 419, thông báo tiếng Việt | Đạt |
| AUTH07 | Chromium ở 375px/1440px, ảnh được kiểm tra; không tràn ngang, old email/name giữ, password/confirmation trống | Đạt |

Lệnh đã chạy: PHP XAMPP `artisan test --compact --no-ansi`: **13 tests passed, 163 assertions**. Không dùng kết quả feature test để thay cho CSRF HTTP thật. `npm.cmd run build`: thành công. `composer check-platform-reqs`: đạt trên PHP 8.2.12. Script kiểm tra database SQL: cả 10 giá trị kiểm tra bằng 1.

Minh chứng: `docs/evidence/auth-tests.txt`, `browser-auth-result.txt`, `database-check.txt`, ảnh register/login ở 375px và 1440px, ảnh lỗi đăng ký.

## Giới hạn và bước tiếp theo

Feature 02 đã hoạt động. Phần 01 ngoài SQL và nền cần cho tài khoản chưa hoàn thiện: chưa có migration/seed Laravel cho 8 bảng, các model/quan hệ Recipe/catalog, config/recipe.php hoặc FoundationTest. Chưa ghi nhận database hiện có vào lịch sử migration; phải thiết kế tiếp nhận baseline không phá dữ liệu trước khi dùng migrate. Không chạy migrate:fresh/reset/rollback để chữa vấn đề này.

Tiếp tục tài liệu 03 sau khi bổ sung các phần nền Recipe cần thiết. Chưa triển khai Cloudinary, CRUD, tìm/lọc, rating/comment. Không thêm admin, reset password, xác minh email, remember-me UI, Policy hoặc API.

Hợp đồng route/schema phần 02 được giữ; khác biệt môi trường có chủ đích: database chính tên `webcookingrecipe`, source đầu vào thiếu nền Laravel nên chỉ bổ sung phần cần chạy tài khoản. Quan hệ User.recipes sẽ bổ sung cùng model Recipe; hiện chưa có thành phần nghiệp vụ Recipe để gọi.
