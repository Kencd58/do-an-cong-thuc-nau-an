<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Bếp Nhà') · Công thức mỗi ngày</title>
    <meta name="theme-color" content="#6b3f28">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&amp;family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500;1,600&amp;display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php($showSidebar = ! request()->routeIs('login', 'register'))
<body class="{{ $showSidebar ? 'has-app-shell' : 'has-auth-shell' }}">
    <a class="skip-link" href="#main">Đến nội dung chính</a>

    <header class="site-header">
        <div class="header-inner">
            @if ($showSidebar)
                <button class="menu-toggle" type="button" data-sidebar-toggle aria-controls="app-sidebar" aria-expanded="false">
                    <span class="sr-only">Mở menu</span>
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            @endif
            <a class="brand" href="{{ url('/') }}" aria-label="Bếp Nhà — Trang chủ">
                <span class="brand-mark" aria-hidden="true">B.</span>
                <span>Bếp Nhà</span>
            </a>

            <div class="header-actions">
                @if ($showSidebar && Route::has('recipes.index'))
                    <a class="header-search" href="{{ route('recipes.index') }}">
                        <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
                        <span>Tìm công thức</span>
                    </a>
                @endif
                @auth
                    @if (Route::has('recipes.create'))
                        <a class="button button-compact" href="{{ route('recipes.create') }}">Tạo công thức</a>
                    @endif
                    <span class="account-chip" title="{{ auth()->user()->name }}">
                        <span class="account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="account-name">{{ auth()->user()->name }}</span>
                    </span>
                @else
                    <a href="{{ route('login') }}" @if(request()->routeIs('login')) aria-current="page" @endif>Đăng nhập</a>
                    <a class="button button-compact" href="{{ route('register') }}" @if(request()->routeIs('register')) aria-current="page" @endif>Đăng ký</a>
                @endauth
            </div>
        </div>
    </header>

    <div class="app-shell {{ $showSidebar ? '' : 'app-shell-centered' }}">
        @if ($showSidebar)
            <div class="sidebar-backdrop" data-sidebar-close hidden></div>
            <aside class="app-sidebar" id="app-sidebar" aria-label="Điều hướng chính">
                <nav class="sidebar-nav">
                    <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>
                        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m3.5 10.5 8.5-7 8.5 7v9a1 1 0 0 1-1 1h-5v-6h-5v6h-5a1 1 0 0 1-1-1z"/></svg>
                        <span>Trang chủ</span>
                    </a>
                    @if (Route::has('recipes.index'))
                        <a href="{{ route('recipes.index') }}" @if(request()->routeIs('recipes.index')) aria-current="page" @endif>
                            <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
                            <span>Tìm kiếm</span>
                        </a>
                    @endif
                    @auth
                        @if (Route::has('my.recipes.index'))
                            <a href="{{ route('my.recipes.index') }}" @if(request()->routeIs('my.recipes.index')) aria-current="page" @endif>
                                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 4.5h14v15H5zM8.5 8h7M8.5 12h7M8.5 16h4"/></svg>
                                <span>Công thức của tôi</span>
                            </a>
                        @endif
                        @if (Route::has('recipes.create'))
                            <a href="{{ route('recipes.create') }}" @if(request()->routeIs('recipes.create')) aria-current="page" @endif>
                                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                                <span>Tạo công thức</span>
                            </a>
                        @endif
                    @endauth
                </nav>

                <div class="sidebar-note">
                    <p>Mỗi món ngon đều bắt đầu từ một căn bếp nhỏ.</p>
                </div>

                @auth
                    <form class="sidebar-logout" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="nav-button" type="submit">
                            <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"/></svg>
                            Đăng xuất
                        </button>
                    </form>
                @endauth
            </aside>
        @endif

        <div class="content-column">
            <main id="main" class="main-container">
                @foreach (['success', 'warning', 'error'] as $type)
                    @if (session($type))
                        <div class="notice notice-{{ $type }}" role="{{ $type === 'error' ? 'alert' : 'status' }}">{{ session($type) }}</div>
                    @endif
                @endforeach
                @if ($errors->any())
                    <div class="notice notice-error" role="alert">Thông tin chưa hợp lệ. Vui lòng kiểm tra các trường bên dưới.</div>
                @endif
                @yield('content')
            </main>
            <footer class="site-footer"><span>Bếp Nhà</span><span>Một chút cảm hứng, một bữa cơm ngon.</span></footer>
        </div>
    </div>
</body>
</html>
