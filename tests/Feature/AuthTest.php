<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use PDOException;
use Tests\TestCase;

class AuthTest extends TestCase
{
    private string $password;

    private bool $transactionStarted = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('mysql', config('database.default'));
        $this->assertSame('recipe_test', DB::selectOne('SELECT DATABASE() AS db')->db,
            'Refusing to run auth tests against a non-test database.');
        DB::beginTransaction();
        $this->transactionStarted = true;
        $this->password = '  '.bin2hex(random_bytes(12)).'  ';
        Route::middleware(['web', 'auth'])->get('/test-private', fn () => 'Private page');
        Route::middleware(['web', 'auth'])->post('/test-write', fn () => 'Saved');
    }

    protected function tearDown(): void
    {
        if ($this->transactionStarted) {
            User::flushEventListeners();
            DB::rollBack();
        }
        parent::tearDown();
    }

    private function registration(array $overrides = []): array
    {
        return array_replace([
            'name' => '  Người nấu ăn  ',
            'email' => '  UserA@Example.test  ',
            'password' => $this->password,
            'password_confirmation' => $this->password,
        ], $overrides);
    }

    private function user(string $password = ''): User
    {
        return User::create([
            'name' => 'Người nấu ăn', 'email' => 'usera@example.test',
            'password' => Hash::make($password ?: $this->password),
        ]);
    }

    public function test_auth01_registration_normalizes_hashes_and_rotates_session(): void
    {
        $this->withSession(['marker' => 'before']);
        $oldSession = session()->getId();
        $count = User::count();
        $this->post('/register', $this->registration(['id' => 999999, 'role' => 'admin']))
            ->assertRedirect('/')->assertSessionHas('success');
        $user = User::where('email', 'usera@example.test')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame($count + 1, User::count());
        $this->assertSame('Người nấu ăn', $user->name);
        $this->assertTrue(Hash::check($this->password, $user->password));
        $this->assertFalse(Hash::check(trim($this->password), $user->password));
        $this->assertNotSame($oldSession, session()->getId());
        $this->assertNotNull($user->createdAt);
        $this->assertNotNull($user->updatedAt);
        $this->assertNotEquals(999999, $user->id);
        $this->assertSame('rememberToken', $user->getRememberTokenName());
        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('rememberToken', $user->toArray());
    }

    public function test_auth02_duplicate_and_confirmation_errors_keep_only_safe_input(): void
    {
        $this->user();
        $count = User::count();
        $this->from('/register')->post('/register', $this->registration(['password_confirmation' => 'different']))
            ->assertRedirect('/register')
            ->assertSessionHasErrors(['email' => 'Email này đã được sử dụng.', 'password'])
            ->assertSessionHasInput('email', 'usera@example.test')
            ->assertSessionHasInput('name', 'Người nấu ăn')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation');
        $this->assertSame($count, User::count());
        $this->assertGuest();
        $this->get('/register')->assertSee('value="usera@example.test"', false)
            ->assertDontSee($this->password);
    }

    public function test_long_password_is_hashed_in_full(): void
    {
        $password = str_repeat('a', 80).bin2hex(random_bytes(8));
        $this->post('/register', $this->registration([
            'password' => $password, 'password_confirmation' => $password,
        ]))->assertRedirect('/');
        $hash = User::where('email', 'usera@example.test')->sole()->password;
        $this->assertTrue(Hash::check($password, $hash));
        $this->assertFalse(Hash::check(substr($password, 0, 80).'different-suffix', $hash));
    }

    public function test_registration_rejects_empty_short_long_and_malformed_inputs(): void
    {
        $count = User::count();
        foreach ([
            ['name' => '', 'email' => '', 'password' => ''],
            ['name' => 'x', 'email' => 'not-email', 'password' => 'short'],
            ['name' => str_repeat('a', 101), 'email' => str_repeat('a', 250).'@test.test', 'password' => []],
            ['name' => [], 'email' => [], 'password' => []],
        ] as $input) {
            $this->from('/register')->post('/register', $this->registration($input))
                ->assertSessionHasErrors(['name', 'email', 'password']);
        }
        $this->assertSame($count, User::count());
    }

    public function test_auth03_login_rotates_session_and_uses_framework_intended_url(): void
    {
        $user = $this->user();
        $this->get('/test-private')->assertRedirect('/login');
        $oldSession = session()->getId();
        $this->post('/login', ['email' => ' UserA@Example.test ', 'password' => $this->password])
            ->assertRedirect('/test-private');
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($oldSession, session()->getId());
        $this->get('/test-private')->assertOk();
    }

    public function test_auth03_wrong_and_unknown_accounts_have_identical_errors(): void
    {
        $this->user();
        foreach (['usera@example.test', 'unknown@example.test'] as $email) {
            $this->from('/login')->post('/login', ['email' => $email, 'password' => 'wrong'])
                ->assertRedirect('/login')
                ->assertSessionHasErrors(['email' => 'Thông tin đăng nhập không đúng'])
                ->assertSessionHasInput('email', $email)
                ->assertSessionMissing('_old_input.password');
            $this->assertGuest();
        }
    }

    public function test_login_does_not_reapply_registration_password_length_rules(): void
    {
        $user = $this->user('tiny');
        $this->post('/login', ['email' => $user->email, 'password' => 'tiny'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->get('/')->assertOk()->assertSee('Công thức mới chia sẻ')->assertDontSee('name="keyword"', false);
    }

    public function test_auth04_logout_invalidates_session_token_and_write_access(): void
    {
        $this->actingAs($this->user())->withSession(['private-marker' => 'sensitive']);
        $oldSession = session()->getId();
        $oldToken = session()->token();
        $this->get('/logout')->assertStatus(405);
        $this->assertAuthenticated();
        $this->post('/logout')->assertRedirect('/')->assertSessionMissing('private-marker');
        $this->assertGuest();
        $this->assertNotSame($oldSession, session()->getId());
        $this->assertNotSame($oldToken, session()->token());
        Auth::forgetGuards();
        $this->post('/test-write')->assertRedirect('/login');
    }

    public function test_auth05_guest_and_authenticated_redirects_do_not_loop(): void
    {
        $this->post('/logout')->assertRedirect('/login');
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->actingAs($this->user());
        $this->get('/login')->assertRedirect('/');
        $this->get('/register')->assertRedirect('/');
        $this->post('/login', [])->assertRedirect('/');
        $this->post('/register', [])->assertRedirect('/');
        $this->get('/')->assertOk()->assertSee('Đăng xuất')->assertDontSee('href="http://localhost/register"', false);
    }

    public function test_authentication_defaults_to_home_even_when_search_exists(): void
    {
        Route::get('/recipes', fn () => 'Recipes')->name('recipes.index');
        Route::getRoutes()->refreshNameLookups();
        $this->post('/register', $this->registration())->assertRedirect('/');
        $this->get('/login')->assertRedirect('/');
        $this->post('/logout')->assertRedirect('/');
        $this->post('/login', ['email' => 'usera@example.test', 'password' => $this->password])
            ->assertRedirect('/');
    }

    public function test_auth07_forms_have_labels_csrf_and_safe_values(): void
    {
        foreach (['/register', '/login'] as $path) {
            $this->get($path)->assertOk()
                ->assertSee('name="_token"', false)
                ->assertSee('for="email"', false)
                ->assertSee('for="password"', false)
                ->assertSee('name="viewport"', false)
                ->assertDontSee('name="remember"', false);
        }
    }

    public function test_racing_email_insert_becomes_email_validation_error(): void
    {
        User::creating(function (User $user) {
            DB::table('users')->insert([
                'name' => 'Racing insert', 'email' => $user->email,
                'password' => Hash::make(bin2hex(random_bytes(16))),
            ]);
        });
        $this->from('/register')->post('/register', $this->registration())
            ->assertRedirect('/register')
            ->assertSessionHasErrors(['email' => 'Email này đã được sử dụng.']);
        $this->assertGuest();
        $this->assertSame(1, User::where('email', 'usera@example.test')->count());
    }

    public function test_other_database_errors_are_generic_and_logs_exclude_bindings(): void
    {
        Log::shouldReceive('error')->once()->with('Auth database operation failed.', [
            'sqlState' => '23000', 'driverCode' => 1048,
        ]);
        User::creating(function () {
            $previous = new PDOException('Simulated database error');
            $previous->errorInfo = ['23000', 1048, 'Column cannot be null'];
            throw new QueryException('mysql', 'insert into users ...', [$this->password], $previous);
        });
        $this->from('/register')->post('/register', $this->registration())
            ->assertRedirect('/register')->assertSessionHas('error')
            ->assertSessionHasNoErrors()->assertSessionMissing('_old_input.password');
        $this->assertGuest();
    }
}
