<?php
require_once __DIR__ . '/../src/core/bootstrap.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard.html');
    exit;
}

$pageTitle = 'Register — InternBoot';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="icon" href="assets/css/favicon.ico">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap');
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Poppins','Segoe UI',Arial,sans-serif; min-height: 100vh; background: #e8edf3; display: flex; align-items: center; justify-content: center; padding: 30px 20px; }
    .icon { display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; flex-shrink: 0; }
    .icon svg { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .outer-container { width: 100%; max-width: 1200px; background: #ffffff; border-radius: 32px; padding: 20px; box-shadow: 0 30px 80px rgba(0,0,0,0.1), 0 10px 30px rgba(0,0,0,0.06); animation: pageEntry 0.9s cubic-bezier(0.16,1,0.3,1) forwards; opacity: 0; transform: translateY(50px) scale(0.97); }
    @keyframes pageEntry { to { opacity: 1; transform: translateY(0) scale(1); } }
    .inner-container { display: flex; width: 100%; min-height: 88vh; background: linear-gradient(150deg,#0f2557 0%,#1a3a7a 25%,#1e40af 50%,#2563eb 80%,#3b82f6 100%); border-radius: 24px; overflow: hidden; position: relative; }
    .left-panel { width: 44%; min-width: 420px; padding: 48px 40px; display: flex; flex-direction: column; justify-content: center; position: relative; overflow: hidden; }
    .left-content { position: relative; z-index: 2; animation: leftSlide 0.8s 0.3s cubic-bezier(0.16,1,0.3,1) backwards; }
    @keyframes leftSlide { from { opacity: 0; transform: translateX(-40px); } }
    .logo-area { display: flex; align-items: center; gap: 14px; margin-bottom: 8px; }
    .logo-icon-box { width: 60px; height: 60px; background: #ffffff; border: 2px solid #ffffff; border-radius: 18px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(0,0,0,0.15); transition: all 0.5s cubic-bezier(0.16,1,0.3,1); animation: logoPulse 3s ease-in-out infinite; overflow: hidden; }
    .logo-icon-box:hover { transform: rotate(15deg) scale(1.1); background: #ffffff; box-shadow: 0 8px 30px rgba(255,255,255,0.4); }
    @keyframes logoPulse { 0%,100% { box-shadow: 0 0 0 0 rgba(255,255,255,0.3); } 50% { box-shadow: 0 0 0 12px rgba(255,255,255,0); } }
    .logo-text { font-size: 34px; font-weight: 900; color: #ffffff; letter-spacing: -1px; }
    .logo-text span { color: #93c5fd; }
    .left-subtitle { font-size: 14px; color: rgba(255,255,255,0.6); margin-bottom: 24px; letter-spacing: 1px; }
    .key-points { display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px; }
    .point { display: flex; align-items: flex-start; gap: 14px; transition: all 0.3s ease; cursor: default; }
    .point:hover { transform: translateX(10px); }
    .point-icon { width: 44px; height: 44px; min-width: 44px; background: rgba(255,255,255,0.12); border-radius: 12px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.1); color: #93c5fd; }
    .point:hover .point-icon { background: rgba(255,255,255,0.22); transform: scale(1.1) rotate(-5deg); box-shadow: 0 6px 20px rgba(0,0,0,0.15); color: #ffffff; }
    .point-icon .icon svg { width: 20px; height: 20px; }
    .point-text strong { display: block; font-size: 14px; font-weight: 700; color: #ffffff; margin-bottom: 2px; }
    .point-text span { font-size: 12px; color: rgba(255,255,255,0.5); line-height: 1.4; }
    .teacher-section { position: relative; display: flex; align-items: center; justify-content: center; height: 190px; margin-bottom: 30px; margin-top: 10px; }
    .register-visual { position: relative; width: 270px; height: 160px; z-index: 2; animation: visualFloat 3.5s ease-in-out infinite; }
    .register-visual::after { content: ''; position: absolute; left: 50%; bottom: 5px; width: 190px; height: 18px; background: rgba(15,23,42,0.18); border-radius: 999px; filter: blur(8px); transform: translateX(-50%); z-index: 0; }
    @keyframes visualFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
    .application-card { position: absolute; left: 24px; top: 22px; width: 190px; height: 120px; padding: 16px; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.36); border-radius: 18px; backdrop-filter: blur(10px); box-shadow: 0 18px 40px rgba(15,23,42,0.18), inset 0 1px 0 rgba(255,255,255,0.24); z-index: 2; }
    .application-card::before { content: ''; position: absolute; right: 16px; top: 16px; width: 34px; height: 34px; background: linear-gradient(135deg,#93c5fd,#ffffff); border-radius: 50%; opacity: 0.95; }
    .application-card::after { content: ''; position: absolute; right: 24px; top: 25px; width: 18px; height: 10px; border-left: 3px solid #1d4ed8; border-bottom: 3px solid #1d4ed8; transform: rotate(-45deg); }
    .app-title-line { width: 96px; height: 8px; background: rgba(255,255,255,0.92); border-radius: 999px; margin-bottom: 16px; }
    .app-row { display: flex; align-items: center; gap: 9px; margin-bottom: 10px; }
    .app-dot { width: 18px; height: 18px; border-radius: 50%; background: rgba(96,165,250,0.44); border: 1px solid rgba(255,255,255,0.35); position: relative; flex-shrink: 0; }
    .app-dot::after { content: ''; position: absolute; left: 5px; top: 4px; width: 7px; height: 4px; border-left: 2px solid #fff; border-bottom: 2px solid #fff; transform: rotate(-45deg); }
    .app-line { height: 7px; flex: 1; border-radius: 999px; background: rgba(255,255,255,0.48); }
    .app-progress { width: 100%; height: 8px; margin-top: 15px; background: rgba(255,255,255,0.3); border-radius: 999px; overflow: hidden; }
    .app-progress span { display: block; width: 72%; height: 100%; background: linear-gradient(90deg,#fff,#bfdbfe); border-radius: inherit; }
    .success-badge { position: absolute; right: 18px; bottom: 16px; width: 74px; height: 74px; border-radius: 22px; background: #ffffff; box-shadow: 0 16px 32px rgba(15,23,42,0.18); z-index: 3; }
    .success-badge::before { content: ''; position: absolute; left: 20px; top: 18px; width: 34px; height: 34px; background: linear-gradient(135deg,#22c55e,#60a5fa); border-radius: 50%; }
    .success-badge::after { content: ''; position: absolute; left: 31px; top: 29px; width: 14px; height: 8px; border-left: 3px solid #fff; border-bottom: 3px solid #fff; transform: rotate(-45deg); }
    .visual-arrow { position: absolute; right: 8px; top: 54px; width: 72px; height: 2px; background: rgba(255,255,255,0.88); transform: rotate(-18deg); transform-origin: right center; z-index: 1; }
    .visual-arrow::after { content: ''; position: absolute; right: -1px; top: -5px; width: 11px; height: 11px; border-top: 2px solid rgba(255,255,255,0.88); border-right: 2px solid rgba(255,255,255,0.88); transform: rotate(45deg); }
    .speech-bubble { position: absolute; right: 0; top: 0; background: #ffffff; color: #1e40af; padding: 10px 18px; border-radius: 16px 16px 16px 4px; font-size: 13px; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.15); animation: bubbleFloat 3s ease-in-out infinite; white-space: nowrap; z-index: 5; }
    .speech-bubble::before { content: ''; position: absolute; bottom: -8px; left: 14px; width: 0; height: 0; border-left: 8px solid #fff; border-right: 8px solid transparent; border-top: 8px solid #fff; border-bottom: 8px solid transparent; }
    @keyframes bubbleFloat { 0%,100% { transform: scale(1) translateY(0); } 50% { transform: scale(1.04) translateY(-4px); } }
    .speech-arrow { display: inline-block; animation: arrowMove 1s ease-in-out infinite; }
    @keyframes arrowMove { 0%,100% { transform: translateX(0); } 50% { transform: translateX(5px); } }
    .mini-board { position: absolute; left: 10px; top: 20px; width: 80px; height: 60px; background: rgba(255,255,255,0.08); border-radius: 8px; border: 2px solid rgba(255,255,255,0.12); padding: 12px 10px; display: flex; flex-direction: column; gap: 8px; z-index: 1; }
    .board-line { height: 3px; background: rgba(255,255,255,0.2); border-radius: 3px; }
    .board-line.short { width: 55%; }
    .board-line.med { width: 75%; }
    .stats-bar { display: flex; align-items: center; justify-content: center; gap: 18px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 18px 22px; backdrop-filter: blur(8px); }
    .stat-item { display: flex; align-items: center; gap: 10px; transition: transform 0.3s ease; color: #93c5fd; }
    .stat-item:hover { transform: scale(1.06); }
    .stat-item .icon svg { width: 22px; height: 22px; }
    .stat-item strong { display: block; font-size: 17px; font-weight: 800; color: #ffffff; line-height: 1.2; }
    .stat-item > div span { font-size: 10px; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 1px; }
    .stat-sep { width: 1px; height: 34px; background: rgba(255,255,255,0.15); }
    .deco { position: absolute; border-radius: 50%; z-index: 1; }
    .deco-1 { width: 220px; height: 220px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); top: -70px; right: -70px; animation: decoF 14s ease-in-out infinite; }
    .deco-2 { width: 150px; height: 150px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.05); bottom: -50px; left: -40px; animation: decoF 10s ease-in-out infinite reverse; }
    .deco-3 { width: 90px; height: 90px; background: rgba(147,197,253,0.05); top: 40%; left: 65%; animation: decoF 8s ease-in-out infinite; }
    .deco-4 { width: 14px; height: 14px; background: rgba(255,255,255,0.2); top: 18%; left: 12%; animation: decoSpin 6s linear infinite; }
    .deco-5 { width: 10px; height: 10px; background: rgba(147,197,253,0.3); bottom: 28%; right: 18%; animation: decoSpin 8s linear infinite reverse; }
    @keyframes decoF { 0%,100% { transform: translate(0,0); } 50% { transform: translate(20px,-20px); } }
    @keyframes decoSpin { 0% { transform: rotate(0deg) scale(1); } 50% { transform: rotate(180deg) scale(1.5); } 100% { transform: rotate(360deg) scale(1); } }
    .right-panel { flex: 1; background: #ffffff; padding: 24px 30px; overflow-y: auto; border-radius: 0 24px 24px 0; position: relative; }
    .right-panel::-webkit-scrollbar { width: 5px; }
    .right-panel::-webkit-scrollbar-thumb { background: #dbeafe; border-radius: 10px; }
    .mobile-logo { display: none; align-items: center; justify-content: center; gap: 10px; margin-bottom: 22px; }
    .form-header { margin-bottom: 30px; display: flex; flex-direction: column; align-items: center; text-align: center; animation: headerIn 0.6s 0.5s cubic-bezier(0.16,1,0.3,1) backwards; }
    @keyframes headerIn { from { opacity: 0; transform: translateY(-15px); } }
    .form-header h2 { font-size: 28px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; position: relative; display: inline-block; }
    .form-header h2::after { content: ''; position: absolute; bottom: -6px; left: 50%; width: 50px; height: 4px; background: linear-gradient(90deg,#2563eb,#60a5fa); border-radius: 4px; transform: translateX(-50%); transition: width 0.5s ease; }
    .form-header:hover h2::after { width: 100%; }
    .form-header p { font-size: 13.5px; color: #94a3b8; margin-top: 12px; }
    form { display: flex; flex-direction: column; gap: 6px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 8px; }
    .form-group { margin-bottom: 8px; animation: fieldUp 0.5s cubic-bezier(0.16,1,0.3,1) backwards; }
    .form-group.full { width: 100%; }
    @keyframes fieldUp { from { opacity: 0; transform: translateY(16px); } }
    label { display: flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; transition: all 0.3s ease; cursor: default; }
    label .icon { color: #3b82f6; transition: all 0.3s ease; }
    label .icon svg { width: 13px; height: 13px; }
    label:hover { color: #1e40af; transform: translateX(3px); }
    label:hover .icon { color: #1e40af; transform: scale(1.2) rotate(-8deg); }
    input[type="text"], input[type="email"], input[type="password"], input[type="tel"], select { display: block; width: 100%; padding: 12px 16px; font-size: 13px; font-family: 'Poppins','Segoe UI',Arial,sans-serif; font-weight: 400; color: #1e293b; background: #ffffff; border: 2px solid #e2e8f0; border-radius: 8px; outline: none; transition: all 0.3s cubic-bezier(0.25,0.8,0.25,1); -webkit-appearance: none; appearance: none; }
    input::placeholder { color: #cbd5e1; font-weight: 300; }
    input[type="text"]:hover,input[type="email"]:hover,input[type="password"]:hover,input[type="tel"]:hover,select:hover { border-color: #93c5fd; background: #f0f7ff; box-shadow: 0 4px 12px rgba(59,130,246,0.08); }
    input[type="text"]:focus,input[type="email"]:focus,input[type="password"]:focus,input[type="tel"]:focus,select:focus { border-color: #3b82f6; background: #ffffff; box-shadow: 0 0 0 4px rgba(59,130,246,0.15), 0 8px 16px rgba(59,130,246,0.1); }
    .select-wrap select { cursor: pointer; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%233b82f6' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; padding-right: 40px; }
    select option { background: #ffffff; color: #1e293b; }
    .phone-input-wrap { display: grid; grid-template-columns: 75px 1fr; gap: 12px; width: 100%; }
    .phone-input-wrap .country-code-select { width: 100%; padding: 12px 6px; cursor: pointer; text-align: center; }
    .btn-submit { display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 14px 32px; margin-top: 10px; font-family: 'Poppins','Segoe UI',Arial,sans-serif; font-size: 15px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; color: #2563eb; background: #ffffff; border: 2px solid #2563eb; border-radius: 8px; cursor: pointer; position: relative; overflow: hidden; transition: all 0.5s cubic-bezier(0.25,0.8,0.25,1); z-index: 1; animation: btnReveal 0.5s 1.3s cubic-bezier(0.16,1,0.3,1) backwards; }
    @keyframes btnReveal { from { opacity: 0; transform: translateY(15px) scale(0.95); } }
    .btn-submit .icon { transition: transform 0.4s ease; }
    .btn-submit .icon svg { width: 18px; height: 18px; stroke-width: 2.5; }
    .btn-submit::before { content: ''; position: absolute; top: 0; left: 0; width: 0; height: 100%; background: linear-gradient(135deg,#1e40af,#2563eb,#3b82f6); z-index: -2; transition: width 0.5s cubic-bezier(0.25,0.8,0.25,1); border-radius: 6px; }
    .btn-submit::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(90deg,transparent,rgba(255,255,255,0.25),transparent); z-index: -1; transition: left 0.6s ease; }
    .btn-submit:hover { color: #ffffff; border-color: #1e40af; transform: translateY(-3px); box-shadow: 0 12px 24px rgba(37,99,235,0.25); }
    .btn-submit:hover::before { width: 100%; }
    .btn-submit:hover::after { left: 120%; }
    .btn-submit:hover .icon { transform: translateX(-3px) scale(1.1); color: #ffffff; }
    .btn-submit:active { transform: translateY(-1px) scale(0.98); }
    .login-section { display: flex; align-items: center; justify-content: center; gap: 12px; margin-top: 24px; padding-top: 0px; flex-wrap: wrap; animation: fadeUp 0.5s 1.4s backwards; }
    .login-section p { font-size: 13.5px; color: #94a3b8; }
    .btn-login { display: inline-flex; align-items: center; gap: 8px; padding: 10px 26px; font-family: 'Poppins','Segoe UI',Arial,sans-serif; font-size: 13px; font-weight: 600; color: #2563eb; background: #ffffff; border: 2px solid #dbeafe; border-radius: 8px; text-decoration: none; transition: all 0.4s cubic-bezier(0.25,0.8,0.25,1); position: relative; overflow: hidden; }
    .btn-login::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg,#2563eb,#3b82f6); opacity: 0; transition: opacity 0.4s ease; z-index: 0; border-radius: 6px; }
    .btn-login span,.btn-login .icon { position: relative; z-index: 1; }
    .btn-login:hover { color: #ffffff; border-color: #2563eb; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(37,99,235,0.2); }
    .btn-login:hover::before { opacity: 1; }
    .btn-login:hover .icon { transform: translateX(3px); color: #ffffff; }
    .secure-note { text-align: center; margin-top: 16px; font-size: 11px; color: #94a3b8; display: flex; align-items: center; justify-content: center; gap: 6px; animation: fadeUp 0.5s 1.5s backwards; }
    .secure-note strong { color: #2563eb; font-weight: 700; }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } }
    .form-error { display: none; padding: 11px 14px; margin: 4px 0 8px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 10px; font-size: 13px; font-weight: 600; }
    .form-error.show { display: block; }
    .overlay-btn { position: fixed; bottom: 20px; right: 20px; background: #ff4d6d; color: white; border: none; border-radius: 50px; padding: 14px 20px; font-size: 16px; cursor: pointer; box-shadow: 0 8px 20px rgba(0,0,0,0.25); transition: all 0.3s ease; z-index: 1000; font-family: 'Poppins',sans-serif; }
    .overlay-btn:hover { background: #e63956; transform: scale(1.1); }
    .form-row:nth-child(1) .form-group:nth-child(1) { animation-delay: 0.55s; }
    .form-row:nth-child(1) .form-group:nth-child(2) { animation-delay: 0.6s; }
    .form-row:nth-child(2) .form-group:nth-child(1) { animation-delay: 0.65s; }
    .form-row:nth-child(2) .form-group:nth-child(2) { animation-delay: 0.7s; }
    .form-row:nth-child(3) .form-group:nth-child(1) { animation-delay: 0.75s; }
    .form-row:nth-child(3) .form-group:nth-child(2) { animation-delay: 0.8s; }
    .form-group.full { animation-delay: 0.85s; }
    @media screen and (max-width: 1024px) { .left-panel { width: 380px; min-width: 340px; padding: 36px 30px; } .right-panel { padding: 36px 32px 30px; } }
    @media screen and (max-width: 860px) { .inner-container { flex-direction: column; } .left-panel { width: 100%; min-width: unset; padding: 36px 30px 28px; } .teacher-section { display: none; } .right-panel { border-radius: 0 0 24px 24px; padding: 32px 30px 28px; } }
    @media screen and (max-width: 640px) { body { padding: 10px; } .outer-container { padding: 10px; border-radius: 22px; } .inner-container { border-radius: 18px; } .left-panel { display: none; } .mobile-logo { display: flex; } .right-panel { border-radius: 18px; padding: 28px 20px 24px; } .form-header h2 { font-size: 22px; } .form-row { grid-template-columns: 1fr; gap: 4px; } }
  </style>
</head>
<body>
<button class="overlay-btn" onclick="window.location.href='login.php'">Login Now</button>
<div class="outer-container">
  <div class="inner-container">
    <div class="left-panel">
      <div class="left-content">
        <div class="logo-area">
          <div class="logo-icon-box">
            <img src="assets/css/internboot-official-logo.webp" alt="logo" style="width:52px;height:52px;object-fit:contain;">
          </div>
          <h1 class="logo-text">Intern<span>Boot</span></h1>
        </div>
        <p class="left-subtitle">Your Gateway to Professional Growth</p>
        <div class="welcome-box" style="margin-bottom:20px; color: #fff;">
          <h3>Welcome! 👋</h3>
          <p style="font-size:14px; color:rgba(255,255,255,0.8);">Give assessments, clear levels, unlock placement opportunities, and download your verified certificates — all in one place.</p>
        </div>
        <div class="key-points">
          <div class="point">
            <div class="point-icon"><span class="icon"><svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span></div>
            <div class="point-text"><strong>Level Assessment Test</strong><span>Take the test to evaluate your skills</span></div>
          </div>
          <div class="point">
            <div class="point-icon"><span class="icon"><svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span></div>
            <div class="point-text"><strong>Clear Levels 1 to 5</strong><span>Progress through levels to unlock placements</span></div>
          </div>
          <div class="point">
            <div class="point-icon"><span class="icon"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span></div>
            <div class="point-text"><strong>Verified Certificates</strong><span>Download verified certificates anytime</span></div>
          </div>
        </div>
        <div class="teacher-section">
          <div class="mini-board">
            <div class="board-line"></div><div class="board-line short"></div>
            <div class="board-line"></div><div class="board-line med"></div>
          </div>
          <div class="register-visual" aria-hidden="true">
            <div class="application-card">
              <div class="app-title-line"></div>
              <div class="app-row"><span class="app-dot"></span><span class="app-line"></span></div>
              <div class="app-row"><span class="app-dot"></span><span class="app-line"></span></div>
              <div class="app-row"><span class="app-dot"></span><span class="app-line"></span></div>
              <div class="app-progress"><span></span></div>
            </div>
            <div class="success-badge"></div>
            <div class="visual-arrow"></div>
          </div>
          <div class="speech-bubble">Register Now! <span class="speech-arrow">-></span></div>
        </div>
        <div class="stats-bar">
          <div class="stat-item">
            <span class="icon"><svg viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 3 3 6 3s6-1 6-3v-5"/></svg></span>
            <div><strong>10,000+</strong><span>Students</span></div>
          </div>
          <div class="stat-sep"></div>
          <div class="stat-item">
            <span class="icon"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V5a4 4 0 0 0-8 0v2"/></svg></span>
            <div><strong>500+</strong><span>Companies</span></div>
          </div>
          <div class="stat-sep"></div>
          <div class="stat-item">
            <span class="icon"><svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></span>
            <div><strong>4.9/5</strong><span>Rating</span></div>
          </div>
        </div>
      </div>
      <div class="deco deco-1"></div><div class="deco deco-2"></div>
      <div class="deco deco-3"></div><div class="deco deco-4"></div><div class="deco deco-5"></div>
    </div>
    <div class="right-panel">
      <div class="mobile-logo">
        <img src="assets/css/internboot-official-logo.webp" alt="InternBoot" style="height:44px;">
      </div>
      <div class="form-header">
        <h2>Create Account</h2>
        <p>Fill in your details to start your internship journey</p>
      </div>
      <form id="ibRegisterForm" action="api/auth/register.php" method="POST" autocomplete="off">
        <div class="form-group full">
          <label>FULL NAME</label>
          <input type="text" name="full_name" required>
        </div>
        <div class="form-group full">
          <label>EMAIL ADDRESS</label>
          <input type="email" name="email" required>
        </div>
        <div class="form-group full">
          <label>PHONE NUMBER</label>
          <div class="phone-input-wrap">
            <select id="mobile_country_code" class="country-code-select">
              <option value="+91" selected>+91</option>
              <option value="+1">+1</option>
              <option value="+7">+7</option>
              <option value="+20">+20</option>
              <option value="+27">+27</option>
              <option value="+30">+30</option>
              <option value="+31">+31</option>
              <option value="+32">+32</option>
              <option value="+33">+33</option>
              <option value="+34">+34</option>
              <option value="+36">+36</option>
              <option value="+39">+39</option>
              <option value="+40">+40</option>
              <option value="+41">+41</option>
              <option value="+43">+43</option>
              <option value="+44">+44</option>
              <option value="+45">+45</option>
              <option value="+46">+46</option>
              <option value="+47">+47</option>
              <option value="+48">+48</option>
              <option value="+49">+49</option>
              <option value="+51">+51</option>
              <option value="+52">+52</option>
              <option value="+53">+53</option>
              <option value="+54">+54</option>
              <option value="+55">+55</option>
              <option value="+56">+56</option>
              <option value="+57">+57</option>
              <option value="+58">+58</option>
              <option value="+60">+60</option>
              <option value="+61">+61</option>
              <option value="+62">+62</option>
              <option value="+63">+63</option>
              <option value="+64">+64</option>
              <option value="+65">+65</option>
              <option value="+66">+66</option>
              <option value="+81">+81</option>
              <option value="+82">+82</option>
              <option value="+84">+84</option>
              <option value="+86">+86</option>
              <option value="+90">+90</option>
              <option value="+92">+92</option>
              <option value="+93">+93</option>
              <option value="+94">+94</option>
              <option value="+95">+95</option>
              <option value="+98">+98</option>
              <option value="+211">+211</option>
              <option value="+212">+212</option>
              <option value="+213">+213</option>
              <option value="+216">+216</option>
              <option value="+218">+218</option>
              <option value="+220">+220</option>
              <option value="+221">+221</option>
              <option value="+222">+222</option>
              <option value="+223">+223</option>
              <option value="+224">+224</option>
              <option value="+225">+225</option>
              <option value="+226">+226</option>
              <option value="+227">+227</option>
              <option value="+228">+228</option>
              <option value="+229">+229</option>
              <option value="+230">+230</option>
              <option value="+231">+231</option>
              <option value="+232">+232</option>
              <option value="+233">+233</option>
              <option value="+234">+234</option>
              <option value="+235">+235</option>
              <option value="+236">+236</option>
              <option value="+237">+237</option>
              <option value="+238">+238</option>
              <option value="+239">+239</option>
              <option value="+240">+240</option>
              <option value="+241">+241</option>
              <option value="+242">+242</option>
              <option value="+243">+243</option>
              <option value="+244">+244</option>
              <option value="+245">+245</option>
              <option value="+248">+248</option>
              <option value="+249">+249</option>
              <option value="+250">+250</option>
              <option value="+251">+251</option>
              <option value="+252">+252</option>
              <option value="+253">+253</option>
              <option value="+254">+254</option>
              <option value="+255">+255</option>
              <option value="+256">+256</option>
              <option value="+257">+257</option>
              <option value="+258">+258</option>
              <option value="+260">+260</option>
              <option value="+261">+261</option>
              <option value="+262">+262</option>
              <option value="+263">+263</option>
              <option value="+264">+264</option>
              <option value="+265">+265</option>
              <option value="+266">+266</option>
              <option value="+267">+267</option>
              <option value="+268">+268</option>
              <option value="+269">+269</option>
              <option value="+290">+290</option>
              <option value="+291">+291</option>
              <option value="+297">+297</option>
              <option value="+298">+298</option>
              <option value="+299">+299</option>
              <option value="+350">+350</option>
              <option value="+351">+351</option>
              <option value="+352">+352</option>
              <option value="+353">+353</option>
              <option value="+354">+354</option>
              <option value="+355">+355</option>
              <option value="+356">+356</option>
              <option value="+357">+357</option>
              <option value="+358">+358</option>
              <option value="+359">+359</option>
              <option value="+370">+370</option>
              <option value="+371">+371</option>
              <option value="+372">+372</option>
              <option value="+373">+373</option>
              <option value="+374">+374</option>
              <option value="+375">+375</option>
              <option value="+376">+376</option>
              <option value="+377">+377</option>
              <option value="+378">+378</option>
              <option value="+380">+380</option>
              <option value="+381">+381</option>
              <option value="+382">+382</option>
              <option value="+383">+383</option>
              <option value="+385">+385</option>
              <option value="+386">+386</option>
              <option value="+387">+387</option>
              <option value="+389">+389</option>
              <option value="+420">+420</option>
              <option value="+421">+421</option>
              <option value="+423">+423</option>
              <option value="+500">+500</option>
              <option value="+501">+501</option>
              <option value="+502">+502</option>
              <option value="+503">+503</option>
              <option value="+504">+504</option>
              <option value="+505">+505</option>
              <option value="+506">+506</option>
              <option value="+507">+507</option>
              <option value="+508">+508</option>
              <option value="+509">+509</option>
              <option value="+590">+590</option>
              <option value="+591">+591</option>
              <option value="+592">+592</option>
              <option value="+593">+593</option>
              <option value="+594">+594</option>
              <option value="+595">+595</option>
              <option value="+596">+596</option>
              <option value="+597">+597</option>
              <option value="+598">+598</option>
              <option value="+599">+599</option>
              <option value="+670">+670</option>
              <option value="+672">+672</option>
              <option value="+673">+673</option>
              <option value="+674">+674</option>
              <option value="+675">+675</option>
              <option value="+676">+676</option>
              <option value="+677">+677</option>
              <option value="+678">+678</option>
              <option value="+679">+679</option>
              <option value="+680">+680</option>
              <option value="+681">+681</option>
              <option value="+682">+682</option>
              <option value="+683">+683</option>
              <option value="+685">+685</option>
              <option value="+686">+686</option>
              <option value="+687">+687</option>
              <option value="+688">+688</option>
              <option value="+689">+689</option>
              <option value="+690">+690</option>
              <option value="+691">+691</option>
              <option value="+692">+692</option>
              <option value="+850">+850</option>
              <option value="+852">+852</option>
              <option value="+853">+853</option>
              <option value="+855">+855</option>
              <option value="+856">+856</option>
              <option value="+880">+880</option>
              <option value="+886">+886</option>
              <option value="+960">+960</option>
              <option value="+961">+961</option>
              <option value="+962">+962</option>
              <option value="+963">+963</option>
              <option value="+964">+964</option>
              <option value="+965">+965</option>
              <option value="+966">+966</option>
              <option value="+967">+967</option>
              <option value="+968">+968</option>
              <option value="+970">+970</option>
              <option value="+971">+971</option>
              <option value="+972">+972</option>
              <option value="+973">+973</option>
              <option value="+974">+974</option>
              <option value="+975">+975</option>
              <option value="+976">+976</option>
              <option value="+977">+977</option>
              <option value="+992">+992</option>
              <option value="+993">+993</option>
              <option value="+994">+994</option>
              <option value="+995">+995</option>
              <option value="+996">+996</option>
              <option value="+998">+998</option>
            </select>
            <input type="tel" id="mobile_number" class="phone-number-input" inputmode="numeric" pattern="[0-9\s-]{6,15}" maxlength="15" required>
          </div>
          <input type="hidden" name="mobile" id="mobile_full">
        </div>
        <div class="form-group full">
          <label>PASSWORD</label>
          <div style="position: relative;">
            <input type="password" name="password" required>
            <span class="icon" style="position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#94a3b8;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></span>
          </div>
        </div>
        <div class="form-group full">
          <label>CONFIRM PASSWORD</label>
          <div style="position: relative;">
            <input type="password" name="confirm_password" required>
            <span class="icon" style="position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#94a3b8;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></span>
          </div>
        </div>
        <div id="formAlert" class="form-error"></div>
        <button type="submit" class="btn-submit">
          <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg></span>
          <span>CREATE ACCOUNT</span>
        </button>
      </form>
      <div class="login-section">
        <p>Already have an account?</p>
        <a href="login.php" class="btn-login">
          <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg></span>
          <span>LOGIN HERE</span>
        </a>
      </div>
      <div class="secure-note">
        <span class="icon" style="color:#10b981;"><svg viewBox="0 0 24 24" style="stroke:#10b981"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
        256-bit SSL Encrypted . <strong>InternBoot</strong>
      </div>
    </div>
  </div>
</div>
<script src="assets/js/auth.js"></script>
<script>
  const mobileCode = document.getElementById('mobile_country_code');
  const mobileNum  = document.getElementById('mobile_number');
  const mobileFull = document.getElementById('mobile_full');
  function updatePhone() { const d = mobileNum.value.replace(/[^0-9]/g,''); mobileFull.value = d ? mobileCode.value+' '+d : ''; }
  [mobileCode,mobileNum].forEach(f => f.addEventListener('input', updatePhone));
  document.getElementById('ibRegisterForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    updatePhone();
    
    const fn = this.querySelector('[name="full_name"]').value.trim();
    const email = this.querySelector('[name="email"]').value.trim();
    const pwd = this.querySelector('[name="password"]').value;
    const cpwd = this.querySelector('[name="confirm_password"]').value;
    const phone = mobileFull.value.trim();
    
    const alert = document.getElementById('formAlert');
    alert.classList.remove('show');
    
    if (pwd !== cpwd) { 
      alert.textContent = 'Passwords do not match.'; 
      alert.classList.add('show'); 
      return; 
    }
    
    const payload = {
      full_name: fn,
      email: email,
      phone: phone,
      password: pwd,
      confirm_password: cpwd
    };
    
    try {
      const res = await fetch('api/auth/register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      
      if (data.status === 'success') {
        window.location.href = 'otp.html?email=' + encodeURIComponent(email);
      } else {
        alert.textContent = data.message || 'Registration failed.';
        alert.classList.add('show');
      }
    } catch (err) {
      alert.textContent = 'An error occurred. Please try again.';
      alert.classList.add('show');
    }
  });
</script>
</body>
</html>