/**
 * admin-init.js
 * Shared bootstrap for all admin pages.
 * - Loads admin profile from /api/admin/me.php and populates the sidebar
 * - Wires the sidebar logout button with CSRF-aware POST to /api/auth/logout.php
 * - Handles mobile menu toggle
 */
(function () {
  'use strict';

  // ── Load admin info ────────────────────────────────────────────────────────
  async function loadAdminInfo() {
    try {
      const res = await fetch('/api/admin/me.php', { credentials: 'same-origin' });
      if (!res.ok) {
        if (res.status === 401 || res.status === 403) {
          window.location.href = '/login.php';
        }
        return;
      }
      const payload = await res.json();
      if (payload.status !== 'success') return;

      const { full_name, email, role, initials } = payload.data;

      // Sidebar name, email subtitle, role, initials
      const nameEl     = document.getElementById('sidebar-admin-name');
      const roleEl     = document.getElementById('sidebar-admin-role');
      const initialsEl = document.getElementById('sidebar-admin-initials');

      if (nameEl) nameEl.textContent = full_name || 'Admin';
      if (roleEl) roleEl.textContent = email || (role ? (role.charAt(0).toUpperCase() + role.slice(1)) : 'Admin');
      if (initialsEl) initialsEl.textContent = initials || 'A';

    } catch (e) {
      console.error('Failed to load admin info:', e);
    }
  }

  // ── Logout ─────────────────────────────────────────────────────────────────
  async function doLogout() {
    try {
      // Get CSRF token first
      const csrfRes = await fetch('/api/admin/evaluate.php?action=csrf', {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' }
      });
      const csrfPayload = await csrfRes.json();
      const token = csrfPayload?.data?.token || '';

      const res = await fetch('/api/auth/logout.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': token
        }
      });
      const payload = await res.json();
      window.location.href = payload?.data?.redirect || '/login.php';
    } catch (e) {
      // Fallback: just redirect to login
      window.location.href = '/login.php';
    }
  }

  // ── Mobile menu ────────────────────────────────────────────────────────────
  function initMobileMenu() {
    const menuBtn = document.getElementById('menuButton');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (!menuBtn || !sidebar) return;

    menuBtn.addEventListener('click', () => {
      sidebar.classList.toggle('-translate-x-full');
      if (overlay) overlay.classList.toggle('hidden');
    });

    if (overlay) {
      overlay.addEventListener('click', () => {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
      });
    }
  }

  // ── Bootstrap ──────────────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    loadAdminInfo();
    initMobileMenu();

    const logoutBtn = document.getElementById('sidebar-logout-btn');
    if (logoutBtn) {
      logoutBtn.addEventListener('click', doLogout);
    }
  });
})();
