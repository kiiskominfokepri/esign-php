# CodeIgniter 3 Integration Guide

This guide explains how to install and use `kiiskominfokepri/esign-php` as a third-party library inside a **CodeIgniter 3** application.

## Requirements recap

- CodeIgniter 3.1.x (PHP 5.6+ recommended — works fine on PHP 7.2 / 8.x)
- Composer installed globally or locally
- This package does NOT conflict with any CI3 bundled library (CI3 has no bundled HTTP client)

## Step 1 — Decide where `vendor/` lives

CodeIgniter 3 expects Composer's `vendor/autoload.php` at `application/vendor/autoload.php` by default, but the Composer community standard is `vendor/` at the project root. Both work; pick one and configure `composer_autoload` accordingly.

| Layout | `composer.json` location | `vendor/` location | `composer_autoload` value |
|--------|--------------------------|--------------------|--------------------------|
| **Root (recommended)** | `<project>/composer.json` | `<project>/vendor/` | `FCPATH . 'vendor/autoload.php'` |
| Application | `application/composer.json` | `application/vendor/` | `TRUE` (or `APPPATH . 'vendor/autoload.php'`) |

> Note: the literal value `TRUE` looks for `application/vendor/autoload.php`. On some setups it does not resolve reliably — see [bcit-ci/CodeIgniter#4197](https://github.com/bcit-ci/CodeIgniter/issues/4197). Using an absolute path is the safest option.

## Step 2 — Configure `composer_autoload`

Open `application/config/config.php` and set:

```php
// Root layout
$config['composer_autoload'] = FCPATH . 'vendor/autoload.php';

// OR application layout
$config['composer_autoload'] = APPATH . 'vendor/autoload.php';
```

## Step 3 — Add the package via VCS repository

Because the package is not yet on Packagist, you must declare a VCS repository.

### Root layout

Create or edit `<project>/composer.json`:

```json
{
    "require": {
        "kiiskominfokepri/esign-php": "^1.0.3"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/kiiskominfokepri/esign-php"
        }
    ]
}
```

### Application layout

Create or edit `application/composer.json`:

```json
{
    "require": {
        "kiiskominfokepri/esign-php": "^1.0.3"
    },
    "config": {
        "vendor-dir": "application/vendor"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/kiiskominfokepri/esign-php"
        }
    ]
}
```

## Step 4 — Install

From the directory that contains `composer.json`:

```bash
composer install
# or, if already installed elsewhere
composer require kiiskominfokepri/esign-php:^1.0.3
```

## Step 5 — Configure esign credentials

Add to `application/config/config.php` (or a custom config file loaded via `$this->config->load()`):

```php
$config['esign_base_url']  = getenv('ESIGN_BASE_URL')  ?: 'https://esign.example.go.id';
$config['esign_username']  = getenv('ESIGN_USERNAME')  ?: '';
$config['esign_password']  = getenv('ESIGN_PASSWORD')  ?: '';
$config['esign_nik']       = getenv('ESIGN_NIK')       ?: '';
$config['esign_email']     = getenv('ESIGN_EMAIL')      ?: '';
$config['esign_passphrase']= getenv('ESIGN_PASSPHRASE')?: '';
```

## Step 6 — Use it in a controller

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use KiisKepri\Esign\EsignFactory;

class Esign extends CI_Controller
{
    public function sign_invisible()
    {
        $esign = EsignFactory::v2(
            $this->config->item('esign_base_url'),
            $this->config->item('esign_username'),
            $this->config->item('esign_password')
        )
            ->setNIK($this->config->item('esign_nik'))
            ->setEmail($this->config->item('esign_email'));

        $filePath = FCPATH . 'uploads/test.pdf';
        $savePath = FCPATH . 'uploads/signed/test-signed.pdf';

        $response = $esign->signInvisible(
            $this->config->item('esign_passphrase'),
            $filePath
        );

        if ($response->isSuccess()) {
            $response->saveToFile($savePath);
            echo 'Signed OK: ' . $savePath . PHP_EOL;
            echo 'Timestamp: ' . ($response->getTimestamp() ?? '-') . PHP_EOL;
            return;
        }

        log_message('error', 'esign: ' . json_encode($response->getErrors()));
        show_error('Esign failed', 500);
    }
}
```

## Notes

- **PHP version:** the package requires `^7.2.5 || ^8.0`. Make sure your CI3 environment runs PHP 7.2 or newer — CI3 itself supports this range fine.
- **No ServiceProvider needed:** CI3 does not use a dependency container; the `EsignFactory` static factories are all you need.
- **Case sensitivity:** `KiisKepri\Esign\` namespace is case-sensitive on Linux servers. Match the casing exactly in `use` statements.
- **Worker / CLI:** the package is stateless on a per-call basis, so it is safe under CI3's CLI runner and long-running processes.
- **Dependencies:** Guzzle 7 and `symfony/polyfill-php80` are pulled in automatically. They have no conflicts with CI3's bundled libraries.

## Verification checklist

- [ ] `$config['composer_autoload']` set to the correct path
- [ ] `composer install` succeeded
- [ ] `vendor/kiiskominfokepri/esign-php/src/EsignFactory.php` exists
- [ ] `class_exists('KiisKepri\Esign\EsignFactory')` returns `true` in a controller
- [ ] A sample `EsignFactory::v2(...)` call reaches the BSrE API endpoint

## References

- CodeIgniter 3 Server Requirements: https://codeigniter.com/userguide3/general/requirements.html
- CodeIgniter 3 Auto-loading Resources (Composer): https://codeigniter.com/userguide3/general/autoloader.html
- `composer_autoload` path resolution issue: https://github.com/bcit-ci/CodeIgniter/issues/4197
