// Run only on the isolated recipe_test server described in README.md.
async page => {
    const base = 'http://127.0.0.1:8001';
    const check = (condition, message) => { if (!condition) throw new Error(message); };
    const created = [];
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.context().clearCookies();
    await page.goto(`${base}/register`);
    check((await page.context().cookies()).some(cookie => cookie.name === 'bep_nha_auth_check'), 'Refusing non-test server');
    const marker = await page.evaluate(() => crypto.randomUUID());
    const password = await page.evaluate(() => crypto.randomUUID());
    await page.locator('#name').fill('Bếp kiểm thử danh sách');
    await page.locator('#email').fill(`list-${marker}@example.test`);
    await page.locator('#password').fill(password);
    await page.locator('#password_confirmation').fill(password);
    await page.locator('.button-full').click();
    await page.waitForURL(`${base}/`);
    await page.goto(`${base}/my/recipes`);
    check(await page.getByRole('link', {name: 'Đăng công thức đầu tiên'}).count() === 1, 'Missing first-recipe link');
    await page.goto(`${base}/recipes/create`);
    const token = await page.locator('input[name="_token"]').first().inputValue();
    try {
        for (let i = 0; i < 13; i++) {
            const response = await page.request.post(`${base}/recipes`, {maxRedirects: 0, form: {
                _token: token, title: `Trứng ${String(i).padStart(2, '0')} ${marker}`, description: 'Công thức kiểm thử bộ lọc.',
                categoryId: 1, prepTime: 5, cookTime: 10, servings: 2, difficulty: 'easy',
                'tagIds[0]': 1, 'tagIds[1]': 2,
                'ingredients[0][ingredientId]': 7, 'ingredients[0][quantity]': 2, 'ingredients[0][unit]': 'quả',
                'ingredients[1][ingredientId]': 11, 'ingredients[1][quantity]': 1, 'ingredients[1][unit]': 'nhánh',
                'steps[0][instruction]': 'Chiên trứng đến khi chín.'
            }});
            const location = response.headers().location;
            check(response.status() === 302 && /\/recipes\/\d+$/.test(location ?? ''), 'Fixture creation failed');
            created.push(location);
        }
        await page.getByRole('link', {name: 'Trang chủ', exact: true}).click();
        check(await page.locator('.recipe-card').count() === 12, 'Home should immediately show recipes');
        check(await page.locator('input[type="search"]').count() === 0, 'Home should not display the search form');
        check(await page.locator('.recipe-card > a').first().getAttribute('href') === created[12], 'Home should show newest first');
        for (const width of [375, 1440]) {
            await page.setViewportSize({width, height: 900});
            check(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `Home overflows at ${width}px`);
            check(await page.locator('.recipe-card').first().evaluate(card => card.getBoundingClientRect().top < innerHeight), 'Recipes should be visible without scrolling');
            await page.screenshot({path: `docs/evidence/home-${width}.png`, fullPage: false});
        }
        await page.getByRole('link', {name: 'Trang sau', exact: true}).click();
        check(new URL(page.url()).searchParams.get('page') === '2', 'Home pagination failed');
        await page.getByRole('link', {name: 'Tìm kiếm', exact: true}).click();
        check(await page.locator('input[type="search"]').count() === 1, 'Search should remain available');
        const query = new URLSearchParams({keyword: marker, categoryId: '1', difficulty: 'easy', maxTotalTime: '15', sort: 'time'});
        for (const id of ['1', '2']) query.append('tagIds[]', id);
        for (const id of ['7', '11']) query.append('ingredientIds[]', id);
        for (const path of ['/recipes', '/my/recipes']) {
            await page.goto(`${base}${path}?${query}`);
            check(await page.locator('.recipe-card').count() === 12, 'Expected 12 cards');
            check(await page.locator('.recipe-card img').count() === 12, 'Every recipe post should have an image');
            check(await page.locator('.recipe-card .tags').count() === 12, 'Missing tags');
            for (const width of [375, 1440]) {
                await page.setViewportSize({width, height: 1000});
                check(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `Overflow ${path} at ${width}px`);
                await page.screenshot({path: `docs/evidence/list-${path.includes('my') ? 'my' : 'public'}-${width}.png`, fullPage: true});
            }
            await page.getByRole('link', {name: 'Trang sau', exact: true}).click();
            check(await page.locator('.recipe-card').count() === 1, 'Second page should have one card');
            const current = new URL(page.url()).searchParams;
            check(current.get('page') === '2', 'Wrong page');
            for (const [key, value] of query) {
                if (!key.endsWith('[]')) check(current.get(key) === value, `Lost query: ${key}`);
            }
            check(await page.locator('input[name="tagIds[]"]:checked').count() === 2, 'Lost tags');
            check(await page.locator('input[name="ingredientIds[]"]:checked').count() === 2, 'Lost ingredients');
            await page.locator('#sort').selectOption('title');
            await page.getByRole('button', {name: 'Áp dụng'}).click();
            await page.waitForLoadState();
            check(!new URL(page.url()).searchParams.has('page'), 'Submit retained page');
            check(await page.locator('.recipe-card').count() === 12, 'Submit did not return to first page');
            await page.goto(`${base}${path}?${query}&page=999`);
            check(await page.getByRole('heading', {name: 'Trang này không có công thức'}).count() === 1, 'Missing out-of-range state');
            await page.getByRole('link', {name: 'Về trang 1'}).click();
            check(await page.locator('.recipe-card').count() === 12, 'Return to page 1 lost filters');
            await page.goto(`${base}${path}?keyword=missing-${marker}`);
            check(await page.getByRole('heading', {name: 'Không tìm thấy công thức phù hợp'}).count() === 1, 'Missing empty result state');
            check(await page.getByRole('link', {name: 'Đăng công thức đầu tiên'}).count() === 0, 'Incorrect first-recipe state');
            await page.getByRole('link', {name: 'Xóa bộ lọc', exact: true}).first().click();
            check(page.url() === `${base}${path}`, 'Clear filters targets wrong list');
            await page.goto(`${base}${path}?sort=invalid&page=-1`);
            check(page.url() === `${base}${path}`, 'Invalid query was not redirected cleanly');
            check(await page.getByRole('alert').count() === 1, 'Missing or duplicated filter error');
            await page.setViewportSize({width: 375, height: 1000});
            check(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Error view overflows');
            await page.screenshot({path: `docs/evidence/list-errors-${path.includes('my') ? 'my' : 'public'}-375.png`, fullPage: true});
            await page.reload();
            check(await page.getByRole('alert').count() === 0, 'Filter errors persist unexpectedly');
        }
        check(errors.length === 0, 'JavaScript errors: ' + errors.join(', '));
        return {passed: true, fixtures: 13, widths: [375, 1440], checks: ['home feed visible without scrolling', 'newest first and home pagination', 'separate search navigation', 'public and personal filters', '12/1 pagination',
            'query and selection retention', 'submit resets page', 'clear on current route', 'empty and out-of-range states',
            'clean invalid-query redirect', 'tags and post images', 'no overflow or JavaScript errors']};
    } finally {
        for (const url of created) {
            const response = await page.request.post(url, {maxRedirects: 0, form: {_token: token, _method: 'DELETE'}});
            check(response.status() === 302, 'Fixture cleanup failed');
        }
    }
}
