<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · Finora</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/finance.css') }}" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-wrap">
        <a class="brand login-brand" href="{{ route('login') }}"><span class="brand-mark"><i class="bi bi-layers-half"></i></span><span>finora<span class="brand-dot">.</span><small>FINANCE STUDIO</small></span></a>
        <div class="login-card">
            <div class="eyebrow">WELCOME BACK</div>
            <h1>Good to see you.</h1>
            <p class="muted-copy">Sign in to your finance workspace.</p>
            @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <label class="form-label" for="email">Email address</label>
                <input class="form-control form-control-lg" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@company.com" required autofocus autocomplete="username">
                <label class="form-label mt-3" for="password">Password</label>
                <input class="form-control form-control-lg" id="password" name="password" type="password" placeholder="Enter your password" required autocomplete="current-password">
                <label class="remember-row"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
                <button class="btn btn-primary w-100 btn-lg login-submit" type="submit">Sign in <span>→</span></button>
            </form>
            <div class="login-foot"><i class="bi bi-shield-lock"></i> Secure access to your business workspace</div>
        </div>
        <div class="login-copyright">FINORA FINANCE STUDIO · {{ now()->year }}</div>
    </div>
</body>
</html>
