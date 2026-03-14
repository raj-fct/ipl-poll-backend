<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - IPL Poll</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            background: #fff;
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-card .brand {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1a1a2e;
        }
        .login-card .brand span { color: #e94560; }
        .form-control:focus {
            border-color: #e94560;
            box-shadow: 0 0 0 0.2rem rgba(233, 69, 96, 0.15);
        }
        .btn-login {
            background: #e94560;
            border: none;
            padding: 0.65rem;
            font-weight: 600;
            font-size: 1rem;
        }
        .btn-login:hover { background: #c73852; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="brand"><i class="bi bi-trophy-fill"></i> IPL <span>Poll</span></div>
            <p class="text-muted mt-1">Admin Panel</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger py-2">
                @foreach($errors->all() as $error)
                    <small>{{ $error }}</small><br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-semibold">Mobile Number</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-phone"></i></span>
                    <input type="text" name="mobile" class="form-control" placeholder="Enter mobile number"
                           value="{{ old('mobile') }}" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-login btn-primary w-100">
                <i class="bi bi-box-arrow-in-right"></i> Sign In
            </button>
        </form>
    </div>
</body>
</html>
