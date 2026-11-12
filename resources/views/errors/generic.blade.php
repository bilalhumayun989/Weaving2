<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Unable to complete request · Weaving ERP</title>
  <link rel="stylesheet" href="{{ asset('css/weaving.css') }}">
</head>
<body>
  <main class="error-page">
    <div class="error-card">
      <div class="logo">W</div>
      <div class="kicker">Weaving ERP</div>
      <h1>We couldn’t complete that request</h1>
      <p class="tagline">Your information may not have been saved. Please return to the form and try again. If the problem continues, contact your administrator.</p>
      <div class="actions error-actions">
        <a class="btn" href="{{ $retryUrl }}">Try again</a>
        <a class="btn secondary" href="{{ $retryUrl }}">Return to the application</a>
      </div>
    </div>
  </main>
</body>
</html>
