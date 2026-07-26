<!DOCTYPE html>
<html>
<head>
    <title>Login - Adxsway POS</title>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; font-family: -apple-system, sans-serif; height: 100%; }
        body { display: flex; flex-direction: column; min-height: 100vh; background: #f6f6f7; }

        .topbar {
            height: 60px; background: #111; color: white;
            display: flex; align-items: center; padding: 0 25px;
            font-weight: 700; font-size: 16px;
        }

        .login-wrap { flex: 1; display: flex; align-items: center; justify-content: center; padding: 30px; }

        .box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 100%; max-width: 380px; }
        .box h1 { font-size: 20px; margin-bottom: 25px; text-align: center; }
        label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 5px; margin-top: 15px; }
        input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        button { margin-top: 20px; width: 100%; padding: 11px; background: #008060; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 15px; font-weight: 600; }
        button:hover { background: #006e52; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; }

        .app-footer {
            height: 44px; background: #111; color: #ccc;
            display: flex; align-items: center; justify-content: center; font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="topbar">Adxsway POS</div>

    <div class="login-wrap">
        <div class="box">
            <h1>Sign in to your account</h1>

            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}">
                @csrf
                <label>Username</label>
                <input type="text" name="username" placeholder="Enter username" value="{{ old('username') }}" autofocus>

                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password">

                <button type="submit">Sign In</button>
            </form>
        </div>
    </div>

    <div class="app-footer">Built by Adxsway</div>
</body>
</html>
