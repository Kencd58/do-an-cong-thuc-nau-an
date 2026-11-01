// Run with playwright-cli run-code --filename=tests/browser-auth.js.
// Port 8001 must run with DB_DATABASE=recipe_test and SESSION_COOKIE=bep_nha_auth_check.
async page => {
    const base = 'http://127.0.0.1:8001';
    const check = (condition, message) => { if (!condition) throw new Error(message); };
    const results = [];
    await page.context().clearCookies();
    await page.goto(`${base}/register`);
    check((await page.context().cookies()).some(c => c.name === 'bep_nha_auth_check'), 'Refusing to test a non-test server');
    for (const width of [1440, 375]) {
        await page.setViewportSize({width, height: 1000});
        for (const path of ['register', 'login']) {
            await page.goto(`${base}/${path}`);
            check(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `${path}: horizontal overflow at ${width}px`);
            await page.screenshot({path: `docs/evidence/${path}-${width}.png`, fullPage: true});
        }
    }
    results.push('AUTH07: register/login fit 375px and 1440px; screenshots saved');
    const suffix = await page.evaluate(() => crypto.randomUUID());
    const email = `browser-${suffix}@example.test`;
    const password = `  ${await page.evaluate(() => crypto.randomUUID())}  `;
    for (const path of ['register', 'login', 'logout']) {
        const response = await page.request.post(`${base}/${path}`, {
            form: {name: 'AUTH_BROWSER_CHECK', email, password, password_confirmation: password},
            maxRedirects: 0,
        });
        check(response.status() === 419, `Missing CSRF on ${path}: ${response.status()}`);
        check((await response.text()).includes('Phiên làm việc đã hết hạn, vui lòng tải lại trang.'), 'Missing Vietnamese 419 message');
    }
    results.push('AUTH06: POST register/login/logout without CSRF all return 419');
    await page.goto(`${base}/register`);
    await page.locator('#name').fill('  AUTH_BROWSER_CHECK  ');
    await page.locator('#email').fill(`  ${email.toUpperCase()}  `);
    await page.locator('#password').fill(password);
    await page.locator('#password_confirmation').fill(password);
    await page.locator('.button-full').click();
    await page.waitForURL(`${base}/`);
    check(await page.locator('.account-name').textContent() === 'AUTH_BROWSER_CHECK', 'Registration did not authenticate');
    await page.goto(`${base}/login`);
    check(page.url() === `${base}/`, 'Member not redirected away from login');
    const staleCookies = (await page.context().cookies()).map(c => `${c.name}=${c.value}`).join('; ');
    const staleToken = await page.locator('input[name="_token"]').inputValue();
    await page.locator('button.nav-button').click();
    await page.waitForURL(`${base}/`);
    check(await page.locator('.account-name').count() === 0, 'Logout kept authenticated menu');
    const replay = await page.request.post(`${base}/logout`, {headers: {Cookie: staleCookies}, form: {_token: staleToken}, maxRedirects: 0});
    check(replay.status() === 419, 'Old session/token still accepted after logout');
    results.push('AUTH01/04/05: registered, auto-login, guest redirect, logout, old-session replay rejected');
    await page.goto(`${base}/register`);
    await page.locator('#name').fill('AUTH_BROWSER_CHECK');
    await page.locator('#email').fill(email.toUpperCase());
    await page.locator('#password').fill(password);
    await page.locator('#password_confirmation').fill(password);
    await page.locator('.button-full').click();
    await page.locator('#email-error').waitFor();
    check((await page.locator('#email-error').textContent()).includes('Email này đã được sử dụng.'), 'Missing duplicate email error');
    check(await page.locator('#email').inputValue() === email, 'Normalized email not retained');
    check(await page.locator('#password').inputValue() === '', 'Password was retained');
    check(await page.locator('#password_confirmation').inputValue() === '', 'Confirmation was retained');
    await page.screenshot({path: 'docs/evidence/register-errors-375.png', fullPage: true});
    results.push('AUTH02/07: duplicate email rejected, normalized old input retained, passwords blank');
    await page.goto(`${base}/login`);
    await page.locator('#email').fill(email);
    await page.locator('#password').fill('incorrect');
    await page.locator('.button-full').click();
    await page.locator('#email-error').waitFor();
    check((await page.locator('#email-error').textContent()).includes('Thông tin đăng nhập không đúng'), 'Wrong login error');
    check(await page.locator('#password').inputValue() === '', 'Failed login retained password');
    await page.locator('#password').fill(password);
    await page.locator('.button-full').click();
    await page.waitForURL(`${base}/`);
    check(await page.locator('.account-name').count() === 1, 'Correct login failed');
    check(await page.getByRole('heading', {name: 'Công thức mới chia sẻ'}).count() === 1, 'Login should open the community feed');
    check(await page.locator('input[type="search"]').count() === 0, 'Login should not open the search form');
    await page.locator('button.nav-button').click();
    await page.waitForURL(`${base}/`);
    results.push('AUTH03: wrong login rejected, correct spaced password accepted');
    await page.goto(`${base}/login`);
    const invalid = await page.request.post(`${base}/login`, {form: {_token: 'expired-token', email, password}, maxRedirects: 0});
    check(invalid.status() === 419, 'Expired CSRF accepted');
    return {results, testEmail: email};
}
