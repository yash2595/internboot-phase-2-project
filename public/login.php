<?php
require_once __DIR__ . '/../src/core/bootstrap.php';
require_once __DIR__ . '/../src/core/candidate_resolver.php';

if (isset($_SESSION['user_id']) || isset($_SESSION['candidate_id'])) {
    $role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';

    if (in_array($role, ['admin', 'staff'], true)) {
        $validatedRole = resolve_admin_role($conn);
        if ($validatedRole && in_array($validatedRole, ['admin', 'staff'], true)) {
            header('Location: /admin/index.html');
            exit;
        }
    } elseif ($role === 'candidate' || isset($_SESSION['candidate_id'])) {
        $validatedCid = validate_candidate_session($conn);
        if ($validatedCid !== null) {
            header('Location: /dashboard.html');
            exit;
        }
    } else {
        // Unknown or unset role: check admin/staff first, then candidate
        $validatedRole = resolve_admin_role($conn);
        if ($validatedRole && in_array($validatedRole, ['admin', 'staff'], true)) {
            header('Location: /admin/index.html');
            exit;
        }
        $validatedCid = validate_candidate_session($conn);
        if ($validatedCid !== null) {
            header('Location: /dashboard.html');
            exit;
        }
    }

    // If validation failed (deactivated, row missing, or session stale), destroy session entirely
    destroy_session();
}

