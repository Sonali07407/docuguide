<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

require_admin('login.php');

$cat_check = $conn->query("SELECT COUNT(*) AS total FROM categories")->fetch_assoc();
if ($cat_check && intval($cat_check['total']) === 0) {
    $conn->query("INSERT INTO categories (category_name) VALUES ('Certificates'), ('Identity Cards'), ('General')");
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $doc_name = trim($_POST['doc_name'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $overview = trim($_POST['overview'] ?? '');
    $eligibility = trim($_POST['eligibility'] ?? '');
    $processing_time = trim($_POST['processing_time'] ?? '');
    $application_fees = trim($_POST['application_fees'] ?? 'Check Official Portal');
    $official_portal_url = trim($_POST['official_portal_url'] ?? '');

    if ($application_fees === '') {
        $application_fees = 'Check Official Portal';
    }

    if ($official_portal_url !== '' && !preg_match("~^https?://~i", $official_portal_url)) {
        $official_portal_url = "https://" . $official_portal_url;
    }

    $stmt = $conn->prepare("INSERT INTO documents (category_id, doc_name, overview, eligibility, processing_time, application_fees, official_portal_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        $error = "Database error: " . $conn->error;
    } else {
        $stmt->bind_param("issssss", $category_id, $doc_name, $overview, $eligibility, $processing_time, $application_fees, $official_portal_url);
        if ($stmt->execute()) {
            $new_doc_id = intval($conn->insert_id);

            if (!empty($_POST['proof_name']) && is_array($_POST['proof_name'])) {
                $req_stmt = $conn->prepare("INSERT INTO document_requirements (doc_id, proof_name, proof_type) VALUES (?, ?, ?)");
                foreach ($_POST['proof_name'] as $index => $p_name) {
                    $p_name = trim($p_name);
                    $p_type = $_POST['proof_type'][$index] ?? 'Mandatory';
                    if ($p_type !== 'Mandatory' && $p_type !== 'Optional') {
                        $p_type = 'Mandatory';
                    }
                    if ($p_name !== '' && $req_stmt) {
                        $req_stmt->bind_param("iss", $new_doc_id, $p_name, $p_type);
                        $req_stmt->execute();
                    }
                }
            }

            if (!empty($_POST['step_instruction']) && is_array($_POST['step_instruction'])) {
                $step_stmt = $conn->prepare("INSERT INTO application_steps (doc_id, step_number, instruction) VALUES (?, ?, ?)");
                $step_num = 1;
                foreach ($_POST['step_instruction'] as $inst) {
                    $inst = trim($inst);
                    if ($inst !== '' && $step_stmt) {
                        $step_stmt->bind_param("iis", $new_doc_id, $step_num, $inst);
                        $step_stmt->execute();
                        $step_num++;
                    }
                }
            }

            header("Location: dashboard.php?msg=added");
            exit;
        } else {
            $error = "Could not save document: " . $stmt->error;
        }
    }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Document - DocuGuide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">DocuGuide Admin</a>
        <a href="dashboard.php" class="btn btn-light btn-sm">← Back to Dashboard</a>
    </div>
</nav>

<div class="container bg-white p-4 rounded shadow-sm mb-5" style="max-width: 720px;">
    <h4 class="mb-3">Add New Document Guide</h4>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <?php echo csrf_field(); ?>

        <div class="mb-3">
            <label class="form-label fw-bold">Document Name</label>
            <input type="text" name="doc_name" class="form-control" placeholder="e.g. Income Certificate" required>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label fw-bold">Category</label>
                <select name="category_id" class="form-select" required>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo intval($cat['category_id']); ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Processing Time</label>
                <input type="text" name="processing_time" class="form-control" placeholder="e.g. 7-10 Working Days" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Application Fees</label>
                <input type="text" name="application_fees" class="form-control" placeholder="e.g. ₹30 or Free" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Overview</label>
            <textarea name="overview" class="form-control" rows="2" placeholder="Short description of what this document is for..." required></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Eligibility Criteria</label>
            <textarea name="eligibility" class="form-control" rows="2" placeholder="Who can apply for this document..." required></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Official Portal URL</label>
            <input type="text" name="official_portal_url" class="form-control" placeholder="e.g. https://aaplesarkar.mahaonline.gov.in/" required>
        </div>

        <hr>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label fw-bold mb-0">Required Proofs Checklist</label>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addProofRow()">+ Add More Proof</button>
        </div>

        <div id="proof-list">
            <div class="row g-2 mb-2">
                <div class="col-8">
                    <input type="text" name="proof_name[]" class="form-control" placeholder="e.g. Aadhaar Card" required>
                </div>
                <div class="col-4">
                    <select name="proof_type[]" class="form-select">
                        <option value="Mandatory">Mandatory</option>
                        <option value="Optional">Optional</option>
                    </select>
                </div>
            </div>
        </div>

        <hr>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label fw-bold mb-0">Application Steps</label>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addStepRow()">+ Add Step</button>
        </div>

        <div id="step-list">
            <div class="mb-2">
                <input type="text" name="step_instruction[]" class="form-control" placeholder="Step 1: Visit the official portal and register/login..." required>
            </div>
        </div>

        <button type="submit" class="btn btn-warning fw-bold w-100 mt-3 py-2">Publish Document Guide</button>
    </form>
</div>

<script>
function addProofRow() {
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2';
    row.innerHTML = `
        <div class="col-8">
            <input type="text" name="proof_name[]" class="form-control" placeholder="Another required proof...">
        </div>
        <div class="col-4">
            <select name="proof_type[]" class="form-select">
                <option value="Mandatory">Mandatory</option>
                <option value="Optional">Optional</option>
            </select>
        </div>`;
    document.getElementById('proof-list').appendChild(row);
}

function addStepRow() {
    const stepCount = document.querySelectorAll('#step-list input[name="step_instruction[]"]').length + 1;
    const div = document.createElement('div');
    div.className = 'mb-2';
    div.innerHTML = `<input type="text" name="step_instruction[]" class="form-control" placeholder="Step ${stepCount}: Enter next step...">`;
    document.getElementById('step-list').appendChild(div);
}
</script>

</body>
</html>