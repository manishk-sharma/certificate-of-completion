<?php
require_once __DIR__ . '/certificate_fetcher.php';

// Base certificate defaults
$defaultCertificate = [
    'recipient'       => 'Full Name',
    'program'         => 'Data Analytics with Gen AI & Agentic AI',
    'dates'           => 'xx/xx/xxxx to xx/xx/xxxx',
    'certificationId' => 'xxxxxxx',
    'issueDate'       => 'Issue Date',
    'signatory'       => 'Kaushlendra Singh Sisodia',
    'signatoryTitle'  => 'Chief Mentor, The IoT Academy',
    'signatoryRole'   => 'Director, Uniconverge Technologies',
];

// 1. If an ID is passed via GET or REQUEST, look it up from Sheet / CSV / Database
$targetId = trim($_REQUEST['id'] ?? $_REQUEST['certificationId'] ?? '');
if (!empty($targetId)) {
    $fetched = getCertificateFromSheet($sheetCsvUrl, $targetId);
    if ($fetched) {
        $defaultCertificate = array_merge($defaultCertificate, $fetched);
    }
}

// 2. Allow direct query parameter overrides (e.g. from Dashboard or custom URL parameters)
$certificate = array_merge($defaultCertificate, array_intersect_key($_REQUEST, $defaultCertificate));

// Security helper
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

