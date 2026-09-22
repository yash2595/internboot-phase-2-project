# Manual Verification for OTP_PEPPER Security Fix

This file contains the verified terminal output of tests run against the OTP_PEPPER enforcement mechanisms. PHP 8.3 CLI was installed locally to run these tests.

## 1. Production Without OTP_PEPPER
**Command:**
```powershell
$env:APP_ENV="production"
Remove-Item Env:\OTP_PEPPER -ErrorAction SilentlyContinue
& php scratch/test_otp.php 2>&1
```
**Output:**
```
FATAL: OTP_PEPPER must be set to a unique, non-default secret in production.
```
**Status:** PASS

## 2. Production With OTP_PEPPER
**Command:**
```powershell
$env:APP_ENV="production"
$env:OTP_PEPPER="secret_a"
& php scratch/test_otp.php 2>&1

$env:OTP_PEPPER="secret_b"
& php scratch/test_otp.php 2>&1
```
**Output:**
```
Output with secret_a: dc425046d8f61064b5252919edbe9f441dd86cdb24c629bc2219a2e1ed4e5a13
Output with secret_b: ccc3a5b584195ea5c280021d6fe544409a8b72e2158cd6e1a73eb289e8c370fb
```
**Status:** PASS (Hashes correctly differentiate based on pepper)

## 3. Development Fallback
**Command:**
```powershell
$env:APP_ENV="development"
Remove-Item Env:\OTP_PEPPER -ErrorAction SilentlyContinue
& php scratch/test_otp.php 2>&1
```
**Output:**
```
3f7ad874250c7bece74a27f3f0e52fcc2ab9564f93f8e8e7994af2904514a047
```
**Status:** PASS (Boots successfully and generates a fallback hash)

## 4. Staging / Unrecognized Environment (Deny-by-Default)
**Command:**
```powershell
$env:APP_ENV="staging"
Remove-Item Env:\OTP_PEPPER -ErrorAction SilentlyContinue
& php scratch/test_otp.php 2>&1
```
**Output:**
```
FATAL: OTP_PEPPER must be set to a unique, non-default secret in production.
```
**Status:** PASS (Deny-by-default behavior succeeds in rejecting unconfigured environments)
