# Cập nhật ảnh local ngày 02/11/2026

Runtime hiện tại không còn Cloudinary hay API ảnh bên ngoài. Upload/thay/gỡ ảnh dùng Laravel public disk; post không có ảnh riêng hiển thị `public/images/recipe-default.png`. Các kết quả Cloudinary phía dưới là bằng chứng lịch sử của phiên bản trước và không áp dụng cho mã nguồn hiện tại.

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

# Báo cáo kiểm thử và bàn giao phần 06

Ngày 11/10/2026, Asia/Saigon. Source: `recipe-feature06-20261011`, thư mục code không có Git commit. Gói cuối `recipe-feature06.zip` kèm SHA-256; các lệnh và fixture chính xác nằm trong tests và evidence được liên kết dưới đây. Đây là kết quả thực tế, không xác nhận toàn bộ kế hoạch đã đạt.

## Cập nhật kết nối Cloudinary sau snapshot phần 06

Ngày 11/10/2026 đã cấu hình CLOUDINARY_URL thật trên máy hiện tại, giữ secret trong .env. `php tests/check-cloudinary.php --live` qua UploadApi binding của ứng dụng đạt upload có chữ ký, HTTPS delivery HTTP 200/image và destroy ảnh thử; exit code 0, không ghi database. [Bằng chứng](evidence/cloudinary-live-connection.txt).

Điều kiện thiếu tài khoản/kết nối Cloudinary đã được giải quyết. Chưa chạy lại toàn bộ TC06/09/20 qua form recipe với ảnh thật, nên không đổi các ca đó thành Pass chỉ từ kiểm tra kết nối. Các trạng thái và tổng 26 Pass/4 Bị chặn phía dưới là kết quả snapshot bàn giao trước cập nhật này; lý do tiếp tục chờ ở nhóm ảnh hiện là kiểm chứng luồng đầy đủ. ZIP cũ chưa chứa bản cập nhật này hoặc script kiểm tra kết nối.

## Môi trường và điều kiện đầu

Windows, XAMPP PHP 8.2.12, MariaDB 10.4.32, Laravel 12.69.3, Composer 2.10.3, Node 24.21.0/npm 11.19.0/Vite 7.3.7; trình duyệt Chromium qua playwright-cli. Feature tests: mysql 127.0.0.1/recipe_test, transaction rollback; fixture độc lập DemoSeeder. Browser: server local 8001, session file/cookie bep_nha_auth_check, middleware CSRF bật. Cloudinary mock, không dịch vụ thật. Lượt chạy này dùng tài khoản DB local hiện có; hướng dẫn bàn giao yêu cầu cấu hình tài khoản testing riêng.

Tài liệu phần 01 chưa được triển khai đủ migration nền: 8 bảng SQL và 2 migration tương tác. DB chính vẫn webcookingrecipe. Phần 06 giữ nguyên schema/route/giao diện; bổ sung installer SQL cho database trống và ghi rõ khác biệt với quy trình migrate-only trong Word.

## Lệnh và bằng chứng

| Nhóm | Lệnh và kết quả |
| --- | --- |
| Nền trước sửa | `C:\xampp\php\php.exe artisan test --no-ansi`: [50 passed/1487 assertions](evidence/baseline-tests.txt) |
| Hồi quy sau sửa | Cùng lệnh: [54 passed/2063 assertions](evidence/TC01-TC30-feature-tests.txt) |
| Seed | `artisan test --filter=DemoSeederTest --no-ansi`: [4 passed/426 assertions](evidence/TC19-demo-seeder.txt) |
| Browser auth | `playwright-cli -s=recipe-handover --raw run-code --filename=tests/browser-auth.js`: [đạt](evidence/TC03-TC04-TC16-browser.txt) |
| Browser CRUD | `run-code --filename=tests/browser-recipes.js`, sau đó đọc `window.recipeCheckResults`: [đạt](evidence/TC06-TC17-browser.txt) |
| Browser filter | `run-code --filename=tests/browser-list.js`: [đạt](evidence/TC12-TC15-browser.txt) |
| Browser tương tác | `run-code --filename=tests/browser-interactions.js`: [đạt](evidence/TC21-TC29-browser.txt) |
| Migration phần 05 | `php tests/check-interaction-migrations.php --fixture`: [đạt, hash 8 bảng không đổi](evidence/TC30-migration.txt) |
| Assets/platform | `npm.cmd run build`: [đạt](evidence/TC18-build.txt); Composer với PHP XAMPP `check-platform-reqs`: [đạt](evidence/TC18-platform.txt) |
| Cài source sạch | `php tests/check-fresh-install.php <source-giải-nén-mới> <composer.phar>`: [log cài và kiểm tra](evidence/TC18-fresh-install.txt) |
| PHP style | Pint --test các file PHP mới/sửa: [kết quả](evidence/php-style.txt) |

