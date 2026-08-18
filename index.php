<?php
die('INDEX.PHP LOADED - ' . __FILE__);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Email Sender</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

  <style>
    /* Styling "Do You Want To View Emails?" pop up */
    .md-dialog {
      display: none;
      position: fixed;
      z-index: 1050;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      align-items: center;
      justify-content: center;
      transition: opacity 0.3s ease;
    }

    .md-dialog.show {
      display: flex;
    }

    .md-dialog-content {
      background: #fff;
      border-radius: 12px;
      padding: 2rem;
      width: 100%;
      max-width: 450px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
      text-align: center;
      animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
      from {
        transform: translateY(-20px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .md-dialog h5 {
      font-weight: 600;
      margin-bottom: 1rem;
      color: #333;
    }

    .md-dialog-buttons {
      margin-top: 1.5rem;
      display: flex;
      justify-content: center;
      gap: 1rem;
    }

    .md-btn {
      border: none;
      padding: 0.6rem 1.4rem;
      border-radius: 8px;
      font-weight: 500;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .md-btn-yes {
      background-color: #1976d2;
      color: white;
    }

    .md-btn-no {
      background-color: #f1f1f1;
      color: #333;
    }

    .md-btn-yes:hover {
      background-color: #0d47a1;
    }

    .md-btn-no:hover {
      background-color: #ddd;
    }
  </style>

  <script>
    let formSubmitEvent; //Stores event temporarily

    function handleFormSubmit(event) {
      event.preventDefault();
      formSubmitEvent = event;
      document.getElementById("materialDialog").classList.add("show");
    }

    async function proceedWithPreview() {
      document.getElementById("materialDialog").classList.remove("show");

      const subject = document.querySelector('[name="subject"]').value.trim();
      const message = document.querySelector('[name="message"]').value.trim();
      const ccEmail = document.querySelector('[name="cc_email"]').value.trim(); // ADDED: Get CC email from form
      const recipientsText = document.querySelector('[name="recipients_manual"]').value.trim();
      const fileInput = document.querySelector('[name="recipients"]');
      let recipients = [];

      // --- Handle manual entries ---
      if (recipientsText) {
        const lines = recipientsText.split("\n");
        lines.forEach(line => {
          const parts = line.split(",");
          if (parts.length >= 2) {
            recipients.push({ name: parts[0].trim(), email: parts[1].trim() });
          }
        });
      }

      // Handles uploaded CSV/XLSX file
      if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        const ext = file.name.split('.').pop().toLowerCase();

        if (ext === 'csv') {
          const text = await file.text();
          const rows = text.split(/\r?\n/);
          rows.forEach(row => {
            const parts = row.split(",");
            if (parts.length >= 2) {
              recipients.push({ name: parts[0].trim(), email: parts[1].trim() });
            }
          });
        } else if (ext === 'xlsx') {
          const data = await file.arrayBuffer();
          const workbook = XLSX.read(data, { type: 'array' });
          const sheet = workbook.Sheets[workbook.SheetNames[0]];
          const rows = XLSX.utils.sheet_to_json(sheet, { header: 1 });

          rows.forEach((row, i) => {
            if (i === 0 && /name/i.test(row[0])) return; // skip header
            if (row[0] && row[1]) {
              recipients.push({ name: row[0].trim(), email: row[1].trim() });
            }
          });
        }
      }

      // Build email preview
      let previewHTML = "";
      if (recipients.length === 0) {
        previewHTML = `<p><em>No recipients found (manual or uploaded).</em></p>`;
      } else {
        recipients.forEach((r, i) => {
          const greeting = makeGreeting(r.name);
          const body = `${greeting}\n\n${message}`;
          previewHTML += `
            <div class="border rounded p-3 mb-3">
              <h6><strong>Email #${i + 1}</strong></h6>
              <p><strong>To:</strong> ${r.name} &lt;${r.email}&gt;</p>
              ${ccEmail ? `<p><strong>CC:</strong> ${ccEmail}</p>` : ''} 
              <p><strong>Subject:</strong> ${subject}</p>
              <pre style="white-space: pre-wrap;">${body}</pre>
            </div>`;
        });
      }
      // ADDED: Display CC email in preview if provided (line above with ${ccEmail ? ...})

      document.getElementById('previewEmails').innerHTML = previewHTML;
      const modal = new bootstrap.Modal(document.getElementById('previewModal'));
      modal.show();
    }

    function skipPreview() {
      document.getElementById("materialDialog").classList.remove("show");
      document.getElementById("emailForm").submit();
    }

    function makeGreeting(fullName) {
      const parts = fullName.split(" ");
      if (parts.length >= 2) {
        const title = parts[0];
        const lastName = parts[parts.length - 1];
        if (/^(Dr\.?|Prof\.?|Mr\.?|Ms\.?|Mrs\.?)/i.test(title)) {
          return `Dear ${title} ${lastName},`;
        }
      }
      return `Dear ${fullName},`;
    }

    function confirmSend() {
      document.getElementById('emailForm').submit();
    }
  </script>
</head>

<body class="p-5 bg-light">
  <div class="container bg-white p-4 rounded shadow-sm">
    <h2 class="mb-4">Send Emails via ProtonMail SMTP</h2>

    <form id="emailForm" action="mailer2.php" method="POST" enctype="multipart/form-data" onsubmit="handleFormSubmit(event)">
      <div class="mb-3">
        <label class="form-label">Subject:</label>
        <input type="text" name="subject" class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Message:</label>
        <textarea name="message" class="form-control" rows="6" required></textarea>
      </div>

      <!-- ADDED: CC Email input field (entire div block below) -->
      <div class="mb-3">
        <label class="form-label">CC Email (optional):</label>
        <input type="email" name="cc_email" class="form-control" placeholder="email@example.com">
        <small class="text-muted">This email will be CC'd on all outgoing messages</small>
      </div>
      <!-- END ADDED CC Email input field -->

      <div class="mb-3">
        <label class="form-label">Recipients (CSV or XLSX file):</label>
        <input type="file" name="recipients" class="form-control" accept=".csv,.xlsx">
        <small class="text-muted">File must contain columns: Name, Email</small>
      </div>

      <div class="mb-3">
        <label class="form-label">Manual Recipients (one per line, Name,Email):</label>
        <textarea name="recipients_manual" class="form-control" rows="5" placeholder="John Doe,john@example.com&#10;Jane Smith,jane@example.com"></textarea>
        <small class="text-muted">Optional: enter recipients manually if not using a file.</small>
      </div>

      <button type="submit" class="btn btn-primary">Send Emails</button>
    </form>
  </div>

  <!-- Material Design Confirmation Dialog -->
  <div id="materialDialog" class="md-dialog">
    <div class="md-dialog-content">
      <h5>View Emails Before Sending?</h5>
      <div class="md-dialog-buttons">
        <button class="md-btn md-btn-no" onclick="skipPreview()">No</button>
        <button class="md-btn md-btn-yes" onclick="proceedWithPreview()">Yes</button>
      </div>
    </div>
  </div>

  <!-- Email Preview Modal -->
  <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Preview All Emails</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="previewEmails" style="max-height: 70vh; overflow-y: auto;">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel Execution</button>
          <button type="button" class="btn btn-success" onclick="confirmSend()">Looks Good, Send</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
