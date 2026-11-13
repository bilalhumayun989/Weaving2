<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign In · Weaving ERP</title>
  <link rel="stylesheet" href="{{ asset('css/weaving.css') }}">
  <style>
    /* ── Login page layout ── */
    .login-shell {
      min-height: 100vh;
      display: grid;
      grid-template-columns: 1fr 1fr;
    }

    /* Left panel – brand / illustration */
    .login-brand {
      background: #173c3d;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 48px 52px;
      position: relative;
      overflow: hidden;
    }

    /* decorative circles */
    .login-brand::before,
    .login-brand::after {
      content: '';
      position: absolute;
      border-radius: 50%;
      pointer-events: none;
    }
    .login-brand::before {
      width: 420px;
      height: 420px;
      background: rgba(214,255,58,.06);
      top: -120px;
      right: -120px;
    }
    .login-brand::after {
      width: 280px;
      height: 280px;
      background: rgba(214,255,58,.04);
      bottom: -80px;
      left: -60px;
    }

    .brand-top {
      display: flex;
      align-items: center;
      gap: 14px;
      position: relative;
      z-index: 1;
    }
    .brand-top strong {
      font-size: 18px;
      font-weight: 750;
      color: #fff;
      display: block;
    }
    .brand-top small {
      font-size: 11px;
      color: #a5c5c0;
      letter-spacing: .04em;
    }

    .brand-mid {
      position: relative;
      z-index: 1;
    }
    .brand-tagline {
      font-size: 32px;
      font-weight: 780;
      letter-spacing: -.04em;
      line-height: 1.2;
      color: #fff;
      margin: 0 0 18px;
    }
    .brand-tagline em {
      font-style: normal;
      color: var(--lime);
    }
    .brand-desc {
      font-size: 14px;
      color: #98c0bb;
      line-height: 1.65;
      max-width: 340px;
    }

    /* mini stats strip */
    .brand-stats {
      display: flex;
      gap: 32px;
      margin-top: 36px;
    }
    .brand-stat-item {
      border-top: 2px solid rgba(214,255,58,.35);
      padding-top: 12px;
    }
    .brand-stat-item strong {
      display: block;
      font-size: 22px;
      font-weight: 780;
      color: var(--lime);
      letter-spacing: -.03em;
    }
    .brand-stat-item span {
      font-size: 11px;
      color: #7daaa5;
      text-transform: uppercase;
      letter-spacing: .08em;
    }

    .brand-bottom {
      position: relative;
      z-index: 1;
      font-size: 11px;
      color: #557a76;
      letter-spacing: .04em;
    }

    /* Right panel – form */
    .login-form-panel {
      background: var(--bg);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 48px 40px;
    }

    .login-box {
      width: 100%;
      max-width: 400px;
    }

    .login-box-header {
      margin-bottom: 32px;
    }
    .login-box-header .kicker {
      margin-bottom: 8px;
    }
    .login-box-header h1 {
      font-size: 26px;
      margin: 0 0 6px;
    }
    .login-box-header p {
      margin: 0;
      font-size: 13px;
      color: var(--sub);
    }

    .login-card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 16px;
      padding: 32px;
      box-shadow: 0 4px 24px rgba(23,60,61,.07);
    }

    .login-card .field {
      margin-bottom: 20px;
    }
    .login-card .field:last-of-type {
      margin-bottom: 24px;
    }

    /* input with icon */
    .input-wrap {
      position: relative;
    }
    .input-wrap input {
      padding-left: 40px;
    }
    .input-icon {
      position: absolute;
      left: 13px;
      top: 50%;
      transform: translateY(-50%);
      color: #94adb4;
      font-size: 15px;
      pointer-events: none;
      line-height: 1;
    }

    .login-submit {
      width: 100%;
      justify-content: center;
      padding: 12px 16px;
      font-size: 14px;
      border-radius: 10px;
      letter-spacing: .01em;
    }

    .login-footer-note {
      text-align: center;
      margin-top: 20px;
      font-size: 11px;
      color: var(--sub);
      line-height: 1.6;
    }
    .login-footer-note a {
      color: var(--brand);
    }

    /* divider between label and "forgot" link */
    .label-row {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      margin-bottom: 6px;
    }
    .label-row label {
      margin: 0;
    }

    /* ── Responsive ── */
    @media (max-width: 820px) {
      .login-shell {
        grid-template-columns: 1fr;
      }
      .login-brand {
        padding: 32px 28px;
        min-height: 220px;
      }
      .brand-mid {
        display: none;
      }
      .brand-bottom {
        display: none;
      }
      .brand-stats {
        display: none;
      }
      .login-form-panel {
        padding: 32px 20px;
      }
    }
  </style>
</head>
<body style="margin:0">

<div class="login-shell">

  {{-- ── LEFT BRAND PANEL ── --}}
  <div class="login-brand">
    <div class="brand-top">
      <div class="logo" style="width:42px;height:42px;font-size:22px;border-radius:12px">W</div>
      <div>
        <strong>Weaving ERP</strong>
        <small>BroshTech · v1.1</small>
      </div>
    </div>

    <div class="brand-mid">
      <h2 class="brand-tagline">
        Textile operations,<br>
        <em>fully in control.</em>
      </h2>
      <p class="brand-desc">
        Manage contracts, yarn, fabric checking, dispatch, and accounts
        across all three factories from a single unified workspace.
      </p>
      <div class="brand-stats">
        <div class="brand-stat-item">
          <strong>3</strong>
          <span>Factories</span>
        </div>
        <div class="brand-stat-item">
          <strong>360°</strong>
          <span>Visibility</span>
        </div>
        <div class="brand-stat-item">
          <strong>Live</strong>
          <span>Ledger</span>
        </div>
      </div>
    </div>

    <div class="brand-bottom">
      © {{ date('Y') }} BroshTech · Internal system · All rights reserved
    </div>
  </div>

  {{-- ── RIGHT FORM PANEL ── --}}
  <div class="login-form-panel">
    <div class="login-box">

      <div class="login-box-header">
        <div class="kicker">Secure access</div>
        <h1>Welcome back</h1>
        <p>Sign in to your account to continue.</p>
      </div>

      @if($errors->any())
        <div class="notice error" style="margin-bottom:20px">
          {{ $errors->first() }}
        </div>
      @endif

      <div class="login-card">
        <form method="POST" action="{{ route('login.post') }}">
          @csrf

          <div class="field">
            <label>Email Address</label>
            <div class="input-wrap">
              <span class="input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="4" width="20" height="16" rx="2"/>
                  <path d="m2 7 10 7 10-7"/>
                </svg>
              </span>
              <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                autocomplete="username"
                placeholder="you@company.com"
                required
                autofocus
              >
            </div>
          </div>

          <div class="field">
            <div class="label-row">
              <label>Password</label>
            </div>
            <div class="input-wrap">
              <span class="input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="11" width="18" height="11" rx="2"/>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
              </span>
              <input
                type="password"
                name="password"
                autocomplete="current-password"
                placeholder="Enter your password"
                required
              >
            </div>
          </div>

          <button type="submit" class="btn login-submit">
            Sign In &rarr;
          </button>
        </form>
      </div>

      <p class="login-footer-note">
        Access is restricted to authorized personnel only.<br>
        Contact your administrator if you need access.
      </p>

    </div>
  </div>

</div>

</body>
</html>