Thời gian chạy của từng ca nằm trong log PHPUnit. Tên test xác định nhánh/fixture; số ngẫu nhiên trong email và DB tạm chỉ để tránh trùng. Script không ghi mật khẩu, cookie, API key vào bằng chứng. Browser test để lại tài khoản thử trong recipe_test; công thức của lượt hoàn tất đã xóa.

## TC01–TC30

`Pass (mock)` chỉ chứng minh logic với Cloudinary mock. Ca bao gồm dịch vụ ảnh thật được ghi Bị chặn dù phần mock đã đạt. Cột bước/input trỏ đến test chạy được để giữ đầy đủ payload, thứ tự gọi và assertion.

| TC | Bước/input và mong đợi | Thực tế, trạng thái và minh chứng |
| --- | --- | --- |
| TC01 | DB trống, migrate và seed catalog hai lần; 10 bảng, catalog 5/10/30 | **Bị chặn đối với migrate-only** do thiếu baseline migration. Installer SQL + 2 migration và catalog chạy lại đã kiểm tra; [seed](evidence/TC19-demo-seeder.txt), [cài sạch](evidence/TC18-fresh-install.txt) |
| TC02 | Nạp user/category/ingredients/steps/tags/ratings/comments, camelCase | **Pass**: RecipeCrudTest REC01/11, Interaction INT01/02; [feature log](evidence/TC01-TC30-feature-tests.txt) |
| TC03 | Register hợp lệ; email trùng/khác hoa thường; hash, tự login | **Pass**: AuthTest AUTH01/02, [browser](evidence/TC03-TC04-TC16-browser.txt) |
| TC04 | Login đúng/sai/unknown; logout, phát lại session | **Pass**: AUTH03/04, thông báo chung, mất quyền ghi; [browser](evidence/TC03-TC04-TC16-browser.txt) |
| TC05 | Guest list/detail; ID thiếu/sai | **Pass**: REC01/10, DemoSeederTest GET cả 30 món 200; ID thiếu 404; [feature](evidence/TC01-TC30-feature-tests.txt) |
| TC06 | Create đủ không ảnh rồi có ảnh | **Bị chặn phần ảnh thật**; không ảnh/browser và upload mock đạt REC01/02; [feature](evidence/TC01-TC30-feature-tests.txt), [browser](evidence/TC06-TC17-browser.txt) |
| TC07 | Field thiếu, nguyên liệu trùng, tổng thời gian 0 | **Pass**: REC03/06, lỗi field, không DB/upload; [feature](evidence/TC01-TC30-feature-tests.txt), [ảnh lỗi](evidence/TC17-recipe-errors-375.png) |
| TC08 | Update scalar/nguyên liệu/bước; bỏ tagIds | **Pass**: REC04, owner giữ, bước đánh lại, tags rỗng; [browser](evidence/TC06-TC17-browser.txt) |
| TC09 | Giữ/thay/gỡ ảnh, lỗi upload/dọn ảnh | **Bị chặn phần Cloudinary thật**; REC05–07 **Pass (mock)** đúng thứ tự/URL-ID/warning; [feature](evidence/TC01-TC30-feature-tests.txt) |
| TC10 | B sửa/xóa bài A, cả payload sai | **Pass**: REC08, 403 trước validation, không DB/Cloudinary; [feature](evidence/TC01-TC30-feature-tests.txt) |
| TC11 | Hủy rồi xác nhận xóa, cascade và catalog | **Pass**: không request khi hủy, xác nhận một request, REC09/INT10 cascade; [browser](evidence/TC06-TC17-browser.txt) |
| TC12 | My recipes với query userId khác/mảng | **Pass**: LIST07 chỉ user session; [feature](evidence/TC01-TC30-feature-tests.txt), [browser](evidence/TC12-TC15-browser.txt) |
| TC13 | Từng filter và nhiều nhóm; nhiều tag/ingredient | **Pass**: LIST02–04, AND nhóm/OR trong nhóm, không trùng; [feature](evidence/TC01-TC30-feature-tests.txt) |
| TC14 | newest/title/time, trang 2, đổi filter | **Pass**: LIST05/06, ổn định id, query giữ, về trang 1; [browser](evidence/TC12-TC15-browser.txt), [375px](evidence/TC14-list-375.png) |
| TC15 | 24 kiểu query sai, không kết quả, page vượt cuối | **Pass**: LIST08/09, lỗi sạch không lặp; [feature](evidence/TC01-TC30-feature-tests.txt), [browser](evidence/TC12-TC15-browser.txt) |
| TC16 | Guest ghi; HTTP thật CSRF thiếu/sai | **Pass**: auth redirect login; POST/PUT/DELETE thật trả 419; [auth](evidence/TC03-TC04-TC16-browser.txt), [CRUD](evidence/TC06-TC17-browser.txt) |
| TC17 | Form 375px/1440px, old input, script text | **Pass**: browser không overflow/thực thi script; đã xem ảnh [register](evidence/TC17-register-375.png), [old input](evidence/TC17-recipe-errors-375.png), [show](evidence/TC17-recipe-show-1440.png) |
| TC18 | Composer/npm từ lock, build, source mới/DB mới | **Pass theo cách cài SQL được ghi README**, không phải migrate-only; [build](evidence/TC18-build.txt), [fresh install](evidence/TC18-fresh-install.txt) |
| TC19 | Seed riêng hai lần; guard production/collision | **Pass**: 2 user, 30 recipe/ratings, 15 comments, đúng phân bố, snapshot lần hai không đổi; [seed](evidence/TC19-demo-seeder.txt), [cài mới](evidence/TC18-fresh-install.txt) |
| TC20 | Đăng ký → tạo/sửa/tìm/xóa/logout với ảnh thật | **Bị chặn**: chưa có CLOUDINARY_URL thật. Luồng không ảnh đã chạy browser; chưa có bằng chứng upload mạng thật |
| TC21 | B chấm bài A, đổi điểm/gửi lặp | **Pass** INT01/02, một dòng và avg/count; [feature](evidence/TC01-TC30-feature-tests.txt), [browser](evidence/TC21-TC29-browser.txt) |
| TC22 | Gỡ rồi gỡ lại | **Pass** INT02, count đúng/lần hai no-op; [feature](evidence/TC01-TC30-feature-tests.txt) |
| TC23 | A tự chấm/gỡ; điểm thiếu, 0,6,1.5,mảng | **Pass** INT03, 403 trước validation/đúng bag; [feature](evidence/TC01-TC30-feature-tests.txt) |
| TC24 | Nhiều comment; rỗng/2001 ký tự/mảng | **Pass** INT04, trim/Unicode 2000, sai bị từ chối; [feature](evidence/TC01-TC30-feature-tests.txt) |
| TC25 | Tác giả sửa/xóa; owner recipe và người khác thử | **Pass** INT05, chỉ tác giả được ghi, còn lại 403; [feature](evidence/TC01-TC30-feature-tests.txt), [browser](evidence/TC21-TC29-browser.txt) |
| TC26 | Comment của recipe khác trong URL | **Pass** INT06, 404 trước validation/DB không đổi; [feature](evidence/TC01-TC30-feature-tests.txt) |
| TC27 | 12 comment, sửa sai ở trang 2 | **Pass** INT08, phân trang 10/2, old input đúng form; [browser](evidence/TC21-TC29-browser.txt), [ảnh lỗi](evidence/TC27-error-375.png) |
| TC28 | Guest đọc/ghi; CSRF thật cả 5 endpoint | **Pass** INT07, guest đọc được/auth ghi, 419 thiếu và sai token; [browser](evidence/TC21-TC29-browser.txt) |
| TC29 | Xóa recipe có rating/comment | **Pass** INT10, cascade đúng recipe, user/catalog/recipe khác giữ; [feature](evidence/TC01-TC30-feature-tests.txt) |
| TC30 | Thêm hai migration trên 8 bảng có dữ liệu/ảnh | **Pass**: fixture DB tạm, hash 8 bảng không đổi, đủ 10 bảng; hồi quy đạt; [migration](evidence/TC30-migration.txt), [feature](evidence/TC01-TC30-feature-tests.txt) |