$pageTitle = 'Log In — InternBoot';
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

  body {
    font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
    min-height: 100vh;
    background: #e8edf3;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 30px 20px;
  }

  .icon { display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; flex-shrink: 0; }
  .icon svg { width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

  /* OUTER WHITE CONTAINER */
  .outer-container {
    width: 100%;
    max-width: 1050px;
    background: #ffffff;
    border-radius: 32px;
    padding: 20px;
    box-shadow: 0 30px 80px rgba(0,0,0,0.1), 0 10px 30px rgba(0,0,0,0.06);
    animation: pageEntry 0.9s cubic-bezier(0.16,1,0.3,1) forwards;
    opacity: 0;
    transform: translateY(50px) scale(0.97);
  }

  @keyframes pageEntry { to { opacity: 1; transform: translateY(0) scale(1); } }

  /* INNER BLUE CONTAINER */
  .inner-container {
    display: flex;
    width: 100%;
    min-height: 600px;
    background: linear-gradient(150deg, #0f2557 0%, #1a3a7a 25%, #1e40af 50%, #2563eb 80%, #3b82f6 100%);
    border-radius: 24px;
    overflow: hidden;
    position: relative;
  }

  /* LEFT PANEL */
  .left-panel {
    width: 46%; min-width: 400px;
    padding: 50px 44px;
    display: flex; flex-direction: column; justify-content: center;
    position: relative; overflow: hidden;
  }

  .left-content { position: relative; z-index: 2; animation: leftSlide 0.8s 0.3s cubic-bezier(0.16,1,0.3,1) backwards; }
  @keyframes leftSlide { from { opacity: 0; transform: translateX(-40px); } }

  /* Logo */
  .logo-area { display: flex; align-items: center; gap: 14px; margin-bottom: 10px; }

  .logo-icon-box {
    width: 60px; height: 60px;
    background: rgba(255,255,255,0.15);
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(8px);
    transition: all 0.5s cubic-bezier(0.16,1,0.3,1);
    animation: logoPulse 3s ease-in-out infinite;
    overflow: hidden;
  }

  .logo-icon-box:hover { transform: rotate(15deg) scale(1.1); background: rgba(255,255,255,0.25); box-shadow: 0 8px 30px rgba(255,255,255,0.15); }
  @keyframes logoPulse { 0%,100% { box-shadow: 0 0 0 0 rgba(255,255,255,0.3); } 50% { box-shadow: 0 0 0 12px rgba(255,255,255,0); } }

  .logo-text { font-size: 34px; font-weight: 900; color: #ffffff; letter-spacing: -1px; }
  .logo-text span { color: #93c5fd; }

  .left-subtitle { font-size: 14px; color: rgba(255,255,255,0.6); margin-bottom: 36px; letter-spacing: 1px; }

  /* Welcome Box */
  .welcome-box {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 18px;
    padding: 28px 24px;
    margin-bottom: 32px;
    backdrop-filter: blur(8px);
  }
  .welcome-box h3 { font-size: 22px; font-weight: 800; color: #ffffff; margin-bottom: 10px; }
  .welcome-box p { font-size: 13px; color: rgba(255,255,255,0.6); line-height: 1.7; }

  /* Features */
  .features-mini { display: flex; flex-direction: column; gap: 16px; margin-bottom: 34px; }

  .feat { display: flex; align-items: center; gap: 12px; transition: all 0.3s ease; cursor: default; }
  .feat:hover { transform: translateX(8px); }

  .feat-dot {
    width: 36px; height: 36px; min-width: 36px;
    background: rgba(255,255,255,0.12);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.3s ease;
    color: #93c5fd;
    border: 1px solid rgba(255,255,255,0.08);
  }
  .feat:hover .feat-dot { background: rgba(255,255,255,0.22); color: #ffffff; transform: scale(1.1) rotate(-5deg); }
  .feat-dot .icon svg { width: 16px; height: 16px; }
  .feat span { font-size: 13px; color: rgba(255,255,255,0.75); font-weight: 500; }
  .feat:hover span { color: #ffffff; }

  /* Illustration */
  .teacher-section {
    position: relative; display: flex; align-items: center; justify-content: center;
    height: 190px; margin-bottom: 32px;
  }

  .register-visual {
    position: relative; width: 270px; height: 160px; z-index: 2;
    animation: visualFloat 3.5s ease-in-out infinite;
  }

  .register-visual::after {
    content: '';
    position: absolute; left: 50%; bottom: 5px;
    width: 190px; height: 18px;
    background: rgba(15,23,42,0.18);
    border-radius: 999px; filter: blur(8px);
    transform: translateX(-50%); z-index: 0;
  }

  @keyframes visualFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }

  .application-card {
    position: absolute; left: 24px; top: 22px;
    width: 190px; height: 120px; padding: 16px;
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.36);
    border-radius: 18px; backdrop-filter: blur(10px);
    box-shadow: 0 18px 40px rgba(15,23,42,0.18), inset 0 1px 0 rgba(255,255,255,0.24);
    z-index: 2;
  }
  .application-card::before { content: ''; position: absolute; right: 16px; top: 16px; width: 34px; height: 34px; background: linear-gradient(135deg,#93c5fd,#ffffff); border-radius: 50%; opacity: 0.95; }
  .application-card::after { content: ''; position: absolute; right: 24px; top: 25px; width: 18px; height: 10px; border-left: 3px solid #1d4ed8; border-bottom: 3px solid #1d4ed8; transform: rotate(-45deg); }

  .app-title-line { width: 96px; height: 8px; background: rgba(255,255,255,0.92); border-radius: 999px; margin-bottom: 16px; }
  .app-row { display: flex; align-items: center; gap: 9px; margin-bottom: 10px; }
  .app-dot { width: 18px; height: 18px; border-radius: 50%; background: rgba(96,165,250,0.44); border: 1px solid rgba(255,255,255,0.35); position: relative; flex-shrink: 0; }
  .app-dot::after { content: ''; position: absolute; left: 5px; top: 4px; width: 7px; height: 4px; border-left: 2px solid #fff; border-bottom: 2px solid #fff; transform: rotate(-45deg); }
  .app-line { height: 7px; flex: 1; border-radius: 999px; background: rgba(255,255,255,0.48); }
  .app-row:nth-child(4) .app-line { max-width: 86px; }
  .app-progress { width: 100%; height: 8px; margin-top: 15px; background: rgba(255,255,255,0.3); border-radius: 999px; overflow: hidden; }
  .app-progress span { display: block; width: 72%; height: 100%; background: linear-gradient(90deg,#fff,#bfdbfe); border-radius: inherit; }

  .success-badge { position: absolute; right: 18px; bottom: 16px; width: 74px; height: 74px; border-radius: 22px; background: #ffffff; box-shadow: 0 16px 32px rgba(15,23,42,0.18); z-index: 3; }
  .success-badge::before { content: ''; position: absolute; left: 20px; top: 18px; width: 34px; height: 34px; background: linear-gradient(135deg,#22c55e,#60a5fa); border-radius: 50%; }
  .success-badge::after { content: ''; position: absolute; left: 31px; top: 29px; width: 14px; height: 8px; border-left: 3px solid #fff; border-bottom: 3px solid #fff; transform: rotate(-45deg); }

  .visual-arrow { position: absolute; right: 8px; top: 54px; width: 72px; height: 2px; background: rgba(255,255,255,0.88); transform: rotate(-18deg); transform-origin: right center; z-index: 1; }
  .visual-arrow::after { content: ''; position: absolute; right: -1px; top: -5px; width: 11px; height: 11px; border-top: 2px solid rgba(255,255,255,0.88); border-right: 2px solid rgba(255,255,255,0.88); transform: rotate(45deg); }

  /* Speech Bubble */
  .speech-bubble {
    position: absolute; right: 0; top: 0;
    background: #ffffff; color: #1e40af;
    padding: 10px 18px; border-radius: 16px 16px 16px 4px;
    font-size: 13px; font-weight: 700;
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    animation: bubbleFloat 3s ease-in-out infinite;
    white-space: nowrap; z-index: 5;
  }
  .speech-bubble::before { content: ''; position: absolute; bottom: -8px; left: 14px; width: 0; height: 0; border-left: 8px solid #fff; border-right: 8px solid transparent; border-top: 8px solid #fff; border-bottom: 8px solid transparent; }
  @keyframes bubbleFloat { 0%,100% { transform: scale(1) translateY(0); } 50% { transform: scale(1.04) translateY(-4px); } }

  .speech-arrow { display: inline-block; animation: arrowMove 1s ease-in-out infinite; }
  @keyframes arrowMove { 0%,100% { transform: translateX(0); } 50% { transform: translateX(5px); } }

  .mini-board { position: absolute; left: 10px; top: 20px; width: 80px; height: 60px; background: rgba(255,255,255,0.08); border-radius: 8px; border: 2px solid rgba(255,255,255,0.12); padding: 12px 10px; display: flex; flex-direction: column; gap: 8px; z-index: 1; }
  .board-line { height: 3px; background: rgba(255,255,255,0.2); border-radius: 3px; }
  .board-line.short { width: 55%; }
  .board-line.med { width: 75%; }

  /* Decorative circles */
  .deco { position: absolute; border-radius: 50%; z-index: 1; }
  .d1 { width: 200px; height: 200px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); top: -60px; right: -60px; animation: df 14s ease-in-out infinite; }
  .d2 { width: 140px; height: 140px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.05); bottom: -40px; left: -40px; animation: df 10s ease-in-out infinite reverse; }
  .d3 { width: 80px; height: 80px; background: rgba(147,197,253,0.05); top: 35%; left: 60%; animation: df 8s ease-in-out infinite; }
  .d4 { width: 14px; height: 14px; background: rgba(255,255,255,0.2); top: 15%; left: 10%; animation: ds 6s linear infinite; }
  .d5 { width: 10px; height: 10px; background: rgba(147,197,253,0.3); bottom: 25%; right: 15%; animation: ds 8s linear infinite reverse; }
  @keyframes df { 0%,100% { transform: translate(0,0); } 50% { transform: translate(20px,-20px); } }
  @keyframes ds { 0% { transform: rotate(0deg) scale(1); } 50% { transform: rotate(180deg) scale(1.5); } 100% { transform: rotate(360deg) scale(1); } }

  /* RIGHT PANEL */
  .right-panel {
    flex: 1; background: #ffffff;
    padding: 50px 50px 44px;
    display: flex; flex-direction: column; justify-content: center;
    border-radius: 0 24px 24px 0;
    position: relative; overflow: hidden;
  }

  .mobile-logo { display: none; align-items: center; justify-content: center; gap: 10px; margin-bottom: 30px; }

  /* Form Header */
  .form-header { margin-bottom: 36px; animation: headIn 0.6s 0.5s cubic-bezier(0.16,1,0.3,1) backwards; }
  @keyframes headIn { from { opacity: 0; transform: translateY(-15px); } }

  .form-header .greeting { font-size: 15px; color: #3b82f6; font-weight: 600; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
  .form-header .greeting .wave { display: inline-block; animation: waveHand 2s ease-in-out infinite; font-size: 22px; }
  @keyframes waveHand { 0%,100% { transform: rotate(0deg); } 25% { transform: rotate(20deg); } 50% { transform: rotate(-10deg); } 75% { transform: rotate(15deg); } }

  .form-header h2 { font-size: 30px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; position: relative; display: inline-block; margin-bottom: 0; }
  .form-header h2::after { content: ''; position: absolute; bottom: -6px; left: 0; width: 50px; height: 4px; background: linear-gradient(90deg,#2563eb,#60a5fa); border-radius: 4px; transition: width 0.5s ease; }
  .form-header:hover h2::after { width: 100%; }
  .form-header p { font-size: 14px; color: #94a3b8; margin-top: 14px; }

  /* Form */
  .login-form { display: flex; flex-direction: column; gap: 8px; }
  .form-group { margin-bottom: 8px; animation: fieldUp 0.5s cubic-bezier(0.16,1,0.3,1) backwards; }
  .form-group:nth-child(1) { animation-delay: 0.6s; }
  .form-group:nth-child(2) { animation-delay: 0.7s; }
  @keyframes fieldUp { from { opacity: 0; transform: translateY(16px); } }

  label { display: flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px; transition: all 0.3s ease; cursor: default; }
  label .icon { color: #3b82f6; transition: all 0.3s ease; }
  label .icon svg { width: 14px; height: 14px; }
  label:hover { color: #1e40af; transform: translateX(4px); }
  label:hover .icon { color: #1e40af; transform: scale(1.2) rotate(-8deg); }

  input[type="email"], input[type="password"] {
    display: block; width: 100%;
    padding: 16px 20px; font-size: 15px;
    font-family: 'Poppins','Segoe UI',Arial,sans-serif;
    font-weight: 400; color: #1e293b;
    background: #ffffff; border: 2px solid #e2e8f0;
    border-radius: 14px; outline: none;
    transition: all 0.4s cubic-bezier(0.25,0.8,0.25,1);
  }
  input::placeholder { color: #cbd5e1; font-weight: 300; }
  input[type="email"]:hover, input[type="password"]:hover { border-color: #93c5fd; background: #f8faff; transform: translateY(-2px); box-shadow: 0 4px 16px rgba(59,130,246,0.1), 0 0 0 3px rgba(59,130,246,0.05); }
  input[type="email"]:focus, input[type="password"]:focus { border-color: #3b82f6; background: #ffffff; transform: translateY(-2px); box-shadow: 0 0 0 4px rgba(59,130,246,0.12), 0 8px 28px rgba(59,130,246,0.1); }

  .forgot-row { display: flex; justify-content: flex-end; margin-top: -2px; margin-bottom: 6px; animation: fieldUp 0.5s 0.75s cubic-bezier(0.16,1,0.3,1) backwards; }
  .forgot-link { font-size: 12.5px; color: #3b82f6; text-decoration: none; font-weight: 500; transition: all 0.3s ease; position: relative; }
  .forgot-link::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 0; height: 1.5px; background: #2563eb; border-radius: 2px; transition: width 0.3s ease; }
  .forgot-link:hover { color: #1e40af; }
  .forgot-link:hover::after { width: 100%; }

  /* LOGIN BUTTON */
  .btn-login-submit {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    width: 100%; padding: 17px 32px; margin-top: 10px;
    font-family: 'Poppins','Segoe UI',Arial,sans-serif;
    font-size: 15.5px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
    color: #2563eb; background: #ffffff; border: 2px solid #2563eb;
    border-radius: 14px; cursor: pointer;
    position: relative; overflow: hidden;
    transition: all 0.5s cubic-bezier(0.25,0.8,0.25,1);
    box-shadow: 0 4px 16px rgba(37,99,235,0.1); z-index: 1;
    animation: btnReveal 0.5s 0.85s cubic-bezier(0.16,1,0.3,1) backwards;
  }
  @keyframes btnReveal { from { opacity: 0; transform: translateY(15px) scale(0.95); } }
  .btn-login-submit .icon { transition: transform 0.4s ease; }
  .btn-login-submit .icon svg { width: 18px; height: 18px; stroke-width: 2.5; }
  .btn-login-submit::before { content: ''; position: absolute; top: 0; left: 0; width: 0; height: 100%; background: linear-gradient(135deg,#1e40af,#2563eb,#3b82f6); z-index: -2; transition: width 0.5s cubic-bezier(0.25,0.8,0.25,1); border-radius: 12px; }
  .btn-login-submit::after { content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%; background: linear-gradient(90deg,transparent,rgba(255,255,255,0.25),transparent); z-index: -1; transition: left 0.6s ease; }
  .btn-login-submit:hover { color: #ffffff; border-color: #1e40af; transform: translateY(-3px); box-shadow: 0 12px 40px rgba(37,99,235,0.3), 0 4px 14px rgba(37,99,235,0.15); }
  .btn-login-submit:hover::before { width: 100%; }
  .btn-login-submit:hover::after { left: 120%; }
  .btn-login-submit:hover .icon { transform: translateX(-3px) rotate(-8deg) scale(1.15); color: #ffffff; }
  .btn-login-submit:active { transform: translateY(-1px) scale(0.98); }

  /* OR divider */
  .or-divider { display: flex; align-items: center; gap: 14px; margin: 22px 0; animation: fieldUp 0.5s 0.9s cubic-bezier(0.16,1,0.3,1) backwards; }
  .or-line { flex: 1; height: 1px; background: linear-gradient(90deg,transparent,#e2e8f0,transparent); }
  .or-text { font-size: 12px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 1.5px; }

  /* Register */
  .register-section { text-align: center; animation: fadeUp 0.5s 1.0s backwards; }
  .register-section p { font-size: 14px; color: #94a3b8; margin-bottom: 14px; }
  .btn-register { display: inline-flex; align-items: center; gap: 8px; padding: 12px 30px; font-family: 'Poppins','Segoe UI',Arial,sans-serif; font-size: 13.5px; font-weight: 600; color: #2563eb; background: #ffffff; border: 2px solid #dbeafe; border-radius: 12px; text-decoration: none; transition: all 0.4s cubic-bezier(0.25,0.8,0.25,1); position: relative; overflow: hidden; }
  .btn-register::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg,#2563eb,#3b82f6); opacity: 0; transition: opacity 0.4s ease; z-index: 0; border-radius: 10px; }
  .btn-register span, .btn-register .icon { position: relative; z-index: 1; }
  .btn-register:hover { color: #ffffff; border-color: #2563eb; transform: translateY(-2px); box-shadow: 0 6px 22px rgba(37,99,235,0.25); }
  .btn-register:hover::before { opacity: 1; }
  .btn-register:hover .icon { transform: translateX(3px); color: #ffffff; }

  /* Secure note */
  .secure-note { text-align: center; margin-top: 24px; font-size: 11px; color: #94a3b8; display: flex; align-items: center; justify-content: center; gap: 6px; animation: fadeUp 0.5s 1.1s backwards; }
  .secure-note strong { color: #2563eb; font-weight: 700; }
  @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } }

  /* Right deco */
  .right-deco { position: absolute; width: 300px; height: 300px; border-radius: 50%; background: radial-gradient(circle,rgba(59,130,246,0.04),transparent 70%); bottom: -100px; right: -100px; z-index: 0; animation: df 12s ease-in-out infinite; }
  .right-deco-2 { position: absolute; width: 200px; height: 200px; border-radius: 50%; background: radial-gradient(circle,rgba(59,130,246,0.03),transparent 70%); top: -80px; left: -60px; z-index: 0; animation: df 10s ease-in-out infinite reverse; }

  /* Error */
  .login-error { display: none; padding: 11px 14px; margin: 4px 0 8px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; border-radius: 10px; font-size: 13px; font-weight: 600; }
  .login-error.show { display: block; }

  /* Register Now button */
  .overlay-btn { position: fixed; bottom: 20px; right: 20px; background: #ff4d6d; color: white; border: none; border-radius: 50px; padding: 14px 20px; font-size: 16px; cursor: pointer; box-shadow: 0 8px 20px rgba(0,0,0,0.25); transition: all 0.3s ease; z-index: 1000; font-family: 'Poppins',sans-serif; }
  .overlay-btn:hover { background: #e63956; transform: scale(1.1); }

  /* Responsive */
  @media screen and (max-width: 900px) {
    .inner-container { flex-direction: column; }
    .left-panel { width: 100%; min-width: unset; padding: 36px 30px 28px; }
    .teacher-section { display: none; }
    .right-panel { border-radius: 0 0 24px 24px; padding: 36px 34px 30px; }
  }
  @media screen and (max-width: 640px) {
    body { padding: 12px; }
    .outer-container { padding: 10px; border-radius: 22px; }
    .inner-container { border-radius: 18px; }
    .left-panel { display: none; }
    .mobile-logo { display: flex; }
    .right-panel { border-radius: 18px; padding: 32px 24px 28px; }
    .form-header h2 { font-size: 24px; }
    input[type="email"], input[type="password"] { padding: 14px 16px; font-size: 14px; border-radius: 12px; }
    .btn-login-submit { padding: 15px 24px; font-size: 14px; border-radius: 12px; }
  }

  /* Password toggle */
  .pw-wrap { position: relative; }
  .pw-toggle { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px; display: flex; align-items: center; transition: color 0.2s; }
  .pw-toggle:hover { color: #3b82f6; }
  .pw-toggle svg { width: 18px; height: 18px; }
  .eye-closed { display: none; }
  .show-pass .eye-open { display: none; }
  .show-pass .eye-closed { display: block; }
</style>
</head>
<body>

<button class="overlay-btn" onclick="window.location.href='register.php'">Register Now</button>

<div class="outer-container">
  <div class="inner-container">

    <!-- LEFT PANEL -->
    <div class="left-panel">
      <div class="left-content">

        <div class="logo-area">
          <div class="logo-icon-box">
            <img src="assets/css/internboot-official-logo.webp" alt="logo" style="width:52px;height:52px;object-fit:contain;">
          </div>
          <h1 class="logo-text">Intern<span>Boot</span></h1>
        </div>

        <p class="left-subtitle">Your Gateway to Professional Growth</p>

        <div class="welcome-box">
          <h3>Welcome Back! 👋</h3>
          <p>Give assessments, clear levels, unlock placement opportunities, and download your verified certificates — all in one place.</p>
        </div>

        <div class="features-mini">
          <div class="feat">
            <div class="feat-dot">
              <span class="icon"><svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
            </div>
            <span>Take the Level Assessment Test</span>
          </div>
          <div class="feat">
            <div class="feat-dot">
              <span class="icon"><svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
            </div>
            <span>Clear Levels 1 to 5 and unlock placement</span>
          </div>
          <div class="feat">
            <div class="feat-dot">
              <span class="icon"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>
            </div>
            <span>Download verified certificates anytime</span>
          </div>
        </div>

        <!-- Illustration -->
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
          <div class="speech-bubble">Login Now! <span class="speech-arrow">→</span></div>
        </div>

      </div>

      <div class="deco d1"></div><div class="deco d2"></div>
      <div class="deco d3"></div><div class="deco d4"></div><div class="deco d5"></div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right-panel">
      <div class="right-deco"></div>
      <div class="right-deco-2"></div>

      <div class="mobile-logo">
        <img src="assets/css/internboot-official-logo.webp" alt="InternBoot" style="height:44px;">
      </div>

      <div class="form-header" style="position:relative;z-index:1;">
        <div class="greeting"><span class="wave">👋</span> Welcome back</div>
        <h2>Student Login</h2>
        <p>Enter your credentials to access your dashboard</p>
      </div>

      <form id="loginForm" novalidate class="login-form" style="position:relative;z-index:1;">

        <div class="form-group">
          <label>
            <span class="icon"><svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
            Email Address
          </label>
          <input type="email" id="email" name="email" placeholder="Enter your email address" autocomplete="email" required>
        </div>

        <div class="form-group">
          <label>
            <span class="icon"><svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
            Password
          </label>
          <div class="pw-wrap">
            <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
            <button type="button" class="pw-toggle" id="pwToggle" aria-label="Toggle password">
              <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </div>

        <div class="forgot-row">
          <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
        </div>

        <div id="formAlert" class="login-error"></div>

        <button type="submit" class="btn-login-submit" id="loginBtn">
          <span class="icon"><svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg></span>
          <span>Login to Dashboard</span>
        </button>

      </form>

      <div class="or-divider" style="position:relative;z-index:1;">
        <span class="or-line"></span><span class="or-text">or</span><span class="or-line"></span>
      </div>

      <div class="register-section" style="position:relative;z-index:1;">
        <p>Don't have an account?</p>
        <a href="register.php" class="btn-register">
          <span class="icon"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg></span>
          <span>Register Here</span>
        </a>
      </div>

      <div class="secure-note" style="position:relative;z-index:1;">
        <span class="icon" style="color:#10b981;"><svg viewBox="0 0 24 24" style="stroke:#10b981"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
        256-bit SSL Encrypted · <strong>InternBoot</strong>
      </div>

    </div>

  </div>
</div>

<script src="assets/js/auth.js"></script>
<script>
  // Password toggle
  const pwToggle = document.getElementById('pwToggle');
  const pwInput = document.getElementById('password');
  if (pwToggle && pwInput) {
    pwToggle.addEventListener('click', () => {
      const isPass = pwInput.type === 'password';
      pwInput.type = isPass ? 'text' : 'password';
      pwToggle.classList.toggle('show-pass', isPass);
    });
  }

  // Show error if ?error=invalid
  const params = new URLSearchParams(window.location.search);
  const errorBox = document.getElementById('formAlert');
  if (params.get('error') === 'invalid' && errorBox) {
    errorBox.textContent = 'Invalid email or password. Please try again.';
    errorBox.classList.add('show');
  }
</script>
</body>
</html>
