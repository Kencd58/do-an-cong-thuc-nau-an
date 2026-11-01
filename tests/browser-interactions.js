// Run only against the isolated recipe_test server in README.md.
async page => {
    const base = 'http://127.0.0.1:8001';
    const check = (ok, message) => { if (!ok) throw new Error(message); };
    const errors = [];
    const register = async (target, name) => {
        await target.goto(`${base}/register`);
        check((await target.context().cookies()).some(cookie => cookie.name === 'bep_nha_auth_check'), 'Refusing non-test server');
        const id = await target.evaluate(() => crypto.randomUUID());
        await target.locator('#name').fill(name);
        await target.locator('#email').fill(`interaction-${id}@example.test`);
        await target.locator('#password').fill(id);
        await target.locator('#password_confirmation').fill(id);
        await target.locator('.button-full').click();
        await target.waitForURL(`${base}/`);
        return target.locator('input[name="_token"]').first().inputValue();
    };
    await page.context().clearCookies();
    const ownerToken = await register(page, 'Chủ bếp kiểm thử');
    const writerContext = await page.context().browser().newContext();
    const writer = await writerContext.newPage();
    writer.on('pageerror', error => errors.push(error.message));
    let recipeUrl;
    try {
        const created = await page.request.post(`${base}/recipes`, {maxRedirects: 0, form: {
            _token: ownerToken, title: 'Trứng chiên hành — kiểm thử tương tác', description: 'Công thức cho một bữa sáng ấm áp.',
            categoryId: 1, prepTime: 5, cookTime: 10, servings: 2, difficulty: 'easy',
            'ingredients[0][ingredientId]': 7, 'ingredients[0][quantity]': 2, 'ingredients[0][unit]': 'quả',
            'steps[0][instruction]': 'Đánh trứng và chiên đến khi chín.'
        }});
        recipeUrl = created.headers().location;
        check(created.status() === 302 && /\/recipes\/\d+$/.test(recipeUrl || ''), 'Recipe fixture failed');
        const token = await register(writer, 'Người nấu thử');
        await writer.goto(recipeUrl);
        await writer.locator('#rating-score').selectOption('4');
        await writer.getByRole('button', {name: 'Gửi đánh giá', exact: true}).click();
        await writer.waitForURL(`${recipeUrl}#ratings`);
        check(await writer.locator('#ratings').innerText().then(text => text.includes('4.0 / 5')), 'Rating create failed');
        await writer.locator('#rating-score').selectOption('5');
        await Promise.all([writer.waitForNavigation(), writer.getByRole('button', {name: 'Cập nhật đánh giá', exact: true}).click()]);
        check((await writer.locator('#ratings').innerText()).includes('5.0 / 5'), 'Rating update failed');
        await Promise.all([writer.waitForNavigation(), writer.getByRole('button', {name: 'Gỡ đánh giá', exact: true}).click()]);
        check((await writer.locator('#ratings').innerText()).includes('Chưa có đánh giá'), 'Rating delete failed');
        await writer.request.post(`${recipeUrl}/rating`, {maxRedirects: 0, form: {_token: token, _method: 'PUT', score: 4}});
        await writer.goto(recipeUrl);
        await writer.locator('#comment-content').fill('<script>window.interactionXss=1</script> Món này ngon!');
        await writer.getByRole('button', {name: 'Gửi bình luận', exact: true}).click();
        await writer.waitForURL(`${recipeUrl}#comments`);
        for (let i = 2; i <= 12; i++) {
            const response = await writer.request.post(`${recipeUrl}/comments`, {maxRedirects: 0, form: {_token: token, content: `Đã nấu thử lần ${i}. Cảm ơn công thức!`}});
            check(response.status() === 302, 'Comment fixture failed');
        }
        await writer.goto(recipeUrl);
        check(await writer.locator('.comment').count() === 10, 'Expected 10 comments');
        for (const width of [375, 1440]) {
            await writer.setViewportSize({width, height: 1000});
            check(await writer.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `Overflow at ${width}`);
            await writer.screenshot({path: `docs/evidence/interaction-show-${width}.png`, fullPage: true});
        }
        await writer.getByRole('link', {name: 'Trang sau', exact: true}).click();
        check(await writer.locator('.comment').count() === 2, 'Page 2 must have 2 comments');
        check(await writer.evaluate(() => window.interactionXss === undefined), 'XSS executed');
        const row = writer.locator('.comment').first();
        await row.getByRole('button', {name: 'Sửa bình luận', exact: true}).click();
        check(await row.locator('textarea').isVisible(), 'Edit toggle failed');
        const action = await row.locator('form[id^="comment-edit-"]').getAttribute('action');
        const bad = 'x'.repeat(2001);
        const invalid = await writer.request.post(action, {maxRedirects: 0, form: {_token: token, _method: 'PUT', content: bad, commentsPage: 2}});
        check(invalid.headers().location === `${recipeUrl}?commentsPage=2#comments`, 'Validation lost page');
        // The redirect points to the current page/fragment; force a real GET to consume flash data.
        await writer.reload();
        check(await writer.locator('.comment textarea:visible').count() === 1, 'Wrong edit form expanded');
        const restored = await writer.locator('.comment textarea:visible').inputValue();
        check(restored === bad, `Lost old content: expected ${bad.length}, got ${restored.length}, prefix ${restored.slice(0, 30)}`);
        check(await writer.locator('#comment-content').inputValue() === '', 'Old content leaked to create form');
        await writer.setViewportSize({width: 375, height: 1000});
        check(await writer.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Error form overflow');
        await writer.screenshot({path: 'docs/evidence/interaction-error-375.png', fullPage: true});
        await writer.locator('.comment textarea:visible').fill('Đã sửa nội dung bình luận.');
        await writer.getByRole('button', {name: 'Lưu bình luận', exact: true}).filter({visible: true}).click();
        await writer.waitForURL(`${recipeUrl}#comments`);
        await writer.goto(`${recipeUrl}?commentsPage=2#comments`);
        check((await writer.locator('#comments').innerText()).includes('Đã sửa nội dung bình luận.'), 'Edit not saved');
        let deletes = 0;
        writer.on('request', request => { if (request.method() === 'POST' && request.url().includes('/comments/')) deletes++; });
        writer.once('dialog', dialog => dialog.dismiss());
        await writer.locator('.comment').first().getByRole('button', {name: 'Xóa bình luận', exact: true}).click();
        check(deletes === 0 && await writer.locator('.comment').count() === 2, 'Cancel sent delete');
        writer.once('dialog', dialog => dialog.accept());
        await Promise.all([writer.waitForNavigation(), writer.locator('.comment').first().getByRole('button', {name: 'Xóa bình luận', exact: true}).click()]);
        check(deletes === 1, 'Delete request count wrong');
        const remainingAction = await writer.locator('.comment form[id^="comment-edit-"]').first().getAttribute('action');
        for (const [url, method] of [[`${recipeUrl}/rating`, 'PUT'], [`${recipeUrl}/rating`, 'DELETE'], [`${recipeUrl}/comments`, 'POST'], [remainingAction, 'PUT'], [remainingAction, 'DELETE']]) {
            for (const csrf of [null, 'invalid-token']) {
                const form = {_method: method, score: 3, content: 'CSRF must fail'};
                if (csrf !== null) form._token = csrf;
                const response = await writer.request.post(url, {maxRedirects: 0, form});
                check(response.status() === 419, `CSRF ${method} failed: ${response.status()}`);
            }
        }
        await page.goto(recipeUrl);
        check(await page.locator('#rating-score').count() === 0, 'Owner can see rating form');
        check(await page.getByRole('button', {name: 'Sửa bình luận', exact: true}).count() === 0, 'Owner sees foreign edit');
        await writerContext.clearCookies();
        await writer.goto(recipeUrl);
        check(await writer.locator('.comment').count() === 10, 'Guest cannot read comments');
        check(await writer.locator('#rating-score, #comment-content').count() === 0, 'Guest sees write forms');
        check(errors.length === 0, 'JavaScript errors: ' + errors.join(', '));
        return {passed: true, widths: [375, 1440], checks: ['rating create/update/remove', 'comment create/edit/delete', '12 comments, pagination 10/2', 'isolated validation errors on page 2', 'escaped script', 'cancel and confirm delete', 'five endpoints reject missing and invalid CSRF with 419', 'owner and guest controls', 'no overflow or JavaScript errors']};
    } finally {
        if (recipeUrl) {
            const response = await page.request.post(recipeUrl, {maxRedirects: 0, form: {_token: ownerToken, _method: 'DELETE'}});
            check(response.status() === 302, 'Recipe cleanup failed');
        }
        await writerContext.close();
    }
}