Tổng: **26 Pass, 4 Bị chặn** (TC01, TC06, TC09, TC20). Chưa đủ điều kiện tuyên bố toàn bộ nghiệm thu đạt. LAN01 cùng server/DB, LAN02 đọc/ghi liên máy, LAN03 xác nhận DB testing trên từng máy và LAN04 sao lưu/migrate liên nhóm đều **Chưa chạy** trên mạng thật. Kiểm tra local recipe_test không thay thế LAN03.

## Đối chiếu A01–A21

| A | Tiêu chí | TC/bằng chứng hoặc source | Kết luận |
| --- | --- | --- | --- |
| A01 | Vai trò/dữ liệu/nghiệp vụ/phạm vi | [00](../plans/00_Huong_dan_va_hop_dong_chung.docx), [contracts](../contracts.md) | Có tài liệu |
| A02 | CSDL/ERD/quan hệ | contracts; SQL, migration, TC02/30 | Có ERD 10 bảng; kiểm thử quan hệ đạt |
| A03 | Migration/model/relationship | TC01/02/30 | Thiếu migration 8 bảng nền |
| A04 | Phân quyền | TC10/12/23/25/26 | Pass |
| A05 | Route/controller/view | [routes](evidence/routes.txt), contracts, TC05–17 | Pass trong ca đã chạy |
| A06 | Use case/ERD/chức năng | 00 và contracts | Đủ sơ đồ trong bộ tài liệu |
| A07 | CRUD chính | TC06–11 | Không ảnh/mock đạt, ảnh thật bị chặn |
| A08 | Tìm/lọc/sort/page | TC12–15 | Pass |
| A09 | Laravel MVC | app/Http, Models, Services, resources/views; contracts | Có cấu trúc theo hợp đồng |
| A10 | Validation | TC03/07/15/23/24/27 | Pass |
| A11 | Đăng nhập/đăng xuất/auth | TC03/04/16 | Pass |
| A12 | Blade/layout | TC17/27, ảnh evidence | Pass 375px/1440px đã chạy |
| A13 | Ổn định/ít lỗi | 54 tests + 4 browser scripts | Phạm vi đã chạy đạt; chưa nghiệm thu Cloudinary/LAN |
| A14 | Input/lỗi rõ | TC07/09/15/27 | Pass nghiệp vụ, ảnh mock |
| A15 | Rỗng/sai/trùng/không thấy | TC03/05/07/15/23/24/26 | Pass |
| A16 | CSRF/auth/quyền | TC10/16/25/28 | Pass, có HTTP thật |
| A17 | Minh chứng | Report/evidence TC01–30 | Có, trạng thái thiếu ghi rõ |
| A18 | Chạy Laravel | TC18, routes/platform | Cài sạch theo SQL + migration |
| A19 | DB/migration/demo seed | TC01/19/30 | Seed đạt; baseline migration thiếu |
| A20 | Hướng dẫn cài/chạy | README, TC18 | Có cài mới/thành viên/testing/LAN |
| A21 | Source đầy đủ | scripts/package-source.ps1, 7 Word, manifest/hash zip | Gói source; xem kiểm tra gói |

