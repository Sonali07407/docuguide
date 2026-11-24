<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

$doc_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $conn->prepare("SELECT d.*, c.category_name FROM documents d LEFT JOIN categories c ON d.category_id = c.category_id WHERE d.doc_id = ?");
$stmt->bind_param("i", $doc_id);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();

if (!$doc) {
    http_response_code(404);
    exit("Document not found. <a href='index.php'>Go back</a>");
}

$feedback_msg = "";
$feedback_err = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    verify_csrf();
    $user_name = trim($_POST['user_name'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($user_name !== '' && $message !== '') {
        $fb_stmt = $conn->prepare("INSERT INTO feedback (doc_id, user_name, message) VALUES (?, ?, ?)");
        $fb_stmt->bind_param("iss", $doc_id, $user_name, $message);
        if ($fb_stmt->execute()) {
            $feedback_msg = "Thanks! Your feedback has been submitted.";
        } else {
            $feedback_err = "Could not submit feedback right now.";
        }
    } else {
        $feedback_err = "Please enter your name and feedback message.";
    }
}

$req_stmt = $conn->prepare("SELECT * FROM document_requirements WHERE doc_id = ?");
$req_stmt->bind_param("i", $doc_id);
$req_stmt->execute();
$reqs = $req_stmt->get_result();

$step_stmt = $conn->prepare("SELECT * FROM application_steps WHERE doc_id = ? ORDER BY step_number ASC");
$step_stmt->bind_param("i", $doc_id);
$step_stmt->execute();
$steps = $step_stmt->get_result();

$faq_stmt = $conn->prepare("SELECT * FROM faqs WHERE doc_id = ? ORDER BY faq_id ASC");
$faq_stmt->bind_param("i", $doc_id);
$faq_stmt->execute();
$faqs = $faq_stmt->get_result();

$page_title = ($doc['doc_name'] ?? 'Document') . ' - DocuGuide';
$show_search = false;

require_once __DIR__ . '/includes/header.php';

$safe_url = '#';
if (!empty($doc['official_portal_url']) && preg_match('~^https?://~i', $doc['official_portal_url'])) {
    $safe_url = $doc['official_portal_url'];
}
?>

<style>
    .btn-print { background-color: #ff9f00; color: #fff; font-weight: 700; border: none; }
    .btn-print:hover { background-color: #e68a00; color: #fff; }
    .btn-apply { background-color: #fb641b; color: #fff; font-weight: 700; border: none; }
    .btn-apply:hover { background-color: #e0530f; color: #fff; }
    .fee-badge { background: #1976d2; color: #fff; font-size: 0.8rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; }
    .proof-row { border: 1px solid #e0e0e0; border-radius: 6px; padding: 12px 14px; margin-bottom: 10px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
    .proof-row:hover { background-color: #f8faff; }
</style>

<div class="container my-4">
    <div class="mb-3 no-print">
        <a href="index.php" class="btn btn-outline-primary btn-sm fw-bold">← Back to All Guides</a>
    </div>

    <div class="bg-white p-4 rounded-1 shadow-sm">
        <div class="row g-4">
            <div class="col-md-4 border-end">
                <div class="p-4 text-center bg-light rounded-1 mb-3">
                    <div style="font-size: 4rem;">📄</div>
                    <span class="badge bg-primary mt-2"><?php echo htmlspecialchars($doc['category_name'] ?? 'Official Document'); ?></span>
                </div>
                <div class="d-grid gap-2 no-print">
                    <button type="button" onclick="window.print()" class="btn btn-print py-2">🖨️ PRINT CHECKLIST</button>
                    <a href="<?php echo htmlspecialchars($safe_url); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-apply py-2">⚡ APPLY ON OFFICIAL PORTAL</a>
                </div>
            </div>

            <div class="col-md-8">
                <h3 class="fw-bold mb-2"><?php echo htmlspecialchars($doc['doc_name']); ?></h3>
                <div class="mb-3 d-flex flex-wrap gap-2">
                    <span class="time-badge">⏱️ <?php echo htmlspecialchars($doc['processing_time']); ?></span>
                    <span class="fee-badge">💰 Fees: <?php echo htmlspecialchars($doc['application_fees'] ?? 'Check Official Portal'); ?></span>
                </div>
                <p class="text-muted"><?php echo htmlspecialchars($doc['overview']); ?></p>

                <hr>

                <h6 class="fw-bold text-uppercase text-secondary">Eligibility Criteria</h6>
                <p class="bg-light p-3 rounded-1"><?php echo htmlspecialchars($doc['eligibility']); ?></p>

                <h6 class="fw-bold text-uppercase text-secondary mt-4 mb-3">Required Documents Checklist (Tick what you have)</h6>
                <?php if ($reqs->num_rows > 0): ?>
                    <?php while ($r = $reqs->fetch_assoc()): ?>
                        <label class="proof-row">
                            <div class="d-flex align-items-center gap-2">
                                <input type="checkbox" class="form-check-input mt-0">
                                <span class="fw-semibold"><?php echo htmlspecialchars($r['proof_name']); ?></span>
                            </div>
                            <span class="badge <?php echo $r['proof_type'] === 'Mandatory' ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle text-secondary'; ?>">
                                <?php echo htmlspecialchars($r['proof_type']); ?>
                            </span>
                        </label>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted">No proofs listed yet.</p>
                <?php endif; ?>

                <h6 class="fw-bold text-uppercase text-secondary mt-4 mb-3">Application Guide (Steps)</h6>
                <?php if ($steps->num_rows > 0): ?>
                    <ol class="list-group list-group-numbered mb-4">
                        <?php while ($st = $steps->fetch_assoc()): ?>
                            <li class="list-group-item"><?php echo htmlspecialchars($st['instruction']); ?></li>
                        <?php endwhile; ?>
                    </ol>
                <?php else: ?>
                    <p class="text-muted small mb-4">Follow the official portal instructions after gathering your required proofs.</p>
                <?php endif; ?>

                <h6 class="fw-bold text-uppercase text-secondary mt-4 mb-3">Frequently Asked Questions (FAQs)</h6>
                <?php if ($faqs->num_rows > 0): ?>
                    <div class="accordion mb-4" id="faqAccordion">
                        <?php $i = 0; while ($f = $faqs->fetch_assoc()): $i++; ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button <?php echo $i > 1 ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq<?php echo intval($f['faq_id']); ?>">
                                        <?php echo htmlspecialchars($f['question']); ?>
                                    </button>
                                </h2>
                                <div id="faq<?php echo intval($f['faq_id']); ?>" class="accordion-collapse collapse <?php echo $i === 1 ? 'show' : ''; ?>" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body small text-muted">
                                        <?php echo htmlspecialchars($f['answer']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-4">No FAQs added for this document yet.</p>
                <?php endif; ?>

                <hr class="no-print">
                <div class="no-print">
                    <h6 class="fw-bold text-uppercase text-secondary mb-2">Citizen Feedback</h6>
                    <?php if ($feedback_msg): ?>
                        <div class="alert alert-success py-2 small"><?php echo htmlspecialchars($feedback_msg); ?></div>
                    <?php endif; ?>
                    <?php if ($feedback_err): ?>
                        <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($feedback_err); ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="mb-2">
                            <input type="text" name="user_name" class="form-control form-control-sm" placeholder="Your Name" value="<?php echo htmlspecialchars(current_user_name()); ?>" required>
                        </div>
                        <div class="mb-2">
                            <textarea name="message" class="form-control form-control-sm" rows="2" placeholder="Found an outdated rule or have a suggestion for this guide?" required></textarea>
                        </div>
                        <button type="submit" name="submit_feedback" class="btn btn-primary btn-sm fw-bold px-3">Send Feedback</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>