// Base URL and Verification Target for QR Code
$baseUrl = getBaseUrl();
$verificationUrl = $baseUrl . '/verify.php?id=' . urlencode($certificate['certificationId']);
$qrCodeApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($verificationUrl);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CERTIFICATE OF COMPLETION <?= e($certificate['recipient']) ?></title>
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <link rel="stylesheet" href="certificate.css">
</head>
<body>

  <!-- Floating Control Toolbar linking all files -->
  <nav class="toolbar" id="mainToolbar" aria-label="Certificate Actions">
    <a href="dashboard.php" class="btn btn-secondary" title="Return to Certificate Management Dashboard">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
      <span class="btn-text">Dashboard</span>
    </a>

    <a href="<?= e($verificationUrl) ?>" target="_blank" class="btn btn-secondary" id="verifyToolbarBtn" title="Verify authenticity on verify.php">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
      <span class="btn-text">Verify</span>
    </a>

    <button type="button" class="btn btn-icon active" id="fitToggleBtn" title="Toggle between Fit Screen and 100% Size" aria-label="Toggle Fit Screen">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path>
      </svg>
      <span class="btn-text">Fit Screen</span>
    </button>

    <button type="button" class="btn btn-icon" id="fullscreenBtn" title="Toggle Fullscreen (F)" aria-label="Toggle Fullscreen">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <polyline points="15 3 21 3 21 9"></polyline>
        <polyline points="9 21 3 21 3 15"></polyline>
        <line x1="21" y1="3" x2="14" y2="10"></line>
        <line x1="3" y1="21" x2="10" y2="14"></line>
      </svg>
      <span class="btn-text">Fullscreen</span>
    </button>

    <button type="button" class="btn btn-primary" id="toggleEditBtn" title="Edit Certificate Details (E)" aria-label="Edit Certificate">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
      </svg>
      <span class="btn-text">Edit</span>
    </button>

    <button type="button" class="btn btn-secondary" id="printBtn" onclick="window.print()" title="Print or Save as PDF (Ctrl+P)" aria-label="Print Certificate">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <polyline points="6 9 6 2 18 2 18 9"></polyline>
        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
        <rect x="6" y="14" width="12" height="8"></rect>
      </svg>
      <span class="btn-text">Print</span>
    </button>
  </nav>

  <!-- Live Editor Drawer -->
  <div class="backdrop" id="backdrop"></div>
  <aside class="editor-drawer <?= !empty($_GET['openEditor']) ? 'open' : '' ?>" id="editorDrawer" aria-label="Certificate Editor">
    <div class="editor-header">
      <h2>Edit Certificate</h2>
      <button type="button" class="close-btn" id="closeDrawerBtn" aria-label="Close drawer">&times;</button>
    </div>
    <form class="editor-form" method="POST" action="">
      <div class="form-group">
        <label for="input-recipient">Recipient Name</label>
        <input type="text" id="input-recipient" name="recipient" value="<?= e($certificate['recipient']) ?>" required>
      </div>

      <div class="form-group">
        <label for="input-program">Program Title</label>
        <input type="text" id="input-program" name="program" value="<?= e($certificate['program']) ?>" required>
      </div>

      <div class="form-group">
        <label for="input-dates">Dates (From - To)</label>
        <input type="text" id="input-dates" name="dates" value="<?= e($certificate['dates']) ?>" required>
      </div>

      <div class="form-group">
        <label for="input-certificationId">Certification ID</label>
        <input type="text" id="input-certificationId" name="certificationId" value="<?= e($certificate['certificationId']) ?>" required>
      </div>

      <div class="form-group">
        <label for="input-issueDate">Issue Date</label>
        <input type="text" id="input-issueDate" name="issueDate" value="<?= e($certificate['issueDate']) ?>" required>
      </div>

      <div class="form-group">
        <label for="input-signatory">Signatory Name</label>
        <input type="text" id="input-signatory" name="signatory" value="<?= e($certificate['signatory']) ?>" required>
      </div>

      <div class="form-group">
        <label for="input-signatoryTitle">Signatory Title</label>
        <input type="text" id="input-signatoryTitle" name="signatoryTitle" value="<?= e($certificate['signatoryTitle']) ?>" required>
      </div>

      <div class="form-group">
        <label for="input-signatoryRole">Signatory Role / Organization</label>
        <input type="text" id="input-signatoryRole" name="signatoryRole" value="<?= e($certificate['signatoryRole']) ?>" required>
      </div>

      <div style="display: flex; gap: 10px; margin-top: 10px;">
        <button type="submit" class="btn btn-primary" style="flex: 1;">Save & Apply</button>
        <button type="button" class="btn btn-secondary" id="resetBtn">Reset</button>
      </div>

      <div style="margin-top: 10px;">
        <a href="dashboard.php" class="btn btn-secondary" style="display: flex; justify-content: center;">
          &larr; Back to Dashboard
        </a>
      </div>
    </form>
  </aside>

  <!-- Certificate Canvas -->
  <div class="page-shell">
    <article class="certificate" aria-label="CERTIFICATE OF COMPLETION">
      <div class="paper">
        <div class="wave-pattern" aria-hidden="true"></div>
        <div class="watermark" aria-hidden="true">
          <span>The IoT</span>
          <strong>Academy</strong>
        </div>

        <!-- Brand Header -->
        <header class="brand-header" aria-label="Certificate partners">
          <img class="brand brand-iot" src="assets/iot-academy.png" alt="The IoT Academy">
          <img class="brand brand-uct" src="assets/uct-nasscom.png" alt="UCT, member of NASSCOM">
          <img class="brand brand-upskill" src="assets/upskill-campus.png" alt="upSkill Campus">
        </header>

        <!-- Certificate Body -->
        <main class="certificate-body">
          <h1>CERTIFICATE OF COMPLETION</h1>
          <div class="title-rule"></div>
          <p class="certify-copy">This is to certify that Mr./Ms.</p>
          <p class="recipient" id="display-recipient"><?= e($certificate['recipient']) ?></p>
          <p class="completion-copy">has successfully completed the online</p>
          <p class="program" id="display-program"><?= e($certificate['program']) ?></p>
          <p class="dates" id="display-dates">held from <?= e($certificate['dates']) ?></p>
          <p class="description">
            Through dedication and hard work, he/she has demonstrated a
            comprehensive understanding and proficiency in achieving the program
            objectives.
            <br>
            By completing this program, he/she has exhibited commendable
            performance.
            <br>
            We wish him/her success throughout his/her career.
          </p>
          <p class="certificate-id">
            Certification ID: <span id="display-certificationId"><?= e($certificate['certificationId']) ?></span>
          </p>
        </main>

        <!-- Certificate Footer -->
        <footer class="certificate-footer">
          <div class="footer-block issue-block">
            <div class="signature-line"></div>
            <strong id="display-issueDate"><?= e($certificate['issueDate']) ?></strong>
          </div>

          <!-- Dynamic Verification QR Code (Clickable) -->
          <a href="<?= e($verificationUrl) ?>" target="_blank" title="Scan or click to verify authenticity on verify.php" class="qr-link" style="display: block; justify-self: center;">
            <img class="qr-code" id="display-qr" src="<?= e($qrCodeApiUrl) ?>" alt="Verification QR Code">
          </a>

          <div class="footer-block signatory-block">
            <div class="signature-line"></div>
            <strong id="display-signatory"><?= e($certificate['signatory']) ?></strong>
            <span id="display-signatoryTitle"><?= e($certificate['signatoryTitle']) ?></span>
            <span id="display-signatoryRole"><?= e($certificate['signatoryRole']) ?></span>
          </div>
        </footer>
      </div>

      <!-- Frame Artwork (Borders & Decorative Corners) -->
      <svg class="frame-artwork" viewBox="0 0 1578 1118" preserveAspectRatio="none" aria-hidden="true">
        <defs>
          <linearGradient id="gold-left" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#f9d75d" />
            <stop offset=".55" stop-color="#e3a62d" />
            <stop offset="1" stop-color="#b66e12" />
          </linearGradient>
          <linearGradient id="gold-right" x1="1" y1="0" x2="0" y2="0">
            <stop offset="0" stop-color="#f9d75d" />
            <stop offset=".55" stop-color="#e3a62d" />
            <stop offset="1" stop-color="#b66e12" />
          </linearGradient>
        </defs>

        <rect x="12" y="12" width="1554" height="1094" fill="none" stroke="#e4bd42" stroke-width="2.5" />
        <rect x="23" y="24" width="1532" height="1038" fill="none" stroke="#26306f" stroke-width="2" />

        <path d="M0 821 L84 979 L84 1055 L363 1055 L313 1029 L55 1029 L55 985 L0 879 Z" fill="url(#gold-left)" />
        <path d="M0 846 L56 985 L56 1056 L0 970 Z" fill="#2a3375" />
        <path d="M1578 821 L1495 979 L1495 1055 L1215 1055 L1265 1029 L1522 1029 L1522 985 L1578 879 Z" fill="url(#gold-right)" />
        <path d="M1578 846 L1522 985 L1522 1056 L1578 970 Z" fill="#2a3375" />
      </svg>
    </article>
  </div>

  <script>
    // Live editing & Drawer controls
    const toggleEditBtn = document.getElementById('toggleEditBtn');
    const closeDrawerBtn = document.getElementById('closeDrawerBtn');
    const drawer = document.getElementById('editorDrawer');
    const backdrop = document.getElementById('backdrop');

    function toggleDrawer(open) {
      if (!drawer || !backdrop) return;
      drawer.classList.toggle('open', open);
      backdrop.classList.toggle('open', open);
    }

    if (toggleEditBtn) toggleEditBtn.addEventListener('click', () => toggleDrawer(true));
    if (closeDrawerBtn) closeDrawerBtn.addEventListener('click', () => toggleDrawer(false));
    if (backdrop) backdrop.addEventListener('click', () => toggleDrawer(false));

    // If URL has ?openEditor=1, open drawer automatically
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('openEditor') === '1') {
      toggleDrawer(true);
    }

    // Responsive Fit Screen Scaling (uses pure px measurements)
    function updateFitScale() {
      const cert = document.querySelector('.certificate');
      if (!cert) return;
      if (document.body.classList.contains('actual-size')) {
        cert.style.transform = '';
        return;
      }
      const pad = 24;
      const availW = window.innerWidth - (pad * 2);
      const availH = window.innerHeight - (pad * 2);
      const scale = Math.min(availW / 1578, availH / 1118, 1);
      cert.style.transform = 'scale(' + scale + ')';
      cert.style.transformOrigin = 'center center';
    }

    window.addEventListener('resize', updateFitScale);
    window.addEventListener('DOMContentLoaded', updateFitScale);
    window.addEventListener('beforeprint', () => {
      const cert = document.querySelector('.certificate');
      if (cert) cert.style.transform = '';
    });
    window.addEventListener('afterprint', updateFitScale);
    updateFitScale();

    // Fit Screen / 100% Size toggle
    const fitToggleBtn = document.getElementById('fitToggleBtn');
    if (fitToggleBtn) {
      fitToggleBtn.addEventListener('click', () => {
        const isActual = document.body.classList.toggle('actual-size');
        const textSpan = fitToggleBtn.querySelector('.btn-text');
        fitToggleBtn.classList.toggle('active', !isActual);
        if (textSpan) {
          textSpan.textContent = isActual ? 'Fit Screen' : '100% Size';
        }
        fitToggleBtn.title = isActual ? 'Switch to Fit Screen view' : 'Switch to 100% Actual Size view';
        updateFitScale();
      });
    }

    // Fullscreen toggle
    const fullscreenBtn = document.getElementById('fullscreenBtn');
    if (fullscreenBtn) {
      fullscreenBtn.addEventListener('click', () => {
        if (!document.fullscreenElement) {
          document.documentElement.requestFullscreen().catch(() => {});
        } else {
          document.exitFullscreen().catch(() => {});
        }
      });
      document.addEventListener('fullscreenchange', () => {
        fullscreenBtn.classList.toggle('active', !!document.fullscreenElement);
        setTimeout(updateFitScale, 50);
      });
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
      if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
        if (e.key === 'Escape' && drawer && drawer.classList.contains('open')) {
          toggleDrawer(false);
        }
        return;
      }
      if (e.key === 'Escape') {
        toggleDrawer(false);
      } else if (e.key === 'e' || e.key === 'E') {
        e.preventDefault();
        toggleDrawer(true);
      } else if (e.key === 'f' || e.key === 'F') {
        e.preventDefault();
        if (fullscreenBtn) fullscreenBtn.click();
      }
    });

    // Base URL for dynamic QR generation
    const baseUrl = <?= json_encode($baseUrl) ?>;

    // Live sync inputs to certificate view + Live QR regeneration
    const fields = [
      { inputId: 'input-recipient', displayId: 'display-recipient' },
      { inputId: 'input-program', displayId: 'display-program' },
      { 
        inputId: 'input-dates', 
        displayId: 'display-dates',
        format: val => 'held from ' + val
      },
      { inputId: 'input-certificationId', displayId: 'display-certificationId' },
      { inputId: 'input-issueDate', displayId: 'display-issueDate' },
      { inputId: 'input-signatory', displayId: 'display-signatory' },
      { inputId: 'input-signatoryTitle', displayId: 'display-signatoryTitle' },
      { inputId: 'input-signatoryRole', displayId: 'display-signatoryRole' }
    ];

    fields.forEach(({ inputId, displayId, format }) => {
      const input = document.getElementById(inputId);
      const display = document.getElementById(displayId);
      if (input && display) {
        input.addEventListener('input', (e) => {
          const val = e.target.value;
          display.textContent = format ? format(val) : val;

          // Dynamically regenerate QR Code & verify link when Certification ID is edited
          if (inputId === 'input-certificationId') {
            const qrImg = document.getElementById('display-qr');
            const verifyToolbarBtn = document.getElementById('verifyToolbarBtn');
            const dynamicVerifyUrl = baseUrl + '/verify.php?id=' + encodeURIComponent(val);
            if (qrImg) {
              qrImg.src = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(dynamicVerifyUrl);
              const qrAnchor = qrImg.closest('a');
              if (qrAnchor) qrAnchor.href = dynamicVerifyUrl;
            }
            if (verifyToolbarBtn) {
              verifyToolbarBtn.href = dynamicVerifyUrl;
            }
          }
        });
      }
    });

    // Reset button
    const defaultData = <?= json_encode($defaultCertificate) ?>;
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
      resetBtn.addEventListener('click', () => {
        Object.keys(defaultData).forEach(key => {
          const input = document.getElementById('input-' + key);
          const display = document.getElementById('display-' + key);
          if (input) input.value = defaultData[key];
          if (display) {
            display.textContent = key === 'dates' ? ('held from ' + defaultData[key]) : defaultData[key];
          }
        });

        // Reset QR
        const qrImg = document.getElementById('display-qr');
        const defaultVerifyUrl = baseUrl + '/verify.php?id=' + encodeURIComponent(defaultData.certificationId);
        if (qrImg) {
          qrImg.src = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(defaultVerifyUrl);
          const qrAnchor = qrImg.closest('a');
          if (qrAnchor) qrAnchor.href = defaultVerifyUrl;
        }
      });
    }
  </script>
</body>
</html>