## Lỗi của lượt kiểm chứng và chạy lại

1. Assertion seeder so sánh model mới tạo với model đọc DB bị lệch thứ tự cột/default rememberToken. Đổi snapshot đầu sang model đọc lại; 4 ca seeder và full 54 ca đạt. Không sửa nghiệp vụ để làm test đạt.
2. Script cài sạch v1 kế thừa env Laravel cha, seed nhầm DB webcookingrecipe: tạo đúng 2 user demo, 30 món và tương tác vào 12:32:31–32 (UTC+7). Đã xóa riêng fixture xác định bằng email/title/timestamp trong transaction, **không reset/truncate**; [kiểm tra lại](evidence/install-harness-cleanup.txt): users/recipes/ratings/comments đều 0, catalog 5/10/30. Catalog seed đã cập nhật updatedAt và bộ đếm AUTO_INCREMENT đã tăng; không giả định khôi phục các metadata này. Schema và giá trị catalog không thay đổi. Script đã cô lập env, ép DB tạm và kiểm tra DB trống trước ghi; installer trả exit code khác 0 khi từ chối.
3. Lượt v2 DB/seed đạt nhưng npm.cmd gọi qua tên tương đối không tìm được npm-cli trong subprocess Windows. Sửa dùng đường dẫn npm.cmd tuyệt đối lấy từ PATH, chạy lại cài sạch. Log TC18 là lượt cuối; ghi các lỗi cũ ở đây thay vì giữ log thừa.

## Tiếp tục nghiệm thu

Hoàn thiện baseline migration phần 01 theo quy trình giữ dữ liệu; cấp cấu hình Cloudinary thật rồi chạy TC06/09/20 với hai tài khoản và ảnh thử, kiểm tra ảnh đã dọn; chạy LAN01–04 trên A/B/C. Ghi ngày giờ, source hash, input, mong đợi/thực tế, trạng thái và ảnh/log đã che secret cho mỗi lượt. Không gửi mật khẩu/key trong evidence.
